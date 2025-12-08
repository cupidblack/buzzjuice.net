<?php

namespace GiveFunds\Models;

use InvalidArgumentException;


/**
 * Class Fund
 *
 * @package GiveFunds
 * @since 1.0.0
 */
class Fund {

	/**
	 * @since 1.0.0
	 * @var int
	 */
	private $id;

	/**
	 * @since 1.0.0
	 * @var string
	 */
	private $title;

	/**
	 * @since 1.0.0
	 * @var string
	 */
	private $description;

	/**
	 * @since 1.0.0
	 * @var bool
	 */
	private $is_default;

	/**
	 * @since 1.0.0
	 * @var int
	 */
	private $author_id;

	/**
	 * @since 1.0.0
	 * @var array
	 */
	private $associated_forms;


	/**
	 * FundEntity constructor.
	 *
	 * @param int $id
	 * @param string $title
	 * @param string $description
	 * @param bool $is_default
	 * @param int $author_id
	 * @param array $associated_forms
	 */
	public function __construct( $id, $title, $description, $is_default, $author_id, $associated_forms = [] ) {
		$this->id               = $id;
		$this->title            = $title;
		$this->description      = $description;
		$this->is_default       = $is_default;
		$this->author_id        = $author_id;
		$this->associated_forms = $associated_forms;
	}

	/**
	 * Check if class property exist
	 *
	 * @param string $name
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function has( $name ) {
		return property_exists( __CLASS__, $name );
	}

	/**
	 * Get Fund property
	 *
	 * @param string $prop
	 *
	 * @throws InvalidArgumentException if property does not exist
	 *
	 * @since 1.0.0
	 * @return mixed
	 */
	public function get( $prop ) {
		if ( ! $this->has( $prop ) ) {
			throw InvalidArgumentException( "Property {$prop} does not exist" );
		}

		return $this->{$prop};
	}


	/**
	 * @param string $prop
	 * @param mixed $value
	 */
	public function set( $prop, $value ) {
		if ( $this->has( $prop ) ) {
			$this->{$prop} = $value;
		}
	}

	/**
	 * Is Fund a default fund
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function isDefault() {
		return $this->is_default;
	}

	/**
	 * Get FUnd ID
	 *
	 * @since 1.0.0
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Get FUnd ID
	 *
	 * @since 1.0.0
	 * @return int
	 */
	public function getAuthorId() {
		return $this->author_id;
	}

	/**
	 * Get fund title
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Get fund description
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * Get associated forms
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function getAssociatedForms() {
		return $this->associated_forms;
	}

}
