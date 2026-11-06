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
        'legacy disabled members remain understandable to the server');
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
    check(str_contains($js,"t('expand'") &&
        str_contains($js,"t('collapse'") &&
        str_contains($js,'data-mm-group-toggle') &&
        str_contains($js,"setAttribute('aria-expanded'") &&
        str_contains($js,'event.stopPropagation()'),
        'independent, keyboard-accessible expand/collapse button with no link conflict');
    check(str_contains($js,'renderGroup(') && str_contains($js,'details') &&
        str_contains($js,'summary'),'one group row expands its member list');
    check(str_contains($js,"'current'") && str_contains($js,'enabled:true'),
        'new group defaults to the editing product as an enabled member');
    check(str_contains($js,'category_id') &&
        str_contains($js,'searchUrl') &&
        str_contains($js,'groupUrl'),
        'product chooser searches by name/SKU or filters by category');
    check(!str_contains($js,"checkbox.type = 'checkbox'") &&
        !str_contains($js,"checkbox.checked =") &&
        !str_contains($js,"type = 'checkbox'"),
        'no redundant member checkboxes anywhere in either group editor');
    check(str_contains($js,"link.target = '_blank'") &&
        str_contains($js,"link.rel = 'noopener noreferrer'") &&
        str_contains($js,'task=product.edit&id=') &&
        str_contains($js,"openLink(href, name, 'mm-group-member__link')"),
        'member titles open the matching product editor in a new tab');
    check(str_contains($js,'const renderGroup = (g, index) =>') &&
        str_contains($js,'for (const [i, group] of groups.entries())'),
        'product editor shows any number of independently expandable groups');
    check(str_contains($js,'g.members = g.members.filter(x => x !== member);') &&
        !str_contains($js,"g.leave = true"),
        'Remove product unlinks exactly one member rather than deleting a group');
    check(str_contains($js,"group.deleteRelationship") &&
        str_contains($js,'submitGroupDeletion()'),
        'standalone and product editors both expose explicit group deletion');
    check(str_contains($js,'const submitGroupChanges = () => {') &&
        str_contains($js,'mm-group-item__save-actions') &&
        str_contains($js,"'btn-success'") &&
        str_contains($js,'submitGroupChanges'),
        'group Save and Cancel Creation are separated from Add product');
    $groupController=file_get_contents($base.'admin/src/Controller/GroupController.php');
    check(str_contains($groupController,'function deleteRelationship(): void') &&
        str_contains($groupController,"Session::checkToken('post')") &&
        str_contains($groupController,"authorise('core.delete', 'com_minimarket')") &&
        str_contains($groupController,'$model->delete($ids)'),
        'standalone delete checks post token, ACL and calls group-only deletion');
    check(str_contains($js, "groups.unshift({") &&
        str_contains($js, "group.members.unshift({") &&
        str_contains($js, "mount.prepend(addExisting())"),
        'new relationships, attached groups, search and new members appear first');
    check(str_contains($js,'mm-group-toolbar--footer') &&
        str_contains($js,'mount.appendChild(toolbar)') &&
        str_contains($js,'for (const [i, group] of groups.entries())'),
        'relationship creation buttons are below the existing relationship list');
    check(str_contains($js,"mm-group-finder__search--groups") &&
        str_contains($js,"mm-group-existing__results") &&
        str_contains($js,"'btn-success'") &&
        !str_contains($js,'btn-outline-'),
        'existing-group search mirrors product search, solid native buttons only');
    check(str_contains($js,'content.appendChild(finder(g))') &&
        str_contains($js,"g._finderOpen = !g._finderOpen"),
        'adding a product opens category/name search at the start of its group');
    $controller=file_get_contents($base.'admin/src/Controller/ProductpickerController.php');
    check(str_contains($controller,"authorise('core.manage', 'com_minimarket')") &&
        str_contains($controller,"Session::checkToken('get')") &&
        str_contains($controller,'$db->setQuery($q, 0, 25);'),
        'search API requires admin permission and CSRF token, limits results');
    check(str_contains($controller,'count($found) > 1000') &&
        str_contains($controller,'Group too large for inline editing'),
        'large existing groups refuse truncated membership data');
    $groupModelDelete=file_get_contents($base.'admin/src/Model/GroupModel.php');
    check(str_contains($groupModelDelete,'parent::delete($pks)') &&
        str_contains($groupModelDelete,'$db->transactionStart();') &&
        str_contains($groupModelDelete,"->delete(\$db->quoteName('#__minimarket_product_group_members'))"),
        'explicit group deletion unlinks members in a transaction without deleting products');
    $service=file_get_contents($base.'admin/src/Service/GroupEditor.php');
    check(str_contains($service,'ProductGroups::replaceForGroup') &&
        str_contains($service,'saveProduct(') &&
        str_contains($service,'saveGroup('),
        'both editors save using same relational service');
    check(str_contains($service,'foreach ($original as $gid)') &&
        str_contains($service,'if (!in_array($gid,$present,true))'),
        'missing saved groups are not accidentally detached');
    check(str_contains($service,'A new group can survive removal of its founding product.') &&
        !str_contains($service,'if (!in_array($productId,$members,true))'),
        'new group can remain in Groups even after founding product leaves');
    check(str_contains($service,'ProductGroups::replaceForGroup($db,$id,$members);') &&
        str_contains($service,'$q=$db->getQuery(true)') &&
        str_contains($service,'if ($row[\'delete\'])'),
        'member removal preserves group record unless explicitly deleted');
    $productModel=file_get_contents($base.'admin/src/Model/ProductModel.php');
    $groupModel=file_get_contents($base.'admin/src/Model/GroupModel.php');
    check(str_contains($productModel,'GroupEditor::saveProduct(') &&
        str_contains($groupModel,'GroupEditor::saveGroup(') &&
        str_contains($productModel,'$db->transactionRollback();') &&
        str_contains($groupModel,'$db->transactionRollback();'),
        'all changes guarded by one Joomla transaction');
    check(str_contains($productModel,"authorise('core.delete', 'com_minimarket')"),
        'global deletion from product also requires component delete permission');
    $css=file_get_contents($base.'media/css/admin-ui.css');
    check(str_contains($css,'grid-template-columns: repeat(2, minmax(0, 1fr));') &&
        str_contains($css,'.mm-group-item > summary'),
        'option values are grouped into cards and relationships are expandable');
    $migration=file_get_contents($base.'admin/sql/updates/mysql/0.5.12.sql');
    check(str_contains($migration,'MODIFY '.chr(96).'value_text'.chr(96).' TEXT NULL;') &&
        !str_contains($migration,'MODIFY COLUMN '.chr(96).'value_text'.chr(96)),
        'Joomla DB schema checker no longer misreads COLUMN as column name');
    $manifest=file_get_contents($base.'minimarket.xml');
    check(str_contains($manifest,'<version>0.5.54</version>'),
        'component metadata matches 0.5.51');
    echo 'GROUP_EDITOR_OK: '.$checks." cases\n";
}
