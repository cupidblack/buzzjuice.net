<?php
/**
 * Plugin Name: LearnDash Step Requirements (Buzzjuice) - Patched + Debug Endpoint
 * Description: Adds "Requirements for Enrollment" to LearnDash Lessons, Topics, and Quizzes. AJAX Select2 search, per-step prerequisites (lessons/topics/quizzes), Any/All mode. Includes an admin-only debug REST endpoint to inspect per-source completion state for troubleshooting.
 * Version:     2.5.3
 * Author:      Copilot for lemonbuzzjuice
 * License:     GPLv2+
 *
 * Patch notes (2.5.3):
 * - Adds an admin-only REST debug endpoint: /wp-json/bz-ld/v1/debug-step-status
 *   Use it to inspect each internal check (learndash helpers, course_progress usermeta,
 *   learndash_user_activity table, legacy usermeta arrays) for specific step(s) and user.
 * - Keeps the activity-table interpretation toggle (filter 'bz_ld_activity_table_found_indicates_complete').
 * - This file is a drop-in replacement for the mu-plugin. It will not change behavior except
 *   to expose the new debug endpoint.
 *
 * Install:
 * - Drop into: wp-content/mu-plugins/learndash-step-requirements.php
 *
 * Debug endpoint usage (examples):
 * - GET as admin/editor:
 *   /wp-json/bz-ld/v1/debug-step-status?user=1079&steps=sfwd-lessons:2384,2385
 *
 * - Or for numeric ids (assumes lessons by default):
 *   /wp-json/bz-ld/v1/debug-step-status?user=1079&steps=2384,2385
 *
 * The endpoint returns JSON with one entry per requested step listing:
 *   - helper_result (what learndash_is_* returned, if applicable),
 *   - progression_result (learndash_user_progress_is_step_complete),
 *   - course_progress_usermeta value, alternate course_progress_X usermeta value,
 *   - activity_table_rows_found (count) and how we interpret those rows,
 *   - legacy_usermeta arrays hit,
 *   - final computed 'computed_complete' boolean used by the plugin.
 *
 * The endpoint requires a user with edit_posts capability (admin/editor).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BZ_LD_Step_Requirements {
    const META_ENABLED = '_ld_step_prereq_enabled';
    const META_MODE    = '_ld_step_prereq_mode';
    const META_STEPS   = '_ld_step_prereq';

    const OLD_META_ENABLED = '_ld_lesson_prereq_enabled';
    const OLD_META_IDS     = '_ld_lesson_prereq_ids';
    const OLD_META_MODE    = '_ld_lesson_prereq_mode';

    private static $instance = null;

    private $supported_post_types = [ 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz' ];

    private $rest_namespace = 'bz-ld/v1';
    private $rest_route = 'search-steps';

    /**
     * Default behavior: when rows exist in learndash_user_activity matched to (user_id, post_id)
     * and status in the $statuses list, interpret them as meaning COMPLETE.
     *
     * Override via:
     * add_filter( 'bz_ld_activity_table_found_indicates_complete', '__return_false' );
     */
    private $activity_found_indicates_complete = true;

    public static function init() {
        if ( self::$instance === null ) {
            self::$instance = new self();
            // allow runtime override via filter
            self::$instance->activity_found_indicates_complete = apply_filters( 'bz_ld_activity_table_found_indicates_complete', true );
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
        add_action( 'save_post', [ $this, 'save_meta' ], 10, 2 );
        add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_assets' ] );
        add_action( 'admin_head', [ $this, 'admin_inline_styles' ] );

        add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );

        add_filter( 'learndash_can_user_access_step', [ $this, 'filter_can_user_access_step' ], 9999, 4 );
        add_filter( 'learndash_lesson_available_from_text', [ $this, 'filter_available_text' ], 9999, 3 );
        add_filter( 'learndash_topic_available_from_text', [ $this, 'filter_available_text' ], 9999, 3 );
        add_filter( 'learndash_quiz_available_from_text', [ $this, 'filter_available_text' ], 9999, 3 );

        add_filter( 'ld_lesson_access_from', [ $this, 'filter_ld_access_from' ], 9999, 3 );

        add_action( 'template_redirect', [ $this, 'enforce_frontend_access' ], 1 );
    }

    /* ------------------------
     * Meta box UI
     * ------------------------ */
    public function add_meta_boxes() {
        foreach ( $this->supported_post_types as $pt ) {
            add_meta_box(
                'bz_ld_step_prereq',
                __( 'Requirements for Enrollment (Step)', 'bz-ld-step-req' ),
                [ $this, 'render_meta_box' ],
                $pt,
                'side',
                'high'
            );
        }
    }

    public function render_meta_box( $post, $meta ) {
        if ( ! current_user_can( 'edit_post', $post->ID ) ) {
            echo '<p>' . esc_html__( 'Insufficient permissions to edit this item.', 'bz-ld-step-req' ) . '</p>';
            return;
        }

        wp_nonce_field( 'bz_ld_step_prereq_save', 'bz_ld_step_prereq_nonce' );

        $this->maybe_migrate_legacy_meta( $post->ID );

        $enabled = get_post_meta( $post->ID, self::META_ENABLED, true );
        $enabled = ( $enabled === '1' ) ? '1' : '0';

        $mode = get_post_meta( $post->ID, self::META_MODE, true );
        $mode = ( in_array( $mode, [ 'any', 'all' ], true ) ? $mode : 'any' );

        $raw = get_post_meta( $post->ID, self::META_STEPS, true );
        if ( ! is_array( $raw ) ) $raw = [];

        $values = [];
        foreach ( $raw as $r ) {
            if ( is_string( $r ) && strpos( $r, ':' ) !== false ) {
                $values[] = $r;
            } elseif ( is_array( $r ) && isset( $r['type'], $r['id'] ) ) {
                $values[] = sanitize_key( $r['type'] ) . ':' . intval( $r['id'] );
            }
        }
        ?>
        <p>
            <label><input type="checkbox" name="bz_ld_enabled" value="1" <?php checked( $enabled, '1' ); ?> /> <?php esc_html_e( 'Enable prerequisites for this item', 'bz-ld-step-req' ); ?></label>
        </p>

        <p>
            <strong><?php esc_html_e( 'Compare Mode', 'bz-ld-step-req' ); ?></strong><br/>
            <label><input type="radio" name="bz_ld_mode" value="any" <?php checked( $mode, 'any' ); ?> /> <?php esc_html_e( 'Any selected (complete any one)', 'bz-ld-step-req' ); ?></label><br/>
            <label><input type="radio" name="bz_ld_mode" value="all" <?php checked( $mode, 'all' ); ?> /> <?php esc_html_e( 'All selected (complete all)', 'bz-ld-step-req' ); ?></label>
        </p>

        <p>
            <label for="bz_ld_steps"><strong><?php esc_html_e( 'Prerequisite Steps', 'bz-ld-step-req' ); ?></strong></label><br/>
            <select id="bz_ld_steps" name="bz_ld_steps[]" multiple="multiple" style="width:100%;" data-placeholder="<?php esc_attr_e( 'Search for lessons, topics or quizzes…', 'bz-ld-step-req' ); ?>">
                <?php
                foreach ( $values as $val ) {
                    $parts = explode( ':', $val );
                    if ( count( $parts ) !== 2 ) continue;
                    list( $type, $id ) = $parts;
                    $id = intval( $id );
                    $title = get_the_title( $id );
                    if ( ! $title ) $title = sprintf( '%s #%d', ucfirst( str_replace( 'sfwd-', '', $type ) ), $id );
                    printf( '<option value="%s" selected>%s (%s)</option>', esc_attr( $val ), esc_html( $title ), esc_html( $this->human_readable_type( $type ) ) );
                }
                ?>
            </select>

            <p class="description">
                <?php esc_html_e( 'Select steps (Lessons, Topics, or Quizzes) that must be completed before this item becomes available. Use search for large sites.', 'bz-ld-step-req' ); ?>
            </p>
        </p>
        <?php
    }

    /* ------------------------
     * Admin assets (Select2) - uses local files if present, falls back to CDN
     * ------------------------ */
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

        if ( ! in_array( $post_type, $this->supported_post_types, true ) ) return;

        $local_js_path  = WP_CONTENT_DIR . '/shared/select2/select2.min.js';
        $local_css_path = WP_CONTENT_DIR . '/shared/select2/select2.min.css';

        $local_js_url  = content_url( 'shared/select2/select2.min.js' );
        $local_css_url = content_url( 'shared/select2/select2.min.css' );

        $cdn_js  = 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js';
        $cdn_css = 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css';

        if ( file_exists( $local_css_path ) ) {
            wp_register_style( 'bz-select2-css', $local_css_url, [], '4.1.0-rc.0' );
            wp_enqueue_style( 'bz-select2-css' );
        } else {
            wp_enqueue_style( 'bz-select2-cdn', $cdn_css, [], '4.1.0-rc.0' );
        }

        // Choose JS source and enqueue
        if ( file_exists( $local_js_path ) ) {
            wp_register_script( 'select2-local', $local_js_url, [ 'jquery' ], '4.1.0-rc.0', true );
            wp_enqueue_script( 'select2-local' );
            wp_register_script( 'bz-select2-loader', $local_js_url, [ 'jquery', 'select2-local' ], '1.0', true );
            wp_enqueue_script( 'bz-select2-loader' );
        } else {
            wp_register_script( 'select2-cdn', $cdn_js, [ 'jquery' ], '4.1.0-rc.0', true );
            wp_enqueue_script( 'select2-cdn' );
            wp_register_script( 'bz-select2-loader', $cdn_js, [ 'jquery', 'select2-cdn' ], '1.0', true );
            wp_enqueue_script( 'bz-select2-loader' );
        }

        $rest_url = rest_url( sprintf( '%s/%s', $this->rest_namespace, $this->rest_route ) );
        $nonce = wp_create_nonce( 'wp_rest' );
        wp_localize_script( 'bz-select2-loader', 'bz_ld_step_req', [
            'rest_url' => esc_url_raw( $rest_url ),
            'nonce'    => $nonce,
        ] );

        $init_js = <<<JS
jQuery(function($){
    function initSelect2(){
        var sel = $('#bz_ld_steps');
        if (!sel.length) return;

        if (typeof sel.select2 !== 'function') {
            setTimeout(initSelect2, 500);
            return;
        }

        var rest_url = (typeof bz_ld_step_req !== 'undefined' && bz_ld_step_req.rest_url) ? bz_ld_step_req.rest_url : window.location.origin + '/wp-json/bz-ld/v1/search-steps';
        var nonce = (typeof bz_ld_step_req !== 'undefined' && bz_ld_step_req.nonce) ? bz_ld_step_req.nonce : null;

        sel.select2({
            ajax: {
                url: rest_url,
                dataType: 'json',
                delay: 250,
                headers: nonce ? { 'X-WP-Nonce': nonce } : {},
                data: function(params){
                    return { q: params.term || '', page: params.page || 1 };
                },
                processResults: function(data, params){
                    params.page = params.page || 1;
                    return { results: data.items, pagination: { more: data.more } };
                },
                cache: true
            },
            placeholder: sel.data('placeholder') || 'Search for steps',
            width: 'resolve',
            allowClear: true,
            minimumInputLength: 1,
            templateResult: function(item){
                if (!item || !item.id) return item && item.text ? item.text : '';
                var \$wrap = $('<div></div>');
                \$wrap.append( $('<div>').text(item.text) );
                if (item.type_label) {
                    \$wrap.append( $('<div style="color:#666;font-size:11px">').text(item.type_label) );
                }
                return \$wrap;
            },
            templateSelection: function(item){
                return item.text || item.id;
            }
        });

        sel.trigger('change');
    }

    initSelect2();
});
JS;
        wp_add_inline_script( 'bz-select2-loader', $init_js );
    }

    public function admin_inline_styles() {
        // Provide robust, cross-platform override so selected items render vertically (as a list)
        // - fallback to block display for older browsers
        // - prefer flex column where supported
        // - force .select2-selection__choice to be block-level and allow wrapping text
        echo '<style>
#bz_ld_steps{font-size:13px}
.select2-container .select2-selection--multiple .select2-selection__rendered{
    display:block !important;
    list-style:none;
    padding:0;
    margin:0;
}
@supports (display:flex){
    .select2-container .select2-selection--multiple .select2-selection__rendered{
        display:flex !important;
        flex-direction:column;
        gap:6px;
        padding:0;
        margin:0;
    }
}
.select2-container .select2-selection--multiple .select2-selection__choice{
    display:block !important;
    margin:0 !important;
    padding:4px 8px !important;
    white-space:normal !important;
    box-sizing:border-box;
}
</style>';
    }

    /* ------------------------
     * Save meta
     * ------------------------ */
    public function save_meta( $post_id, $post ) {
        if ( ! in_array( $post->post_type, $this->supported_post_types, true ) ) return;

        if ( empty( $_POST['bz_ld_step_prereq_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['bz_ld_step_prereq_nonce'] ), 'bz_ld_step_prereq_save' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $enabled = ( isset( $_POST['bz_ld_enabled'] ) && $_POST['bz_ld_enabled'] ) ? '1' : '0';
        update_post_meta( $post_id, self::META_ENABLED, $enabled );

        $mode = ( isset( $_POST['bz_ld_mode'] ) && in_array( $_POST['bz_ld_mode'], [ 'any', 'all' ], true ) ) ? sanitize_text_field( wp_unslash( $_POST['bz_ld_mode'] ) ) : 'any';
        update_post_meta( $post_id, self::META_MODE, $mode );

        $steps = [];
        if ( isset( $_POST['bz_ld_steps'] ) && is_array( $_POST['bz_ld_steps'] ) ) {
            foreach ( $_POST['bz_ld_steps'] as $v ) {
                $v = trim( wp_unslash( $v ) );
                if ( strpos( $v, ':' ) !== false ) {
                    list( $type, $id ) = explode( ':', $v );
                    $type = sanitize_key( $type );
                    $id = intval( $id );
                    if ( $id > 0 && in_array( $type, $this->supported_post_types, true ) ) {
                        $steps[] = $type . ':' . $id;
                    }
                } else {
                    $id = intval( $v );
                    if ( $id > 0 ) {
                        $steps[] = $post->post_type . ':' . $id;
                    }
                }
            }
        }
        update_post_meta( $post_id, self::META_STEPS, $steps );
    }

    /* ------------------------
     * REST search endpoint
     * ------------------------ */
    public function register_rest_routes() {
        register_rest_route( $this->rest_namespace, '/' . $this->rest_route, [
            'methods'             => 'GET',
            'callback'            => [ $this, 'rest_search_steps' ],
            'permission_callback' => function( $request ) {
                return is_user_logged_in() && current_user_can( 'edit_posts' );
            },
            'args' => [
                'q' => [
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'page' => [
                    'required' => false,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ] );

        // Debug endpoint (admin/editor only)
        register_rest_route( $this->rest_namespace, '/debug-step-status', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'rest_debug_step_status' ],
            'permission_callback' => function( $request ) {
                return is_user_logged_in() && current_user_can( 'edit_posts' );
            },
            'args' => [
                'user' => [
                    'required' => true,
                    'sanitize_callback' => 'absint',
                ],
                'steps' => [
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field', // will split
                ],
            ],
        ] );
    }

    public function rest_search_steps( $request ) {
        global $wpdb;

        $q = $request->get_param( 'q' ) ?: '';
        $page = max( 1, intval( $request->get_param( 'page' ) ?: 1 ) );
        $per_page = 25;
        $offset = ( $page - 1 ) * $per_page;

        if ( is_numeric( $q ) && intval( $q ) > 0 ) {
            $id = intval( $q );
            $post = get_post( $id );
            if ( ! $post ) {
                return rest_ensure_response( [ 'items' => [], 'more' => false ] );
            }
            $ptype = get_post_type( $id );
            if ( ! in_array( $ptype, $this->supported_post_types, true ) ) {
                return rest_ensure_response( [ 'items' => [], 'more' => false ] );
            }
            $title = get_the_title( $id ) ?: $post->post_title;
            return rest_ensure_response( [
                'items' => [
                    [ 'id' => $ptype . ':' . $id, 'text' => $title, 'type' => $ptype, 'type_label' => $this->human_readable_type( $ptype ) ]
                ],
                'more' => false
            ] );
        }

        $items = [];

        if ( $q !== '' ) {
            $like = '%' . $wpdb->esc_like( $q ) . '%';
            $placeholders = implode( ',', array_fill( 0, count( $this->supported_post_types ), '%s' ) );
            $statuses = [ 'publish', 'private', 'draft' ];
            $status_placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );

            $sql = $wpdb->prepare(
                "
                SELECT ID, post_title, post_type
                FROM {$wpdb->posts}
                WHERE post_type IN ({$placeholders})
                  AND post_status IN ({$status_placeholders})
                  AND post_title LIKE %s
                ORDER BY post_title ASC
                LIMIT %d OFFSET %d
                ",
                array_merge( $this->supported_post_types, $statuses, [ $like, $per_page, $offset ] )
            );

            $results = $wpdb->get_results( $sql );

            foreach ( $results as $r ) {
                $items[] = [
                    'id' => $r->post_type . ':' . intval( $r->ID ),
                    'text' => esc_html( $r->post_title ),
                    'type' => $r->post_type,
                    'type_label' => $this->human_readable_type( $r->post_type ),
                ];
            }

            $count_sql = $wpdb->prepare(
                "
                SELECT COUNT(1) FROM {$wpdb->posts}
                WHERE post_type IN ({$placeholders})
                  AND post_status IN ({$status_placeholders})
                  AND post_title LIKE %s
                ",
                array_merge( $this->supported_post_types, $statuses, [ $like ] )
            );
            $found = intval( $wpdb->get_var( $count_sql ) );
            $more = ( ( $page * $per_page ) < $found );
        } else {
            $args = [
                'post_type' => $this->supported_post_types,
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
                    $ptype = get_post_type( $pid );
                    $title = get_the_title( $pid ) ?: sprintf( '%s #%d', ucfirst( str_replace( 'sfwd-', '', $ptype ) ), intval( $pid ) );
                    $items[] = [ 'id' => $ptype . ':' . intval( $pid ), 'text' => $title, 'type' => $ptype, 'type_label' => $this->human_readable_type( $ptype ) ];
                }
            }
            $more = ( $page * $per_page ) < intval( $query->found_posts );
        }

        return rest_ensure_response( [ 'items' => $items, 'more' => (bool) $more ] );
    }

    /**
     * Debug endpoint: returns detailed per-source results for given steps and user.
     *
     * Query parameters:
     *  - user (int) required
     *  - steps (csv) required. Each step may be type-prefixed (sfwd-lessons:123) or just numeric ID.
     *
     * Response structure:
     * {
     *   "sfwd-lessons:2384": {
     *       "step_id": 2384,
     *       "post_type": "sfwd-lessons",
     *       "course_id": 937,
     *       "helper_result": null|bool,
     *       "progression_result": null|bool,
     *       "course_progress_value": mixed,
     *       "course_progress_alt_value": mixed,
     *       "activity_table_rows_found": int,
     *       "activity_interpretation": "found_means_COMPLETE"|"found_means_INCOMPLETE"|"table_missing",
     *       "legacy_usermeta": {...},
     *       "computed_complete": true|false
     *   }, ...
     * }
     */
    public function rest_debug_step_status( $request ) {
        global $wpdb;

        $user_id = intval( $request->get_param( 'user' ) );
        $steps_param = $request->get_param( 'steps' );

        if ( $user_id <= 0 || empty( $steps_param ) ) {
            return new WP_Error( 'invalid_params', 'Invalid user or steps param', [ 'status' => 400 ] );
        }

        // Parse steps CSV
        $raw_steps = array_map( 'trim', explode( ',', $steps_param ) );
        $out = [];

        foreach ( $raw_steps as $raw ) {
            $post_type = '';
            $step_id = 0;
            if ( strpos( $raw, ':' ) !== false ) {
                list( $pt, $id ) = explode( ':', $raw, 2 );
                $post_type = sanitize_key( $pt );
                $step_id = intval( $id );
            } else {
                $step_id = intval( $raw );
                // try to detect post type by looking at the post
                $ptype = get_post_type( $step_id );
                $post_type = $ptype ? $ptype : 'sfwd-lessons';
            }

            if ( $step_id <= 0 ) {
                $out[ $raw ] = [ 'error' => 'invalid_step_id' ];
                continue;
            }

            $post = get_post( $step_id );
            if ( ! $post ) {
                $out_key = ( $post_type ? $post_type : 'post' ) . ':' . $step_id;
                $out[ $out_key ] = [ 'error' => 'post_not_found' ];
                continue;
            }

            $post_type = get_post_type( $post );

            // Resolve course id
            $step_course_id = 0;
            if ( function_exists( 'learndash_get_course_id' ) ) {
                $step_course_id = intval( @learndash_get_course_id( $step_id ) );
            }
            if ( $step_course_id <= 0 ) {
                $try_meta_keys = [ 'course_id', '_course_id', '_lesson_course', 'lesson_course' ];
                foreach ( $try_meta_keys as $mk ) {
                    $val = get_post_meta( $step_id, $mk, true );
                    if ( ! empty( $val ) && intval( $val ) > 0 ) {
                        $step_course_id = intval( $val );
                        break;
                    }
                }
            }

            $debug = [];
            $helper_result = null;
            $progression_result = null;
            $course_progress_value = null;
            $course_progress_alt_value = null;
            $activity_rows = 0;
            $activity_interpretation = 'table_missing';
            $legacy_usermeta = [];

            // 1) helper functions
            try {
                if ( $post_type === 'sfwd-lessons' && function_exists( 'learndash_is_lesson_complete' ) ) {
                    $helper_result = (bool) @learndash_is_lesson_complete( $user_id, $step_id, $step_course_id );
                    $debug[] = 'learndash_is_lesson_complete=>' . ($helper_result ? '1' : '0');
                } elseif ( $post_type === 'sfwd-topic' && function_exists( 'learndash_is_topic_complete' ) ) {
                    $helper_result = (bool) @learndash_is_topic_complete( $user_id, $step_id, $step_course_id );
                    $debug[] = 'learndash_is_topic_complete=>' . ($helper_result ? '1' : '0');
                } elseif ( $post_type === 'sfwd-quiz' && function_exists( 'learndash_is_quiz_complete' ) ) {
                    $helper_result = (bool) @learndash_is_quiz_complete( $user_id, $step_id, $step_course_id );
                    $debug[] = 'learndash_is_quiz_complete=>' . ($helper_result ? '1' : '0');
                }
            } catch ( Exception $e ) {
                $debug[] = 'helper-exception:' . $e->getMessage();
            }

            // 2) progression helper
            if ( function_exists( 'learndash_user_progress_is_step_complete' ) ) {
                try {
                    $progression_result = @learndash_user_progress_is_step_complete( $user_id, $step_course_id, $step_id );
                    $progression_result = ( $progression_result ? true : false );
                    $debug[] = 'learndash_user_progress_is_step_complete=>' . ($progression_result ? '1' : '0');
                } catch ( Exception $e ) {
                    $debug[] = 'progression-ex:' . $e->getMessage();
                }
            }

            // 3) course_progress usermeta
            $progress = get_user_meta( $user_id, 'course_progress', true );
            if ( is_array( $progress ) && $step_course_id > 0 && isset( $progress[ $step_course_id ] ) && is_array( $progress[ $step_course_id ] ) ) {
                $cp = $progress[ $step_course_id ];
                $type_key = '';
                if ( $post_type === 'sfwd-lessons' ) $type_key = 'lessons';
                if ( $post_type === 'sfwd-topic' ) $type_key = 'topics';
                if ( $post_type === 'sfwd-quiz' ) $type_key = 'quizzes';
                if ( $type_key && isset( $cp[ $type_key ] ) ) {
                    $course_progress_value = isset( $cp[ $type_key ][ $step_id ] ) ? $cp[ $type_key ][ $step_id ] : null;
                } else {
                    $course_progress_value = $cp;
                }
                $debug[] = 'course_progress_usermeta_checked';
            }

            // alt per-course key
            if ( $step_course_id > 0 ) {
                $alt_key = 'course_progress_' . $step_course_id;
                $alt = get_user_meta( $user_id, $alt_key, true );
                if ( is_array( $alt ) ) {
                    $type_key = '';
                    if ( $post_type === 'sfwd-lessons' ) $type_key = 'lessons';
                    if ( $post_type === 'sfwd-topic' ) $type_key = 'topics';
                    if ( $post_type === 'sfwd-quiz' ) $type_key = 'quizzes';
                    if ( $type_key && isset( $alt[ $type_key ] ) ) {
                        $course_progress_alt_value = isset( $alt[ $type_key ][ $step_id ] ) ? $alt[ $type_key ][ $step_id ] : null;
                    } else {
                        $course_progress_alt_value = $alt;
                    }
                    $debug[] = 'course_progress_alt_checked';
                }
            }

            // 4) activity table
            $activity_table = $wpdb->prefix . 'learndash_user_activity';
            $check_table = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $wpdb->esc_like( $activity_table ) ) );
            if ( $check_table ) {
                $statuses = [ 'completed', 'complete', 'passed', 'finished', 'completed-manual', 'manual' ];
                $placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );

                $sql = $wpdb->prepare(
                    "SELECT COUNT(1) FROM {$activity_table} WHERE user_id = %d AND post_id = %d AND activity_status IN ({$placeholders})",
                    array_merge( [ $user_id, $step_id ], $statuses )
                );
                $found = intval( $wpdb->get_var( $sql ) );
                $activity_rows = $found;
                if ( $found > 0 ) {
                    if ( $this->activity_found_indicates_complete ) {
                        $activity_interpretation = 'found_means_COMPLETE';
                    } else {
                        $activity_interpretation = 'found_means_INCOMPLETE';
                    }
                } else {
                    $activity_interpretation = 'found_zero_rows';
                }
                $debug[] = 'activity_table_checked';
            } else {
                $activity_interpretation = 'table_missing';
                $debug[] = 'activity_table_missing';
            }

            // 5) legacy usermeta arrays
            $legacy_hits = [];
            if ( $post_type === 'sfwd-lessons' ) {
                $completed = get_user_meta( $user_id, 'sfwd-lessons_completed', true );
                if ( is_array( $completed ) && in_array( $step_id, $completed, true ) ) {
                    $legacy_hits[] = 'sfwd-lessons_completed';
                }
            } elseif ( $post_type === 'sfwd-topic' ) {
                $completed = get_user_meta( $user_id, 'sfwd-topic_completed', true );
                if ( is_array( $completed ) && in_array( $step_id, $completed, true ) ) {
                    $legacy_hits[] = 'sfwd-topic_completed';
                }
            } elseif ( $post_type === 'sfwd-quiz' ) {
                $completed = get_user_meta( $user_id, 'sfwd-quiz_completed', true );
                if ( is_array( $completed ) && in_array( $step_id, $completed, true ) ) {
                    $legacy_hits[] = 'sfwd-quiz_completed';
                }
            }
            if ( ! empty( $legacy_hits ) ) {
                $legacy_usermeta = $legacy_hits;
                $debug[] = 'legacy_usermeta_hits';
            }

            // Now compute final 'computed_complete' following the plugin's logic:
            // Priority order used by is_step_complete_cached:
            // 1) helper_result (if not null)
            // 2) progression_result (if not null)
            // 3) course_progress_usermeta / alt
            // 4) activity table interpretation
            // 5) legacy usermeta arrays
            $computed_complete = false;
            if ( $helper_result !== null ) {
                $computed_complete = (bool) $helper_result;
                $debug[] = 'final_from_helper';
            } elseif ( $progression_result !== null ) {
                $computed_complete = (bool) $progression_result;
                $debug[] = 'final_from_progression';
            } elseif ( $course_progress_value !== null ) {
                $val = $course_progress_value;
                $computed_complete = ( $val === 1 || $val === '1' || $val === true || ( is_string( $val ) && in_array( strtolower( $val ), [ 'complete', 'completed', 'finished', 'passed' ], true ) ) );
                $debug[] = 'final_from_course_progress';
            } elseif ( $course_progress_alt_value !== null ) {
                $val = $course_progress_alt_value;
                $computed_complete = ( $val === 1 || $val === '1' || $val === true || ( is_string( $val ) && in_array( strtolower( $val ), [ 'complete', 'completed', 'finished', 'passed' ], true ) ) );
                $debug[] = 'final_from_course_progress_alt';
            } elseif ( $activity_rows > 0 ) {
                // Interpret based on site config
                $computed_complete = ( $this->activity_found_indicates_complete ? true : false );
                $debug[] = 'final_from_activity_table';
            } elseif ( ! empty( $legacy_usermeta ) ) {
                $computed_complete = true;
                $debug[] = 'final_from_legacy_usermeta';
            } else {
                $computed_complete = false;
                $debug[] = 'final_default_incomplete';
            }

            $key = $post_type . ':' . $step_id;
            $out[ $key ] = [
                'step_id' => $step_id,
                'post_type' => $post_type,
                'course_id' => $step_course_id,
                'helper_result' => $helper_result,
                'progression_result' => $progression_result,
                'course_progress_value' => $course_progress_value,
                'course_progress_alt_value' => $course_progress_alt_value,
                'activity_table_rows_found' => $activity_rows,
                'activity_interpretation' => $activity_interpretation,
                'legacy_usermeta' => $legacy_usermeta,
                'computed_complete' => (bool) $computed_complete,
                'debug_tokens' => $debug,
            ];
        }

        return rest_ensure_response( $out );
    }

    private function human_readable_type( $post_type ) {
        switch ( $post_type ) {
            case 'sfwd-lessons': return __( 'Lesson', 'bz-ld-step-req' );
            case 'sfwd-topic':   return __( 'Topic', 'bz-ld-step-req' );
            case 'sfwd-quiz':    return __( 'Quiz', 'bz-ld-step-req' );
            default: return ucfirst( $post_type );
        }
    }

    /* ------------------------
     * Completion check (memoized) - used by the plugin at runtime
     * ------------------------ */
    private static function is_step_complete_cached( $user_id, $post_type, $step_id, $course_id = 0 ) {
        global $wpdb;

        static $cache = [];
        $key = "{$user_id}:{$post_type}:{$step_id}";
        if ( array_key_exists( $key, $cache ) ) return $cache[ $key ];

        $complete = false;
        $debug = [];

        // Find course id for the step
        $step_course_id = 0;
        if ( function_exists( 'learndash_get_course_id' ) ) {
            $step_course_id = intval( @learndash_get_course_id( $step_id ) );
            $debug[] = 'learndash_get_course_id=>' . $step_course_id;
        }

        if ( $step_course_id <= 0 ) {
            $try_meta_keys = [ 'course_id', '_course_id', '_lesson_course', 'lesson_course' ];
            foreach ( $try_meta_keys as $mk ) {
                $val = get_post_meta( $step_id, $mk, true );
                if ( ! empty( $val ) && intval( $val ) > 0 ) {
                    $step_course_id = intval( $val );
                    $debug[] = "{$mk}=>" . $step_course_id;
                    break;
                }
            }
        }

        if ( $step_course_id <= 0 && intval( $course_id ) > 0 ) {
            $step_course_id = intval( $course_id );
            $debug[] = 'fallback_course_arg=>' . $step_course_id;
        }

        // 1) Preferred LearnDash helpers
        try {
            if ( $post_type === 'sfwd-lessons' && function_exists( 'learndash_is_lesson_complete' ) ) {
                $helper = (bool) @learndash_is_lesson_complete( $user_id, $step_id, $step_course_id );
                $complete = $helper;
                $debug[] = 'learndash_is_lesson_complete=>' . ($helper ? '1' : '0');
            } elseif ( $post_type === 'sfwd-topic' && function_exists( 'learndash_is_topic_complete' ) ) {
                $helper = (bool) @learndash_is_topic_complete( $user_id, $step_id, $step_course_id );
                $complete = $helper;
                $debug[] = 'learndash_is_topic_complete=>' . ($helper ? '1' : '0');
            } elseif ( $post_type === 'sfwd-quiz' && function_exists( 'learndash_is_quiz_complete' ) ) {
                $helper = (bool) @learndash_is_quiz_complete( $user_id, $step_id, $step_course_id );
                $complete = $helper;
                $debug[] = 'learndash_is_quiz_complete=>' . ($helper ? '1' : '0');
            }
        } catch ( Exception $e ) {
            $debug[] = 'helper-exception:' . $e->getMessage();
        }

        // 2) Generic LD progression check (defensive)
        if ( ! $complete && function_exists( 'learndash_user_progress_is_step_complete' ) ) {
            try {
                $res = @learndash_user_progress_is_step_complete( $user_id, $step_course_id, $step_id );
                $complete = (bool) $res;
                $debug[] = "learndash_user_progress_is_step_complete=>" . ($complete ? '1' : '0');
            } catch ( Exception $e ) {
                $debug[] = 'learndash_user_progress_is_step_complete-ex:' . $e->getMessage();
            }
        }

        // 3) course_progress usermeta (updated by "Mark Complete" UI in many sites)
        if ( ! $complete ) {
            $progress = get_user_meta( $user_id, 'course_progress', true );
            if ( is_array( $progress ) && $step_course_id > 0 ) {
                if ( isset( $progress[ $step_course_id ] ) && is_array( $progress[ $step_course_id ] ) ) {
                    $course_progress = $progress[ $step_course_id ];
                    $type_key = '';
                    if ( $post_type === 'sfwd-lessons' ) $type_key = 'lessons';
                    if ( $post_type === 'sfwd-topic' ) $type_key = 'topics';
                    if ( $post_type === 'sfwd-quiz' ) $type_key = 'quizzes';

                    if ( $type_key && isset( $course_progress[ $type_key ] ) && is_array( $course_progress[ $type_key ] ) ) {
                        $val = isset( $course_progress[ $type_key ][ $step_id ] ) ? $course_progress[ $type_key ][ $step_id ] : null;
                        if ( $val !== null ) {
                            if ( $val === 1 || $val === '1' || $val === true || in_array( strtolower( (string) $val ), [ 'complete', 'completed', 'finished', 'passed' ], true ) ) {
                                $complete = true;
                                $debug[] = 'course_progress_usermeta=>' . ( is_scalar( $val ) ? (string) $val : 'array' );
                            } else {
                                $debug[] = 'course_progress_usermeta_value=>' . ( is_scalar( $val ) ? (string) $val : 'array' );
                            }
                        }
                    }
                }
            } else {
                if ( $step_course_id > 0 ) {
                    $alt_key = 'course_progress_' . $step_course_id;
                    $alt = get_user_meta( $user_id, $alt_key, true );
                    if ( is_array( $alt ) ) {
                        $type_key = '';
                        if ( $post_type === 'sfwd-lessons' ) $type_key = 'lessons';
                        if ( $post_type === 'sfwd-topic' ) $type_key = 'topics';
                        if ( $post_type === 'sfwd-quiz' ) $type_key = 'quizzes';
                        if ( $type_key && isset( $alt[ $type_key ] ) && isset( $alt[ $type_key ][ $step_id ] ) ) {
                            $aval = $alt[ $type_key ][ $step_id ];
                            if ( $aval === 1 || $aval === '1' || $aval === true || in_array( strtolower( (string) $aval ), [ 'complete', 'completed', 'finished', 'passed' ], true ) ) {
                                $complete = true;
                                $debug[] = 'course_progress_alt_usermeta=>' . ( is_scalar( $aval ) ? (string) $aval : 'array' );
                            } else {
                                $debug[] = 'course_progress_alt_value=>' . ( is_scalar( $aval ) ? (string) $aval : 'array' );
                            }
                        }
                    }
                }
            }
        }

        // 4) Inspect learndash_user_activity table. NOTE: some sites use entries differently.
        if ( ! $complete ) {
            $activity_table = $wpdb->prefix . 'learndash_user_activity';
            $check_table = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $wpdb->esc_like( $activity_table ) ) );
            if ( $check_table ) {
                $statuses = [ 'completed', 'complete', 'passed', 'finished', 'completed-manual', 'manual' ];
                $placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );
                $sql = $wpdb->prepare(
                    "SELECT COUNT(1) FROM {$activity_table} WHERE user_id = %d AND post_id = %d AND activity_status IN ({$placeholders})",
                    array_merge( [ $user_id, $step_id ], $statuses )
                );
                $found = intval( $wpdb->get_var( $sql ) );
                $debug[] = 'activity_table_found_rows=>' . $found;
                if ( $found > 0 ) {
                    if ( self::init()->activity_found_indicates_complete ) {
                        $complete = true;
                        $debug[] = 'activity_interpretation=>found_means_COMPLETE';
                    } else {
                        $complete = false;
                        $debug[] = 'activity_interpretation=>found_means_INCOMPLETE';
                    }
                }
            } else {
                $debug[] = 'activity_table_missing';
            }
        }

        // 5) Legacy fallback: check usermeta arrays
        if ( ! $complete ) {
            if ( $post_type === 'sfwd-lessons' ) {
                $completed = get_user_meta( $user_id, 'sfwd-lessons_completed', true );
                if ( is_array( $completed ) && in_array( $step_id, $completed, true ) ) {
                    $complete = true;
                    $debug[] = 'usermeta_lessons_array';
                }
            } elseif ( $post_type === 'sfwd-topic' ) {
                $completed = get_user_meta( $user_id, 'sfwd-topic_completed', true );
                if ( is_array( $completed ) && in_array( $step_id, $completed, true ) ) {
                    $complete = true;
                    $debug[] = 'usermeta_topic_array';
                }
            } elseif ( $post_type === 'sfwd-quiz' ) {
                $completed = get_user_meta( $user_id, 'sfwd-quiz_completed', true );
                if ( is_array( $completed ) && in_array( $step_id, $completed, true ) ) {
                    $complete = true;
                    $debug[] = 'usermeta_quiz_array';
                }
            }
        }

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( sprintf(
                'bz-ld-step-req: check user=%d step=%s:%d course=%d => %s ; debug=%s',
                intval( $user_id ),
                esc_html( $post_type ),
                intval( $step_id ),
                intval( $step_course_id ),
                $complete ? 'COMPLETE' : 'INCOMPLETE',
                implode( '|', $debug )
            ) );
        }

        $cache[ $key ] = !$complete;
        return !$complete;
    }

    /* ------------------------
     * Access filter
     * ------------------------ */
    public function filter_can_user_access_step( $can_access, $post_id, $user_id, $course_id ) {
        if ( $can_access === false ) {
            return false;
        }

        $post_type = get_post_type( $post_id );
        if ( ! in_array( $post_type, $this->supported_post_types, true ) ) {
            return $can_access;
        }

        $enabled = get_post_meta( $post_id, self::META_ENABLED, true );
        if ( empty( $enabled ) || $enabled !== '1' ) {
            return $can_access;
        }

        $raw = get_post_meta( $post_id, self::META_STEPS, true );
        if ( ! is_array( $raw ) || empty( $raw ) ) return $can_access;

        if ( empty( $user_id ) ) return false;

        $all_satisfied = true;
        $any_satisfied = false;
        $count_total = 0;

        foreach ( $raw as $r ) {
            if ( ! is_string( $r ) || strpos( $r, ':' ) === false ) continue;
            list( $ptype, $sid ) = explode( ':', $r );
            $sid = intval( $sid );
            if ( $sid <= 0 ) continue;
            $count_total++;

            $is_completed = self::is_step_complete_cached( $user_id, $ptype, $sid, 0 );
            $satisfied = ( $is_completed === true );

            $all_satisfied = $all_satisfied && $satisfied;
            $any_satisfied = $any_satisfied || $satisfied;
        }

        if ( $count_total === 0 ) return $can_access;

        $mode = get_post_meta( $post_id, self::META_MODE, true );
        $mode = ( $mode === 'all' ) ? 'all' : 'any';

        if ( $mode === 'all' ) {
            return $all_satisfied ? $can_access : false;
        }

        if ( $mode === 'any' ) {
            return $any_satisfied ? $can_access : false;
        }

        return $can_access;
    }

    /* ------------------------
     * Maintain lesson availability timestamp behavior
     * ------------------------ */
    public function filter_ld_access_from( $timestamp, $lesson_id, $user_id ) {
        $post_type = get_post_type( $lesson_id );
        if ( $post_type !== 'sfwd-lessons' ) return $timestamp;

        $enabled = get_post_meta( $lesson_id, self::META_ENABLED, true );
        if ( empty( $enabled ) || $enabled !== '1' ) return $timestamp;

        $raw = get_post_meta( $lesson_id, self::META_STEPS, true );
        if ( ! is_array( $raw ) || empty( $raw ) ) return $timestamp;

        if ( empty( $user_id ) ) {
            return strtotime( '+10 years' );
        }

        $all_satisfied = true;
        $any_satisfied = false;
        $count_total = 0;

        foreach ( $raw as $r ) {
            if ( ! is_string( $r ) || strpos( $r, ':' ) === false ) continue;
            list( $ptype, $sid ) = explode( ':', $r );
            $sid = intval( $sid );
            if ( $sid <= 0 ) continue;
            $count_total++;
            $is_completed = self::is_step_complete_cached( $user_id, $ptype, $sid, 0 );
            $satisfied = ( $is_completed === true );
            $any_satisfied = $any_satisfied || $satisfied;
            $all_satisfied = $all_satisfied && $satisfied;
        }

        if ( $count_total === 0 ) return $timestamp;

        $mode = get_post_meta( $lesson_id, self::META_MODE, true );
        $mode = ( $mode === 'all' ) ? 'all' : 'any';

        if ( ( $mode === 'all' && $all_satisfied ) || ( $mode === 'any' && $any_satisfied ) ) {
            return $timestamp;
        }

        return strtotime( '+10 years' );
    }

    /* ------------------------
     * Availability text
     * ------------------------ */
    public function filter_available_text( $text, $post_id, $user_id ) {
        $enabled = get_post_meta( $post_id, self::META_ENABLED, true );
        if ( empty( $enabled ) || $enabled !== '1' ) return $text;

        $raw = get_post_meta( $post_id, self::META_STEPS, true );
        if ( ! is_array( $raw ) || empty( $raw ) ) return $text;

        if ( empty( $user_id ) ) {
            $titles = $this->get_titles_from_raw( $raw );
            $list = implode( ', ', $titles );
            $login_link = wp_login_url( get_permalink( $post_id ) );
            return '<div class="bz-ld-locked"><p>' . sprintf(
                esc_html__( 'This item requires completion of the following: %1$s. Please log in and complete the required item(s) to access this content.', 'bz-ld-step-req' ),
                esc_html( $list )
            ) . '</p>'
            . '<p><a class="button" href="' . esc_url( $login_link ) . '">' . esc_html__( 'Log in to view', 'bz-ld-step-req' ) . '</a></p></div>';
        }

        $missing = [];
        foreach ( $raw as $r ) {
            if ( ! is_string( $r ) || strpos( $r, ':' ) === false ) continue;
            list( $ptype, $sid ) = explode( ':', $r );
            $sid = intval( $sid );
            if ( $sid <= 0 ) continue;
            if ( ! self::is_step_complete_cached( $user_id, $ptype, $sid, 0 ) ) {
                $title = get_the_title( $sid );
                $label = $this->human_readable_type( $ptype );
                if ( $title ) $missing[] = esc_html( $title ) . ' (' . esc_html( $label ) . ')';
                else $missing[] = sprintf( __( '%s #%d', 'bz-ld-step-req' ), ucfirst( $label ), $sid );
            }
        }

        if ( empty( $missing ) ) return $text;

        $list = implode( ', ', $missing );
        return '<div class="bz-ld-locked"><p>' . sprintf(
            esc_html__( 'You must complete the following before accessing this item: %1$s', 'bz-ld-step-req' ),
            $list
        ) . '</p></div>';
    }

    private function get_titles_from_raw( $raw ) {
        $titles = [];
        foreach ( $raw as $r ) {
            if ( ! is_string( $r ) || strpos( $r, ':' ) === false ) continue;
            list( $ptype, $sid ) = explode( ':', $r );
            $sid = intval( $sid );
            if ( $sid <= 0 ) continue;
            $t = get_the_title( $sid );
            if ( $t ) $titles[] = $t . ' (' . $this->human_readable_type( $ptype ) . ')';
            else $titles[] = sprintf( __( '%s #%d', 'bz-ld-step-req' ), ucfirst( $this->human_readable_type( $ptype ) ), $sid );
        }
        return $titles;
    }

    private function get_missing_prereqs( $post_id, $user_id ) {
        $missing = [];
        $raw = get_post_meta( $post_id, self::META_STEPS, true );
        if ( ! is_array( $raw ) || empty( $raw ) ) return $missing;

        foreach ( $raw as $r ) {
            if ( ! is_string( $r ) || strpos( $r, ':' ) === false ) continue;
            list( $ptype, $sid ) = explode( ':', $r );
            $sid = intval( $sid );
            if ( $sid <= 0 ) continue;
            if ( ! self::is_step_complete_cached( $user_id, $ptype, $sid, 0 ) ) {
                $title = get_the_title( $sid );
                $label = $this->human_readable_type( $ptype );
                $missing[] = ( $title ? $title : sprintf( '%s #%d', ucfirst( $label ), $sid ) ) . ' (' . $label . ')';
            }
        }

        return $missing;
    }

    /**
     * Replaced function: enforce_frontend_access
     *
     * Updates the frontend blocking UI so missing prerequisite items are presented as
     * clickable/tappable links that point to the correct per-CPT URL (not the course-scoped
     * nested LearnDash step URL). This constructs the link by resolving the public rewrite
     * slug for the post type (falls back to a conservative mapping) and the post_name
     * (slug) of the target step, producing URLs like:
     *   - https://example.com/lesson/introduction-to-health-safety-regulations/
     *   - https://example.com/topic/some-topic-slug/
     *   - https://example.com/quiz/quiz-slug/
     *
     * This function keeps prior behavior for access checks but builds correct direct links
     * to the resource so users can tap the item and be taken straight to it.
     */
    public function enforce_frontend_access() {
        if ( is_admin() ) return;
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) return;
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) return;

        if ( ! is_singular() ) return;

        global $post;
        if ( ! $post || ! in_array( get_post_type( $post ), $this->supported_post_types, true ) ) return;

        $user_id = get_current_user_id();

        if ( empty( $user_id ) ) {
            $enabled = get_post_meta( $post->ID, self::META_ENABLED, true );
            if ( $enabled === '1' ) {
                wp_redirect( wp_login_url( get_permalink( $post->ID ) ) );
                exit;
            }
            return;
        }

        $course_id = 0;
        if ( function_exists( 'learndash_get_course_id' ) ) {
            $course_id = intval( learndash_get_course_id( $post->ID ) );
        }

        // Temporarily remove our filter to allow other plugins/LD to run first.
        remove_filter( 'learndash_can_user_access_step', [ $this, 'filter_can_user_access_step' ], 9999 );

        $can = apply_filters( 'learndash_can_user_access_step', true, $post->ID, $user_id, $course_id );

        add_filter( 'learndash_can_user_access_step', [ $this, 'filter_can_user_access_step' ], 9999, 4 );

        if ( $can === false ) {
            wp_die( esc_html__( 'You do not have access to this content.', 'bz-ld-step-req' ), esc_html__( 'Access Denied', 'bz-ld-step-req' ), [ 'back_link' => true ] );
        }

        // Apply our prerequisites check (ensures direct URL access is blocked if missing prereqs).
        $can_after_steps = $this->filter_can_user_access_step( $can, $post->ID, $user_id, $course_id );

        if ( ! $can_after_steps ) {
            // Build a list of missing prerequisites with links to each item (if possible).
            $raw = get_post_meta( $post->ID, self::META_STEPS, true );
            if ( ! is_array( $raw ) || empty( $raw ) ) {
                wp_die( esc_html__( 'You do not have access to this content.', 'bz-ld-step-req' ), esc_html__( 'Access Denied', 'bz-ld-step-req' ), [ 'back_link' => true ] );
            }

            // Helper: resolve a tidy public URL for a step post (lesson/topic/quiz/course).
            $resolve_step_url = function( $step_id ) use ( $course_id ) {
                // First try the canonical WP permalink for the post (this often returns the correct direct CPT permalink).
                $permalink = get_permalink( $step_id );
                if ( ! empty( $permalink ) ) {
                    // Ensure permalink points to a top-level CPT URL. Sometimes LD rewrites can nest; we'll compare.
                    // If permalink looks like /course/.../lesson/... and we want a direct /lesson/slug/ we will build it manually below.
                    $p = wp_parse_url( $permalink );
                    if ( isset( $p['path'] ) ) {
                        // If path contains '/course/' and also '/lesson/' return manual built path instead of nested course path.
                        if ( strpos( $p['path'], '/course/' ) !== false && ( strpos( $p['path'], '/lesson/' ) !== false || strpos( $p['path'], '/topic/' ) !== false || strpos( $p['path'], '/quiz/' ) !== false ) ) {
                            // Fallthrough to building clean per-CPT URL.
                        } else {
                            return $permalink;
                        }
                    } else {
                        return $permalink;
                    }
                }

                // Build a direct public URL using the post type's rewrite slug and the post_name (slug).
                $post_obj = get_post( $step_id );
                if ( ! $post_obj ) {
                    // fallback to course or home
                    if ( ! empty( $course_id ) ) return get_permalink( $course_id );
                    return home_url( '/' );
                }

                $ptype = $post_obj->post_type;

                // Determine the public slug for the CPT
                $rewrite_slug = '';
                $pt_obj = get_post_type_object( $ptype );
                if ( $pt_obj && ! empty( $pt_obj->rewrite ) && ! empty( $pt_obj->rewrite['slug'] ) ) {
                    $rewrite_slug = $pt_obj->rewrite['slug'];
                } else {
                    // Conservative mapping for LearnDash CPTs
                    $map = [
                        'sfwd-lessons' => 'lesson',
                        'sfwd-topic'   => 'topic',
                        'sfwd-quiz'    => 'quiz',
                        'sfwd-courses' => 'course',
                        'sfwd-course'  => 'course',
                    ];
                    if ( isset( $map[ $ptype ] ) ) {
                        $rewrite_slug = $map[ $ptype ];
                    }
                }

                // Use post_name (slug). If missing, fallback to ID path.
                $slug = $post_obj->post_name ? $post_obj->post_name : $step_id;

                if ( ! empty( $rewrite_slug ) ) {
                    // Ensure we return a trailing slash URL
                    $url = home_url( user_trailingslashit( $rewrite_slug . '/' . $slug ) );
                } else {
                    // Unknown CPT rewrite: fallback to get_permalink() or course/home
                    $url = get_permalink( $step_id );
                    if ( empty( $url ) ) {
                        $url = ! empty( $course_id ) ? get_permalink( $course_id ) : home_url( '/' );
                    }
                }

                return $url;
            };

            $missing_links = [];
            foreach ( $raw as $r ) {
                if ( ! is_string( $r ) || strpos( $r, ':' ) === false ) continue;
                list( $ptype, $sid ) = explode( ':', $r );
                $sid = intval( $sid );
                if ( $sid <= 0 ) continue;

                if ( ! self::is_step_complete_cached( $user_id, $ptype, $sid, 0 ) ) {
                    // Title for the link
                    $title = get_the_title( $sid );
                    if ( ! $title ) {
                        $title = sprintf( '%s #%d', ucfirst( str_replace( 'sfwd-', '', $ptype ) ), $sid );
                    }

                    // Resolve the clean URL for the step (direct CPT URL)
                    $url = $resolve_step_url( $sid );

                    // Build anchor element; open in same tab so users can complete the step inline.
                    $anchor = sprintf(
                        '<a href="%s">%s</a>',
                        esc_url( $url ),
                        esc_html( $title . ' (' . $this->human_readable_type( $ptype ) . ')' )
                    );

                    $missing_links[] = $anchor;
                }
            }

            if ( empty( $missing_links ) ) {
                // No missing titles found (unexpected); show generic deny.
                wp_die( esc_html__( 'You do not have access to this content.', 'bz-ld-step-req' ), esc_html__( 'Access Denied', 'bz-ld-step-req' ), [ 'back_link' => true ] );
            }

            // Build an accessible list (clickable/tappable)
            $list = '<ul>';
            foreach ( $missing_links as $link_html ) {
                $list .= '<li>' . $link_html . '</li>';
            }
            $list .= '</ul>';

            $message = '<h2>' . esc_html__( 'This content is locked', 'bz-ld-step-req' ) . '</h2>';
            $message .= '<p>' . esc_html__( 'Please complete the following item(s) before accessing this content:', 'bz-ld-step-req' ) . '</p>';
            $message .= $list;
            $message .= '<p>' . sprintf( '<a class="button" href="%s">%s</a>', esc_url( get_permalink( $course_id ? $course_id : home_url( '/' ) ) ), esc_html__( 'Back to course', 'bz-ld-step-req' ) ) . '</p>';

            wp_die( $message, esc_html__( 'Prerequisites Required', 'bz-ld-step-req' ), [ 'back_link' => true ] );
        }
    }

    private function maybe_migrate_legacy_meta( $post_id ) {
        if ( get_post_type( $post_id ) !== 'sfwd-lessons' ) return;

        $old_enabled = get_post_meta( $post_id, self::OLD_META_ENABLED, true );
        $old_ids = get_post_meta( $post_id, self::OLD_META_IDS, true );
        $old_mode = get_post_meta( $post_id, self::OLD_META_MODE, true );

        if ( empty( $old_enabled ) && empty( $old_ids ) ) return;

        $migrated = get_post_meta( $post_id, self::META_STEPS, true );
        if ( ! empty( $migrated ) ) return;

        $new_enabled = ( $old_enabled === '1' ) ? '1' : '0';
        $new_mode = ( in_array( $old_mode, [ 'any', 'all' ], true ) ? $old_mode : 'any' );
        $new_steps = [];

        if ( is_array( $old_ids ) ) {
            foreach ( $old_ids as $id ) {
                $id = intval( $id );
                if ( $id > 0 ) {
                    $new_steps[] = 'sfwd-lessons:' . $id;
                }
            }
        }

        if ( ! empty( $new_steps ) ) {
            update_post_meta( $post_id, self::META_ENABLED, $new_enabled );
            update_post_meta( $post_id, self::META_MODE, $new_mode );
            update_post_meta( $post_id, self::META_STEPS, $new_steps );
        }
    }
}

BZ_LD_Step_Requirements::init();