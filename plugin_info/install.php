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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

function sms_update() {
	$pid_file = dirname(__FILE__) .'/../../../tmp/sms.pid';
	if (file_exists($pid_file)) {
		$pid = intval(trim(file_get_contents($pid_file)));
		$kill = posix_kill($pid, 15);
		$retry = 0;
		while (!$kill && $retry < 5) {
			sleep(1);
			$kill = posix_kill($pid, 9);
			$retry++;
		}
		unlink($pid_file);
	}
	try {
		sms::stopDeamon();
		sms::runDeamon();
	} catch (Exception  $e) {
		
	}
}

function sms_remove() {
	sms::stopDeamon();
}

?>
