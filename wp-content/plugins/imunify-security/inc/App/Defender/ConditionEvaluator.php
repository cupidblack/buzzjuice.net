<?php
/**
 * Copyright (с) Cloud Linux GmbH & Cloud Linux Software, Inc 2010-2025 All Rights Reserved
 *
 * Licensed under CLOUD LINUX LICENSE AGREEMENT
 * https://www.cloudlinux.com/legal/
 */

namespace CloudLinux\Imunify\App\Defender;

use CloudLinux\Imunify\App\Defender\Model\Condition;
use CloudLinux\Imunify\App\Defender\Model\ConditionSource;
use CloudLinux\Imunify\App\Defender\Model\ConditionType;

/**
 * Condition evaluator class.
 *
 * Handles evaluation of security rule conditions.
 *
 * @since 2.1.0
 */
class ConditionEvaluator {

	/**
	 * The last failed condition during evaluation.
	 *
	 * @var Condition
	 */
	private $failedCondition = null;

	/**
	 * Evaluate a list of conditions.
	 *
	 * @param Condition[] $conditions Array of Condition objects.
	 * @param Request     $request    Request object.
	 *
	 * @return bool True if all conditions are met, false otherwise.
	 */
	public function evaluateConditions( $conditions, $request ) {
		if ( empty( $conditions ) ) {
			return true;
		}

		// Evaluate all conditions - all must be true to proceed.
		foreach ( $conditions as $condition ) {
			if ( ! $this->evaluateCondition( $condition, $request ) ) {
				$this->failedCondition = $condition;
				return false;
			}
		}

		return true;
	}

	/**
	 * Evaluate a single condition.
	 *
	 * @param Condition $condition The condition to evaluate.
	 * @param Request   $request   Request object.
	 *
	 * @return bool True if condition is met, false otherwise.
	 */
	private function evaluateCondition( $condition, $request ) {
		if ( ! $condition->isValidType() ) {
			return false;
		}

		switch ( $condition->getType() ) {
			case ConditionType::EXISTS:
				return $this->evaluateFieldExists( $condition, $request );
			case ConditionType::EQUALS:
				return $this->evaluateFieldEquals( $condition, $request );
			case ConditionType::CONTAINS:
				return $this->evaluateFieldContains( $condition, $request );
			case ConditionType::REGEX:
				return $this->evaluateFieldRegex( $condition, $request );
			case ConditionType::DETECT_XSS:
				return $this->evaluateFieldDetectXSS( $condition, $request );
			case ConditionType::DETECT_SQLI:
				return $this->evaluateFieldDetectSQLi( $condition, $request );
			case ConditionType::MISSING_CAPABILITY:
				return $this->evaluateMissingCapability( $condition, $request );
			default:
				return false;
		}
	}

	/**
	 * Evaluate exists condition.
	 *
	 * @param Condition $condition The condition object.
	 * @param Request   $request   Request object.
	 *
	 * @return bool True if field exists, false otherwise.
	 */
	private function evaluateFieldExists( $condition, $request ) {
		if ( ! $condition->hasRequiredFields() ) {
			return false;
		}

		$parsed = $condition->parseName();
		$source = $parsed['source'];
		$field  = $parsed['field'];

		switch ( $source ) {
			case ConditionSource::ARGS:
				if ( null === $field ) {
					// Check if any GET/POST parameters exist.
					return ! empty( $request->getAllGet() ) || ! empty( $request->getAllPost() );
				}
				return $request->hasGet( $field ) || $request->hasPost( $field );
			case ConditionSource::FILES:
				return $request->hasFile( $field );
			case ConditionSource::REQUEST_COOKIES:
				return $request->hasCookie( $field );
			case ConditionSource::REQUEST_URI:
				return ! empty( $request->getUri() );
			default:
				return false;
		}
	}

	/**
	 * Evaluate equals condition.
	 *
	 * @param Condition $condition The condition object.
	 * @param Request   $request   Request object.
	 *
	 * @return bool True if field equals value, false otherwise.
	 */
	private function evaluateFieldEquals( $condition, $request ) {
		if ( ! $condition->hasRequiredFields() || null === $condition->getValue() ) {
			return false;
		}

		$parsed = $condition->parseName();
		$source = $parsed['source'];
		$field  = $parsed['field'];
		$value  = $condition->getValue();

		switch ( $source ) {
			case ConditionSource::ARGS:
				if ( null === $field ) {
					// Check if any GET/POST parameter equals the value.
					$allArgs = array_merge( $request->getAllGet(), $request->getAllPost() );
					return in_array( $value, $allArgs, true );
				}
				$fieldValue = $request->get( $field );
				if ( null === $fieldValue ) {
					$fieldValue = $request->post( $field );
				}
				return $fieldValue === $value;
			case ConditionSource::REQUEST_URI:
				return $request->getUri() === $value;
			default:
				return false;
		}
	}

	/**
	 * Evaluate field_contains condition.
	 *
	 * @param Condition $condition The condition object.
	 * @param Request   $request   Request object.
	 *
	 * @return bool True if field contains value, false otherwise.
	 */
	private function evaluateFieldContains( $condition, $request ) {
		if ( ! $condition->hasRequiredFields() || null === $condition->getValue() ) {
			return false;
		}

		$parsed = $condition->parseName();
		$source = $parsed['source'];
		$field  = $parsed['field'];
		$value  = $condition->getValue();

		switch ( $source ) {
			case ConditionSource::ARGS:
				if ( null === $field ) {
					// Check if any GET/POST parameter contains the value.
					$allArgs = array_merge( $request->getAllGet(), $request->getAllPost() );
					foreach ( $allArgs as $argValue ) {
						if ( is_string( $argValue ) && strpos( $argValue, $value ) !== false ) {
							return true;
						}
					}
					return false;
				}
				$fieldValue = $request->get( $field );
				if ( null === $fieldValue ) {
					$fieldValue = $request->post( $field );
				}
				return is_string( $fieldValue ) && strpos( $fieldValue, $value ) !== false;
			case ConditionSource::REQUEST_URI:
				return strpos( $request->getUri(), $value ) !== false;
			default:
				return false;
		}
	}

	/**
	 * Evaluate field_regex condition.
	 *
	 * @param Condition $condition The condition object.
	 * @param Request   $request   Request object.
	 *
	 * @return bool True if field matches regex, false otherwise.
	 */
	private function evaluateFieldRegex( $condition, $request ) {
		if ( ! $condition->hasRequiredFields() || null === $condition->getValue() ) {
			return false;
		}

		$parsed  = $condition->parseName();
		$source  = $parsed['source'];
		$field   = $parsed['field'];
		$pattern = $condition->getValue();

		switch ( $source ) {
			case ConditionSource::ARGS:
				if ( null === $field ) {
					// Check if any GET/POST parameter matches the regex.
					$allArgs = array_merge( $request->getAllGet(), $request->getAllPost() );
					foreach ( $allArgs as $argValue ) {
						if ( is_string( $argValue ) && preg_match( $pattern, $argValue ) ) {
							return true;
						}
					}
					return false;
				}
				$fieldValue = $request->get( $field );
				if ( null === $fieldValue ) {
					$fieldValue = $request->post( $field );
				}
				return is_string( $fieldValue ) && (bool) preg_match( $pattern, $fieldValue );
			case ConditionSource::REQUEST_URI:
				return (bool) preg_match( $pattern, $request->getUri() );
			default:
				return false;
		}
	}

	/**
	 * Evaluate field_detectXSS condition.
	 *
	 * @param Condition $condition The condition object.
	 * @param Request   $request   Request object.
	 *
	 * @return bool True if XSS is detected, false otherwise.
	 */
	private function evaluateFieldDetectXSS( $condition, $request ) {
		if ( ! $condition->hasRequiredFields() ) {
			return false;
		}

		$fieldValue = $this->getFieldValueFromCondition( $condition, $request );
		if ( ! is_string( $fieldValue ) ) {
			return false;
		}

		return $this->detectXSS( $fieldValue );
	}

	/**
	 * Evaluate field_detectSQLi condition.
	 *
	 * @param Condition $condition The condition object.
	 * @param Request   $request   Request object.
	 *
	 * @return bool True if SQL injection is detected, false otherwise.
	 */
	private function evaluateFieldDetectSQLi( $condition, $request ) {
		if ( ! $condition->hasRequiredFields() ) {
			return false;
		}

		$fieldValue = $this->getFieldValueFromCondition( $condition, $request );
		if ( null === $fieldValue || ! is_string( $fieldValue ) ) {
			return false;
		}

		return $this->detectSQLi( $fieldValue );
	}

	/**
	 * Get field value from condition.
	 *
	 * @param Condition $condition The condition object.
	 * @param Request   $request   Request object.
	 *
	 * @return string|array<string, mixed>|null Field value or null if not found.
	 */
	private function getFieldValueFromCondition( $condition, $request ) {
		$parsed = $condition->parseName();
		$source = $parsed['source'];
		$field  = $parsed['field'];

		return $this->getFieldValue( $request, $source, $field );
	}

	/**
	 * Get field value from request based on source and field name.
	 *
	 * @param Request $request Request object.
	 * @param string  $source  Field source.
	 * @param string  $field   Field name.
	 *
	 * @return string|array<string, mixed>|null Field value or null if not found.
	 */
	private function getFieldValue( $request, $source, $field ) {
		switch ( $source ) {
			case ConditionSource::ARGS:
				if ( null === $field ) {
					// For ARGS without field, we can't detect XSS on the entire args array.
					return null;
				}
				$fieldValue = $request->get( $field );
				if ( null === $fieldValue ) {
					$fieldValue = $request->post( $field );
				}
				return $fieldValue;
			case ConditionSource::REQUEST_URI:
				return $request->getUri();
			case ConditionSource::FILES:
				if ( null === $field ) {
					return null;
				}
				return $request->getFile( $field );
			case ConditionSource::REQUEST_COOKIES:
				if ( null === $field ) {
					return null;
				}
				return $request->cookie( $field );
			case ConditionSource::REQUEST_HEADERS:
				if ( null === $field ) {
					return null;
				}
				return $request->getHeader( $field );
			default:
				return null;
		}
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

		// Convert to lowercase for case-insensitive matching.
		$value = strtolower( $value );

		// XSS detection patterns based on ModSecurity and OWASP.
		$xssPatterns = array(
			// Basic script tags.
			'/<script[^>]*>/i',

			// Event handlers.
			'/on\w+\s*=/i',

			// JavaScript protocol.
			'/javascript:/i',
			'/vbscript:/i',
			'/data:/i',

			// Common XSS vectors.
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

			// Encoded/obfuscated patterns.
			'/&#x?[0-9a-f]+/i',
			'/%[0-9a-f]{2}/i',
			'/\\\\x[0-9a-f]{2}/i',

			// Expression and eval.
			'/expression\s*\(/i',
			'/eval\s*\(/i',
			'/settimeout\s*\(/i',
			'/setinterval\s*\(/i',

			// CSS expressions.
			'/url\s*\(\s*javascript:/i',

			// Base64 encoded content.
			'/data:text\/html;base64,/i',
			'/data:application\/x-javascript;base64,/i',
		);

		foreach ( $xssPatterns as $pattern ) {
			if ( preg_match( $pattern, $value ) ) {
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

		// Convert to lowercase for case-insensitive matching.
		$value = strtolower( $value );

		// SQL injection detection patterns based on ModSecurity and OWASP.
		$sqliPatterns = array(
			// Basic SQL keywords.
			'/\b(union|select|insert|update|delete|drop|create|alter|exec|execute)\b/i',
			'/\b(union\s+select|select\s+from|insert\s+into|update\s+set|delete\s+from)\b/i',

			// SQL operators and functions (only in SQL context).
			'/\b(and\s+1\s*=\s*1|and\s+1\s*=\s*0|and\s+true|and\s+false)\b/i',
			'/\b(or\s+1\s*=\s*1|or\s+1\s*=\s*0|or\s+true|or\s+false)\b/i',
			'/\b(not\s+null|not\s+exists)\b/i',
			'/\b(xor\s+1|like\s+\'%|between\s+\d+\s+and\s+\d+)\b/i',
			'/(in\s*\(|exists\s*\(|all\s*\(|any\s*\(|some\s*\()/i',
			'/(count\s*\(|sum\s*\(|avg\s*\(|min\s*\(|max\s*\(|group\s+by|order\s+by|having\s*\))/i',

			// SQL comments.
			'/(--|\/\*|\*\/|#)/',

			// SQL string concatenation and functions.
			'/(concat\s*\(|substring\s*\(|substr\s*\(|length\s*\(|char\s*\(|ascii\s*\(|hex\s*\(|unhex\s*\()/i',

			// Database-specific functions (only in SQL context).
			'/(user\s*\(|database\s*\(|version\s*\(|schema\s*\(|table\s*\(|column\s*\()/i',
			'/(user\(\)|database\(\)|version\(\)|schema\(\)|table\(\)|column\(\))/i',
			'/(sysdate\s*\(|now\s*\(|curdate\s*\(|curtime\s*\(|timestamp\s*\()/i',

			// SQL injection techniques.
			'/(union\s+select|union\s+all\s+select)/i',
			'/(select\s+.*\s+from)/i',
			'/(insert\s+into\s+.*\s+values)/i',
			'/(update\s+.*\s+set)/i',
			'/(delete\s+from)/i',
			'/(drop\s+table|drop\s+database)/i',
			'/(create\s+table|create\s+database)/i',
			'/(alter\s+table)/i',

			// Time-based injection.
			'/(sleep\s*\(|benchmark\s*\(|waitfor\s+delay)/i',

			// Error-based injection.
			'/(extractvalue|updatexml|floor\s*\(|rand\s*\(|exp\s*\()/i',

			// Stacked queries.
			'/(;\s*select|;\s*insert|;\s*update|;\s*delete|;\s*drop)/i',

			// Information gathering.
			'/(information_schema|mysql\.|sys\.|pg_)/i',

			// Encoded/obfuscated patterns.
			'/(%27|%22|%3b|%3d|%20)/i', // URL encoded: ', ", ;, =, space.
			'/(\\x27|\\x22|\\x3b|\\x3d)/i', // Hex encoded: ', ", ;, =.
			'/(&#39;|&#34;|&#59;|&#61;)/i', // HTML entities: ', ", ;, =.

			// Common SQL injection payloads.
			'/(\'\s+or\s+\'\'=\'|\'\s+and\s+\'\'=\'|\'\s+union\s+select)/i',
			'/(\'\s*or\s*1\s*=\s*1\s*--|\'\s*or\s*1\s*=\s*1\s*#)/i',
			'/(admin\'\s*--|admin\'\s*#|admin\'\s*\/\*)/i',

			// Blind SQL injection.
			'/(if\s*\(|case\s+when|when\s+.*\s+then)/i',

			// Database fingerprinting (only in SQL context).
			'/(mysql\.|postgresql\.|sqlite\.|oracle\.|sql\s+server\.)/i',

			// Privilege escalation.
			'/(grant|revoke|privilege|role)/i',
		);

		foreach ( $sqliPatterns as $pattern ) {
			if ( preg_match( $pattern, $value ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Evaluate missing_capability condition.
	 *
	 * @param Condition $condition Condition to evaluate.
	 * @param Request   $request   Request object.
	 *
	 * @return bool True if capability is missing (condition matches), false otherwise.
	 */
	private function evaluateMissingCapability( Condition $condition, Request $request ) {
		$capability = $condition->getValue();
		if ( empty( $capability ) ) {
			return false;
		}

		$name = $condition->getName();
		if ( ! empty( $name ) ) {
			// Check specific user capability (e.g., ARGS:user_id).
			$user_id = $this->getUserIdFromRequest( $name, $request );
			if ( null === $user_id ) {
				return false;
			}
			return ! user_can( $user_id, $capability );
		}

		// Check current user capability.
		return ! current_user_can( $capability );
	}

	/**
	 * Get user ID from request using condition name (e.g., ARGS:user_id, REQUEST_COOKIES:author_id).
	 *
	 * @param string  $name    Condition name with source and field (e.g., 'ARGS:user_id', 'REQUEST_COOKIES:author_id', 'ARGS:author').
	 * @param Request $request Request object.
	 *
	 * @return int|null User ID or null if not found.
	 */
	private function getUserIdFromRequest( $name, Request $request ) {
		$parsed = Condition::parseNameString( $name );
		$source = $parsed['source'];
		$field  = $parsed['field'];

		// Require both source and field for user ID extraction.
		if ( null === $field ) {
			return null;
		}

		$value = $this->getFieldValue( $request, $source, $field );
		if ( null === $value || empty( $value ) || ! is_numeric( $value ) ) {
			return null;
		}

		return (int) $value;
	}

	/**
	 * Get the last failed condition during evaluation.
	 *
	 * @return Condition|null The last failed Condition object or null if none failed.
	 */
	public function getFailedCondition() {
		return $this->failedCondition;
	}
}
