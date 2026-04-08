<?php
/**
 * Notification View: myCred Points
 */

defined( 'ABSPATH' ) || exit;

class LLMS_Notification_View_Mycred_Points extends LLMS_Abstract_Notification_View {

	/**
	 * Settings for basic notifications
	 *
	 * @var array
	 */
	protected $basic_options = array(
		/**
		 * Time in milliseconds to show a notification
		 * before automatically dismissing it
		 */
		'auto_dismiss' => 10000,
		/**
		 * Enables manual dismissal of notifications
		 */
		'dismissible'  => true,
	);

	/**
	 * Notification Trigger ID
	 *
	 * @var string
	 */
	public $trigger_id = 'mycred_points';

	/**
	 * Setup body content for output
	 *
	 * @return string
	 */
	protected function set_body() {
		ob_start();
		?>
		<p style="text-align: center;">{{point_type_image}}</p>
		<h2 style="text-align: center;"><strong>{{cred_f}}</strong></h2>
		<p style="text-align: center;">{{entry}}</p>
		<?php
		return ob_get_clean();
	}

	/**
	 * Setup footer content for output
	 *
	 * @return string
	 */
	protected function set_footer() {
		return '';
	}

	/**
	 * Setup notification icon for output
	 *
	 * @return string
	 */
	protected function set_icon() {
		return '';
	}

	/**
	 * Setup merge codes that can be used with the notification
	 *
	 * @return array
	 */
	protected function set_merge_codes() {
		return array(
			'{{plural}}'   => __('Point type plural name', 'mycred-toolkit'),
			'{{singular}}' => __('Point type singular name', 'mycred-toolkit'),
			'{{cred}}'     => __('Points amount', 'mycred-toolkit'),
			'{{cred_f}}'   => __('Points amount with prefix/ suffix', 'mycred-toolkit'),
			'{{entry}}'    => __('Log entry', 'mycred-toolkit'),
			'{{point_type_image}}' => __('Point type image', 'mycred-toolkit')
		);
	}

	/**
	 * Replace merge codes with actual values
	 *
	 * @param string $code The merge code to get merged data for.
	 * @return string
	 */
	protected function set_merge_data( $code ) {

		$entry_id = absint($this->notification->post_id);

		global $wpdb, $mycred_log_table;

		$entry = $wpdb->get_row( 
			$wpdb->prepare("SELECT * FROM $mycred_log_table WHERE id = %d LIMIT 1;", $entry_id) 
		);
		
		if (empty($entry)) {
return $code;
		}

		$mycred = mycred($entry->ctype);

		switch ($code) {

			case '{{plural}}':
				$code = $mycred->template_tags_general( '%plural%' );
				break;

			case '{{singular}}':
				$code  = $mycred->template_tags_general( '%singular%' );
				break;

			case '{{cred}}':
				$code = $mycred->template_tags_amount( '%cred%', $entry->creds );
				break;

			case '{{cred_f}}':
				$code = $mycred->template_tags_amount( '%cred_f%', $entry->creds );
				break;

			case '{{entry}}':
				$code = $mycred->template_tags_general( $entry->entry );
				break;

			case '{{point_type_image}}':
				if ( ! empty( $mycred->image_url ) ) {
					$code = sprintf(
						// Translators: %1$s is the name, %2$s is the currency symbol, and %3$s is the singular form of the currency.
						__( 'Enter the %1$s (%2$s) equivalent value of 1 %3$s based on your local currency', 'mycred-toolkit' ),
						$name,
						get_lifterlms_currency_symbol(),
						strtolower( $mycred->singular() )
					);              
				}
				break;
		}

		return $code;
	}

	/**
	 * Setup notification subject for output
	 *
	 * @return string
	 */
	protected function set_subject() {
		return '';
	}

	/**
	 * Setup notification title for output
	 *
	 * @return string
	 */
	protected function set_title() {
		return __('You\'ve earned Points!', 'mycred-toolkit');
	}

	/**
	 * Define field support for the view
	 *
	 * @return array
	 */
	protected function set_supported_fields() {
		return array(
			'basic' => array(
				'body'  => true,
				'title' => true,
				'icon'  => false,
			),
		);
	}
}
