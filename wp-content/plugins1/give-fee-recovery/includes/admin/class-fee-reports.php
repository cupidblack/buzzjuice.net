<?php
/**
 * Class Give_Fee_Reports.
 *
 * Define Fee Report int Give Report tab.
 *
 * @link       https://givewp.com
 * @since      1.0.0
 *
 * @package    Give_Fee_Reports
 */

if ( ! class_exists( 'Give_Fee_Reports' ) ) :
	/**
	 * Give_Fee_Reports.
	 *
	 * @since 1.0.0
	 */
	class Give_Fee_Reports extends Give_Settings_Page {

		/**
		 * Setting page id.
		 *
		 * @since  1.0.0
		 * @access protected
		 *
		 * @var   string
		 */
		protected $id = '';

		/**
		 * Setting page label.
		 *
		 * @since  1.0.0
		 * @access protected
		 *
		 * @var   string
		 */
		protected $label = '';

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id          = 'fee';
			$this->label       = __( 'Fees', 'give-fee-recovery' );
			$this->default_tab = 'give-fee-income';

			add_filter( 'give-reports_tabs_array', array( $this, 'add_settings_page' ), 20 );
			add_action( "give-reports_settings_{$this->id}_page", array( $this, 'output' ) );
			add_action( 'give_admin_field_report_fee_income', array( $this, 'display_report' ), 10, 2 );
			add_action( 'give_admin_field_report_fee_conversion', array(
				$this,
				'display_fee_conversion',
			), 10, 2 );

			// Do not use main form for this tab.
			if ( give_get_current_setting_tab() === $this->id ) {
				add_action( 'give-reports_open_form', '__return_empty_string' );
				add_action( 'give-reports_close_form', '__return_empty_string' );
			}

			parent::__construct();
		}

		/**
		 * Add this page to settings.
		 *
		 * @since  1.0.0
		 * @access public
		 *
		 * @param  array $pages Lst of pages.
		 *
		 * @return array
		 */
		public function add_settings_page( $pages ) {
			$pages[ $this->id ] = $this->label;

			return $pages;
		}

		/**
		 * Get settings array.
		 *
		 * @since  1.0.0
		 * @access public
		 *
		 * @return array
		 */
		public function get_settings() {
			// Hide save button.
			$GLOBALS['give_hide_save_button'] = true;

			$settings        = array();
			$current_section = give_get_current_setting_section();

			switch ( $current_section ) {
				case 'give-fee-income':
					$settings = array(
						// Section 1: Fee Income.
						array(
							'id'         => 'give_reports_fee_income',
							'type'       => 'title',
							'table_html' => false,
						),
						array(
							'id'   => 'give_fee_income',
							'name' => __( 'Fee income', 'give-fee-recovery' ),
							'type' => 'report_fee_income',
						),
						array(
							'id'         => 'give_reports_fee_income',
							'type'       => 'sectionend',
							'table_html' => false,
						),
					);
					break;

				case 'give-fee-conversion' :
					$settings = array(
						// Section 2: Fee Conversion.
						array(
							'id'         => 'give_reports_fee_conversion',
							'type'       => 'title',
							'table_html' => false,
						),
						array(
							'id'   => 'give_fee_conversion',
							'name' => __( 'Fee Conversion', 'give-fee-recovery' ),
							'type' => 'report_fee_conversion',
						),
						array(
							'id'         => 'give_reports_fee_conversion',
							'type'       => 'sectionend',
							'table_html' => false,
						),
					);
					break;
			} // End switch().

			/**
			 * Filter the settings.
			 *
			 * @since  1.1.0
			 *
			 * @param  array $settings
			 */
			$settings = apply_filters( 'give_fee_get_settings_' . $this->id, $settings );

			// Output.
			return $settings;
		}

		/**
		 * Get sections.
		 *
		 * @since  1.1.0
		 * @access public
		 *
		 * @return array
		 */
		public function get_sections() {
			$sections = array(
				'give-fee-income'     => __( 'Fee Income', 'give-fee-recovery' ),
				'give-fee-conversion' => __( 'Fee Conversion', 'give-fee-recovery' ),
			);

			return apply_filters( 'give_fee_get_sections_' . $this->id, $sections );
		}

		/**
		 * Output the settings.
		 *
		 * @since  1.0.0
		 * @access public
		 *
		 * @return void
		 */
		public function output() {
			$settings = $this->get_settings();

			Give_Admin_Settings::output_fields( $settings, 'give_settings' );
		}


		/**
		 * Get Fee by Date
		 *
		 * Helper function for reports
		 *
		 * @since      1.0.0
		 * @access     public
		 *
		 * @param null $day   Day value pass.
		 * @param null $month Month value pass.
		 * @param null $year  Year value pass.
		 * @param null $hour  Hour value pass.
		 *
		 * @return array
		 */
		public function get_fee_by_date( $day = null, $month = null, $year = null, $hour = null ) {

			$args = apply_filters( 'give_get_fee_by_date', array(
				'post_type'        => 'give_payment',
				'post_status'      => 'publish',
				'year'             => $year,
				'monthnum'         => $month,
				'fields'           => 'ids',
				'suppress_filters' => false,
			), $day, $month, $year );

			if ( ! empty( $day ) ) {
				$args['day'] = $day;
			}

			if ( ! empty( $hour ) ) {
				$args['hour'] = $hour;
			}

			$payments = get_posts( $args );

			$return                           = array();
			$return['number_of_donation']     = 0;
			$return['number_of_fee_donation'] = 0;
			$return['number_of_disabled_fee'] = 0;
			$return['number_of_rejected_fee'] = 0;
			$return['total_donation_amount']  = (float) 0.00;
			$return['total_donation_fee']     = (float) 0.00;

			if ( $payments ) {
				foreach ( $payments as $payment_id ) {

					$give_fee_amount = give_get_meta( $payment_id, '_give_fee_amount', true );
					$donation_amount = give_get_meta( $payment_id, '_give_fee_donation_amount', true );
					$payment_total   = give_get_meta( $payment_id, '_give_payment_total', true );
					$payment_total   = ! empty( $donation_amount ) ? $donation_amount : $payment_total;
					$give_fee_status = give_get_meta( $payment_id, '_give_fee_status', true );
					$give_fee_status = ! empty( $give_fee_status ) ? $give_fee_status : 'disabled';

					// Store count, Based on Give Fee status is rejected/disabled.
					if ( 'rejected' === $give_fee_status ) {
						// Store number of rejected fee.
						$return['number_of_rejected_fee'] += 1;
					} elseif ( 'disabled' === $give_fee_status ) {
						// Store number of disabled fee.
						$return['number_of_disabled_fee'] += 1;
					}

					// Store number of donation count.
					$return['number_of_donation'] += 1;
					// Store total donation amount.
					$return['total_donation_amount'] += (float) give_fee_number_format( $payment_total, true, $payment_id );

					// Check if Give fee is set.
					if ( $give_fee_amount ) {
						// Store number of fee donation.
						$return['number_of_fee_donation'] += 1;
						// Store total donation fee.
						$return['total_donation_fee'] += (float) give_fee_number_format( $give_fee_amount,true, $payment_id );

					}
				}
			}


			return $return;
		}

		/**
		 * Show Fee Income Report.
		 *
		 * @access      public
		 * @since       1.1.0
		 *
		 * @param array  $field       Array of Field passed.
		 * @param string $field_value Field value.
		 *
		 * @return      void
		 */
		public function display_report( $field, $field_value ) {

			if ( ! current_user_can( 'view_give_reports' ) ) {
				wp_die( __( 'You do not have permission to view this data.', 'give-fee-recovery' ), __( 'Error', 'give-fee-recovery' ), array( 'response' => 401 ) );
			}

			// Retrieve the queried dates.
			$dates = give_get_report_dates();

			// Determine graph options.
			switch ( $dates['range'] ) :
				case 'today' :
				case 'yesterday' :
					$day_by_day = true;
					break;
				case 'last_year' :
				case 'this_year' :
				case 'last_quarter' :
				case 'this_quarter' :
					$day_by_day = false;
					break;
				case 'other' :
					if ( $dates['m_end'] - $dates['m_start'] >= 2 || $dates['year_end'] > $dates['year'] && ( $dates['m_start'] !== '12' && $dates['m_end'] !== '1' ) ) {
						$day_by_day = false;
					} else {
						$day_by_day = true;
					}
					break;
				default:
					$day_by_day = true;
					break;
			endswitch;

			$total_donation  = 0;
			$fee_donation    = 0;
			$donation_amount = 0.00;
			$donation_fee    = 0.00;

			$total_donations = array();
			$donation_fees   = array();

			if ( 'today' === $dates['range'] || 'yesterday' === $dates['range'] ) {
				// Hour by hour.
				$hour  = 1;
				$month = $dates['m_start'];
				while ( $hour <= 23 ) :

					$fees = $this->get_fee_by_date( $dates['day'], $month, $dates['year'], $hour );

					$total_donation  += $fees['number_of_donation'];
					$fee_donation    += $fees['number_of_fee_donation'];
					$donation_amount += $fees['total_donation_amount'];
					$donation_fee    += $fees['total_donation_fee'];

					$date              = mktime( $hour, 0, 0, $month, $dates['day'], $dates['year'] ) * 1000;
					$total_donations[] = array( $date, $fees['total_donation_amount'] );
					$donation_fees[]   = array( $date, $fees['total_donation_fee'] );

					$hour ++;
				endwhile;

			} elseif ( 'this_week' === $dates['range'] || 'last_week' === $dates['range'] ) {

				// Day by day.
				$day     = $dates['day'];
				$day_end = $dates['day_end'];

				// If day end is greater than 31 then use 31.
				if ( $day_end > 31 ) {
					$day_end = 31;
				}

				$month = $dates['m_start'];
				while ( $day <= $day_end ) :

					$fees = $this->get_fee_by_date( $day, $month, $dates['year'] );

					$total_donation  += $fees['number_of_donation'];
					$fee_donation    += $fees['number_of_fee_donation'];
					$donation_amount += $fees['total_donation_amount'];
					$donation_fee    += $fees['total_donation_fee'];

					$date              = mktime( 0, 0, 0, $month, $day, $dates['year'] ) * 1000;
					$total_donations[] = array( $date, $fees['total_donation_amount'] );
					$donation_fees[]   = array( $date, $fees['total_donation_fee'] );
					$day ++;
				endwhile;

			} else {

				$y = $dates['year'];

				while ( $y <= $dates['year_end'] ) :

					$last_year = false;

					if ( $dates['year'] === $dates['year_end'] ) {
						$month_start = $dates['m_start'];
						$month_end   = $dates['m_end'];
						$last_year   = true;
					} elseif ( $y === $dates['year'] ) {
						$month_start = $dates['m_start'];
						$month_end   = 12;
					} elseif ( $y === $dates['year_end'] ) {
						$month_start = 1;
						$month_end   = $dates['m_end'];
					} else {
						$month_start = 1;
						$month_end   = 12;
					}

					$i = $month_start;
					while ( $i <= $month_end ) :
						if ( $day_by_day ) :
							if ( $i === $month_end ) {
								$num_of_days = $dates['day_end'];
							} else {
								$num_of_days = cal_days_in_month( CAL_GREGORIAN, $i, $y );
							}

							$d = $dates['day'];
							while ( $d <= $num_of_days ) :
								$fees = $this->get_fee_by_date( $d, $i, $y );

								$total_donation  += $fees['number_of_donation'];
								$fee_donation    += $fees['number_of_fee_donation'];
								$donation_amount += $fees['total_donation_amount'];
								$donation_fee    += $fees['total_donation_fee'];

								$date              = mktime( 0, 0, 0, $i, $d, $y ) * 1000;
								$total_donations[] = array( $date, $fees['total_donation_amount'] );
								$donation_fees[]   = array( $date, $fees['total_donation_fee'] );
								$d ++;

							endwhile;

						else :

							$fees = $this->get_fee_by_date( null, $i, $y );

							$total_donation  += $fees['number_of_donation'];
							$fee_donation    += $fees['number_of_fee_donation'];
							$donation_amount += $fees['total_donation_amount'];
							$donation_fee    += $fees['total_donation_fee'];

							if ( $i === $month_end && $last_year ) {
								$num_of_days = cal_days_in_month( CAL_GREGORIAN, $i, $y );
							} else {
								$num_of_days = 1;
							}

							$date              = mktime( 0, 0, 0, $i, $num_of_days, $y ) * 1000;
							$total_donations[] = array( $date, $fees['total_donation_amount'] );
							$donation_fees[]   = array( $date, $fees['total_donation_fee'] );

						endif;
						$i ++;
					endwhile;
					$y ++;
				endwhile;
			}// End if().

			$data = array(
				__( 'Total income for period', 'give-fee-recovery' )         => $total_donations,
				__( 'Total fees collected for period', 'give-fee-recovery' ) => $donation_fees,
			);

			ob_start();
			?>

			<div class="tablenav top reports-table-nav">
				<h3 class="alignleft reports-earnings-title">
					<span><?php _e( 'Fee Income', 'give-fee-recovery' ); ?></span></h3>
			</div>

			<div id="give-dashboard-widgets-wrap" style="padding-top: 0;">
				<div class="metabox-holder" style="padding-top: 0;">
					<div class="postbox">

						<div class="inside">
							<?php
							do_action( 'give_fee_income_reports_graph_before' );
							give_reports_graph_controls();
							$graph = new Give_Graph( $data, array( 'dataType' => array( 'amount', 'amount' ), ) );
							$graph->set( 'x_mode', 'time' );
							$graph->set( 'multiple_y_axes', true );
							$graph->display();
							do_action( 'give_fee_income_reports_graph_after' ); ?>
						</div>

					</div>
				</div>
				<table class="widefat reports-table alignleft" style="max-width:450px">
					<tbody>
					<tr>
						<td class="row-title">
							<label for="tablecell"><?php _e( 'Total income for period:', 'give-fee-recovery' ); ?></label>
						</td>
						<td><?php echo give_currency_filter( give_fee_number_format( $donation_amount ) ); ?></td>
					</tr>
					<tr class="alternate">
						<td class="row-title">
							<label for="tablecell"><?php _e( 'Total donations for period: ', 'give-fee-recovery' ); ?></label>
						</td>
						<td><?php echo $total_donation; ?></td>
					</tr>
					<tr>
						<td class="row-title">
							<label for="tablecell"><?php _e( 'Total fees collected for period: ', 'give-fee-recovery' ); ?></label>
						</td>
						<td><?php echo give_currency_filter( give_fee_number_format( $donation_fee ) ); ?></td>
					</tr>
					<tr class="alternate">
						<td class="row-title">
							<label for="tablecell"><?php _e( 'Percentage of fees to donations: ', 'give-fee-recovery' ); ?></label>
						</td>
						<td>
							<?php
							$fee_percentage = 0;
							if ( ! empty( $donation_fee ) && ! empty( $donation_amount ) ) {
								// Calculate Fee percentage on donation amount.
								$fee_percentage = ( $donation_fee * 100 ) / $donation_amount;
							}
							echo give_format_decimal( $fee_percentage ) . '%'; ?>
						</td>
					</tr>
					<?php do_action( 'give_fee_income_reports_graph_additional_stats' ); ?>
					</tbody>
				</table>
			</div>

			<?php
			$section      = isset( $_GET['section'] ) ? sanitize_text_field( $_GET['section'] ) : 'give-fee-income';
			$section_html = '';
			$section_html .= '<form id="give-graphs-filter" method="get"><input type="hidden" value="' . $section . '" name="section"/>';

			// get output buffer contents and end our own buffer.
			$output = ob_get_contents();
			$output = preg_replace( '/<form .*?>/', $section_html, $output );
			ob_end_clean();

			echo $output;
		}

		/**
		 * Show Fee Conversion Report.
		 *
		 * @access      public
		 * @since       1.1.0
		 *
		 * @param array  $field       Array of Field passed.
		 * @param string $field_value Field value.
		 *
		 * @return      void
		 */
		public function display_fee_conversion( $field, $field_value ) {

			if ( ! current_user_can( 'view_give_reports' ) ) {
				wp_die( __( 'You do not have permission to view this data.', 'give-fee-recovery' ), __( 'Error', 'give-fee-recovery' ), array( 'response' => 401 ) );
			}

			// Retrieve the queried dates.
			$dates = give_get_report_dates();

			// Determine graph options.
			switch ( $dates['range'] ) :
				case 'today' :
				case 'yesterday' :
					$day_by_day = true;
					break;
				case 'last_year' :
				case 'this_year' :
				case 'last_quarter' :
				case 'this_quarter' :
					$day_by_day = false;
					break;
				case 'other' :
					if ( $dates['m_end'] - $dates['m_start'] >= 2 || $dates['year_end'] > $dates['year'] && ( $dates['m_start'] !== '12' && $dates['m_end'] !== '1' ) ) {
						$day_by_day = false;
					} else {
						$day_by_day = true;
					}
					break;
				default:
					$day_by_day = true;
					break;
			endswitch;

			$total_donation = 0;
			$fee_donation   = 0;
			$disabled_fee   = 0;

			$donations     = array();
			$fee_donations = array();

			if ( 'today' === $dates['range'] || 'yesterday' === $dates['range'] ) {
				// Hour by hour.
				$hour  = 1;
				$month = $dates['m_start'];
				while ( $hour <= 23 ) :

					$fees = $this->get_fee_by_date( $dates['day'], $month, $dates['year'], $hour );

					$total_donation += $fees['number_of_donation'];
					$fee_donation   += $fees['number_of_fee_donation'];
					$disabled_fee   += $fees['number_of_disabled_fee'];

					$date            = mktime( $hour, 0, 0, $month, $dates['day'], $dates['year'] ) * 1000;
					$donations[]     = array( $date, $fees['number_of_donation'] );
					$fee_donations[] = array( $date, $fees['number_of_fee_donation'] );

					$hour ++;
				endwhile;

			} elseif ( 'this_week' === $dates['range'] || 'last_week' === $dates['range'] ) {

				// Day by day.
				$day     = $dates['day'];
				$day_end = $dates['day_end'];
				$month   = $dates['m_start'];
				while ( $day <= $day_end ) :

					$fees = $this->get_fee_by_date( $day, $month, $dates['year'] );

					$total_donation += $fees['number_of_donation'];
					$fee_donation   += $fees['number_of_fee_donation'];
					$disabled_fee   += $fees['number_of_disabled_fee'];

					$date            = mktime( 0, 0, 0, $month, $day, $dates['year'] ) * 1000;
					$donations[]     = array( $date, $fees['number_of_donation'] );
					$fee_donations[] = array( $date, $fees['number_of_fee_donation'] );
					$day ++;
				endwhile;

			} else {

				$y = $dates['year'];

				while ( $y <= $dates['year_end'] ) :

					$last_year = false;

					if ( $dates['year'] === $dates['year_end'] ) {
						$month_start = $dates['m_start'];
						$month_end   = $dates['m_end'];
						$last_year   = true;
					} elseif ( $y === $dates['year'] ) {
						$month_start = $dates['m_start'];
						$month_end   = 12;
					} elseif ( $y === $dates['year_end'] ) {
						$month_start = 1;
						$month_end   = $dates['m_end'];
					} else {
						$month_start = 1;
						$month_end   = 12;
					}

					$i = $month_start;
					while ( $i <= $month_end ) :
						if ( $day_by_day ) :
							if ( $i === $month_end ) {
								$num_of_days = $dates['day_end'];
							} else {
								$num_of_days = cal_days_in_month( CAL_GREGORIAN, $i, $y );
							}

							$d = $dates['day'];
							while ( $d <= $num_of_days ) :
								$fees = $this->get_fee_by_date( $d, $i, $y );

								$total_donation += $fees['number_of_donation'];
								$fee_donation   += $fees['number_of_fee_donation'];
								$disabled_fee   += $fees['number_of_disabled_fee'];

								$date            = mktime( 0, 0, 0, $i, $d, $y ) * 1000;
								$donations[]     = array( $date, $fees['number_of_donation'] );
								$fee_donations[] = array( $date, $fees['number_of_fee_donation'] );
								$d ++;

							endwhile;

						else :

							$fees = $this->get_fee_by_date( null, $i, $y );

							$total_donation += $fees['number_of_donation'];
							$fee_donation   += $fees['number_of_fee_donation'];
							$disabled_fee   += $fees['number_of_disabled_fee'];

							if ( $i === $month_end && $last_year ) {
								$num_of_days = cal_days_in_month( CAL_GREGORIAN, $i, $y );
							} else {
								$num_of_days = 1;
							}

							$date            = mktime( 0, 0, 0, $i, $num_of_days, $y ) * 1000;
							$donations[]     = array( $date, $fees['number_of_donation'] );
							$fee_donations[] = array( $date, $fees['number_of_fee_donation'] );

						endif;
						$i ++;
					endwhile;
					$y ++;
				endwhile;
			}

			$data = array(
				__( 'Total number of donations for period', 'give-fee-recovery' )      => $donations,
				__( 'Total number of fees collected for period', 'give-fee-recovery' ) => $fee_donations,
			);

			ob_start();
			?>
			<div class="tablenav top reports-table-nav">
				<h3 class="alignleft reports-earnings-title">
					<span><?php _e( 'Fee Conversion', 'give-fee-recovery' ); ?></span></h3>
			</div>

			<div id="give-dashboard-widgets-wrap" style="padding-top: 0;">
				<div class="metabox-holder" style="padding-top: 0;">
					<div class="postbox">

						<div class="inside">
							<?php
							do_action( 'give_fee_conversion_reports_graph_before' );

							give_reports_graph_controls();
							$graph = new Give_Graph( $data, array( 'dataType' => array( 'count', 'count' ), ) );
							$graph->set( 'x_mode', 'time' );
							$graph->set( 'multiple_y_axes', true );
							$graph->display();

							do_action( 'give_fee_conversion_reports_graph_after' ); ?>
						</div>

					</div>
				</div>
				<table class="widefat reports-table alignleft" style="max-width:450px">
					<tbody>
					<tr>
						<td class="row-title">
							<label for="tablecell"><?php _e( 'Total number of donations for period: ', 'give-fee-recovery' ); ?></label>
						</td>
						<td><?php echo $total_donation; ?></td>
					</tr>
					<tr class="alternate">
						<td class="row-title">
							<label for="tablecell"><?php _e( 'Total number of fees collected for period: ', 'give-fee-recovery' ); ?></label>
						</td>
						<td><?php echo $fee_donation; ?></td>
					</tr>
					<tr>
						<td class="row-title">
							<label for="tablecell"><?php _e( 'Donations given without fee recovery enabled: ', 'give-fee-recovery' ); ?></label>
						</td>
						<td><?php echo $disabled_fee; ?></td>
					</tr>

					<tr class="alternate">
						<td class="row-title">
							<label for="tablecell"><?php _e( 'Fee recovery opt-in conversion rate: ', 'give-fee-recovery' ); ?></label>
						</td>
						<td>
							<?php
							$conversion_rate = 0;
							if ( ! empty( $fee_donation ) && ! empty( $total_donation ) ) {
								// Calculate conversion rate: ( Number of fee donation / Number of total donation ) * 100.
								$conversion_rate = ( $fee_donation / $total_donation ) * 100;
							}
							echo give_format_decimal( $conversion_rate ) . '%';
							?>
						</td>
					</tr>
					<?php do_action( 'give_fee_conversion_reports_graph_additional_stats' ); ?>
					</tbody>
				</table>
			</div>

			<?php
			$section      = isset( $_GET['section'] ) ? sanitize_text_field( $_GET['section'] ) : 'give-fee-income';
			$section_html = '';
			$section_html .= '<form id="give-graphs-filter" method="get"><input type="hidden" value="' . $section . '" name="section"/>';

			// get output buffer contents and end our own buffer.
			$output = ob_get_contents();
			$output = preg_replace( '/<form .*?>/', $section_html, $output );
			ob_end_clean();

			echo $output;
		}
	}

endif;

return new Give_Fee_Reports();
