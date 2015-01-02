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

    public static function start() {
        $port = config::byKey('port', 'sms', 'none');
        if ($port != 'none') {
            if (file_exists(jeedom::getUsbMapping($port))) {
                if (!self::deamonRunning()) {
                    self::runDeamon();
                }
                message::removeAll('sms', 'noSMSComPort');
            } else {
                log::add('sms', 'error', __('Le port du SMS est vide ou n\'éxiste pas', __FILE__), 'noSMSComPort');
            }
        }
    }

    public static function cronHourly() {
        $port = config::byKey('port', 'sms', 'none');
        if ($port != 'none') {
            if (file_exists(jeedom::getUsbMapping($port))) {
                if (!self::deamonRunning()) {
                    self::runDeamon();
                }
                message::removeAll('sms', 'noSMSComPort');
            } else {
                log::add('sms', 'error', __('Le port du SMS est vide ou n\'éxiste pas', __FILE__), 'noSMSComPort');
            }
        }
    }

    public static function cronDaily() {
        if (self::deamonRunning()) {
            self::stopDeamon();
            if (!self::deamonRunning()) {
                self::runDeamon();
            }
        }
    }

    public static function runDeamon($_debug = false) {
        log::add('sms', 'info', 'Lancement du démon sms');
        $port = jeedom::getUsbMapping(config::byKey('port', 'sms'));
        if (!file_exists($port)) {
            config::save('port', '', 'sms');
            throw new Exception(__('Le port : ', __FILE__) . print_r($port, true) . __(' n\'éxiste pas', __FILE__));
        }
        $sms_path = realpath(dirname(__FILE__) . '/../../ressources/smscmd');

        if (file_exists($sms_path . '/config.xml')) {
            unlink($sms_path . '/config.xml');
        }
        $replace_config = array(
            '#device#' => $port,
            '#text_mode#' => (config::byKey('text_mode', 'sms') == 1 ) ? 'yes' : 'no',
            '#socketport#' => config::byKey('socketport', 'sms', 55002),
            '#pin#' => config::byKey('pin', 'sms','None'),
            '#smsc#' => config::byKey('smsc', 'sms','None'),
            '#log_path#' => log::getPathToLog('sms'),
            '#trigger_path#' => $sms_path . '/../../core/php/jeeSMS.php',
            '#pid_path#' => '/tmp/sms.pid'
            );
        if (config::byKey('jeeNetwork::mode') == 'slave') {
            $replace_config['#sockethost#'] = getIpFromString(config::byKey('internalAddr', 'core', '127.0.0.1'));
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
        sleep(2);
        if (!self::deamonRunning()) {
            sleep(10);
            if (!self::deamonRunning()) {
                log::add('sms', 'error', 'Impossible de lancer le démon sms, vérifiez le port', 'unableStartDeamon');
                return false;
            }
        }
        message::removeAll('sms', 'unableStartDeamon');
        log::add('sms', 'info', 'Démon sms lancé');
    }

    public static function deamonRunning() {
        $pid_file = '/tmp/sms.pid';
        $sms_path = realpath(dirname(__FILE__) . '/../../ressources/smscmd/smscmd.py');
        if (!file_exists($pid_file)) {
          if(jeedom::checkOngoingThread($sms_path) > 0){
            exec("kill -9 `ps ax | grep '$sms_path' | awk '{print $1}'` > /dev/null 2&1");
        }
        return false;
    }
    $pid = trim(file_get_contents($pid_file));
    if(count(explode("\n", $pid)) != 1){
        exec("kill -9 `ps ax | grep '$sms_path' | awk '{print $1}'` > /dev/null 2&1");
        unlink($pid_file);
        return false;
    }
    if (posix_getsid($pid)) {
        return true;
    } 
    unlink($pid_file);
    return false;
}

public static function stopDeamon() {
    if (!self::deamonRunning()) {
        return true;
    }
    $pid_file = '/tmp/sms.pid';
    if (!file_exists($pid_file)) {
        return true;
    }
    $pid = intval(trim(file_get_contents($pid_file)));
    posix_kill($pid, 15);
    if (self::deamonRunning()) {
        sleep(1);
        posix_kill($pid, 9);
    }
    if (self::deamonRunning()) {
        sleep(1);
        exec('kill -9 ' . $pid . ' > /dev/null 2&1');
    }
    return self::deamonRunning();
}

/*     * *********************Methode d'instance************************* */
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

    public function execute($_options = null) {
        $values = array();
        $message = $_options['title'] . ' ' . $_options['message'];
        if (config::byKey('text_mode', 'sms') == 1) {
            $message = self::cleanSMS(trim($message), true);
        }
        if (strlen($message) > 140) {
            $messages = str_split($message, 140);
            foreach ($messages as $message_split) {
                $values[] = json_encode(array('number' => $this->getConfiguration('phonenumber'), 'message' => $message_split));
            }
        } else {
            $values[] = json_encode(array('number' => $this->getConfiguration('phonenumber'), 'message' => $message));
        }
        if (config::byKey('jeeNetwork::mode') == 'master') {
            foreach (jeeNetwork::byPlugin('sms') as $jeeNetwork) {
                foreach ($values as $value) {
                    if (trim($value['message']) != '') {
                        $socket = socket_create(AF_INET, SOCK_STREAM, 0);
                        socket_connect($socket, $jeeNetwork->getRealIp(), config::byKey('socketport', 'sms', 55002));
                        socket_write($socket, $value, strlen($value));
                        socket_close($socket);
                    }
                }
            }
        }
        if (config::byKey('port', 'sms', 'none') != 'none') {
            foreach ($values as $value) {
                if (trim($value) != '') {
                    $socket = socket_create(AF_INET, SOCK_STREAM, 0);
                    socket_connect($socket, '127.0.0.1', config::byKey('socketport', 'sms', 55002));
                    socket_write($socket, $value, strlen($value));
                    socket_close($socket);
                }
            }
        }
    }

}
