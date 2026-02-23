<?php
/**
 * Copyright (с) Cloud Linux GmbH & Cloud Linux Software, Inc 2010-2025 All Rights Reserved
 *
 * Licensed under CLOUD LINUX LICENSE AGREEMENT
 * https://www.cloudlinux.com/legal/
 */

namespace CloudLinux\Imunify\App\Defender\Model;

/**
 * Condition model class.
 *
 * Represents a condition within a security rule.
 *
 * @since 2.1.0
 */
class Condition {
	/**
	 * Condition name (e.g., REQUEST_URI, ARGS:page, FILES:fileToUpload).
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Condition type (e.g., contains, regex, detectXSS).
	 *
	 * @var string
	 */
	private $type;

	/**
	 * Condition value for comparison.
	 *
	 * @var string|null
	 */
	private $value;

	/**
	 * Create a condition from an array.
	 *
	 * @param array $data Condition data.
	 *
	 * @return Condition
	 */
	public static function fromArray( $data ) {
		$condition        = new self();
		$condition->name  = isset( $data['name'] ) ? $data['name'] : '';
		$condition->type  = isset( $data['type'] ) ? $data['type'] : '';
		$condition->value = isset( $data['value'] ) ? $data['value'] : null;

		return $condition;
	}

	/**
	 * Convert condition to array.
	 *
	 * @return array
	 */
	public function toArray() {
		$data = array(
			'name' => $this->name,
			'type' => $this->type,
		);

		if ( null !== $this->value ) {
			$data['value'] = $this->value;
		}

		return $data;
	}

	/**
	 * Get condition name.
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Get condition type.
	 *
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * Get condition value.
	 *
	 * @return string|null
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * Check if condition type is valid.
	 *
	 * @return bool True if valid, false otherwise.
	 */
	public function isValidType() {
		return ConditionType::isValid( $this->type );
	}

	/**
	 * Check if condition has required fields.
	 *
	 * @return bool True if has required fields, false otherwise.
	 */
	public function hasRequiredFields() {
		return ! empty( $this->name ) && ! empty( $this->type );
	}

	/**
	 * Parse condition name to extract source and specific field.
	 *
	 * @return array Array with 'source' and 'field' keys.
	 */
	public function parseName() {
		return self::parseNameString( $this->name );
	}

	/**
	 * Parse a condition name string to extract source and specific field.
	 *
	 * @param string $name Condition name string (e.g., 'ARGS:page', 'REQUEST_URI').
	 *
	 * @return array Array with 'source' and 'field' keys.
	 */
	public static function parseNameString( $name ) {
		$parts = explode( ':', $name, 2 );

		if ( count( $parts ) === 1 ) {
			// Simple source like REQUEST_URI.
			return array(
				'source' => $parts[0],
				'field'  => null,
			);
		}

		// Source with specific field like ARGS:page.
		return array(
			'source' => $parts[0],
			'field'  => $parts[1],
		);
	}
}
