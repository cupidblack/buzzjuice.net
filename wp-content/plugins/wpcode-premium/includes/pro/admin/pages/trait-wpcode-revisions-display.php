<?php
/**
 * Trait for displaying code revisions in the admin with extra data for the pro version.
 *
 * @package WPCode
 */

/**
 * Trait WPCode_Revisions_Display.
 */
trait WPCode_Revisions_Display {
	use WPCode_Revisions_Display_Lite;

	/**
	 * Output a list of available code revisions.
	 *
	 * @return string
	 */
	public function code_revisions_list() {
		if ( ! wpcode()->license->get() ) {
			return $this->code_revisions_list_with_notice(
				esc_html__( 'Code Revisions is a Pro Feature', 'wpcode-premium' ),
				sprintf(
					'<p>%s</p>',
					esc_html__( 'Please add your license key in the WPCode settings panel to unlock all Pro features.', 'wpcode-premium' )
				),
				array(
					'text' => esc_html__( 'Add License Key', 'wpcode-premium' ),
					'url'  => admin_url( 'admin.php?page=wpcode-settings' ),
				),
				array(
					'text' => esc_html__( 'Go to your WPCode Account', 'wpcode-premium' ),
					'url'  => wpcode_utm_url( 'https://library.wpcode.com/account/downloads/', 'snippet-editor', 'revisions', 'go-to-account' ),
				)
			);
		}
		$revisions = isset( $this->snippet_id ) ? wpcode()->revisions->get_snippet_revisions( $this->snippet_id ) : array();

		return $this->revisions_list_render( $revisions );
	}

	/**
	 * Render a list of revisions for comparison.
	 *
	 * @param array $revisions The revisions to render.
	 *
	 * @return string
	 */
	public function revisions_list_render( $revisions ) {
		$current_version_text = '<span>' . __( 'Current Version', 'wpcode-premium' ) . '</span>';

		if ( ! $revisions || 1 === count( $revisions ) ) {
			$author  = isset( $this->snippet ) ? $this->snippet->get_snippet_author() : get_current_user_id();
			$updated = esc_html__( 'Not saved', 'wpcode-premium' );
			if ( 1 === count( $revisions ) ) {
				$modified_time = strtotime( $revisions[0]->created );
				$author        = $revisions[0]->author_id;
				$updated       = sprintf(
				// Translators: time since the revision has been updated.
					esc_html__( 'Updated %s ago', 'wpcode-premium' ),
					human_time_diff( $modified_time )
				);
			}

			$list[] = $this->get_revision_item(
				$author,
				$updated,
				array(
					$current_version_text,
				)
			);
		} else {
			$list       = array();
			$list_extra = array();
			$datef      = _x( 'F j, Y @ H:i:s', 'revision date format' );
			$first      = true;
			$count      = 0;

			foreach ( $revisions as $revision ) {
				$count ++;
				$modified_time = strtotime( $revision->created );
				// Let's take into account the timezone.
				$updated = sprintf(
				// Translators: time since the revision has been updated.
					esc_html__( 'Updated %s ago', 'wpcode-premium' ),
					human_time_diff( $modified_time )
				);
				if ( time() - $modified_time > 15 * DAY_IN_SECONDS ) {
					$updated = sprintf(
					// Translators: date when revision was updated.
						esc_html__( 'Updated on %s', 'wpcode-premium' ),
						date_i18n( $datef, strtotime( $revision->created ) + get_option( 'gmt_offset' ) * HOUR_IN_SECONDS )
					);
				}
				if ( $first ) {
					$compare = $current_version_text;
				} else {
					$compare = sprintf(
						'<a href="%1$s">%2$s</a>',
						esc_url( $this->get_compare_revision_url( $revision->revision_id ) ),
						esc_html__( 'Compare', 'wpcode-premium' )
					);
				}
				$view = sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_url( $this->get_revision_url( $revision->revision_id ) ),
					get_wpcode_icon( 'eye', 16, 11, '0 0 16 11' )
				);

				$list_item = $this->get_revision_item( $revision->author_id, $updated, array( $compare, $view ) );

				if ( $count > 10 ) {
					$list_extra[] = $list_item;
				} else {
					$list[] = $list_item;
				}
				$first = false;
			}
		}

		$html = '<div class="wpcode-revisions-list-area">';

		$html .= sprintf(
			'<ul class="wpcode-revisions-list">%s</ul>',
			implode( '', $list )
		);

		if ( ! empty( $list_extra ) ) {
			$list_extra_count = count( $list_extra );
			$button_text      = sprintf(
			// Translators: The placeholder gets replaced with the extra number of revisions available.
				esc_html( _n( '%d Other Revision', '%d Other Revisions', $list_extra_count, 'wpcode-premium' ) ),
				$list_extra_count
			);

			$html .= sprintf(
				'<ul class="wpcode-revisions-list wpcode-revisions-list-extra wpcode-revisions-list-collapsed">%s</ul>',
				implode( '', $list_extra )
			);
			$html .= sprintf(
				'<button type="button" class="wpcode-button wpcode-button-secondary wpcode-button-icon" id="wpcode-show-all-snippets">%1$s %2$s</button>',
				get_wpcode_icon( 'rewind', 16, 14 ),
				$button_text
			);
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Get the url to view revision compare screen.
	 *
	 * @param int $revision_id The revision id to grab the URL for.
	 *
	 * @return string
	 */
	public function get_compare_revision_url( $revision_id ) {
		return add_query_arg(
			array(
				'page'     => 'wpcode-revisions',
				'revision' => $revision_id,
				'compare'  => '',
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Get the url to view a revision in the admin.
	 *
	 * @param int $revision_id The revision id to grab the URL for.
	 *
	 * @return string
	 */
	public function get_revision_url( $revision_id ) {
		return add_query_arg(
			array(
				'page'     => 'wpcode-revisions',
				'revision' => $revision_id,
			),
			admin_url( 'admin.php' )
		);
	}
}
