<?php
// Exact allow-list matching; no Jeedom instance required.
require_once __DIR__ . '/../../core/class/smsPhoneNumber.class.php';

function check($condition, $message) {
	if (!$condition) {
		throw new RuntimeException($message);
	}
}

foreach (array('06 12 34 56 78', '+33 (6) 12-34-56-78', '0033612345678', '33612345678', '+33612345678') as $number) {
	check(smsPhoneNumber::isAuthorized($number, '+33612345678'), 'French format: ' . $number);
	check(smsPhoneNumber::isAuthorized('+33612345678', $number), 'Incoming French format: ' . $number);
}
check(smsPhoneNumber::isAuthorized('+442071234567; +33612345678', '00442071234567'), 'International list');
// Substring matching used to accept these.
foreach (array('+336123456789', '+3361234567', '612345678', '', '1234', '+44612345678') as $number) {
	check(!smsPhoneNumber::isAuthorized('+33612345678', $number), 'Reject partial or different sender: ' . $number);
}
check(!smsPhoneNumber::isAuthorized('prefix+33612345678suffix', '+33612345678'), 'Reject malformed configuration');
check(!smsPhoneNumber::isAuthorized('+33612345678;+33698765432', '+3361234567'), 'Reject prefix of a configured number');
foreach (array('Bouygues', '614', 'BOUYGUESTEL', '12345678901A', '36179') as $sender) {
	check(smsPhoneNumber::normalize($sender) === null, 'Service sender is not a phone number: ' . $sender);
}
echo "Phone numbers: all checks passed\n";
