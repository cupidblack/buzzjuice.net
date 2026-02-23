<?php 

/**
 * Includes the DB query functionality for the EDD module.
 */
class USIN_EDD_Query{
	
	protected $has_ordered_join_applied = false;
	protected $orders_join_set = false;
	protected $customers_join_set = false;
	protected $placed_orders_count = 0;
	
	/**
	 * Inits the main functionality - registers filter hooks.
	 */
	public function init(){
		add_filter('usin_db_map', array($this, 'filter_db_map'));
		add_filter('usin_query_join_table', array($this, 'filter_query_joins'), 10, 2);
		add_filter('usin_custom_query_filter', array($this, 'apply_filters'), 10, 2);
		add_filter('usin_custom_select', array($this, 'filter_query_select'), 10, 2);
		add_filter('usin_custom_query_filter_edd_placed_order', array($this, 'apply_placed_order_filter'), 10, 2);
	}
	
	/**
	 * Filters the default DB map fields and adds the custom EDD fields to the map.
	 * @param  array $db_map the default DB map array
	 * @return array         the default DB map array including the EDD fields
	 */
	public function filter_db_map($db_map){
		$db_map['edd_order_num'] = array('db_ref'=>'purchase_count', 'db_table'=>'edd_customers', 'null_to_zero'=>true);
		$db_map['edd_total_spent'] = array('db_ref'=>'purchase_value', 'db_table'=>'edd_customers', 'null_to_zero'=>true, 'custom_select'=>true, 'cast' => 'DECIMAL(18,2)');
		$db_map['edd_has_ordered'] = array('db_ref'=>'', 'db_table'=>'', 'no_select'=>true);
		$db_map['edd_has_order_status'] = array('db_ref'=>'', 'db_table'=>'payments', 'no_select'=>true);
		$db_map['edd_last_order'] = array('db_ref'=>'edd_last_order', 'db_table'=>'payments_dates', 'nulls_last'=>true, 'cast'=>'DATETIME');
		$db_map['edd_placed_order'] = array('db_ref'=>'', 'db_table'=>'', 'no_select'=>true);
		return $db_map;
	}
	
	/**
	 * Adds the custom SELECT clauses for the EDD fields.
	 * @param  string $query_select the main SELECT clause to which to append the
	 * EDD selects
	 * @return string               the modified SELECT clause
	 */
	public function filter_query_select($query_select, $field){
		if($field == 'edd_total_spent'){
			$query_select='CAST(IFNULL(edd_customers.purchase_value, 0) AS DECIMAL(18,2))';
		}
		return $query_select;
	}

	/**
	 * Adds the custom query JOINS for the EDD fields.
	 * @param  string $query_joins the main JOINS string to which to append the 
	 * custom EDD joins 
	 * @return string              the modified JOINS query
	 */
	public function filter_query_joins($query_joins, $table){
		global $wpdb;

		if($table == 'edd_customers'){
			$query_joins .= $this->get_customers_join();
		}elseif($table == 'payments_dates'){
			$orders_table = $wpdb->prefix.'edd_orders';
			$max_date_local = USIN_Query_Helper::get_gmt_offset_date_select('MAX(date_created)');
			$subquery = "SELECT user_id, $max_date_local AS edd_last_order FROM $orders_table WHERE type='sale' GROUP BY user_id";
			$query_joins .= " LEFT JOIN ($subquery) AS payments_dates ON payments_dates.user_id = $wpdb->users.ID";
		}
		
		return $query_joins;
	}
	
	protected function get_customers_join(){
		if(!$this->customers_join_set){
			global $wpdb;
			$this->customers_join_set = true;
			return " LEFT JOIN ".$wpdb->prefix."edd_customers AS edd_customers ON $wpdb->users.ID = edd_customers.user_id";
		}
		return '';
	}
	
	/**
	 * Generates a LEFT JOIN with the posts table to join the orders (edd payments)
	 * posts only. This JOIN is generated only once.
	 * @return string the JOIN clause if it hasn't been loaded yet or an empty 
	 * string otherwise.
	 */
	protected function get_orders_join(){
		if(!$this->orders_join_set){
			global $wpdb;
			
			$this->orders_join_set = true;
			$orders_table = $wpdb->prefix.'edd_orders';
			return " LEFT JOIN $orders_table AS edd_orders ON edd_orders.user_id = $wpdb->users.ID AND edd_orders.type = 'sale'";
			
		}
		return '';
	}
	
	/**
	 * Applies the custom filters for "Products ordered include/exclude" and 
	 * "Orders status include/exclude"
	 * @param  array $custom_query_data includes the default joins, where and having 
	 * clauses, so that this function can generate them and return this array
	 * @param  object $filter            filter object, contains the filter data 
	 * such as condition and operator
	 * @return array                    the modified $custom_query_data array, that 
	 * includes the generated JOIN, WHERE and HAVING clauses
	 */
	public function apply_filters($custom_query_data, $filter){

		if(in_array($filter->operator, array('include', 'exclude'))){
			global $wpdb;
			$operator = $filter->operator == 'include' ? '>' : '=';
			
			if($filter->by == 'edd_has_ordered'){
				//filter by the products ordered (can be include or exclude)
				
				if(!$this->has_ordered_join_applied){
					//this join depends on the edd_customers join above, so we are going to append it
					//to the main joins query, instead of this one
					$custom_query_data['joins'] =  $this->get_orders_join().
						" INNER JOIN ".$wpdb->prefix."edd_order_items AS edd_order_items ON edd_orders.id = edd_order_items.order_id";

					$this->has_ordered_join_applied = true;
				}
				
				$custom_query_data['having'] = $wpdb->prepare(" AND SUM(edd_order_items.product_id IN (%d)) $operator 0", $filter->condition);

			}elseif($filter->by == 'edd_has_order_status'){
				//filter by the status of the orders (can be include or exclude)
			
				$custom_query_data['joins'] = $this->get_orders_join();
				$custom_query_data['having'] = $wpdb->prepare(" AND SUM(edd_orders.status IN (%s)) $operator 0", $filter->condition);
			
			}
		}

		return $custom_query_data;
	}

	public function apply_placed_order_filter($custom_query_data, $filter){
		global $wpdb;
		$joins = array();
		$wheres = array("WHERE 1 = 1", "o.type = 'sale'", "o.status != 'trash'");

		foreach ($filter->condition as $condition ) {
			switch ($condition->id) {
				case 'status':
					$wheres[]= $wpdb->prepare("o.status = %s", $condition->val);
					break;
				case 'date':
					$date_column = USIN_Query_Helper::get_gmt_offset_date_select('o.date_created');
					if(isset($condition->val[0])){
						$wheres[]= $wpdb->prepare("DATE($date_column) >= %s", $condition->val[0]);
					}
					if(isset($condition->val[1])){
						$wheres[]= $wpdb->prepare("DATE($date_column) <= %s", $condition->val[1]);
					}
					break;
				case 'total':
					if(isset($condition->val[0])){
						$wheres[]= $wpdb->prepare("o.total >= %f", $condition->val[0]);
					}
					if(isset($condition->val[1])){
						$wheres[]= $wpdb->prepare("o.total <= %f", $condition->val[1]);
					}
					break;
				case 'product':
					$joins[] =  " INNER JOIN ".$wpdb->prefix."edd_order_items AS products ON o.id = products.order_id";
					$wheres[] = $wpdb->prepare("products.product_id = %d", $condition->val);
					break;
			}
		}

		$table_name = "placed_order_".$this->placed_orders_count;

		$custom_query_data['joins'] .= " INNER JOIN ( 
				SELECT user_id FROM {$wpdb->prefix}edd_orders AS o ".
			implode(" ", $joins)." ".
			implode(" AND ", $wheres).
			") AS $table_name ON $wpdb->users.ID = $table_name.user_id";

		$this->placed_orders_count++;

		return $custom_query_data;
	}

	/**
	 * Resets the query options - this should be called when more than one
	 * query is performed per http request
	 */
	public function reset(){
		$this->has_ordered_join_applied = false;
		$this->customers_join_set = false;
		$this->orders_join_set = false;
	}
}