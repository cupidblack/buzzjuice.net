<?php

if (!defined('ABSPATH')) {
	exit;
}

class USIN_BuddyPress extends USIN_Plugin_Module {
	const BUDDYPRESS_PATH = 'buddypress/bp-loader.php';
	const BUDDYBOSS_PATH = 'buddyboss-platform/bp-loader.php';

	protected $module_name = 'buddypress';
	protected $plugin_path = array(self::BUDDYPRESS_PATH, self::BUDDYBOSS_PATH);
	public $xprofile;

	public function init() {
		$this->xprofile = new USIN_BuddyPress_XProfile();

		$bp_query = new USIN_BuddyPress_Query($this->xprofile);
		$bp_query->init();

		$bp_user_activity = new USIN_BuddyPress_User_Activity();
		$bp_user_activity->init();

		add_filter('usin_user_db_data', array($this, 'filter_user_data'));
	}

	public static function is_bp_feature_active($feature) {
		if (function_exists('bp_is_active')) {
			return bp_is_active($feature);
		}
		return true;
	}

	public static function is_buddyboss_active() {
		return USIN_Helper::is_plugin_activated(self::BUDDYBOSS_PATH);
	}

	public function register_module() {
		return array(
			'id' => $this->module_name,
			'name' => 'BuddyPress',
			'desc' => __('Retrieves and displays data about the users activity in the BuddyPress social network.', 'usin'),
			'allow_deactivate' => true,
			'buttons' => array(
				array('text' => __('Learn More', 'usin'), 'link' => 'https://usersinsights.com/buddypress-users-data/', 'target' => '_blank')
			),
			'active' => false
		);
	}

	protected function init_reports() {
		new USIN_BuddyPress_Reports($this);
	}

	public function register_fields() {
		$fields = array();

		if ($this->is_bp_feature_active('groups')) {
			$fields[] = array(
				'name' => __('Group Number', 'usin'),
				'id' => 'groups',
				'order' => 'ASC',
				'show' => true,
				'fieldType' => 'buddypress',
				'filter' => array(
					'type' => 'number',
					'disallow_null' => true
				),
				'module' => 'buddypress'
			);

			$fields[] = array(
				'name' => __('Groups Created', 'usin'),
				'id' => 'groups_created',
				'order' => 'ASC',
				'show' => true,
				'fieldType' => 'buddypress',
				'filter' => array(
					'type' => 'number',
					'disallow_null' => true
				),
				'module' => 'buddypress'
			);

			$fields[] = array(
				'name' => __('Group', 'usin'),
				'id' => 'bp_group',
				'order' => false,
				'show' => false,
				'hideOnTable' => true,
				'fieldType' => 'none',
				'filter' => array(
					'type' => 'include_exclude_with_nulls',
					'options' => self::get_groups(),
					'disallow_null' => true
				),
				'module' => 'buddypress'
			);
		}

		if ($this->is_bp_feature_active('friends')) {
			$fields[] = array(
				'name' => __('Friends', 'usin'),
				'id' => 'friends',
				'order' => 'ASC',
				'show' => true,
				'fieldType' => 'buddypress',
				'filter' => array(
					'type' => 'number',
					'disallow_null' => true
				),
				'module' => 'buddypress'
			);
		}

		if ($this->is_bp_feature_active('activity')) {
			$fields[] = array(
				'name' => __('Activity Updates', 'usin'),
				'id' => 'activity_updates',
				'order' => 'ASC',
				'show' => true,
				'fieldType' => 'buddypress',
				'filter' => array(
					'type' => 'number',
					'disallow_null' => true
				),
				'module' => 'buddypress'
			);
		}

		if (self::is_buddyboss_active()) {
			$fields[] = array(
				'name' => __('Profile Type', 'usin'),
				'id' => 'buddyboss_profile_type',
				'order' => false,
				'show' => false,
				'fieldType' => 'general',
				'filter' => array(
					'type' => 'include_exclude_with_nulls',
					'options' => self::get_buddyboss_profile_types()
				),
				'module' => 'buddypress'
			);
		} else {
			$fields[] = array(
				'name' => __('Member Type', 'usin'),
				'id' => 'bp_member_type',
				'order' => false,
				'show' => false,
				'fieldType' => 'general',
				'filter' => array(
					'type' => 'include_exclude_with_nulls',
					'options' => self::get_member_types()
				),
				'module' => 'buddypress'
			);
		}

		if (!empty($this->xprofile)) {
			$xprof_fields = $this->xprofile->get_fields();
			if (!empty($xprof_fields)) {
				$fields = array_merge($fields, $xprof_fields);
			}
		}


		return $fields;
	}

	public function filter_user_data($data) {
		foreach ($this->xprofile->multi_option_fields as $key) {
			if (isset($data->$key)) {
				$data->$key = implode(', ', unserialize($data->$key));
			}
		}

		return $data;
	}

	public static function get_groups($return_associative = false) {
		$groups = array();
		if (method_exists('BP_Groups_Group', 'get')) {
			$bp_groups = BP_Groups_Group::get(array(
				'type' => 'alphabetical',
				'per_page' => -1,
				'show_hidden' => true
			));

			if (!empty($bp_groups['groups']) && is_array($bp_groups['groups'])) {
				foreach ($bp_groups['groups'] as $bp_group) {
					if ($return_associative) {
						$groups[$bp_group->id] = $bp_group->name;
					} else {
						$groups[] = array('key' => $bp_group->id, 'val' => $bp_group->name);
					}
				}
			}
		}

		return $groups;
	}

	public static function get_member_types($return_associative = false) {
		$result = array();

		if (function_exists('bp_get_member_types')) {
			$types = bp_get_member_types(array(), 'objects');
			foreach ($types as $type) {
				if ($return_associative) {
					$result[intval($type->db_id)] = $type->labels['singular_name'];
				} else {
					$result[] = array('key' => $type->db_id, 'val' => $type->labels['singular_name']);
				}
			}
		}

		return $result;
	}

	public static function get_buddyboss_profile_types() {
		$result = array();

		if (function_exists('bp_get_member_types')) {
			$types = bp_get_member_types(array(), 'objects');

			foreach ($types as $slug => $type) {
				$term = get_term_by('slug', $slug, 'bp_member_type');
				$result[] = array('key' => $term->term_id, 'val' => $type->labels['singular_name']);
			}
		}

		return $result;
	}

}

new USIN_BuddyPress();