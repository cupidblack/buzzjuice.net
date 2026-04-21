<?php
/**
 * Copyright (с) Cloud Linux GmbH & Cloud Linux Software, Inc 2010-2025 All Rights Reserved
 *
 * Licensed under CLOUD LINUX LICENSE AGREEMENT
 * https://www.cloudlinux.com/legal/
 */

namespace CloudLinux\Imunify\App\Defender;

/**
 * Pure comparison functions for condition evaluation.
 *
 * Each match method handles both string and array values internally,
 * extracting leaf strings from arrays via Request::extractLeafValues().
 *
 * @since 3.0.0
 */
class ConditionMatcher {

	/**
	 * XSS detection patterns based on ModSecurity and OWASP.
	 *
	 * @var string[]
	 */
	const XSS_PATTERNS = array(
		'/<script[^>]*>/i',
		'/on\w+\s*=/i',
		'/javascript:/i',
		'/vbscript:/i',
		'/data:/i',
		'/<iframe[^>]*>/i',
		'/<object[^>]*>/i',
		'/<embed[^>]*>/i',
		'/<applet[^>]*>/i',
		'/<form[^>]*>/i',
		'/<input[^>]*>/i',
		'/<textarea[^>]*>/i',
		'/<select[^>]*>/i',
		'/<button[^>]*>/i',
		'/<link[^>]*>/i',
		'/<meta[^>]*>/i',
		'/<style[^>]*>/i',
		'/<title[^>]*>/i',
		'/<xmp[^>]*>/i',
		'/<plaintext[^>]*>/i',
		'/<listing[^>]*>/i',
		'/&#x?[0-9a-f]+/i',
		'/%[0-9a-f]{2}/i',
		'/\\\\x[0-9a-f]{2}/i',
		'/expression\s*\(/i',
		'/eval\s*\(/i',
		'/settimeout\s*\(/i',
		'/setinterval\s*\(/i',
		'/url\s*\(\s*javascript:/i',
		'/data:text\/html;base64,/i',
		'/data:application\/x-javascript;base64,/i',
	);

	/**
	 * SQL injection detection patterns based on ModSecurity and OWASP.
	 *
	 * @var string[]
	 */
	const SQLI_PATTERNS = array(
		'/\b(union|select|insert|update|delete|drop|create|alter|exec|execute)\b/i',
		'/\b(union\s+select|select\s+from|insert\s+into|update\s+set|delete\s+from)\b/i',
		'/\b(and\s+1\s*=\s*1|and\s+1\s*=\s*0|and\s+true|and\s+false)\b/i',
		'/\b(or\s+1\s*=\s*1|or\s+1\s*=\s*0|or\s+true|or\s+false)\b/i',
		'/\b(not\s+null|not\s+exists)\b/i',
		'/\b(xor\s+1|like\s+\'%|between\s+\d+\s+and\s+\d+)\b/i',
		'/(in\s*\(|exists\s*\(|all\s*\(|any\s*\(|some\s*\()/i',
		'/(count\s*\(|sum\s*\(|avg\s*\(|min\s*\(|max\s*\(|group\s+by|order\s+by|having\s*\))/i',
		'/(--|\/\*|\*\/|#)/',
		'/(concat\s*\(|substring\s*\(|substr\s*\(|length\s*\(|char\s*\(|ascii\s*\(|hex\s*\(|unhex\s*\()/i',
		'/(user\s*\(|database\s*\(|version\s*\(|schema\s*\(|table\s*\(|column\s*\()/i',
		'/(user\(\)|database\(\)|version\(\)|schema\(\)|table\(\)|column\(\))/i',
		'/(sysdate\s*\(|now\s*\(|curdate\s*\(|curtime\s*\(|timestamp\s*\()/i',
		'/(union\s+select|union\s+all\s+select)/i',
		'/(select\s+.*\s+from)/i',
		'/(insert\s+into\s+.*\s+values)/i',
		'/(update\s+.*\s+set)/i',
		'/(delete\s+from)/i',
		'/(drop\s+table|drop\s+database)/i',
		'/(create\s+table|create\s+database)/i',
		'/(alter\s+table)/i',
		'/(sleep\s*\(|benchmark\s*\(|waitfor\s+delay)/i',
		'/(extractvalue|updatexml|floor\s*\(|rand\s*\(|exp\s*\()/i',
		'/(;\s*select|;\s*insert|;\s*update|;\s*delete|;\s*drop)/i',
		'/(information_schema|mysql\.|sys\.|pg_)/i',
		'/(%27|%22|%3b|%3d|%20)/i',
		'/(\\x27|\\x22|\\x3b|\\x3d)/i',
		'/(&#39;|&#34;|&#59;|&#61;)/i',
		'/(\'\s+or\s+\'\'=\'|\'\s+and\s+\'\'=\'|\'\s+union\s+select)/i',
		'/(\'\s*or\s*1\s*=\s*1\s*--|\'\s*or\s*1\s*=\s*1\s*#)/i',
		'/(admin\'\s*--|admin\'\s*#|admin\'\s*\/\*)/i',
		'/(if\s*\(|case\s+when|when\s+.*\s+then)/i',
		'/(mysql\.|postgresql\.|sqlite\.|oracle\.|sql\s+server\.)/i',
		'/(grant|revoke|privilege|role)/i',
	);

	/**
	 * Test if a value strictly equals the expected string.
	 *
	 * For array values, extracts leaf strings and checks each.
	 *
	 * @param mixed  $value    The resolved value (string or array).
	 * @param string $expected The expected string.
	 *
	 * @return bool
	 */
	public function matchEquals( $value, $expected ) {
		if ( is_string( $value ) ) {
			return $value === $expected;
		}

		if ( is_array( $value ) ) {
			foreach ( Request::extractLeafValues( $value ) as $leaf ) {
				if ( $leaf === $expected ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Test if a value contains the given substring.
	 *
	 * For array values, extracts leaf strings and checks each.
	 *
	 * @param mixed  $value  The resolved value (string or array).
	 * @param string $needle The substring to search for.
	 *
	 * @return bool
	 */
	public function matchContains( $value, $needle ) {
		if ( is_string( $value ) ) {
			return strpos( $value, $needle ) !== false;
		}

		if ( is_array( $value ) ) {
			foreach ( Request::extractLeafValues( $value ) as $leaf ) {
				if ( strpos( $leaf, $needle ) !== false ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Test if a value matches a PCRE pattern (fail-closed).
	 *
	 * For array values, extracts leaf strings and checks each.
	 *
	 * @param mixed  $value   The resolved value (string or array).
	 * @param string $pattern PCRE pattern.
	 *
	 * @return bool
	 */
	public function matchRegex( $value, $pattern ) {
		if ( is_string( $value ) ) {
			return self::pregMatchFailClosed( $pattern, $value );
		}

		if ( is_array( $value ) ) {
			foreach ( Request::extractLeafValues( $value ) as $leaf ) {
				if ( self::pregMatchFailClosed( $pattern, $leaf ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Detect XSS patterns in a value.
	 *
	 * For array values, extracts leaf strings and checks each.
	 *
	 * @param mixed $value The value to check.
	 *
	 * @return bool True if XSS is detected.
	 */
	public function matchXSS( $value ) {
		if ( is_array( $value ) ) {
			foreach ( Request::extractLeafValues( $value ) as $leaf ) {
				if ( $this->detectXSS( $leaf ) ) {
					return true;
				}
			}
			return false;
		}

		return $this->detectXSS( $value );
	}

	/**
	 * Detect SQL injection patterns in a value.
	 *
	 * For array values, extracts leaf strings and checks each.
	 *
	 * @param mixed $value The value to check.
	 *
	 * @return bool True if SQL injection is detected.
	 */
	public function matchSQLi( $value ) {
		if ( is_array( $value ) ) {
			foreach ( Request::extractLeafValues( $value ) as $leaf ) {
				if ( $this->detectSQLi( $leaf ) ) {
					return true;
				}
			}
			return false;
		}

		return $this->detectSQLi( $value );
	}

	/**
	 * Fail-closed preg_match wrapper.
	 *
	 * When preg_match hits the PCRE backtrack or recursion limit it returns false.
	 * Treating false as "no match" would let an attacker craft input that
	 * exhausts the limit and silently bypasses a blocking rule.
	 * This wrapper treats any PCRE error the same as a positive match.
	 *
	 * @param string $pattern PCRE pattern.
	 * @param string $subject String to test.
	 *
	 * @return bool True when the pattern matches OR when preg_match fails.
	 */
	public static function pregMatchFailClosed( $pattern, $subject ) {
		$result = @preg_match( $pattern, $subject ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- PCRE errors are handled via the return value.

		return 1 === $result || false === $result;
	}

	/**
	 * Detect XSS patterns in a string value.
	 *
	 * @param string $value The value to check for XSS patterns.
	 *
	 * @return bool True if XSS is detected, false otherwise.
	 */
	private function detectXSS( $value ) {
		if ( ! is_string( $value ) || empty( $value ) ) {
			return false;
		}

		$value = strtolower( $value );

		foreach ( self::XSS_PATTERNS as $pattern ) {
			if ( self::pregMatchFailClosed( $pattern, $value ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Detect SQL injection patterns in a string value.
	 *
	 * @param string $value The value to check for SQL injection patterns.
	 *
	 * @return bool True if SQL injection is detected, false otherwise.
	 */
	private function detectSQLi( $value ) {
		if ( ! is_string( $value ) || empty( $value ) ) {
			return false;
		}

		$value = strtolower( $value );

		foreach ( self::SQLI_PATTERNS as $pattern ) {
			if ( self::pregMatchFailClosed( $pattern, $value ) ) {
				return true;
			}
		}

		return false;
	}
}
