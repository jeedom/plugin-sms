<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */
require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";

if (php_sapi_name() != 'cli' || isset($_SERVER['REQUEST_METHOD']) || !isset($_SERVER['argc'])) {
	if (config::byKey('api') != init('apikey') && init('apikey') != '') {
		connection::failed();
		echo 'Clef API non valide, vous n\'etes pas autorisé à effectuer cette action (jeeZwave)';
		die();
	}
}

if (isset($argv)) {
	foreach ($argv as $arg) {
		$argList = explode('=', $arg);
		if (isset($argList[0]) && isset($argList[1])) {
			$_GET[$argList[0]] = $argList[1];
		}
	}
}
$message = trim(init('message'));
$number = trim(init('number'));
if ($message == '' || $number == '') {
	die();
}

if ($number == 'none') {
	message::add('sms', 'Error : ' . $message, '', 'smscmderror');
	if (strpos($message, 'PIN') !== false) {
		config::save('port', 'none', 'sms');
	}
	die();
}

if ($number == 'signal_strength') {
	config::save('signal_strengh', $message, 'sms');
	foreach (eqLogic::byType('sms') as $eqLogic) {
		$cmd = $eqLogic->getCmd(null, 'signal');
		if (is_object($cmd)) {
			$cmd->event($message);
		}
	}
	die();
}

if ($number == 'network_name') {
	config::save('network_name', $message, 'sms');
	die();
}

$eqLogics = eqLogic::byType('sms');
if (count($eqLogics) < 1) {
	die();
}
$eqLogics = eqLogic::byType('sms');
if (strlen($number) == 11) {
	$number = '+' . $number;
}
$formatedPhoneNumber = '0' . substr($number, 3);
$reply = '';
$smsOk = false;
foreach ($eqLogics as $eqLogic) {
	foreach ($eqLogic->getCmd() as $cmd) {
		if (strpos($cmd->getConfiguration('phonenumber'), $number) === false && strpos($cmd->getConfiguration('phonenumber'), $formatedPhoneNumber) === false) {
			continue;
		}
		$params = array();
		$smsOk = true;
		log::add('sms', 'info', __('Message venant de ', __FILE__) . $formatedPhoneNumber . ' : ' . trim($message));
		if ($cmd->getConfiguration('user') != '') {
			$user = user::byId($cmd->getConfiguration('user'));
			if (is_object($user)) {
				$params['profile'] = $user->getLogin();
			}
		}
		$reply = interactQuery::tryToReply(trim($message), $params);
		if (trim($reply) != '') {
			$cmd->execute(array('title' => $reply, 'message' => '', 'number' => $number));
			log::add('sms', 'info', __("\nRéponse : ", __FILE__) . $reply);
		}
		$cmd_sms = $cmd->getEqlogic()->getCmd('info', 'sms');
		$cmd_sms->event(trim($message));
		break;
	}
}

if (!$smsOk) {
	log::add('sms', 'info', __('Message venant d\un numéro non autorisé : ', __FILE__) . $number . ' (' . $formatedPhoneNumber . ') : ' . trim($message));
}
