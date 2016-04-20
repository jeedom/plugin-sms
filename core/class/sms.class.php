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

	public static function cronDaily() {
		self::deamon_start();
	}

	public static function dependancy_info() {
		$return = array();
		$return['log'] = 'sms_update';
		$return['progress_file'] = '/tmp/dependancy_sms_in_progress';
		if (exec('sudo dpkg --get-selections | grep python-serial | grep install | wc -l') != 0) {
			$return['state'] = 'ok';
		} else {
			$return['state'] = 'nok';
		}
		return $return;
	}

	public static function dependancy_install() {
		log::remove('sms_update');
		$cmd = 'sudo /bin/bash ' . dirname(__FILE__) . '/../../ressources/install.sh';
		$cmd .= ' >> ' . log::getPathToLog('sms_update') . ' 2>&1 &';
		exec($cmd);
	}

	public static function deamon_info() {
		$return = array();
		$return['log'] = 'smscmd';
		$return['state'] = 'nok';
		$pid_file = '/tmp/sms.pid';
		if (file_exists($pid_file)) {
			if (posix_getsid(trim(file_get_contents($pid_file)))) {
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

	public static function deamon_start($_debug = false) {
		self::deamon_stop();
		$deamon_info = self::deamon_info();
		if ($deamon_info['launchable'] != 'ok') {
			throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
		}
		log::remove('smscmd');
		$port = config::byKey('port', 'sms');
		if ($port != 'auto') {
			$port = jeedom::getUsbMapping($port);
		}

		$sms_path = realpath(dirname(__FILE__) . '/../../ressources/smscmd');

		if (file_exists('/tmp/config_sms.xml')) {
			unlink('/tmp/config_sms.xml');
		}
		$replace_config = array(
			'#device#' => $port,
			'#text_mode#' => (config::byKey('text_mode', 'sms') == 1) ? 'yes' : 'no',
			'#socketport#' => config::byKey('socketport', 'sms', 55002),
			'#pin#' => config::byKey('pin', 'sms', 'None'),
			'#smsc#' => config::byKey('smsc', 'sms', 'None'),
			'#log_path#' => log::getPathToLog('sms'),
			'#pid_path#' => '/tmp/sms.pid',
			'#serial_rate#' => config::byKey('serial_rate', 'sms', 115200),
		);
		if (config::byKey('jeeNetwork::mode') == 'slave') {
			$replace_config['#sockethost#'] = network::getNetworkAccess('internal', 'ip', '127.0.0.1');
			$replace_config['#trigger_url#'] = config::byKey('jeeNetwork::master::ip') . '/plugins/sms/core/php/jeeSMS.php';
			$replace_config['#apikey#'] = config::byKey('jeeNetwork::master::apikey');
		} else {
			$replace_config['#sockethost#'] = '127.0.0.1';
			$replace_config['#trigger_url#'] = network::getNetworkAccess('internal', 'proto:127.0.0.1:port:comp') . '/plugins/sms/core/php/jeeSMS.php';
			$replace_config['#apikey#'] = config::byKey('api');
		}
		$config = file_get_contents($sms_path . '/config_tmpl.xml');
		$config = template_replace($replace_config, $config);
		file_put_contents('/tmp/config_sms.xml', $config);
		chmod('/tmp/config_sms.xml', 0777);

		$cmd = '/usr/bin/python ' . $sms_path . '/smscmd.py -l -o /tmp/config_sms.xml';
		if ($_debug) {
			$cmd .= ' -D';
		}
		log::add('smscmd', 'info', 'Lancement démon sms : ' . $cmd);
		$result = exec('nohup ' . $cmd . ' >> ' . log::getPathToLog('smscmd') . ' 2>&1 &');
		if (strpos(strtolower($result), 'error') !== false || strpos(strtolower($result), 'traceback') !== false) {
			log::add('smscmd', 'error', $result);
			return false;
		}
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
			log::add('smscmd', 'error', 'Impossible de lancer le démon sms, vérifiez le port', 'unableStartDeamon');
			return false;
		}
		message::removeAll('sms', 'unableStartDeamon');
		log::add('smscmd', 'info', 'Démon sms lancé');
		return true;
	}

	public static function deamon_stop() {
		$pid_file = '/tmp/sms.pid';
		if (file_exists($pid_file)) {
			$pid = intval(trim(file_get_contents($pid_file)));
			system::kill($pid);
		}
		system::fuserk(config::byKey('socketport', 'sms', 55002));
		$port = config::byKey('port', 'sms');
		if ($port != 'auto') {
			system::fuserk(jeedom::getUsbMapping($port));
		}
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
					$value = json_decode($value, true);
					foreach ($phonenumbers as $phonenumber) {
						if (is_array($value)) {
							$tmp_values[] = json_encode(array('number' => $phonenumber, 'message' => $value['message']));
						}
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
