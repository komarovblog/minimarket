<?php
namespace MiniMarket\Component\Minimarket\Site\Service;
\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use MiniMarket\Component\Minimarket\Administrator\Service\BundleStock;

/** All cart quotes are reread from published server data (never from a form). */
final class CartCatalog
{
    private CatalogRepository $repository;
    private ?array $snapshot = null;

    private function snapshot(): array
    {
        return $this->snapshot ??= BundleStock::snapshot($this->db);
    }

    /** Exact stock limit after accounting for every OTHER cart line. */
    public function maxQuantity(array $state,int $productId,int $variantId,bool $inventoryEnabled): int
    {
        $this->quote($productId,$variantId);
        if (!$inventoryEnabled) return CartState::MAX_QUANTITY;
        $others=CartState::normalize($state);
        unset($others[CartState::key($productId,$variantId)]);
        $rows=[];
        foreach ($others as $key=>$qty) {
            [$pid,$vid]=array_map('intval',explode(':',$key));
            $this->quote($pid,$vid);
            $rows[]=['product_id'=>$pid,'variant_id'=>$vid,'quantity'=>$qty];
        }
        $lo=0;$hi=CartState::MAX_QUANTITY;
        while ($lo<$hi) {
            $mid=intdiv($lo+$hi+1,2);
            try {
                CartStock::assertAvailable(array_merge($rows,[[
                    'product_id'=>$productId,'variant_id'=>$variantId,'quantity'=>$mid
                ]]),$this->snapshot(),true);
                $lo=$mid;
            } catch (\DomainException) {
                $hi=$mid-1;
            }
        }
        return $lo;
    }

    public function __construct(private DatabaseInterface $db)
    {
        $this->repository = new CatalogRepository($db);
    }

    /** @return array{product_id:int,variant_id:int,title:string,sku:string,image:string,unit_cents:int,stock:int,type:string} */
    public function quote(int $productId,int $variantId): array
    {
        $product = $this->repository->getProduct($productId);
        if ($product === null) throw new \DomainException('COM_MINIMARKET_CART_NOT_AVAILABLE');
        $type = (string)($product->product_type ?? 'simple');
        if (!in_array($type,['simple','bundle'],true)
            || (string)($product->delivery_type ?? 'physical') !== 'physical') {
            throw new \DomainException('COM_MINIMARKET_CART_NOT_AVAILABLE');
        }
        if ($type === 'bundle' && $variantId !== 0) {
            throw new \DomainException('COM_MINIMARKET_CART_NOT_AVAILABLE');
        }
        $sku = (string)($product->sku ?? '');
        $image = (string)($product->image ?? '');
        $title = (string)$product->title;
        $stock = (int)$product->stock;
        if ($type === 'simple') {
            $options = $this->repository->getProductVariants($productId,(int)$product->category_id);
            $items = $options['items'] ?? [];
            if ($items && $variantId === 0) {
                throw new \DomainException('COM_MINIMARKET_CART_CHOOSE_VARIANT');
            }
            if ($variantId > 0) {
                $found = null;
                foreach ($items as $variant) {
                    if ((int)$variant['id'] === $variantId) { $found = $variant; break; }
                }
                if ($found === null) throw new \DomainException('COM_MINIMARKET_CART_NOT_AVAILABLE');
                $sku = (string)$found['sku'];
                $stock = (int)($product->option_stock_mode ?? 1) === 0
                    ? (int)$product->stock : (int)$found['stock'];
                $cover = '';
                foreach ($found['gallery'] ?? [] as $photo) {
                    if (!empty($photo['cover'])) { $cover = (string)$photo['path']; break; }
                }
                $image = $cover ?: (string)($found['image'] ?: $image);
                $parts = [];
                foreach ($found['selection'] as $axis => $value) {
                    foreach ($options['axes'][$axis]['options'] ?? [] as $option) {
                        if ($option['value'] === $value) {
                            $parts[] = (string)$option['title']; break;
                        }
                    }
                }
                if ($parts) $title .= ' — '.implode(', ',$parts);
            }
        }
        $image = explode('#',$image,2)[0];
        if (!preg_match('~^images/[a-zA-Z0-9_./ -]+\.(?:png|jpe?g|gif|webp|avif)$~iD',$image)
            || str_contains($image,'..') || str_contains($image,'//')) $image = '';
        return [
            'product_id'=>$productId, 'variant_id'=>$variantId,
            'title'=>$title, 'sku'=>$sku, 'image'=>$image, 'stock'=>$stock,
            'type'=>$type, 'unit_cents'=>CartState::cents((string)$product->price),
            'shipping_weight_grams'=>(int)($product->shipping_weight_grams??0),
            'shipping_volume_cm3'=>(int)($product->shipping_volume_cm3??0),
        ];
    }

    /** @return array{lines:array,total_cents:int,units:int,available:bool,error:string} */
    public function details(array $state,bool $inventoryEnabled): array
    {
        $lines=[]; $total=0; $quantity=0; $rows=[];
        foreach (CartState::normalize($state) as $key=>$qty) {
            [$productId,$variantId] = array_map('intval',explode(':',$key));
            try {
                $line = $this->quote($productId,$variantId);
                $line['valid'] = true;
                $total += $line['unit_cents']*$qty;
                $rows[] = ['product_id'=>$productId,'variant_id'=>$variantId,'quantity'=>$qty];
            } catch (\DomainException) {
                $line = ['product_id'=>$productId,'variant_id'=>$variantId,
                    'title'=>'#'.$productId,'sku'=>'','image'=>'','stock'=>0,
                    'type'=>'invalid','unit_cents'=>0,'valid'=>false];
            }
            $line['quantity'] = $qty;
            $line['key'] = $key;
            $line['line_cents'] = $line['unit_cents']*$qty;
            $lines[] = $line;
            $quantity += $qty;
        }
        $error = '';
        $available = count($rows) === count($lines);
        if ($available) {
            try {
                CartStock::assertAvailable($rows, $this->snapshot(), $inventoryEnabled);
            } catch (\DomainException $exception) {
                $available = false; $error = $exception->getMessage();
            }
        } else {
            $error = 'COM_MINIMARKET_CART_NOT_AVAILABLE';
        }
        foreach ($lines as &$line) {
            $line['max_quantity']=0;
            if ($line['valid']) {
                try {
                    $line['max_quantity']=$this->maxQuantity(
                        $state,(int)$line['product_id'],(int)$line['variant_id'],$inventoryEnabled
                    );
                } catch (\DomainException) { /* Removed/unpublished lines remain removable. */ }
            }
        }
        unset($line);
        return ['lines'=>$lines,'total_cents'=>$total,'units'=>$quantity,
            'available'=>$available,'error'=>$error];
    }

    /** Re-validate whole cart, including shared stock and nested bundle items. */
    public function assertAvailable(array $cart,bool $inventoryEnabled): void
    {
        $details = $this->details($cart,$inventoryEnabled);
        if (!$details['available']) {
            throw new \DomainException($details['error'] ?: 'COM_MINIMARKET_CART_NOT_AVAILABLE');
        }
    }
}
