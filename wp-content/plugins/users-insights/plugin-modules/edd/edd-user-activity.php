<?php

/**
 * Includes the User Activity functionality for the EDD module.
 */
class USIN_EDD_User_Activity{
	
	protected $order_post_type;
	protected $product_post_type;
	protected $payment_page_slug = 'edd-payment-history';

	/**
	 * @param string $order_post_type   the order(payment) post type
	 * @param string $product_post_type the product(download) post type
	 */
	public function __construct($order_post_type, $product_post_type){
		$this->order_post_type = $order_post_type;
		$this->product_post_type = $product_post_type;
	}
	
	/**
	 * Registers the required filter and action hooks.
	 */
	public function init(){
		add_filter('usin_user_activity', array($this, 'add_orders_to_user_activity'), 10, 2);
		add_filter('usin_user_profile_data', array($this, 'filter_profile_data'));
		add_filter('usin_user_actions', array($this, 'filter_user_actions'), 10, 2);
	}
	
	/**
	 * Adds the EDD order list to the user activity.
	 * @param array $activity the default user activity data 
	 * @param int $user_id  the ID of the user
	 * @return array the default user activity including the EDD order list
	 */
	public function add_orders_to_user_activity($activity, $user_id){
		if(function_exists('edd_get_payments')){
			$orders = edd_get_payments(array(
				'user' => $user_id, 
				'orderby'=>'date', 
				'order'=>'DESC', 
				'nopaging'=>true
			));
			$count = sizeof($orders);
			
			if(!empty($orders)){
				$list = array();
				$min = min($count, 5);
				for ($i = 0; $i < $min; $i++) {
					//load the last several orders only
					$order_id = $orders[$i]->ID;
					$title = "#$order_id";

					if(class_exists('EDD_Payment')){
						$order = new EDD_Payment($order_id);
						$title .= ' '.USIN_Helper::format_date($this->get_order_local_date($order->date));
					}

					if(function_exists('edd_payment_amount')){
						$title .= ' - '.edd_payment_amount( $order_id );
					}
					
					$order_status = '';
					if(function_exists('edd_get_payment_status')){
						$status = edd_get_payment_status($order, true);
						$title.= USIN_Html::tag($status, strtolower($status));
						
					}
					
					if(function_exists('edd_get_payment_meta_downloads') &&
						function_exists('edd_get_download')){
						//get the names of the products ordered
						$order_items = edd_get_payment_meta_downloads($order_id);
						$details = array();
						
						foreach ($order_items as $item) {
							$download = edd_get_download($item['id']);
							$item_name= $download->post_title;
							if(!empty($item['quantity']) && floatval($item['quantity']) != 1){
								$item_name.= " (x".$item['quantity'].")";
							}
							$details[]=$item_name;
						}
					}
					
					$order_info = array('title'=>$title, 'link'=>$this->get_order_link($order_id));
					if(!empty($details)){
						$order_info['details'] = $details;
					}
					$list[]=$order_info;
				}

				$user = get_user_by('id', $user_id);
				$activity[] = array(
					'type' => 'edd_order',
					'for' => $this->order_post_type,
					'label' => _n('Order', 'Orders', $count, 'usin'),
					'count' => $count,
					'link' => $this->get_order_list_link($user_id),
					'list' => $list,
					'icon' => 'edd'
				);
			}
		}
		
		return $activity;
	}

	protected function get_order_local_date($date_string){
		// EDD 3.0+ stores dates in UTC so we need to convert them to local dates
		return USIN_EDD::is_edd_v30() ? get_date_from_gmt($date_string) : $date_string;
	}

	protected function get_order_link($order_id){
		if(USIN_EDD::is_edd_v30()){
			return admin_url( 'edit.php?post_type=download&page=edd-payment-history&view=view-order-details&id=' . $order_id );
		}else{
			return get_edit_post_link( $order_id );
		}
	}

	protected function get_order_list_link($user_id){
		return admin_url(sprintf('edit.php?post_type=%s&page=%s&customer=%d', 
						$this->product_post_type, 
						$this->payment_page_slug, 
						$this->get_customer_id($user_id)));
	}

	private function get_customer_id($user_id){
		global $wpdb;
		$customers_table = $wpdb->prefix.'edd_customers';
		return $wpdb->get_var($wpdb->prepare("SELECT id from $customers_table where user_id = %d", $user_id));
	}
	
	/**
	 * Filters the user profile data - formats the total spent field to include 
	 * the currency.
	 * @param  USIN_User $user the user object
	 * @return USIN_User       the modified user object
	 */
	public function filter_profile_data($user){
		if(isset($user->edd_total_spent) && function_exists('edd_currency_filter')){
			$user->edd_total_spent = html_entity_decode(edd_currency_filter($user->edd_total_spent));
		}
		return $user;
	}

	public function filter_user_actions($actions, $user_id){
		$customer_id = $this->get_customer_id($user_id);

		if($customer_id){
			$actions[]=array(
				'id'=>'view-edd-profile',
				'name' => __('View Easy Digital Downloads profile', 'usin'),
				'link' => admin_url(sprintf('edit.php?post_type=download&page=edd-customers&view=overview&id=%d', $customer_id))
			);
		}

		return $actions;
	}
	
	
}