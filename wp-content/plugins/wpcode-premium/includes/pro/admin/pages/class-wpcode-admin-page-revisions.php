<?php
/**
 * Class for the revision compare page.
 *
 * @package WPCode
 */

/**
 * Class WPCode_Admin_Page_Revisions.
 */
class WPCode_Admin_Page_Revisions extends WPCode_Admin_Page {

	/**
	 * If the snippet id is not set, redirect to the list of snippets.
	 *
	 * @var int The snippet id.
	 */
	public $snippet_id;
	/**
	 * Main revision id to display when the page is first loaded.
	 *
	 * @var int
	 */
	public $revision_id;
	/**
	 * Should the compare view be the default?
	 *
	 * @var bool
	 */
	public $compare = false;
	/**
	 * The page slug.
	 *
	 * @var string
	 */
	public $page_slug = 'wpcode-revisions';

	/**
	 * The page title.
	 *
	 * @var string
	 */
	public $page_title = '';

	/**
	 * What are we comparing?
	 *
	 * @var string
	 */
	public $compare_title = '';

	/**
	 * The URL to go back to the thing we're seeing revisions for.
	 *
	 * @var string
	 */
	public $back_url = '';

	/**
	 * The snippet for which revisions are loaded.
	 *
	 * @var WPCode_Snippet
	 */
	private $snippet;

	/**
	 * If this is a revision of the Header & Footer.
	 *
	 * @var bool
	 */
	protected $is_global = false;

	/**
	 * If this is a revision of page scripts.
	 *
	 * @var bool
	 */
	protected $is_page_scripts = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->page_title = __( 'Revisions', 'wpcode-premium' );
		$this->menu_title = '';
		parent::__construct();
		add_action( 'admin_head', array( $this, 'hide_menu' ) );
	}

	/**
	 * Page-specific hooks.
	 *
	 * @return void
	 */
	public function page_hooks() {
		add_action( 'admin_init', array( $this, 'capture_id_or_redirect' ) );
		add_action( 'admin_init', array( $this, 'maybe_restore_revision' ), 15 );
		add_filter( 'admin_body_class', array( $this, 'add_wpcode_classname' ) );
		add_filter( 'submenu_file', array( $this, 'change_current_menu' ) );

	}

	/**
	 * Remove the revisions page from the admin menu.
	 */
	public function hide_menu() {
		remove_submenu_page( 'wpcode', $this->page_slug );
	}

	/**
	 * Look for a valid snippet id or redirect to the list of snippets.
	 *
	 * @return void
	 */
	public function capture_id_or_redirect() {
		$id = ! empty( $_GET['revision'] ) ? absint( $_GET['revision'] ) : 0;// phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$parent_id     = wpcode()->revisions->get_revision_parent_id( $id );
		$this->compare = isset( $_GET['compare'] );// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 0 === $parent_id ) {
			$this->handle_header_footer_revision( $id );

			return;
		}
		if ( wpcode_get_post_type() !== get_post_type( $parent_id ) ) {
			// Let's check if it's a post we actually have revisions for.
			$this->handle_page_script_revision( $id, $parent_id );

			return;
		}

		if ( ! current_user_can( 'edit_post', $parent_id ) ) {
			$id = 0;
		}

		if ( 0 === $id || false === $parent_id ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page' => 'wpcode',
					),
					$this->admin_url( 'admin.php' )
				)
			);
			exit;
		}

		$this->snippet_id    = $parent_id;
		$this->revision_id   = $id;
		$this->snippet       = wpcode_get_snippet( $this->snippet_id );
		$this->compare_title = $this->snippet->get_title();
		$this->back_url      = add_query_arg(
			array(
				'page'       => 'wpcode-snippet-manager',
				'snippet_id' => $this->snippet_id,
			),
			$this->admin_url( 'admin.php' )
		);
	}

	/**
	 * Setup for a revision of the Header & Footer.
	 *
	 * @param int $revision_id The revision id.
	 *
	 * @return void
	 */
	public function handle_header_footer_revision( $revision_id ) {
		$this->is_global   = true;
		$this->revision_id = $revision_id;
		$this->snippet_id  = 0;

		$this->compare_title = __( 'Global Header & Footer', 'insert-headers-and-footers' );
		$this->back_url      = add_query_arg( 'page', 'wpcode-headers-footers', $this->admin_url( 'admin.php' ) );
	}

	/**
	 * Let's try to set up the revision for a page script.
	 *
	 * @param int $id The revision id.
	 * @param int $post_id The id of the post for which we're viewing page scripts revisions.
	 *
	 * @return void
	 */
	public function handle_page_script_revision( $id, $post_id ) {
		if ( ! current_user_can( 'edit_post', $post_id ) || false === $post_id ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page' => 'wpcode',
					),
					$this->admin_url( 'admin.php' )
				)
			);
			exit;
		}

		$this->is_page_scripts = true;
		$this->revision_id     = $id;
		$this->snippet_id      = $post_id;

		$this->compare_title = get_the_title( $post_id );
		$this->back_url      = add_query_arg( 'wpcode-show', 'revisions', get_edit_post_link( $post_id ) );
	}

	/**
	 * Listen for the restore action and restore a revision on the snippet.
	 *
	 * @return void
	 */
	public function maybe_restore_revision() {
		// Check params and nonce.
		if ( ! isset( $_GET['restore'] ) || ! isset( $_GET['_wpnonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'wpcode-restore-revision' ) ) {
			wp_die( esc_html__( 'The link you followed has expired.', 'wpcode-premium' ) );
		}

		$revision_to_restore = absint( $_GET['restore'] );
		$revision            = wpcode()->revisions->get_revision_data( $revision_to_restore );
		if ( empty( $revision ) ) {
			wp_die( esc_html__( 'Invalid revision id.', 'wpcode-premium' ) );
		}
		// Let's determine the revision type.
		$parent = wpcode()->revisions->get_revision_parent_id( $revision_to_restore );
		if ( 0 === $parent ) {
			$this->handle_header_footer_restore( $revision_to_restore, $revision );

			return;
		}
		$post_type = get_post_type( $parent );
		if ( wpcode_get_post_type() !== $post_type ) {
			$this->handle_page_script_restore( $revision_to_restore, $revision, $parent );

			return;
		}

		$revision['code'] = wp_slash( $revision['code'] );

		$snippet = wpcode_get_snippet( $revision );
		$snippet->save();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'       => 'wpcode-snippet-manager',
					'snippet_id' => $snippet->get_id(),
				),
				$this->admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handle the restore of a revision of the Header & Footer.
	 *
	 * @param $revision_to_restore
	 * @param $revision
	 *
	 * @return void
	 */
	public function handle_header_footer_restore( $revision_to_restore, $revision ) {
		$allowed_keys = array(
			'ihaf_insert_header',
			'ihaf_insert_footer',
			'ihaf_insert_body',
		);

		foreach ( $revision as $key => $value ) {
			if ( ! in_array( $key, $allowed_keys, true ) ) {
				continue;
			}

			update_option( $key, $value );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page' => 'wpcode-headers-footers',
				),
				$this->admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handle the restore of a revision of a page script.
	 *
	 * @param     $revision_to_restore
	 * @param     $revision
	 * @param int $post_id The post id.
	 *
	 * @return void
	 */
	public function handle_page_script_restore( $revision_to_restore, $revision, $post_id ) {
		$allowed_keys = array(
			'_wpcode_header_scripts',
			'_wpcode_footer_scripts',
			'_wpcode_body_scripts',
			'_wpcode_page_snippets',
		);
		$updated_data = array();

		foreach ( $revision as $key => $value ) {
			if ( ! in_array( $key, $allowed_keys, true ) ) {
				continue;
			}
			$updated_data[ $key ] = $value;

			update_post_meta( $post_id, $key, $value );
		}

		wpcode()->revisions->create_revision( $post_id, $updated_data );

		wp_safe_redirect(
			add_query_arg(
				array(
					'wpcode-show' => 'header',
				),
				get_edit_post_link( $post_id, 'edit' )
			)
		);
		exit;
	}

	/**
	 * Add the admin-body class as it gets removed when we remove the submenu item.
	 *
	 * @param string $classes The admin body classes.
	 *
	 * @return string
	 */
	public function add_wpcode_classname( $classes ) {
		$classes .= ' wpcode-admin-page';

		if ( ! empty( wpcode()->settings->get_option( 'dark_mode' ) ) ) {
			$classes .= ' wpcode-dark-mode';
		}

		return $classes;
	}

	/**
	 * Change the active submenu for the revisions page.
	 *
	 * @param null|string $submenu_file The submenu file.
	 *
	 * @return null|string
	 */
	public function change_current_menu( $submenu_file ) {
		return 'wpcode';
	}

	/**
	 * The bottom of the header part.
	 *
	 * @return void
	 */
	public function output_header_bottom() {
		?>
		<div class="wpcode-column">
			<h1>
				<?php
				printf(
				// Translators: The placeholder gets replaced with the title of the snippet.
					esc_html__( 'Compare Revisions of "%s"', 'wpcode-premium' ),
					esc_html( $this->compare_title )
				);
				?>
			</h1>
		</div>
		<div class="wpcode-column">
			<a href="<?php echo esc_url( $this->back_url ); ?>" class="wpcode-button-text">
				<?php
				wpcode_icon( 'arrow-left', 16, 8 );
				esc_html_e( 'Go back to editor', 'wpcode-premium' );
				?>
			</a>
		</div>
		<?php
	}

	/**
	 * Page content.
	 *
	 * @return void
	 */
	public function output_content() {
		$revisions        = array_reverse( wpcode()->revisions->get_snippet_revisions( $this->snippet_id ) );
		$current_revision = 0;
		$revision_ids     = array();
		foreach ( $revisions as $key => $revision ) {
			if ( absint( $revision->revision_id ) === $this->revision_id ) {
				$current_revision = $key;
			}
			$revision_ids[] = $revision->revision_id;
		}
		$total = count( $revisions ) - 1;
		?>
		<div class="wpcode-compare-checkbox-holder">
			<label>
				<input type="checkbox" id="wpcode-compare-two"/>
				<?php esc_html_e( 'Compare any two revisions', 'wpcode-premium' ); ?>
			</label>
		</div>
		<div class="wpcode-revisions-timeline">
			<div class="wpcode-revisions-timeline-left">
				<button class="wpcode-button wpcode-button-secondary" id="wpcode-revision-slider-prev"><?php esc_html_e( 'Previous', 'wpcode-premium' ); ?></button>
			</div>
			<div class="wpcode-revisions-timeline-center">
				<div
						class="wpcode-revisions-timeline-slider"
						data-value="<?php echo absint( $current_revision ); ?>"
						data-total="<?php echo absint( $total ); ?>"
						data-revisions='<?php echo wp_json_encode( $revision_ids ); ?>'
						data-snippet_id="<?php echo absint( $this->snippet_id ); ?>"
						data-compare="<?php echo esc_attr( $this->compare ); ?>"
				></div>
			</div>
			<div class="wpcode-revisions-timeline-right">
				<button class="wpcode-button wpcode-button-secondary" id="wpcode-revision-slider-next"><?php esc_html_e( 'Next', 'wpcode-premium' ); ?></button>
			</div>
		</div>
		<div class="wpcode-revisions-diff-display">
			<?php
			$default_data  = $this->get_default_data();
			$previous_data = $default_data;
			foreach ( $revisions as $key => $revision ) {
				$revision_data = json_decode( $revision->revision_data, true );
				?>
				<div class="wpcode-revision-display" data-slider-id="<?php echo absint( $key ); ?>">
					<?php
					self::get_revision_compare_header( $revision, count( $revisions ) - 1 === $key );
					self::get_revision_compare_content( $previous_data, $revision_data, $this->get_type() );
					?>
				</div>
				<?php
				$previous_data = wp_parse_args( $revision_data, $default_data );
			}
			?>
			<div id="wpcode-revision-compare-any-display">

			</div>
		</div>
		<script>
			jQuery( function ( $ ) {
				const allrevisions = $( '.wpcode-revision-display' );
				const timeline_slider = $( '.wpcode-revisions-timeline-slider' );
				const timeline_center = $( '.wpcode-revisions-timeline-center' );
				const total = timeline_slider.data( 'total' );
				const value = timeline_slider.data( 'value' );
				const snippet_id = timeline_slider.data( 'snippet_id' );
				const revisions = timeline_slider.data( 'revisions' );
				const default_compare = timeline_slider.data( 'compare' );
				const available_width = timeline_center.width();
				const prev_button = $( '#wpcode-revision-slider-prev' );
				const next_button = $( '#wpcode-revision-slider-next' );
				const any_display = $( '#wpcode-revision-compare-any-display' );
				const diff_display = $( '.wpcode-revisions-diff-display' );
				const content = $( '.wpcode-content' );
				const compare_toggle = $( '#wpcode-compare-two' );

				let is_compare = 1 === default_compare;
				if ( total * 55 < available_width ) {
					timeline_slider.width( total * 55 );
				} else {
					timeline_slider.width( '100%' );
				}

				function init_slider( add_steps ) {
					const slider = timeline_slider.slider( {
						value: value,
						min: 0,
						max: total,
						step: 1,
						change: function ( event, ui ) {
							allrevisions.hide();
							$( '[data-slider-id="' + ui.value + '"]' ).show();
							if ( ui.value === 0 ) {
								prev_button.prop( 'disabled', true );
							} else {
								prev_button.prop( 'disabled', false );
							}
							if ( ui.value === total ) {
								next_button.prop( 'disabled', true );
							} else {
								next_button.prop( 'disabled', false );
							}
						},
						slide: function ( event, ui ) {
							if ( is_compare && ui.values && ui.values[0] === ui.values[1] ) {
								return false;
							}
						},
						classes: {
							'ui-slider': '',
							'ui-slider-handle': 'wpcode-slider-handle'
						}
					} );

					$( '[data-slider-id="' + value + '"]' ).show();

					if ( add_steps ) {
						slider.each( function () {
							const opt = $( this ).data().uiSlider.options;
							const vals = opt.max - opt.min;
							for ( let i = 0; i <= vals; i ++ ) {
								const el = $( '<span class="wpcode-revisions-slider-step-indicator"></span>' ).css( 'left', i / vals * 100 + '%' );
								$( '.wpcode-revisions-timeline-slider' ).append( el );
							}
						} );
					}
				}

				init_slider( true );
				if ( is_compare ) {
					compare_toggle.prop( 'checked', true );
					enable_compare();
				}

				if ( total === value ) {
					next_button.prop( 'disabled', true );
				}

				prev_button.on( 'click', function ( e ) {
					e.preventDefault();
					timeline_slider.slider( 'value', timeline_slider.slider( 'value' ) - 1 );
				} );

				next_button.on( 'click', function ( e ) {
					e.preventDefault();
					timeline_slider.slider( 'value', timeline_slider.slider( 'value' ) + 1 );
				} );


				compare_toggle.on( 'change', function () {
					if ( $( this ).prop( 'checked' ) ) {
						enable_compare();
					} else {
						disable_compare();
					}
				} );

				function enable_compare() {
					let current_value = timeline_slider.slider( 'value' );
					let second_value;
					if ( current_value === 0 ) {
						second_value = current_value + 1;
					} else {
						second_value = current_value;
						current_value = second_value - 1;
					}
					if ( is_compare ) {
						current_value = value;
						second_value = revisions.length - 1;
					}

					timeline_slider.slider( 'destroy' );
					timeline_slider.slider( {
						values: [current_value, second_value],
						min: 0,
						max: total,
						step: 1,
						change: function ( event, ui ) {
							load_ajax_revisions( ui.values );
						},
						slide: function ( event, ui ) {
							if ( ui.values && ui.values[0] === ui.values[1] ) {
								return false;
							}
						},
						classes: {
							'ui-slider': '',
							'ui-slider-handle': 'wpcode-slider-handle'
						}
					} );

					load_ajax_revisions( [current_value, second_value] );

					is_compare = true;
					any_display.empty();
					any_display.show();
					content.addClass( 'wpcode-compare-mode' );
				}

				function disable_compare() {
					is_compare = false;
					timeline_slider.slider( 'destroy' );
					init_slider( false );
					any_display.hide();
					content.removeClass( 'wpcode-compare-mode' );
				}

				function load_ajax_revisions( values ) {
					diff_display.addClass( 'wpcode-show-loading' );
					const id1 = revisions[values[0]];
					const id2 = revisions[values[1]];
					if ( id1 && id2 ) {
						$.post(
							ajaxurl,
							{
								action: 'wpcode_load_revisions_compare',
								_wpnonce: wpcode.nonce,
								ids: [
									id1,
									id2,
								],
								snippet_id
							}
						).then( function ( response ) {
							diff_display.removeClass( 'wpcode-show-loading' );
							if ( response.success ) {
								allrevisions.hide();
								any_display.html( response.data.markup );
							}
						} );
					}
				}
			} );
		</script>
		<?php

		wp_enqueue_script( 'jquery-ui-slider' );
	}

	/**
	 * Override this method to prevent having two ".wrap" on the same page.
	 *
	 * @return void
	 */
	public function maybe_output_message() {
	}

	/**
	 * Based on wp_text_diff but also outputs something if both values are similar.
	 *
	 * @param string       $left_string "old" (left) version of string.
	 * @param string       $right_string "new" (right) version of string.
	 * @param string|array $args {
	 *     Associative array of options to pass to WP_Text_Diff_Renderer_Table().
	 *
	 * @type string        $title Titles the diff in a manner compatible
	 *                                   with the output. Default empty.
	 * @type string        $title_left Change the HTML to the left of the title.
	 *                                   Default empty.
	 * @type string        $title_right Change the HTML to the right of the title.
	 *                                   Default empty.
	 * @type bool          $show_split_view True for split view (two columns), false for
	 *                                   un-split view (single column). Default true.
	 * }
	 *
	 * @return string
	 * @see wp_text_diff
	 */
	public static function text_diff( $left_string, $right_string, $args = null ) {
		$defaults = array(
			'title'           => '',
			'title_left'      => '',
			'title_right'     => '',
			'show_split_view' => true,
			'show_no_diff'    => false,
		);
		$args     = wp_parse_args( $args, $defaults );

		if ( ! class_exists( 'WP_Text_Diff_Renderer_Table', false ) ) {
			require ABSPATH . WPINC . '/wp-diff.php';
		}

		$left_string  = normalize_whitespace( $left_string );
		$right_string = normalize_whitespace( $right_string );

		$left_lines  = explode( "\n", $left_string );
		$right_lines = explode( "\n", $right_string );
		$text_diff   = new Text_Diff( $left_lines, $right_lines );
		$renderer    = new WP_Text_Diff_Renderer_Table();
		$diff        = $renderer->render( $text_diff );

		if ( ! $diff ) {
			if ( ! $args['show_no_diff'] ) {
				return '';
			}
			$diff = '';
			// If there are no differences return a simple table with the same value instead of empty like in the core function.
			foreach ( $left_lines as $key => $left_line ) {
				$diff .= "<tr><td>{$left_line}</td><td>{$right_lines[$key]}</td></tr>";
			}
		}

		$is_split_view       = ! empty( $args['show_split_view'] );
		$is_split_view_class = $is_split_view ? ' is-split-view' : '';

		$r = "<table class='diff$is_split_view_class'>\n";

		if ( $args['title'] ) {
			$r .= sprintf( "<caption class='diff-title'>%s</caption>\n", esc_html( $args['title'] ) );
		}

		if ( $args['title_left'] || $args['title_right'] ) {
			$r .= '<thead>';
		}

		if ( $args['title_left'] || $args['title_right'] ) {
			$th_or_td_left  = empty( $args['title_left'] ) ? 'td' : 'th';
			$th_or_td_right = empty( $args['title_right'] ) ? 'td' : 'th';

			$r .= "<tr class='diff-sub-title'>\n";
			$r .= "\t<$th_or_td_left>$args[title_left]</$th_or_td_left>\n";
			if ( $is_split_view ) {
				$r .= "\t<$th_or_td_right>$args[title_right]</$th_or_td_right>\n";
			}
			$r .= "</tr>\n";
		}

		if ( $args['title_left'] || $args['title_right'] ) {
			$r .= "</thead>\n";
		}

		$r .= "<tbody>\n$diff\n</tbody>\n";
		$r .= '</table>';

		return $r;
	}

	/**
	 * Convert the Auto-insert int value to a human-readable value.
	 *
	 * @param int $value The auto-insert value.
	 *
	 * @return string
	 */
	public static function auto_insert_to_text( $value ) {
		if ( 1 === $value ) {
			return __( 'Auto Insert Enabled', 'wpcode-premium' );
		}

		return __( 'Shortcode only', 'wpcode-premium' );
	}

	/**
	 * Get the label for a code type from its key.
	 *
	 * @param string $type The code type key.
	 *
	 * @return string
	 */
	public static function code_type_to_label( $type ) {
		$code_types = wpcode()->execute->get_options();

		return isset( $code_types[ $type ] ) ? $code_types[ $type ] : '';
	}

	/**
	 * Convert the use_rules boolean value to a human-readable label.
	 *
	 * @param bool $bool The boolean value to convert.
	 *
	 * @return string
	 */
	public static function use_rules_to_text( $bool ) {
		return $bool ? __( 'Rules Enabled', 'wpcode-premium' ) : __( 'Rules Disabled', 'wpcode-premium' );
	}

	/**
	 * Convert the schedule array to text for display.
	 *
	 * @param array $schedule The schedule array.
	 *
	 * @return string
	 */
	public static function schedule_to_text( $schedule ) {
		$start = isset( $schedule['start'] ) ? $schedule['start'] : '';
		$end   = isset( $schedule['end'] ) ? $schedule['end'] : '';

		return __( 'Start', 'wpcode-premium' ) . ': ' . $start . ' ' . __( 'End', 'wpcode-premium' ) . ': ' . $end;
	}


	/**
	 * Convert a set of conditional logic rules to a text representation.
	 *
	 * @param array $rules Array of rules as they are stored in the snippet object.
	 *
	 * @return string
	 * @see WPCode_Admin_Page_Snippet_Manager::get_conditional_logic_input()
	 * @see WPCode_Snippet::get_conditional_rules()
	 */
	public static function conditional_logic_to_text( $rules ) {
		if ( empty( $rules ) ) {
			return '';
		}

		$text = '';
		if ( isset( $rules['show'] ) ) {
			$text .= 'show' === $rules['show'] ? __( 'Show', 'wpcode-premium' ) : __( 'Hide', 'wpcode-premium' );
			$text .= ' ' . __( 'this code snippet if', 'wpcode-premium' );
			$text .= PHP_EOL;
		}
		if ( ! empty( $rules['groups'] ) ) {

			$rules_count = count( $rules['groups'] );
			foreach ( $rules['groups'] as $i => $group ) {
				$and_rules_count = count( $group );
				foreach ( $group as $and_i => $and_rules ) {
					$text .= self::get_conditional_logic_group_as_text( $and_rules );
					if ( $and_rules_count > 1 && $and_i < $and_rules_count - 1 ) {
						$text .= PHP_EOL . __( 'AND', 'wpcode-premium' ) . PHP_EOL;
					}
				}
				if ( $rules_count > 1 && $i < $rules_count - 1 ) {
					$text .= PHP_EOL . __( 'OR', 'wpcode-premium' ) . PHP_EOL;
				}
			}
		}

		return $text;
	}

	/**
	 * Convert conditional rules group to a human-readable text that can be easily compared.
	 *
	 * @param array $group The group of rules.
	 *
	 * @return string
	 */
	public static function get_conditional_logic_group_as_text( $group ) {
		$logic_options = wpcode()->conditional_logic->get_all_admin_options();

		if ( ! isset( $group['type'] ) || ! isset( $logic_options[ $group['type'] ] ) ) {
			return '';
		}
		$relation_labels = wpcode_get_conditions_relation_labels();

		$cl_options = $logic_options[ $group['type'] ];
		$value      = $group['value'];
		if ( 'logged_in' === $group['option'] ) {
			$value = boolval( $value );
		}
		if ( ! empty( $cl_options['options'][ $group['option'] ]['options'] ) && is_array( $cl_options['options'][ $group['option'] ]['options'] ) ) {
			foreach ( $cl_options['options'][ $group['option'] ]['options'] as $option ) {
				if ( is_array( $value ) ) {
					foreach ( $value as $i => $v ) {
						if ( $option['value'] === $v ) {
							$value[ $i ] = $option['label'];
						}
					}
				} else {
					if ( $option['value'] === $value ) {
						$value = $option['label'];
					}
				}
			}
		}

		if ( is_array( $value ) ) {
			$value = implode( ', ', $value );
		}

		return '[' . $cl_options['label'] . '] [' . $cl_options['options'][ $group['option'] ]['label'] . '] [' . $relation_labels[ $group['relation'] ] . '] [' . $value . ']';
	}

	/**
	 * Get the header of a revision comparison with the revision details.
	 *
	 * @param stdClass $revision The revision object.
	 * @param bool     $disabled_restore_button Whether to disable the restore button for this revision.
	 * @param string   $prefix_label The label to display before the author avatar.
	 *
	 * @return void
	 */
	public static function get_revision_compare_header( $revision, $disabled_restore_button = false, $prefix_label = '' ) {
		$datef         = _x( 'F j, Y @ H:i:s', 'revision date format', 'wpcode-premium' );
		$modified_time = strtotime( $revision->created );
		$updated       = sprintf(
		// Translators: time since the revision has been updated.
			__( 'Updated %s ago', 'wpcode-premium' ),
			human_time_diff( $modified_time )
		);
		if ( time() - $modified_time > 15 * DAY_IN_SECONDS ) {
			$updated = sprintf(
			// Translators: date when revision was updated.
				__( 'Updated on %s', 'wpcode-premium' ),
				date_i18n( $datef, strtotime( $revision->created ) )
			);
		}
		$restore_revision_url = wp_nonce_url(
			add_query_arg(
				array(
					'restore'  => $revision->revision_id,
					'revision' => $revision->revision_id
				),
				admin_url( 'admin.php?page=wpcode-revisions' )
			),
			'wpcode-restore-revision'
		);
		?>
		<div class="wpcode-revision-display-header">
			<?php if ( ! empty( $prefix_label ) ) { ?>
				<span class="wpcode-revision-header-prefix-label"><?php echo esc_html( $prefix_label ); ?></span>
			<?php } ?>
			<?php echo get_avatar( $revision->author_id, 30 ); ?>
			<span class="wpcode-revision-header-author">
						<?php
						printf(
						// Translators: Placeholder gets replaced by the name of the author.
							esc_html__( 'Revision by %s', 'wpcode-premium' ),
							'<span class="wpcode-revision-list-author">' . esc_html( get_the_author_meta( 'display_name', $revision->author_id ) ) . '</span>'
						);
						?>
						</span>
			<span class="wpcode-revision-list-date">
							<?php echo esc_html( $updated ); ?>
						</span>
			<div class="wpcode-revision-display-header-right">
				<?php if ( $disabled_restore_button ) { ?>
					<button class="wpcode-button wpcode-button-small wpcode-button-disabled" type="button" disabled>
						<?php esc_html_e( 'Restore this revision', 'wpcode-premium' ); ?>
					</button>
				<?php } else { ?>
					<a href="<?php echo esc_url( $restore_revision_url ); ?>" class="wpcode-button wpcode-button-small">
						<?php esc_html_e( 'Restore this revision', 'wpcode-premium' ); ?>
					</a>
				<?php } ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Get the markup for comparing the revision data visually from 2 revision data arrays.
	 *
	 * @param array  $previous_data The old version.
	 * @param array  $revision_data The new version.
	 * @param string $type The type of revision we are comparing.
	 *
	 * @return void
	 */
	public static function get_revision_compare_content( $previous_data, $revision_data, $type = 'snippet' ) {
		?>
		<div class="wpcode-revision-display-content">
			<?php
			if ( 'snippet' === $type ) {
				self::get_snippet_revision_compare_content( $previous_data, $revision_data );
			} elseif ( 'header_footer' === $type ) {
				self::get_header_footer_revision_compare_content( $previous_data, $revision_data );
			} elseif ( 'page_scripts' === $type ) {
				self::get_page_scripts_revision_compare_content( $previous_data, $revision_data );
			}
			?>
		</div>
		<?php
	}

	/**
	 * Get the markup for comparing the revision data for a snippet.
	 *
	 * @param array $previous_data The old version.
	 * @param array $revision_data The new version.
	 *
	 * @return void
	 */
	public static function get_snippet_revision_compare_content( $previous_data, $revision_data ) {
		?>
		<div class="wpcode-revision-display-title">
			<h3><?php esc_html_e( 'Title', 'wpcode-premium' ); ?></h3>
			<?php
			echo self::text_diff( $previous_data['title'], $revision_data['title'], array( 'show_no_diff' => true ) );
			?>
		</div>
		<?php
		if ( $previous_data['code_type'] !== $revision_data['code_type'] ) {
			?>
			<div class="wpcode-revision-display-code-type">
				<h3><?php esc_html_e( 'Code Type', 'wpcode-premium' ); ?></h3>
				<?php
				echo self::text_diff( self::code_type_to_label( $previous_data['code_type'] ), self::code_type_to_label( $revision_data['code_type'] ) );
				?>
			</div>
			<?php
		}
		if ( $previous_data['code'] !== $revision_data['code'] ) {
			?>
			<div class="wpcode-revision-display-code">
				<h3><?php esc_html_e( 'Code', 'wpcode-premium' ); ?></h3>
				<?php echo self::text_diff( $previous_data['code'], $revision_data['code'] ); ?>
			</div>
			<?php
		}
		if ( $previous_data['auto_insert'] !== $revision_data['auto_insert'] ) {
			?>
			<div class="wpcode-revision-display-location">
				<h3><?php esc_html_e( 'Auto Insert', 'wpcode-premium' ); ?></h3>
				<?php
				echo self::text_diff( self::auto_insert_to_text( $previous_data['auto_insert'] ), self::auto_insert_to_text( $revision_data['auto_insert'] ) );
				?>
			</div>
			<?php
		}
		if ( isset( $revision_data['shortcode_attributes'] ) && $previous_data['shortcode_attributes'] !== $revision_data['shortcode_attributes'] ) {
			?>
			<div class="wpcode-revision-display-location">
				<h3><?php esc_html_e( 'Shortcode Attributes', 'wpcode-premium' ); ?></h3>
				<?php
				echo self::text_diff( self::shortcode_attributes_to_text( $previous_data['shortcode_attributes'] ), self::shortcode_attributes_to_text( $revision_data['shortcode_attributes'] ) );
				?>
			</div>
			<?php
		}
		if ( $previous_data['location'] !== $revision_data['location'] ) {
			?>
			<div class="wpcode-revision-display-location">
				<h3><?php esc_html_e( 'Location', 'wpcode-premium' ); ?></h3>
				<?php
				echo self::text_diff( wpcode_find_location_label( $previous_data['location'] ), wpcode_find_location_label( $revision_data['location'] ) );
				?>
			</div>
			<?php
		}
		if ( $previous_data['insert_number'] !== $revision_data['insert_number'] ) {
			?>
			<div class="wpcode-revision-display-number">
				<h3><?php esc_html_e( 'Insert Number', 'wpcode-premium' ); ?></h3>
				<?php
				echo self::text_diff( $previous_data['insert_number'], $revision_data['insert_number'] );
				?>
			</div>
			<?php
		}
		if ( $previous_data['custom_shortcode'] !== $revision_data['custom_shortcode'] ) {
			?>
			<div class="wpcode-revision-display-location">
				<h3><?php esc_html_e( 'Custom Shortcode', 'wpcode-premium' ); ?></h3>
				<?php
				echo self::text_diff( $previous_data['custom_shortcode'], $revision_data['custom_shortcode'] );
				?>
			</div>
			<?php
		}
		if ( isset( $revision_data['device_type'] ) && $previous_data['device_type'] !== $revision_data['device_type'] ) {
			?>
			<div class="wpcode-revision-display-location">
				<h3><?php esc_html_e( 'Device Type', 'wpcode-premium' ); ?></h3>
				<?php
				echo self::text_diff( $previous_data['device_type'], $revision_data['device_type'] );
				?>
			</div>
			<?php
		}
		if ( isset( $revision_data['schedule'] ) && $previous_data['schedule'] !== $revision_data['schedule'] ) {
			?>
			<div class="wpcode-revision-display-location">
				<h3><?php esc_html_e( 'Schedule', 'wpcode-premium' ); ?></h3>
				<?php
				echo self::text_diff( self::schedule_to_text( $previous_data['schedule'] ), self::schedule_to_text( $revision_data['schedule'] ) );
				?>
			</div>
			<?php
		}
		if ( $previous_data['tags'] !== $revision_data['tags'] ) {
			?>
			<div class="wpcode-revision-display-tags">
				<h3><?php esc_html_e( 'Tags', 'wpcode-premium' ); ?></h3>
				<?php
				echo self::text_diff( implode( "\n", $previous_data['tags'] ), implode( "\n", $revision_data['tags'] ) );
				?>
			</div>
			<?php
		}
		if ( $previous_data['priority'] !== $revision_data['priority'] ) {
			?>
			<div class="wpcode-revision-display-priority">
				<h3><?php esc_html_e( 'Priority', 'wpcode-premium' ); ?></h3>
				<?php
				echo self::text_diff( $previous_data['priority'], $revision_data['priority'] );
				?>
			</div>
			<?php
		}
		if ( $previous_data['note'] !== $revision_data['note'] ) {
			?>
			<div class="wpcode-revision-display-note">
				<h3><?php esc_html_e( 'Note', 'wpcode-premium' ); ?></h3>
				<?php
				echo self::text_diff( $previous_data['note'], $revision_data['note'] );
				?>
			</div>
			<?php
		}
		if ( $previous_data['use_rules'] !== $revision_data['use_rules'] || $previous_data['rules'] !== $revision_data['rules'] ) {
			?>
			<div class="wpcode-revision-display-conditional-logic">
				<h3><?php esc_html_e( 'Smart Conditional Logic', 'wpcode-premium' ); ?></h3>
				<?php
				echo self::text_diff( self::use_rules_to_text( $previous_data['use_rules'] ), self::use_rules_to_text( $revision_data['use_rules'] ) );
				echo self::text_diff( self::conditional_logic_to_text( $previous_data['rules'] ), self::conditional_logic_to_text( $revision_data['rules'] ) );
				?>
			</div>
			<?php
		}
		if ( $previous_data['location_extra'] !== $revision_data['location_extra'] ) {
			?>
			<div class="wpcode-revision-display-location-extra">
				<h3><?php esc_html_e( 'Location-specific setting', 'wpcode-premium' ); ?></h3>
				<?php
				echo self::text_diff( self::location_extra_to_text( $previous_data ), self::location_extra_to_text( $revision_data ) );
				?>
			</div>
			<?php
		}
		?>
		<?php
	}

	/**
	 * Get the markup for comparing the revision data for Header & Footer data.
	 *
	 * @param array $previous_data The old version.
	 * @param array $revision_data The new version.
	 *
	 * @return void
	 */
	public static function get_header_footer_revision_compare_content( $previous_data, $revision_data ) {
		$labels = array(
			'ihaf_insert_header' => __( 'Header', 'wpcode-premium' ),
			'ihaf_insert_footer' => __( 'Footer', 'wpcode-premium' ),
			'ihaf_insert_body'   => __( 'Body', 'wpcode-premium' ),
		);

		foreach ( $previous_data as $key => $value ) {
			?>
			<div class="wpcode-revision-display-code-type">
				<h3><?php echo esc_html( $labels[ $key ] ) ?></h3>
				<?php
				echo self::text_diff( $previous_data[ $key ], $revision_data[ $key ] );
				?>
			</div>
			<?php
		}
	}

	/**
	 * Get the markup for comparing the revision data for page scripts.
	 *
	 * @param array $previous_data The old version.
	 * @param array $revision_data The new version.
	 *
	 * @return void
	 */
	public static function get_page_scripts_revision_compare_content( $previous_data, $revision_data ) {
		$labels = array(
			'_wpcode_header_scripts' => __( 'Header Scripts', 'wpcode-premium' ),
			'_wpcode_footer_scripts' => __( 'Footer Scripts', 'wpcode-premium' ),
			'_wpcode_body_scripts'   => __( 'Body Scripts', 'wpcode-premium' ),
			'_wpcode_page_snippets'  => __( 'Custom Code Snippets', 'wpcode-premium' ),
		);

		$titles = array(
			'disable_global' => __( 'Disable Global Scripts', 'wpcode-premium' ),
			'any'            => __( 'Any Device Type', 'wpcode-premium' ),
			'desktop'        => __( 'Desktop Only', 'wpcode-premium' ),
			'mobile'         => __( 'Mobile Only', 'wpcode-premium' ),
		);

		foreach ( $revision_data as $key => $value ) {
			if ( $value === $previous_data[ $key ] ) {
				continue;
			}
			?>
			<div class="wpcode-revision-display-code-type">
				<h3><?php echo esc_html( $labels[ $key ] ); ?></h3>
				<?php
				if ( '_wpcode_page_snippets' === $key ) {
					echo self::text_diff(
						self::page_scripts_snippets_to_text( $previous_data[ $key ] ),
						self::page_scripts_snippets_to_text( $revision_data[ $key ] ),
						array(
							'title_left' => esc_html__( 'Loaded Snippets', 'wpcode-premium' ),
						)
					);
				} else {
					if ( is_array( $value ) ) {
						foreach ( $value as $k => $v ) {
							$current_data      = isset( $revision_data[ $key ][ $k ] ) ? $revision_data[ $key ][ $k ] : '';
							$current_prev_data = isset( $previous_data[ $key ][ $k ] ) ? $previous_data[ $key ][ $k ] : '';
							echo self::text_diff(
								self::maybe_page_scripts_to_text( $current_prev_data ),
								self::maybe_page_scripts_to_text( $current_data ),
								array(
									'title_left'   => isset( $titles[ $k ] ) ? $titles[ $k ] : '',
									'title_right'  => isset( $titles[ $k ] ) ? $titles[ $k ] : '',
									'show_no_diff' => true,
								)
							);
						}
					}
				}
				?>
			</div>
			<?php
		}
	}

	/**
	 * Convert the page scripts snippets to text for display.
	 *
	 * @param mixed $data The data to convert.
	 *
	 * @return string
	 */
	public static function maybe_page_scripts_to_text( $data ) {
		if ( is_bool( $data ) ) {
			return $data ? esc_html__( 'Enabled', 'wpcode-premium' ) : esc_html__( 'Disabled', 'wpcode-premium' );
		}

		return $data;
	}

	/**
	 * Convert the page scripts snippets to text for display.
	 *
	 * @param array $data The data to convert.
	 *
	 * @return string
	 */
	public static function page_scripts_snippets_to_text( $data ) {
		if ( ! is_array( $data ) ) {
			return '';
		}
		$text = '';
		foreach ( $data as $snippet_data ) {
			$snippet = wpcode_get_snippet( $snippet_data['snippet_id'] );

			$text = sprintf(
			// Translators: %s: Label of the auto-insert location selected for the snippet.
				esc_html__( 'Snippet: %s', 'wpcode-premium' ),
				$snippet->get_title()
			);

			$text .= "\n";
			$text .= sprintf(
			// Translators: %s: Label of the auto-insert location selected for the snippet.
				esc_html__( 'Location: %s', 'wpcode-premium' ),
				wpcode_find_location_label( $snippet_data['location'] )
			);
		}

		return $text;
	}

	/**
	 *
	 * @param array $snippet_data The snippet data to extract.
	 *
	 * @return string
	 */
	public static function location_extra_to_text( $snippet_data ) {
		if ( empty( $snippet_data['location_extra'] ) ) {
			return '';
		}
		$anywhere_locations = array(
			'before_css_selector',
			'after_css_selector',
			'start_css_selector',
			'end_css_selector',
		);
		$text               = '';
		if ( in_array( $snippet_data['location'], $anywhere_locations, true ) ) {
			$location_data = json_decode( $snippet_data['location_extra'], true );
			if ( ! empty( $location_data['css_selector'] ) ) {
				$text .= sprintf(
				         /* Translators: %s: CSS selector */
					         esc_html__( 'CSS selector: %s', 'wpcode-premium' ),
					         $location_data['css_selector']
				         ) . "\n";
			}
			if ( ! empty( $location_data['index'] ) ) {
				$text .= sprintf(
				         /* Translators: %s: CSS selector */
					         esc_html__( 'Element Index: %s', 'wpcode-premium' ),
					         $location_data['index']
				         ) . "\n";
			}
		}

		return $text;
	}

	public static function shortcode_attributes_to_text( $attributes ) {
		if ( ! is_array( $attributes ) ) {
			return '';
		}

		return implode( PHP_EOL, $attributes );
	}

	/**
	 * Get the default data for revision compare based on the type of revision we are displaying.
	 *
	 * @return array|string[]
	 */
	public function get_default_data() {
		$data = array(
			'title'                => '',
			'code'                 => '',
			'location'             => '',
			'auto_insert'          => '',
			'insert_number'        => '',
			'tags'                 => array(),
			'priority'             => '',
			'note'                 => '',
			'code_type'            => '',
			'use_rules'            => '',
			'rules'                => '',
			'custom_shortcode'     => '',
			'device_type'          => '',
			'schedule'             => '',
			'location_extra'       => '',
			'shortcode_attributes' => array(),
		);

		if ( $this->is_global ) {
			$data = array(
				'ihaf_insert_header' => '',
				'ihaf_insert_footer' => '',
				'ihaf_insert_body'   => '',
			);
		}

		if ( $this->is_page_scripts ) {
			$data = array(
				'_wpcode_header_scripts' => array(
					'disable_global' => false,
					'any'            => '',
					'desktop'        => '',
					'mobile'         => '',
				),
				'_wpcode_footer_scripts' => array(
					'disable_global' => false,
					'any'            => '',
					'desktop'        => '',
					'mobile'         => '',
				),
				'_wpcode_body_scripts'   => array(
					'disable_global' => false,
					'any'            => '',
					'desktop'        => '',
					'mobile'         => '',
				),
				'_wpcode_page_snippets'  => array(),
			);
		}

		return $data;
	}

	/**
	 * Get the type of revision we are displaying.
	 *
	 * @return string
	 */
	public function get_type() {
		$type = 'snippet';
		if ( $this->is_global ) {
			$type = 'header_footer';
		}
		if ( $this->is_page_scripts ) {
			$type = 'page_scripts';
		}

		return $type;
	}
}
