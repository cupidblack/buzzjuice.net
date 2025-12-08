<?php
/**
 * Plugin Name: LearnDash Step Requirements (Buzzjuice)
 * Description: Adds "Requirements for Enrollment" to LearnDash Lessons, Topics, and Quizzes. AJAX Select2 search, per-step prerequisites (lessons/topics/quizzes), Any/All mode. Drop into wp-content/mu-plugins/.
 * Version:     2.1.0
 * Author:      Copilot for lemonbuzzjuice
 * License:     GPLv2+
 *
 * Notes:
 *  - Select2 loader & assets expected at: wp-content/shared/select2/
 *      - select2-loader.js
 *      - select2.min.js
 *      - select2.min.css
 *  - Meta keys:
 *      _ld_step_prereq_enabled => '1'|'0'
 *      _ld_step_prereq_mode    => 'any'|'all'
 *      _ld_step_prereq         => array of strings like 'sfwd-lessons:123'
 *
 *  - Supports pre-ld keys migration for lessons (legacy LearnDash lesson-only keys).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BZ_LD_Step_Requirements {
    const META_ENABLED = '_ld_step_prereq_enabled';
    const META_MODE    = '_ld_step_prereq_mode';
    const META_STEPS   = '_ld_step_prereq';

    // legacy keys for migration (LearnDash older implementation)
    const OLD_META_ENABLED = '_ld_lesson_prereq_enabled';
    const OLD_META_IDS     = '_ld_lesson_prereq_ids';
    const OLD_META_MODE    = '_ld_lesson_prereq_mode';

    private static $instance = null;

    // Supported LearnDash step post types
    private $supported_post_types = [ 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz' ];

    private $rest_namespace = 'bz-ld/v1';
    private $rest_route = 'search-steps';

    public static function init() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Admin UI
        add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
        add_action( 'save_post', [ $this, 'save_meta' ], 10, 2 );
        add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_assets' ] );
        add_action( 'admin_head', [ $this, 'admin_inline_styles' ] );

        // REST
        add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );

        // LearnDash integration
        add_filter( 'learndash_can_user_access_step', [ $this, 'filter_can_user_access_step' ], 10, 4 );
        add_filter( 'learndash_lesson_available_from_text', [ $this, 'filter_available_text' ], 10, 3 );
        add_filter( 'learndash_topic_available_from_text', [ $this, 'filter_available_text' ], 10, 3 );
        add_filter( 'learndash_quiz_available_from_text', [ $this, 'filter_available_text' ], 10, 3 );

        // Maintain lesson timestamp behavior
        add_filter( 'ld_lesson_access_from', [ $this, 'filter_ld_access_from' ], 10, 3 );
    }

    /* ------------------------
     * Admin Meta Box
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

        // Migrate legacy lesson meta if any (only for lessons)
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
                // Render any pre-selected items so Select2 shows initial selections
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
                <?php esc_html_e( 'Select steps (Lessons, Topics, or Quizzes) that must be completed before this item becomes available. Use the search box to find items on large sites.', 'bz-ld-step-req' ); ?>
            </p>
        </p>

        <?php
    }

    /* ------------------------
     * Enqueue admin assets and Select2 init (AJAX)
     * ------------------------ */
    public function admin_enqueue_assets( $hook ) {
        // Only load on post edit screens
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

        // Register/enqueue our select2 loader from content shared path (loader handles local->CDN fallback)
        $shared_loader_path = content_url( 'shared/select2/select2-loader.js' );
        wp_register_script( 'bz-select2-loader', $shared_loader_path, [ 'jquery' ], '1.0', true );
        wp_enqueue_script( 'bz-select2-loader' );

        // Enqueue local CSS as primary source (loader will attempt local CSS/JS too)
        $shared_css = content_url( 'shared/select2/select2.min.css' );
        wp_enqueue_style( 'bz-select2-css', $shared_css, [], '4.1.0-rc.0' );

        // Inline JS to initialize our Select2 with AJAX (safe: waits for loader event)
        $rest_url = esc_js( rest_url( sprintf( '%s/%s', $this->rest_namespace, $this->rest_route ) ) );

        $init_js = <<<JS
jQuery(function($){
    function initSelect2(){
        var sel = $('#bz_ld_steps');
        if (!sel.length) return;

        if (typeof sel.select2 !== 'function') {
            // Wait for loader event
            $(document).on('bz.select2.loaded', function(){
                initSelect2();
            });
            // fallback retry
            setTimeout(initSelect2, 1500);
            return;
        }

        // initialize with AJAX-backed server search (Select2)
        sel.select2({
            ajax: {
                url: '{$rest_url}',
                dataType: 'json',
                delay: 250,
                data: function(params){
                    return {
                        q: params.term || '',
                        page: params.page || 1
                    };
                },
                processResults: function(data, params){
                    params.page = params.page || 1;
                    return {
                        results: data.items,
                        pagination: { more: data.more }
                    };
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

        // Ensure server-rendered selected options show
        sel.trigger('change');
    }

    initSelect2();
});
JS;
        wp_add_inline_script( 'bz-select2-loader', $init_js );
    }

    public function admin_inline_styles() {
        echo '<style>#bz_ld_steps{font-size:13px}</style>';
    }

    /* ------------------------
     * Save meta
     * ------------------------ */
    public function save_meta( $post_id, $post ) {
        // Only supported post types
        if ( ! in_array( $post->post_type, $this->supported_post_types, true ) ) return;

        // Verify nonce
        if ( empty( $_POST['bz_ld_step_prereq_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['bz_ld_step_prereq_nonce'] ), 'bz_ld_step_prereq_save' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        // Enabled
        $enabled = ( isset( $_POST['bz_ld_enabled'] ) && $_POST['bz_ld_enabled'] ) ? '1' : '0';
        update_post_meta( $post_id, self::META_ENABLED, $enabled );

        // Mode
        $mode = ( isset( $_POST['bz_ld_mode'] ) && in_array( $_POST['bz_ld_mode'], [ 'any', 'all' ], true ) ) ? sanitize_text_field( wp_unslash( $_POST['bz_ld_mode'] ) ) : 'any';
        update_post_meta( $post_id, self::META_MODE, $mode );

        // Steps (array of strings like 'sfwd-lessons:123')
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
                    // If legacy numeric value provided, assume same post type as the saved post
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
     * REST: search endpoint for steps (Select2) - optimized for large sets
     * ------------------------ */
    public function register_rest_routes() {
        register_rest_route( $this->rest_namespace, '/' . $this->rest_route, [
            'methods'             => 'GET',
            'callback'            => [ $this, 'rest_search_steps' ],
            'permission_callback' => function() {
                // Editors+ should be allowed to pick prerequisites. Adjust capability if needed.
                return current_user_can( 'edit_posts' );
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
    }

    public function rest_search_steps( $request ) {
        global $wpdb;

        $q = $request->get_param( 'q' ) ?: '';
        $page = max( 1, intval( $request->get_param( 'page' ) ?: 1 ) );
        $per_page = 25;
        $offset = ( $page - 1 ) * $per_page;

        // When q is numeric, assume ID lookup (fast)
        if ( is_numeric( $q ) && intval( $q ) > 0 ) {
            $id = intval( $q );
            $post = get_post( $id );
            if ( ! $post ) {
                return rest_ensure_response( [ 'items' => [], 'more' => false ] );
            }
            $ptype = get_post_type( $id );
            if ( ! in_array( $ptype, $this->supported_post_types, true ) ) {
                // allow only supported types
                return rest_ensure_response( [ 'items' => [], 'more' => false ] );
            }
            $title = get_the_title( $id ) ?: $post->post_title;
            return rest_ensure_response( [
                'items' => [
                    [
                        'id' => $ptype . ':' . $id,
                        'text' => $title,
                        'type' => $ptype,
                        'type_label' => $this->human_readable_type( $ptype ),
                    ]
                ],
                'more' => false
            ] );
        }

        $items = [];

        // If there's a non-empty search term, use a lean direct DB search on post_title (fast for large sites).
        if ( $q !== '' ) {
            $like = '%' . $wpdb->esc_like( $q ) . '%';
            $placeholders = implode( ',', array_fill( 0, count( $this->supported_post_types ), '%s' ) );
            $statuses = [ 'publish', 'private', 'draft' ];
            $status_placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );

            // Build SQL - search by post_title and limit/pagination
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

            // Count whether there are more results
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
            // No search term: return recent items (title-ordered) using WP_Query
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
                    $items[] = [
                        'id' => $ptype . ':' . intval( $pid ),
                        'text' => $title,
                        'type' => $ptype,
                        'type_label' => $this->human_readable_type( $ptype ),
                    ];
                }
            }
            $more = ( $page * $per_page ) < intval( $query->found_posts );
        }

        return rest_ensure_response( [
            'items' => $items,
            'more'  => (bool) $more,
        ] );
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
     * Completion check (memoized)
     * ------------------------ */
    private static function is_step_complete_cached( $user_id, $post_type, $step_id, $course_id = 0 ) {
        static $cache = [];
        $key = "{$user_id}:{$post_type}:{$step_id}:{$course_id}";
        if ( array_key_exists( $key, $cache ) ) return $cache[ $key ];

        $complete = false;

        // Pick the best available LearnDash helper based on type
        if ( $post_type === 'sfwd-lessons' && function_exists( 'learndash_is_lesson_complete' ) ) {
            $complete = (bool) learndash_is_lesson_complete( $user_id, $step_id, $course_id );
        } elseif ( $post_type === 'sfwd-topic' && function_exists( 'learndash_is_topic_complete' ) ) {
            $complete = (bool) learndash_is_topic_complete( $user_id, $step_id, $course_id );
        } elseif ( $post_type === 'sfwd-quiz' ) {
            if ( function_exists( 'learndash_is_quiz_complete' ) ) {
                $complete = (bool) learndash_is_quiz_complete( $user_id, $step_id, $course_id );
            } elseif ( function_exists( 'learndash_user_progress_is_step_complete' ) ) {
                // older LD helper signature
                $complete = (bool) learndash_user_progress_is_step_complete( $user_id, $course_id, $step_id );
            }
        } elseif ( function_exists( 'learndash_user_progress_is_step_complete' ) ) {
            // Generic fallback
            $complete = (bool) learndash_user_progress_is_step_complete( $user_id, $course_id, $step_id );
        } else {
            // Legacy fallback: check usermeta arrays if LearnDash functions missing
            if ( $post_type === 'sfwd-lessons' ) {
                $completed = get_user_meta( $user_id, 'sfwd-lessons_completed', true );
                if ( is_array( $completed ) && in_array( $step_id, $completed, true ) ) $complete = true;
            } elseif ( $post_type === 'sfwd-topic' ) {
                $completed = get_user_meta( $user_id, 'sfwd-topic_completed', true );
                if ( is_array( $completed ) && in_array( $step_id, $completed, true ) ) $complete = true;
            } elseif ( $post_type === 'sfwd-quiz' ) {
                $completed = get_user_meta( $user_id, 'sfwd-quiz_completed', true );
                if ( is_array( $completed ) && in_array( $step_id, $completed, true ) ) $complete = true;
            }
        }

        $cache[ $key ] = $complete;
        return $complete;
    }

    /* ------------------------
     * Access filter (unified)
     * ------------------------ */
    public function filter_can_user_access_step( $can_access, $post_id, $user_id, $course_id ) {
        $post_type = get_post_type( $post_id );
        if ( ! in_array( $post_type, $this->supported_post_types, true ) ) return $can_access;

        $enabled = get_post_meta( $post_id, self::META_ENABLED, true );
        if ( empty( $enabled ) || $enabled !== '1' ) return $can_access;

        $raw = get_post_meta( $post_id, self::META_STEPS, true );
        if ( ! is_array( $raw ) || empty( $raw ) ) return $can_access;

        // If not logged in, deny access to locked content
        if ( empty( $user_id ) ) {
            return false;
        }

        // Determine effective course id if not provided
        if ( empty( $course_id ) && function_exists( 'learndash_get_course_id' ) ) {
            $course_id = intval( learndash_get_course_id( $post_id ) );
        }

        $all_complete = true;
        $any_complete = false;
        $count_total = 0;

        foreach ( $raw as $r ) {
            if ( ! is_string( $r ) || strpos( $r, ':' ) === false ) continue;
            list( $ptype, $sid ) = explode( ':', $r );
            $sid = intval( $sid );
            if ( $sid <= 0 ) continue;
            $count_total++;
            $complete = self::is_step_complete_cached( $user_id, $ptype, $sid, $course_id );
            $all_complete = $all_complete && $complete;
            $any_complete = $any_complete || $complete;
        }

        if ( $count_total === 0 ) return $can_access;

        $mode = get_post_meta( $post_id, self::META_MODE, true );
        $mode = ( $mode === 'all' ) ? 'all' : 'any';

        if ( $mode === 'all' && $all_complete ) return true;
        if ( $mode === 'any' && $any_complete ) return true;

        return false;
    }

    /* ------------------------
     * For lessons: maintain availability timestamp behavior
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

        $course_id = 0;
        if ( function_exists( 'learndash_get_course_id' ) ) {
            $course_id = intval( learndash_get_course_id( $lesson_id ) );
        }

        $all_complete = true;
        $any_complete = false;
        $count_total = 0;

        foreach ( $raw as $r ) {
            if ( ! is_string( $r ) || strpos( $r, ':' ) === false ) continue;
            list( $ptype, $sid ) = explode( ':', $r );
            $sid = intval( $sid );
            if ( $sid <= 0 ) continue;
            $count_total++;
            $complete = self::is_step_complete_cached( $user_id, $ptype, $sid, $course_id );
            $any_complete = $any_complete || $complete;
            $all_complete = $all_complete && $complete;
        }

        if ( $count_total === 0 ) return $timestamp;

        $mode = get_post_meta( $lesson_id, self::META_MODE, true );
        $mode = ( $mode === 'all' ) ? 'all' : 'any';

        if ( ( $mode === 'all' && $all_complete ) || ( $mode === 'any' && $any_complete ) ) {
            return $timestamp;
        }

        return strtotime( '+10 years' );
    }

    /* ------------------------
     * Availability text: show missing prerequisites
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

        $course_id = 0;
        if ( function_exists( 'learndash_get_course_id' ) ) {
            $course_id = intval( learndash_get_course_id( $post_id ) );
        }

        $missing = [];
        foreach ( $raw as $r ) {
            if ( ! is_string( $r ) || strpos( $r, ':' ) === false ) continue;
            list( $ptype, $sid ) = explode( ':', $r );
            $sid = intval( $sid );
            if ( $sid <= 0 ) continue;
            if ( ! self::is_step_complete_cached( $user_id, $ptype, $sid, $course_id ) ) {
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

    /* ------------------------
     * Migration: read old lesson meta keys and convert to new meta when appropriate
     * ------------------------ */
    private function maybe_migrate_legacy_meta( $post_id ) {
        if ( get_post_type( $post_id ) !== 'sfwd-lessons' ) return;

        $old_enabled = get_post_meta( $post_id, self::OLD_META_ENABLED, true );
        $old_ids = get_post_meta( $post_id, self::OLD_META_IDS, true );
        $old_mode = get_post_meta( $post_id, self::OLD_META_MODE, true );

        if ( empty( $old_enabled ) && empty( $old_ids ) ) return;

        $migrated = get_post_meta( $post_id, self::META_STEPS, true );
        if ( ! empty( $migrated ) ) return; // already migrated

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