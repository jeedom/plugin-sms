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

function sms_install() {
	if (config::byKey('api::sms::mode') == '') {
		config::save('api::sms::mode', 'localhost');
	}
}

function sms_update() {
	if (config::byKey('api::sms::mode') == '') {
		config::save('api::sms::mode', 'localhost');
	}
	$paths = array(
		'resources/smsd/gsmmodem/compat.py',
	);
	foreach ($paths as $path) {
		$file = dirname(__FILE__) . '/../' . $path;
		if (file_exists($file)) {
			if (is_dir($file)) {
				exec('rm -rf ' . escapeshellarg($file), $output, $return_var);
				if ($return_var != 0) {
					log::add('sms', 'error', 'Failed to remove ' . $file . ' (return code: ' . $return_var . ')');
				}
			} else {
				if (!unlink($file)) {
					log::add('sms', 'error', 'Failed to remove ' . $file);
				}
			}
		}
	}
}

function sms_remove() {

}

?>
