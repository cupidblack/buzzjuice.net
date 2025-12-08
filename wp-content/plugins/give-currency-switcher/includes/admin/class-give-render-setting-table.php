<?php
/**
 * Give Currency Switcher Exchange rate table.
 *
 * @package    Give_Currency_Switcher
 * @copyright  Copyright (c) 2016, GiveWP
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Include the WP_List_Table file.
 */
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Class Give_Exchange_Rate_Table
 *
 * @since 1.0
 */
class Give_Render_Setting_Table extends WP_List_Table {

	/**
	 * Table columns
	 *
	 * @since  1.0
	 * @access private
	 * @var array
	 */
	private $table_cols = array();

	/**
	 *Store the currencies row data.
	 *
	 * @access private
	 * @var array
	 * @since  1.0
	 */
	private $table_data = array();

	/**
	 * Table classes.
	 *
	 * @access private
	 * @since  1.0
	 * @var array
	 */
	private $table_classes = array();

	/**
	 * Table type.
	 *
	 * @since 1.0
	 * @var string $table_type
	 */
	private $table_type;

	/**
	 * Give_Exchange_Rate_Table constructor.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array  $cols          Exchange rate columns.
	 * @param array  $table_data    Table data columns.
	 * @param string $type          CS Field Table type.
	 * @param array  $table_classes Table Classes.
	 */
	public function __construct( $cols, $table_data, $type = '', $table_classes = array() ) {
		parent::__construct();

		$this->table_cols    = $cols;
		$this->table_data    = $table_data;
		$this->table_classes = $table_classes;

		$this->table_type = $type;

	}

	/**
	 * Get the columns.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	function get_columns() {
		return $this->table_cols;
	}

	/**
	 * This function renders most of the columns in the list table.
	 *
	 * @access public
	 * @since  1.0
	 *
	 * @param array  $exchange_rate_data Contains all the data of the Exchange Rate data.
	 * @param string $column             The name of the column.
	 *
	 * @return string Column Name
	 */
	public function column_default( $exchange_rate_data, $column ) {

		// If it is for exchange rate field.
		if ( 'cs_exchange_rates' === $this->table_type ) {

			switch ( $column ) {
				case 'currency' :
					echo $exchange_rate_data['currency']['value'];
					break;
				case 'exchange_rates' :

					$col_data = $exchange_rate_data['exchange_rates'];
					?>
					<input
						type="text"
						name="<?php echo esc_attr( $col_data['name'] ); ?>"
						value="<?php echo esc_attr( $col_data['value'] ); ?>"
						class="<?php echo esc_attr( $col_data['class'] ); ?>"
						placeholder="<?php echo esc_attr( $col_data['placeholder'] ); ?>"
					/>
					<?php
					break;
				case 'set_manually' :
					$col_data = $exchange_rate_data['set_manually'];
					?>
					<input
						type="checkbox"
						name="<?php echo esc_attr( $col_data['name'] ); ?>"
						value="1"
						class="<?php echo esc_attr( $col_data['class'] ); ?>"
						<?php checked( $col_data['value'], '1', true ); ?>
					/>
					<?php
					break;
				case 'number_decimal' :
					$col_data = $exchange_rate_data['number_decimal'];
					?>
					<input
							type="text"
							name="<?php echo esc_attr( $col_data['name'] ); ?>"
							value="<?php echo esc_attr( $col_data['value'] ); ?>"
							class="<?php echo esc_attr( $col_data['class'] ); ?>"
					/>
					<?php
					break;
				case 'rate_markup' :
					$col_data = $exchange_rate_data['rate_markup'];
					?>
					<input
							type="text"
							name="<?php echo esc_attr( $col_data['name'] ); ?>"
							value="<?php echo esc_attr( $col_data['value'] ); ?>"
							class="<?php echo esc_attr( $col_data['class'] ); ?>"
					/>
					<?php
					break;
				default:
					// $value = isset( $item[ $column_name ] ) ? $item[ $column_name ] : '';
					break;
			}// End switch().


		} else if ( 'cs_payment_gateway' === $this->table_type ) {

			$col_data = $exchange_rate_data;

			switch ( $column ) {
				case 'currency_key':
					echo $col_data['currency_label'] . ' [' . $col_data['currency_key'] . ']';
					break;
				case 'enabled_gateway':
					?>
					<select
						id="cs_payment_gateway-<?php echo esc_attr( strtolower( $col_data['currency_key'] ) ); ?>"
						name="<?php echo esc_attr( $col_data['currency_field_name'] ); ?>[<?php echo esc_attr( $col_data['currency_key'] ); ?>][]"
						data-placeholder="<?php echo __( 'Select payment gateway', 'give-currency-switcher' ); ?>"
						class="cs-chosen-field"
						multiple
					>
						<option value=""></option>
						<?php
						if ( ! empty( $col_data['currency_gateways'] ) ) {
							foreach ( $col_data['currency_gateways'] as $gateway_slug => $gateway ) {
								?>
								<option
									value="<?php echo esc_attr( $gateway_slug ); ?>"
									<?php ( isset( $col_data['currency_saved_gateways'] ) ) ? selected( in_array( $gateway_slug, $col_data['currency_saved_gateways'], true ), 1, true ) : ''; ?>>
									<?php echo esc_html( $gateway['admin_label'] ); ?>
								</option>
								<?php
							}
						} ?>
					</select>
					<?php
					break;
				default:
					break;
			}
		} else {

			// Allow third party to render their own fields.
			do_action( 'currency_switcher_table_field_' . $this->table_type, $column, $this->table_data );
		}
	}

	/**
	 * Setup the final data for the table
	 *
	 * @access public
	 * @since  1.0.0
	 *
	 * @uses   Give_Donor_Reports_Table::get_columns()
	 * @uses   WP_List_Table::get_sortable_columns()
	 *
	 * @return void
	 */
	public function prepare_items() {

		$columns  = $this->get_columns();
		$hidden   = array(); // No hidden columns.
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		// Get the currency data.
		$this->items = $this->table_data;
	}

	/**
	 * Generates content for a single row of the table
	 *
	 * @since  3.1.0
	 * @access public
	 *
	 * @param object $item The current item
	 */
	public function single_row( $item ) {

		$currency_key = '';
		if ( 'cs_exchange_rates' === $this->table_type ) {
			$currency_key = $item['currency']['currency_key'];
		} else if ( 'cs_payment_gateway' === $this->table_type ) {
			$currency_key = $item['currency_key'];
		}

		// Get the currency key.
		$currency_key = apply_filters( 'cs_table_' . $this->table_type . '_currency_key', $currency_key );
		?>
	<tr class="exchange-rate-row" data-tabletype="<?php echo esc_attr( $this->table_type ); ?>" data-currency="<?php echo $currency_key; ?>">
		<?php $this->single_row_columns( $item ); ?>
		</tr><?php
	}

	/**
	 * Generate the table navigation above or below the table
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @param string $which
	 *
	 * @return false
	 */
	public function display_tablenav( $which ) {
		return false;
	}

	/**
	 * Message to be displayed when there are no items
	 *
	 * @since  1.0
	 * @access public
	 */
	public function no_items() {

		// Default
		$message = __( 'No items found.', 'give-currency-switcher' );

		if ( 'cs_exchange_rates' === $this->table_type ) {
			$message = __( 'Only the base Give currency has been enabled, therefore there are no Exchange Rates to be configured.', 'give-currency-switcher' );
		} else if ( 'cs_payment_gateway' === $this->table_type ) {
			$message = __( 'Only base currency is activated, Please enable some currencies in order to set the payment support.', 'give-currency-switcher' );
		}

		echo apply_filters( 'cs_table_' . $this->table_type . '_no_item_message', $message );
	}

	/**
	 * Display the table
	 *
	 * @since 1.1
	 */
	public function display() {
		$singular = $this->_args['singular'];
		$this->display_tablenav( 'top' );
		$this->screen->render_screen_reader_content( 'heading_list' );
		?>
		<table class="wp-list-table widefat striped">
			<thead>
			<tr>
				<?php $this->print_column_headers(); ?>
			</tr>
			</thead>

			<tbody id="the-list"<?php
			if ( $singular ) {
				echo " data-wp-lists='list:$singular'";
			} ?>>
			<?php $this->display_rows_or_placeholder(); ?>
			</tbody>

			<tfoot>
			<tr>
				<?php $this->print_column_headers( false ); ?>
			</tr>
			</tfoot>

		</table>
		<?php
		$this->display_tablenav( 'bottom' );
	}
}
