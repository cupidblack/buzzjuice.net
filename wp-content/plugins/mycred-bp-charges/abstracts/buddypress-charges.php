<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * myCRED_BP_Charge class
 * @see http://mycred.me/classes/mycred_bp_charge/
 * @since 1.0
 * @version 1.0
 */
if ( ! class_exists( 'myCRED_BP_Charge' ) ) :
	abstract class myCRED_BP_Charge {

        // Explicitly declare this property - Fixes PHP 8.2+ dynamic property warning
        public $defaults = array();

		// Charge ID
		public $id;

		// myCRED_Settings Class
		public $core;

		// Multipoint types
		public $is_main_type = true;
		public $mycred_type = 'mycred_default';

		// Charge Prefs
		public $prefs = false;

		/**
		 * Construct
		 */
		function __construct( $args = array(), $charge_prefs = NULL, $type = 'mycred_default' ) {

			if ( ! empty( $args ) ) {
				foreach ( $args as $key => $value ) {
					$this->$key = $value;
				}
			}

			// Grab myCRED Settings
			$this->core = mycred( $type );

			if ( $type != '' ) {
				$this->core->cred_id = sanitize_text_field( $type );
				$this->mycred_type = $this->core->cred_id;
			}

			if ( $this->mycred_type != 'mycred_default' )
				$this->is_main_type = false;

			// Grab settings
			if ( $charge_prefs !== NULL ) {

				// Assign prefs if set
				if ( isset( $charge_prefs[ $this->id ] ) )
					$this->prefs = $charge_prefs[ $this->id ];

				// Defaults must be set
				if ( ! isset( $this->defaults ) )
					$this->defaults = array();

			}

			// Apply default settings if needed
			if ( ! empty( $this->defaults ) )
				$this->prefs = mycred_apply_defaults( $this->defaults, $this->prefs );

		}

		/**
		 * Run
		 * Must be over-ridden by sub-class!
		 * @since 1.0
		 * @version 1.0
		 */
		function run() {

			wp_die( 'function myCRED_BP_Charge::run() must be over-ridden in a sub-class.' );

		}

		/**
		 * Preferences
		 * @since 1.0
		 * @version 1.0
		 */
		function preferences() {

			echo '<p>' . __( 'This Charge has no settings', 'bp_charge' ) . '</p>';

		}

		/**
		 * Sanitise Preference
		 * @since 1.0
		 * @version 1.0
		 */
		function sanitise_preferences( $data ) {

			return $data;

		}

		/**
		 * Get Field Name
		 * Returns the field name for the current charge
		 * @since 1.0
		 * @version 1.0
		 */
		function field_name( $field = '' ) {

			if ( is_array( $field ) ) {
				$array = array();
				foreach ( $field as $parent => $child ) {
					if ( ! is_numeric( $parent ) )
						$array[] = $parent;

					if ( ! empty( $child ) && ! is_array( $child ) )
						$array[] = $child;
				}
				$field = '[' . implode( '][', $array ) . ']';
			}
			else {
				$field = '[' . $field . ']';
			}
			
			$option_id = 'mycred_pref_buddypress_charges';

			return $option_id . '[charge_prefs][' . $this->id . ']' . $field;

		}

		/**
		 * Get Field ID
		 * Returns the field id for the current charge
		 * @since 1.0
		 * @version 1.0
		 */
		function field_id( $field = '' ) {

			if ( is_array( $field ) ) {
				$array = array();
				foreach ( $field as $parent => $child ) {
					if ( ! is_numeric( $parent ) )
						$array[] = str_replace( '_', '-', $parent );

					if ( ! empty( $child ) && ! is_array( $child ) )
						$array[] = str_replace( '_', '-', $child );
				}
				$field = implode( '-', $array );
			}
			else {
				$field = str_replace( '_', '-', $field );
			}

			$option_id = 'mycred_pref_buddypress_charges';
			$option_id = str_replace( '_', '-', $option_id );

			return $option_id . '-' . str_replace( '_', '-', $this->id ) . '-' . $field;

		}

		/**
		 * Available Template Tags
		 * @since 1.0
		 * @version 1.0
		 */
		function available_template_tags( $available = array(), $custom = '' ) {

			return $this->core->available_template_tags( $available, $custom );

		}

	}
endif;

?>