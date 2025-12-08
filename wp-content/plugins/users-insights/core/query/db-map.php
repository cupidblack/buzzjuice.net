<?php

class USIN_Db_Map{
	/**
	 * Sets the mapping of the options to the database columns names and table names
	 * db_ref : database column name
	 * db_table : database column table - "main" sets the users table, "meta" is the
	 * 		user_meta table
	 * nulls_last : when set to true and the data is ordered by this field ascending,
	 * 		it would load the null rows last instead of first
	 * null_to_zero : when set to true and the value of this column has to meet a condition
	 * stores_empty_str: when set to true it means that this field can store an empty string
	 * 		as a value. This will avoid filters such as "smaller than" return the rows with empty values
	 * 		(as '' is smaller than 10 for example). This cannot be used together with null_to_zero.
	 * utc: when set to true it will convert UTC date fields to the WordPress site timezone
	 *
	 * custom_select : do not build select query automatically, but load a custom select clause
	 * no_ref: do not build a reference in the form of table.column
	 * @var array
	 */
	protected $db_map = array(
		'ID' => array('db_ref'=>'ID', 'db_table'=>'main'),
		'username' => array('db_ref'=>'user_login', 'db_table'=>'main'),
		'role' => array('db_ref'=>'meta_value', 'db_table'=>'role_meta', 'no_select'=>true),
		'email' => array('db_ref'=>'user_email', 'db_table'=>'main'),
		'name' => array('db_ref'=>'display_name', 'db_table'=>'main'),
		'first_name' => array('db_ref'=>'meta_value', 'db_table'=>'first_name_meta'),
		'last_name' => array('db_ref'=>'meta_value', 'db_table'=>'last_name_meta'),
		'registered' => array('db_ref'=>'user_registered', 'db_table'=>'main', 'utc' => true),
		'website' => array('db_ref'=>'user_url', 'db_table'=>'main'),
		'posts' => array('db_ref'=>'posts', 'db_table'=>'', 'custom_select'=>true, 'set_alias'=>true),
		'comments' => array('db_ref'=>'comment_num', 'db_table'=>'comment_count', 'null_to_zero'=>true),
		'last_seen' => array('db_ref'=>'last_seen', 'db_table'=>'user_data', 'nulls_last'=>true, 'cast'=>'DATETIME'),
		'sessions' => array('db_ref'=>'sessions', 'db_table'=>'user_data', 'nulls_last'=>true, 'cast'=>'DECIMAL'),
		'browser' => array('db_ref'=>'browser', 'db_table'=>'user_data', 'nulls_last'=>true),
		'coordinates' => array('db_ref'=>'coordinates', 'db_table'=>'user_data'),
		'browser_version' => array('db_ref'=>'browser_version', 'db_table'=>'user_data', 'nulls_last'=>true),
		'platform' => array('db_ref'=>'platform', 'db_table'=>'user_data', 'nulls_last'=>true),
		'country' => array('db_ref'=>'country', 'db_table'=>'user_data', 'nulls_last'=>true),
		'city' => array('db_ref'=>'city', 'db_table'=>'user_data', 'nulls_last'=>true),
		'region' => array('db_ref'=>'region', 'db_table'=>'user_data', 'nulls_last'=>true),
		'user_groups' => array('db_ref'=>'term_id', 'db_table'=>'tt', 'custom_select'=>true, 'set_alias' => false),
		'notes_count' => array('db_ref'=>'meta_value', 'db_table'=>'nc', 'null_to_zero'=>true, 'cast'=>'DECIMAL')
	);
	protected $fields = array();

	protected function __construct(){
		$this->db_map = apply_filters('usin_db_map', $this->db_map);
		$this->build_db_ref();

		foreach ($this->db_map as $key => $map) {
			$this->fields[$key] = new USIN_Db_Map_Field($key, $map);
		};
	}

	public static function init(){
		return new USIN_Db_Map();
	}

	public function has_field($field_id){
		return isset($this->fields[$field_id]);
	}

	public function get_field($field_id){
		if($this->has_field($field_id)){
			return $this->fields[$field_id];
		}
	}

	/**
	 * For each registered column in the mapping, builds a unique reference to the column
	 * that can be used in the query.
	 */
	protected function build_db_ref(){
		global $wpdb;

		foreach ($this->db_map as $key => &$map) {
			if(isset($map['db_ref']) && isset($map['db_table'])){
				$table = $map['db_table'];
				$ref = $map['db_ref'];

				//set if the select should set an alias
				if(!isset($map['set_alias'])){
					$map['set_alias'] = $key != $ref;
				}

				if(!isset($map['no_ref'])){
					if($table == 'main'){
						$map['db_ref'] = $wpdb->users.'.'.$ref;
					}elseif(!empty($table)){
						$map['db_ref'] = $map['db_table'].'.'.$ref;
					}
				}
			}

		}
	}
}