<?php
// Daemon launch option checks; no Jeedom instance or modem required.
require_once __DIR__ . '/../../core/class/smsDaemonOptions.class.php';

function check($condition, $message) {
	if (!$condition) {
		throw new RuntimeException($message);
	}
}

$payload = "value with spaces ' \" ; $(printf injected) `printf injected`";
$arguments = smsDaemonOptions::arguments(array('callback' => $payload, 'pin' => ''));
$command = escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg('echo json_encode(array_slice($argv, 1));') . ' -- ' . $arguments;
check(json_decode(shell_exec($command), true) === array('--callback', $payload, '--pin', 'None'), 'Shell arguments survive literally');

$options = array('device' => 'auto', 'socketport' => '55002', 'serialrate' => '115200', 'cycle' => '0.3', 'pin' => '', 'smsc' => '');
smsDaemonOptions::validate($options);
smsDaemonOptions::validate(array_merge($options, array('pin' => '1234', 'smsc' => '+33600000000')));
$invalid = array('socketport' => '55002;id', 'serialrate' => '-1', 'cycle' => 'INF', 'pin' => '1234;id', 'smsc' => '+33";id', 'device' => '/tmp/modem');
foreach ($invalid as $key => $value) {
	try {
		smsDaemonOptions::validate(array_merge($options, array($key => $value)));
	} catch (InvalidArgumentException $e) {
		continue;
	}
	throw new RuntimeException('Accepted invalid option: ' . $key);
}

check(!smsDaemonOptions::isSerialDevice(__FILE__), 'Reject regular file');
check(!smsDaemonOptions::isSerialDevice('/dev/../tmp'), 'Reject directory');
if (file_exists('/dev/null')) {
	check(smsDaemonOptions::isSerialDevice('/dev/null'), 'Accept character device');
}
echo "Daemon options: all checks passed\n";
