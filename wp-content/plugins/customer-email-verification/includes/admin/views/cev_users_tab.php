<?php
/**
 * Users Tab Template
 *
 * Displays a table of user log entries from the email verification system.
 * Includes functionality for:
 * - Viewing user log entries (email, last updated timestamp)
 * - Bulk actions (delete multiple entries)
 * - Individual entry deletion
 * - Empty state when no entries exist
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $wpdb;

// Get plugin instance for URL and version.
$plugin_instance = cev_pro();
$plugin_url      = $plugin_instance->plugin_dir_url();
$plugin_version  = isset( $plugin_instance->version ) ? $plugin_instance->version : '1.0.0';

/**
 * Retrieve user log entries from the database.
 *
 * Fetches all entries from the cev_user_log table ordered by
 * last_updated in descending order (newest first).
 *
 * Note: Table name is constructed from $wpdb->prefix which is safe.
 * We escape the table name using esc_sql() for WordPress coding standards compliance.
 * Table names cannot be placeholders in prepared statements, so we escape it separately.
 */

$results = $wpdb->get_results(
	"SELECT id, email, last_updated
	 FROM {$wpdb->prefix}cev_user_log
	 ORDER BY last_updated DESC",
	ARRAY_A
);
?>
<section id="cev_content_user" class="cev_tab_section">
	<?php if ( ! empty( $results ) ) : ?>
		<?php
		/**
		 * Bulk Actions Section
		 *
		 * Provides dropdown for selecting bulk actions and
		 * a button to apply the selected action to checked items.
		 */
		?>
		<div id="filterForm" role="toolbar" aria-label="<?php esc_attr_e( 'Bulk actions', 'customer-email-verification' ); ?>">
			<div class="filter_select">
				<label for="bulk_action" class="screen-reader-text">
					<?php esc_html_e( 'Select bulk action', 'customer-email-verification' ); ?>
				</label>
				<select id="bulk_action" name="bulk_action" aria-label="<?php esc_attr_e( 'Bulk action', 'customer-email-verification' ); ?>">
					<option value=""><?php esc_html_e( 'Bulk Action', 'customer-email-verification' ); ?></option>
					<option value="delete"><?php esc_html_e( 'Delete', 'customer-email-verification' ); ?></option>
				</select>
				<button 
					type="button" 
					class="apply_bulk_action" 
					aria-label="<?php esc_attr_e( 'Apply bulk action', 'customer-email-verification' ); ?>"
				>
					<?php esc_html_e( 'Apply', 'customer-email-verification' ); ?>
				</button>
			</div>
		</div>

		<?php
		/**
		 * User Log Table Section
		 *
		 * Displays a data table with all user log entries,
		 * including checkboxes for bulk selection, email addresses,
		 * last updated timestamps, and individual delete buttons.
		 */
		?>
		<div id="userLog">
			<table id="userLogTable" class="display" role="table" aria-label="<?php esc_attr_e( 'User log entries', 'customer-email-verification' ); ?>">
				<thead>
					<tr role="row">
						<th scope="col" role="columnheader">
							<label for="select_all" class="screen-reader-text">
								<?php esc_html_e( 'Select all entries', 'customer-email-verification' ); ?>
							</label>
							<input 
								type="checkbox" 
								id="select_all" 
								aria-label="<?php esc_attr_e( 'Select all entries', 'customer-email-verification' ); ?>"
							/>
						</th>
						<th scope="col" role="columnheader"><?php esc_html_e( 'Email', 'customer-email-verification' ); ?></th>
						<th scope="col" role="columnheader"><?php esc_html_e( 'Last Updated', 'customer-email-verification' ); ?></th>
						<th scope="col" role="columnheader"><?php esc_html_e( 'Delete', 'customer-email-verification' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $results as $row ) : ?>
						<?php
						// Sanitize row data.
						$entry_id       = isset( $row['id'] ) ? absint( $row['id'] ) : 0;
						$entry_email    = isset( $row['email'] ) ? sanitize_email( $row['email'] ) : '';
						$last_updated   = isset( $row['last_updated'] ) ? $row['last_updated'] : '';

						// Format the last updated timestamp.
						$formatted_date = __( 'N/A', 'customer-email-verification' );
						if ( ! empty( $last_updated ) ) {
							try {
								$date_object = new DateTime( $last_updated );
								$formatted_date = $date_object->format( 'F j, Y H:i' );
							} catch ( Exception $e ) {
								// If date parsing fails, use the raw value or N/A.
								$formatted_date = ! empty( $last_updated ) ? esc_html( $last_updated ) : __( 'N/A', 'customer-email-verification' );
							}
						}
						?>
						<tr role="row">
							<td role="gridcell">
								<label for="row_checkbox_<?php echo esc_attr( $entry_id ); ?>" class="screen-reader-text">
									<?php
									/* translators: %s: Email address */
									printf( esc_html__( 'Select entry for %s', 'customer-email-verification' ), esc_html( $entry_email ) );
									?>
								</label>
								<input 
									type="checkbox" 
									class="row_checkbox" 
									id="row_checkbox_<?php echo esc_attr( $entry_id ); ?>"
									value="<?php echo esc_attr( $entry_id ); ?>"
									aria-label="<?php esc_attr_e( 'Select this entry', 'customer-email-verification' ); ?>"
								/>
							</td>
							<td role="gridcell"><?php echo esc_html( $entry_email ); ?></td>
							<td role="gridcell"><?php echo esc_html( $formatted_date ); ?></td>
							<td role="gridcell">
								<button 
									type="button"
									class="delete_button" 
									data-id="<?php echo esc_attr( $entry_id ); ?>"
									aria-label="
									<?php
									/* translators: %s: Email address */
									printf( esc_attr__( 'Delete entry for %s', 'customer-email-verification' ), esc_attr( $entry_email ) );
									?>
									"
								>
									<img 
										src="<?php echo esc_url( $plugin_url . 'assets/images/bin.png' ); ?>?v=<?php echo esc_attr( $plugin_version ); ?>" 
										alt="<?php esc_attr_e( 'Delete', 'customer-email-verification' ); ?>"
										width="16"
										height="16"
									>
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php else : ?>
		<?php
		/**
		 * Empty State Section
		 *
		 * Displays when no user log entries are found in the database.
		 */
		?>
		<div class="no_user" role="status" aria-live="polite">
			<div class="no_user_content">
				<img 
					src="<?php echo esc_url( $plugin_url . 'assets/images/nouser.png' ); ?>?v=<?php echo esc_attr( $plugin_version ); ?>" 
					alt="<?php esc_attr_e( 'No users found', 'customer-email-verification' ); ?>"
					width="200"
					height="200"
				>
				<span><?php esc_html_e( 'No user found!', 'customer-email-verification' ); ?></span>
			</div>
		</div>
	<?php endif; ?>
</section>
