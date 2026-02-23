<?php
/**
 * Class that handles conditional logic for scheduling snippets.
 *
 * @package WPCode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The WPCode_Conditional_Schedule class.
 */
class WPCode_Conditional_Schedule extends WPCode_Conditional_Type {

	/**
	 * The type unique name (slug).
	 *
	 * @var string
	 */
	public $name = 'schedule';

	/**
	 * The type category.
	 *
	 * @var string
	 */
	public $category = 'advanced';

	/**
	 * Set the translatable label.
	 *
	 * @return void
	 */
	protected function set_label() {
		$this->label = __( 'Schedule', 'wpcode-premium' );
	}

	/**
	 * Load evaluation-only options (without translations) for frontend use.
	 * This method is called when translations are not yet loaded to avoid WP 6.7 notices.
	 *
	 * @return void
	 */
	public function load_evaluation_options() {
		$this->options = array(
			'date_is' => array(
				'callback' => array( $this, 'get_current_date' ),
			),
			'time_is' => array(
				'callback' => array( $this, 'get_current_time' ),
			),
			'weekday' => array(
				'callback' => array( $this, 'get_weekday' ),
			),
			'time'    => array(
				'callback' => array( $this, 'get_time_of_day' ),
			),
		);
	}

	/**
	 * Set the type options for the admin mainly.
	 *
	 * @return void
	 */
	public function load_type_options() {
		$this->options = array(
			'date_is' => array(
				'label'       => __( 'Date', 'wpcode-premium' ),
				'description' => __( 'Check whether today is before or after a date.', 'wpcode-premium' ),
				'type'        => 'date',
				'callback'    => array( $this, 'get_current_date' ),
			),
			'time_is' => array(
				'label'       => __( 'Date & Time', 'wpcode-premium' ),
				'description' => __( 'Get more specific by also including a specific time.', 'wpcode-premium' ),
				'type'        => 'datetime',
				'callback'    => array( $this, 'get_current_time' ),
			),
			'weekday' => array(
				'label'       => __( 'Day of the Week', 'wpcode-premium' ),
				'description' => __( 'Load the snippet on specific days of the week.', 'wpcode-premium' ),
				'type'        => 'select',
				'options'     => array(
					array(
						'label' => __( 'Monday', 'wpcode-premium' ),
						'value' => '1',
					),
					array(
						'label' => __( 'Tuesday', 'wpcode-premium' ),
						'value' => '2',
					),
					array(
						'label' => __( 'Wednesday', 'wpcode-premium' ),
						'value' => '3',
					),
					array(
						'label' => __( 'Thursday', 'wpcode-premium' ),
						'value' => '4',
					),
					array(
						'label' => __( 'Friday', 'wpcode-premium' ),
						'value' => '5',
					),
					array(
						'label' => __( 'Saturday', 'wpcode-premium' ),
						'value' => '6',
					),
					array(
						'label' => __( 'Sunday', 'wpcode-premium' ),
						'value' => '7',
					),
				),
				'callback'    => array( $this, 'get_weekday' ),
				'multiple'    => true,
			),
			'time'    => array(
				'label'       => __( 'Current time', 'wpcode-premium' ),
				'description' => __( 'Check whether it\'s before or after a specific time', 'wpcode-premium' ),
				'type'        => 'time',
				'callback'    => array( $this, 'get_time_of_day' ),
			),
		);
		if ( is_admin() ) {
			if ( ! wpcode()->license->get() ) {
				foreach ( $this->options as $key => $value ) {
					$this->options[ $key ]['upgrade'] = array(
						'title'  => __( 'Scheduling rules are a Pro Feature', 'wpcode-premium' ),
						'text'   => __( 'Please add your license key in the Settings Panel to unlock all pro features.', 'wpcode-premium' ),
						'link'   => add_query_arg(
							array(
								'page' => 'wpcode-settings',
							),
							admin_url( 'admin.php' )
						),
						'button' => __( 'Add License Key Now', 'wpcode-premium' ),
					);
				}
			}
		}
	}

	/**
	 * Get the Device type
	 *
	 * @return string
	 */
	public function get_current_time() {
		$current_date_time = WPCode_Conditional_Schedule::current_datetime();

		return $current_date_time->format( 'Y-m-d H:i' );
	}

	/**
	 * Get the current day of the week.
	 *
	 * @return string
	 */
	public function get_weekday() {
		$current_date_time = WPCode_Conditional_Schedule::current_datetime();

		return $current_date_time->format( 'N' );
	}

	/**
	 * @return string
	 */
	public function get_time_of_day() {
		$current_date_time = WPCode_Conditional_Schedule::current_datetime();

		return $current_date_time->format( 'H:i' );
	}

	/**
	 * @return string
	 */
	public function get_current_date() {
		$current_date_time = WPCode_Conditional_Schedule::current_datetime();

		return $current_date_time->format( 'Y-m-d' );
	}

	/**
	 * Adds schedule-specific operators logic.
	 *
	 * @param mixed  $value1 This is the first value to compare with value 2.
	 * @param mixed  $value2 This is the 2nd value.
	 * @param string $operator This is the operator string.
	 *
	 * @return bool
	 */
	protected function get_relation_comparison( $value1, $value2, $operator ) {

		if ( ! is_array( $value2 ) ) {
			$value1 = $this->normalize_value( $value1 );
			$value2 = $this->normalize_value( $value2 );
		}
		switch ( $operator ) {
			case 'before':
				$result = $this->before( $value1, $value2 );
				break;
			case 'after':
				$result = $this->after( $value1, $value2 );
				break;
			case 'before-or':
				$result = $this->before_or_equal( $value1, $value2 );
				break;
			case 'after-or':
				$result = $this->after_or_equal( $value1, $value2 );
				break;
			default:
				$result = parent::get_relation_comparison( $value1, $value2, $operator );
				break;
		}

		return $result;

	}

	/**
	 * @param $value1
	 * @param $value2
	 *
	 * @return bool
	 */
	public function before( $value1, $value2 ) {
		return $value1 < $value2;
	}

	/**
	 * @param $value1
	 * @param $value2
	 *
	 * @return bool
	 */
	public function before_or_equal( $value1, $value2 ) {
		return $value1 <= $value2;
	}

	/**
	 * @param $value1
	 * @param $value2
	 *
	 * @return bool
	 */
	public function after( $value1, $value2 ) {
		return $value1 > $value2;
	}

	/**
	 * @param $value1
	 * @param $value2
	 *
	 * @return bool
	 */
	public function after_or_equal( $value1, $value2 ) {
		return $value1 >= $value2;
	}

	/**
	 * @param $value
	 *
	 * @return false|int
	 */
	public function normalize_value( $value ) {
		return strtotime( $value );
	}

	/**
	 * Retrieves the current time as an object using the site's timezone.
	 *
	 * @return DateTimeImmutable Date and time object.
	 * @since WP 5.3.0
	 *
	 */
	public static function current_datetime() {
		return new DateTimeImmutable( 'now', self::wp_timezone() );
	}

	/**
	 * In case we're running on WP < 5.3, we need to use this function to get the timezone.
	 *
	 * @return DateTimeZone
	 */
	public static function wp_timezone() {
		if ( function_exists( 'wp_timezone' ) ) {
			return wp_timezone();
		}

		return new DateTimeZone( self::wp_timezone_string() );
	}

	/**
	 * In case we're running on WP < 5.3, we need to use this function to get the timezone string.
	 *
	 * @return string.
	 */
	public static function wp_timezone_string() {
		return wpcode_wp_timezone_string();
	}
}

new WPCode_Conditional_Schedule();
