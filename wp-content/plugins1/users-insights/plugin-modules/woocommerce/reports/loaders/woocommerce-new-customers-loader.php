<?php

class USIN_Woocommerce_New_Customers_Loader extends USIN_Period_Report_Loader {

	public function call(){
		$result = parent::call();

		$new_customers = $this->find_dataset('new', $result);
		$total_customers = $this->find_dataset('total', $result);
		$returning_customers = new USIN_Dataset(array(), __('Returning customers', 'usin'), 'yellow');

		foreach($new_customers->items as $new_customers_item){
			$label = $new_customers_item->label;
			$total_customers_for_label = array_values(wp_filter_object_list($total_customers->items, array('label' => $label)));

			if(sizeof($total_customers_for_label) != 1){
				throw new Exception("Expected 1 item for date $label, found " . sizeof($total_customers_for_label));
			}

			$returning_customers_for_label = $total_customers_for_label[0]->total - $new_customers_item->total;
			$returning_customers->items[]= $this->data_item($label, $returning_customers_for_label);
		}

		$new_customers->name = __('New customers', 'usin');
		return array($new_customers, $returning_customers);
	}

	protected function load_data(){
		$new_customers = $this->get_new_customers();
		$total_customers = $this->get_total_customers();

		return array(
			$this->dataset($new_customers, 'new', 'green'),
			$this->dataset($total_customers, 'total', 'blue')
		);
	}

	protected function find_dataset($name, $datasets){
		$filtered = array_values(wp_filter_object_list($datasets, array('name' => $name)));
		return $filtered[0];
	}

	protected function get_new_customers(){
		global $wpdb;

		$subquery = USIN_Woocommerce::custom_order_tables_enabled() ? $this->get_new_customers_subquery() : $this->get_new_customers_legacy_subquery();

		$query = "SELECT COUNT(*) AS $this->total_col, first_order AS $this->label_col" .
			" FROM ($subquery) AS order_dates GROUP BY {$this->get_group_by()}";

		return $wpdb->get_results($query);
	}

	protected function get_new_customers_subquery(){
		global $wpdb;

		$date_select = USIN_Query_Helper::get_gmt_offset_date_select('date_created_gmt');

		return $wpdb->prepare("SELECT MIN($date_select) AS first_order FROM {$wpdb->prefix}wc_orders" .
			" WHERE type = %s GROUP BY billing_email" .
			" HAVING first_order >= %s AND first_order <= %s", USIN_Woocommerce::ORDER_POST_TYPE, $this->get_period_start(), $this->get_period_end());
	}

	protected function get_new_customers_legacy_subquery(){
		global $wpdb;

		return $wpdb->prepare("SELECT MIN(post_date) AS first_order FROM $wpdb->posts AS posts" .
			" INNER JOIN $wpdb->postmeta AS emails ON posts.ID = emails.post_id AND emails.meta_key = '_billing_email'" .
			" WHERE posts.post_type = %s GROUP BY emails.meta_value" .
			" HAVING first_order >= %s AND first_order <= %s", USIN_Woocommerce::ORDER_POST_TYPE, $this->get_period_start(), $this->get_period_end());
	}

	protected function get_total_customers(){
		global $wpdb;

		$query = USIN_Woocommerce::custom_order_tables_enabled() ? $this->get_total_customers_query() : $this->get_total_customers_legacy_query();

		return $wpdb->get_results($query);
	}

	protected function get_total_customers_query(){
		global $wpdb;

		$date_select = USIN_Query_Helper::get_gmt_offset_date_select('date_created_gmt');

		return $wpdb->prepare("SELECT COUNT(DISTINCT(billing_email)) AS total, $date_select AS label" .
			" FROM {$wpdb->prefix}wc_orders" .
			" WHERE type = %s AND $date_select >= %s AND $date_select <= %s" .
			" GROUP BY {$this->get_group_by()}",
			USIN_Woocommerce::ORDER_POST_TYPE, $this->get_period_start(), $this->get_period_end());
	}

	protected function get_total_customers_legacy_query(){
		global $wpdb;

		return $wpdb->prepare("SELECT COUNT(DISTINCT emails.meta_value) AS total, post_date as label FROM $wpdb->posts AS posts" .
			" INNER JOIN $wpdb->postmeta AS emails ON posts.ID = emails.post_id AND emails.meta_key = '_billing_email'" .
			" WHERE posts.post_type = %s AND post_date >= %s AND post_date <= %s" .
			" GROUP BY {$this->get_group_by()}",
			USIN_Woocommerce::ORDER_POST_TYPE, $this->get_period_start(), $this->get_period_end());
	}
}