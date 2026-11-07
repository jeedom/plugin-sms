<?php

/** Daemon launch option checks, independent of the Jeedom runtime. */
class smsDaemonOptions {
	public static function isSerialDevice($device) {
		if (!is_string($device)) {
			return false;
		}
		$resolved = realpath($device);
		return $resolved !== false && strpos($resolved, '/dev/') === 0 && filetype($resolved) === 'char';
	}

	public static function validate($options) {
		if ($options['device'] !== 'auto' && !self::isSerialDevice($options['device'])) {
			throw new InvalidArgumentException('Invalid SMS serial device');
		}
		foreach (array('socketport' => 65535, 'serialrate' => 4000000) as $key => $maximum) {
			if (!preg_match('/^[0-9]+$/D', (string) $options[$key]) || $options[$key] < 1 || $options[$key] > $maximum) {
				throw new InvalidArgumentException('Invalid SMS ' . $key);
			}
		}
		if (!is_numeric($options['cycle']) || !is_finite((float) $options['cycle']) || $options['cycle'] <= 0) {
			throw new InvalidArgumentException('Invalid SMS cycle');
		}
		if ($options['pin'] !== '' && $options['pin'] !== 'None' && !preg_match('/^[0-9]{4,8}$/D', (string) $options['pin'])) {
			throw new InvalidArgumentException('Invalid SIM PIN');
		}
		if ($options['smsc'] !== '' && $options['smsc'] !== 'None' && !preg_match('/^\+?[0-9]{1,15}$/D', (string) $options['smsc'])) {
			throw new InvalidArgumentException('Invalid SMS service center');
		}
	}

	public static function arguments($options) {
		$result = '';
		foreach ($options as $key => $value) {
			if (!preg_match('/^[a-z]+$/D', $key)) {
				throw new InvalidArgumentException('Invalid daemon option');
			}
			// The Python daemon expects the literal string None for an unset PIN/SMSC.
			if (($key === 'pin' || $key === 'smsc') && $value === '') {
				$value = 'None';
			}
			$result .= ' --' . $key . ' ' . escapeshellarg((string) $value);
		}
		return $result;
	}
}
