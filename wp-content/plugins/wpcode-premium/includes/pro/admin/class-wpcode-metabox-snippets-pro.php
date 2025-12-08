<?php
/**
 * Class used for the lite-specific metabox.
 *
 * @package WPCode
 */

class WPCode_Metabox_Snippets_Pro extends WPCode_Metabox_Snippets {

	use WPCode_Revisions_Display;

	/**
	 * The editor instance for this class.
	 *
	 * @var WPCode_Code_Editor
	 */
	private $editor;

	/**
	 * Extend parent hooks method.
	 *
	 * @return void
	 */
	public function hooks() {
		parent::hooks();
		add_action( 'save_post', array( $this, 'save' ) );

		add_action( 'wp_ajax_wpcode_load_revisions_list', array( $this, 'ajax_load_revisions_list' ) );
	}

	/**
	 * Display the metabox only if the headers footers mode is disabled.
	 *
	 * @param string $post_type The post type.
	 *
	 * @return void
	 */
	public function register_metabox( $post_type ) {
		if ( wpcode()->settings->get_option( 'headers_footers_mode' ) ) {
			return;
		}

		parent::register_metabox( $post_type );
	}

	/**
	 * Override the header tab content to make it specific to this class.
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @return void
	 */
	public function output_tab_header( $post ) {
		$this->form_for_scripts(
			$post,
			__( 'Header', 'wpcode-premium' )
		);
	}

	/**
	 * Override the footer tab content to make it specific to this class.
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @return void
	 */
	public function output_tab_footer( $post ) {
		$this->form_for_scripts(
			$post,
			__( 'Footer', 'wpcode-premium' ),
			'footer'
		);
	}

	/**
	 * Override the Body tab content to make it specific to this class.
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @return void
	 */
	public function output_tab_body( $post ) {
		$this->form_for_scripts(
			$post,
			__( 'Body', 'wpcode-premium' ),
			'body'
		);
	}

	/**
	 * Custom code tab content
	 *
	 * @param WP_Post $post The post object (currently being edited).
	 *
	 * @return void
	 */
	public function output_tab_code( $post ) {
		$snippets        = get_post_meta( $post->ID, '_wpcode_page_snippets', true );
		$add_snippet_url = add_query_arg(
			array(
				'page'   => 'wpcode-snippet-manager',
				'custom' => 1,
			),
			admin_url( 'admin.php' )
		);
		?>
		<p>
			<?php esc_html_e( 'Choose the snippets you want to run on this page. Please note: only active snippets will be executed.', 'wpcode-premium' ); ?>
		</p>
		<div class="wpcode-metabox-snippets wpcode-snippet-chooser-closed">
			<div id="wpcode-snippet-chooser">
				<h3>
					<?php esc_html_e( 'Select snippets', 'wpcode-premium' ); ?>
					<button class="wpcode-button-just-icon wpcode-drawer-toggle" id="wpcode-close-drawer">
						<?php wpcode_icon( 'close' ); ?>
					</button>
				</h3>
				<div class="wpcode-snippets-search">
					<input type="text" id="wpcode-search-snippets" class="wpcode-input-text" placeholder="<?php esc_attr_e( 'Search snippets', 'wpcode-premium' ); ?>"/>
					<span class="wpcode-loading-spinner" id="wpcode-chooser-spinner"></span>
				</div>
				<div class="wpcode-chooser-fixed-height">
					<div id="wpcode-choose-snippets"></div>
					<div class="wpcode-choose-actions">
						<button type="button" class="wpcode-button wpcode-button-secondary" id="wpcode-metabox-load-more"><?php esc_html_e( 'Load more snippets', 'wpcode-premium' ); ?></button>
					</div>
				</div>
			</div>
			<div class="wpcode-picked-snippets-area">
				<h3>
					<button class="wpcode-button wpcode-drawer-toggle" id="wpcode-add-snippet-toggle">
						<?php esc_html_e( '+ Choose Snippet', 'wpcode-premium' ); ?>
					</button>
					<a class="wpcode-button wpcode-button-secondary" href="<?php echo esc_url( $add_snippet_url ); ?>" target="_blank">
						<?php esc_html_e( 'Add New Snippet', 'wpcode-premium' ); ?>
					</a>
				</h3>
				<div id="wpcode-picked-snippets">
					<?php
					if ( ! empty( $snippets ) && is_array( $snippets ) ) {
						foreach ( $snippets as $snippet ) {
							$snippet = wp_parse_args(
								$snippet,
								array(
									'snippet_id' => 0,
									'location'   => '',
									'number'     => 1,
								)
							);
							echo wpcode_get_snippet_item_selected( $snippet['snippet_id'], $snippet['location'], $snippet['number'] );
						}
					}
					?>
					<h3 id="wpcode-no-snippets-selected"><?php esc_html_e( 'No snippets selected.', 'wpcode-premium' ); ?></h3>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Generic form for header/footer scripts in the metabox.
	 *
	 * @param WP_Post $post The post object.
	 * @param string  $label The label to use in the input labels.
	 * @param string  $type The key to use for ids and such.
	 *
	 * @return void
	 */
	public function form_for_scripts( $post, $label = 'Header', $type = 'header' ) {
		$type_scripts = wpcode()->page_scripts->get_scripts( $type, $post->ID );
		?>
		<p>
			<?php
			printf(
			// Translators: placeholder for the name of the section (header or footer).
				esc_html__( 'Add scripts below to the %s section of this page.', 'wpcode-premium' ),
				esc_html( $label )
			);
			?>
		</p>
		<p>
			<label>
				<input type="checkbox" name="wpcode-metabox-disable-global-<?php echo esc_attr( $type ); ?>" <?php checked( $type_scripts['disable_global'] ); ?>/>
				<?php
				printf(
				// Translators: placeholder for the name of the section (header or footer).
					esc_html__( 'Disable global %s scripts on this page', 'wpcode-premium' ),
					esc_html( $label )
				);
				?>
			</label>
		</p>
		<div class="wpcode-input-row">
			<label for="wpcode-<?php echo esc_attr( $type ); ?>-any-device">
				<?php
				printf(
				// Translators: placeholder for the name of the section (header or footer).
					esc_html__( '%s - any device type', 'wpcode-premium' ),
					esc_html( $label )
				);
				?>
			</label>
			<?php wpcode()->smart_tags->smart_tags_picker( "wpcode-$type-any-device" ); ?>
			<textarea name="wpcode-<?php echo esc_attr( $type ); ?>-any-device" id="wpcode-<?php echo esc_attr( $type ); ?>-any-device"><?php echo esc_textarea( $type_scripts['any'] ); ?></textarea>
		</div>
		<div class="wpcode-input-row">
			<label for="wpcode-<?php echo esc_attr( $type ); ?>-desktop-only">
				<?php
				printf(
				// Translators: placeholder for the name of the section (header or footer).
					esc_html__( '%s - desktop only', 'wpcode-premium' ),
					esc_html( $label )
				);
				?>
			</label>
			<?php wpcode()->smart_tags->smart_tags_picker( "wpcode-$type-desktop-only" ); ?>
			<textarea name="wpcode-<?php echo esc_attr( $type ); ?>-desktop-only" id="wpcode-<?php echo esc_attr( $type ); ?>-desktop-only"><?php echo esc_textarea( $type_scripts['desktop'] ); ?></textarea>
		</div>
		<div class="wpcode-input-row">
			<label for="wpcode-<?php echo esc_attr( $type ); ?>-mobile-only">
				<?php
				printf(
				// Translators: placeholder for the name of the section (header or footer).
					esc_html__( '%s - mobile only', 'wpcode-premium' ),
					esc_html( $label )
				);
				?>
			</label>
			<?php wpcode()->smart_tags->smart_tags_picker( "wpcode-$type-mobile-only" ); ?>
			<textarea name="wpcode-<?php echo esc_attr( $type ); ?>-mobile-only" id="wpcode-<?php echo esc_attr( $type ); ?>-mobile-only"><?php echo esc_textarea( $type_scripts['mobile'] ); ?></textarea>
		</div>
		<?php

		$this->editor->register_editor( "wpcode-{$type}-any-device" );
		$this->editor->register_editor( "wpcode-{$type}-desktop-only" );
		$this->editor->register_editor( "wpcode-{$type}-mobile-only" );
	}

	/**
	 * Save the metabox values.
	 *
	 * @param int $post_id The id of the post being saved.
	 *
	 * @return void
	 */
	public function save( $post_id ) {

		if ( ! isset( $_POST['wpcode_metabox_nonce'] ) || defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_key( $_POST['wpcode_metabox_nonce'] ), 'wpcode_metabox_save' ) ) {
			return;
		}

		if ( ! current_user_can( 'wpcode_edit_html_snippets' ) ) {
			// Any code added here gets automatically activated so they need proper permissions.
			return;
		}

		/**
		 * Use this hook to run any code before the page scripts data is updated.
		 */
		do_action( 'wpcode_before_update_page_scripts', $post_id );
		$updated = array();

		$types = array(
			'header',
			'footer',
			'body',
		);

		foreach ( $types as $type ) {
			$disable_global = isset( $_POST["wpcode-metabox-disable-global-{$type}"] );
			$any_device     = ! empty( $_POST["wpcode-{$type}-any-device"] ) ? $_POST["wpcode-{$type}-any-device"] : '';
			$desktop_only   = ! empty( $_POST["wpcode-{$type}-desktop-only"] ) ? $_POST["wpcode-{$type}-desktop-only"] : '';
			$mobile_only    = ! empty( $_POST["wpcode-{$type}-mobile-only"] ) ? $_POST["wpcode-{$type}-mobile-only"] : '';

			$scripts = array(
				'disable_global' => $disable_global,
				'any'            => $any_device,
				'desktop'        => $desktop_only,
				'mobile'         => $mobile_only,
			);

			$meta_key = "_wpcode_{$type}_scripts";

			update_post_meta( $post_id, $meta_key, $scripts );

			$updated[ $meta_key ] = $scripts;
		}

		// Let's save custom snippets for this page.
		$snippet_ids = array();

		if ( isset( $_POST['wpcode_auto_insert_override'] ) && is_array( $_POST['wpcode_auto_insert_override'] ) ) {
			$snippet_locations = $_POST['wpcode_auto_insert_override']; // phpcs:ignore
			foreach ( $snippet_locations as $snippet_id => $snippet_location ) {
				$insert_number = 1;
				if ( isset( $_POST['wpcode_auto_insert_number_override'][ $snippet_id ] ) ) {
					$insert_number = absint( $_POST['wpcode_auto_insert_number_override'][ $snippet_id ] );
				}
				$snippet_ids[] = array(
					'snippet_id' => absint( $snippet_id ),
					'location'   => sanitize_text_field( wp_unslash( $snippet_location ) ),
					'number'     => $insert_number,
				);
			}
		}

		$updated['_wpcode_page_snippets'] = $snippet_ids;

		update_post_meta( $post_id, '_wpcode_page_snippets', $snippet_ids );

		/**
		 * Use this hook to run any code after the page scripts data has been updated.
		 *
		 * @param int   $post_id The id of the post for which we updated the values.
		 * @param array $updated The updated values.
		 */
		do_action( 'wpcode_after_update_page_scripts', $post_id, $updated );

	}

	/**
	 * Code to run at the beginning of the metabox.
	 *
	 * @return void
	 */
	public function metabox_start() {
		$this->editor = new WPCode_Code_Editor();
		wp_nonce_field( 'wpcode_metabox_save', 'wpcode_metabox_nonce' );
	}

	/**
	 * After all the textareas have been loaded init the editor scripts.
	 *
	 * @return void
	 */
	public function metabox_end() {
		$this->editor->init_editor();
	}

	/**
	 * Show a list of revisions available for Page Scripts data.
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @return void
	 */
	public function output_tab_revisions( $post ) {
		$post_id   = $post->ID;
		$revisions = wpcode()->revisions->get_snippet_revisions( $post_id );

		printf(
			'<p>%s</p>',
			esc_html__( 'As you make changes to your page scripts and save, you will get a list of previous versions with all the changes made in each revision. You can compare revisions to the current version or see changes as they have been saved by going through each revision. Any of the revisions can then be restored as needed without interfering with your post/page.', 'wpcode-premium' )
		);

		echo '<div class="wpcode-revisions-list-area"><div class="wpcode-loading-spinner"></div></div>';
	}

	/**
	 * Load the revisions list for the page scripts with Ajax so we can refresh when Gutenberg saves.
	 *
	 * @return void
	 */
	public function ajax_load_revisions_list() {
		check_ajax_referer( 'wpcode_admin_global' );

		if ( ! current_user_can( 'wpcode_edit_html_snippets' ) || ! isset( $_POST['post_id'] ) ) {
			return;
		}
		$post_id = absint( $_POST['post_id'] );

		$revisions = wpcode()->revisions->get_snippet_revisions( $post_id );

		if ( empty( $revisions ) ) {
			$post      = get_post( $post_id );
			$revisions = array(
				(object) array(
					'id'        => 0,
					'created'   => $post->post_modified,
					'author_id' => $post->post_author,
				),
			);
		}

		wp_send_json_success(
			array(
				'revisions' => $this->revisions_list_render( $revisions ),
			)
		);
	}
}
