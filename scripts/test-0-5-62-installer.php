<?php
/** Simulated Joomla installer postflight: fresh, upgrade and failure paths. */
declare(strict_types=1);

namespace Joomla\Database {
    interface DatabaseInterface {}
}
namespace Joomla\CMS\Installer {
    class InstallerScript {
        public int $dashboardCalls=0;
        protected function addDashboardMenu(string $dashboard,string $menu):void {
            $this->dashboardCalls++;
        }
    }
    final class Installer {
        public static bool $shouldFail=false;
        public static array $sources=[];
        public function setDatabase(object $db):void {}
        public function install(string $path):bool {
            self::$sources[]=$path;
            return !self::$shouldFail;
        }
    }
}
namespace Joomla\CMS {
    final class Factory {
        public static object $db;
        public static object $app;
        public static function getContainer():object{
            return new class {
                public function get(string $type):object{return Factory::$db;}
            };
        }
        public static function getApplication():object{return self::$app;}
    }
}
namespace MiniMarket\InstallerQuicklinkTest {
    use Joomla\CMS\Factory;
    use Joomla\CMS\Installer\Installer;
    use Joomla\Database\DatabaseInterface;

    $tests=0;
    function assertInstall(bool $value,string $msg):void {
        global $tests; ++$tests;
        if(!$value)throw new \RuntimeException('QUICKLINK_INSTALL_FAIL: '.$msg);
    }
    final class FakeQuery {
        public string $operation='read';
        public string $table='';
        public array $sets=[];
        public array $clauses=[];
        public function select($x):self{$this->operation='read';return $this;}
        public function from($x):self{$this->table=(string)$x;return $this;}
        public function update($x):self{$this->operation='update';$this->table=(string)$x;return $this;}
        public function set($x):self{$this->sets[]=$x;return $this;}
        public function where($x):self{$this->clauses[]=$x;return $this;}
        public function order($x):self{return $this;}
        public function insert($x):self{$this->operation='insert';$this->table=(string)$x;return $this;}
        public function columns($x):self{return $this;}
        public function values($x):self{$this->clauses[]=(string)$x;return $this;}
    }
    final class FakeDatabase implements DatabaseInterface {
        public bool $existing=false;
        public int $assignments=0;
        public array $updates=[];
        public int $moduleId=54;
        public int $dashboardMenuCount=1;
        public ?FakeQuery $current=null;
        public function quoteName($name):string{return '`'.(string)$name.'`';}
        public function quote($value):string{return "'".str_replace("'","''",(string)$value)."'";}
        public function getQuery(bool $new=true):FakeQuery{return new FakeQuery();}
        public function setQuery($sql):void{$this->current=$sql;}
        public function loadResult():int{
            if(!$this->current)throw new \RuntimeException('No current query');
            $q=$this->current;
            if($q->table==='`#__extensions`')return $this->existing?202:0;
            if($q->table==='`#__modules_menu`')return $this->assignments;
            if($q->table==='`#__modules`' && array_filter($q->clauses,static fn($v)=>str_contains($v,'mod_submenu')))
                return $this->dashboardMenuCount;
            if($q->table==='`#__modules`')return $this->moduleId;
            throw new \RuntimeException('Unexpected DB table: '.$q->table);
        }
        public function execute():void{
            if(!$this->current)throw new \RuntimeException('No current query');
            if($this->current->operation==='update'){
                $this->updates[]=$this->current->sets;
            }elseif($this->current->operation==='insert'){
                $this->assignments++;
            }else throw new \RuntimeException('Unexpected write');
        }
    }
    \define('_JEXEC',1);
    $tmp=sys_get_temp_dir().'/minimarket-quicklink-test-'.bin2hex(random_bytes(6));
    \define('JPATH_ADMINISTRATOR',$tmp);
    $source=$tmp.'/components/com_minimarket/modules/mod_minimarket_quicklink';
    if(!mkdir($source,0770,true)&&!is_dir($source))throw new \RuntimeException('Cannot create temp directory');
    copy(__DIR__.'/../src/com_minimarket/admin/modules/mod_minimarket_quicklink/mod_minimarket_quicklink.xml',
        $source.'/mod_minimarket_quicklink.xml');
    require __DIR__.'/../src/com_minimarket/script.php';
    Factory::$app=new class {
        public array $messages=[];
        public function enqueueMessage(string $msg,string $type='message'):void {
            $this->messages[]=[$msg,$type];
        }
    };
    try {
        $db=new FakeDatabase();
        Factory::$db=$db;
        $script=new \com_minimarketInstallerScript();
        assertInstall($script->postflight('install',(object)[])===true,
            'fresh component install succeeds');
        assertInstall(count(Installer::$sources)===1 &&
            Installer::$sources[0]===$source,
            'Joomla independent module installer receives bundled path');
        assertInstall(count($db->updates)===1 && $db->assignments===1,
            'new module activated and assigned once');
        $joined=implode(' ', $db->updates[0]);
        assertInstall(str_contains($joined,"'status'") &&
            str_contains($joined,'published`=1') &&
            str_contains($joined,'access`=3') &&
            str_contains($joined,'showtitle`=0') &&
            str_contains($joined,'MiniMarket'),
            'first install positions in header and restricts access');
        assertInstall(Factory::$app->messages===[], 'fresh module install has no warning');
        $db->existing=true;
        $db->updates=[];
        $db->assignments=1;
        $count=count(Installer::$sources);
        assertInstall($script->postflight('update',(object)[])===true
            && count(Installer::$sources)===$count+1,
            'component upgrade independently upgrades module code');
        assertInstall($db->updates===[] && $db->assignments===1,
            'component upgrade preserves user module title, status, position and assignment');
        Installer::$shouldFail=true;
        $db->existing=false;
        $db->updates=[];
        $db->assignments=0;
        assertInstall($script->postflight('install',(object)[])===true,
            'failed module installation does not roll back MiniMarket component');
        assertInstall(count(Factory::$app->messages)===1 &&
            Factory::$app->messages[0][1]==='warning' &&
            str_contains(Factory::$app->messages[0][0],'ZIP'),
            'user gets clear manual module ZIP recovery instructions');
        Installer::$shouldFail=false;
        assertInstall($script->postflight('uninstall',(object)[])===true &&
            $db->updates===[], 'no quicklink mutation on component uninstall postflight');
    }finally {
        @unlink($source.'/mod_minimarket_quicklink.xml');
        @rmdir($source);
        @rmdir(dirname($source));
        @rmdir(dirname(dirname($source)));
        @rmdir(dirname(dirname(dirname($source))));
        @rmdir($tmp);
    }
    echo "QUICKLINK_INSTALL_FLOW_OK: $tests checks\n";
}
