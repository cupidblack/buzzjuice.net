<?php

trait WPCode_My_Library_Markup {
	use WPCode_My_Library_Markup_Lite;

	/**
	 * Get the library markup.
	 *
	 */
	public function get_my_library_markup() {
		if ( wpcode()->library_auth->has_auth() ) {
			?>
			<?php
			$snippets = WPCode()->my_library->get_data();

			if ( empty( $snippets['snippets'] ) ) {
				?>
				<div class="wpcode-metabox">
					<div class="wpcode-alert wpcode-alert-success">
						<?php printf( '<h4>%s</h4>', esc_html__( 'Your library is currently empty, as you create and save snippets to your personal library, they will show up here.', 'wpcode-premium' ) ); ?>
					</div>
				</div>
				<?php
			} else {
				$this->get_library_markup( $snippets['categories'], $snippets['snippets'], 'get_my_cloud_snippet_item', 'mylibrary' );
			}
			?>
			<?php
		} else {
			$this->blurred_placeholder_items();
			// Show message that you need to connect.
			echo WPCode_Admin_Page::get_upsell_box(
				__( 'Your site is not connected to the WPCode Library', 'wpcode-premium' ),
				'<p>' . __( 'Connect your WPCode account and access your Library snippets in the plugin.', 'wpcode-premium' ) . '</p>',
				array(
					'text'  => __( 'Connect to the WPCode Library', 'wpcode-premium' ),
					'tag'   => 'button',
					'class' => 'wpcode-button wpcode-button-large wpcode-start-auth',
				)
			);
		}
	}

	/**
	 * Output specific for the "my cloud" items output.
	 *
	 * @param array  $snippet The snippet object.
	 * @param string $category The category of this snippet (for filtering).
	 *
	 * @return void
	 */
	public function get_my_cloud_snippet_item( $snippet = array(), $category = '*' ) {
		$title         = '';
		$url           = '';
		$description   = '';
		$button_text   = __( 'Use snippet', 'wpcode-premium' );
		$used_snippets = $this->get_data_handler()->get_used_library_snippets();
		$pill_text     = '';
		$snippet_id    = 0;
		if ( ! empty( $snippet ) ) {
			$url = add_query_arg(
				array(
					'page'   => 'wpcode-snippet-manager',
					'custom' => true,
				),
				$this->admin_url( 'admin.php' )
			);
			if ( 0 !== $snippet['cloud_id'] ) {

				if ( ! empty( $used_snippets[ $snippet['cloud_id'] ] ) ) {
					$snippet_id  = absint( $used_snippets[ $snippet['cloud_id'] ] );
					$url         = add_query_arg(
						array(
							'page'       => 'wpcode-snippet-manager',
							'snippet_id' => $snippet_id,
						),
						$this->admin_url( 'admin.php' )
					);
					$button_text = __( 'Edit snippet', 'wpcode-premium' );
					$pill_text   = __( 'Used', 'wpcode-premium' );
				} else {
					$url = wp_nonce_url(
						add_query_arg(
							array(
								'cloud_id' => $snippet['cloud_id'],
								'page'     => 'wpcode-library',
								'view'     => 'my_library',
							),
							$this->admin_url( 'admin.php' )
						),
						'wpcode_add_from_cloud'
					);
				}
			}
			$title       = $snippet['title'];
			$description = $snippet['note'];
		}
		if ( empty( $description ) ) {
			$description = $snippet['code'];
		}
		$description   = wp_trim_words( $description, 15 );
		$id            = $snippet['cloud_id'];
		$button_2_text = __( 'Preview', 'wpcode-premium' );
		$categories    = isset( $snippet['categories'] ) ? $snippet['categories'] : array();

		$button_1 = array(
			'url'        => $url,
			'text'       => $button_text,
			'attributes' => array(
				'data-snippet-id' => $snippet_id,
			),
		);
		$button_2 = array(
			'text'  => $button_2_text,
			'class' => 'wpcode-button-secondary wpcode-button wpcode-library-preview-button',
		);

		$this->get_list_item( $id, $title, $description, $button_1, $button_2, $categories, $pill_text, 'blue', $category );
	}

	/**
	 * Get the data handler.
	 *
	 * @return object
	 */
	public function get_data_handler() {
		return wpcode()->my_library;
	}

	/**
	 * Add my-cloud snippet data.
	 *
	 * @param array $data The data localised.
	 *
	 * @return array
	 */
	public function maybe_add_my_cloud_data( $data ) {
		if ( isset( $this->view ) && 'library' === $this->view ) {
			return $data;
		}

		$snippets = $this->get_data_handler()->get_data();

		if ( empty( $snippets['snippets'] ) ) {
			return $data;
		}

		foreach ( $snippets['snippets'] as $key => $snippet ) {
			$snippets['snippets'][ $key ]['library_id'] = $snippet['cloud_id'];

			$updated_date_time_string = sprintf(
			// Translators: Used for displaying the updated date, in the format Date at time, eg: August 15, 2022 at 10:32.
				__( '%1$s at %2$s', 'wpcode-premium' ),
				date_i18n( 'F d, Y', $snippet['updated'] ),
				date_i18n( 'g:i a', $snippet['updated'] )
			);

			$snippets['snippets'][ $key ]['updated_text'] = sprintf(
			// Translators: the date at which a cloud snippet has been updated.
				esc_html__( 'Updated on %s', 'wpcode-premium' ),
				$updated_date_time_string
			);
		}
		$data['library']['mysnippets'] = $snippets['snippets'];
		$data['cloud_edit_url']        = WPCode()->my_library->get_editor_url();
		$data['confirm_delete_title']  = __( 'Are you sure you want to delete this snippet?', 'wpcode-premium' );
		$data['confirm_delete_text']   = __( 'This will delete the snippet from your library, if the snippet is used locally that snippet will not be affected.', 'wpcode-premium' );
		$data['confirm_delete_button'] = __( 'Delete snippet', 'wpcode-premium' );

		return $data;
	}
}
