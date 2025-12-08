<?php

class USIN_WC_Subscriptions_Query{
	
	protected $order_type;
	protected $has_subscription_status_join_applied = false;
	protected $has_subscriptions_counter = 0;
	
	public function __construct($order_type){
		$this->order_type = $order_type;
		$this->init();
	}
	
	public function init(){
		add_filter('usin_db_map', array($this, 'filter_db_map'));
		add_filter('usin_query_join_table', array($this, 'filter_query_joins'), 10, 2);
		add_filter('usin_custom_query_filter', array($this, 'apply_filters'), 10, 2);
		add_filter('usin_user_db_data', array($this, 'set_status_names'));
		add_filter('usin_custom_query_filter_has_subscription', array($this, 'apply_has_subscription_filter'), 10, 2);
	}
	
	public function filter_db_map($db_map){
		$db_map['subscription_num'] = array('db_ref'=>'subscription_num', 'db_table'=>'subscriptions', 'null_to_zero'=>true, 'set_alias'=>true);
		$db_map['subscripton_statuses'] = array('db_ref'=>'statuses', 'db_table'=>'subscriptions', 'nulls_last'=>true);
		$db_map['subscripton_next_payment'] = array('db_ref'=>'next_payment', 'db_table'=>'subscripton_payments', 'set_alias'=>true, 'nulls_last'=>true);
		$db_map['has_subscription'] = array('db_ref'=>'', 'db_table'=>'', 'no_select'=>true);
		return $db_map;
	}

	public function filter_query_joins($query_joins, $table){
		global $wpdb;

		if($table === 'subscriptions'){
			$query_joins .= " LEFT JOIN (SELECT count(id) as subscription_num,  GROUP_CONCAT(status SEPARATOR ',') AS statuses, customer_id FROM {$wpdb->prefix}wc_orders".
				" WHERE type = '$this->order_type'";
				
			$allowed_statuses = USIN_Helper::array_to_sql_string($this->get_subscription_statuses_keys());
			if(!empty($allowed_statuses)){
				$query_joins .= " AND status IN ($allowed_statuses)";
			}
			$query_joins .=" GROUP BY customer_id) as subscriptions ON $wpdb->users.ID = subscriptions.customer_id";
		}elseif($table === 'subscripton_payments'){
			$next_payment_select = USIN_Query_Helper::get_gmt_offset_date_select('MIN(CAST(next_payments.meta_value AS DATETIME))');

			$query_joins .= " LEFT JOIN (
					SELECT $next_payment_select AS next_payment, subs.customer_id FROM {$wpdb->prefix}wc_orders as subs
					INNER JOIN {$wpdb->prefix}wc_orders_meta AS next_payments on subs.id = next_payments.order_id AND next_payments.meta_key = '_schedule_next_payment'
					WHERE subs.type = '$this->order_type' AND subs.status = 'wc-active'
					GROUP BY subs.customer_id
				) AS subscripton_payments ON $wpdb->users.ID = subscripton_payments.customer_id";
		}

		return $query_joins;
	}

	public function apply_filters($custom_query_data, $filter){
		if($filter->by == 'subscripton_statuses'){
			return $this->set_subsciption_statuses_join($custom_query_data, $filter);
		}

		return $custom_query_data;
	}

	protected function set_subsciption_statuses_join($custom_query_data, $filter){
		global $wpdb;

		$operator = $filter->operator == 'include' ? '>' : '=';
		
		if(!$this->has_subscription_status_join_applied){
			//apply the joins only once, even when this type of filter is applied multiple times
			$custom_query_data['joins'] .=
				" INNER JOIN {$wpdb->prefix}wc_orders AS wcs_orders ON $wpdb->users.ID = wcs_orders.customer_id AND wcs_orders.type = '$this->order_type'";

			$this->has_subscription_status_join_applied = true;
		}

		$custom_query_data['having'] = $wpdb->prepare(" AND SUM(wcs_orders.status IN (%s)) $operator 0", $filter->condition);

		return $custom_query_data;
	}


	public function apply_has_subscription_filter($custom_query_data, $filter){
		global $wpdb;
		$joins = array();
		$wheres = array("WHERE 1 = 1", "subs.type = '$this->order_type'");

		foreach ($filter->condition as $condition ) {
			switch ($condition->id) {
				case 'status':
					$wheres[]= $wpdb->prepare("subs.status = %s", $condition->val);
					break;
				case 'start_date':
					$joins[]= "LEFT JOIN {$wpdb->prefix}wc_orders_meta AS start_dates ON subs.id = start_dates.order_id AND start_dates.meta_key = '_schedule_start'";
					$start_date_ref = USIN_Query_Helper::get_gmt_offset_date_select('IFNULL(start_dates.meta_value, subs.date_created_gmt)');
					$start_date_ref = "DATE($start_date_ref)";
					if(isset($condition->val[0])){
						$wheres[]= $wpdb->prepare("$start_date_ref >= %s", $condition->val[0]);
					}
					if(isset($condition->val[1])){
						$wheres[]= $wpdb->prepare("$start_date_ref <= %s", $condition->val[1]);
					}
					break;
				case 'end_date':
					$joins[]= "INNER JOIN {$wpdb->prefix}wc_orders_meta AS end_dates ON subs.id = end_dates.order_id and end_dates.meta_key = '_schedule_end'";
					$end_date_ref = USIN_Query_Helper::get_gmt_offset_date_select('end_dates.meta_value');
					$end_date_ref = "DATE($end_date_ref)";
					if(isset($condition->val[0])){
						$wheres[]= $wpdb->prepare("$end_date_ref >= %s", $condition->val[0]);
					}
					if(isset($condition->val[1])){
						$wheres[]= $wpdb->prepare("$end_date_ref <= %s", $condition->val[1]);
					}
					break;
				case 'product':
					$joins[]="INNER JOIN ".$wpdb->prefix."woocommerce_order_items AS items ON subs.id =  items.order_id";
					$joins[]="INNER JOIN ".$wpdb->prefix."woocommerce_order_itemmeta AS item_meta ON items.order_item_id = item_meta.order_item_id AND item_meta.meta_key = '_product_id'";
					$wheres[]= $wpdb->prepare("item_meta.meta_value = %d", $condition->val);
					break;
			}
		}

		$table_name = "has_subscriptions_".$this->has_subscriptions_counter;

		$custom_query_data['joins'] .= " INNER JOIN (".
				" SELECT customer_id FROM {$wpdb->prefix}wc_orders AS subs ".
				implode(" ", $joins)." ".
				implode(" AND ", $wheres).
			") AS $table_name ON $wpdb->users.ID = $table_name.customer_id";


		$this->has_subscriptions_counter++;

		return $custom_query_data;
	}

	
	public function set_status_names($user_data){
		$statuses = USIN_WC_Subscriptions::get_statuses();
		
		if(property_exists($user_data, 'subscripton_statuses') && !empty($user_data->subscripton_statuses)){
			$user_statuses = explode(',', $user_data->subscripton_statuses);
			foreach ($user_statuses as $key => $status) {
				if(isset($statuses[$status])){
					$user_statuses[$key] = $statuses[$status];
				}
			}
			$user_data->subscripton_statuses = implode(', ', $user_statuses);
		}
		
		return $user_data;
	}
	
	protected function get_subscription_statuses_keys($except = array()){
		$statuses = USIN_WC_Subscriptions::get_statuses();
		$status_keys = array_keys($statuses);
		if(!empty($except)){
			$status_keys = array_diff($status_keys, $except);
		}
		return array_values($status_keys);
	}

	public function reset(){
		$this->has_subscription_status_join_applied = false;
		$this->has_subscriptions_counter = 0;
	}
}