<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;
\defined('_JEXEC') or die;

/**
 * Per-category card visibility, attached to the EXISTING category property
 * assignment. A 'v<id>' form key accompanies the normal integer mode.
 * Separate keys retain backwards-compatible category mode payloads.
 */
final class CategoryListVisibility
{
    public static function modesOnly(mixed $submitted): mixed
    {
        if (!is_array($submitted)) return $submitted;
        foreach (array_keys($submitted) as $key) {
            if (preg_match('/^v[1-9][0-9]*$/D', (string)$key)) unset($submitted[$key]);
        }
        return $submitted;
    }

    /** @return array<int,int> Explicit show_in_list values; no implicit clears. */
    public static function flags(mixed $submitted, array $allowedIds): array
    {
        if ($submitted===null || $submitted==='') return [];
        if (!is_array($submitted)) throw new \InvalidArgumentException('COM_MINIMARKET_LIST_VISIBILITY_INVALID');
        $allowed=array_fill_keys(array_map('intval',$allowedIds),true);
        $result=[];
        foreach ($submitted as $key=>$value) {
            if (!str_starts_with((string)$key,'v')) continue;
            if (!preg_match('/^v([1-9][0-9]*)$/D',(string)$key,$m)
                || !isset($allowed[(int)$m[1]]) || !in_array((string)$value,['0','1'],true))
                throw new \InvalidArgumentException('COM_MINIMARKET_LIST_VISIBILITY_INVALID');
            $result[(int)$m[1]]=(int)$value;
        }
        return $result;
    }

    public static function formValues(array $modes,array $flags): array
    {
        foreach ($modes as $id=>$_) $modes['v'.(int)$id]=(int)($flags[(int)$id]??1);
        return $modes;
    }

    /**
     * Resolve effective linked options and their card visibility; a child can
     * exclude, re-enable or override the inherited flag without copying data.
     * @return array<int,bool> enabled property_id => list visibility
     */
    public static function resolve(int $categoryId,array $parents,array $rules,array $flags): array
    {
        if ($categoryId<1) return [];
        $seen=[];$chain=[];
        for($id=$categoryId;$id>0;$id=(int)($parents[$id]??-1)) {
            if (!array_key_exists($id,$parents)||isset($seen[$id])) return [];
            $seen[$id]=true;$chain[]=$id;
        }
        $result=[];
        foreach (array_reverse($chain) as $node) {
            foreach ($rules[$node]??[] as $propertyId=>$mode) {
                if ((int)$mode===0) unset($result[(int)$propertyId]);
                elseif (in_array((int)$mode,[1,2],true)) {
                    $result[(int)$propertyId]=(int)($flags[$node][$propertyId]??1)===1;
                }
            }
        }
        return $result;
    }
}
