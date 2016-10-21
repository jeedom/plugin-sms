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

/* * ***************************Includes********************************* */

class sms extends eqLogic {
	/*     * ***********************Methode static*************************** */

	public static function deamon_info() {
		$return = array();
		$return['log'] = 'sms';
		$return['state'] = 'nok';
		$pid_file = '/tmp/smsd.pid';
		if (file_exists($pid_file)) {
			if (@posix_getsid(trim(file_get_contents($pid_file)))) {
				$return['state'] = 'ok';
			} else {
				shell_exec('sudo rm -rf ' . $pid_file . ' 2>&1 > /dev/null;rm -rf ' . $pid_file . ' 2>&1 > /dev/null;');
			}
		}
		$return['launchable'] = 'ok';
		$port = config::byKey('port', 'sms');
		if ($port != 'auto') {
			$port = jeedom::getUsbMapping($port);
			if (@!file_exists($port)) {
				$return['launchable'] = 'nok';
				$return['launchable_message'] = __('Le port n\'est pas configuré', __FILE__);
			}
			exec('sudo chmod 777 ' . $port . ' > /dev/null 2>&1');
		}
		return $return;
	}

	public static function dependancy_info() {
		$return = array();
		$return['progress_file'] = '/tmp/dependancy_sms_in_progress';
		if (exec('sudo dpkg --get-selections | grep -E "python\-serial|python\-request|python\-pyudev" | grep -v desinstall | wc -l') >= 3) {
			$return['state'] = 'ok';
		} else {
			$return['state'] = 'nok';
		}
		return $return;
	}
	public static function dependancy_install() {
		log::remove('sms_update');
		$cmd = 'sudo /bin/bash ' . dirname(__FILE__) . '/../../resources/install.sh';
		$cmd .= ' >> ' . log::getPathToLog('sms_dependancy') . ' 2>&1 &';
		exec($cmd);
	}

	public static function deamon_start() {
		self::deamon_stop();
		$deamon_info = self::deamon_info();
		if ($deamon_info['launchable'] != 'ok') {
			throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
		}
		$port = config::byKey('port', 'sms');
		if ($port != 'auto') {
			$port = jeedom::getUsbMapping($port);
		}
		$sms_path = realpath(dirname(__FILE__) . '/../../resources/smsd');
		$cmd = '/usr/bin/python ' . $sms_path . '/smsd.py';
		$cmd .= ' --device=' . $port;
		$cmd .= ' --loglevel=' . log::convertLogLevel(log::getLogLevel('sms'));
		$cmd .= ' --socketport=' . config::byKey('socketport', 'sms');
		$cmd .= ' --serialrate=' . config::byKey('serial_rate', 'sms');
		$cmd .= ' --pin=' . config::byKey('pin', 'sms', 'None');
		$cmd .= ' --textmode=';
		$cmd .= (config::byKey('text_mode', 'sms') == 1) ? 'yes' : 'no';
		$cmd .= ' --smsc=' . config::byKey('smsc', 'sms', 'None');
		$cmd .= ' --callback=' . network::getNetworkAccess('internal', 'proto:127.0.0.1:port:comp') . '/plugins/sms/core/php/jeeSMS.php';
		$cmd .= ' --apikey=' . jeedom::getApiKey('sms');
		log::add('sms', 'info', 'Lancement démon sms : ' . $cmd);
		$result = exec($cmd . ' >> ' . log::getPathToLog('sms') . ' 2>&1 &');
		$i = 0;
		while ($i < 30) {
			$deamon_info = self::deamon_info();
			if ($deamon_info['state'] == 'ok') {
				break;
			}
			sleep(1);
			$i++;
		}
		if ($i >= 30) {
			log::add('sms', 'error', 'Impossible de lancer le démon sms, vérifiez le port', 'unableStartDeamon');
			return false;
		}
		message::removeAll('sms', 'unableStartDeamon');
		return true;
	}

	public static function deamon_stop() {
		$pid_file = '/tmp/smsd.pid';
		if (file_exists($pid_file)) {
			$pid = intval(trim(file_get_contents($pid_file)));
			system::kill($pid);
		}
		system::kill('smsd.py');
		system::fuserk(config::byKey('socketport', 'sms'));
		$port = config::byKey('port', 'sms');
		if ($port != 'auto') {
			system::fuserk(jeedom::getUsbMapping($port));
		}
		sleep(1);
	}

/*     * *********************Methode d'instance************************* */

	public function postSave() {
		$signal = $this->getCmd(null, 'signal');
		if (!is_object($signal)) {
			$signal = new smsCmd();
			$signal->setLogicalId('signal');
			$signal->setIsVisible(0);
			$signal->setName(__('Signal', __FILE__));
		}
		$signal->setType('info');
		$signal->setSubType('numeric');
		$signal->setEqLogic_id($this->getId());
		$signal->save();

		$sms = $this->getCmd(null, 'sms');
		if (!is_object($sms)) {
			$sms = new smsCmd();
			$sms->setLogicalId('sms');
			$sms->setIsVisible(0);
			$sms->setName(__('Message', __FILE__));
		}
		$sms->setType('info');
		$sms->setSubType('string');
		$sms->setEqLogic_id($this->getId());
		$sms->save();

		$sender = $this->getCmd(null, 'sender');
		if (!is_object($sender)) {
			$sender = new smsCmd();
			$sender->setLogicalId('sender');
			$sender->setIsVisible(0);
			$sender->setName(__('Expediteur', __FILE__));
		}
		$sender->setType('info');
		$sender->setSubType('string');
		$sender->setEqLogic_id($this->getId());
		$sender->save();
	}
}

class smsCmd extends cmd {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	public static function cleanSMS($_message) {
		$caracteres = array(
			'À' => 'a', 'Á' => 'a', 'Â' => 'a', 'Ä' => 'a', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a', '@' => 'a',
			'È' => 'e', 'É' => 'e', 'Ê' => 'e', 'Ë' => 'e', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', '€' => 'e',
			'Ì' => 'i', 'Í' => 'i', 'Î' => 'i', 'Ï' => 'i', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
			'Ò' => 'o', 'Ó' => 'o', 'Ô' => 'o', 'Ö' => 'o', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'ö' => 'o',
			'Ù' => 'u', 'Ú' => 'u', 'Û' => 'u', 'Ü' => 'u', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'µ' => 'u',
			'Œ' => 'oe', 'œ' => 'oe',
			'$' => 's');
		return preg_replace('#[^A-Za-z0-9 \n\.\'=\*:]+#', '', strtr($_message, $caracteres));
	}

	/*     * *********************Methode d'instance************************* */

	public function dontRemoveCmd() {
		if ($this->getLogicalId() == 'signal') {
			return true;
		}
		return false;
	}

	public function preSave() {
		if ($this->getSubtype() == 'message') {
			$this->setDisplay('title_disable', 1);
		}
	}

	public function execute($_options = null) {
		$number = $this->getConfiguration('phonenumber');
		if (isset($_options['number'])) {
			$number = $_options['number'];
		}
		if (isset($_options['answer'])) {
			$_options['message'] .= ' (' . implode(';', $_options['answer']) . ')';
		}
		$values = array();
		if (isset($_options['message']) && $_options['message'] != '') {
			$message = trim($_options['message']);
		} else {
			$message = trim($_options['title'] . ' ' . $_options['message']);
		}
		if (config::byKey('text_mode', 'sms') == 1) {
			$message = self::cleanSMS(trim($message), true);
		}
		if (strlen($message) > config::byKey('maxChartByMessage', 'sms')) {
			$messages = str_split($message, config::byKey('maxChartByMessage', 'sms'));
			foreach ($messages as $message_split) {
				$values[] = json_encode(array('apikey' => jeedom::getApiKey('sms'), 'number' => $number, 'message' => $message_split));
			}
		} else {
			$values[] = json_encode(array('apikey' => jeedom::getApiKey('sms'), 'number' => $number, 'message' => $message));
		}
		if (!isset($_options['number'])) {
			$phonenumbers = explode(';', $this->getConfiguration('phonenumber'));
			if (is_array($phonenumbers) && count($phonenumbers) > 1) {
				$tmp_values = array();
				foreach ($values as $value) {
					$value = json_decode($value, true);
					foreach ($phonenumbers as $phonenumber) {
						if (is_array($value)) {
							$tmp_values[] = json_encode(array('apikey' => jeedom::getApiKey('sms'), 'number' => $phonenumber, 'message' => $value['message']));
						}
					}
				}
				$values = $tmp_values;
			}
		}
		if (config::byKey('port', 'sms', 'none') != 'none') {
			foreach ($values as $value) {
				$socket = socket_create(AF_INET, SOCK_STREAM, 0);
				socket_connect($socket, '127.0.0.1', config::byKey('socketport', 'sms'));
				socket_write($socket, $value, strlen($value));
				socket_close($socket);
			}
		}
	}

}
