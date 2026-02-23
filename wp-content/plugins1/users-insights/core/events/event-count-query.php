<?php

class USIN_Event_Count_Query {
	private $field_id;
	private $table_alias;
	private $event_type;

	public function __construct($field_id, $event_type) {
		$this->field_id = $field_id;
		$this->event_type = $event_type;
		$this->table_alias = 'usin_event_counts_' . $event_type;
		$this->init();
	}

	protected function init() {
		add_filter('usin_db_map', array($this, 'filter_db_map'));
		add_filter('usin_query_join_table', array($this, 'filter_query_joins'), 10, 2);
		add_filter('usin_custom_select', array($this, 'filter_query_select'), 10, 2);
	}

	public function filter_query_select($query_select, $field) {
		if ($field == $this->field_id) {
			$query_select .= "IFNULL( LENGTH({$this->table_alias}.items) - LENGTH(REPLACE({$this->table_alias}.items, ',', '')) + 1, 0)";
		}
		return $query_select;
	}

	public function filter_db_map($db_map) {
		$db_map[$this->field_id] = array(
			'db_ref' => 'item_count',
			'db_table' => $this->table_alias,
			'set_alias' => true,
			'null_to_zero' => true);
		return $db_map;
	}

	public function filter_query_joins($query_joins, $table) {
		if ($table == $this->table_alias) {
			global $wpdb;
			$db_table = USIN_Event::get_table_name();
			$subquery = $wpdb->prepare(
				"SELECT user_id, LENGTH(items) - LENGTH(REPLACE(items, ',', '')) + 1 AS item_count
				FROM $db_table WHERE event_type = %s", $this->event_type);

			$query_joins .= " LEFT JOIN ($subquery) AS $this->table_alias ON {$wpdb->users}.ID = {$this->table_alias}.user_id";
		}

		return $query_joins;
	}
}
