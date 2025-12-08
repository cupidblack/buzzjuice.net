<?php

class USIN_WooCommerce_Reports extends USIN_Module_Reports{

	protected $group = 'woocommerce';
	protected $product_group = 'woocommerce_products';
	protected $category_group = 'woocommerce_categories';

	public function get_group(){
		$product_search = new USIN_Post_Option_Search(USIN_Woocommerce::PRODUCT_POST_TYPE);

		return array(
			array(
				'id' => $this->group,
				'name' => 'WooCommerce',
				'info' => '* All of the WooCommerce reports except cart reports reflect both user and guest orders'
			),
			array(
				'id' => $this->product_group,
				'name' => 'WooCommerce Products',
				'info' => '* All of the WooCommerce product reports reflect both user and guest orders',
				'filters' => array(
					array(
						'id' => 'product',
						'name' => __('Select a product', 'usin'),
						'type' => 'select_option',
						'options' => $product_search->get_options(),
						'searchAction' => $product_search->get_search_action()
					)
				)
			),
			array(
				'id' => $this->category_group,
				'name' => 'WooCommerce Categories',
				'info' => '* All of the WooCommerce category reports reflect both user and guest orders',
				'filters' => array(
					array(
						'id' => 'category',
						'name' => __('Select a category', 'usin'),
						'type' => 'select_option',
						'options' => USIN_WooCommerce::get_product_category_options()
					)
				)
			)
		);
	}

	public function get_reports(){
		$statuses = array('all' => __('All statuses', 'usin'));
		$sales_statuses = USIN_Woocommerce_Query::get_sales_statuses();

		if(function_exists('wc_get_order_statuses')){
			$wc_statuses = wc_get_order_statuses();
			if(is_array($wc_statuses)){
				$statuses = array_merge($statuses, $wc_statuses);

				// replace status keys with names
				foreach($sales_statuses as &$status){
					if(isset($wc_statuses[$status])){
						$status = $wc_statuses[$status];
					}
				}
			}
		}

		$sales_statuses = implode(', ', $sales_statuses);

		return array(
			new USIN_Period_Report('woocommerce_sales', __('Sales', 'usin'), 
				array('group' => $this->group, 'info' => sprintf(__('Orders with status %s', 'usin'), $sales_statuses))),
			new USIN_Period_Report('woocommerce_sales_total', __('Sales total', 'usin'), 
				array('group' => $this->group, 'format' => 'float', 'info' => 'Total amount of sales (does not reflect partial refunds)')),
			new USIN_Stacked_Period_Report('woocommerce_orders', __('Orders by status', 'usin'),
				array('group' => $this->group,)),
			new USIN_Stacked_Period_Report('woocommerce_new_customers', __('New vs returning customers', 'usin'),
				array('group' => $this->group)),
			new USIN_Standard_Report('woocommerce_order_number', __('Number of orders per customer', 'usin'), 
				array('group' => $this->group,
				'filters' => array(
					'options' => $statuses,
					'default' => 'all'
				))),
			new USIN_Standard_Report_With_Period_Comparison_Filter('woocommerce_performance_comparison', __('Performance comparison', 'usin'),
				array('group' => $this->group, 'type'=>USIN_Report::BAR)),
			new USIN_Standard_Report('woocommerce_items_per_order', __('Number of items per order', 'usin'), 
				array('group' => $this->group,
				'filters' => array(
					'options' => $statuses,
					'default' => 'all'
				))),	
			new USIN_Standard_Report('woocommerce_billing_countries', __('Top billing countries', 'usin'), 
				array('group' => $this->group, 'type'=>USIN_Report::BAR)),
			new USIN_Standard_Report('woocommerce_billing_states', __('Top billing states', 'usin'), 
				array('group' => $this->group, 'type'=>USIN_Report::BAR, 'visible' => false)),
			new USIN_Standard_Report('woocommerce_billing_cities', __('Top billing cities', 'usin'), 
				array('group' => $this->group, 'type'=>USIN_Report::BAR)),
			new USIN_Standard_Report_With_Period_Filter('woocommerce_order_statuses', __('Order status', 'usin'),
				array('group' => $this->group)),
			new USIN_Standard_Report_With_Period_Filter('woocommerce_payment_methods', __('Payment methods used', 'usin'),
				array('group' => $this->group, 'visible' => false)),
			new USIN_Standard_Report_With_Period_Filter('woocommerce_coupons_used', __('Top coupons used', 'usin'),
				array('group' => $this->group, 'type' => USIN_Report::BAR)),
			new USIN_Standard_Report_With_Period_Filter('woocommerce_ordered_products', __('Top ordered products', 'usin'),
				array('group' => $this->group, 'type'=>USIN_Report::BAR)),
			new USIN_Standard_Report_With_Period_Filter('woocommerce_refunded_products', __('Most refunded products', 'usin'),
				array('group' => $this->group, 'type'=>USIN_Report::BAR)),
			new USIN_Period_Report('woocommerce_abandoned_carts', __('Abandoned carts', 'usin'),
				array('group' => $this->group, 'info' => __('Number of abandoned carts of logged in users', 'usin'))),
			new USIN_Standard_Report_With_Period_Filter('woocommerce_origin_sources', __('Top order origin sources', 'usin'),
				array('group' => $this->group, 'type'=>USIN_Report::BAR)),
			new USIN_Standard_Report_With_Period_Filter('woocommerce_origin_types', __('Top order origin types', 'usin'),
				array('group' => $this->group, 'type'=>USIN_Report::BAR)),

			// PRODUCT REPORTS
			new USIN_Period_Report('woocommerce_product_sales', __('Sales', 'usin'),
				array(
					'group' => $this->product_group,
					'loader_class' => 'USIN_Woocommerce_Sales_Loader',
					'info' => sprintf(__('Number of orders containing the selected product with status %s'), $sales_statuses)
				)),
			new USIN_Stacked_Period_Report('woocommerce_product_orders', __('Orders by status', 'usin'),
				array('group' => $this->product_group, 'loader_class' => 'USIN_Woocommerce_Orders_Loader')),
			new USIN_Standard_Report_With_Period_Comparison_Filter('woocommerce_product_performance_comparison', __('Performance comparison', 'usin'),
				array('group' => $this->product_group, 'type'=>USIN_Report::BAR, 'loader_class' => 'USIN_Woocommerce_Performance_Comparison_Loader')),
			new USIN_Period_Report('woocommerce_product_items_sold', __('Items sold', 'usin'),
				array('group' => $this->product_group, 'info' => sprintf(__('Number of items sold of the selected product in orders with status %s'), $sales_statuses))),
			new USIN_Period_Report('woocommerce_product_sales_total', __('Items sold total', 'usin'),
				array('group' => $this->product_group, 'format' => 'float', 'info' => sprintf(__('Total amount of items sold of the selected product in orders with status %s (does not reflect partial refunds)', 'usin'), $sales_statuses))),
			new USIN_Standard_Report_With_Period_Filter('woocommerce_bought_together', __('Frequently bought together', 'usin'),
				array('group' => $this->product_group, 'type'=>USIN_Report::BAR)),
			new USIN_Standard_Report_With_Period_Filter('woocommerce_product_order_statuses', __('Order status', 'usin'),
				array('group' => $this->product_group, 'loader_class' => 'USIN_Woocommerce_Order_Statuses_Loader')),
			new USIN_Standard_Report_With_Period_Filter('woocommerce_product_variations', __('Top ordered variations', 'usin'),
				array('group' => $this->product_group, 'type'=>USIN_Report::BAR)),
			new USIN_Standard_Report_With_Period_Filter('woocommerce_product_attributes', __('Top ordered attributes', 'usin'),
				array('group' => $this->product_group, 'type'=>USIN_Report::BAR)),
			new USIN_Standard_Report_With_Period_Filter('woocommerce_product_origin_sources', __('Top order origin sources', 'usin'),
				array('group' => $this->product_group, 'type'=>USIN_Report::BAR, 'loader_class' => 'USIN_Woocommerce_Origin_Sources_Loader')),
			new USIN_Standard_Report_With_Period_Filter('woocommerce_product_origin_types', __('Top order origin types', 'usin'),
				array('group' => $this->product_group, 'type'=>USIN_Report::BAR, 'loader_class' => 'USIN_Woocommerce_Origin_Types_Loader')),


			// CATEGORY REPORTS
			new USIN_Period_Report('woocommerce_category_sales', __('Sales', 'usin'),
				array(
					'group' => $this->category_group,
					'loader_class' => 'USIN_Woocommerce_Sales_Loader',
					'info' => sprintf(__('Number of orders containing the products from the selected category with status %s'), $sales_statuses)
				)),
			new USIN_Stacked_Period_Report('woocommerce_category_orders', __('Orders by status', 'usin'),
				array('group' => $this->category_group, 'loader_class' => 'USIN_Woocommerce_Orders_Loader')),
			new USIN_Period_Report('woocommerce_category_items_sold', __('Items sold', 'usin'),
				array(
					'group' => $this->category_group,
					'loader_class' => 'USIN_Woocommerce_Product_Items_Sold_Loader',
					'info' => sprintf(__('Number of items sold of selected category products in orders with status %s'), $sales_statuses)
				)),
			new USIN_Period_Report('woocommerce_category_sales_total', __('Items sold total', 'usin'),
				array(
					'group' => $this->category_group,
					'loader_class' => 'USIN_Woocommerce_Product_Sales_Total_Loader',
					'format' => 'float',
					'info' => sprintf(__('Total amount of items sold of selected category products in orders with status %s (does not reflect partial refunds)', 'usin'), $sales_statuses)
				)),
			new USIN_Standard_Report_With_Period_Filter('woocommerce_category_order_statuses', __('Order status', 'usin'),
				array('group' => $this->category_group, 'loader_class' => 'USIN_Woocommerce_Order_Statuses_Loader')),
			new USIN_Standard_Report_With_Period_Filter('woocommerce_category_ordered_products', __('Top ordered products', 'usin'),
				array('group' => $this->category_group, 'type'=>USIN_Report::BAR, 'loader_class' => 'USIN_Woocommerce_Ordered_Products_Loader')),
		);
	}

	public static function get_status_colors(){
		$colors = array(
			'wc-completed' => 'green',
			'wc-processing' => 'dark_purple',
			'wc-pending' => 'purple',
			'wc-on-hold' => 'blue',
			'wc-refunded' => 'yellow',
			'wc-failed' => 'pink',
			'wc-cancelled' => 'red',
		);
		return apply_filters('usin_reports_wc_status_colors', $colors);
	}
}