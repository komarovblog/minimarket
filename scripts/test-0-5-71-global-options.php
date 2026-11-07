<?php
/** Joomla component Options migration using an isolated in-memory DB mock. */
declare(strict_types=1);
namespace Joomla\Database { interface DatabaseInterface {} }
namespace Joomla\CMS\Installer {
    class InstallerScript {
        protected function addDashboardMenu(string $a,string $b):void {}
    }
    class Installer {
        public function setDatabase(object $db):void {}
        public function uninstall(string $type,int $id):bool{return true;}
    }
}
namespace Joomla\CMS {
    final class Factory {
        public static object $db;
        public static function getContainer():object {
            return new class {
                public function get(string $name):object{return Factory::$db;}
            };
        }
        public static function getApplication():object {
            return new class {
                public function enqueueMessage(string $text,string $type='warning'):void {
                    throw new \RuntimeException('Unexpected installer warning: '.$text);
                }
            };
        }
    }
}
namespace MiniMarket\Tests\GridOptions {
    use Joomla\CMS\Factory;
    use Joomla\Database\DatabaseInterface;
    $checks=0;
    function expect(bool $passed,string $why):void {
        global $checks;$checks++;
        if(!$passed)throw new \RuntimeException('MM71_OPTIONS_FAIL: '.$why);
    }
    class Query {
        public string $table='';
        public array $clauses=[];
        public array $sets=[];
        public function select(mixed $x):self{return $this;}
        public function from(string $x):self{$this->table=$x;return $this;}
        public function where(string $x):self{$this->clauses[]=$x;return $this;}
        public function update(string $x):self{$this->table=$x;return $this;}
        public function set(string $x):self{$this->sets[]=$x;return $this;}
    }
    final class Db implements DatabaseInterface {
        public string $params='{}';
        public int $writes=0;
        public Query $query;
        public function getQuery(bool $new=true):Query{return new Query();}
        public function quoteName(string $name):string{return chr(96).$name.chr(96);}
        public function quote(string $value):string{return "'".str_replace("'","''",$value)."'";}
        public function setQuery(Query $q):void{$this->query=$q;}
        public function loadResult():int{
            if($this->query->table===$this->quoteName('#__modules'))return 1;
            if($this->query->table===$this->quoteName('#__extensions'))return 0;
            throw new \RuntimeException('Unexpected loadResult');
        }
        public function loadAssoc():?array{
            if($this->query->table!==$this->quoteName('#__extensions')
                || !str_contains(implode(' ',$this->query->clauses),"'com_minimarket'")) {
                throw new \RuntimeException('Unscoped settings read');
            }
            return ['extension_id'=>7,'params'=>$this->params];
        }
        public function execute():void{
            expect($this->query->table===$this->quoteName('#__extensions'),
                'writes only component settings');
            expect(str_contains(implode(' ',$this->query->clauses),'extension_id'),
                'scopes update to extension ID');
            $sql=implode(' ',$this->query->sets);
            $prefix=$this->quoteName('params').'=';
            if(!str_starts_with($sql,$prefix))throw new \RuntimeException('Invalid UPDATE');
            $quoted=substr($sql,strlen($prefix));
            if(!preg_match("/^'(.*)'$/s",$quoted,$match)){
                throw new \RuntimeException('No quoted JSON');
            }
            $this->params=str_replace("''","'",$match[1]);
            $this->writes++;
        }
    }
    \define('_JEXEC',1);
    require __DIR__.'/../src/com_minimarket/script.php';
    $db=new Db();Factory::$db=$db;
    $db->params=json_encode([
        'category_product_layout'=>'grid','inventory_enabled'=>0,
        'category_cards_mobile'=>2,'category_cards_tablet'=>3,
        'category_cards_desktop'=>4,'category_cards_wide'=>5,
        'other_setting'=>['label'=>'untouched']
    ],JSON_THROW_ON_ERROR);
    $installer=new \com_minimarketInstallerScript();
    expect($installer->postflight('update',null)===true,'upgrade completed');
    $params=json_decode($db->params,true,512,JSON_THROW_ON_ERROR);
    expect($db->writes===1,'old JSON migrated exactly once');
    foreach([
        'category_cards_xs'=>2,'category_cards_sm'=>2,
        'category_cards_md'=>3,'category_cards_lg'=>4,
        'category_cards_xl'=>5,'category_cards_xxl'=>5
    ] as $key=>$value)expect(($params[$key]??null)===$value,'preserves '.$key);
    foreach(['mobile','tablet','desktop','wide'] as $old) {
        expect(!array_key_exists('category_cards_'.$old,$params),'removes '.$old);
    }
    expect($params['inventory_enabled']===0 &&
        $params['other_setting']['label']==='untouched',
        'other settings unchanged');
    $saved=$db->params;
    expect($installer->postflight('update',null)===true &&
        $db->writes===1 && $db->params===$saved,'repeat is idempotent');
    $db->params=json_encode(['category_cards_mobile'=>1,
        'category_cards_xs'=>6,'category_cards_wide'=>3,
        'category_cards_xxl'=>5],JSON_THROW_ON_ERROR);
    expect($installer->postflight('update',null)===true,'partial settings migrated');
    $params=json_decode($db->params,true,512,JSON_THROW_ON_ERROR);
    expect($params['category_cards_xs']===6 && $params['category_cards_xxl']===5,
        'previously migrated new choices have priority');
    echo 'MM71_GLOBAL_OPTIONS_OK: '.$checks." checks\n";
}
