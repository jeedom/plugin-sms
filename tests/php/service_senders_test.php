<?php
// Execute the actual inbound routing block without a Jeedom instance or actions.
class log { public static function add(...$args) {} }
function __($value, $file) {return $value;}
function secureXSS($value) {return htmlspecialchars($value, ENT_QUOTES);}
function handleMessage(...$args) {throw new RuntimeException('Service sender reached interactions');}
class FakeCommand {
 public function getConfiguration($key) {return 'Bouygues;614;0666000000';}
}
class FakeEquipment {
 public $events = array();
 private $allow;
 public function __construct($allow) {$this->allow=$allow;}
 public function getCmd() {return array(new FakeCommand());}
 public function getConfiguration($key, $default=0) {return $key==='allowUnknownOrigin' ? $this->allow : ($key==='autoAddNewNumber' ? 1 : $default);}
 public function checkAndUpdateCmd($key,$value) {$this->events[$key]=$value;}
}
$source=file_get_contents(__DIR__.'/../../core/php/jeeSMS.php');
$start=strpos($source,"if (isset(\$result['devices'])) {");
$end=strpos($source,"/**\n * Handle sms", $start);
$block=substr($source,$start,$end-$start);
foreach(array('Bouygues','614','BOUYGUESTEL','12345678901A') as $sender) {
 foreach(array(0,1) as $allow) {
  $eq=new FakeEquipment($allow);$eqLogics=array($eq);
  $result=array('devices'=>array(array('number'=>$sender,'message'=>'Operator announcement')));
  eval($block);
  $expected=$allow ? array('sms'=>'Operator announcement','sender'=>$sender) : array();
  if($eq->events!==$expected){throw new RuntimeException('Routing failed for '.$sender);}
 }
}
echo "Service sender routing: 8 cases passed\n";
