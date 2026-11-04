<?php
/** Standalone editor validation + native Joomla integration contract. */
declare(strict_types=1);
namespace Joomla\Database { interface DatabaseInterface {} }
namespace MiniMarket\GroupEditorSmoke {
    \define('_JEXEC',1);
    $base=__DIR__.'/../src/com_minimarket/';
    require $base.'admin/src/Service/ProductGroups.php';
    require $base.'admin/src/Service/GroupEditor.php';
    use MiniMarket\Component\Minimarket\Administrator\Service\GroupEditor;
    $checks=0;
    function check(bool $yes,string $message):void {
        global $checks;
        ++$checks;
        if (!$yes) throw new \RuntimeException('GROUP_EDITOR_FAIL: '.$message);
    }
    $valid=[['id'=>33,'title'=>'Похожие товары','delete'=>false,
        'members'=>[
            ['id'=>'current','enabled'=>true],
            ['id'=>1012,'enabled'=>true],
            ['id'=>1031,'enabled'=>false],
        ]],['id'=>0,'title'=>'Новая связь',
            'members'=>[['id'=>'current','enabled'=>true]]]];
    $json=json_encode($valid,JSON_UNESCAPED_UNICODE);
    $normalized=GroupEditor::normalize($json,'product');
    check(count($normalized)===2 && $normalized[0]['members'][0]['id']==='current',
        'saved group and new group include current product');
    check($normalized[0]['members'][2]['enabled']===false,
        'member checkboxes control relationship, not product publication');
    check(GroupEditor::normalize('[{"id":0,"title":"","members":[]}]','group')[0]['id']===0,
        'new standalone group can be saved before adding members');
    foreach ([
        [['id'=>1,'title'=>'A','members'=>[['id'=>1,'enabled'=>true],
                                             ['id'=>1,'enabled'=>true]]]],
        [['id'=>0,'title'=>'','members'=>[]]],
        [['id'=>0,'title'=>'<script>','members'=>[]]],
        [['id'=>99,'title'=>'Good','delete'=>1,'members'=>[]]],
        [['id'=>0,'title'=>'Good','delete'=>true,'members'=>[]]],
        [['id'=>1,'title'=>'A','members'=>[['id'=>'current','enabled'=>'true']]]],
    ] as $bad) {
        try {
            GroupEditor::normalize(json_encode($bad),'product');
            check(false,'invalid group payload accepted');
        } catch (\InvalidArgumentException $e) {
            check(true,'malformed group payload rejected');
        }
    }
    $product=simplexml_load_file($base.'admin/forms/product.xml');
    $group=simplexml_load_file($base.'admin/forms/group.xml');
    check(count($product->xpath("//fieldset[@name='groups']/field[@name='group_editor' and @type='Groupeditor']"))===1,
        'product renders one group list, not all catalog checkboxes');
    check(count($group->xpath("//fieldset[@name='members']/field[@name='member_editor' and @type='Groupeditor']"))===1,
        'standalone group uses same editor');
    check(count($product->xpath("//field[@name='group_ids' or @name='new_group_title']"))===0
        && count($group->xpath("//field[@name='member_ids']"))===0,
        'no old checkbox walls in visible Joomla forms');
    $js=file_get_contents($base.'media/js/group-editor.js');
    check(str_contains($js,'renderGroup(') && str_contains($js,'details') &&
        str_contains($js,'summary'),'one group row expands its member list');
    check(str_contains($js,"'current'") && str_contains($js,'enabled:true'),
        'new group defaults to the editing product as an enabled member');
    check(str_contains($js,'category_id') &&
        str_contains($js,'searchUrl') &&
        str_contains($js,'groupUrl'),
        'product chooser searches by name/SKU or filters by category');
    check(!str_contains($js,'CheckboxesField') &&
        !str_contains($js,'document.querySelectorAll("input[type=checkbox]")'),
        'no global catalog list of checkboxes');
    $controller=file_get_contents($base.'admin/src/Controller/ProductpickerController.php');
    check(str_contains($controller,"authorise('core.manage', 'com_minimarket')") &&
        str_contains($controller,"Session::checkToken('get')") &&
        str_contains($controller,'$db->setQuery($q, 0, 25);'),
        'search API requires admin permission and CSRF token, limits results');
    $service=file_get_contents($base.'admin/src/Service/GroupEditor.php');
    check(str_contains($service,'ProductGroups::replaceForGroup') &&
        str_contains($service,'saveProduct(') &&
        str_contains($service,'saveGroup('),
        'both editors save using same relational service');
    check(str_contains($service,'foreach ($original as $gid)') &&
        str_contains($service,'if (!in_array($gid,$present,true))'),
        'missing saved groups are not accidentally detached');
    $productModel=file_get_contents($base.'admin/src/Model/ProductModel.php');
    $groupModel=file_get_contents($base.'admin/src/Model/GroupModel.php');
    check(str_contains($productModel,'GroupEditor::saveProduct(') &&
        str_contains($groupModel,'GroupEditor::saveGroup(') &&
        str_contains($productModel,'$db->transactionRollback();') &&
        str_contains($groupModel,'$db->transactionRollback();'),
        'all changes guarded by one Joomla transaction');
    $css=file_get_contents($base.'media/css/admin-ui.css');
    check(str_contains($css,'grid-template-columns: repeat(2, minmax(0, 1fr));') &&
        str_contains($css,'.mm-group-item > summary'),
        'option values are grouped into cards and relationships are expandable');
    $migration=file_get_contents($base.'admin/sql/updates/mysql/0.5.12.sql');
    check(str_contains($migration,'MODIFY '.chr(96).'value_text'.chr(96).' TEXT NULL;') &&
        !str_contains($migration,'MODIFY COLUMN '.chr(96).'value_text'.chr(96)),
        'Joomla DB schema checker no longer misreads COLUMN as column name');
    $manifest=file_get_contents($base.'minimarket.xml');
    check(str_contains($manifest,'<version>0.5.40</version>'),
        'component metadata matches 0.5.40');
    echo 'GROUP_EDITOR_OK: '.$checks." cases\n";
}
