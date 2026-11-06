<?php
/**
 * Standalone administrator quicklink installer lifecycle and deterministic
 * placement immediately to the left of the Joomla version (mock Joomla DB).
 */
declare(strict_types=1);
namespace Joomla\Database {
    interface DatabaseInterface {}
}
namespace Joomla\CMS\Installer {
    class InstallerScript {}
}
namespace Joomla\CMS {
    final class Factory {
        public static object $db;
        public static object $app;
        public static function getContainer():object {
            return new class {
                public function get(string $service): object {return Factory::$db;}
            };
        }
        public static function getApplication(): object {return Factory::$app;}
    }
}
namespace MiniMarket\QuicklinkInstallerTest {
    use Joomla\CMS\Factory;
    use Joomla\Database\DatabaseInterface;
    function check(bool $passed,string $message): void {
        global $checks;
        ++$checks;
        if (!$passed) throw new \RuntimeException('TOOLBAR_064_INSTALL_FAIL: '.$message);
    }
    class Query {
        public string $operation='select';
        public string $table='';
        public array $values=[];
        public array $sets=[];
        public array $where=[];
        public array $orders=[];
        public function select($x):self{$this->operation='select';return $this;}
        public function from($x):self{$this->table=$x;return $this;}
        public function update($x):self{$this->table=$x;$this->operation='update';return $this;}
        public function insert($x):self{$this->table=$x;$this->operation='insert';return $this;}
        public function where($x):self{$this->where[]=(string)$x;return $this;}
        public function order($x):self{$this->orders[]=$x;return $this;}
        public function columns($x):self{return $this;}
        public function values($x):self{$this->values[]=$x;return $this;}
        public function set($x):self{$this->sets[]=$x;return $this;}
    }
    class Db implements DatabaseInterface {
        public ?Query $q=null;
        public int $assignments=0;
        public int $updates=0;
        public array $rows=[];
        public bool $moduleExists=true;
        public function __construct() {
            $this->rows=[
                1=>['id'=>1,'module'=>'mod_frontend','ordering'=>2,'position'=>'status','published'=>1],
                2=>['id'=>2,'module'=>'mod_version','ordering'=>4,'position'=>'status','published'=>1],
                3=>['id'=>3,'module'=>'mod_user','ordering'=>6,'position'=>'status','published'=>1],
                4=>['id'=>4,'module'=>'mod_minimarket_toolbar','ordering'=>9,'position'=>'status','published'=>0],
            ];
        }
        public function getQuery(bool $fresh=true):Query{return new Query();}
        public function quoteName($name):string{return '`'.$name.'`';}
        public function quote($name):string{return "'".str_replace("'","''",(string)$name)."'";}
        public function setQuery($query):void{$this->q=$query;}
        public function loadResult():int{
            if($this->q===null)throw new \RuntimeException('No SQL');
            if($this->q->table==='`#__modules_menu`')return $this->assignments;
            if($this->q->table==='`#__modules`')return $this->moduleExists?4:0;
            throw new \RuntimeException('Unexpected loadResult '.$this->q->table);
        }
        public function loadAssocList():array{
            if($this->q?->table!=='`#__modules`')throw new \RuntimeException('Unexpected list');
            $rows=array_values(array_filter($this->rows,
                static fn($row)=>$row['position']==='status'));
            usort($rows,static fn($a,$b)=>[$a['ordering'],$a['id']]<=>[$b['ordering'],$b['id']]);
            return $rows;
        }
        public function execute():void{
            if(!$this->q)throw new \RuntimeException('No query');
            if($this->q->operation==='insert'){
                if($this->q->table!=='`#__modules_menu`')throw new \RuntimeException('Unexpected insert');
                $this->assignments++;
                return;
            }
            if($this->q->operation!=='update'||$this->q->table!=='`#__modules`'){
                throw new \RuntimeException('Unexpected write');
            }
            $this->updates++;
            $id=null;
            foreach($this->q->where as $where){
                if(preg_match('/`id`=(\d+)/',$where,$m))$id=(int)$m[1];
            }
            if($id===null)throw new \RuntimeException('Update without module ID');
            foreach($this->q->sets as $set){
                if(preg_match('/`ordering`=(\d+)/',$set,$m))$this->rows[$id]['ordering']=(int)$m[1];
                if(preg_match('/`position`=\x27([^\x27]+)\x27/',$set,$m))$this->rows[$id]['position']=$m[1];
                if(preg_match('/`published`=(\d+)/',$set,$m))$this->rows[$id]['published']=(int)$m[1];
            }
        }
    }
    \define('_JEXEC',1);
    require __DIR__.'/../src/mod_minimarket_toolbar/script.php';
    $checks=0;
    Factory::$app=new class {
        public array $messages=[];
        public function enqueueMessage(string $message,string $type='message'):void {
            $this->messages[]=[$message,$type];
        }
    };
    $db=new Db();
    Factory::$db=$db;
    $installer=new \mod_minimarket_toolbarInstallerScript();
    check($installer->postflight('install',null)===true,'standalone module installation returns true');
    check($db->assignments===1 && $db->rows[4]['published']===1
        && $db->rows[4]['position']==='status',
        'first module installation publishes in Joomla status for all admin pages');
    $all=$db->loadAssocList();
    $order=array_column($all,'module');
    check(array_search('mod_minimarket_toolbar',$order,true)+1
        ===array_search('mod_version',$order,true),
        'cart button is immediately left of Joomla version');
    check(count($db->rows)===4 && count(Factory::$app->messages)===0,
        'no duplicate Joomla header module and no installer warnings');
    $updates=$db->updates;
    check($installer->postflight('update',null)===true
        && $db->assignments===1 && $db->updates===$updates,
        'module update does not modify existing correctly ordered instances');
    $db->rows[4]['published']=0;
    check($installer->postflight('update',null)===true
        && $db->rows[4]['published']===0,
        'module upgrade preserves administrator unpublish choice');
    $db->rows[4]['position']='customtop';
    $updates=$db->updates;
    check($installer->postflight('update',null)===true &&
        $db->rows[4]['position']==='customtop' &&
        $db->updates===$updates,
        'module upgrade respects manually changed customtop position');
    $db->moduleExists=false;
    check($installer->postflight('install',null)===true &&
        count(Factory::$app->messages)===1,
        'module instance missing is reported non-fatally');
    check($installer->postflight('uninstall',null)===true,
        'uninstall does not try to reconfigure deleted module');
    $component=(string)file_get_contents(__DIR__.'/../src/com_minimarket/script.php');
    check(!str_contains($component,"'mod_minimarket_toolbar'")
       && !str_contains($component,'installQuicklink'),
       'MiniMarket component upgrade never installs or touches new toolbar module');
    echo "TOOLBAR_064_INSTALL_OK: $checks checks\n";
}
