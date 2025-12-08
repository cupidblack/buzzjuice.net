<?php

class USIN_Woocommerce_Query{
	protected $order_type;
	protected $orders_table;
	protected $has_ordered_join_applied = false;
	protected $has_order_status_join_applied = false;
	protected $coupon_join_applied = false;
	protected $cart_items_join_applied = false;
	protected $placed_orders_count = 0;

	public function __construct($order_type){
		global $wpdb;

		$this->order_type = $order_type;
		$this->orders_table = "{$wpdb->prefix}wc_orders";
	}

	public function init(){
		add_filter('usin_db_map', array($this, 'filter_db_map'));
		add_filter('usin_query_join_table', array($this, 'filter_query_joins'), 10, 2);
		add_filter('usin_custom_query_filter', array($this, 'apply_filters'), 10, 2);
		add_filter('usin_custom_select', array($this, 'filter_query_select'), 10, 2);
		add_filter('usin_user_db_data', array($this, 'replace_country_code_with_name'));
		add_filter('usin_custom_query_filter_placed_order', array($this, 'apply_placed_order_filter'), 10, 2);

		$billing_keys = array('billing_country', 'billing_state', 'billing_city');
		foreach ($billing_keys as $key ) {
			$meta_query = new USIN_Meta_Query($key, 'text', 'wc_');
			$meta_query->init();
		}
	}

	public function filter_db_map($db_map){
		$db_map['order_num'] = array('db_ref'=>'order_num', 'db_table'=>'orders', 'null_to_zero'=>true, 'set_alias'=>true);
		$db_map['successful_order_num'] = array('db_ref'=>'successful_order_num', 'db_table'=>'successful_orders', 'null_to_zero'=>true, 'set_alias'=>true);
		$db_map['has_ordered'] = array('db_ref'=>'', 'db_table'=>'', 'no_select'=>true);
		$db_map['placed_order'] = array('db_ref'=>'', 'db_table'=>'', 'no_select'=>true);
		$db_map['has_order_status'] = array('db_ref'=>'', 'db_table'=>'', 'no_select'=>true);
		$db_map['has_used_coupon'] = array('db_ref'=>'', 'db_table'=>'', 'no_select'=>true);
		$db_map['last_order'] = array('db_ref'=>'last_order', 'db_table'=>'orders', 'nulls_last'=>true, 'cast'=>'DATETIME');
		$db_map['first_order'] = array('db_ref'=>'first_order', 'db_table'=>'orders', 'nulls_last'=>true, 'cast'=>'DATETIME');
		$db_map['lifetime_value'] = array('db_ref'=>'ltv', 'db_table'=>'successful_orders', 'null_to_zero'=>true, 'custom_select'=>true, 'set_alias'=>true);
		$db_map['reviews'] = array('db_ref'=>'reviews_num', 'db_table'=>'reviews', 'null_to_zero'=>true, 'set_alias'=>true);
		$db_map['wc_origin_source'] = array('db_ref'=>'source', 'db_table'=>'wc_order_origins', 'set_alias'=>true);
		$db_map['wc_origin_type'] = array('db_ref'=>'type', 'db_table'=>'wc_order_origins', 'set_alias'=>true);
		$db_map['wc_cart'] = array('db_ref'=>'', 'db_table'=>'', 'no_select'=>true);
		$db_map['wc_has_product_in_cart'] = array('db_ref'=>'', 'db_table'=>'', 'no_select'=>true);
		return $db_map;
	}

	public function filter_query_select($query_select, $field){
		if($field == 'lifetime_value'){
			$decimals = USIN_Woocommerce::get_decimals();
			$query_select="CAST(IFNULL(successful_orders.ltv, 0) AS DECIMAL(18,{$decimals}))";
		}
		return $query_select;
	}

	public function filter_query_joins($query_joins, $table){
		global $wpdb;

		if($table === 'orders'){
			$query_joins .= " LEFT JOIN (".$this->get_orders_select().") AS orders ON $wpdb->users.ID = orders.user_id";
		}elseif($table === 'successful_orders'){
			$query_joins .= " LEFT JOIN (".$this->get_successful_orders_select().") AS successful_orders ON $wpdb->users.ID = successful_orders.user_id";
		}elseif ($table === 'reviews') {
			$query_joins.= " LEFT JOIN (SELECT count(comment_ID) as reviews_num, user_id FROM $wpdb->comments ".
			"INNER JOIN $wpdb->posts ON $wpdb->comments.comment_post_ID = $wpdb->posts.ID AND $wpdb->posts.post_type = 'product' ".
			"GROUP BY user_id) AS reviews ON $wpdb->users.ID = reviews.user_id";
		}elseif ($table === 'wc_order_origins') {
			$query_joins.= " LEFT JOIN (".$this->get_order_origins_select().") AS wc_order_origins ON $wpdb->users.ID = wc_order_origins.user_id";
		}

		return $query_joins;
	}

	protected function get_orders_select(){
		global $wpdb;

		$first_order_select = USIN_Query_Helper::get_gmt_offset_date_select('MIN(date_created_gmt)');
		$last_order_select = USIN_Query_Helper::get_gmt_offset_date_select('MAX(date_created_gmt)');

		return "SELECT count(ID) as order_num,  $first_order_select as first_order, $last_order_select as last_order,".
			" customer_id as user_id FROM {$wpdb->prefix}wc_orders".
			" WHERE type = '$this->order_type' GROUP BY user_id";
	}

	protected function get_successful_orders_select(){
		global $wpdb;

		$statuses = self::get_successful_statuses(true);

		return "SELECT count(ID) as successful_order_num, SUM(total_amount) AS ltv, customer_id as user_id FROM {$wpdb->prefix}wc_orders".
			" WHERE type = '$this->order_type' AND status IN ( $statuses ) GROUP BY user_id";
	}

	protected function get_order_origins_select(){
		global $wpdb;

		$orders_meta_table = $wpdb->prefix.'wc_orders_meta';

		$query = "SELECT customer_id as user_id, GROUP_CONCAT(source_meta.meta_value SEPARATOR ', ') AS source, ".
			"GROUP_CONCAT(type_meta.meta_value SEPARATOR ', ') AS type ".
			"FROM $this->orders_table as orders ".
			"LEFT JOIN $orders_meta_table AS source_meta ON orders.id = source_meta.order_id ".
			"LEFT JOIN $orders_meta_table AS type_meta ON orders.id = type_meta.order_id ".
			"WHERE   type = '$this->order_type' ".
			"AND     source_meta.meta_key      = '_wc_order_attribution_utm_source' ".
			"AND     type_meta.meta_key      = '_wc_order_attribution_source_type' ".
			"GROUP BY customer_id order by orders.id ASC";

		return $query;
	}

	public function apply_filters($custom_query_data, $filter){
		global $wpdb;

		if(in_array($filter->operator, array('include', 'exclude'))){
			global $wpdb;

			$operator = $filter->operator == 'include' ? '>' : '=';

			if($filter->by == 'has_ordered'){
				if(!$this->has_ordered_join_applied){
					//apply the joins only once, even when this type of filter is applied multiple times
					$custom_query_data['joins'] .=
						" INNER JOIN $this->orders_table AS woop ON $wpdb->users.ID = woop.customer_id AND woop.type = '$this->order_type'".
						" INNER JOIN ".$wpdb->prefix."woocommerce_order_items AS woi ON woop.id =  woi.order_id".
						" INNER JOIN ".$wpdb->prefix."woocommerce_order_itemmeta AS woim ON woi.order_item_id = woim.order_item_id";

					$this->has_ordered_join_applied = true;
				}

				$custom_query_data['where'] = " AND woim.meta_key = '_product_id'";
				$custom_query_data['having'] = $wpdb->prepare(" AND SUM(woim.meta_value IN (%d)) $operator 0", $filter->condition);

			}elseif($filter->by == 'has_order_status'){
				if(!$this->has_order_status_join_applied){
					//apply the joins only once, even when this type of filter is applied multiple times
					$custom_query_data['joins'] .=
						" INNER JOIN $this->orders_table AS wsp ON $wpdb->users.ID = wsp.customer_id AND wsp.type = '$this->order_type'";
					$this->has_order_status_join_applied = true;
				}

				$custom_query_data['having'] = $wpdb->prepare(" AND SUM(wsp.status IN (%s)) $operator 0", $filter->condition);
			}
		}elseif($filter->by == 'has_used_coupon'){
			if(!$this->coupon_join_applied){
				$custom_query_data['joins'] .=
					" INNER JOIN $this->orders_table AS wccp ON $wpdb->users.ID = wccp.customer_id AND wccp.type = '$this->order_type'".
					" INNER JOIN ".$wpdb->prefix."woocommerce_order_items AS wc_coupons ON wccp.ID =  wc_coupons.order_id AND wc_coupons.order_item_type = 'coupon'";
				$this->coupon_join_applied = true;
			}

			$custom_query_data['having'] = $wpdb->prepare(" AND SUM(wc_coupons.order_item_name = %s) > 0", $filter->condition);

		}elseif($filter->by == 'wc_cart'){
			$custom_query_data['joins'] .= $this->get_cart_join();

			$empty_values = USIN_Helper::array_to_sql_string(array('', 'a:1:{s:4:"cart";a:0:{}}', 'a:0:{}', 's:0:"";'));
			if($filter->condition == 'has_items'){
				$custom_query_data['where'] = " AND wc_cart.meta_value IS NOT NULL AND wc_cart.meta_value NOT IN ($empty_values)";
			}elseif ($filter->condition == 'has_no_items'){
				$custom_query_data['where'] = " AND (wc_cart.meta_value IS NULL OR wc_cart.meta_value IN ($empty_values))";
			}
		}elseif($filter->by == 'wc_has_product_in_cart'){
			$custom_query_data['joins'] .= $this->get_cart_join();

			// convert the condition to a serialized value in the form: s:10:"product_id";i:123;
			$condition = serialize(array('product_id'=>$filter->condition));
			$condition = str_replace(array('a:1:{', '}'), '', $condition);

			$custom_query_data['where'] = $wpdb->prepare(" AND wc_cart.meta_value like '%%%s%%'", $wpdb->esc_like($condition));
		}

		return $custom_query_data;
	}

	protected function get_cart_join(){
		$result = '';
		if(!$this->cart_items_join_applied){
			global $wpdb;

			$result = $wpdb->prepare(
				" LEFT JOIN $wpdb->usermeta AS wc_cart ON $wpdb->users.ID = wc_cart.user_id AND wc_cart.meta_key = %s",
				USIN_Woocommerce::get_persistent_cart_key()
			);
			$this->cart_items_join_applied = true;
		}
		return $result;
	}

	public function apply_placed_order_filter($custom_query_data, $filter){
		$filter_by = wp_list_pluck($filter->condition, 'id');
		if(in_array('product', $filter_by) && in_array('product_category', $filter_by)){
			throw new Exception(__('Filtering by both product and product category is not allowed. Please choose only one.', 'usin'));
		}

		global $wpdb;
		$joins = array();
		$wheres = array("WHERE 1 = 1", "o.type = '$this->order_type'");
		$product_join_applied = false;

		foreach ($filter->condition as $condition ) {
			switch ($condition->id) {
				case 'status':
					$wheres[]= $wpdb->prepare("o.status = %s", $condition->val);
					break;
				case 'date':
					$date_column = USIN_Query_Helper::get_gmt_offset_date_select('o.date_created_gmt');
					if(isset($condition->val[0])){
						$wheres[]= $wpdb->prepare("DATE($date_column) >= %s", $condition->val[0]);
					}
					if(isset($condition->val[1])){
						$wheres[]= $wpdb->prepare("DATE($date_column) <= %s", $condition->val[1]);
					}
					break;
				case 'total':
					if(isset($condition->val[0])){
						$wheres[]= $wpdb->prepare("o.total_amount >= %f", $condition->val[0]);
					}
					if(isset($condition->val[1])){
						$wheres[]= $wpdb->prepare("o.total_amount <= %f", $condition->val[1]);
					}
					break;
				case 'product':
				case 'product_category':
					if(!$product_join_applied){
						$joins[]="INNER JOIN ".$wpdb->prefix."woocommerce_order_items AS woi ON o.ID =  woi.order_id";
						$joins[]="INNER JOIN ".$wpdb->prefix."woocommerce_order_itemmeta AS woim ON woi.order_item_id = woim.order_item_id AND woim.meta_key = '_product_id'";
						$product_join_applied = true;
					}

					if($condition->id == 'product'){
						$wheres[] = $wpdb->prepare("woim.meta_value = %d", $condition->val);
					}else{
						$product_ids_in_cat_subquery = self::get_select_product_ids_in_category_query($condition->val);
						$wheres[] = "woim.meta_value IN ($product_ids_in_cat_subquery)";
					}
					break;
				case 'origin_type':
					$joins[] = "INNER JOIN {$wpdb->prefix}wc_orders_meta AS ot ON o.id = ot.order_id AND ot.meta_key = '_wc_order_attribution_source_type'";
					$wheres[] = $wpdb->prepare("ot.meta_value LIKE '%%%s%%'", $wpdb->esc_like($condition->val));
					break;
				case 'origin_source':
					$joins[] = "INNER JOIN {$wpdb->prefix}wc_orders_meta AS os ON o.id = os.order_id AND os.meta_key = '_wc_order_attribution_utm_source'";
					$wheres[] = $wpdb->prepare("os.meta_value LIKE '%%%s%%'", $wpdb->esc_like($condition->val));
					break;
			}
		}

		$table_name = "placed_order_".$this->placed_orders_count;

		$custom_query_data['joins'] .= " INNER JOIN ( 
				SELECT customer_id AS user_id FROM $this->orders_table AS o ".
			implode(" ", $joins)." ".
			implode(" AND ", $wheres).
			") AS $table_name ON $wpdb->users.ID = $table_name.user_id";

		$this->placed_orders_count++;

		return $custom_query_data;
	}

	public function replace_country_code_with_name($user_data){
		if(!empty($user_data->wc_billing_country)){
			$user_data->wc_billing_country = USIN_Woocommerce::get_wc_country_name_by_code($user_data->wc_billing_country);
		}

		return $user_data;
	}

	/**
	 * Resets the query options - this should be called when more than one
	 * query is performed per http request
	 */
	public function reset(){
		unset($this->orders);
		$this->has_ordered_join_applied = false;
		$this->has_order_status_join_applied = false;
		$this->coupon_join_applied = false;
		$this->cart_items_join_applied = false;
	}

	public static function get_successful_statuses($to_sql = false){
		$statuses = array( 'wc-completed', 'wc-processing' );
		$statuses = apply_filters('usin_wc_successful_order_statuses', $statuses);

		if($to_sql){
			$statuses = USIN_Helper::array_to_sql_string($statuses);
		}
		return $statuses;
	}

	public static function get_sales_statuses($to_sql = false){
		$statuses = array('wc-completed', 'wc-processing', 'wc-on-hold');
		$statuses = apply_filters('usin_wc_sale_order_statuses', $statuses);

		if($to_sql){
			$statuses = USIN_Helper::array_to_sql_string($statuses);
		}
		return $statuses;
	}

	public static function add_subcategories_to_category($category_id){
		$args = array('child_of' => $category_id, 'hierarchical' => 1, 'hide_empty' => false);
		$categories = wp_list_pluck(get_terms('product_cat', $args), 'term_id');
		$categories[]= $category_id;
		return wp_parse_id_list($categories); //sanitize the IDs
	}

	public static function get_select_product_ids_in_category_query($category_id){
		global $wpdb;
		$categories = self::add_subcategories_to_category($category_id);

		return $wpdb->prepare("SELECT DISTINCT(p.ID) FROM $wpdb->posts p
			 INNER JOIN $wpdb->term_relationships tr ON p.ID = tr.object_id
			 INNER JOIN $wpdb->term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
			 INNER JOIN $wpdb->terms t ON tt.term_id = t.term_id
			 WHERE p.post_type = %s AND tt.taxonomy = '%s' AND t.term_id IN (".implode(',', $categories)." )",
			 USIN_Woocommerce::PRODUCT_POST_TYPE, USIN_Woocommerce::PRODUCT_CATEGORY_TAX);
	}

	public static function get_product_query($product_id, $category_id = null){
		global $wpdb;

		if(!$product_id && !$category_id){
			return '';
		}

		$id_column = USIN_Woocommerce::custom_order_tables_enabled() ? 'id' : 'ID';
		$query = " INNER JOIN {$wpdb->prefix}woocommerce_order_items AS items ON orders.{$id_column} = items.order_id" .
			" INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta ON items.order_item_id = meta.order_item_id" .
			" AND meta.meta_key = '_product_id'";

		if($product_id){
			$query .= $wpdb->prepare(" AND meta.meta_value = %d", intval($product_id));
		}else{
			$category_subquery = self::get_select_product_ids_in_category_query(intval($category_id));
			$query .= " AND meta.meta_value IN ($category_subquery)";
		}
		return $query;
	}
}