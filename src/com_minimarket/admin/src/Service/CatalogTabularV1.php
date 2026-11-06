<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;

\defined('_JEXEC') or die;

/**
 * Tabular transport for the documented CatalogImport v1 fields only.
 * Every representation is converted back to exactly the validated v1 document;
 * it does NOT bypass the existing preview/transaction/identity checks.
 * All XLSX cells are text (inlineStr), preserving SKUs with leading zeroes.
 */
final class CatalogTabularV1
{
    public const FIELDS = [
        'categories' => ['key','title','alias','parent','state'],
        'attributes' => ['key','title','code','type','state','values'],
        'options' => ['key','title','code','state','values'],
        'products' => ['key','title','alias','sku','price','old_price','badge_sale','stock','stock_mode','category','state'],
        'bundles' => ['key','title','alias','sku','category','price','old_price','price_mode','discount_pct','badge_sale','badge_benefit','state','items'],
        'links' => ['key','title','members','state'],
        'category_attributes' => ['key','category','attribute','mode'],
        'category_options' => ['key','category','option','mode'],
    ];
    private const COMMON = ['section','source','adopt_existing'];
    private const LIMIT = 3145728;

    private static function fail(): never
    {
        throw new \InvalidArgumentException('COM_MINIMARKET_IMPORT_SCHEMA_INVALID');
    }

    /** Flatten an existing validated v1 package for spreadsheet interchange. */
    private static function displayCell(mixed $value): string
    {
        if (is_array($value)) return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        if (is_bool($value)) return $value ? '1' : '0';
        return $value === null ? '' : (string) $value;
    }

    /**
     * CSV opens in spreadsheet software as active formula cells unless we
     * prepend an apostrophe. Undo only our own sentinel on re-import.
     */
    private static function csvSafe(string $value): string
    {
        return preg_match('/^[\s]*[=+@-]/u', $value) ? "'" . $value : $value;
    }

    private static function csvUnprotect(string $value): string
    {
        return str_starts_with($value,"'")
            && preg_match('/^[\s]*[=+@-]/u',substr($value,1))
            ? substr($value,1) : $value;
    }

    private static function decodeField(string $section, string $name, string $value): mixed
    {
        if (in_array($name, ['values','members','items'], true)) {
            if ($value === '') return [];
            try { $parsed = json_decode($value, true, 40, JSON_THROW_ON_ERROR); }
            catch (\JsonException) { self::fail(); }
            if (!is_array($parsed) || !array_is_list($parsed)) self::fail();
            return $parsed;
        }
        if ($name === 'parent' && $section === 'categories') {
            return $value === '' ? null : $value;
        }
        if (in_array($name, ['state','stock','stock_mode','badge_sale','badge_benefit'], true)) {
            if (!preg_match('/^(0|[1-9][0-9]*)$/D', $value)) self::fail();
            $parsed = filter_var($value, FILTER_VALIDATE_INT);
            if ($parsed === false) self::fail();
            return $parsed;
        }
        return $value;
    }

    private static function newDoc(string $source, bool $adopt): array
    {
        if (!preg_match('/^[a-z][a-z0-9_-]{0,63}$/D', $source)) self::fail();
        $doc = ['version'=>1,'source'=>$source];
        if ($adopt) $doc['adopt_existing'] = true;
        foreach (array_keys(self::FIELDS) as $section) $doc[$section] = [];
        return $doc;
    }

    private static function rowToEntity(string $section, array $row): ?array
    {
        if (!isset(self::FIELDS[$section])) self::fail();
        if (count(array_filter($row, static fn($x): bool => trim((string)$x) !== '')) === 0) return null;
        $out = [];
        foreach (self::FIELDS[$section] as $field) {
            $value = trim((string) ($row[$field] ?? ''));
            // Optional blank values must be absent; false/zero must survive.
            if ($value === '' && $field !== 'parent') continue;
            $out[$field] = self::decodeField($section, $field, $value);
        }
        if (empty($out['key'])) self::fail();
        return $out;
    }

    /** One ordinary UTF-8 CSV: section + column names; nested lists use JSON cells. */
    public static function csv(array $doc): string
    {
        $columns = array_values(array_unique(array_merge(self::COMMON, ...array_values(self::FIELDS))));
        $stream = fopen('php://temp', 'w+');
        if (!$stream) self::fail();
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, $columns, ',', '"', '');
        $meta = ['section'=>'meta','source'=>(string)$doc['source'],
            'adopt_existing'=>!empty($doc['adopt_existing']) ? '1' : '0'];
        fputcsv($stream, array_map(static fn($name) => $meta[$name] ?? '', $columns), ',', '"', '');
        foreach (self::FIELDS as $section => $_) {
            foreach ($doc[$section] ?? [] as $row) {
                $data = ['section'=>$section];
                foreach ($row as $key=>$value) $data[$key] = self::displayCell($value);
                fputcsv($stream, array_map(static fn($name) => self::csvSafe((string)($data[$name] ?? '')), $columns), ',', '"', '');
            }
        }
        rewind($stream);
        $out = (string)stream_get_contents($stream);
        fclose($stream);
        if (strlen($out) > self::LIMIT) self::fail();
        return $out;
    }

    public static function parseCsv(string $raw): array
    {
        if (strlen($raw) > self::LIMIT || $raw === '') self::fail();
        $stream = fopen('php://temp', 'w+');
        if (!$stream) self::fail();
        fwrite($stream, $raw);
        rewind($stream);
        $header = fgetcsv($stream, 0, ',', '"', '');
        if (!is_array($header)) self::fail();
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        if (count($header) !== count(array_unique($header))
            || !in_array('section',$header,true)
            || !in_array('source',$header,true)
            || !in_array('key',$header,true)
            || array_diff($header, array_values(array_unique(array_merge(self::COMMON,...array_values(self::FIELDS)))))) self::fail();
        $doc = null;
        $count = 0;
        while (($values = fgetcsv($stream, 0, ',', '"', '')) !== false) {
            if (++$count > 2501 || count($values) !== count($header)) self::fail();
            $row = array_combine($header, array_map(self::csvUnprotect(...), $values));
            if (!is_array($row)) self::fail();
            $section = trim((string)($row['section'] ?? ''));
            if ($section === 'meta') {
                if ($doc !== null) self::fail();
                $adopt = (string)($row['adopt_existing'] ?? '0');
                if ($adopt !== '0' && $adopt !== '1' && $adopt !== '') self::fail();
                $doc = self::newDoc(trim((string)$row['source']), $adopt === '1');
                continue;
            }
            if ($doc === null || !isset(self::FIELDS[$section])) self::fail();
            foreach ($row as $field=>$val) {
                if (trim((string)$val) !== '' && !in_array($field, array_merge(['section'],self::FIELDS[$section]),true)) {
                    self::fail();
                }
            }
            $entry = self::rowToEntity($section, $row);
            if ($entry !== null) $doc[$section][] = $entry;
        }
        fclose($stream);
        if ($doc === null) self::fail();
        return $doc;
    }

    private static function xml(string $v): string
    {
        return htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function col(int $number): string
    {
        $out = '';
        do {
            $out = chr(65 + $number % 26) . $out;
            $number = intdiv($number, 26)-1;
        } while ($number >= 0);
        return $out;
    }

    private static function sheetXml(array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<cols><col min="1" max="20" width="22" customWidth="1" style="1"/></cols>'
            . '<sheetData>';
        foreach ($rows as $i=>$row) {
            $xml .= '<row r="' . ($i+1) . '">';
            foreach ($row as $j=>$cell) {
                $value = self::displayCell($cell);
                if ($value === '') continue;
                if (mb_strlen($value) > 32767 || !mb_check_encoding($value,'UTF-8')) self::fail();
                $xml .= '<c r="'.self::col($j).($i+1).'" t="inlineStr" s="1"><is><t xml:space="preserve">'
                    . self::xml($value) . '</t></is></c>';
            }
            $xml .= '</row>';
        }
        return $xml.'</sheetData></worksheet>';
    }

    /** Self-contained OOXML XLSX, no Composer dependencies or network access. */
    public static function xlsx(array $doc): string
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('COM_MINIMARKET_EXCHANGE_ZIP_REQUIRED');
        }
        $sheets = [
            'meta'=>[['field','value'],['version','1'],['source',$doc['source']],
                ['adopt_existing',!empty($doc['adopt_existing'])?'1':'0']],
        ];
        foreach (self::FIELDS as $section=>$fields) {
            $rows = [$fields];
            foreach ($doc[$section] ?? [] as $entity) {
                $rows[] = array_map(static fn($field) => self::displayCell($entity[$field] ?? ''), $fields);
            }
            $sheets[$section] = $rows;
        }
        $tmp = tempnam(sys_get_temp_dir(),'mm-xlsx-');
        if (!$tmp) self::fail();
        try {
            $zip = new \ZipArchive();
            if ($zip->open($tmp, \ZipArchive::OVERWRITE) !== true) self::fail();
            $types = '<?xml version="1.0" encoding="UTF-8"?>'
                . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                . '<Default Extension="xml" ContentType="application/xml"/>'
                . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
            $book = '<?xml version="1.0" encoding="UTF-8"?>'
                . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
                . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';
            $rels = '<?xml version="1.0" encoding="UTF-8"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
            $index=0;
            foreach ($sheets as $name=>$rows) {
                ++$index;
                $types .= '<Override PartName="/xl/worksheets/sheet'.$index.'.xml"'
                    .' ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
                $book .= '<sheet name="'.self::xml($name).'" sheetId="'.$index.'" r:id="rId'.$index.'"/>';
                $rels .= '<Relationship Id="rId'.$index.'"'
                    .' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet"'
                    .' Target="worksheets/sheet'.$index.'.xml"/>';
                $zip->addFromString('xl/worksheets/sheet'.$index.'.xml',self::sheetXml($rows));
            }
            $rels .= '<Relationship Id="rIdStyles" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
            $zip->addFromString('xl/styles.xml',
                '<?xml version="1.0" encoding="UTF-8"?>'
                . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
                . '<fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>'
                . '<fills count="2"><fill><patternFill patternType="none"/></fill>'
                . '<fill><patternFill patternType="gray125"/></fill></fills>'
                . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
                . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
                . '<cellXfs count="2">'
                . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
                . '<xf numFmtId="49" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
                . '</cellXfs></styleSheet>');
            $zip->addFromString('[Content_Types].xml',$types.'</Types>');
            $zip->addFromString('xl/workbook.xml',$book.'</sheets></workbook>');
            $zip->addFromString('xl/_rels/workbook.xml.rels',$rels.'</Relationships>');
            $zip->addFromString('_rels/.rels',
                '<?xml version="1.0" encoding="UTF-8"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                .'</Relationships>');
            $zip->close();
            $out=(string)file_get_contents($tmp);
            if (strlen($out)>self::LIMIT) self::fail();
            return $out;
        } finally { @unlink($tmp); }
    }

    private static function doc(string $xml): \DOMDocument
    {
        if ($xml === '' || stripos($xml,'<!DOCTYPE')!==false
            || stripos($xml,'<!ENTITY')!==false) self::fail();
        $dom=new \DOMDocument();
        if (!$dom->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT)) self::fail();
        return $dom;
    }

    private static function fromColumn(string $name): int
    {
        if (!preg_match('/^([A-Z]{1,3})[1-9][0-9]*$/D',$name,$m)) self::fail();
        $value=0;
        foreach (str_split($m[1]) as $char) $value=$value*26+ord($char)-64;
        if ($value>32) self::fail();
        return $value-1;
    }

    private static function values(\DOMDocument $dom, array $shared): array
    {
        $xp = new \DOMXPath($dom);
        $results=[];
        $rows=$xp->query('//*[local-name()="sheetData"]/*[local-name()="row"]');
        if ($rows === false || $rows->length>2502) self::fail();
        foreach ($rows as $row) {
            $values=[];
            foreach ($xp->query('./*[local-name()="c"]',$row) as $cell) {
                if ($xp->query('./*[local-name()="f"]',$cell)->length) self::fail();
                $index=self::fromColumn((string)$cell->getAttribute('r'));
                if (isset($values[$index])) self::fail();
                $type=(string)$cell->getAttribute('t');
                if ($type==='s') {
                    $v=$xp->query('./*[local-name()="v"]',$cell)->item(0);
                    if (!$v || !ctype_digit(trim($v->textContent))) self::fail();
                    $key=(int)trim($v->textContent);
                    if (!isset($shared[$key])) self::fail();
                    $values[$index]=$shared[$key];
                } elseif ($type==='inlineStr') {
                    $text='';
                    foreach ($xp->query('.//*[local-name()="t"]',$cell) as $node) $text.=$node->textContent;
                    $values[$index]=$text;
                } elseif (in_array($type,['','n','str'],true)) {
                    $v=$xp->query('./*[local-name()="v"]',$cell)->item(0);
                    $values[$index]=$v?trim($v->textContent):'';
                } else self::fail(); // booleans, errors and formulas are not supported in a data sheet.
                if (strlen($values[$index])>100000) self::fail();
            }
            if ($values) {
                ksort($values);
                $last=(int)max(array_keys($values));
                $results[]=array_map(static fn($i)=>$values[$i]??'',range(0,$last));
            }
        }
        return $results;
    }

    public static function parseXlsx(string $raw): array
    {
        if (!class_exists(\ZipArchive::class) || !class_exists(\DOMDocument::class)) {
            throw new \RuntimeException('COM_MINIMARKET_EXCHANGE_ZIP_REQUIRED');
        }
        if ($raw==='' || strlen($raw)>self::LIMIT) self::fail();
        $tmp=tempnam(sys_get_temp_dir(),'mm-read-');
        if (!$tmp) self::fail();
        try {
            file_put_contents($tmp,$raw);
            $zip=new \ZipArchive();
            if ($zip->open($tmp)!==true) self::fail();
            try {
                if ($zip->numFiles>40) self::fail();
                $total=0;
                for ($i=0;$i<$zip->numFiles;$i++) {
                    $stat=$zip->statIndex($i);
                    $total += (int)($stat['size']??0);
                    if ($total>12000000 || (int)($stat['size']??0)>3000000) self::fail();
                }
                $book=self::doc((string)$zip->getFromName('xl/workbook.xml'));
                $rels=self::doc((string)$zip->getFromName('xl/_rels/workbook.xml.rels'));
                $xp=new \DOMXPath($rels);
                $paths=[];
                foreach ($xp->query('//*[local-name()="Relationship"]') as $rel) {
                    if ($rel->getAttribute('TargetMode')==='External') self::fail();
                    $target=$rel->getAttribute('Target');
                    if (preg_match('~^(?:/xl/|worksheets/)worksheets/~',$target)) self::fail();
                    if (str_starts_with($target,'/xl/worksheets/')) $target=substr($target,1);
                    elseif (str_starts_with($target,'worksheets/')) $target='xl/'.$target;
                    if (preg_match('~^xl/worksheets/sheet[0-9]+\.xml$~D',$target)) {
                        $paths[$rel->getAttribute('Id')]=$target;
                    }
                }
                $shared=[];
                $sharedXml=$zip->getFromName('xl/sharedStrings.xml');
                if (is_string($sharedXml)) {
                    $dom=self::doc($sharedXml);$xp=new \DOMXPath($dom);
                    foreach ($xp->query('//*[local-name()="si"]') as $entry) {
                        $text='';
                        foreach ($xp->query('.//*[local-name()="t"]',$entry) as $v) $text.=$v->textContent;
                        $shared[]=$text;
                        if (count($shared)>10000) self::fail();
                    }
                }
                $xp=new \DOMXPath($book);
                $sheets=[];
                $rns='http://schemas.openxmlformats.org/officeDocument/2006/relationships';
                foreach ($xp->query('//*[local-name()="sheets"]/*[local-name()="sheet"]') as $sheet) {
                    $name=$sheet->getAttribute('name');
                    if (isset($sheets[$name]) || ($name!=='meta' && !isset(self::FIELDS[$name]))) self::fail();
                    $id=$sheet->getAttributeNS($rns,'id');
                    if (!isset($paths[$id])) self::fail();
                    $data=$zip->getFromName($paths[$id]);
                    if (!is_string($data)) self::fail();
                    $sheets[$name]=self::values(self::doc($data),$shared);
                }
            } finally { $zip->close(); }
        } finally { @unlink($tmp); }
        $meta=$sheets['meta']??null;
        if (!is_array($meta) || ($meta[0]??null)!==['field','value']) self::fail();
        $settings=[];
        foreach (array_slice($meta,1) as $pair) {
            if (count($pair)<2 || isset($settings[$pair[0]])) self::fail();
            $settings[$pair[0]]=$pair[1];
        }
        if (($settings['version']??'')!=='1'
            || !in_array(($settings['adopt_existing']??'0'),['0','1'],true)) self::fail();
        $doc=self::newDoc((string)($settings['source']??''),
            ($settings['adopt_existing']??'0')==='1');
        $total=0;
        foreach (self::FIELDS as $section=>$fields) {
            $data=$sheets[$section]??null;
            if ($data===null) continue;
            if (($data[0]??null)!==$fields) self::fail();
            foreach (array_slice($data,1) as $line) {
                if (++$total>2500) self::fail();
                $row=[];
                foreach ($fields as $i=>$field) $row[$field]=(string)($line[$i]??'');
                $entity=self::rowToEntity($section,$row);
                if ($entity!==null) $doc[$section][]=$entity;
            }
        }
        return $doc;
    }
}
