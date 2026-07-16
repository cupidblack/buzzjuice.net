<?php
/**
 * BuzzJuice — LearnDash Page Gate (MU)
 *
 * Adds a "Gated Pages" setting to each LearnDash Course Access Settings.
 * Admins select Pages to gate per-course. When a gated Page is visited by a
 * learner who has not completed the associated course(s), an interstitial
 * (3–5s) will guide them to the course page or their next resumeable step.
 *
 * Drop into: wp-content/mu-plugins/bzj-learndash-page-gate.php
 *
 * Filters:
 *  - bzj_ld_gate_index_option (string)   : option name used for the index (default 'bzj_ld_gate_index')
 *  - bzj_ld_gate_delay (float $seconds, int $course_id, int $page_id)
 *
 * Actions:
 *  - bzj_ld_gate_interstitial_shown( int $user_id, int $course_id, int $page_id, string $target_url )
 *  - bzj_ld_rebuild_index_now (callable) — kept available for manual rebuilds (not auto-hooked)
 *
 * @package BuzzJuice
 * @version 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class BZJ_LD_Page_Gate {
	const META_KEY = '_bzj_ld_gate';
	const DEFAULT_INDEX = 'bzj_ld_gate_index';
	const REST_NAMESPACE = 'bzj-ld/v1';
	const REST_ROUTE_SEARCH_PAGES = 'search-pages';

	/** @var BZJ_LD_Page_Gate|null */
	private static $instance = null;

	/** CPTs of LearnDash steps for fallback scanning */
	private $supported_step_post_types = [ 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz' ];

	/** Transient lifetime (seconds) for resume link caching */
	private $resume_cache_ttl = 300;

	public static function init() {
		if ( self::$instance === null ) {
			self::$instance = new self();
			self::$instance->hooks();
		}
		return self::$instance;
	}

	private function hooks() {
		// Add field into LearnDash course access settings
		add_filter( 'learndash_settings_fields', [ $this, 'filter_learndash_settings_fields' ], 60, 2 );
		add_action( 'save_post', [ $this, 'save_course_meta' ], 20, 2 );

		// Admin assets and Select2 REST search
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_assets' ] );
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );

		// Frontend enforcement
		add_action( 'template_redirect', [ $this, 'enforce_gate' ], 1 );

		// Keep a manual hook to rebuild index externally, but DO NOT hook into generic postmeta actions
		add_action( 'bzj_ld_rebuild_index_now', [ $this, 'rebuild_index' ] );
	}

	/* ---------------------------
	 * Admin integration: add a custom field into LearnDash Course Access Settings
	 * --------------------------- */
	public function filter_learndash_settings_fields( $fields, $settings_section_key ) {
		if ( $settings_section_key !== 'learndash-course-access-settings' ) {
			return $fields;
		}

		$fields['bzj_ld_gated_pages'] = [
			'name' => 'bzj_ld_gated_pages',
			'label' => esc_html__( 'Gated Pages', 'bzj-ld-page-gate' ),
			'type' => 'custom',
			'value' => '',
			'default' => '',
			'display_callback' => [ $this, 'render_course_gated_pages_field' ],
		];

		return $fields;
	}

	public function render_course_gated_pages_field() {
		$course_id = get_the_ID();
		if ( empty( $course_id ) ) {
			echo '<p>' . esc_html__( 'Course context missing.', 'bzj-ld-page-gate' ) . '</p>';
			return;
		}

		$meta = get_post_meta( $course_id, self::META_KEY, true );
		if ( ! is_array( $meta ) ) $meta = [];

		$pages = isset( $meta['pages'] ) && is_array( $meta['pages'] ) ? array_map( 'intval', $meta['pages'] ) : [];
		$mode = isset( $meta['mode'] ) ? sanitize_text_field( $meta['mode'] ) : 'resume';
		$logic = isset( $meta['logic'] ) ? sanitize_text_field( $meta['logic'] ) : 'any';
		$delay = isset( $meta['delay_seconds'] ) ? floatval( $meta['delay_seconds'] ) : 3.5;

		// Preload labels for selected pages
		$selected_labels = [];
		if ( ! empty( $pages ) ) {
			$posts = get_posts( [
				'post__in' => $pages,
				'post_type' => 'page',
				'posts_per_page' => count( $pages ),
				'orderby' => 'post__in',
			] );
			foreach ( $posts as $p ) {
				$selected_labels[ $p->ID ] = get_the_title( $p->ID );
			}
		}

		wp_nonce_field( 'bzj_ld_gate_save', 'bzj_ld_gate_nonce' );

		// Render Select2-enabled select
		?>
		<div>
			<label for="bzj_ld_pages"><strong><?php esc_html_e( 'Pages to Gate', 'bzj-ld-page-gate' ); ?></strong></label><br/>
			<select id="bzj_ld_pages" name="bzj_ld_pages[]" multiple="multiple" style="width:100%" data-placeholder="<?php esc_attr_e( 'Search pages by title…', 'bzj-ld-page-gate' ); ?>">
				<?php
				foreach ( $selected_labels as $pid => $label ) {
					printf( '<option value="%d" selected>%s</option>', intval( $pid ), esc_html( $label ) );
				}
				?>
			</select>
			<p class="description"><?php esc_html_e( 'Select Pages that will be gated by this course. When a gated page is visited by a learner who has not completed the course, they will be guided back into the course.', 'bzj-ld-page-gate' ); ?></p>
		</div>

		<div style="margin-top:10px;">
			<strong><?php esc_html_e( 'Redirect Mode', 'bzj-ld-page-gate' ); ?></strong><br/>
			<label><input type="radio" name="bzj_ld_mode" value="resume" <?php checked( $mode, 'resume' ); ?> /> <?php esc_html_e( 'Resume user (preferred)', 'bzj-ld-page-gate' ); ?></label><br/>
			<label><input type="radio" name="bzj_ld_mode" value="course" <?php checked( $mode, 'course' ); ?> /> <?php esc_html_e( 'Course page (always)', 'bzj-ld-page-gate' ); ?></label>
			<p class="description"><?php esc_html_e( 'Resume: attempt to direct user to their next incomplete step. Course: send user to the main course page.', 'bzj-ld-page-gate' ); ?></p>
		</div>

		<div style="margin-top:10px;">
			<strong><?php esc_html_e( 'When multiple courses gate this page', 'bzj-ld-page-gate' ); ?></strong><br/>
			<label><input type="radio" name="bzj_ld_logic" value="any" <?php checked( $logic, 'any' ); ?> /> <?php esc_html_e( 'Allow if any selected course is complete', 'bzj-ld-page-gate' ); ?></label><br/>
			<label><input type="radio" name="bzj_ld_logic" value="all" <?php checked( $logic, 'all' ); ?> /> <?php esc_html_e( 'Require all selected courses to be complete', 'bzj-ld-page-gate' ); ?></label>
			<p class="description"><?php esc_html_e( 'If multiple courses map to the same Page, choose whether ANY or ALL must be completed to allow access.', 'bzj-ld-page-gate' ); ?></p>
		</div>

		<div style="margin-top:10px;">
			<label for="bzj_ld_delay"><strong><?php esc_html_e( 'Interstitial delay (seconds)', 'bzj-ld-page-gate' ); ?></strong></label>
			<input id="bzj_ld_delay" name="bzj_ld_delay" type="number" step="0.5" min="0" value="<?php echo esc_attr( $delay ); ?>" style="width:6rem;" />
			<p class="description"><?php esc_html_e( 'Time the interstitial shows before redirecting. Default: 3.5 seconds. Set to 0 for immediate redirect.', 'bzj-ld-page-gate' ); ?></p>
		</div>
		<?php
	}

	/* ---------------------------
	 * Admin: enqueue Select2 and init script
	 * --------------------------- */
	public function admin_enqueue_assets( $hook ) {
		if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) return;

		$post_type = '';
		if ( isset( $_GET['post'] ) ) {
			$post_type = get_post_type( intval( $_GET['post'] ) );
		} elseif ( isset( $_POST['post_type'] ) ) {
			$post_type = sanitize_text_field( wp_unslash( $_POST['post_type'] ) );
		} elseif ( isset( $GLOBALS['post'] ) && is_object( $GLOBALS['post'] ) ) {
			$post_type = get_post_type( $GLOBALS['post']->ID );
		}

		$course_post_type = function_exists( 'learndash_get_post_type_slug' ) ? learndash_get_post_type_slug( 'course' ) : 'sfwd-courses';
		if ( $post_type !== $course_post_type ) return;

		$cdn_js  = 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js';
		$cdn_css = 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css';

		wp_enqueue_style( 'bzj-select2-css', $cdn_css, [], '4.1.0-rc.0' );
		wp_enqueue_script( 'bzj-select2-js', $cdn_js, [ 'jquery' ], '4.1.0-rc.0', true );

		$rest_url = rest_url( sprintf( '%s/%s', self::REST_NAMESPACE, self::REST_ROUTE_SEARCH_PAGES ) );
		$nonce = wp_create_nonce( 'wp_rest' );

		$init = "
			(function($){
				$(function(){
					if ( typeof $.fn.select2 !== 'function' ) return;
					var sel = $('#bzj_ld_pages');
					if (!sel.length) return;

					sel.select2({
						ajax: {
							url: " . wp_json_encode( esc_url_raw( $rest_url ) ) . ",
							dataType: 'json',
							delay: 250,
							headers: { 'X-WP-Nonce': " . wp_json_encode( $nonce ) . " },
							data: function(params){ return { q: params.term || '', page: params.page || 1 }; },
							processResults: function(data, params){ params.page = params.page || 1; return { results: data.items, pagination: { more: data.more } }; },
							cache: true
						},
						placeholder: sel.data('placeholder') || 'Search pages',
						width: '100%',
						minimumInputLength: 1,
						templateResult: function(item){ if (!item) return ''; return item.text || ''; },
						templateSelection: function(item){ return item && item.text ? item.text : item.id; }
					});
				});
			})(jQuery);
		";
		wp_add_inline_script( 'bzj-select2-js', $init );
	}

	/* ---------------------------
	 * REST: search pages for admin select2
	 * --------------------------- */
	public function register_rest_routes() {
		register_rest_route( self::REST_NAMESPACE, '/' . self::REST_ROUTE_SEARCH_PAGES, [
			'methods' => 'GET',
			'callback' => [ $this, 'rest_search_pages' ],
			'permission_callback' => function() {
				return is_user_logged_in() && current_user_can( 'edit_posts' );
			},
			'args' => [
				'q' => [ 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ],
				'page' => [ 'required' => false, 'sanitize_callback' => 'absint' ],
			],
		] );
	}

	public function rest_search_pages( $request ) {
		global $wpdb;

		$q = $request->get_param( 'q' ) ?: '';
		$page = max( 1, intval( $request->get_param( 'page' ) ?: 1 ) );
		$per_page = 25;
		$offset = ( $page - 1 ) * $per_page;
		$items = [];

		if ( is_numeric( $q ) && intval( $q ) > 0 ) {
			$id = intval( $q );
			$post = get_post( $id );
			if ( $post && $post->post_type === 'page' ) {
				$items[] = [ 'id' => $post->ID, 'text' => get_the_title( $post->ID ) ];
			}
			return rest_ensure_response( [ 'items' => $items, 'more' => false ] );
		}

		if ( $q !== '' ) {
			$like = '%' . $wpdb->esc_like( $q ) . '%';
			$sql = $wpdb->prepare( "
				SELECT ID, post_title FROM {$wpdb->posts}
				WHERE post_type = %s
				  AND post_status IN ('publish','private','draft')
				  AND post_title LIKE %s
				ORDER BY post_title ASC
				LIMIT %d OFFSET %d
			", 'page', $like, $per_page, $offset );
			$results = $wpdb->get_results( $sql );
			foreach ( $results as $r ) {
				$items[] = [ 'id' => intval( $r->ID ), 'text' => $r->post_title ];
			}

			$count_sql = $wpdb->prepare( "
				SELECT COUNT(1) FROM {$wpdb->posts}
				WHERE post_type = %s
				  AND post_status IN ('publish','private','draft')
				  AND post_title LIKE %s
			", 'page', $like );
			$found = intval( $wpdb->get_var( $count_sql ) );
			$more = ( ( $page * $per_page ) < $found );
		} else {
			$args = [
				'post_type' => 'page',
				'post_status' => [ 'publish', 'private', 'draft' ],
				'posts_per_page' => $per_page,
				'paged' => $page,
				'orderby' => 'title',
				'order' => 'ASC',
				'fields' => 'ids',
			];
			$query = new WP_Query( $args );
			if ( $query->have_posts() ) {
				foreach ( $query->posts as $pid ) {
					$items[] = [ 'id' => intval( $pid ), 'text' => get_the_title( $pid ) ];
				}
			}
			$more = ( $page * $per_page ) < intval( $query->found_posts );
		}

		return rest_ensure_response( [ 'items' => $items, 'more' => (bool) $more ] );
	}

	/* ---------------------------
	 * Save course meta and maintain index (index update performed directly here)
	 * --------------------------- */
	public function save_course_meta( $post_id, $post ) {
		$course_post_type = function_exists( 'learndash_get_post_type_slug' ) ? learndash_get_post_type_slug( 'course' ) : 'sfwd-courses';
		if ( $post->post_type !== $course_post_type ) return;
		if ( ! current_user_can( 'edit_post', $post_id ) ) return;
		if ( empty( $_POST['bzj_ld_gate_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['bzj_ld_gate_nonce'] ), 'bzj_ld_gate_save' ) ) {
			return;
		}

		$old = get_post_meta( $post_id, self::META_KEY, true );
		if ( ! is_array( $old ) ) $old = [];

		$pages = [];
		if ( isset( $_POST['bzj_ld_pages'] ) && is_array( $_POST['bzj_ld_pages'] ) ) {
			foreach ( $_POST['bzj_ld_pages'] as $v ) {
				$v = intval( $v );
				if ( $v > 0 ) $pages[] = $v;
			}
			$pages = array_values( array_unique( $pages ) );
		}

		$mode = ( isset( $_POST['bzj_ld_mode'] ) && in_array( $_POST['bzj_ld_mode'], [ 'resume', 'course' ], true ) ) ? sanitize_text_field( wp_unslash( $_POST['bzj_ld_mode'] ) ) : 'resume';
		$logic = ( isset( $_POST['bzj_ld_logic'] ) && in_array( $_POST['bzj_ld_logic'], [ 'any', 'all' ], true ) ) ? sanitize_text_field( wp_unslash( $_POST['bzj_ld_logic'] ) ) : 'any';
		$delay = ( isset( $_POST['bzj_ld_delay'] ) ) ? floatval( wp_unslash( $_POST['bzj_ld_delay'] ) ) : 3.5;
		if ( $delay < 0 ) $delay = 0;

		$new = [
			'pages' => $pages,
			'mode'  => $mode,
			'logic' => $logic,
			'delay_seconds' => $delay,
		];

		update_post_meta( $post_id, self::META_KEY, $new );

		$this->update_index_for_course( $post_id, $old['pages'] ?? [], $pages );
	}

	/* ---------------------------
	 * Index maintenance: page_id => [course_ids]
	 * --------------------------- */
	private function update_index_for_course( $course_id, $old_pages = [], $new_pages = [] ) {
		$opt_name = apply_filters( 'bzj_ld_gate_index_option', self::DEFAULT_INDEX );
		$index = get_option( $opt_name, [] );
		if ( ! is_array( $index ) ) $index = [];

		$old_pages = is_array( $old_pages ) ? array_map( 'intval', $old_pages ) : [];
		$new_pages = is_array( $new_pages ) ? array_map( 'intval', $new_pages ) : [];

		foreach ( $old_pages as $p ) {
			if ( isset( $index[ $p ] ) && is_array( $index[ $p ] ) ) {
				$index[ $p ] = array_values( array_diff( $index[ $p ], [ $course_id ] ) );
				if ( empty( $index[ $p ] ) ) unset( $index[ $p ] );
			}
		}

		foreach ( $new_pages as $p ) {
			if ( ! isset( $index[ $p ] ) || ! is_array( $index[ $p ] ) ) $index[ $p ] = [];
			$index[ $p ][] = $course_id;
			$index[ $p ] = array_values( array_unique( $index[ $p ] ) );
		}

		update_option( $opt_name, $index, false );
	}

	/* ---------------------------
	 * Rebuild index (manual use or programmatic via do_action('bzj_ld_rebuild_index_now'))
	 * --------------------------- */
	public function rebuild_index() {
		$opt_name = apply_filters( 'bzj_ld_gate_index_option', self::DEFAULT_INDEX );
		$index = [];

		$course_post_type = function_exists( 'learndash_get_post_type_slug' ) ? learndash_get_post_type_slug( 'course' ) : 'sfwd-courses';
		$course_query = new WP_Query( [ 'post_type' => $course_post_type, 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids' ] );
		if ( $course_query->have_posts() ) {
			foreach ( $course_query->posts as $cid ) {
				$meta = get_post_meta( $cid, self::META_KEY, true );
				if ( is_array( $meta ) && ! empty( $meta['pages'] ) && is_array( $meta['pages'] ) ) {
					foreach ( $meta['pages'] as $p ) {
						$p = intval( $p );
						if ( $p <= 0 ) continue;
						$index[ $p ][] = $cid;
					}
				}
			}
		}
		foreach ( $index as $p => $arr ) {
			$index[ $p ] = array_values( array_unique( array_map( 'intval', $arr ) ) );
		}
		update_option( $opt_name, $index, false );
	}

	/* ---------------------------
	 * Frontend: enforce gate on page requests
	 * --------------------------- */
	public function enforce_gate() {
		if ( is_admin() ) return;
		if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) return;
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) return;

		if ( ! is_singular( 'page' ) ) return;

		global $post;
		if ( ! $post || empty( $post->ID ) ) return;

		$page_id = $post->ID;
		$opt_name = apply_filters( 'bzj_ld_gate_index_option', self::DEFAULT_INDEX );
		$index = get_option( $opt_name, null );

		if ( $index === null ) {
			// If index hasn't been built yet, do a lightweight build (only once per request)
			$this->rebuild_index();
			$index = get_option( $opt_name, [] );
		}

		if ( empty( $index ) || empty( $index[ $page_id ] ) ) {
			return; // not gated
		}

		$course_ids = array_map( 'intval', $index[ $page_id ] );
		$user_id = get_current_user_id();

		// Not logged in: send to first course page (no interstitial)
		if ( empty( $user_id ) ) {
			wp_safe_redirect( get_permalink( $course_ids[0] ) );
			exit;
		}

		// Evaluate completion state across mapped courses
		$any_completed = false;
		$all_completed = true;
		$require_all = false;
		foreach ( $course_ids as $cid ) {
			$meta = get_post_meta( $cid, self::META_KEY, true );
			$logic = is_array( $meta ) && isset( $meta['logic'] ) ? $meta['logic'] : 'any';
			if ( $logic === 'all' ) $require_all = true;

			$done = $this->is_course_complete_for_user( $cid, $user_id );
			$any_completed = $any_completed || $done;
			$all_completed = $all_completed && $done;
		}

		$allowed = $require_all ? $all_completed : $any_completed;
		if ( $allowed ) return;

		// Not allowed: find best target URL
		$target_url = '';
		$target_course_id = $course_ids[0];

		foreach ( $course_ids as $cid ) {
			$meta = get_post_meta( $cid, self::META_KEY, true );
			$mode = is_array( $meta ) && isset( $meta['mode'] ) ? $meta['mode'] : 'resume';

			// If user hasn't started the course yet -> redirect to course page
			if ( ! $this->has_user_started_course( $cid, $user_id ) ) {
				$target_course_id = $cid;
				$target_url = get_permalink( $cid );
				break;
			}

			// If admin selected 'course' mode -> course page
			if ( $mode === 'course' ) {
				$target_course_id = $cid;
				$target_url = get_permalink( $cid );
				break;
			}

			// Try to resolve resume/next-step target (with transient caching)
			$next = $this->resolve_next_step_for_user( $user_id, $cid );
			if ( $next ) {
				$target_course_id = $cid;
				$target_url = $next;
				break;
			}

			// fallback to course page
			$target_course_id = $cid;
			$target_url = get_permalink( $cid );
		}

		if ( empty( $target_url ) ) {
			$target_url = get_permalink( $target_course_id );
		}

		// Avoid redirect loop: if resolved target equals current page permalink, don't redirect.
		$current_perm = untrailingslashit( get_permalink( $page_id ) );
		if ( untrailingslashit( $target_url ) === $current_perm ) {
			return;
		}

		// interstitial delay (allow filter)
		$meta_for_target = get_post_meta( $target_course_id, self::META_KEY, true );
		$delay = is_array( $meta_for_target ) && isset( $meta_for_target['delay_seconds'] ) ? floatval( $meta_for_target['delay_seconds'] ) : 3.5;
		$delay = apply_filters( 'bzj_ld_gate_delay', $delay, $target_course_id, $page_id );

		// Fire action for analytics
		do_action( 'bzj_ld_gate_interstitial_shown', $user_id, $target_course_id, $page_id, $target_url );

		// Immediate redirect if delay <= 0
		if ( $delay <= 0 ) {
			wp_safe_redirect( $target_url );
			exit;
		}

		// Render interstitial and redirect
		$this->render_gate_interstitial_and_redirect( $target_course_id, $page_id, $target_url, $delay );
	}

	/* ---------------------------
	 * Interstitial UI
	 * --------------------------- */
	private function render_gate_interstitial_and_redirect( $course_id, $page_id, $target_url, $delay_seconds ) {
		nocache_headers();
		status_header( 200 );

		$course_title = get_the_title( $course_id );
		$page_title = get_the_title( $page_id );
		$escape_target = esc_url( $target_url );

		$delay_seconds = floatval( $delay_seconds );
		if ( $delay_seconds < 0.5 ) $delay_seconds = 0.5;
		if ( $delay_seconds > 10 ) $delay_seconds = 10;

		$skip_available_after_ms = 1000; // allow skip after 1s

		$heading = esc_html__( 'Continue your course to gain access', 'bzj-ld-page-gate' );
		$lead = sprintf( esc_html__( 'This page is reserved for learners completing: %s', 'bzj-ld-page-gate' ), esc_html( $course_title ) );
		$next_msg = esc_html__( 'We will take you to the next item to continue your progress.', 'bzj-ld-page-gate' );
		$continue_now = esc_html__( 'Continue now', 'bzj-ld-page-gate' );
		$redirecting = esc_html__( 'Redirecting…', 'bzj-ld-page-gate' );

		?>
		<!doctype html>
		<html lang="<?php echo esc_attr( get_locale() ); ?>">
		<head>
			<meta charset="utf-8">
			<meta name="robots" content="noindex,nofollow">
			<meta name="viewport" content="width=device-width,initial-scale=1">
			<title><?php echo esc_html( $heading ); ?></title>
			<style>
				html,body{height:100%;margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,"Helvetica Neue",Arial}
				body{display:flex;align-items:center;justify-content:center;background:#f6f8fa;color:#111}
				.card{max-width:720px;width:94%;background:#fff;border-radius:12px;padding:28px;box-shadow:0 10px 30px rgba(12,14,20,.08);text-align:center}
				h1{margin:0 0 8px;font-size:20px}
				p.lead{margin:0 0 14px;color:#333;font-size:15px}
				.info{color:#666;font-size:13px;margin-bottom:16px}
				.bar{height:8px;background:#e6e9ee;border-radius:99px;overflow:hidden;margin:10px auto 18px}
				.fill{height:100%;width:0;background:#2b7cff;transition:width linear}
				.btn{display:inline-block;padding:8px 14px;border-radius:8px;background:#2b7cff;color:#fff;text-decoration:none;font-weight:600;cursor:pointer;border:0}
				.btn[disabled]{opacity:.6;cursor:default}
				.small{font-size:13px;color:#666;margin-top:12px}
				.footer{margin-top:18px;font-size:13px;color:#888}
			</style>
		</head>
		<body>
			<div class="card" role="dialog" aria-labelledby="bzj-heading" aria-describedby="bzj-desc">
				<h1 id="bzj-heading"><?php echo esc_html( $heading ); ?></h1>
				<p class="lead" id="bzj-desc"><?php echo esc_html( $lead ); ?></p>
				<p class="info"><?php echo esc_html( $next_msg ); ?></p>

				<div class="bar" aria-hidden="true"><div id="bzj-fill" class="fill"></div></div>

				<button id="bzj-continue" class="btn" disabled><?php echo esc_html( $continue_now ); ?></button>

				<div class="small"><?php echo esc_html( $redirecting ); ?></div>
				<div class="footer"><em><?php echo sprintf( esc_html__( 'If you believe you should have access, contact: %s', 'bzj-ld-page-gate' ), esc_html( get_bloginfo( 'admin_email' ) ) ); ?></em></div>
			</div>

			<script>
				(function(){
					var delay = <?php echo json_encode( round( $delay_seconds * 1000 ) ); ?>;
					var skipAfter = <?php echo json_encode( $skip_available_after_ms ); ?>;
					var fill = document.getElementById('bzj-fill');
					var btn = document.getElementById('bzj-continue');
					var target = <?php echo wp_json_encode( $escape_target ); ?>;

					// Start progress animation
					setTimeout(function(){ fill.style.width = '100%'; }, 50);

					// enable button after a short delay
					setTimeout(function(){ btn.disabled = false; }, skipAfter);

					btn.addEventListener('click', function(){
						location.href = target;
					});

					// Auto-redirect after delay
					setTimeout(function(){ location.href = target; }, delay);

					// Escape key to continue
					window.addEventListener('keydown', function(e){
						if ( e.key === 'Escape' ) {
							location.href = target;
						}
					});
				})();
			</script>
		</body>
		</html>
		<?php
		exit;
	}

	/* ---------------------------
	 * Next-step resolution and completion helpers
	 * --------------------------- */

	/**
	 * Resolve the best next-step URL for a user and course.
	 * Uses transient caching to avoid repeated expensive calls.
	 *
	 * @param int $user_id
	 * @param int $course_id
	 * @return string|null
	 */
	private function resolve_next_step_for_user( $user_id, $course_id ) {
		$trans_key = 'bzj_ld_resume_' . intval( $user_id ) . '_' . intval( $course_id );
		$cached = get_transient( $trans_key );
		if ( $cached !== false && is_string( $cached ) && $cached !== '' ) {
			return $cached;
		}

		// Try known LearnDash resume helpers (defensive)
		$candidates = [
			'learndash_course_get_resume_link',
			'learndash_get_user_course_resume_link',
			'learndash_get_resume_link',
			'learndash_user_get_next_step',
		];

		foreach ( $candidates as $fn ) {
			if ( function_exists( $fn ) ) {
				try {
					$res = call_user_func( $fn, $course_id, $user_id );
					if ( is_string( $res ) && ! empty( $res ) ) {
						set_transient( $trans_key, $res, $this->resume_cache_ttl );
						return $res;
					}
					if ( is_array( $res ) && ! empty( $res['link'] ) ) {
						set_transient( $trans_key, $res['link'], $this->resume_cache_ttl );
						return $res['link'];
					}
					if ( is_object( $res ) && ! empty( $res->link ) ) {
						set_transient( $trans_key, $res->link, $this->resume_cache_ttl );
						return $res->link;
					}
				} catch ( Exception $e ) {
					// swallow and try next
				}
			}
		}

		// Fallback: scan course steps (prefer lessons then topics then quizzes)
		$meta_keys = [ '_lesson_course', 'course_id', 'lesson_course' ];
		foreach ( $this->supported_step_post_types as $ptype ) {
			foreach ( $meta_keys as $mk ) {
				$args = [
					'post_type'      => $ptype,
					'post_status'    => 'publish',
					'meta_key'       => $mk,
					'meta_value'     => $course_id,
					'posts_per_page' => 200,
					'orderby'        => 'menu_order',
					'order'          => 'ASC',
					'fields'         => 'ids',
				];
				$query = new WP_Query( $args );
				if ( $query->have_posts() ) {
					foreach ( $query->posts as $step_id ) {
						if ( ! $this->is_step_complete_for_user( $user_id, $step_id, $ptype, $course_id ) ) {
							$url = get_permalink( $step_id );
							set_transient( $trans_key, $url, $this->resume_cache_ttl );
							return $url;
						}
					}
				}
			}
		}

		// Final fallback return course permalink
		$course_url = get_permalink( $course_id );
		set_transient( $trans_key, $course_url, $this->resume_cache_ttl );
		return $course_url;
	}

	/**
	 * Check if a course is complete for a user (LearnDash-first, robust)
	 *
	 * @param int $course_id
	 * @param int $user_id
	 * @return bool
	 */
	private function is_course_complete_for_user( $course_id, $user_id ) {
		// LearnDash function (user, course) in many versions
		if ( function_exists( 'learndash_is_course_complete' ) ) {
			try {
				return (bool) @learndash_is_course_complete( $user_id, $course_id );
			} catch ( Exception $e ) {
				// ignore
			}
		}

		// Try alternate LearnDash course status helper
		if ( function_exists( 'learndash_course_status' ) ) {
			try {
				$status = @learndash_course_status( $course_id, $user_id, true );
				if ( is_string( $status ) && strtolower( $status ) === 'completed' ) return true;
			} catch ( Exception $e ) {}
		}

		// Final fallback: check legacy usermeta
		$completed = get_user_meta( $user_id, 'learndash_course_completed_' . $course_id, true );
		if ( ! empty( $completed ) ) return true;

		return false;
	}

	/**
	 * Has the user started the course? checks access_from, activity or completion
	 *
	 * @param int $course_id
	 * @param int $user_id
	 * @return bool
	 */
	private function has_user_started_course( $course_id, $user_id ) {
		$access = get_user_meta( $user_id, 'course_' . $course_id . '_access_from', true );
		if ( ! empty( $access ) ) return true;

		// Try LearnDash activity
		if ( function_exists( 'learndash_get_user_activity' ) ) {
			try {
				$activity = learndash_get_user_activity( [ 'user_id' => $user_id, 'post_id' => $course_id, 'activity_type' => 'access' ] );
				if ( ( is_array( $activity ) && ! empty( $activity ) ) || is_object( $activity ) ) return true;
			} catch ( Exception $e ) {}
		}

		// Completed implies started
		if ( $this->is_course_complete_for_user( $course_id, $user_id ) ) return true;

		return false;
	}

	/**
	 * Is a specific step (lesson/topic/quiz) complete for a user
	 *
	 * @param int $user_id
	 * @param int $step_id
	 * @param string $post_type
	 * @param int $course_id
	 * @return bool
	 */
	private function is_step_complete_for_user( $user_id, $step_id, $post_type = '', $course_id = 0 ) {
		try {
			if ( empty( $post_type ) ) $post_type = get_post_type( $step_id );

			if ( $post_type === 'sfwd-lessons' && function_exists( 'learndash_is_lesson_complete' ) ) {
				return (bool) @learndash_is_lesson_complete( $user_id, $step_id, $course_id );
			}
			if ( $post_type === 'sfwd-topic' && function_exists( 'learndash_is_topic_complete' ) ) {
				return (bool) @learndash_is_topic_complete( $user_id, $step_id, $course_id );
			}
			if ( $post_type === 'sfwd-quiz' && function_exists( 'learndash_is_quiz_complete' ) ) {
				return (bool) @learndash_is_quiz_complete( $user_id, $step_id, $course_id );
			}
		} catch ( Exception $e ) {}

		if ( function_exists( 'learndash_user_progress_is_step_complete' ) ) {
			try {
				$res = @learndash_user_progress_is_step_complete( $user_id, $course_id, $step_id );
				return (bool) $res;
			} catch ( Exception $e ) {}
		}

		// course_progress usermeta fallback
		$progress = get_user_meta( $user_id, 'course_progress', true );
		if ( is_array( $progress ) && $course_id > 0 && isset( $progress[ $course_id ] ) && is_array( $progress[ $course_id ] ) ) {
			$cp = $progress[ $course_id ];
			$type_key = '';
			if ( $post_type === 'sfwd-lessons' ) $type_key = 'lessons';
			if ( $post_type === 'sfwd-topic' ) $type_key = 'topics';
			if ( $post_type === 'sfwd-quiz' ) $type_key = 'quizzes';
			if ( $type_key && isset( $cp[ $type_key ] ) && isset( $cp[ $type_key ][ $step_id ] ) ) {
				$val = $cp[ $type_key ][ $step_id ];
				if ( $val === 1 || $val === '1' || $val === true || ( is_string( $val ) && in_array( strtolower( $val ), [ 'complete', 'completed', 'finished', 'passed' ], true ) ) ) {
					return true;
				}
			}
		}

		// legacy arrays
		if ( $post_type === 'sfwd-lessons' ) {
			$completed = get_user_meta( $user_id, 'sfwd-lessons_completed', true );
			if ( is_array( $completed ) && in_array( $step_id, $completed, true ) ) return true;
		}
		if ( $post_type === 'sfwd-topic' ) {
			$completed = get_user_meta( $user_id, 'sfwd-topic_completed', true );
			if ( is_array( $completed ) && in_array( $step_id, $completed, true ) ) return true;
		}
		if ( $post_type === 'sfwd-quiz' ) {
			$completed = get_user_meta( $user_id, 'sfwd-quiz_completed', true );
			if ( is_array( $completed ) && in_array( $step_id, $completed, true ) ) return true;
		}

		// activity table (conservative)
		global $wpdb;
		$activity_table = $wpdb->prefix . 'learndash_user_activity';
		$check = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $wpdb->esc_like( $activity_table ) ) );
		if ( $check ) {
			$statuses = [ 'completed', 'complete', 'passed', 'finished', 'completed-manual', 'manual' ];
			$placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );
			$sql = $wpdb->prepare(
				"SELECT COUNT(1) FROM {$activity_table} WHERE user_id = %d AND post_id = %d AND activity_status IN ({$placeholders})",
				array_merge( [ $user_id, $step_id ], $statuses )
			);
			$found = intval( $wpdb->get_var( $sql ) );
			if ( $found > 0 ) return true;
		}

		return false;
	}
}

// Initialize plugin
BZJ_LD_Page_Gate::init();