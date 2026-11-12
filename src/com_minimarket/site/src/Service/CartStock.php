<?php
namespace MiniMarket\Component\Minimarket\Site\Service;
\defined('_JEXEC') or die;

/**
 * Pure combined-demand check: regular SKUs and bundle components compete for
 * the same real on-hand quantity, including shared-stock variants.
 * Never reserves, changes, or deducts stock.
 */
final class CartStock
{
    /**
     * @param array<int,array{product_id:int,variant_id:int,quantity:int}> $rows
     * @param array $snapshot BundleStock::snapshot() result
     */
    public static function assertAvailable(array $rows, array $snapshot, bool $inventoryEnabled): void
    {
        $products = $snapshot['products'] ?? [];
        $categories = $snapshot['categories'] ?? [];
        $variants = $snapshot['variants'] ?? [];
        $hasVariants = $snapshot['hasVariants'] ?? [];
        $bundles = $snapshot['items'] ?? [];
        $demand = [];

        $visible = static function (int $id) use ($products,$categories): bool {
            $p = $products[$id] ?? null;
            if (!$p || (int)$p['state'] !== 1) return false;
            $category = (int)$p['category_id'];
            $seen = [];
            while ($category > 0) {
                if (isset($seen[$category])) return false;
                $seen[$category] = true;
                $c = $categories[$category] ?? null;
                if (!$c || (int)$c['state'] !== 1) return false;
                $category = (int)$c['parent_id'];
            }
            return true;
        };

        $consume = static function (int $id, int $variantId, int $qty)
            use (&$demand,$products,$variants,$hasVariants,$visible): void {
            if ($qty < 1 || $qty > 500000 || !$visible($id)
                || ($products[$id]['product_type'] ?? '') !== 'simple') {
                throw new \DomainException('COM_MINIMARKET_CART_NOT_AVAILABLE');
            }
            // Digital items have no physical stock demand. Their published
            // state and variant selection are still validated here.
            $digital=(string)($products[$id]['delivery_type']??'physical')==='digital';
            if ($variantId > 0) {
                $variant = $variants[$variantId] ?? null;
                if (!$variant || (int)$variant['product_id'] !== $id
                    || (int)$variant['state'] !== 1) {
                    throw new \DomainException('COM_MINIMARKET_CART_NOT_AVAILABLE');
                }
                $key = (int)$products[$id]['option_stock_mode'] === 0
                    ? 'p:'.$id : 'v:'.$variantId;
            } else {
                if (isset($hasVariants[$id])) {
                    throw new \DomainException('COM_MINIMARKET_CART_NOT_AVAILABLE');
                }
                $key = 'p:'.$id;
            }
            if(!$digital)$demand[$key] = ($demand[$key] ?? 0) + $qty;
        };

        $walk = static function (int $id, int $qty, int $depth, array $path)
            use (&$walk,$products,$bundles,$visible,$consume): void {
            if ($depth > 8 || isset($path[$id]) || !$visible($id)
                || ($products[$id]['product_type'] ?? '') !== 'bundle') {
                throw new \DomainException('COM_MINIMARKET_CART_NOT_AVAILABLE');
            }
            $members = $bundles[$id] ?? [];
            if (!$members || count($members) > 40) {
                throw new \DomainException('COM_MINIMARKET_CART_NOT_AVAILABLE');
            }
            $path[$id] = true;
            foreach ($members as $member) {
                $needed = $qty * (int)$member['quantity'];
                if ((int)$member['quantity'] < 1 || $needed > 500000) {
                    throw new \DomainException('COM_MINIMARKET_CART_LIMIT_ERROR');
                }
                $child = (int)$member['component_product_id'];
                if (($products[$child]['product_type'] ?? '') === 'bundle') {
                    if ($member['component_variant_id'] !== null) {
                        throw new \DomainException('COM_MINIMARKET_CART_NOT_AVAILABLE');
                    }
                    $walk($child, $needed, $depth + 1, $path);
                } else {
                    $consume($child, (int)($member['component_variant_id'] ?? 0), $needed);
                }
            }
        };

        foreach ($rows as $row) {
            $id = (int)$row['product_id'];
            $variant = (int)$row['variant_id'];
            $qty = (int)$row['quantity'];
            if ($qty < 1 || $qty > CartState::MAX_QUANTITY || !$visible($id)) {
                throw new \DomainException('COM_MINIMARKET_CART_NOT_AVAILABLE');
            }
            if (($products[$id]['product_type'] ?? '') === 'bundle') {
                if ($variant !== 0) throw new \DomainException('COM_MINIMARKET_CART_NOT_AVAILABLE');
                $walk($id,$qty,0,[]);
            } else {
                $consume($id,$variant,$qty);
            }
        }

        if (!$inventoryEnabled) return;
        foreach ($demand as $key => $requested) {
            [$type,$id] = explode(':',$key,2);
            $stock = $type === 'v'
                ? (int)($variants[(int)$id]['stock'] ?? 0)
                : (int)($products[(int)$id]['stock'] ?? 0);
            if ($requested > $stock) {
                throw new \DomainException('COM_MINIMARKET_CART_STOCK_ERROR');
            }
        }
    }
}
