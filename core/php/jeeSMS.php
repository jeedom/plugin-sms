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

if (!jeedom::apiAccess(init('apikey'), 'sms')) {
	echo __('Vous n\'etes pas autorisé à effectuer cette action', __FILE__);
	die();
}
if (init('test') != '') {
	echo 'OK';
	die();
}
$result = json_decode(file_get_contents("php://input"), true);
if (!is_array($result)) {
	die();
}

if (isset($result['number']) && $result['number'] == 'signal_strength' && isset($result['message'])) {
	config::save('signal_strengh', $result['message'], 'sms');
	foreach (eqLogic::byType('sms') as $eqLogic) {
		$cmd = $eqLogic->getCmd(null, 'signal');
		if (is_object($cmd)) {
			$cmd->event($result['message']);
		}
	}
	die();
}

if (isset($result['number']) && $result['number'] == 'network_name' && isset($result['message'])) {
	config::save('network_name', $result['message'], 'sms');
	die();
}

if (isset($result['number']) && $result['number'] == 'none' && isset($result['message'])) {
	message::add('sms', 'Error : ' . $result['message'], '', 'smscmderror');
	if (strpos($result['message'], 'PIN') !== false) {
		config::save('deamonAutoMode', 0, 'sms');
	}
}
/** @var array<sms> */
$eqLogics = eqLogic::byType('sms', true);
if (count($eqLogics) < 1) {
	log::add('sms', 'debug', __("Aucun équipement SMS activé", __FILE__));
	die();
}
if (isset($result['devices'])) {
	foreach ($result['devices'] as $key => $datas) {
		$message = trim($datas['message']);
		$number = $datas['number'];
		if (strlen($number) < 10) {
			continue;
		}
		if ($message == '' || $number == '') {
			continue;
		}
		if ($number == 'none') {
			message::add('sms', 'Error : ' . $message, '', 'smscmderror');
			if (strpos($message, 'PIN') !== false) {
				config::save('allowStartDeamon', 0, 'sms');
			}
			continue;
		}
		if (strlen($number) == 11) {
			$number = '+' . $number;
		}
		$formatedPhoneNumber = '0' . substr($number, 3);
		$reply = '';
		$smsOk = false;
		foreach ($eqLogics as $eqLogic) {
			/** @var cmd $cmd */
			foreach ($eqLogic->getCmd() as $cmd) {
				if (strpos($cmd->getConfiguration('phonenumber'), $number) === false && strpos($cmd->getConfiguration('phonenumber'), $formatedPhoneNumber) === false) {
					continue;
				}
				$smsOk = true;
				log::add('sms', 'info', __('Message venant de ', __FILE__) . $formatedPhoneNumber . ' : ' . $message);
				if ($cmd->askResponse($message)) {
					continue (3);
				}
				handleMessage($cmd, $number, $message);
				break;
			}
			if (!$smsOk) {
				if ($eqLogic->getConfiguration('allowUnknownOrigin', 0) == 1) {
					if ($eqLogic->getConfiguration('autoAddNewNumber', 0) == 1) {
						log::add('sms', 'info', __('Message venant d\'un numéro inconnu, création auto activée:', __FILE__) . secureXSS($number) . ' (' . secureXSS($formatedPhoneNumber) . ') : ' . secureXSS($message));
						$new_number = new smsCmd();
						$new_number->setType('action');
						$new_number->setSubType('message');
						$new_number->setEqLogic_id($eqLogic->getId());
						$new_number->setName($number);
						$new_number->setConfiguration('phonenumber', $number);
						$new_number->save();

						handleMessage($new_number, $number, $message);
					} else {
						log::add('sms', 'info', __('Message venant d\'un numéro inconnu mais les numéros inconnus sont autorisés : ', __FILE__) . secureXSS($number) . ' (' . secureXSS($formatedPhoneNumber) . ') : ' . secureXSS($message));
						$eqLogic->checkAndUpdateCmd('sms', $message);
						$eqLogic->checkAndUpdateCmd('sender', $number);
					}
					$smsOk = true;
				}
			}
		}

		if (!$smsOk) {
			log::add('sms', 'info', __('Message venant d\'un numéro non autorisé : ', __FILE__) . secureXSS($number) . ' (' . secureXSS($formatedPhoneNumber) . ') : ' . secureXSS($message));
		}
	}
}

/**
 * Handle sms message when smsCmd has been found. It will check for interaction and then update 'sms' & 'sender' cmd on the eqLogic
 *
 * @param smsCmd $cmd
 * @param string $number
 * @param string $message
 * @return void
 */
function handleMessage($cmd, $number, $message) {
	/** @var eqLogic */
	$eqLogic = $cmd->getEqlogic();
	if ($eqLogic->getConfiguration('disableInteract', '0') == '0') {
		$params = array('plugin' => 'sms');
		if ($cmd->getConfiguration('user') != '') {
			$user = user::byId($cmd->getConfiguration('user'));
			if (is_object($user)) {
				$params['profile'] = $user->getLogin();
			}
		}
		$parameters['reply_cmd'] = $cmd;
		$reply = interactQuery::tryToReply($message, $params);
		if (trim($reply['reply']) != '') {
			$cmd->execute(array('title' => $reply['reply'], 'message' => '', 'number' => $number));
			log::add('sms', 'info', __("\nRéponse : ", __FILE__) . $reply['reply']);
		}
	} else {
		log::add('sms', 'debug', __("Interaction désactivée.", __FILE__));
	}
	$eqLogic->checkAndUpdateCmd('sms', $message);
	$eqLogic->checkAndUpdateCmd('sender', $number);
}
