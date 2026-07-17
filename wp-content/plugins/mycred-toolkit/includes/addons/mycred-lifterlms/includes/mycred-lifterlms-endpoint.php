<?php
if ( ! defined( 'ABSPATH' ) ) {
exit;
}

if ( ! class_exists( 'myCRED_LifterLMS_Endpoint' ) ) :
	class myCRED_LifterLMS_Endpoint {

		public function __construct() {
			add_filter('llms_get_student_dashboard_tabs', array( $this, 'add_endpoint' ), 100, 1);
			add_filter('lifterlms_student_dashboard_title', array( $this, 'dashboard_title_my_balance' ), 100, 1);
		}

		/**
		 * Register the My Points (my-points) endpoint
		 * Renders before the Sign Out link
		 *
		 * @param $endpoints
		 *
		 * @return array
		 */
		public function add_endpoint( $endpoints ) {

			$endpoint = array(
				'title'    => __('My Points', 'mycred-toolkit'),
				'endpoint' => 'my-points',
				'nav_item' => false,
				'content'  => array( $this, 'render_my_points_endpoint' ),
			);

			$new_endpoints = $this->array_insert_after('orders', $endpoints, 'my-points', $endpoint);

			return $new_endpoints;
		}

		/**
		 * Inserts a new key/value after the key in the array.
		 *
		 * @param $needle string
		 * @param $haystack array
		 * @param $new_key string
		 * @param $new_value string|array
		 *
		 * @return array
		 */
		private function array_insert_after( $needle, $haystack, $new_key, $new_value ) {

			if (array_key_exists($needle, $haystack)) {

				$new_array = array();

				foreach ($haystack as $key => $value) {

					$new_array[ $key ] = $value;

					if ($key === $needle) {
						$new_array[ $new_key ] = $new_value;
					}
				}

				return $new_array;
			}

			return $haystack;
		}

		/**
		 * Render the Log table for the current user
		 */
		public function render_my_points_endpoint() {

			if (shortcode_exists('mycred_history')) {
				echo do_shortcode('[mycred_history number="50" show_nav="0" order="DESC" user_id="current"/]');
			}
		}

		/**
		 * Append the user's current balance to the My Points title
		 *
		 * @param $html_title
		 *
		 * @return string
		 */
		public function dashboard_title_my_balance( $html_title ) {

			$data = LLMS_Student_Dashboard::get_current_tab();

			if (array_key_exists('endpoint', $data) && 'my-points' === $data['endpoint'] && shortcode_exists('mycred_my_balance')) {
				return sprintf('<h2 class="llms-sd-title">%s: <small class="llms-mycred-my-balance">Balance %s %s</small></h2>',
					$data['title'],
					do_shortcode('[mycred_my_balance balance_el="span" wrapper="0"/]'),
					strtolower(mycred_get_point_type_name(MYCRED_DEFAULT_TYPE_KEY, false))
				);
			}

			// Return regular title
			return $html_title;
		}
	}
endif;

new myCRED_LifterLMS_Endpoint();
