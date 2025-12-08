<?php

class USIN_Woocommerce_User_Activity{

	protected $order_post_type;

	public function __construct($order_post_type){
		$this->order_post_type = $order_post_type;
	}

	public function init(){
		add_filter('usin_user_activity', array($this, 'filter_user_activity'), 10, 2);
	}
	
	public function filter_user_activity($activity, $user_id){
		$activity = $this->add_orders_to_user_activity($activity, $user_id);
		$activity = $this->add_cart_items_to_user_activity($activity, $user_id);
		$activity = $this->add_reviews_to_activity($activity, $user_id);
		$activity = $this->add_coupons_to_user_activity($activity, $user_id);
		return $activity;
	}
	
	protected function add_orders_to_user_activity($activity, $user_id){
		$order_activity_items = $this->get_order_activity($user_id);
		
		if(empty($order_activity_items)){
			return $activity;
		}

		$count = sizeof($order_activity_items);
		$latest_activity_items = array_slice($order_activity_items, 0, 5);

		$list = array();
		foreach ($latest_activity_items as $order_activity_item) {
			$order_info=array('title' => $order_activity_item->get_title(), 'link' => $order_activity_item->get_link());
			$details = $order_activity_item->get_details();

			if(!empty($details)){
				$order_info['details'] = $details;
			}
			$list[]= $order_info;
		}

		$activity[] = array(
			'type' => 'wc_orders',
			'for' => $this->order_post_type,
			'label' => _n('Order', 'Orders', $count, 'usin'),
			'count' => $count,
			'link' => admin_url('edit.php?post_status=all&post_type=shop_order&_customer_user='.intval($user_id)),
			'list' => $list,
			'icon' => 'woocommerce'
		);

	return $activity;
	}

	protected function get_order_activity($user_id){
		global $wpdb;

		if(USIN_Woocommerce::custom_order_tables_enabled()){
			$date_select = USIN_Query_Helper::get_gmt_offset_date_select('date_created_gmt');
			$query = $wpdb->prepare("SELECT id as order_id, DATE($date_select) AS order_date FROM {$wpdb->prefix}wc_orders " .
				"WHERE type = %s AND customer_id = %d ORDER BY date_created_gmt DESC", $this->order_post_type, $user_id);
		}else{
			$query = $wpdb->prepare("SELECT ID as order_id, DATE(post_date) AS order_date FROM $wpdb->posts AS orders ".
				"INNER JOIN $wpdb->postmeta AS meta ON meta.post_id = orders.ID AND meta.meta_key = '_customer_user' ".
				"WHERE post_type = %s AND meta.meta_value = %d ORDER BY post_date DESC", $this->order_post_type, $user_id);
		}
		
		$result = $wpdb->get_results($query);
		$activity = array();

		foreach($result as $order_result){
			$activity[]= new USIN_WooCommerce_Order_Activity_Item($order_result->order_id, $order_result->order_date);
		}

		return $activity;
	}

	protected function add_reviews_to_activity($activity, $user_id){
		
		foreach ($activity as $i => $activity_data) {
			
			if(isset($activity_data['type']) && $activity_data['type'] == 'comment_product'){
				//unset it and add it to the end of the list so it will be shown after the orders
				unset($activity[$i]);
				
				$reviews = $activity_data;
				
				$reviews['label'] = _n('Product Review', 'Product Reviews', $reviews['count'], 'usin');
				$reviews['icon'] = 'woocommerce';
				$reviews['list'] = array();
				
				$com_args = array('user_id'=>$user_id, 'post_type'=>'product', 'number'=>5,
					'orderby'=>'date', 'order'=>'DESC');
				$comments = get_comments($com_args);
				
				foreach ($comments as $comment) {
					$rating = intval(get_comment_meta( $comment->comment_ID, 'rating', true ));
					$title = '<span title="'.$rating.' star rating" class="usin-rating">';
					for($i = 0; $i<5; $i++){
						if($i < $rating){
							$title .= '<span class="usin-icon-star usin-rating-icon"></span>';
						}else{
							$title .= '<span class="usin-icon-star_border usin-rating-icon"></span>';
						}
						
					}
					$title .=sprintf('</span> <span class="usin-rating-title">%s %s</span>', __('for', 'usin') , get_the_title($comment->comment_post_ID));
					
					
					$content = wp_html_excerpt( $comment->comment_content, 40, ' [...]');
					$reviews['list'][]=array(
						'title'=>$title, 
						'link'=>get_permalink($comment->comment_post_ID),
						'details'=>array($content)
					);
				}
				
				$activity[] = $reviews;

			}
		}
		return array_values($activity);
	}


	protected function add_coupons_to_user_activity($activity, $user_id){
		$coupons_used = $this->get_coupons_used($user_id);

		if(empty($coupons_used)){
			return $activity;
		}

		$count = sizeof($coupons_used);
		$list = array();

		foreach ($coupons_used as $coupon ) {
			$title = sprintf('%s - %s #%s', $coupon->code, __('Order', 'usin'), $coupon->order_id);
			$link = null;

			$order = new WC_Order($coupon->order_id);
			if(method_exists($order, 'get_edit_order_url')){
				$link = $order->get_edit_order_url();
			}
			$list[]= array('title' => $title, 'link' => $link);
		}

		$activity[] = array(
			'type' => 'wc_coupons',
			'for' => 'wc_coupons',
			'label' => _n('Coupon Used', 'Coupons Used', $count, 'usin'),
			'count' => $count,
			'list' => $list,
			'icon' => 'woocommerce'
		);

		return $activity;
		
	}
	
	protected function add_cart_items_to_user_activity($activity, $user_id){
		$cart = maybe_unserialize(get_user_meta( $user_id, USIN_Woocommerce::get_persistent_cart_key(), true));

		if(empty($cart) || empty($cart['cart'])){
			return $activity;
		}

		$count = 0;
		$list = array();

		foreach ($cart['cart'] as $item) {
			$title = get_the_title($item['product_id']);
			if($item['quantity']!=1){
				$title .= sprintf(" (x%d)", $item['quantity']);
			}
			$count += $item['quantity'];
			$list[]= array(
				'title' => $title,
				'link' => get_the_permalink($item['product_id'])
			);
		}

		$activity[] = array(
			'type' => 'wc_cart_items',
			'for' => 'wc_cart_items',
			'label' => _n('Item in Cart', 'Items in Cart', $count, 'usin'),
			'count' => $count,
			'list' => $list,
			'icon' => 'woocommerce'
		);

		return $activity;
	}

	protected function get_coupons_used($user_id){
		global $wpdb;

		if(USIN_Woocommerce::custom_order_tables_enabled()){
			$query = $wpdb->prepare("SELECT c.order_item_name as code, order_id FROM ".$wpdb->prefix."woocommerce_order_items c".
				" INNER JOIN {$wpdb->prefix}wc_orders o ON c.order_id = o.id".
				" WHERE c.order_item_type = 'coupon' AND o.type = %s AND o.customer_id = %d", $this->order_post_type, $user_id);
		}else{
			$query = $wpdb->prepare("SELECT c.order_item_name as code, order_id FROM ".$wpdb->prefix."woocommerce_order_items c".
				" INNER JOIN $wpdb->posts p ON c.order_id = p.ID".
				" INNER JOIN $wpdb->postmeta m ON p.ID = m.post_id AND m.meta_key = '_customer_user'".
				" WHERE c.order_item_type = 'coupon' AND p.post_type = %s AND m.meta_value = %d", $this->order_post_type, $user_id);
		}

		return $wpdb->get_results($query);

	}
	
}

class USIN_WooCommerce_Order_Activity_Item{
	protected $order_id;
	protected $order_date;
	protected $wc_order;

	public function __construct($order_id, $order_date){
		$this->order_id = $order_id;
		$this->order_date = $order_date;
	}

	/**
	 * Use a separate method to retrieve the WC_Order object only when it is needed.
	 * In this way, we'll avoid initializing (and DB lookup) of WC_Order unless it is needed.
	 */
	protected function get_order(){
		if(empty($this->wc_order)){
			$this->wc_order = new WC_Order($this->order_id);
		}
		return $this->wc_order;
	}

	public function get_title(){
		$order = $this->get_order();
		$title = sprintf('#%s %s', $this->order_id, USIN_Helper::format_date($this->order_date));

		if(method_exists($order, 'get_total') && function_exists('wc_price')){
			$title .= ' - ' . wc_price($order->get_total());
		}

		if(method_exists($order, 'get_status') && function_exists('wc_get_order_status_name')){
			$status = $order->get_status();
			$title .= USIN_Html::tag(wc_get_order_status_name($status), $status);
		}

		return $title;
	}

	public function get_details(){
		$order = $this->get_order();
		$details = array();

		if(method_exists($order, 'get_items')){
			$order_items = $order->get_items();

			if(!empty($order_items) && is_array($order_items)){
				foreach ($order_items as $item_id => $item ) {
					$name = '';
					$qty = null;
					if(is_array($item)){
						$name = $item['name'];
						if(function_exists('wc_get_order_item_meta')){
							$qty = wc_get_order_item_meta($item_id, '_qty', true);
						}
					}elseif(get_class($item) == 'WC_Order_Item_Product' && method_exists($item, 'get_name')){
						$name = $item->get_name();
						if(method_exists($item, 'get_quantity')){
							$qty = $item->get_quantity();
						}
					}

					if(!empty($qty) && floatval($qty) != 1){
						$name.= " (x$qty)";
					}
					$details[]=$name;
				}
			}
		}

		$origin = $this->get_origin();
		if(!empty($origin)){
			$details[] = $origin;
		}

		return $details;
	}

	public function get_link(){
		$order = $this->get_order();
		if(method_exists($order, 'get_edit_order_url')){
			return $order->get_edit_order_url();
		}
	}

	private function get_origin(){
		if(!USIN_Woocommerce::is_order_attribution_enabled()){
			return;
		}

		$origin_parts = [];

		$order = $this->get_order();
		if(method_exists($order, 'get_meta')){
			$type = $order->get_meta('_wc_order_attribution_source_type', true);
			if(!empty($type)){
				$origin_parts[] = $type;
			}

			$source = $order->get_meta('_wc_order_attribution_utm_source', true);
			if(!empty($source)){
				$origin_parts[] = $source;
			}
		}

		if(!empty($origin_parts)){
			return USIN_Html::activity_label(__('Origin', 'usin'), implode(' - ', $origin_parts));
		}
	}
}
