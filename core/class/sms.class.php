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

	public static function slaveReload() {
		self::stopDeamon();
	}

	public static function health() {
		$return = array();
		$demon_state = self::deamonRunning();
		$return[] = array(
			'test' => __('Démon local', __FILE__),
			'result' => ($demon_state) ? __('OK', __FILE__) : __('NOK', __FILE__),
			'advice' => ($demon_state) ? '' : __('Peut être normal si vous êtes en déporté', __FILE__),
			'state' => $demon_state,
		);
		if (config::byKey('jeeNetwork::mode') == 'master') {
			foreach (jeeNetwork::byPlugin('sms') as $jeeNetwork) {
				try {
					$demon_state = $jeeNetwork->sendRawRequest('deamonRunning', array('plugin' => 'sms'));
				} catch (Exception $e) {
					$demon_state = false;
				}
				$return[] = array(
					'test' => __('Démon sur', __FILE__) . $jeeNetwork->getName(),
					'result' => ($demon_state) ? __('OK', __FILE__) : __('NOK', __FILE__),
					'advice' => '',
					'state' => $demon_state,
				);
			}
		}
		return $return;
	}

	public static function start() {
		if (config::byKey('allowStartDeamon', 'sms', 1) == 1 && config::byKey('port', 'sms', 'none') != 'none' && !self::deamonRunning()) {
			self::runDeamon();
		}
	}

	public static function cron15() {
		if (config::byKey('allowStartDeamon', 'sms', 1) == 1 && config::byKey('port', 'sms', 'none') != 'none' && !self::deamonRunning()) {
			self::runDeamon();
		}
	}

	public static function cronDaily() {
		if (config::byKey('allowStartDeamon', 'sms', 1) == 1 && config::byKey('port', 'sms', 'none') != 'none') {
			self::runDeamon();
		}
	}

	public static function runDeamon($_debug = false) {
		if (config::byKey('allowStartDeamon', 'edisio', 1) == 0) {
			return;
		}
		self::stopDeamon();
		$port = config::byKey('port', 'sms');
		if ($port != 'auto') {
			$port = jeedom::getUsbMapping($port);
			if (@!file_exists($port)) {
				throw new Exception(__('Le port : ', __FILE__) . print_r($port, true) . __(' n\'éxiste pas', __FILE__));
			}
			exec('sudo chmod 777 ' . $port . ' > /dev/null 2>&1');
		}
		$sms_path = realpath(dirname(__FILE__) . '/../../ressources/smscmd');

		if (file_exists($sms_path . '/config.xml')) {
			unlink($sms_path . '/config.xml');
		}
		$replace_config = array(
			'#device#' => $port,
			'#text_mode#' => (config::byKey('text_mode', 'sms') == 1) ? 'yes' : 'no',
			'#socketport#' => config::byKey('socketport', 'sms', 55002),
			'#pin#' => config::byKey('pin', 'sms', 'None'),
			'#smsc#' => config::byKey('smsc', 'sms', 'None'),
			'#log_path#' => log::getPathToLog('sms'),
			'#trigger_path#' => $sms_path . '/../../core/php/jeeSMS.php',
			'#pid_path#' => '/tmp/sms.pid',
		);
		if (config::byKey('jeeNetwork::mode') == 'slave') {
			$replace_config['#sockethost#'] = network::getNetworkAccess('internal', 'ip', '127.0.0.1');
		} else {
			$replace_config['#sockethost#'] = '127.0.0.1';
		}
		if (config::byKey('jeeNetwork::mode') == 'slave') {
			$config = str_replace(array('#ip_master#', '#apikey#'), array(config::byKey('jeeNetwork::master::ip'), config::byKey('jeeNetwork::master::apikey')), file_get_contents($sms_path . '/config_tmpl_remote.xml'));
		} else {
			$config = file_get_contents($sms_path . '/config_tmpl.xml');
		}
		$config = template_replace($replace_config, $config);
		file_put_contents($sms_path . '/config.xml', $config);
		chmod($sms_path . '/config.xml', 0777);

		$cmd = '/usr/bin/python ' . $sms_path . '/smscmd.py -l -o ' . $sms_path . '/config.xml';
		if ($_debug) {
			$cmd .= ' -D';
		}
		log::add('sms', 'info', 'Lancement démon sms : ' . $cmd);
		$result = exec('nohup ' . $cmd . ' >> ' . log::getPathToLog('smscmd') . ' 2>&1 &');
		if (strpos(strtolower($result), 'error') !== false || strpos(strtolower($result), 'traceback') !== false) {
			log::add('sms', 'error', $result);
			return false;
		}
		$i = 0;
		while ($i < 30) {
			if (self::deamonRunning()) {
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
		log::add('sms', 'info', 'Démon sms lancé');
		return true;
	}

	public static function deamonRunning() {
		$pid_file = '/tmp/sms.pid';
		if (!file_exists($pid_file)) {
			return false;
		}
		$pid = trim(file_get_contents($pid_file));
		if (posix_getsid($pid)) {
			return true;
		}
		unlink($pid_file);
		return false;
	}

	public static function stopDeamon() {
		$pid_file = '/tmp/sms.pid';
		if (file_exists($pid_file)) {
			$pid = intval(trim(file_get_contents($pid_file)));
			posix_kill($pid, 15);
			if (self::deamonRunning()) {
				sleep(1);
				posix_kill($pid, 9);
			}
			if (self::deamonRunning()) {
				sleep(1);
				exec('kill -9 ' . $pid . ' > /dev/null 2>&1');
			}
		}
		$sms_path = realpath(dirname(__FILE__) . '/../../ressources/smscmd/smscmd.py');
		exec('fuser -k ' . config::byKey('socketport', 'sms', 55002) . '/tcp > /dev/null 2>&1');
		exec('sudo fuser -k ' . config::byKey('socketport', 'sms', 55002) . '/tcp > /dev/null 2>&1');
		return self::deamonRunning();
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
		$signal->setEventOnly(1);
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
		$sms->setEventOnly(1);
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
		$sender->setEventOnly(1);
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
		$message = trim($_options['title'] . ' ' . $_options['message']);
		if (config::byKey('text_mode', 'sms') == 1) {
			$message = self::cleanSMS(trim($message), true);
		}
		if (strlen($message) > config::byKey('maxChartByMessage', 'sms')) {
			$messages = str_split($message, config::byKey('maxChartByMessage', 'sms'));
			foreach ($messages as $message_split) {
				$values[] = json_encode(array('number' => $number, 'message' => $message_split));
			}
		} else {
			$values[] = json_encode(array('number' => $number, 'message' => $message));
		}
		if (!isset($_options['number'])) {
			$phonenumbers = explode(';', $this->getConfiguration('phonenumber'));
			if (is_array($phonenumbers) && count($phonenumbers) > 1) {
				$tmp_values = array();
				foreach ($values as $value) {
					foreach ($phonenumbers as $phonenumber) {
						$value = json_decode($value, true);
						$tmp_values[] = json_encode(array('number' => $phonenumber, 'message' => $message));
					}
				}
				$values = $tmp_values;
			}
		}

		if (config::byKey('jeeNetwork::mode') == 'master') {
			foreach (jeeNetwork::byPlugin('sms') as $jeeNetwork) {
				foreach ($values as $value) {
					$socket = socket_create(AF_INET, SOCK_STREAM, 0);
					socket_connect($socket, $jeeNetwork->getRealIp(), config::byKey('socketport', 'sms', 55002));
					socket_write($socket, $value, strlen($value));
					socket_close($socket);
				}
			}
		}
		if (config::byKey('port', 'sms', 'none') != 'none') {
			foreach ($values as $value) {
				$socket = socket_create(AF_INET, SOCK_STREAM, 0);
				socket_connect($socket, '127.0.0.1', config::byKey('socketport', 'sms', 55002));
				socket_write($socket, $value, strlen($value));
				socket_close($socket);
			}
		}
	}

}