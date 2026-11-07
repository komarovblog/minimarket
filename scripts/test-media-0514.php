<?php
/** Pure PHP 0.5.14 document, color and product video safety regression. */
declare(strict_types=1);
namespace Joomla\Database {
    interface DatabaseInterface {
        public function getQuery($new=true);
        public function quoteName($name);
        public function quote($value);
        public function setQuery($query);
        public function loadAssocList();
        public function execute();
    }
}
namespace MiniMarket\MediaSmoke {
    use Joomla\Database\DatabaseInterface;
    use MiniMarket\Component\Minimarket\Administrator\Service\ProductVideos;
    use MiniMarket\Component\Minimarket\Administrator\Service\AttributeValueRows;
    \define('_JEXEC',1);
    require __DIR__ . '/../src/com_minimarket/admin/src/Service/ProductVideos.php';
    require __DIR__ . '/../src/com_minimarket/admin/src/Service/AttributeValueRows.php';
    require __DIR__ . '/../src/com_minimarket/admin/src/Service/MediaPath.php';
    $checks=0;
    function check(bool $ok,string $why):void {global $checks;++$checks;if(!$ok)throw new \RuntimeException('MEDIA_0514_FAIL: '.$why);}
    $rows=ProductVideos::normalize([
       'row1'=>['title'=>'Demo','source'=>'https://youtu.be/dQw4w9WgXcQ'],
       'row0'=>['title'=>'MP4','source'=>'images/videos/demo.mp4'],
       'row2'=>['title'=>'RuTube','source'=>'https://rutube.ru/video/abcdef1234567890/'],
    ]);
    check(count($rows)===3,'multiple videos');
    check($rows[0]['source']==='https://www.youtube.com/watch?v=dQw4w9WgXcQ','YouTube canonical');
    check($rows[1]['sort_order']===1,'drag order');
    check(ProductVideos::player($rows[0]['source'])['url']
        ==='https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ','YouTube iframe allowlist');
    check(ProductVideos::player($rows[1]['source'])['kind']==='file','local HTML5 video');
    check(str_starts_with(ProductVideos::player($rows[2]['source'])['url'],
        'https://rutube.ru/play/embed/'),'RuTube embed');
    check(ProductVideos::normalize('{}')===[],'empty Joomla subform');
    $cloud=ProductVideos::normalize([['title'=>'Cloud','source'=>'https://cdn.example.org/video/demo.mp4',
        'poster'=>'images/covers/video.webp']]);
    check($cloud[0]['poster']==='images/covers/video.webp','video cover saved');
    check(ProductVideos::player($cloud[0]['source'])['kind']==='file','HTTPS cloud video');
    $local=ProductVideos::normalize([['title'=>'Media picker','source'=>'',
        'media_path'=>'images/video/demo.webm']]);
    check($local[0]['source']==='images/video/demo.webm','Joomla media video selected');
    foreach([
        [['source'=>'javascript:alert(1)']],
        [['source'=>'https://evil.example/xyz']],
        [['source'=>'https://www.youtube.com.evil.test/watch?v=dQw4w9WgXcQ']],
        [['source'=>'images/../../etc/passwd.mp4']],
        [['source'=>'images/a.php']],
        [['source'=>'https://www.youtube.com/watch?v=abc']],
        [['source'=>'https://www.youtube.com/watch?v=dQw4w9WgXcQ','title'=>'<script>']],
        [['source'=>'images/same.mp4'],['source'=>'images/same.mp4']],
        array_fill(0,21,['source'=>'images/too-many.mp4']),
    ] as $bad) {
        try {ProductVideos::normalize($bad);check(false,'unsafe video should fail');}
        catch(\InvalidArgumentException $ex){check(true,'unsafe video rejected');}
    }
    $promo=ProductVideos::normalize([['source'=>'images/minimarket/demo-promo.mp4',
       'title'=>'Demo promo','is_promo'=>1]]);
    check($promo[0]['is_promo']===1,'safe local MP4 promo can be saved');
    foreach ([
      [['source'=>'https://youtu.be/dQw4w9WgXcQ','is_promo'=>1]],
      [['source'=>'https://cdn.example.org/movie.mp4','is_promo'=>1]],
      [['source'=>'images/a.mp4','is_promo'=>1],
       ['source'=>'images/b.mp4','is_promo'=>1]],
    ] as $invalid) {
      try {ProductVideos::normalize($invalid); check(false,'invalid promo accepted');}
      catch(\InvalidArgumentException $e){check(true,'invalid promo rejected');}
    }
    $legacy=[
        10=>['id'=>10,'title'=>'Red','code'=>'red','color_hex'=>'','state'=>1,'sort_order'=>0],
        11=>['id'=>11,'title'=>'Yellow','code'=>'yellow','color_hex'=>'','state'=>1,'sort_order'=>1],
    ];
    $colors=AttributeValueRows::normalize([
        ['id'=>10,'title'=>'Red','code'=>'red','color_hex'=>'#FF0000','state'=>1],
        ['id'=>11,'title'=>'Yellow','code'=>'yellow','color_hex'=>'#FFFF00','state'=>1],
    ],$legacy);
    check($colors[0]['id']===10 && $colors[0]['color_hex']==='#FF0000',
        'legacy value ID preserved when assigned HEX swatch');
    check($colors[1]['id']===11 && $colors[1]['color_hex']==='#FFFF00',
        'another existing option ID preserved');
    try {AttributeValueRows::normalize([
        ['id'=>10,'title'=>'Red','code'=>'red','color_hex'=>'red;overflow:hidden','state'=>1]
    ],$legacy);check(false,'invalid color');}
    catch(\InvalidArgumentException $ex){check(true,'CSS injection blocked');}

    $f=simplexml_load_file(__DIR__.'/../src/com_minimarket/admin/forms/attribute.xml');
    $p=simplexml_load_file(__DIR__.'/../src/com_minimarket/admin/forms/product.xml');
    $color=simplexml_load_file(__DIR__.'/../src/com_minimarket/admin/forms/attribute_unified_value_row.xml');
    $video=simplexml_load_file(__DIR__.'/../src/com_minimarket/admin/forms/product_video.xml');
    check($f!==false && $p!==false && $color!==false && $video!==false,'forms parse');
    check(count($f->xpath("//field[@name='unified_values' and @type='subform']"))===1,
        'color swatches are edited in the canonical dictionary');
    check(count($color->xpath("//field[@name='color_hex']"))===1,
        'HEX and sample image supported by the common value form');
    check(count($p->xpath("//fieldset[@name='videos']/field[@name='videos' and @type='subform']"))===1,
        'videos belong directly to product');
    check(count($video->xpath("//field[@name='source']"))===1,'video source per row');
    check(str_contains(file_get_contents(__DIR__.'/../src/com_minimarket/admin/src/Model/ProductModel.php'),
        'ProductVideos::replace'),'videos saved in product transaction');
    check(str_contains(file_get_contents(__DIR__.'/../src/com_minimarket/admin/src/Service/ProductAttributes.php'),
        'COM_MINIMARKET_DOCUMENT_INVALID'),'documents validated by extension/path');
    check(str_contains(file_get_contents(__DIR__.'/../src/com_minimarket/site/tmpl/product/default.php'),
        'youtube-nocookie.com'),'public safe video rendering');
    echo 'PRODUCT_MEDIA_0514_OK: '.$checks." cases\n";
}
