<?php
/** Joomla administrator link regression: prevent Route XHTML double encoding. */
declare(strict_types=1);

namespace Joomla\CMS\Router {
    final class Route
    {
        public static array $calls = [];

        public static function _($url, $xhtml = true): string
        {
            self::$calls[] = ['url' => $url, 'xhtml' => $xhtml];

            return $xhtml ? str_replace('&', '&amp;', $url) : $url;
        }
    }
}

namespace MiniMarket\EntityLinksSmoke {
    use Joomla\CMS\Router\Route;
    use MiniMarket\Component\Minimarket\Administrator\Service\AdminEntityLinks as Links;

    \define('_JEXEC', 1);
    require __DIR__ . '/../src/com_minimarket/admin/src/Service/AdminEntityLinks.php';

    $count = 0;
    function verify(bool $ok, string $label): void
    {
        global $count;
        ++$count;
        if (!$ok) {
            throw new \RuntimeException('ENTITY_LINKS_FAIL: ' . $label);
        }
    }

    function decodeLink(string $escapedHtml): array
    {
        // The browser decodes an HTML attribute exactly once, then sends query.
        verify(!str_contains($escapedHtml, '&amp;amp;'),
            'only one layer of escaping in link');
        $url = html_entity_decode($escapedHtml, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $urlParts = parse_url($url);
        parse_str((string) ($urlParts['query'] ?? ''), $query);
        verify(($urlParts['path'] ?? '') === 'index.php', 'administrator URL path');
        verify(($query['option'] ?? null) === 'com_minimarket', 'component option retained');
        verify(!isset($query['amp;task']) && !isset($query['amp;id']) && !isset($query['amp;view']),
            'no amp-prefixed parameters after decoding');
        return $query;
    }

    foreach (['attribute' => 12, 'orderoption' => 14,
        'category' => 9, 'group' => 8, 'product' => 22, 'value' => 3, 'brand' => 4, 'collection' => 15] as $entity => $id) {
        $href = Links::edit($entity, $id);
        verify(str_contains($href, '&amp;task='), 'escaped task separator for ' . $entity);
        $query = decodeLink($href);
        verify(($query['task'] ?? null) === $entity . '.edit',
            'edit task reaches ' . $entity . ' controller');
        verify(($query['id'] ?? null) === (string) $id,
            'real ' . $entity . ' record ID reaches controller');
    }

    $create = decodeLink(Links::add('orderoption'));
    verify(($create['task'] ?? null) === 'orderoption.add',
        'empty category options creates an option through FormController');

    foreach (['categories', 'attributes', 'orderoptions', 'products', 'groups', 'dashboard', 'import', 'brands', 'collections'] as $view) {
        $href = Links::view($view);
        $query = decodeLink($href);
        verify(($query['view'] ?? null) === $view, 'dashboard navigation reaches ' . $view);
    }

    foreach (Route::$calls as $call) {
        verify($call['xhtml'] === false, 'Joomla route generated without pre-encoded XHTML');
    }

    foreach ([['edit','invalid',1],['edit','attribute',0],['add','bad',null],
              ['view','bad',null]] as [$method,$name,$id]) {
        try {
            $method === 'edit' ? Links::edit($name, $id)
                : ($method === 'add' ? Links::add($name) : Links::view($name));
            verify(false, 'invalid target rejected');
        } catch (\InvalidArgumentException $e) {
            verify(true, 'invalid target rejected');
        }
    }

    // Ensure all link-rendering locations use the central one-pass encoder.
    $root = __DIR__ . '/../src/com_minimarket/';
    $targets = [
        'admin/src/Field/CategoryattributesField.php' => "Links::edit('attribute'",
        'admin/src/Field/CategoryorderoptionsField.php' => "Links::edit('orderoption'",
        'admin/src/Field/ProductattributesField.php' => "Links::edit('attribute'",
        'admin/src/Field/GroupcontentsField.php' => "Links::edit('group'",
        'admin/src/Field/ProductcategoryoptionsField.php' => "Links::view('categories'",
        'admin/tmpl/dashboard/default.php' => 'Links::view($view)',
    ];
    foreach ($targets as $path => $call) {
        $source = file_get_contents($root . $path);
        $needle = str_replace('Links::', 'AdminEntityLinks::', $call);
        verify(str_contains($source, $needle), 'field uses safe HTML link: ' . $path);
    }

    echo 'ENTITY_LINKS_OK: ' . $count . " cases\n";
}
