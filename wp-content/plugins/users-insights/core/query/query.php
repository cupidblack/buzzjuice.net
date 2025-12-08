<?php

/**
 * Includes the main functionality to build the database query that loads the users data.
 */
class USIN_Query{
	public $args;
	public $filters;
	public $query;
	public $query_select = '';
	public $query_joins = '';
	public $query_where = ' WHERE 1=1';
	public $query_having = ' HAVING 1=1';
	public $query_order = '';
	public $user_data_db_table;
	public $join_tables = array();
	public $loaded_join_tables = array();
	
	protected $meta_query_num = 0;
	protected $db_map = null;
	protected $empty_serialized_values = array('', 'a:0:{}', 's:0:"";', 'a:1:{i:0;s:0:"";}');

	/**
	 * @param array  $args    the options/arguments for the query
	 * @param array $filters the filters to apply to the query
	 */
	public function __construct($args=array(), $filters=null){
		$this->args = $args;
		$this->filters = $filters;
		$this->db_map = USIN_Db_Map::init();

		global $usin;
		$this->user_data_db_table = $usin->manager->user_data_db_table;
	}

	/**
	 * Builds the SELECT clause
	 */
	protected function set_query_select($default_fields = null){
		global $wpdb;
		
		$fields_without = array();
		
		if(!empty($default_fields)){
			$fields = $default_fields;
		}else{
			$fields = usin_options()->get_visible_fields();
			$fields_without[]='comments';
			$fields_without[]='user_groups';
			$fields_without = apply_filters('usin_query_fields_without', $fields_without);
		}
		
		$fields = array_diff($fields, $fields_without); //some fields shouldn't be loaded 
		//unless they are used in filters or order by
		
		//add order by to the fields
		$fields[]= $this->get_order_by_field()->id;

		//add the fields added to the filters
		$filter_fields = empty($this->filters) ? array() : wp_list_pluck($this->filters, 'by');
		$fields = array_unique(array_merge($fields, $filter_fields));
		
		$selects = array($this->get_ref('ID'));
		
		foreach ($fields as $field) {
			$db_field = $this->get_db_field($field);

			if($db_field != null){
				if($db_field->has_select()){
					if($db_field->has_custom_select()){
						//apply a custom select
						$select = $this->get_custom_select($field);
					}else{
						$select = $this->get_transformed_ref($field);
					}
					
					if(!empty($select)){
						$selects[] = $db_field->should_set_alias() ? sprintf("%s AS `%s`", $select, $field) : $select;
					}
				}
				
				$db_table = $db_field->get_table();
				if($db_table != 'main' && !isset($this->join_tables[$db_table])){
					$this->join_tables[] = $db_table;
				}
			}
		}

		$selects = array_values(array_unique($selects));

		$this->query_select = 'SELECT SQL_CALC_FOUND_ROWS '.implode(', ', $selects).' FROM '.$wpdb->users;
		$this->query_select = apply_filters('usin_user_query_select', $this->query_select);
	}

	/**
	 * Builds a custom select clause for a single field when its select statement
	 * is complex and can't be built automatically
	 * @param  string $field the field ID
	 * @return string        the select statement
	 */
	protected function get_custom_select($field){
		global $wpdb;
		
		switch ($field) {
			case 'posts':
				return "COUNT(DISTINCT $wpdb->posts.ID)";
				break;
			case 'user_groups':
				$select = '';
				if(isset($this->args['export'])){
					//export process loads the groups with a left join
					$select = "GROUP_CONCAT(DISTINCT wpt.name SEPARATOR ', ') as user_groups";
				}
				return $select;
				break;
			default:
				return apply_filters('usin_custom_select', '', $field);
				break;
		}
	}
	

	/**
	 * Calls the required functions to apply the filters/conditions to the query.
	 */
	protected function set_filters(){
		if(!empty($this->filters)){
			$this->apply_filters();
		}

		$this->add_multisite_filter();
	}

	/**
	 * Appends the conditional WHERE/HAVING queries to the main query.
	 */
	protected function set_conditions(){
		global $wpdb;
		$this->query_where = apply_filters('usin_query_where', $this->query_where);
		if($this->query_where != " WHERE 1=1"){
			$this->query .= $this->query_where;
		}

		$this->query .= " GROUP BY $wpdb->users.ID";
		
		$this->query_having = apply_filters('usin_query_having', $this->query_having);
		if($this->query_having != " HAVING 1=1"){
			$this->query .= $this->query_having;
		}
	}

	/**
	 * Adds a filter to load the users for the current network/site on multisite
	 * installations. Since the multisite installations share one users table
	 * between all of the sites, the way we filter the users by the current site
	 * is by applying an inner join to the user meta table on the wp_[ID]_capability field.
	 */
	protected function add_multisite_filter(){
		global $wpdb;
		if ( is_multisite()) {
			$blog_id = $GLOBALS['blog_id'];
			if($blog_id){
				$key = $wpdb->get_blog_prefix( $blog_id ) . 'capabilities';
				$this->generate_meta_ref($key);
			}

		}
	}
	
	
	/**
	 * Checks if the filter by is a column that has been set by an
	 * aggregate function
	 * @param  array $filter the filter options
	 * @return boolean          true if the filter column has been set by an
	 * aggregate function and false otherwise.
	 */
	protected function filter_contains_function_col($filter){
		//add list with column names that have been generated from aggregate functions
		$func_columns = array('posts', 'user_groups');
		$func_columns = apply_filters('usin_db_aggregate_columns', $func_columns);

		if(isset($filter->by) && in_array($filter->by, $func_columns)){
			return true;
		}
		
		return false;
	}

	/**
	 * Checks whether the current filters contain a selected column.
	 * @param  string $column the column ID
	 * @return boolean         true if the filters contain the column and false
	 * otherwise.
	 */
	protected function filters_contain_col($column){
		if(!empty($this->filters)){
			foreach ($this->filters as $filter) {
				if(isset($filter->by) && $filter->by == $column){
					return true;
				}
			}
		}
		return false;
	}


	/**
	 * Retrieves the JOIN clauses for the query.
	 * @return string a string containing all of the JOIN clauses
	 */
	protected function get_query_joins(){
		global $wpdb;
		
		foreach ($this->join_tables as $table ) {
			
			if(!in_array($table, $this->loaded_join_tables)){
				switch ($table) {
					case 'user_data':
						$this->query_joins.= " LEFT JOIN ".$wpdb->prefix."$this->user_data_db_table as user_data ON ".
							"$wpdb->users.ID = user_data.user_id";
						break;
					
					case 'tt':
						//user groups
						$this->query_joins.= " LEFT JOIN $wpdb->term_relationships rel ON $wpdb->users.ID = rel.object_id";
						$this->query_joins.= " LEFT JOIN $wpdb->term_taxonomy tt ON rel.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = '".USIN_GROUPS::$slug."'";
						if(isset($this->args['export'])){
							//we need the term names for the export
							$this->query_joins.= " LEFT JOIN $wpdb->terms wpt ON tt.term_id = wpt.term_id";
						}
						break;
					
					case 'nc':
					case 'first_name_meta':
					case 'last_name_meta':
						//meta fields
						$keys = array(
							'nc' => '_usin_note_count',
							'first_name_meta' => 'first_name',
							'last_name_meta' => 'last_name'
						);
						$this->query_joins.=$wpdb->prepare(" LEFT JOIN $wpdb->usermeta $table ON $wpdb->users.ID = $table.user_id AND $table.meta_key = %s", $keys[$table]);
						break;
						
					case 'posts':
						//post count
						$join_query = " LEFT JOIN $wpdb->posts on $wpdb->users.ID = $wpdb->posts.post_author";

						$allowed_statuses = USIN_Helper::get_allowed_post_statuses('sql_string');
						if(!empty($allowed_statuses)){
							$join_query .= " AND $wpdb->posts.post_status IN ($allowed_statuses)";
						}

						$allowed_post_types = USIN_Helper::get_allowed_post_types('sql_string');
						if(!empty($allowed_post_types)){
							$join_query .= " AND $wpdb->posts.post_type IN ($allowed_post_types)";
						}

						$this->query_joins .= $join_query;
						break;
					case 'comment_count':
						$exclude_comment_types = USIN_Helper::get_exclude_comment_types('sql_string');
						$where = '';
						if(!empty($exclude_comment_types)){
							$where = "WHERE comment_type NOT IN ($exclude_comment_types) ";
						}
						$this->query_joins .= " LEFT JOIN (SELECT user_id, COUNT(*) as comment_num".
							" FROM $wpdb->comments ".$where."GROUP BY user_id)".
							" as comment_count ON ($wpdb->users.ID = comment_count.user_id)";
						break;
					case 'role_meta':
						if(is_multisite()){
							//change the wp_capabilities ref to wp_[ID]_capabilities
							$blog_id = $GLOBALS['blog_id'];
							$key = $wpdb->get_blog_prefix( $blog_id ) . 'capabilities';
						}else{
							$key = $wpdb->prefix . 'capabilities';
						}
						$this->generate_meta_ref($key, true, 'role_meta');
						break;
					default:
						$this->query_joins .= apply_filters('usin_query_join_table', '', $table);
						break;
				}
				
				$this->loaded_join_tables[]=$table;
			}
			
		}
		
		$this->query_joins = apply_filters('usin_query_joins', $this->query_joins);
		return $this->query_joins;
	}

	/**
	 * Applies the ORDER BY clause to the main query.
	 * @return void
	 */
	protected function set_query_order(){
		global $wpdb;

		$order_by_field = $this->get_order_by_field();
		$order = $this->get_order_direction();

		$order_by = $order_by_field->get_ref();
		$cast_type = $order_by_field->get_cast_type();
		$cast_order_by = $cast_type !== null ? "CAST($order_by AS $cast_type)" : $order_by;

		if($order_by_field->nulls_are_last() && $order == 'ASC'){
			// make the NULLs and empty strings displayed last
			// add an empty string condition only to fields that can be compared to empty string - e.g. date
			// fields cannot be compared to empty string in MySQL 8
			$empty_str_condition = $order_by_field->is_comparable_to_empty_string() ? " OR $order_by = ''" : '';
			$is_null_order_by = "(ISNULL($order_by){$empty_str_condition})";
			$this->query_order = " ORDER BY $is_null_order_by ASC, $cast_order_by ASC";
		}else{
			$this->query_order = " ORDER BY $cast_order_by $order";
		}

		if($order_by_field->id != 'username'){
			$this->query_order .= ", $wpdb->users.user_login ASC";
		}
	}

	/**
	 * Returns an instance of the USIN_Db_Map_Field that will be used for ordering.
	 * @return USIN_Db_Map_Field if orderby is set and the field exists, the field with orderby id will be returned.
	 * Otherwise the date registered field is returned.
	 */
	protected function get_order_by_field(){
		if(!empty($this->args['orderby']) && $this->db_map->has_field($this->args['orderby'])){
			$order_by = $this->args['orderby'];
		}else{
			$order_by = 'registered';
		}
		return $this->get_db_field($order_by);
	}

	/**
	 * @return string the order direction (ASC or DESC) based on the query args.
	 */
	protected function get_order_direction(){
		return isset($this->args['order']) && strtoupper($this->args['order']) == 'ASC' ? 'ASC' : 'DESC';
	}

	/**
	 * Applies the selected filters.
	 */
	protected function apply_filters(){
		if(!empty($this->filters)){
			foreach ($this->filters as $filter) {
				$db_field = isset($filter->by) ? $this->get_db_field($filter->by) : null;
				$condition_is_set = isset($filter->condition) || in_array($filter->operator, array('isnull', 'notnull', 'isset', 'notset', 'isset_ser', 'notset_ser'));

				if($db_field !== null && $condition_is_set){
					if($filter->by=='role'){
						//set the operator to check the string for contains and not contains
						$filter->operator = $filter->operator == 'is' ? 'contains_ser' : 'notcontains_ser';
					}

					if($this->filter_contains_function_col($filter)){
						$clause = &$this->query_having;
					}else{
						$clause = &$this->query_where;
					}
					
					switch ($filter->operator) {
						case 'is':
						case 'not':
							$this->add_text_match_filter($filter, $clause);
							break;
						case 'contains':
						case 'starts':
						case 'ends':
						case 'notcontains' :
							$this->add_text_search_filter($filter, $clause);
							break;
						case 'equals':
						case 'bigger':
						case 'smaller':
							if($filter->type == 'date'){
								$this->add_date_filter($filter, $clause);
							}else{
								$this->add_number_filter($filter, $clause);
							}
							break;
						case 'morethan':
						case 'lessthan':
						case 'exactly':
							$this->add_days_ago_filter($filter, $clause);
							break;
						case 'isnull':
						case 'notnull':
							$this->add_null_filter($filter, $clause);
							break;
						case 'isset_ser':
						case 'notset_ser':
							$this->add_null_filter_ser($filter, $clause);
							break;	
						case 'include_wn':
						case 'exclude_wn':
						case 'isset':
						case 'notset':
							$this->add_include_exclude_with_nulls_filter($filter, $clause);
							break;
						case 'contains_ser':
						case 'notcontains_ser':
							$this->add_serialized_search_filter($filter, $clause);
							break;
						case 'contains_com':
						case 'notcontains_com':
							$this->add_comma_search_filter($filter, $clause);
							break;
						default:
							$custom_query_data = array(
								'where' => '',
								'having' => '',
								'joins' => ''
								);
							$custom_query_data = apply_filters('usin_custom_query_filter', $custom_query_data, $filter);
							$custom_query_data = apply_filters('usin_custom_query_filter_'.$filter->by, $custom_query_data, $filter);
							$this->query_where .= $custom_query_data['where'];
							$this->query_having .= $custom_query_data['having'];
							$this->query_joins .= $custom_query_data['joins'];

							break;
					}
				}
			}
		}
	}

	/**
	 * Generates a unique reference for the usermeta table, so that multiple
	 * joins can be made for this table to retrieve different user meta fields
	 * @param  string  $meta_key  the meta key that will be used for the join
	 * @param  boolean $left_join sets when set to true, a left join will be applied,
	 * otherwise inner join will be applied
	 * @return string             the new alias that can be used to access the columns
	 * from this join
	 */
	protected function generate_meta_ref($meta_key, $left_join = false, $custom_alias = null){
		global $wpdb;
		$alias = $custom_alias ? $custom_alias : 'mt'.$this->meta_query_num;
		$join = $left_join ? 'LEFT' : 'INNER';
		$this->query_joins .= " $join JOIN $wpdb->usermeta AS $alias ON ".
			"($wpdb->users.ID = $alias.user_id AND $alias.meta_key = '$meta_key')";

		$this->meta_query_num++;

		return $alias;
	}

	protected function add_text_match_filter($filter, &$clause){
		if($this->is_condition_set($filter)){
			global $wpdb;
			$ref = $this->get_ref($filter->by);
			$operator = $filter->operator == 'is' ? '=' : '!=';

			$clause .= $wpdb->prepare(" AND $ref $operator %s", $filter->condition);
		}
	}
	
	protected function add_text_search_filter($filter, &$clause){
		if($this->is_condition_set($filter)){
			global $wpdb;
			$ref = $this->get_ref($filter->by);
			$format = $this->get_db_search_format($filter->operator, $filter->condition);
			$operator = $filter->operator == 'notcontains' ? 'NOT LIKE' : 'LIKE';

			$clause .= $wpdb->prepare(" AND $ref $operator %s", $format);
		}
	}
	
	protected function add_serialized_search_filter($filter, &$clause){
		if($this->is_condition_set($filter)){
			global $wpdb;
			$ref = $this->get_ref($filter->by);
			$condition = $wpdb->esc_like($filter->condition);
			$serialized_condition = '%'.$wpdb->esc_like(serialize($filter->condition)).'%';
			
			if($filter->operator == 'contains_ser'){
				//contains operator - search for both serialized and unserialized values
				//e.g. it will search for both 's:5:"value";' and 'value'
				$clause .= $wpdb->prepare(" AND ($ref LIKE %s OR $ref LIKE %s)", $serialized_condition, $condition);
			}else{
				$empty_values = USIN_Helper::array_to_sql_string($this->empty_serialized_values);
				$clause .= $wpdb->prepare(" AND (($ref NOT LIKE %s AND $ref NOT LIKE %s) OR $ref IS NULL)", $serialized_condition, $condition);
			}
			
		}
	}

	protected function add_comma_search_filter($filter, &$clause){
		global $wpdb;
		$ref = $this->get_ref($filter->by);
		$condition = "(^|,)".preg_quote($filter->condition)."(,|$)";
		
		if($filter->operator == 'contains_com'){
			$clause .= $wpdb->prepare(" AND ($ref REGEXP %s)", $condition);
		}else{
			$clause .= $wpdb->prepare(" AND ($ref NOT REGEXP %s OR $ref IS NULL)", $condition);
		}
	}

	protected function add_null_filter($filter, &$clause){
		$ref = $this->get_ref($filter->by);
		$condition = $filter->operator === 'isnull' ?
			"(%s='' OR %s IS NULL)" : "%s!='' AND %s IS NOT NULL";


		$clause .= " AND ".sprintf($condition, $ref, $ref);
	}

	protected function add_null_filter_ser($filter, &$clause){
		$ref = $this->get_ref($filter->by);

		$empty_values = USIN_Helper::array_to_sql_string($this->empty_serialized_values);
		if($filter->operator == 'isset_ser'){
			$clause .= " AND $ref IS NOT NULL AND $ref NOT IN ($empty_values)";
		}elseif ($filter->operator == 'notset_ser'){
			$clause .= " AND ($ref IS NULL OR $ref IN ($empty_values))";
		}
	}

	protected function add_date_filter($filter, &$clause){
		global $wpdb;

		$operators = array ('equals'=>'=', 'bigger'=>'>', 'smaller'=>'<');
		$operator = $operators[$filter->operator];
		$ref = $this->get_transformed_ref($filter->by);
		$clause .= $wpdb->prepare(" AND DATE($ref) $operator %s", $filter->condition);
	}

	protected function add_number_filter($filter, &$clause){
		global $wpdb;

		$operators = array ('equals'=>'=', 'bigger'=>'>', 'smaller'=>'<');
		$operator = $operators[$filter->operator];
		$ref = $this->get_transformed_ref($filter->by);

		$db_field = $this->get_db_field($filter->by);
		if($db_field->stores_empty_string() && !$db_field->converts_null_to_zero()){
			// do not return empty string values in filters like "smaller than X" or "equals 0"
			$clause .= " AND {$db_field->get_ref()} != ''";
		}
		$clause .= $wpdb->prepare(" AND $ref $operator %F", $filter->condition);
	}

	protected function add_days_ago_filter($filter, &$clause){
		global $wpdb;

		$db_field = $this->get_db_field($filter->by);
		$operators = array ('morethan'=>'>', 'lessthan'=>'<', 'exactly'=>'=');
		$operator = $operators[$filter->operator];

		$ref = $this->get_transformed_ref($filter->by);

		$today = current_time('mysql');

		$is_datetime = $db_field->get_cast_type() == 'DATETIME';
		
		if($is_datetime && $filter->condition === 1 && $operator == '<'){
			//load the last 24 hours
			$clause .= $wpdb->prepare(" AND TIMESTAMPDIFF(HOUR, $ref, %s) < 24", $today);
		}elseif($is_datetime && $filter->condition === 1 && $operator == '='){
			//load from yesterday's date and the last 24 - 48 hours
			$clause .= $wpdb->prepare(" AND (TIMESTAMPDIFF(HOUR, $ref, %s) BETWEEN 24 AND 48) AND (DATE(%s) - INTERVAL 1 DAY) = DATE($ref)", $today, $today);
		}else{
			$clause .= $wpdb->prepare(" AND (DATE(%s) - INTERVAL %d DAY) $operator DATE($ref)", $today, $filter->condition);
		}
	}
	
	protected function add_include_exclude_with_nulls_filter($filter, &$clause){
		global $wpdb;
		$ref = $this->get_ref($filter->by);
	
		switch ($filter->operator) {
			case 'include_wn':
				$clause .= $wpdb->prepare(" AND SUM($ref = %d) > 0", $filter->condition);
				break;
			case 'exclude_wn':
				$clause .= $wpdb->prepare(" AND ( SUM($ref = %d) = 0 OR COUNT($ref) = 0)", $filter->condition);
				break;
			case 'isset':
				$clause .= " AND COUNT($ref) > 0";
				break;
			case 'notset':
				$clause .= " AND COUNT($ref) = 0";
				break;
		}
	}


	// HELPER METHODS

	protected function get_db_field($field_id){
		return $this->db_map->get_field($field_id);
	}

	protected function get_ref($field_id){
		return $this->get_db_field($field_id)->get_ref();
	}

	protected function get_transformed_ref($field_id){
		$db_field = $this->get_db_field($field_id);
		$ref = $db_field->get_ref();

		if($db_field->converts_null_to_zero()){
			$ref = "IFNULL($ref, 0)";
		}elseif($db_field->is_utc()){
			$ref = USIN_Query_Helper::get_gmt_offset_date_select($ref);
		}

		return $ref;
	}

	protected function is_condition_set($filter){
		return !empty($filter->condition) || $filter->condition=='0';
	}

	protected function db_rows_to_objects($results){
		$users = array();
		$export_options = isset($this->args['export']) ? $this->args['export'] : null;
		
		foreach ($results as $res) {
			if($export_options){
				$res->is_exported = true;
				$users[] = new USIN_User_Exported($res, $export_options);
			}else{
				$users[] = new USIN_User($res);
			}
		}
		return $users;
	}


	protected function get_db_search_format($operator, $string){
		global $wpdb;
		$string = $wpdb->esc_like($string);
		switch ($operator) {
			case 'contains':
			case 'notcontains':
				$f = '%'.$string.'%';
				break;
			case 'starts':
				$f = $string.'%';
				break;
			case 'ends':
				$f = '%'.$string;
				break;
			default:
				$f = $string;
				break;
		}

		return $f;
	}
}