<?php
namespace MiniMarket\Component\Minimarket\Administrator\Service;
\defined('_JEXEC') or die;
use Joomla\Database\DatabaseInterface;

/**
 * Public XML sitemap for the chosen MiniMarket products and publicly
 * accessible published Joomla com_content articles. No private content or
 * draft/expired articles may be exposed.
 */
final class SitemapFeed
{
    public const MAX_URLS=5000;

    /** Joomla articles accessible to anonymous visitors only. */
    public static function publicArticles(DatabaseInterface $db): array
    {
        $content=$db->quoteName('#__content');
        $categories=$db->quoteName('#__categories');
        $sql='SELECT a.id,a.catid,a.modified,a.created FROM '.$content.' AS a'
            .' INNER JOIN '.$categories.' AS c ON c.id=a.catid'
            .' WHERE a.state=1 AND a.access=1'
            .' AND c.published=1 AND c.access=1'
            .' AND c.extension='.$db->quote('com_content')
            .' AND (a.publish_up IS NULL OR a.publish_up='
            .$db->quote('0000-00-00 00:00:00').' OR a.publish_up<=UTC_TIMESTAMP())'
            .' AND (a.publish_down IS NULL OR a.publish_down='
            .$db->quote('0000-00-00 00:00:00').' OR a.publish_down>=UTC_TIMESTAMP())'
            .' ORDER BY a.id ASC';
        $db->setQuery($sql,0,self::MAX_URLS+1);
        return (array)$db->loadAssocList();
    }

    /**
     * Resolve Joomla's routed URLs and keep links within the current site.
     * Supports subfolder installations and avoids XSS or off-origin entries.
     */
    public static function absolute(string $route,string $base): ?string
    {
        $site=parse_url($base);
        if(!is_array($site)||!in_array(strtolower((string)($site['scheme']??'')),['http','https'],true)
            || empty($site['host']))return null;
        $origin=strtolower($site['scheme']).'://'.$site['host']
            .(isset($site['port'])?':'.$site['port']:'');
        $prefix=rtrim((string)($site['path']??'/'),'/').'/';
        $route=trim(html_entity_decode($route,ENT_QUOTES|ENT_HTML5,'UTF-8'));
        if($route===''||preg_match('/[\x00-\x20<>]/',$route)||str_contains($route,'#'))return null;
        if(preg_match('~^https?://~i',$route))$url=$route;
        elseif(str_starts_with($route,'/'))$url=$origin.$route;
        elseif(str_starts_with($route,'index.php') || !str_contains($route,':'))
            $url=$origin.$prefix.ltrim($route,'/');
        else return null;
        $parsed=parse_url($url);
        if(!is_array($parsed)||!in_array(strtolower((string)($parsed['scheme']??'')),['http','https'],true)
            || strtolower((string)($parsed['host']??''))!==strtolower((string)$site['host'])
            || (int)($parsed['port']??(strtolower((string)$parsed['scheme'])==='https'?443:80))
                !==(int)($site['port']??(strtolower((string)$site['scheme'])==='https'?443:80))
            || isset($parsed['user']) || isset($parsed['pass']) || isset($parsed['fragment']))
            return null;
        $path=(string)($parsed['path']??'/');
        if(!str_starts_with($path,$prefix)&&$path!==rtrim($prefix,'/'))return null;
        return $url;
    }

    public static function lastModified(string $modified,string $created=''): string
    {
        foreach([$modified,$created] as $raw){
            if(!preg_match('/^(\d{4}-\d{2}-\d{2})[ T]/D',$raw,$m))continue;
            if($m[1]==='0000-00-00')continue;
            $date=\DateTimeImmutable::createFromFormat('!Y-m-d',$m[1]);
            if($date!==false && $date->format('Y-m-d')===$m[1])return $m[1];
        }
        return '';
    }

    /** @param array<int,array{loc:string,lastmod?:string}> $entries */
    public static function render(array $entries): string
    {
        if(count($entries)>self::MAX_URLS)
            throw new \InvalidArgumentException('Sitemap URL limit exceeded');
        $xml='<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $seen=[];
        foreach($entries as $entry){
            $url=(string)($entry['loc']??'');
            if(!preg_match('~^https?://[^/]+/~iD',$url)||isset($seen[$url]))continue;
            $seen[$url]=true;
            $xml.='<url><loc>'.htmlspecialchars($url,ENT_XML1|ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'</loc>';
            $date=(string)($entry['lastmod']??'');
            if(preg_match('/^\d{4}-\d{2}-\d{2}$/D',$date))
                $xml.='<lastmod>'.$date.'</lastmod>';
            $xml.='</url>';
        }
        return $xml.'</urlset>'."\n";
    }
}
