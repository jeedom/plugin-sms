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

if (isset($result['devices'])) {
	foreach ($result['devices'] as $key => $datas) {
		$message = $datas['message'];
		$number = $datas['number'];
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

		$eqLogics = eqLogic::byType('sms');
		if (count($eqLogics) < 1) {
			continue;
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
				$params = array('plugin' => 'sms');
				$smsOk = true;
				log::add('sms', 'info', __('Message venant de ', __FILE__) . $formatedPhoneNumber . ' : ' . trim($message));
				if ($cmd->getCache('storeVariable', 'none') != 'none') {
					$cmd->askResponse($message);
					continue (3);
				}
				if ($cmd->getConfiguration('user') != '') {
					$user = user::byId($cmd->getConfiguration('user'));
					if (is_object($user)) {
						$params['profile'] = $user->getLogin();
					}
				}
				$parameters['reply_cmd'] = $cmd;
				$reply = interactQuery::tryToReply(trim($message), $params);
				if (trim($reply['reply']) != '') {
					$cmd->execute(array('title' => $reply['reply'], 'message' => '', 'number' => $number));
					log::add('sms', 'info', __("\nRéponse : ", __FILE__) . $reply['reply']);
				}
				$cmd_sms = $cmd->getEqlogic()->getCmd('info', 'sms');
				$cmd_sms->event(trim($message));
				$cmd_sender = $cmd->getEqlogic()->getCmd('info', 'sender');
				$cmd_sender->event($cmd->getName());
				break;
			}
		}

		if (!$smsOk) {
			log::add('sms', 'info', __('Message venant d\un numéro non autorisé : ', __FILE__) . secureXSS($number) . ' (' . secureXSS($formatedPhoneNumber) . ') : ' . secureXSS(trim($message)));
		}
	}
}
