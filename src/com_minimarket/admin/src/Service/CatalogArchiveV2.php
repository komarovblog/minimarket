<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * Complete catalog JSON archive v2. Stores all 32 MiniMarket tables including
 * exact SKU IDs, images/links, bundles, options, videos and SEO text.
 * Binary media files are NOT included. Never drops, deletes or overwrites rows.
 * Intended for fresh catalogs and exact, idempotent reimports of a snapshot.
 */
final class CatalogArchiveV2
{
    public const MAX_BYTES = 12582912;
    public const MAX_ROWS = 50000;
    public const MAX_ROWS_PER_TABLE = 20000;

    // Dependency order: parents always precede FK children.
    public const TABLES = [
        'categories','brands','collections','products',
        'product_collections','product_images','product_videos',
        'attributes','attribute_values','category_attributes',
        'product_attribute_scalars','product_attribute_options',
        'product_attribute_multi_values','product_attribute_free_values',
        'product_variants','variant_dimensions','variant_dimension_values',
        'variant_value_assignments','variant_value_images',
        'product_variant_images','product_variant_videos','product_image_variants',
        'bundle_items','product_groups','product_group_members',
        'import_product_keys','import_sku_keys','import_group_keys',
        'order_options','order_option_values','category_order_options',
        'import_entity_keys',
    ];

    private static function table(DatabaseInterface $db, string $suffix): string
    {
        return $db->quoteName('#__minimarket_' . $suffix);
    }

    /** Names and MySQL column types, with no user-derived identifiers. */
    private static function metadata(DatabaseInterface $db, string $suffix): array
    {
        $db->setQuery('SHOW COLUMNS FROM ' . self::table($db, $suffix));
        $raw=(array)$db->loadAssocList();
        if (!$raw) throw new \RuntimeException('COM_MINIMARKET_EXCHANGE_SCHEMA');
        $columns=[];
        foreach($raw as $item) {
            $columns[(string)$item['Field']] = strtolower((string)$item['Type']);
        }
        return $columns;
    }

    private static function primary(DatabaseInterface $db, string $suffix): array
    {
        $db->setQuery("SHOW INDEX FROM " . self::table($db, $suffix)
            . " WHERE Key_name = 'PRIMARY'");
        $indices=(array)$db->loadAssocList();
        $keys=[];
        foreach($indices as $index) {
            $keys[(int)$index['Seq_in_index']] = (string)$index['Column_name'];
        }
        ksort($keys, SORT_NUMERIC);
        if (!$keys) throw new \RuntimeException('COM_MINIMARKET_EXCHANGE_SCHEMA');
        return array_values($keys);
    }

    /** Collect only installed component tables, in exact column/PK identity. */
    public static function document(DatabaseInterface $db): array
    {
        $schema=[]; $tables=[]; $total=0;
        foreach(self::TABLES as $suffix) {
            $schema[$suffix]=self::metadata($db, $suffix);
            $keys=self::primary($db, $suffix);
            $db->setQuery('SELECT * FROM ' . self::table($db,$suffix)
                . ' ORDER BY ' . implode(',',array_map([$db,'quoteName'],$keys)),
                0, self::MAX_ROWS_PER_TABLE + 1);
            $rows=(array)$db->loadAssocList();
            $total+=count($rows);
            if (count($rows)>self::MAX_ROWS_PER_TABLE || $total>self::MAX_ROWS)
                throw new \RuntimeException('COM_MINIMARKET_EXCHANGE_LIMIT');
            $tables[$suffix]=$rows;
        }
        $doc=['format'=>'minimarket-full','version'=>2,'schema'=>$schema,
            'tables'=>$tables];
        $data=json_encode($doc, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES);
        if (strlen($data)>self::MAX_BYTES)
            throw new \RuntimeException('COM_MINIMARKET_EXCHANGE_LIMIT');
        $doc['digest']=hash('sha256',$data);
        return $doc;
    }

    public static function decode(string $raw): array
    {
        if (strlen($raw)>self::MAX_BYTES || $raw==='') {
            throw new \InvalidArgumentException('COM_MINIMARKET_EXCHANGE_LIMIT');
        }
        try { $doc=json_decode($raw,true,32,JSON_THROW_ON_ERROR); }
        catch (\JsonException) {
            throw new \InvalidArgumentException('COM_MINIMARKET_EXCHANGE_SCHEMA');
        }
        if (!is_array($doc) || array_keys($doc)!==
            ['format','version','schema','tables','digest']
            || $doc['format']!=='minimarket-full' || $doc['version']!==2
            || !is_array($doc['schema']) || !is_array($doc['tables'])
            || !is_string($doc['digest'])
            || !preg_match('/^[a-f0-9]{64}$/D',$doc['digest'])) {
            throw new \InvalidArgumentException('COM_MINIMARKET_EXCHANGE_SCHEMA');
        }
        $expected=self::TABLES;
        if (array_keys($doc['schema'])!==$expected
            || array_keys($doc['tables'])!==$expected)
            throw new \InvalidArgumentException('COM_MINIMARKET_EXCHANGE_SCHEMA');
        $digest=$doc['digest'];
        unset($doc['digest']);
        $canonical=json_encode($doc,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES);
        if (!hash_equals(hash('sha256',$canonical),$digest))
            throw new \InvalidArgumentException('COM_MINIMARKET_EXCHANGE_SCHEMA');
        $doc['digest']=$digest;
        $all=0;
        foreach(self::TABLES as $suffix) {
            $types=$doc['schema'][$suffix];
            $rows=$doc['tables'][$suffix];
            if (!is_array($types) || array_is_list($types) || !$types
                || !is_array($rows) || !array_is_list($rows)
                || count($rows)>self::MAX_ROWS_PER_TABLE)
                throw new \InvalidArgumentException('COM_MINIMARKET_EXCHANGE_SCHEMA');
            $cols=array_keys($types);
            foreach($cols as $key) {
                if (!preg_match('/^[a-z][a-z0-9_]*$/D',$key)
                    || !is_string($types[$key]))
                    throw new \InvalidArgumentException('COM_MINIMARKET_EXCHANGE_SCHEMA');
            }
            $all+=count($rows);
            if($all>self::MAX_ROWS)
                throw new \InvalidArgumentException('COM_MINIMARKET_EXCHANGE_LIMIT');
            foreach($rows as $row) {
                if(!is_array($row) || array_keys($row)!==$cols)
                    throw new \InvalidArgumentException('COM_MINIMARKET_EXCHANGE_SCHEMA');
                foreach($row as $value) {
                    if ($value!==null && !is_scalar($value))
                        throw new \InvalidArgumentException('COM_MINIMARKET_EXCHANGE_SCHEMA');
                    if (is_string($value)
                        && (strlen($value)>1048576 || str_contains($value,"\\0"))) {
                        throw new \InvalidArgumentException('COM_MINIMARKET_EXCHANGE_LIMIT');
                    }
                }
            }
        }
        return $doc;
    }

    /**
     * Preview inserts under a transaction and ALWAYS rolls back. Apply inserts
     * missing records only. Existing matching rows are a no-op; any different
     * row (or natural key collision) aborts the whole transaction.
     */
    public static function run(DatabaseInterface $db, array $doc, bool $apply): array
    {
        $doc=self::decode(json_encode($doc,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES));
        $report=[];
        foreach(self::TABLES as $suffix) {
            if (self::metadata($db,$suffix)!==$doc['schema'][$suffix])
                throw new \RuntimeException('COM_MINIMARKET_EXCHANGE_SCHEMA');
        }
        $db->transactionStart();
        try {
            foreach(self::TABLES as $suffix) {
                $keys=self::primary($db,$suffix);
                $columns=array_keys($doc['schema'][$suffix]);
                $created=0;$unchanged=0;
                foreach($doc['tables'][$suffix] as $row) {
                    $where=[];
                    foreach($keys as $key) {
                        $value=$row[$key]??null;
                        $where[]=$db->quoteName($key)
                            .($value===null?' IS NULL':' = '.$db->quote((string)$value));
                    }
                    $db->setQuery('SELECT * FROM '.self::table($db,$suffix)
                        .' WHERE '.implode(' AND ',$where));
                    $existing=$db->loadAssoc();
                    if ($existing!==null && $existing!==false) {
                        foreach($columns as $key) {
                            $a=$existing[$key]??null;$b=$row[$key]??null;
                            if (!(($a===null && $b===null)
                                || ($a!==null && $b!==null && (string)$a===(string)$b))) {
                                throw new \RuntimeException('COM_MINIMARKET_EXCHANGE_CONFLICT');
                            }
                        }
                        $unchanged++;
                        continue;
                    }
                    $names=array_map([$db,'quoteName'],$columns);
                    $values=array_map(
                        static fn($v)=>$v===null?'NULL':$db->quote((string)$v),
                        array_values($row)
                    );
                    $db->setQuery('INSERT INTO '.self::table($db,$suffix)
                        .' ('.implode(',',$names).') VALUES ('.implode(',',$values).')');
                    $db->execute();
                    $created++;
                }
                $report[$suffix]=['created'=>$created,'unchanged'=>$unchanged];
            }
            if ($apply) $db->transactionCommit();
            else $db->transactionRollback();
        } catch (\Throwable $error) {
            $db->transactionRollback();
            if ($error instanceof \RuntimeException
                && str_starts_with($error->getMessage(),'COM_MINIMARKET_EXCHANGE_')) {
                throw $error;
            }
            throw new \RuntimeException('COM_MINIMARKET_EXCHANGE_CONFLICT',0,$error);
        }
        return $report;
    }
}
