<?php

/** Phone number normalization and exact allow-list matching, independent of the Jeedom runtime. */
class smsPhoneNumber {
	/** Return the number in +<country><number> form, or null when it is not a phone number. */
	public static function normalize($number) {
		if (!is_string($number)) {
			return null;
		}
		$number = preg_replace('/[\s().-]+/', '', trim($number));
		if (strpos($number, '00') === 0) {
			$number = '+' . substr($number, 2);
		} elseif (preg_match('/^0[1-9][0-9]{8}$/D', $number)) {
			// Keep the historical French interpretation of national numbers.
			$number = '+33' . substr($number, 1);
		} elseif (preg_match('/^[1-9][0-9]{10,14}$/D', $number)) {
			$number = '+' . $number;
		}
		return preg_match('/^\+[1-9][0-9]{6,14}$/D', $number) ? $number : null;
	}

	/** True when one ';'-separated configured number is exactly the incoming number. */
	public static function isAuthorized($configured, $incoming) {
		$incoming = self::normalize($incoming);
		if ($incoming === null || !is_string($configured)) {
			return false;
		}
		foreach (explode(';', $configured) as $candidate) {
			if (self::normalize($candidate) === $incoming) {
				return true;
			}
		}
		return false;
	}
}
