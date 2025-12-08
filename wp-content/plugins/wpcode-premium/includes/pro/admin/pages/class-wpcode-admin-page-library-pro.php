<?php
/**
 * Pro version of the library page.
 *
 * @package WPCode
 */

/**
 * Class WPCode_Admin_Page_Library_Pro.
 */
class WPCode_Admin_Page_Library_Pro extends WPCode_Admin_Page_Library {

	use WPCode_My_Library_Markup;

	/**
	 * Page-specific hooks.
	 *
	 * @return void
	 */
	public function page_hooks() {
		parent::page_hooks();
		add_action( 'admin_init', array( $this, 'maybe_add_from_my_cloud' ) );
		add_filter( 'wpcode_admin_js_data', array( $this, 'maybe_add_my_cloud_data' ) );
	}

	/**
	 * Get the data handler specific to the view.
	 *
	 * @return WPCode_Library|WPCode_My_Favorites|WPCode_My_Library
	 */
	public function get_data_handler() {
		if ( ! isset( $this->data_handler ) ) {
			switch ( $this->view ) {
				case 'my_library':
					$this->data_handler = wpcode()->my_library;
					break;
				case 'my_favorites':
					$this->data_handler = new WPCode_My_Favorites();
					break;
				default:
					$this->data_handler = wpcode()->library;
			}
		}

		return $this->data_handler;
	}

	/**
	 * Handler for adding snippets from the my_cloud snippets.
	 *
	 * @return void
	 */
	public function maybe_add_from_my_cloud() {
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'wpcode_add_from_cloud' ) ) {
			return;
		}
		$cloud_id = isset( $_GET['cloud_id'] ) ? sanitize_key( $_GET['cloud_id'] ) : 0;

		if ( empty( $cloud_id ) ) {
			return;
		}

		$snippet = $this->get_data_handler()->create_new_snippet( $cloud_id );

		if ( $snippet ) {
			$url = add_query_arg(
				array(
					'page'       => 'wpcode-snippet-manager',
					'snippet_id' => $snippet->get_id(),
				),
				$this->admin_url( 'admin.php' )
			);
		} else {
			$url = add_query_arg(
				array(
					'message' => 1,
				),
				remove_query_arg(
					array(
						'_wpnonce',
						'cloud_id',
					)
				)
			);
		}

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Item actions specific to this class.
	 *
	 * @param string|int $id The id of the element passed for generating action urls.
	 *
	 * @return void
	 */
	public function get_list_item_top_actions( $id ) {
		if ( 'my_library' !== $this->view ) {
			return;
		}
		?>
		<div class="wpcode-list-item-top-actions">
			<button
					type="button"
					class="wpcode-my-library-trash wpcode-button-just-icon"
					data-id="<?php echo esc_attr( $id ); ?>"
					title="<?php esc_attr_e( 'Delete snippet from library', 'wpcode-premium' ); ?>"
			>
				<?php wpcode_icon( 'trash', 13, 16, '0 0 13 16' ); ?>
			</button>
		</div>
		<?php
	}

	/**
	 * Markup for the "My Library" page.
	 *
	 * @return void
	 */
	public function output_view_my_library() {
		$this->get_my_library_markup();
		$this->library_preview_modal_content();
	}

	/**
	 * Override the view for the pro version to load favorites.
	 *
	 * @return void
	 */
	public function output_view_my_favorites() {

		$my_favorites = new WPCode_My_Favorites();
		if ( wpcode()->library_auth->has_auth() ) {
			$snippets = $my_favorites->get_data();

			if ( empty( $snippets['snippets'] ) ) {
				?>
				<div class="wpcode-alert wpcode-alert-success">
					<?php printf( '<h4>%s</h4>', esc_html__( 'You don\'t have any favorites yet, as you add snippets to your favorites list, they will show up here.', 'wpcode-premium' ) ); ?>
				</div>
				<?php
			} else {
				$this->get_library_markup( $snippets['categories'], $snippets['snippets'], 'get_my_cloud_snippet_item', 'favourites' );
				$this->library_preview_modal_content();
			}
			?>
			<?php
		} else {
			$this->blurred_placeholder_items();
			// Show message that you need to connect.
			echo WPCode_Admin_Page::get_upsell_box(
				__( 'Your site is not connected to the WPCode Library', 'wpcode-premium' ),
				'<p>' . __( 'Connect your WPCode account and access your favourite snippets in the plugin.', 'wpcode-premium' ) . '</p>',
				array(
					'text'  => __( 'Connect to the WPCode Library', 'wpcode-premium' ),
					'tag'   => 'button',
					'class' => 'wpcode-button wpcode-button-large wpcode-start-auth',
				)
			);
		}
	}
}
