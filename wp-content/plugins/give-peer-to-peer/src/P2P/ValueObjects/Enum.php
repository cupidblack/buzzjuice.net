<?php

namespace GiveP2P\P2P\ValueObjects;

use ReflectionClass;
use ReflectionException;
use InvalidArgumentException;

/**
 * Class Enum
 * @package GiveP2P\P2P\ValueObjects
 *
 * @since 1.0.0
 */
abstract class Enum {

	/**
	 * @var mixed
	 */
	protected $value;

	public function __construct( $value ) {
		$class = static::class;

		if ( $value instanceof $class ) {
			$this->value = $value->value;
		} elseif ( ! in_array( $value, self::all(), true ) ) {
			throw new InvalidArgumentException( "Invalid {$class} enumeration value provided: $value" );
		} else {
			$this->value = $value;
		}
	}


	/**
	 * Get all constants
	 *
	 * @return array
	 */
	public static function all() {
		static $all = [];

		$class = static::class;

		if ( ! isset( $all[ $class ] ) ) {
			try {
				$reflection = new ReflectionClass( $class );
			} catch ( ReflectionException $exception ) {
				return [];
			}

			$all[ $class ] = $reflection->getConstants();
		}

		return $all[ $class ];
	}

	/**
	 * @param  string  $value
	 *
	 * @return bool
	 */
	public static function isValid( $value ) {
		return in_array( $value, self::all(), true );
	}

	/**
	 * Converts camel case to snake case in caps: FooBar -> FOO_BAR.
	 *
	 * @param $string
	 *
	 * @return string
	 */
	private static function toConstantCase( $string ) {
		return strtoupper( ltrim( preg_replace( '/[A-Z]([A-Z](?![a-z]))*/', '_$0', $string ), '_' ) );
	}

	/**
	 * Returns the value of the given constant or null if not defined.
	 *
	 * @param  string  $key
	 *
	 * @return string|null
	 */
	private static function getConstantValue( $key ) {
		$all = self::all();

		return isset( $all[ $key ] ) ? $all[ $key ] : null;
	}

	/**
	 * Adds support for `makeFoo` static methods wherein an Enum constant is FOO, or any available constant.
	 *
	 * @param  string  $name
	 * @param  array  $arguments
	 *
	 * @return static
	 */
	public static function __callStatic( $name, $arguments ) {
		if ( false !== preg_match( '/^make[A-Z]/', $name ) ) {
			$constant = self::toConstantCase( substr( $name, 4 ) );

			if ( null !== ( $value = self::getConstantValue( $constant ) ) ) {
				return new static( $value );
			}

			throw new InvalidArgumentException( "Invalid argument, does not match constant: $name" );
		}
	}


	/**
	 * Compares the value to another Enum or scalar value.
	 *
	 * @param  mixed|static  $value
	 *
	 * @return bool
	 */
	public function is( $value ) {
		return is_object( $value ) && is_callable( $value )
			? $value instanceof static && $value() === $this->value
			: $value === $this->value;
	}
}
