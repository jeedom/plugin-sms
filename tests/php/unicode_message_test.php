<?php
// Load the real command class without booting Jeedom or using any socket/modem.
class eqLogic {}
class cmd {}
require_once __DIR__ . '/../../core/class/sms.class.php';
function check($condition, $message) {
 if (!$condition) {throw new RuntimeException($message);}
}
$cases=array(
 array(str_repeat('a',159).'é',160,1),
 array(str_repeat('a',159).'éZ',160,2),
 array(str_repeat('é',161),160,2),
 array(str_repeat('€',141),140,2),
 array("Température : 19 °C\nFenêtre fermée",10,4),
 array('你好世界',2,2),
 array('A🙂B',2,2),
 array("e\u{0301}e\u{0301}",1,4),
 array(str_repeat('A',280),140,2),
 array('',140,1),
);
foreach($cases as $case){
 list($text,$limit,$expectedCount)=$case;
 $parts=smsCmd::splitMessage($text,(string)$limit);
 check(count($parts)===$expectedCount,'Unexpected part count');
 check(implode('',$parts)===$text,'Text changed during splitting');
 foreach($parts as $part){
  check(preg_match_all('/./us',$part)<=$limit,'Part exceeds character limit');
  $json=json_encode(array('message'=>$part));
  check($json!==false,'Invalid JSON chunk');
  check(json_decode($json,true)['message']===$part,'JSON text changed');
 }
}
foreach(array(0,-1,'','bad','1.5') as $limit){
 try{smsCmd::splitMessage('test',$limit);}catch(InvalidArgumentException $e){continue;}
 throw new RuntimeException('Invalid limit accepted');
}
try{smsCmd::splitMessage("bad\xC3",140);throw new RuntimeException('Invalid UTF-8 accepted');}catch(InvalidArgumentException $e){}
echo "Unicode splitting: 10 text cases and 6 invalid inputs passed\n";
