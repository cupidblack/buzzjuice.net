<?php

class USIN_Gravity_Forms_User_Registration {

	const PLUGIN_SLUG = 'gravityformsuserregistration';
	const PLUGIN_PATH = 'gravityformsuserregistration/userregistration.php';
	protected $fields;
	protected $json_fields = array();
	protected $module_name;

	public function __construct($module_name) {
		$this->module_name = $module_name;
		if ($this->is_active()) {
			$this->init();
		}
	}

	public static function get_user_registration_feeds() {
		$result = array();

		if (method_exists('GFAPI', 'get_feeds') && method_exists('GFAPI', 'get_form')) {
			$feeds = GFAPI::get_feeds(null, null, self::PLUGIN_SLUG);

			if (!empty($feeds)) {
				foreach ($feeds as $feed) {
					if (isset($feed['meta']) && !empty($feed['meta']['userMeta']) && isset($feed['form_id'])) {
						$result [] = $feed;
					}
				}
			}
		}

		return $result;
	}

	public static function get_user_registration_form_ids() {
		$user_reg_feeds = self::get_user_registration_feeds();
		return wp_list_pluck($user_reg_feeds, 'form_id');
	}

	public function is_active() {
		return USIN_Helper::is_plugin_activated(self::PLUGIN_PATH);
	}

	protected function init() {
		add_filter('usin_user_db_data', array($this, 'format_json_fields'));
	}

	public function register_fields() {
		$result = array();

		foreach ($this->get_meta_fields() as $field) {
			$result[] = array(
				'id' => $field->get_prefixed_id(),
				'name' => $field->get_name(),
				'order' => 'ASC',
				'show' => false,
				'fieldType' => 'general',
				'filter' => $field->get_filter_options(),
				'module' => $this->module_name
			);
		}

		return $result;
	}

	/**
	 * Loads all of the registered Gravity Form user form fields.
	 * @return array containing the fields data, formatted as Users Insights fields
	 */
	public function get_meta_fields() {

		if (isset($this->fields)) {
			return $this->fields;
		}

		$this->fields = array();
		$feeds = self::get_user_registration_feeds();

		foreach ($feeds as $feed) {
			$meta_fields = $feed['meta']['userMeta'];
			$form = GFAPI::get_form($feed['form_id']);

			if (!$this->is_form_valid($form)) continue;

			foreach ($meta_fields as $meta_field) {
				//find the form field by ID
				$matches = wp_list_filter($form['fields'], array('id' => (int)$meta_field['value']));

				if (sizeof($matches) > 0) {
					$gf_field = array_shift($matches);
					$field = new USIN_Gravity_Forms_Meta_Field($gf_field, $meta_field);
					$this->fields[$field->get_id()] = $field;

					if ($field->is_json_field()) {
						$this->json_fields[] = $field;
					}
				}
			}
		}

		return $this->fields;
	}

	/**
	 * Filters the user data that is loaded from the database and applied to
	 * the user when creating a new user. Formats the JSON data to a string/
	 * @param object $data the user DB data
	 * @return object       the DB data with unserialized values
	 */
	public function format_json_fields($data) {
		foreach ($this->json_fields as $field) {
			$prop = $field->get_prefixed_id();
			if (!empty($data->$prop)) {
				$data->$prop = $field->format_json_field_data($data->$prop);
			}
		}
		return $data;
	}

	private function is_form_valid($form) {
		if (empty($form) || !is_array($form)) return false;

		$form_deleted = isset($form['is_trash']) && $form['is_trash'];
		$form_has_fields = !empty($form['fields']);

		return $form_has_fields && !$form_deleted;
	}
}