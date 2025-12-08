<?php

class USIN_WC_Memberships_Query {

	protected $post_type;
	protected $memberships_join_applied = false;
	protected $has_memberships_counter = 0;

	public function __construct($post_type) {
		$this->post_type = $post_type;
		$this->init();
	}

	public function init() {
		add_filter('usin_db_map', array($this, 'filter_db_map'));
		add_filter('usin_query_join_table', array($this, 'filter_query_joins'), 10, 2);
		add_filter('usin_custom_query_filter', array($this, 'apply_filters'), 10, 2);
		add_filter('usin_custom_select', array($this, 'filter_query_select'), 10, 2);
		add_filter('usin_db_aggregate_columns', array($this, 'filter_aggregate_columns'));
		add_filter('usin_user_db_data', array($this, 'set_status_names'));
		add_filter('usin_custom_query_filter_has_membership', array($this, 'apply_has_membership_filter'), 10, 2);
	}

	public function filter_db_map($db_map) {
		$db_map['membership_num'] = array('db_ref' => 'membership_num', 'db_table' => 'memberships', 'null_to_zero' => true, 'set_alias' => false, 'custom_select' => true, 'no_ref' => true);
		$db_map['member_since'] = array('db_ref' => 'member_since', 'db_table' => 'memberships_since', 'set_alias' => true, 'nulls_last' => true);
		$db_map['membership_statuses'] = array('db_ref' => 'membership_statuses', 'db_table' => 'mem_statuses', 'set_alias' => true, 'nulls_last' => true);
		$db_map['active_memberships'] = array('db_ref' => 'names', 'db_table' => 'wcm_active_memberships', 'set_alias' => true);
		$db_map['has_membership_plan'] = array('db_ref' => '', 'db_table' => 'memberships', 'no_select' => true);
		$db_map['has_membership'] = array('db_ref' => '', 'db_table' => '', 'no_select' => true);
		return $db_map;
	}

	public function filter_query_select($query_select, $field) {
		if ($field == 'membership_num') {
			$query_select = "COUNT(DISTINCT memberships.ID) AS membership_num";
		}
		return $query_select;
	}

	public function filter_aggregate_columns($columns) {
		$columns[] = 'membership_num';
		return $columns;
	}

	public function filter_query_joins($query_joins, $table) {
		global $wpdb;

		if (!in_array($table, array('memberships', 'memberships_since', 'mem_statuses', 'wcm_active_memberships'))) {
			return $query_joins;
		}

		if ($table == 'memberships') {
			$query_joins .= $this->get_memberships_join();

		} elseif ($table == 'memberships_since') {
			$member_since_select = USIN_Query_Helper::get_gmt_offset_date_select('MIN(CAST(meta_value AS DATETIME))');
			$query_joins .= " LEFT JOIN (
					SELECT $member_since_select AS member_since, $wpdb->posts.post_author AS user_id FROM $wpdb->postmeta
					INNER JOIN $wpdb->posts ON $wpdb->postmeta.post_id = $wpdb->posts.ID
					WHERE $wpdb->postmeta.meta_key = '_start_date' AND $wpdb->posts.post_type = '$this->post_type' AND $wpdb->posts.post_status IN (" . self::get_status_string() . ")
					GROUP BY $wpdb->posts.post_author
				) AS memberships_since ON $wpdb->users.ID = memberships_since.user_id";

		} elseif ($table == 'mem_statuses') {
			$query_joins .= " LEFT JOIN (
				SELECT GROUP_CONCAT(post_status SEPARATOR ',') AS membership_statuses, post_author AS user_id
				FROM $wpdb->posts
				WHERE post_type='$this->post_type' AND post_status IN (" . self::get_status_string() . ")
				GROUP BY $wpdb->posts.post_author
				) AS mem_statuses ON  $wpdb->users.ID = mem_statuses.user_id";
		} elseif ($table == 'wcm_active_memberships') {
			$subquery = $wpdb->prepare("SELECT m.ID, m.post_author AS user_id, GROUP_CONCAT(plans.post_title SEPARATOR ', ') AS names FROM $wpdb->posts m
				INNER JOIN $wpdb->posts plans ON m.post_parent = plans.ID
				WHERE m.post_type = %s AND m.post_status = 'wcm-active'
				GROUP BY user_id", $this->post_type);
			$query_joins .= " LEFT JOIN ($subquery) AS wcm_active_memberships ON wcm_active_memberships.user_id = $wpdb->users.ID";
		}

		return $query_joins;
	}


	protected function get_memberships_join() {
		if ($this->memberships_join_applied === true) {
			return '';
		}

		$this->memberships_join_applied = true;
		global $wpdb;
		return " LEFT JOIN $wpdb->posts AS memberships 
			ON $wpdb->users.ID = memberships.post_author AND memberships.post_type='$this->post_type'
			AND memberships.post_status IN (" . self::get_status_string() . ")";
	}

	public static function get_status_string() {
		return USIN_Helper::array_to_sql_string(self::get_membership_statuses_keys());
	}


	public function apply_filters($custom_query_data, $filter) {
		global $wpdb;

		if ($filter->by == 'membership_statuses') {
			$operator = $filter->operator == 'include' ? '>' : '=';

			$custom_query_data['joins'] = $this->get_memberships_join();
			$custom_query_data['having'] = $wpdb->prepare(" AND SUM(memberships.post_status IN (%s)) $operator 0", $filter->condition);

		} elseif ($filter->by == 'has_membership_plan') {
			$operator = $filter->operator == 'include' ? '>' : '=';

			$custom_query_data['having'] = $wpdb->prepare(" AND SUM(memberships.post_parent IN (%d)) $operator 0", $filter->condition);
		}

		return $custom_query_data;
	}

	public function apply_has_membership_filter($custom_query_data, $filter) {
		global $wpdb;
		$joins = array();

		$cond_builder = new USIN_Combined_Filter_Condition_Builder();

		foreach ($filter->condition as $condition) {
			switch ($condition->id) {
				case 'plan':
					$cond_builder->add_number_condition('memberships.post_parent', $condition->val, false, false);
					break;
				case 'status':
					$cond_builder->add_text_condition('memberships.post_status', $condition->val);
					break;
				case 'start_date':
				case 'end_date':
				case 'cancelled_date':
					$meta_key = "_$condition->id";
					$ref = $condition->id;
					$joins[] = $wpdb->prepare("INNER JOIN $wpdb->postmeta AS $ref ON memberships.ID = $ref.post_id AND $ref.meta_key = %s", $meta_key);
					$col = USIN_Query_Helper::get_gmt_offset_date_select("$ref.meta_value");
					$cond_builder->add_date_range_condition($col, $condition->val);
					break;
			}
		}

		$table_name = "has_memberships_" . $this->has_memberships_counter++;

		$custom_query_data['joins'] .= $wpdb->prepare(" INNER JOIN (" .
			" SELECT memberships.post_author AS user_id FROM $wpdb->posts AS memberships " .
			implode(" ", $joins) . " WHERE memberships.post_type = %s" . $cond_builder->build(true) .
			") AS $table_name ON $wpdb->users.ID = $table_name.user_id", $this->post_type);

		return $custom_query_data;
	}

	public function set_status_names($user_data) {
		$statuses = USIN_WC_Memberships::get_statuses();

		if (property_exists($user_data, 'membership_statuses') && !empty($user_data->membership_statuses)) {
			$user_statuses = explode(',', $user_data->membership_statuses);
			foreach ($user_statuses as $key => $status) {
				if (isset($statuses[$status])) {
					$user_statuses[$key] = $statuses[$status]['label'];
				}
			}
			$user_data->membership_statuses = implode(', ', $user_statuses);
		}

		return $user_data;
	}

	protected static function get_membership_statuses_keys() {
		$statuses = USIN_WC_Memberships::get_statuses();
		return array_keys($statuses);
	}

}