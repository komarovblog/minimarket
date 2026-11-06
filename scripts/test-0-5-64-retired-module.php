<?php
/**
 * Regression: remove ONLY the retired v0.5.62/63 quicklink extension through
 * Joomla's official uninstall path; leave the new standalone toolbar and every
 * unrelated administrative module untouched.
 */
declare(strict_types=1);

namespace Joomla\Database {
    interface DatabaseInterface {}
}
namespace Joomla\CMS\Installer {
    class InstallerScript {
        public int $dashboards=0;
        protected function addDashboardMenu(string $name,string $menu):void {$this->dashboards++;}
    }
    class Installer {
        public static array $uninstallCalls=[];
        public static bool $shouldSucceed=true;
        public function setDatabase(object $db):void {}
        public function uninstall(string $type,int $extensionId):bool{
            self::$uninstallCalls[]=[$type,$extensionId];
            return self::$shouldSucceed;
        }
    }
}
namespace Joomla\CMS {
    class Factory {
        public static object $db;
        public static object $app;
        public static function getContainer():object{
            return new class {
                public function get(string $type):object {return Factory::$db;}
            };
        }
        public static function getApplication():object{return self::$app;}
    }
}
namespace MiniMarket\RetiredShortcutTest {
    use Joomla\CMS\Factory;
    use Joomla\CMS\Installer\Installer;
    use Joomla\Database\DatabaseInterface;
    $n=0;
    function ok(bool $test,string $message):void {
        global $n; ++$n;
        if (!$test)throw new \RuntimeException('RETIRED_QUICKLINK_FAIL: '.$message);
    }
    class Query {
        public string $table='';
        public array $clauses=[];
        public function select($x):self{return $this;}
        public function from($x):self {$this->table=(string)$x;return $this;}
        public function where($x):self{$this->clauses[]=(string)$x;return $this;}
    }
    class Db implements DatabaseInterface {
        public ?Query $q=null;
        public int $legacy=42;
        public array $otherExtensions=[43,44,45];
        public function getQuery(bool $new=true):Query{return new Query();}
        public function quoteName(string $name):string{return '`'.$name.'`';}
        public function quote(string $value):string{return "'".str_replace("'","''",$value)."'";}
        public function setQuery($query):void{$this->q=$query;}
        public function loadResult():int{
            if($this->q?->table==='`#__modules`')return 1; // dashboard exists
            if($this->q?->table!=='`#__extensions`')throw new \RuntimeException('Unexpected SQL');
            $where=implode(' ', $this->q->clauses);
            if(!str_contains($where,"'mod_minimarket_quicklink'") ||
               !str_contains($where,"'module'") ||
               !str_contains($where,'client_id`=1')){
                throw new \RuntimeException('Uninstall query not restricted to exact retired administrator module');
            }
            return $this->legacy;
        }
    }
    \define('_JEXEC',1);
    require __DIR__.'/../src/com_minimarket/script.php';
    Factory::$app=new class {
        public array $messages=[];
        public function enqueueMessage(string $msg,string $level='message'):void {
            $this->messages[]=[$level,$msg];
        }
    };
    $db=new Db();Factory::$db=$db;
    $component=new \com_minimarketInstallerScript();
    ok($component->postflight('update',null)===true,
        'component can update while retiring old shortcut');
    ok(Installer::$uninstallCalls===[['module',42]],
        'exact legacy module ID passed to native Joomla uninstaller');
    ok($db->otherExtensions===[43,44,45] &&
        !str_contains(file_get_contents(__DIR__.'/../src/com_minimarket/script.php'),
        "'mod_minimarket_toolbar'"),
        'new toolbar and unrelated modules never removed or installed by component');
    $db->legacy=0;Installer::$uninstallCalls=[];
    ok($component->postflight('update',null)===true &&
        Installer::$uninstallCalls===[],
        'repeat component update is safe after legacy module is gone');
    $db->legacy=42;Installer::$shouldSucceed=false;
    ok($component->postflight('update',null)===true &&
       count(Factory::$app->messages)===1 &&
       Factory::$app->messages[0][0]==='warning',
       'legacy uninstall failure is a warning and never blocks catalog update');
    Installer::$shouldSucceed=true;
    $before=count(Installer::$uninstallCalls);
    ok($component->postflight('uninstall',null)===true &&
       count(Installer::$uninstallCalls)===$before,
       'component uninstall does not mutate shortcut state');
    $xml=simplexml_load_file(__DIR__.'/../src/com_minimarket/minimarket.xml');
    ok((string)$xml->version==='0.5.64' &&
       count($xml->xpath('/extension/administration/files/folder[text()="modules"]'))===0,
       'new component does not ship any header module inside ZIP');
    echo "RETIRED_QUICKLINK_064_OK: $n checks\n";
}
