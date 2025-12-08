<?php

class USIN_WC_Subscriptions_User_Activity {
	protected $order_type;
	protected $cot_enabled;

	public function __construct($order_type){
		$this->order_type = $order_type;
		$this->cot_enabled = USIN_WC_Subscriptions::custom_order_tables_enabled();
		$this->init();
	}

	public function init(){
		add_filter('usin_user_activity', array($this, 'add_subscriptions_to_user_activity'), 10, 2);
	}

	public function add_subscriptions_to_user_activity($activity, $user_id){
		$subscription_ids = $this->get_subscription_ids($user_id);
		if(empty($subscription_ids) || !class_exists('WC_Subscription')){
			return $activity;
		}

		$count = sizeof($subscription_ids);
		$latest_subscriptions_ids = array_slice($subscription_ids, 0, 5);
		$list = array();

		foreach($latest_subscriptions_ids as $subscription_id){
			$subscription = new WC_Subscription($subscription_id);
			$title = "#{$subscription->get_id()}";
			$details = array();

			//get the date
			if(method_exists($subscription, 'get_date')){
				$title .= ' ' . USIN_Helper::format_date($subscription->get_date('date_created', 'site'));
			}

			//get the status
			if(method_exists($subscription, 'get_status') && function_exists('wcs_get_subscription_status_name')){
				$status = $subscription->get_status();
				$title .= USIN_Html::tag(wcs_get_subscription_status_name($status), $status);
			}

			//get the items
			if(method_exists($subscription, 'get_items')){
				$subscription_items = $subscription->get_items();

				if(!empty($subscription_items) && is_array($subscription_items)){
					foreach($subscription_items as $item){
						if(is_array($item) && isset($item['name'])){
							$details[] = $item['name'];
						}elseif(method_exists($item, 'get_name')){
							$details[] = $item->get_name();
						}
					}
				}
			}

			//get start date, end date and next payment date
			if(method_exists($subscription, 'get_date')){
				$start_date = $subscription->get_date('start', 'site');
				if($start_date){
					$details[] = USIN_Html::activity_label(__('Start date', 'usin'), USIN_Helper::format_date($start_date));
				}

				$end_date = $subscription->get_date('end', 'site');
				if($end_date){
					$details[] = USIN_Html::activity_label(__('End date', 'usin'), USIN_Helper::format_date($end_date));
				}

				$next_payment_date = $subscription->get_date('next_payment', 'site');
				if($next_payment_date && $subscription->get_status() == 'active'){
					$details[] = USIN_Html::activity_label(__('Next payment', 'usin'), USIN_Helper::format_date($next_payment_date));
				}
			}

			if(method_exists($subscription, 'get_related_orders')){
				$order_num = sizeof($subscription->get_related_orders());
				if($order_num > 0){
					$orders_url = $this->get_related_orders_url($subscription->get_id());
					$details[] = sprintf('<a href="%s" target="_blank">%d %s</a>', $orders_url, $order_num, _n('related order', 'related orders', $order_num));
				}
			}

			$subscription_info = array('title' => $title, 'link' => $this->get_subscription_edit_url($subscription->get_id()));
			if(!empty($details)){
				$subscription_info['details'] = $details;
			}

			$list[] = $subscription_info;
		}

		$activity[] = array(
			'type' => 'wc_subscriptions',
			'for' => $this->order_type,
			'label' => _n('Subscription', 'Subscriptions', $count, 'usin'),
			'count' => $count,
			'link' => $this->get_subscription_list_url($user_id),
			'list' => $list,
			'icon' => 'woocommerce'
		);

		return $activity;
	}

	private function get_subscription_ids($user_id){
		if($this->cot_enabled){
			global $wpdb;
			$query = $wpdb->prepare("SELECT id FROM {$wpdb->prefix}wc_orders WHERE type = %s AND customer_id = %d ORDER BY date_created_gmt DESC",
				$this->order_type, $user_id);
			return $wpdb->get_col($query);
		}else{
			$args = array(
				'meta_key' => '_customer_user',
				'meta_value' => $user_id,
				'post_type' => $this->order_type,
				'post_status' => 'any',
				'fields' => 'ids',
				'numberposts' => -1,
				'orderby' => 'date',
				'order' => 'DESC'
			);
			return get_posts($args);
		}
	}

	private function get_subscription_edit_url($subscription_id){
		if($this->cot_enabled){
			return admin_url('admin.php?page=wc-orders--shop_subscription&action=edit&id=' . intval($subscription_id));
		}else{
			return get_edit_post_link($subscription_id, '');
		}
	}

	private function get_subscription_list_url($user_id){
		if($this->cot_enabled){
			return admin_url('admin.php?page=wc-orders--shop_subscription&_customer_user=' . intval($user_id) . '&status=all');
		}else{
			return admin_url('edit.php?post_status=all&post_type=' . $this->order_type . '&_customer_user=' . intval($user_id));
		}
	}

	private function get_related_orders_url($subscription_id){
		if($this->cot_enabled){
			return admin_url('admin.php?page=wc-orders&status=all&_subscription_related_orders=' . intval($subscription_id));
		}else{
			return admin_url('edit.php?post_status=all&post_type=shop_order&_subscription_related_orders=' . intval($subscription_id));
		}
	}
}