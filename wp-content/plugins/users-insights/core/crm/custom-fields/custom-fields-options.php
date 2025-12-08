<?php

/**
 * Includes the functionality to register, update and delete custom user meta fields.
 */
class USIN_Custom_Fields_Options {

	public static $field_types = array(
		array('name' => 'Text', 'type' => 'text'),
		array('name' => 'Number', 'type' => 'number'),
		array('name' => 'Date (read only)', 'type' => 'date'),
		array('name' => 'Dropdown', 'type' => 'select'),
	);
	public static $option_key = '_usin_custom_fields';

	/**
	 * Retrieves all of the regsistered custom user meta fields.
	 * @return array array containing all of the custom user meta fields
	 */
	public static function get_saved_fields() {
		return get_option(self::$option_key, array());
	}

	/**
	 * Registers a custom user meta field.
	 * @param array $field Field data array, expected keys: "name", "key" and "type"
	 */
	public static function add_field($field, $force = false) {
		$valid = self::validate_field_data($field, true, $force);

		if (is_wp_error($valid)) {
			return $valid;
		}

		$saved_fields = self::get_saved_fields();
		$saved_fields [] = self::format_field_to_save($field);
		$res = update_option(self::$option_key, $saved_fields);
		if (!$res) {
			return new WP_Error('field_update_failed', __('Error updating fields', 'usin'));
		}
		return true;
	}

	/**
	 * Validates the elements of a field.
	 * @param array $field Field data array, expected keys: "name", "key" and "type"
	 * @param boolean $validate_key sets whether to validate the field key or not.
	 * This can be set to false when updating the field and not changing the field key
	 * @param boolean $force when set to true, it will create the custom field even if there are
	 * records associated with the field.
	 * @return bool|WP_Error true if the field is valid and WP_Error object it it is not valid
	 */
	protected static function validate_field_data($field, $validate_key = true, $force = false) {
		//validate presence of the required fields
		$required_fields = array('name', 'key', 'type');
		foreach ($required_fields as $key) {
			if (empty($field[$key])) {
				return new WP_Error('fields_required', sprintf(__("Error: The %s field can't be blank", 'usin'), $key));
			}
		}
		if ($field['type'] == 'select' && empty($field['options'])) {
			return new WP_Error('fields_required', __("Error: Field options can't be blank", 'usin'));
		}

		$name = $field['name'];
		$key = $field['key'];

		$sanitized = sanitize_text_field($name);
		if (empty($sanitized)) {
			return new WP_Error('invalid_name', __('Error: Invalid field name', 'usin'));
		}

		if ($validate_key) {
			if (!self::is_key_valid($key)) {
				return new WP_Error('invalid_key', sprintf(
					__('Error: Invalid field key. Only lowercase alphanumeric characters, dashes and underscores are allowed. You can alternatively use "%s"', 'usin'),
					sanitize_key($key)
				));
			}

			if (self::is_key_wp_core_key($key)) {
				return new WP_Error('core_key', sprintf(__('Error: "%s" is a default WordPress user meta key. Please use another key.'), $key));
			}

			if (self::field_exists($key)) {
				return new WP_Error('key_exists', __('Error: A field with this key already exists', 'usin'));
			}

			if (!$force && !self::is_read_only($field) && self::records_exists_with_key($key)) {
				$message = __('There are already user meta records associated with this field key. Using this key will allow you to view and update the existing values. Are you sure that you want to use this key?', 'usin');
				return new WP_Error('key_records_exist', $message, 'confirm_required');
			}
		}
		return true;
	}

	protected static function is_read_only($field){
		return $field['type'] == 'date';
	}

	/**
	 * Checks whether a string is a valid key string.
	 * @param string $key the key to validate
	 * @return boolean      true if it is valid and false otherwise
	 */
	protected static function is_key_valid($key) {
		$sanitized_key = sanitize_key($key);
		return $sanitized_key === $key;
	}

	protected static function records_exists_with_key($key) {
		global $wpdb;
		$res = $wpdb->get_var($wpdb->prepare("SELECT umeta_id FROM $wpdb->usermeta WHERE meta_key = %s LIMIT 1", $key));
		return $res != null;
	}

	public static function is_key_wp_core_key($key) {
		$core_fields = array('first_name', 'last_name ', 'nickname', 'description',
			'rich_editing', 'comment_shortcuts', 'admin_color', 'use_ssl', 'wp_capabilities',
			'wp_user_level', 'show_admin_bar_front', 'dismissed_wp_pointers',
			'show_welcome_panel', 'session_tokens', 'wp_dashboard_quick_press_last_post_id',
			'wp_user-settings', 'wp_user-settings-time');

		$core_fields = apply_filters('usin_wp_user_meta_core_keys', $core_fields);

		return in_array($key, $core_fields);
	}

	/**
	 * Checks whether a field with the specified key is already registered.
	 * @param string $key the key to check
	 * @return boolean      true if it exists, false otherwise
	 */
	protected static function field_exists($key) {
		$saved_fields = self::get_saved_fields();
		foreach ($saved_fields as $field) {
			if ($field['key'] == $key) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Deletes a registered custom field.
	 * @param string $key the key of the field to delete
	 * @return boolean      true if the field was deleted, false otherwise
	 */
	public static function delete_field($key) {
		$saved_fields = self::get_saved_fields();
		foreach ($saved_fields as $index => $field) {
			if ($field['key'] == $key) {
				array_splice($saved_fields, $index, 1);
				return update_option(self::$option_key, $saved_fields);
			}
		}
		return false;
	}

	/**
	 * Updates a registered field.
	 * @param array $field Field data array, expected keys: "name", "key" and "type"
	 * @return bool|WP_Error true if the field was updated, false or WP_Error otherwise
	 */
	public static function update_field($field) {
		$valid = self::validate_field_data($field, false);
		$saved_fields = self::get_saved_fields();

		if (is_wp_error($valid)) {
			return $valid;
		}

		$res = false;

		foreach ($saved_fields as $index => &$saved_field) {
			if ($saved_field['key'] == $field['key']) {
				$updated_field = self::format_field_to_save($field);
				if ($saved_field == $updated_field) {
					return true; //there were no changes, no need to update the field, return true
				}

				$saved_field = $updated_field;
				$res = update_option(self::$option_key, $saved_fields);
			}
		}

		if (!$res) {
			return new WP_Error('field_update_failed', __('Error updating fields', 'usin'));
		}
		return true;
	}

	protected static function format_field_to_save($field) {
		$field_data = array(
			'name' => sanitize_text_field($field['name']),
			'type' => sanitize_key($field['type']),
			'key' => sanitize_key($field['key'])
		);

		if ($field['type'] == 'select') {
			$field_data['options'] = $field['options'];
		}

		return $field_data;
	}
}