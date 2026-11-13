<?php
/**
 * Static controller/button audit. Prints inventory; only route defects fail CI.
 * Real click behavior, Joomla ACL and responsive UX require browser E2E.
 */
declare(strict_types=1);
$root=__DIR__.'/../src/com_minimarket/';
$controllerDir=$root.'admin/src/Controller/';
$views=[];
$controllers=[];
foreach(glob($controllerDir.'*Controller.php') ?: [] as $path) {
    $name=strtolower(substr(basename($path),0,-strlen('Controller.php')));
    $text=(string)file_get_contents($path);
    preg_match_all('/public\\s+function\\s+(\\w+)\\s*\\(/i',$text,$matches);
    $controllers[$name]=array_fill_keys($matches[1],true);
}
// Public feed XML links are printed in the administrator list, but are
// handled by the public Site\Controller\FeedController, not the admin one.
$siteControllers=[];
foreach(glob($root.'site/src/Controller/*Controller.php') ?: [] as $path) {
    $name=strtolower(substr(basename($path),0,-strlen('Controller.php')));
    $source=(string)file_get_contents($path);
    preg_match_all('/public\\s+function\\s+(\\w+)\\s*\\(/i',$source,$methods);
    $siteControllers[$name]=array_fill_keys($methods[1],true);
}
$inherit=array_fill_keys([
    'add','edit','save','apply','cancel','save2new','save2copy','delete',
    'publish','unpublish','checkin','batch','reorder','saveorder','featured',
    'unfeatured','archive','trash','orderup','orderdown'
],true);
$links=[]; $buttons=0;$mismatches=[];
foreach(glob($root.'admin/tmpl/*/*.php') ?: [] as $path) {
    $source=(string)file_get_contents($path);
    preg_match_all('/<button\\b/i',$source,$bm);
    $buttons+=count($bm[0]);
    preg_match_all('/task=([a-z][a-z0-9_]*)\\.([a-z][a-z0-9_]*)/i',$source,$m,PREG_SET_ORDER);
    foreach($m as $found) {
        $name=strtolower($found[1]);$method=$found[2];
        $id=$name.'.'.$method;
        $links[$id]=($links[$id]??0)+1;
        // The Integrations screen links to Joomla's com_plugins, not our own
        // controller. Only this explicit core component route is exempt.
        if($id==='plugin.edit'
            && in_array(basename(dirname($path)),['integrations','notificationmethods','shared'],true)
            && (basename(dirname($path))!=='shared'
                || basename($path)==='plugin-method.php')
            && str_contains($source,'option=com_plugins&task=plugin.edit'))continue;
        // User edit links on order screens belong to Joomla com_users.
        if($id==='user.edit'
            && basename(dirname($path))==='order'
            && in_array(basename($path),['create.php','default.php'],true)
            && str_contains($source,'option=com_users&task=user.edit'))continue;
        if(!isset($controllers[$name])) $mismatches[]='No controller for '.$id;
        elseif(!isset($controllers[$name][$method])&&!isset($inherit[$method])
            && !isset($siteControllers[$name][$method]))
            $mismatches[]='No controller method for '.$id;
    }
    preg_match_all('/view=([a-z][a-z0-9_]*)/i',$source,$vm);
    foreach($vm[1] as $name) {
        // The registration settings screen links to Joomla's com_config.
        // It must not be mistaken for a MiniMarket component view.
        if ($name==='component' && basename(dirname($path))==='registrationmethods'
            && str_contains($source,'option=com_config&view=component&component=com_users'))continue;
        $view=ucfirst(strtolower($name));
        if(!is_file($root.'admin/src/View/'.$view.'/HtmlView.php') &&
          !is_file($root.'site/src/View/'.$view.'/HtmlView.php')) {
            $mismatches[]='No view '.$name.' referenced in '.basename(dirname($path));
        }
    }
}
$assetRefs=[];
foreach(glob($root.'admin/src/View/*/*.php') ?: [] as $path) {
    $s=(string)file_get_contents($path);
    preg_match_all('~media/com_minimarket/(js|css)/([A-Za-z0-9._-]+)~',$s,$mm,PREG_SET_ORDER);
    foreach($mm as $m) {
        $assetRefs[$m[1].'/'.$m[2]]=true;
        if(!is_file($root.'media/'.$m[1].'/'.$m[2]))
            $mismatches[]='Missing registered media: '.$m[1].'/'.$m[2];
    }
}
$import=(string)file_get_contents($root.'admin/tmpl/import/default.php');
$controller=(string)file_get_contents($root.'admin/src/Controller/ImportController.php');
if(!str_contains($import,'task=import.export&filetype=')
 ||!str_contains($controller,"getCmd('filetype', 'json')"))
 $mismatches[]='Export link/controller filetype mismatch';
ksort($links);
echo "ADMIN_WIRING_AUDIT templates=".count(glob($root.'admin/tmpl/*/*.php') ?: [])
   ." buttons=$buttons tasks=".count($links)." media=".count($assetRefs)."\n";
foreach($links as $task=>$count) echo "TASK $task references=$count\n";
if($mismatches) {
  fwrite(STDERR,"ADMIN_WIRING_FAIL: ".implode('; ',array_unique($mismatches))."\n");
  exit(1);
}
echo "ADMIN_WIRING_PASS\n";
