<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;
\defined('_JEXEC') or die;
/** Preset text values with optional sample images; legacy string arrays remain readable. */
final class TextPresetRows {
    public const MAX_ROWS = 200;

    /** @return array<int,array{value:string,image:string}> */
    public static function rows(string $json): array {
        if (trim($json)==='') return [];
        try { return self::normalizeRows(json_decode($json,true,512,JSON_THROW_ON_ERROR)); }
        catch (\JsonException $e) { throw new \InvalidArgumentException('COM_MINIMARKET_TEXT_PRESETS_INVALID'); }
    }
    /** @return string[] */
    public static function values(string $json): array { return array_column(self::rows($json),'value'); }
    /** @return string[] */
    public static function normalize(mixed $input): array {
        if (is_string($input)) return self::values($input);
        return array_column(self::normalizeRows($input),'value');
    }
    /** @return array<int,array{value:string,image:string}> */
    public static function normalizeRows(mixed $input): array {
        if ($input===null || $input==='') return [];
        if (!is_array($input) || count($input)>self::MAX_ROWS)
            throw new \InvalidArgumentException('COM_MINIMARKET_TEXT_PRESETS_INVALID');
        $rows=[];$seen=[];
        foreach ($input as $row) {
            if (is_object($row)) $row=(array)$row;
            $value=is_array($row) ? ($row['value']??null) : $row;
            $image=is_array($row) ? trim((string)($row['image']??'')) : '';
            if (!is_string($value) && !is_int($value) && !is_float($value))
                throw new \InvalidArgumentException('COM_MINIMARKET_TEXT_PRESETS_INVALID');
            $value=trim((string)$value);
            if ($value==='') {
                if ($image!=='') throw new \InvalidArgumentException('COM_MINIMARKET_TEXT_PRESETS_INVALID');
                continue;
            }
            if (mb_strlen($value)>255 || preg_match('/[\x00-\x1f<>]/u',$value))
                throw new \InvalidArgumentException('COM_MINIMARKET_TEXT_PRESETS_INVALID');
            if ($image!=='') {
                $fragment=strpos($image,'#');
                if ($fragment!==false) {
                    if (!str_starts_with(substr($image,$fragment),'#joomlaImage://'))
                        throw new \InvalidArgumentException('COM_MINIMARKET_TEXT_PRESETS_INVALID');
                    $image=substr($image,0,$fragment);
                }
                if (strlen($image)>1024 || !str_starts_with($image,'images/') || str_contains($image,'..')
                    || preg_match('~[\x00-\x1f<>?#\\\\]~',$image))
                    throw new \InvalidArgumentException('COM_MINIMARKET_TEXT_PRESETS_INVALID');
            }
            $key=mb_strtolower($value);
            if (isset($seen[$key])) throw new \InvalidArgumentException('COM_MINIMARKET_TEXT_PRESETS_INVALID');
            $seen[$key]=true; $rows[]=['value'=>$value,'image'=>$image];
        }
        return $rows;
    }
    public static function encode(mixed $input): string {
        if (is_string($input)) {
            try { $input=json_decode($input,true,512,JSON_THROW_ON_ERROR); }
            catch (\JsonException $e) { throw new \InvalidArgumentException('COM_MINIMARKET_TEXT_PRESETS_INVALID'); }
        }
        $rows=self::normalizeRows($input??[]);
        $encoded=array_map(static fn(array $r): string|array=>$r['image']===''?$r['value']:$r,$rows);
        return json_encode($encoded,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
    }
    /** @return array<string,array{value:string,image:string}> */
    public static function formRows(string $json): array {
        $result=[];
        foreach (self::rows($json) as $row) $result['row'.count($result)]=$row;
        return $result;
    }
}
