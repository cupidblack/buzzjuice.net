<?php
/**
 * Plugin Name: BuzzJuice LearnDash Access Gate
 * Description: Unified BuzzJuice access control for LearnDash content, Pages and public custom post types. Includes role/subscription gating, existing LearnDash prerequisite support, legacy course-to-post gating and a single configurable interstitial.
 * Version: 5.5.0
 * Author: BuzzJuice
 * License: GPLv2+
 *
 * Install: wp-content/mu-plugins/bzj-learndash-access-gate.php
 *
 * Migration:
 * - Replace the old bzj-learndash-page-gate.php with this file.
 * - Keep learndash-step-requirements.php and subscription_gate_helpers.php.
 * - Existing _bzj_ld_gate and _ld_step_prereq_* metadata are preserved.
 * - Content-level role/subscription gates are stored in _bzj_ld_access_gate.
 * - LearnDash completion requirements may be attached to any supported WordPress
 *   post/page, allowing pages/products/etc. to require completion of a Course,
 *   Lesson, Topic or Quiz.

 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// The shared helper is authoritative for BuzzJuice subscription roles. Load it
// defensively when the shared file is present, without requiring it for sites
// that already load the helper elsewhere.
if ( ! function_exists( 'bzj_allowed_subscription_roles' ) ) {
    $bzj_subscription_helper = WP_CONTENT_DIR . '/shared/subscription_gate_helpers.php';
    if ( is_readable( $bzj_subscription_helper ) ) {
        require_once $bzj_subscription_helper;
    }
}

if ( ! class_exists( 'BZJ_LD_Access_Gate' ) ) {

final class BZJ_LD_Access_Gate {

    const VERSION      = '5.5.0';
    const ACCESS_META  = '_bzj_ld_access_gate';
    const LEGACY_META  = '_bzj_ld_gate';
    const INDEX_OPTION = 'bzj_ld_gate_index';
    const INDEX_V2_OPTION = 'bzj_ld_gate_index_v2';
    const DEFAULT_DELAY = 20;
    const MAX_DELAY = 60;
    const REST_NAMESPACE = 'bzj-ld-access/v1';

    private static $instance = null;

    public static function init() {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->hooks();
        }
        return self::$instance;
    }

    private function hooks() {
        // LearnDash-native settings UI. This is the same mechanism used by the
        // original working Gated Pages implementation.
        add_filter( 'learndash_settings_fields', array( $this, 'learndash_settings_fields' ), 60, 2 );
        add_action( 'save_post', array( $this, 'save_post_settings' ), 40, 2 );
        add_action( 'learndash_metabox_updated_field', array( $this, 'learn_dash_field_updated' ), 20, 4 );

        // Normal WordPress Pages/CPT UI.
        add_action( 'add_meta_boxes', array( $this, 'add_generic_metaboxes' ), 30 );
        add_action( 'save_post', array( $this, 'save_generic_metabox' ), 50, 2 );

        // Admin UX.
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_global_settings' ) );
        add_action( 'wp_ajax_bzj_search_gated_content', array( $this, 'ajax_search_gated_content' ) );

        // Legacy index maintenance.
        add_action( 'bzj_ld_rebuild_index_now', array( $this, 'rebuild_legacy_index' ) );

        // One unified enforcement engine. This replaces the old page-gate
        // enforcement and the friendly prerequisite presentation.
        add_action( 'template_redirect', array( $this, 'enforce' ), -100 );

        // Administrator diagnostic endpoint.
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
    }

    /* ---------------------------------------------------------------------
     * POST TYPE / LEARNDASH HELPERS
     * ------------------------------------------------------------------ */

    private function course_type() {
        return function_exists( 'learndash_get_post_type_slug' )
            ? learndash_get_post_type_slug( 'course' )
            : 'sfwd-courses';
    }

    private function learn_dash_types() {
        $types = array(
            $this->course_type(),
            function_exists( 'learndash_get_post_type_slug' ) ? learndash_get_post_type_slug( 'lesson' ) : 'sfwd-lessons',
            function_exists( 'learndash_get_post_type_slug' ) ? learndash_get_post_type_slug( 'topic' ) : 'sfwd-topic',
            function_exists( 'learndash_get_post_type_slug' ) ? learndash_get_post_type_slug( 'quiz' ) : 'sfwd-quiz',
            'sfwd-question',
            'sfwd-assignment',
            'sfwd-certificates',
        );
        return array_values( array_unique( array_filter( $types ) ) );
    }

    private function gatable_post_types() {
        $types = get_post_types( array( 'public' => true ), 'names' );
        $types = array_merge( $types, array( 'page' ), $this->learn_dash_types() );
        $types = array_values( array_unique( array_filter( $types, 'post_type_exists' ) ) );
        $types = array_values( array_diff( $types, array( 'attachment', 'revision', 'nav_menu_item' ) ) );
        return apply_filters( 'bzj_ld_gate_supported_post_types', $types );
    }

    private function is_learn_dash_type( $post_type ) {
        return in_array( $post_type, $this->learn_dash_types(), true );
    }

    private function current_post_id() {
        if ( isset( $_GET['post'] ) ) {
            return absint( $_GET['post'] );
        }
        if ( isset( $GLOBALS['post'] ) && $GLOBALS['post'] instanceof WP_Post ) {
            return absint( $GLOBALS['post']->ID );
        }
        return 0;
    }

    /* ---------------------------------------------------------------------
     * GLOBAL INTERSTITIAL DEFAULTS
     * ------------------------------------------------------------------ */

    private function global_interstitial_config() {
        $raw = get_option( 'bzj_ld_access_gate_global', array() );
        $raw = is_array( $raw ) ? $raw : array();
        return array(
            'delay_seconds' => max( 0, min( self::MAX_DELAY, isset( $raw['delay_seconds'] ) ? (float) $raw['delay_seconds'] : self::DEFAULT_DELAY ) ),
            'heading' => ! empty( $raw['heading'] ) ? sanitize_text_field( $raw['heading'] ) : '',
            'continue_label' => ! empty( $raw['continue_label'] ) ? sanitize_text_field( $raw['continue_label'] ) : 'Continue to {redirect_label}',
            'back_label' => ! empty( $raw['back_label'] ) ? sanitize_text_field( $raw['back_label'] ) : '← Go Back',
        );
    }

    private function effective_config( $config ) {
        $global = $this->global_interstitial_config();
        if ( 'content' !== ( isset( $config['delay_source'] ) ? $config['delay_source'] : 'global' ) ) {
            $config['delay_seconds'] = $global['delay_seconds'];
        }
        if ( empty( $config['heading'] ) ) {
            $config['heading'] = $global['heading'];
        }
        if ( empty( $config['continue_label'] ) || 'Continue to {redirect_label}' === $config['continue_label'] ) {
            $config['continue_label'] = $global['continue_label'];
        }
        if ( empty( $config['back_label'] ) || '← Go Back' === $config['back_label'] ) {
            $config['back_label'] = $global['back_label'];
        }
        return $config;
    }

    /* ---------------------------------------------------------------------
     * CANONICAL ACCESS CONFIG
     * ------------------------------------------------------------------ */

    private function default_config() {
        return array(
            'enabled'        => false,
            'role_source'    => 'none',
            'require_subscription' => false,
            'require_roles'  => false,
            'roles'          => array(),
            'role_logic'     => 'any',
            'completion_requirements' => array(),
            'completion_logic' => 'all',
            'requirement_logic' => 'all',
            'redirect_mode'  => 'legacy',
            'redirect_url'   => '',
            'redirect_page'  => 0,
            'delay_seconds'  => self::DEFAULT_DELAY,
            'delay_source'   => 'global',
            'custom_context' => false,
            'heading'        => '',
            'message'        => '',
            'continue_label' => 'Continue to {redirect_label}',
            'back_label'     => '← Go Back',
        );
    }

    private function normalize_config( $raw ) {
        $config = wp_parse_args( is_array( $raw ) ? $raw : array(), $this->default_config() );
        $config['enabled'] = ! empty( $config['enabled'] );
        $config['role_source'] = in_array( $config['role_source'], array( 'none', 'subscription', 'custom' ), true ) ? $config['role_source'] : 'none';
        $config['require_subscription'] = ! empty( $config['require_subscription'] ) || 'subscription' === $config['role_source'];
        $config['require_roles'] = ! empty( $config['require_roles'] ) || 'custom' === $config['role_source'];
        $config['roles'] = array_values( array_unique( array_filter( array_map( 'sanitize_key', (array) $config['roles'] ) ) ) );
        $config['role_logic'] = 'all' === $config['role_logic'] ? 'all' : 'any';

        $clean_completion = array();
        foreach ( (array) $config['completion_requirements'] as $req ) {
            if ( ! is_array( $req ) ) continue;
            $type = sanitize_key( isset( $req['post_type'] ) ? $req['post_type'] : '' );
            $id   = absint( isset( $req['post_id'] ) ? $req['post_id'] : 0 );
            if ( ! $type || ! $id || ! post_type_exists( $type ) ) continue;
            if ( ! $this->is_learn_dash_type( $type ) ) continue;
            $clean_completion[] = array( 'post_type' => $type, 'post_id' => $id );
        }
        $config['completion_requirements'] = array_values( $clean_completion );
        $config['completion_logic'] = 'any' === $config['completion_logic'] ? 'any' : 'all';
        $config['requirement_logic'] = 'any' === $config['requirement_logic'] ? 'any' : 'all';

        $config['redirect_mode'] = in_array( $config['redirect_mode'], array( 'legacy', 'url', 'page' ), true ) ? $config['redirect_mode'] : 'legacy';
        $config['redirect_url'] = esc_url_raw( $config['redirect_url'] );
        $config['redirect_page'] = absint( $config['redirect_page'] );
        $config['delay_seconds'] = max( 0, min( self::MAX_DELAY, (float) $config['delay_seconds'] ) );
        $config['delay_source'] = ( isset( $config['delay_source'] ) && 'content' === $config['delay_source'] ) ? 'content' : 'global';
        $config['custom_context'] = ! empty( $config['custom_context'] );
        $config['heading'] = sanitize_text_field( $config['heading'] );
        $config['message'] = wp_kses_post( $config['message'] );
        $config['continue_label'] = sanitize_text_field( $config['continue_label'] );
        $config['back_label'] = sanitize_text_field( $config['back_label'] );
        return $config;
    }

    private function get_access_config( $post_id ) {
        $raw = get_post_meta( $post_id, self::ACCESS_META, true );
        return $this->effective_config( $this->normalize_config( $raw ) );
    }

    /* ---------------------------------------------------------------------
     * LEARNDASH NATIVE SETTINGS UI
     * ------------------------------------------------------------------ */

    public function learndash_settings_fields( $fields, $settings_metabox_key ) {
        $map = array(
            'learndash-course-access-settings',
            'learndash-lesson-access-settings',
            'learndash-topic-access-settings',
            'learndash-quiz-access-settings',
        );

        if ( ! in_array( $settings_metabox_key, $map, true ) ) {
            return $fields;
        }

        $post_id = $this->current_post_id();
        if ( ! $post_id ) {
            $post_id = get_the_ID();
        }

        $config = $this->get_access_config( $post_id );

        $fields['bzj_ld_access_gate'] = array(
            'name' => 'bzj_ld_access_gate',
            'label' => esc_html__( '🔐 BuzzJuice Access Gate', 'bzj-ld-access-gate' ),
            'type' => 'custom',
            'value' => '',
            'default' => '',
            'display_callback' => array( $this, 'render_learn_dash_access_gate_field' ),
        );

        return $fields;
    }

    public function render_learn_dash_access_gate_field() {
        $post_id = $this->current_post_id();
        if ( ! $post_id ) {
            echo '<p>' . esc_html__( 'Content context is unavailable.', 'bzj-ld-access-gate' ) . '</p>';
            return;
        }
        $this->render_access_fields( $post_id, true );
    }

    public function render_gated_post_type_content_field( $requirement_id = 0 ) {
        $requirement_id = absint( $requirement_id ? $requirement_id : $this->current_post_id() );
        if ( ! $requirement_id || ! $this->is_learn_dash_type( get_post_type( $requirement_id ) ) ) {
            echo '<p>' . esc_html__( 'LearnDash content context is unavailable.', 'bzj-ld-access-gate' ) . '</p>';
            return;
        }

        $meta = get_post_meta( $requirement_id, self::LEGACY_META, true );
        $meta = is_array( $meta ) ? $meta : array();
        $items = ! empty( $meta['items'] ) && is_array( $meta['items'] ) ? $meta['items'] : array();

        // Migrate the original pages array in-memory for display.
        if ( empty( $items ) && ! empty( $meta['pages'] ) && is_array( $meta['pages'] ) ) {
            foreach ( $meta['pages'] as $page_id ) {
                $items[] = array( 'post_type' => 'page', 'post_ids' => array( absint( $page_id ) ) );
            }
        }

        $grouped = array();
        foreach ( $items as $item ) {
            if ( ! is_array( $item ) ) continue;
            $type = sanitize_key( isset( $item['post_type'] ) ? $item['post_type'] : 'page' );
            $ids = array_values( array_filter( array_map( 'absint', (array) ( isset( $item['post_ids'] ) ? $item['post_ids'] : array() ) ) ) );
            if ( $ids ) {
                $grouped[ $type ] = array_values( array_unique( array_merge( isset( $grouped[ $type ] ) ? $grouped[ $type ] : array(), $ids ) ) );
            }
        }

        $post_types = $this->selectable_post_types();
        wp_nonce_field( 'bzj_ld_access_gate_save', 'bzj_ld_access_gate_nonce' );
        ?>
        <div class="bzj-gated-post-type-content">
           
            <p class="description"><?php esc_html_e( 'Select a post type, then select the specific content that should be gated by completion of this LearnDash item. Existing Page mappings are preserved.', 'bzj-ld-access-gate' ); ?></p>
            <div id="bzj-gated-content-rows">
                <?php
                if ( $grouped ) {
                    foreach ( $grouped as $type => $ids ) {
                        $this->render_gated_content_row( $type, $ids, $post_types );
                    }
                } else {
                    $this->render_gated_content_row( 'page', array(), $post_types );
                }
                ?>
            </div>
            <p><button type="button" class="button" id="bzj-add-gated-content-row">+ <?php esc_html_e( 'Add Post Type Content', 'bzj-ld-access-gate' ); ?></button></p>
            <p>
                <label><strong><?php esc_html_e( 'Legacy mapped-content logic', 'bzj-ld-access-gate' ); ?></strong></label><br>
                <select name="bzj_ld_logic">
                    <option value="any" <?php selected( isset( $meta['logic'] ) ? $meta['logic'] : 'any', 'any' ); ?>><?php esc_html_e( 'Allow when ANY mapped course requirement is satisfied', 'bzj-ld-access-gate' ); ?></option>
                    <option value="all" <?php selected( isset( $meta['logic'] ) ? $meta['logic'] : 'any', 'all' ); ?>><?php esc_html_e( 'Require ALL mapped course requirements', 'bzj-ld-access-gate' ); ?></option>
                </select>
            </p>
            <p>
                <label><strong><?php esc_html_e( 'Legacy redirect mode', 'bzj-ld-access-gate' ); ?></strong></label><br>
                <select name="bzj_ld_mode">
                    <option value="resume" <?php selected( isset( $meta['mode'] ) ? $meta['mode'] : 'resume', 'resume' ); ?>><?php esc_html_e( 'Resume learner / next incomplete step', 'bzj-ld-access-gate' ); ?></option>
                    <option value="course" <?php selected( isset( $meta['mode'] ) ? $meta['mode'] : 'resume', 'course' ); ?>><?php esc_html_e( 'Course page', 'bzj-ld-access-gate' ); ?></option>
                </select>
            </p>
        </div>
        <?php
        $this->print_gated_content_js( $post_types );
    }

    private function selectable_post_types() {
        $types = $this->gatable_post_types();
        $out = array();
        foreach ( $types as $type ) {
            $obj = get_post_type_object( $type );
            if ( ! $obj ) continue;
            if ( ! empty( $obj->_builtin ) && 'revision' === $type ) continue;
            $label = ! empty( $obj->labels->name ) ? $obj->labels->name : $type;
            $out[ $type ] = $label;
        }
        asort( $out, SORT_NATURAL | SORT_FLAG_CASE );
        return $out;
    }

    private function render_gated_content_row( $type, $ids, $post_types ) {
        $type = isset( $post_types[ $type ] ) ? $type : 'page';
        $posts = $ids ? get_posts( array(
            'post_type' => $type,
            'post__in' => $ids,
            'post_status' => 'any',
            'posts_per_page' => -1,
            'orderby' => 'post__in',
        ) ) : array();
        ?>
        <div class="bzj-gated-row" style="border:1px solid #ddd;padding:10px;margin:8px 0;background:#fff;">
            <p style="margin-top:0;display:flex;gap:8px;align-items:center;">
                <label><strong><?php esc_html_e( 'Post Type', 'bzj-ld-access-gate' ); ?></strong></label>
                <select class="bzj-gated-type" name="bzj_gated_types[]">
                    <?php foreach ( $post_types as $slug => $label ) : ?>
                        <option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $type, $slug ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="button-link-delete bzj-remove-gated-row"><?php esc_html_e( 'Remove', 'bzj-ld-access-gate' ); ?></button>
            </p>
            <select class="bzj-gated-items" name="bzj_gated_ids[<?php echo esc_attr( $type ); ?>][]" multiple="multiple" style="width:100%;min-height:100px;">
                <?php foreach ( $posts as $post ) : ?>
                    <option value="<?php echo esc_attr( $post->ID ); ?>" selected><?php echo esc_html( get_the_title( $post->ID ) . ' (#' . $post->ID . ')' ); ?></option>
                <?php endforeach; ?>
            </select>
            <p class="description"><?php esc_html_e( 'Select one or more items of this post type. Completion of the current LearnDash item will gate the selected content.', 'bzj-ld-access-gate' ); ?></p>
        </div>
        <?php
    }

    private function print_gated_content_js( $post_types ) {
        $options = '';
        foreach ( $post_types as $slug => $label ) {
            $options .= '<option value="' . esc_attr( $slug ) . '">' . esc_html( $label ) . '</option>';
        }
        ?>
        <script>
        jQuery(function($){
            var wrap = $('#bzj-gated-content-rows');
            if (!wrap.length) return;
            function initSelect(row){
                var type = row.find('.bzj-gated-type');
                var items = row.find('.bzj-gated-items');
                if ($.fn.select2 && !items.hasClass('select2-hidden-accessible')) {
                    items.select2({width:'100%', placeholder:'Search content…', minimumInputLength:0, ajax:{url:ajaxurl,dataType:'json',delay:200,data:function(p){return {action:'bzj_search_gated_content',nonce:'<?php echo esc_js( wp_create_nonce( 'bzj_ld_access_gate_search' ) ); ?>',post_type:type.val(),q:p.term||''};},processResults:function(d){return {results:(d.success?d.data:[])};}}});
                }
                type.on('change', function(){
                    var slug = $(this).val();
                    var old = items.attr('name');
                    items.attr('name', 'bzj_gated_ids[' + slug + '][]');
                    items.empty();
                    if ($.fn.select2) items.trigger('change');
                });
            }
            wrap.find('.bzj-gated-row').each(function(){ initSelect($(this)); });
            $('#bzj-add-gated-content-row').on('click', function(e){
                e.preventDefault();
                var row = $('<div class="bzj-gated-row" style="border:1px solid #ddd;padding:10px;margin:8px 0;background:#fff;">'
                    + '<p style="margin-top:0;display:flex;gap:8px;align-items:center;"><label><strong>Post Type</strong></label><select class="bzj-gated-type" name="bzj_gated_types[]"><?php echo $options; ?></select> <button type="button" class="button-link-delete bzj-remove-gated-row">Remove</button></p>'
                    + '<select class="bzj-gated-items" name="bzj_gated_ids[page][]" multiple="multiple" style="width:100%;min-height:100px;"></select>'
                    + '<p class="description">Select one or more items of this post type.</p></div>');
                wrap.append(row); initSelect(row);
            });
            wrap.on('click','.bzj-remove-gated-row',function(){
                $(this).closest('.bzj-gated-row').remove();
            });
        });
        </script>
        <?php
    }

    /* ---------------------------------------------------------------------
     * GENERIC META BOX FOR PAGES / CPTS
     * ------------------------------------------------------------------ */

    public function add_generic_metaboxes() {
        foreach ( $this->gatable_post_types() as $post_type ) {
            if ( $this->is_learn_dash_type( $post_type ) ) continue;
            add_meta_box(
                'bzj-ld-access-gate',
                __( '🔐 BuzzJuice Access Gate', 'bzj-ld-access-gate' ),
                array( $this, 'render_generic_metabox' ),
                $post_type,
                'normal',
                'high'
            );
        }
    }

    public function render_generic_metabox( $post ) {
        if ( ! current_user_can( 'edit_post', $post->ID ) ) {
            echo '<p>' . esc_html__( 'Insufficient permissions.', 'bzj-ld-access-gate' ) . '</p>';
            return;
        }
        $this->render_access_fields( $post->ID, false );
    }

    private function selectable_learn_dash_completion_types() {
        $allowed = array(
            $this->course_type(),
            function_exists( 'learndash_get_post_type_slug' ) ? learndash_get_post_type_slug( 'lesson' ) : 'sfwd-lessons',
            function_exists( 'learndash_get_post_type_slug' ) ? learndash_get_post_type_slug( 'topic' ) : 'sfwd-topic',
            function_exists( 'learndash_get_post_type_slug' ) ? learndash_get_post_type_slug( 'quiz' ) : 'sfwd-quiz',
            'sfwd-assignment',
        );
        $allowed = array_values( array_unique( array_filter( $allowed, 'post_type_exists' ) ) );
        $out = array();
        foreach ( $allowed as $type ) {
            $obj = get_post_type_object( $type );
            if ( $obj ) {
                $out[ $type ] = ! empty( $obj->labels->singular_name ) ? $obj->labels->singular_name : $this->human_type( $type );
            }
        }
        return $out;
    }

    private function render_completion_requirement_row( $req, $types ) {
        $type = ! empty( $req['post_type'] ) && isset( $types[ $req['post_type'] ] ) ? $req['post_type'] : $this->course_type();
        $id   = ! empty( $req['post_id'] ) ? absint( $req['post_id'] ) : 0;
        $selected = $id ? get_post( $id ) : null;
        $name = 'bzj_completion_ids[' . esc_attr( $type ) . '][]';
        ?>
        <div class="bzj-completion-row" style="border:1px solid #ddd;padding:10px;margin:8px 0;background:#fff;">
            <p style="margin-top:0;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <label><strong><?php esc_html_e( 'LearnDash content type', 'bzj-ld-access-gate' ); ?></strong></label>
                <select class="bzj-completion-type">
                    <?php foreach ( $types as $slug => $label ) : ?>
                        <option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $type, $slug ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="button-link-delete bzj-remove-completion-row"><?php esc_html_e( 'Remove', 'bzj-ld-access-gate' ); ?></button>
            </p>
            <select class="bzj-completion-item" name="<?php echo $name; ?>" multiple="multiple" style="width:100%;min-height:70px;">
                <?php if ( $selected ) : ?>
                    <option value="<?php echo esc_attr( $selected->ID ); ?>" selected><?php echo esc_html( get_the_title( $selected->ID ) . ' (#' . $selected->ID . ')' ); ?></option>
                <?php endif; ?>
            </select>
            <p class="description"><?php esc_html_e( 'Select the LearnDash item the user must complete.', 'bzj-ld-access-gate' ); ?></p>
        </div>
        <?php
    }

    private function print_completion_requirement_js( $types ) {
        $options = '';
        foreach ( $types as $slug => $label ) {
            $options .= '<option value="' . esc_attr( $slug ) . '">' . esc_html( $label ) . '</option>';
        }
        ?>
        <script>
        jQuery(function($){
            function initCompletionRow(row){
                var type=row.find('.bzj-completion-type'), item=row.find('.bzj-completion-item');
                function init(){
                    if($.fn.select2 && !item.hasClass('select2-hidden-accessible')){
                        item.select2({
                            width:'100%', placeholder:'Search LearnDash content…', minimumInputLength:0,
                            ajax:{url:ajaxurl,dataType:'json',delay:200,data:function(p){
                                return {action:'bzj_search_gated_content',nonce:'<?php echo esc_js( wp_create_nonce( 'bzj_ld_access_gate_search' ) ); ?>',post_type:type.val(),q:p.term||''};
                            },processResults:function(d){return {results:(d.success?d.data:[])};}}
                        });
                    }
                }
                type.on('change',function(){
                    item.attr('name','bzj_completion_ids['+$(this).val()+'][]');
                    item.empty().trigger('change');
                });
                init();
            }
            $('.bzj-completion-row').each(function(){initCompletionRow($(this));});
            $('.bzj-add-completion-requirement').on('click',function(e){
                e.preventDefault();
                var wrap=$('#'+$(this).data('target'));
                var row=$('<div class="bzj-completion-row" style="border:1px solid #ddd;padding:10px;margin:8px 0;background:#fff;">'
                    +'<p style="margin-top:0;display:flex;gap:8px;align-items:center;flex-wrap:wrap;"><label><strong>LearnDash content type</strong></label><select class="bzj-completion-type"><?php echo $options; ?></select> <button type="button" class="button-link-delete bzj-remove-completion-row">Remove</button></p>'
                    +'<select class="bzj-completion-item" name="bzj_completion_ids[<?php echo esc_attr( $this->course_type() ); ?>][]" multiple="multiple" style="width:100%;min-height:70px;"></select>'
                    +'<p class="description">Select the LearnDash item the user must complete.</p></div>');
                wrap.append(row); initCompletionRow(row);
            });
            $(document).on('click','.bzj-remove-completion-row',function(e){
                e.preventDefault();
                $(this).closest('.bzj-completion-row').remove();
            });
        });
        </script>
        <?php
    }

    private function render_access_fields( $post_id, $learn_dash ) {
        $config = $this->get_access_config( $post_id );
        $roles = wp_roles()->roles;
        $subscription_roles = function_exists( 'bzj_allowed_subscription_roles' ) ? array_values( array_unique( array_map( 'sanitize_key', (array) bzj_allowed_subscription_roles() ) ) ) : array();
        wp_nonce_field( 'bzj_ld_access_gate_save', 'bzj_ld_access_gate_nonce' );
        ?>
        <div class="bzj-access-gate-admin">
            <p><label><input type="checkbox" name="bzj_access_enabled" value="1" <?php checked( $config['enabled'], true ); ?>> <strong><?php esc_html_e( 'Enable Access Gate for this content', 'bzj-ld-access-gate' ); ?></strong></label></p>
            <div class="bzj-access-options" style="display:<?php echo $config['enabled'] ? 'block' : 'none'; ?>;">
                <?php if ( $learn_dash ) : ?>
                    <hr><h4><?php esc_html_e( '1. Gated Post Type Content', 'bzj-ld-access-gate' ); ?></h4>
                    <?php $this->render_gated_post_type_content_field( $post_id ); ?>
                <?php endif; ?>
                <hr><h4><?php esc_html_e( '2. Access Requirement', 'bzj-ld-access-gate' ); ?></h4>
                <div class="bzj-requirement-group">
                    <p><label><input type="checkbox" name="bzj_access_require_subscription" value="1" <?php checked( ! empty( $config['require_subscription'] ), true ); ?>> <strong><?php esc_html_e( 'BuzzJuice Subscription', 'bzj-ld-access-gate' ); ?></strong></label></p>
                    <p class="description" style="margin-left:24px;"><?php esc_html_e( 'Uses the authoritative allowed subscription roles from subscription_gate_helpers.php.', 'bzj-ld-access-gate' ); ?>
                        <?php if ( $subscription_roles ) : ?><br><code><?php echo esc_html( implode( ', ', $subscription_roles ) ); ?></code><?php endif; ?>
                    </p>
                    <p style="margin-top:14px;"><label><input type="checkbox" name="bzj_access_require_roles" value="1" <?php checked( ! empty( $config['require_roles'] ), true ); ?> class="bzj-require-roles-toggle"> <strong><?php esc_html_e( 'WordPress Role(s)', 'bzj-ld-access-gate' ); ?></strong></label></p>
                    <div class="bzj-role-selector" style="<?php echo ! empty( $config['require_roles'] ) ? '' : 'display:none;'; ?> margin-left:24px;">
                        <div style="max-height:180px;overflow:auto;border:1px solid #ddd;padding:8px;background:#fafafa;">
                            <?php foreach ( $roles as $slug => $role ) : ?>
                                <label style="display:block;margin:4px 0;"><input type="checkbox" name="bzj_access_roles[]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, $config['roles'], true ) ); ?>> <?php echo esc_html( translate_user_role( $role['name'] ) ); ?> <code><?php echo esc_html( $slug ); ?></code></label>
                            <?php endforeach; ?>
                        </div>
                        <p><label><strong><?php esc_html_e( 'Selected role logic', 'bzj-ld-access-gate' ); ?></strong></label><br>
                            <select name="bzj_access_role_logic">
                                <option value="any" <?php selected( $config['role_logic'], 'any' ); ?>><?php esc_html_e( 'ANY selected role', 'bzj-ld-access-gate' ); ?></option>
                                <option value="all" <?php selected( $config['role_logic'], 'all' ); ?>><?php esc_html_e( 'ALL selected roles', 'bzj-ld-access-gate' ); ?></option>
                            </select>
                        </p>
                    </div>
                </div>

                <p><label><strong><?php esc_html_e( 'LearnDash completion requirement(s)', 'bzj-ld-access-gate' ); ?></strong></label></p>
                <p class="description"><?php esc_html_e( 'Optional. The user must complete the selected LearnDash course, lesson, topic or quiz before accessing this content. This works for Pages and public custom post types as well as LearnDash content.', 'bzj-ld-access-gate' ); ?></p>
                <div id="bzj-completion-requirements-<?php echo esc_attr( $post_id ); ?>">
                    <?php
                    $completion_types = $this->selectable_learn_dash_completion_types();
                    $requirements = ! empty( $config['completion_requirements'] ) ? $config['completion_requirements'] : array();
                    if ( $requirements ) {
                        foreach ( $requirements as $req ) {
                            $this->render_completion_requirement_row( $req, $completion_types );
                        }
                    } else {
                        $this->render_completion_requirement_row( array(), $completion_types );
                    }
                    ?>
                </div>
                <p><button type="button" class="button bzj-add-completion-requirement" data-target="bzj-completion-requirements-<?php echo esc_attr( $post_id ); ?>">+ <?php esc_html_e( 'Add LearnDash Requirement', 'bzj-ld-access-gate' ); ?></button></p>
                <p>
                    <label><strong><?php esc_html_e( 'LearnDash requirement matching', 'bzj-ld-access-gate' ); ?></strong></label><br>
                    <select name="bzj_access_completion_logic">
                        <option value="all" <?php selected( $config['completion_logic'], 'all' ); ?>><?php esc_html_e( 'ALL selected LearnDash items must be completed', 'bzj-ld-access-gate' ); ?></option>
                        <option value="any" <?php selected( $config['completion_logic'], 'any' ); ?>><?php esc_html_e( 'ANY selected LearnDash item may be completed', 'bzj-ld-access-gate' ); ?></option>
                    </select>
                </p>
                <p>
                    <label><strong><?php esc_html_e( 'Combine role and LearnDash requirements', 'bzj-ld-access-gate' ); ?></strong></label><br>
                    <select name="bzj_access_requirement_logic">
                        <option value="all" <?php selected( $config['requirement_logic'], 'all' ); ?>><?php esc_html_e( 'ALL requirement groups must be satisfied', 'bzj-ld-access-gate' ); ?></option>
                        <option value="any" <?php selected( $config['requirement_logic'], 'any' ); ?>><?php esc_html_e( 'ANY requirement group may be satisfied', 'bzj-ld-access-gate' ); ?></option>
                    </select>
                </p>

                <hr><h4><label><input type="checkbox" name="bzj_access_custom_context" value="1" <?php checked( $config['custom_context'], true ); ?> class="bzj-custom-context-toggle"> <strong><?php esc_html_e( '3. Custom Context', 'bzj-ld-access-gate' ); ?></strong></label></h4>
                
                <div class="bzj-custom-context-options" style="<?php echo $config['custom_context'] ? '' : 'display:none;'; ?>">
                    <p><label><strong><?php esc_html_e( 'Heading', 'bzj-ld-access-gate' ); ?></strong></label><br><input type="text" name="bzj_access_heading" value="<?php echo esc_attr( $config['heading'] ); ?>" style="width:100%;max-width:680px;"></p>
                    <p><label><strong><?php esc_html_e( 'Custom message', 'bzj-ld-access-gate' ); ?></strong></label><br><textarea name="bzj_access_message" rows="7" style="width:100%;max-width:680px;"><?php echo esc_textarea( $config['message'] ); ?></textarea></p>
                    <p class="description"><?php esc_html_e( 'When Custom Context is unchecked, the plugin generates a contextual default message identifying the gated content, requirement and redirect. When checked, your custom heading/message and button labels are used. Placeholders:', 'bzj-ld-access-gate' ); ?> <code>{content_title}</code> <code>{content_type}</code> <code>{requirement}</code> <code>{redirect_label}</code> <code>{delay}</code> <code>{course_title}</code></p>
                    <p><label><strong><?php esc_html_e( 'Continue button', 'bzj-ld-access-gate' ); ?></strong></label><br><input type="text" name="bzj_access_continue_label" value="<?php echo esc_attr( $config['continue_label'] ); ?>" style="width:100%;max-width:680px;"></p>
                    <p><label><strong><?php esc_html_e( 'Go Back button', 'bzj-ld-access-gate' ); ?></strong></label><br><input type="text" name="bzj_access_back_label" value="<?php echo esc_attr( $config['back_label'] ); ?>" style="width:100%;max-width:680px;"></p>
                </div>
                <hr><h4><label><strong><?php esc_html_e( '4. Redirect destination', 'bzj-ld-access-gate' ); ?></strong></label></h4>
                <p>
                    <select name="bzj_access_redirect_mode" style="min-width:320px;">
                        <option value="legacy" <?php selected( $config['redirect_mode'], 'legacy' ); ?>><?php esc_html_e( 'BuzzJuice Subscriptions redirect', 'bzj-ld-access-gate' ); ?></option>
                        <option value="url" <?php selected( $config['redirect_mode'], 'url' ); ?>><?php esc_html_e( 'Custom URL', 'bzj-ld-access-gate' ); ?></option>
                        <option value="page" <?php selected( $config['redirect_mode'], 'page' ); ?>><?php esc_html_e( 'WordPress page', 'bzj-ld-access-gate' ); ?></option>
                    </select>
                </p>
                <p class="bzj-redirect-url-field" style="<?php echo 'url' === $config['redirect_mode'] ? '' : 'display:none;'; ?>"><input type="url" name="bzj_access_redirect_url" value="<?php echo esc_attr( $config['redirect_url'] ); ?>" placeholder="https://example.com/destination/" style="width:100%;max-width:680px;"></p>
                <p class="bzj-redirect-page-field" style="<?php echo 'page' === $config['redirect_mode'] ? '' : 'display:none;'; ?>">
                    <select name="bzj_access_redirect_page" style="min-width:320px;">
                        <option value="0">— <?php esc_html_e( 'Select a WordPress page', 'bzj-ld-access-gate' ); ?> —</option>
                        <?php foreach ( get_pages( array( 'post_status' => array( 'publish', 'private', 'draft' ), 'sort_column' => 'post_title', 'sort_order' => 'ASC' ) ) as $page ) : ?>
                            <option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( $config['redirect_page'], $page->ID ); ?>><?php echo esc_html( $page->post_title . ' (#' . $page->ID . ')' ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </p>
                <p class="description"><?php esc_html_e( 'Default subscription destination is /subscribe/. Custom URL or WordPress page overrides are used when selected.', 'bzj-ld-access-gate' ); ?></p>
                <p><label><strong><?php esc_html_e( '5. Interstitial Countdown', 'bzj-ld-access-gate' ); ?></strong></label><br>
                    <input type="number" name="bzj_access_delay_seconds" value="<?php echo esc_attr( $config['delay_seconds'] ); ?>" min="0" max="60" step="1" style="width:90px;"> <?php esc_html_e( 'seconds automatic redirect delay', 'bzj-ld-access-gate' ); ?>
                </p>
                <p class="description"><?php esc_html_e( 'Range: 0–60 seconds. Default: 20 seconds. Continue is always available immediately; Go Back uses browser history with safe fallbacks.', 'bzj-ld-access-gate' ); ?></p>
            </div>
        </div>
        <script>
        jQuery(function($){
            $('.bzj-access-gate-admin').each(function(){
                var box=$(this), cb=box.find('input[name="bzj_access_enabled"]'), opts=box.find('.bzj-access-options');
                cb.on('change',function(){ opts.toggle(this.checked); });
                var cc=box.find('input[name="bzj_access_custom_context"]'), co=box.find('.bzj-custom-context-options');
                cc.on('change',function(){ co.toggle(this.checked); });
                var rr=box.find('input[name="bzj_access_require_roles"]'), ro=box.find('.bzj-role-selector');
                rr.on('change',function(){ ro.toggle(this.checked); });
                var rm=box.find('select[name="bzj_access_redirect_mode"]');
                function toggleRedirectFields(){
                    var mode=rm.val();
                    box.find('.bzj-redirect-url-field').toggle(mode==='url');
                    box.find('.bzj-redirect-page-field').toggle(mode==='page');
                }
                rm.on('change',toggleRedirectFields); toggleRedirectFields();
            });
        });
        </script>
        <?php
        $this->print_completion_requirement_js( $this->selectable_learn_dash_completion_types() );
    }

    public function save_post_settings( $post_id, $post ) {
        if ( ! $post instanceof WP_Post || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;
        if ( empty( $_POST['bzj_ld_access_gate_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['bzj_ld_access_gate_nonce'] ), 'bzj_ld_access_gate_save' ) ) return;
        $this->save_access_config_from_post( $post_id );

        if ( $this->is_learn_dash_type( $post->post_type ) ) {
            $this->save_gated_post_type_mapping( $post_id );
        }
    }

    public function save_generic_metabox( $post_id, $post ) {
        // save_post_settings handles both LD and generic content when the nonce exists.
        return;
    }

    public function learn_dash_field_updated( $post, $field_key, $new_value, $old_value ) {
        if ( ! ( $post instanceof WP_Post ) ) return;
        if ( 'bzj_ld_access_gate' === $field_key || 'bzj_ld_gated_post_type_content' === $field_key ) {
            if ( current_user_can( 'edit_post', $post->ID ) ) {
                $this->save_access_config_from_post( $post->ID );
                if ( $this->is_learn_dash_type( get_post_type( $post->ID ) ) ) {
                    $this->save_gated_post_type_mapping( $post->ID );
                }
            }
        }
    }

    private function save_access_config_from_post( $post_id ) {
        $roles = isset( $_POST['bzj_access_roles'] ) && is_array( $_POST['bzj_access_roles'] ) ? $_POST['bzj_access_roles'] : array();
        $completion_by_type = isset( $_POST['bzj_completion_ids'] ) && is_array( $_POST['bzj_completion_ids'] ) ? $_POST['bzj_completion_ids'] : array();
        $completion_requirements = array();
        foreach ( $completion_by_type as $type_raw => $ids ) {
            $type = sanitize_key( wp_unslash( $type_raw ) );
            if ( ! $this->is_learn_dash_type( $type ) || ! is_array( $ids ) ) continue;
            foreach ( $ids as $id ) {
                $id = absint( $id );
                if ( $id ) $completion_requirements[] = array( 'post_type' => $type, 'post_id' => $id );
            }
        }
        $config = array(
            'enabled' => ! empty( $_POST['bzj_access_enabled'] ),
            'role_source' => ! empty( $_POST['bzj_access_require_subscription'] ) ? 'subscription' : ( ! empty( $_POST['bzj_access_require_roles'] ) ? 'custom' : 'none' ),
            'require_subscription' => ! empty( $_POST['bzj_access_require_subscription'] ),
            'require_roles' => ! empty( $_POST['bzj_access_require_roles'] ),
            'roles' => array_map( 'sanitize_key', array_map( 'wp_unslash', $roles ) ),
            'role_logic' => isset( $_POST['bzj_access_role_logic'] ) ? sanitize_key( wp_unslash( $_POST['bzj_access_role_logic'] ) ) : 'any',
            'completion_requirements' => $completion_requirements,
            'completion_logic' => isset( $_POST['bzj_access_completion_logic'] ) ? sanitize_key( wp_unslash( $_POST['bzj_access_completion_logic'] ) ) : 'all',
            'requirement_logic' => isset( $_POST['bzj_access_requirement_logic'] ) ? sanitize_key( wp_unslash( $_POST['bzj_access_requirement_logic'] ) ) : 'all',
            'redirect_mode' => isset( $_POST['bzj_access_redirect_mode'] ) ? sanitize_key( wp_unslash( $_POST['bzj_access_redirect_mode'] ) ) : 'legacy',
            'redirect_url' => isset( $_POST['bzj_access_redirect_url'] ) ? wp_unslash( $_POST['bzj_access_redirect_url'] ) : '',
            'redirect_page' => isset( $_POST['bzj_access_redirect_page'] ) ? absint( $_POST['bzj_access_redirect_page'] ) : 0,
            'delay_seconds' => isset( $_POST['bzj_access_delay_seconds'] ) ? max( 0, min( self::MAX_DELAY, absint( $_POST['bzj_access_delay_seconds'] ) ) ) : self::DEFAULT_DELAY,
            'delay_source' => 'content',
            'custom_context' => ! empty( $_POST['bzj_access_custom_context'] ),
            'heading' => isset( $_POST['bzj_access_heading'] ) ? wp_unslash( $_POST['bzj_access_heading'] ) : '',
            'message' => isset( $_POST['bzj_access_message'] ) ? wp_unslash( $_POST['bzj_access_message'] ) : '',
            'continue_label' => isset( $_POST['bzj_access_continue_label'] ) ? wp_unslash( $_POST['bzj_access_continue_label'] ) : '',
            'back_label' => isset( $_POST['bzj_access_back_label'] ) ? wp_unslash( $_POST['bzj_access_back_label'] ) : '',
        );
        update_post_meta( $post_id, self::ACCESS_META, $this->normalize_config( $config ) );
    }

    /* ---------------------------------------------------------------------
     * LEARNDASH ITEM -> POST TYPE CONTENT MAPPING
     * ------------------------------------------------------------------ */

    private function save_gated_post_type_mapping( $requirement_id ) {
        $old = get_post_meta( $requirement_id, self::LEGACY_META, true );
        $old = is_array( $old ) ? $old : array();
        $types = isset( $_POST['bzj_gated_types'] ) && is_array( $_POST['bzj_gated_types'] ) ? $_POST['bzj_gated_types'] : array();
        $ids_by_type = isset( $_POST['bzj_gated_ids'] ) && is_array( $_POST['bzj_gated_ids'] ) ? $_POST['bzj_gated_ids'] : array();
        $items = array();
        $flat_pages = array();
        foreach ( $types as $index => $type_raw ) {
            $type = sanitize_key( wp_unslash( $type_raw ) );
            if ( ! post_type_exists( $type ) || ! in_array( $type, $this->gatable_post_types(), true ) ) continue;
            $ids = isset( $ids_by_type[ $type ] ) && is_array( $ids_by_type[ $type ] ) ? $ids_by_type[ $type ] : array();
            $ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
            if ( ! $ids ) continue;
            $items[] = array( 'post_type' => $type, 'post_ids' => $ids );
            if ( 'page' === $type ) $flat_pages = array_merge( $flat_pages, $ids );
        }

        $meta = array(
            'items' => $items,
            'requirement_id' => absint( $requirement_id ),
            'requirement_type' => sanitize_key( get_post_type( $requirement_id ) ),
            // Preserve the original fields for backward compatibility.
            'pages' => array_values( array_unique( array_map( 'absint', $flat_pages ) ) ),
            'mode' => isset( $_POST['bzj_ld_mode'] ) && 'course' === $_POST['bzj_ld_mode'] ? 'course' : 'resume',
            'logic' => isset( $_POST['bzj_ld_logic'] ) && 'all' === $_POST['bzj_ld_logic'] ? 'all' : 'any',
            // The unified interstitial setting is now the canonical delay. Keep
            // the legacy key only as a compatibility mirror.
            'delay_seconds' => $this->get_access_config( $requirement_id )['delay_seconds'],
        );
        update_post_meta( $requirement_id, self::LEGACY_META, $meta );
        if ( get_post_type( $requirement_id ) === $this->course_type() ) {
            $this->update_legacy_index_for_course( $requirement_id, $old, $meta );
        }
        $this->update_gated_content_index_v2( $requirement_id, $old, $meta );
    }

    private function legacy_item_map( $meta ) {
        $map = array();
        if ( is_array( $meta ) && ! empty( $meta['items'] ) && is_array( $meta['items'] ) ) {
            foreach ( $meta['items'] as $item ) {
                if ( ! is_array( $item ) ) continue;
                $type = sanitize_key( isset( $item['post_type'] ) ? $item['post_type'] : 'page' );
                foreach ( (array) ( isset( $item['post_ids'] ) ? $item['post_ids'] : array() ) as $id ) {
                    $id = absint( $id );
                    if ( $id ) $map[ $id ][] = $type;
                }
            }
        } elseif ( is_array( $meta ) && ! empty( $meta['pages'] ) ) {
            foreach ( (array) $meta['pages'] as $id ) {
                $id = absint( $id );
                if ( $id ) $map[ $id ][] = 'page';
            }
        }
        return $map;
    }

    private function update_legacy_index_for_course( $course_id, $old, $new ) {
        $index = get_option( self::INDEX_OPTION, array() );
        if ( ! is_array( $index ) ) $index = array();
        foreach ( array_keys( $this->legacy_item_map( $old ) ) as $id ) {
            if ( isset( $index[ $id ] ) ) {
                $index[ $id ] = array_values( array_diff( array_map( 'absint', (array) $index[ $id ] ), array( absint( $course_id ) ) ) );
                if ( empty( $index[ $id ] ) ) unset( $index[ $id ] );
            }
        }
        foreach ( array_keys( $this->legacy_item_map( $new ) ) as $id ) {
            if ( empty( $index[ $id ] ) ) $index[ $id ] = array();
            $index[ $id ][] = absint( $course_id );
            $index[ $id ] = array_values( array_unique( array_map( 'absint', $index[ $id ] ) ) );
        }
        update_option( self::INDEX_OPTION, $index, false );
    }

    private function update_gated_content_index_v2( $requirement_id, $old, $new ) {
        $index = get_option( self::INDEX_V2_OPTION, array() );
        if ( ! is_array( $index ) ) $index = array();

        $remove_from = function( $meta ) use ( &$index, $requirement_id ) {
            foreach ( array_keys( $this->legacy_item_map( $meta ) ) as $content_id ) {
                if ( empty( $index[ $content_id ] ) ) continue;
                $kept = array();
                foreach ( (array) $index[ $content_id ] as $entry ) {
                    if ( is_array( $entry ) ) {
                        $same_id = absint( isset( $entry['requirement_id'] ) ? $entry['requirement_id'] : 0 ) === absint( $requirement_id );
                        if ( $same_id ) continue;
                    }
                    $kept[] = $entry;
                }
                if ( $kept ) $index[ $content_id ] = array_values( $kept );
                else unset( $index[ $content_id ]);
            }
        };

        $remove_from( $old );

        $type = sanitize_key( get_post_type( $requirement_id ) );
        foreach ( array_keys( $this->legacy_item_map( $new ) ) as $content_id ) {
            if ( empty( $index[ $content_id ] ) ) $index[ $content_id ] = array();
            $index[ $content_id ][] = array(
                'requirement_id' => absint( $requirement_id ),
                'requirement_type' => $type,
                'mode' => isset( $new['mode'] ) ? ( 'course' === $new['mode'] ? 'course' : 'resume' ) : 'resume',
                'logic' => isset( $new['logic'] ) ? ( 'all' === $new['logic'] ? 'all' : 'any' ) : 'any',
            );
            $unique = array();
            foreach ( $index[ $content_id ] as $entry ) {
                if ( ! is_array( $entry ) ) continue;
                $key = absint( $entry['requirement_id'] ) . '|' . sanitize_key( $entry['requirement_type'] );
                $unique[ $key ] = $entry;
            }
            $index[ $content_id ] = array_values( $unique );
        }

        update_option( self::INDEX_V2_OPTION, $index, false );
    }

    private function enforce_gated_content_index_v2( $post_id ) {
        $index = get_option( self::INDEX_V2_OPTION, array() );
        if ( ! is_array( $index ) || empty( $index[ $post_id ] ) ) return false;

        $entries = (array) $index[ $post_id ];
        $user_id = get_current_user_id();
        $all_ok = true;
        $any_ok = false;
        $require_all = false;
        $target = '';

        foreach ( $entries as $entry ) {
            if ( ! is_array( $entry ) ) continue;
            $req_id = absint( isset( $entry['requirement_id'] ) ? $entry['requirement_id'] : 0 );
            $req_type = sanitize_key( isset( $entry['requirement_type'] ) ? $entry['requirement_type'] : '' );
            if ( ! $req_id || ! $req_type ) continue;

            $meta = get_post_meta( $req_id, self::LEGACY_META, true );
            $logic = is_array( $meta ) && isset( $meta['logic'] ) ? $meta['logic'] : ( isset( $entry['logic'] ) ? $entry['logic'] : 'any' );
            if ( 'all' === $logic ) $require_all = true;

            $complete = false;
            if ( $user_id ) {
                if ( $req_type === $this->course_type() ) {
                    $complete = $this->is_course_complete( $req_id, $user_id );
                } else {
                    $complete = $this->is_step_complete_compatible( $user_id, $req_id, $req_type );
                }
            }

            $any_ok = $any_ok || $complete;
            $all_ok = $all_ok && $complete;

            if ( ! $complete && ! $target ) {
                if ( 'course' === ( isset( $entry['mode'] ) ? $entry['mode'] : 'resume' ) && get_permalink( $req_id ) ) {
                    $target = get_permalink( $req_id );
                } elseif ( $req_type === $this->course_type() ) {
                    $target = $this->resolve_resume_link( $user_id, $req_id ) ?: get_permalink( $req_id );
                } else {
                    $target = get_permalink( $req_id );
                }
            }
        }

        if ( $require_all ? $all_ok : $any_ok ) return false;

        if ( ! $user_id ) {
            $target = $target ?: home_url( '/' );
            $this->render_interstitial( $post_id, $target, $this->legacy_config(), 'Login Required',
                'This {content_type} requires completion of LearnDash content. You will be redirected in {delay} seconds.',
                'completion of the required LearnDash content' );
            return true;
        }

        $target = $target ?: home_url( '/' );
        $this->render_interstitial( $post_id, $target, $this->legacy_config(), 'Completion Required',
            'This {content_type} is available after completing the required LearnDash content. You will be redirected to {redirect_label} in {delay} seconds.',
            'completion of the required LearnDash content' );
        return true;
    }

    public function rebuild_legacy_index() {
        $index = array();
        $index_v2 = array();

        $query = new WP_Query( array(
            'post_type' => $this->learn_dash_types(),
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
        ) );

        foreach ( $query->posts as $requirement_id ) {
            $meta = get_post_meta( $requirement_id, self::LEGACY_META, true );
            $map = $this->legacy_item_map( $meta );
            foreach ( array_keys( $map ) as $content_id ) {
                // Preserve the original course-only integer index.
                if ( get_post_type( $requirement_id ) === $this->course_type() ) {
                    $index[ $content_id ][] = absint( $requirement_id );
                }

                $index_v2[ $content_id ][] = array(
                    'requirement_id' => absint( $requirement_id ),
                    'requirement_type' => sanitize_key( get_post_type( $requirement_id ) ),
                    'mode' => is_array( $meta ) && isset( $meta['mode'] ) && 'course' === $meta['mode'] ? 'course' : 'resume',
                    'logic' => is_array( $meta ) && isset( $meta['logic'] ) && 'all' === $meta['logic'] ? 'all' : 'any',
                );
            }
        }

        foreach ( $index as $id => $requirement_ids ) {
            $index[ $id ] = array_values( array_unique( array_map( 'absint', $requirement_ids ) ) );
        }
        foreach ( $index_v2 as $id => $entries ) {
            $unique = array();
            foreach ( $entries as $entry ) {
                $key = absint( $entry['requirement_id'] ) . '|' . sanitize_key( $entry['requirement_type'] );
                $unique[ $key ] = $entry;
            }
            $index_v2[ $id ] = array_values( $unique );
        }

        update_option( self::INDEX_OPTION, $index, false );
        update_option( self::INDEX_V2_OPTION, $index_v2, false );
    }

    /* ---------------------------------------------------------------------
     * ACCESS EVALUATION
     * ------------------------------------------------------------------ */

    public function access( $post_id, $user_id = 0 ) {
        return self::init()->user_can_access( $post_id, $user_id );
    }

    public function user_can_access( $post_id, $user_id = 0 ) {
        $post_id = absint( $post_id );
        $user_id = $user_id ? absint( $user_id ) : get_current_user_id();
        $config = $this->get_access_config( $post_id );

        if ( ! $config['enabled'] ) {
            return array( 'allowed' => true, 'reason' => 'gate_disabled', 'config' => $config );
        }
        if ( $user_id && user_can( $user_id, 'manage_options' ) ) {
            return array( 'allowed' => true, 'reason' => 'administrator', 'config' => $config );
        }

        $groups = array();
        $subscription_ok = null;
        $roles_ok = null;
        $completion_ok = null;

        if ( ! empty( $config['require_subscription'] ) ) {
            $subscription_ok = $this->subscription_requirement_satisfied( $user_id );
            $groups['subscription'] = $subscription_ok;
        }
        if ( ! empty( $config['require_roles'] ) ) {
            $roles_ok = $this->role_requirement_satisfied( $config, $user_id );
            $groups['roles'] = $roles_ok;
        }
        if ( ! empty( $config['completion_requirements'] ) ) {
            $completion_ok = $this->completion_requirements_satisfied( $config, $user_id );
            $groups['completion'] = $completion_ok;
        }

        if ( empty( $groups ) ) {
            return array( 'allowed' => true, 'reason' => 'no_requirements', 'subscription_ok' => $subscription_ok, 'roles_ok' => $roles_ok, 'completion_ok' => $completion_ok, 'config' => $config );
        }

        $allowed = 'any' === $config['requirement_logic']
            ? in_array( true, array_map( 'boolval', $groups ), true )
            : ! in_array( false, array_map( 'boolval', $groups ), true );

        return array(
            'allowed' => $allowed,
            'reason' => $allowed ? 'requirements_satisfied' : 'requirements_not_satisfied',
            'subscription_ok' => $subscription_ok,
            'roles_ok' => $roles_ok,
            'completion_ok' => $completion_ok,
            'config' => $config,
        );
    }

    private function subscription_requirement_satisfied( $user_id ) {
        if ( ! $user_id ) return false;
        if ( function_exists( 'bzj_user_has_subscription_role' ) ) return (bool) bzj_user_has_subscription_role( $user_id );
        $user = get_userdata( absint( $user_id ) );
        if ( ! $user ) return false;
        $required = function_exists( 'bzj_allowed_subscription_roles' ) ? (array) bzj_allowed_subscription_roles() : array();
        return ! empty( array_intersect( (array) $user->roles, array_map( 'sanitize_key', $required ) ) );
    }

    private function role_requirement_satisfied( $config, $user_id ) {
        if ( empty( $config['require_roles'] ) ) return true;
        $user = get_userdata( absint( $user_id ) );
        if ( ! $user ) return false;
        $required = (array) $config['roles'];
        $required = array_values( array_unique( array_filter( array_map( 'sanitize_key', $required ) ) ) );
        if ( empty( $required ) ) return false;
        $matches = array_intersect( array_map( 'sanitize_key', (array) $user->roles ), $required );
        if ( 'all' === $config['role_logic'] ) return count( $matches ) === count( $required );
        return ! empty( $matches );
    }

    private function completion_requirements_satisfied( $config, $user_id ) {
        $requirements = (array) $config['completion_requirements'];
        if ( empty( $requirements ) ) return true;

        $matches = 0;
        foreach ( $requirements as $req ) {
            $type = isset( $req['post_type'] ) ? sanitize_key( $req['post_type'] ) : '';
            $id   = isset( $req['post_id'] ) ? absint( $req['post_id'] ) : 0;
            if ( ! $type || ! $id ) continue;

            $complete = false;
            if ( $type === $this->course_type() ) {
                if ( function_exists( 'learndash_course_completed' ) ) {
                    try { $complete = (bool) learndash_course_completed( $user_id, $id ); } catch ( Throwable $e ) { $complete = false; }
                }
                if ( ! $complete ) {
                    $progress = get_user_meta( $user_id, 'course_progress', true );
                    $complete = is_array( $progress ) && isset( $progress[ $id ] ) && ! empty( $progress[ $id ]['completed'] );
                }
            } else {
                $complete = $this->is_step_complete_compatible( $user_id, $id, $type );
            }

            if ( $complete ) $matches++;
        }

        return 'any' === $config['completion_logic'] ? $matches > 0 : $matches === count( $requirements );
    }

    public function enforce() {
        if ( is_admin() || ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) return;
        if ( is_feed() || is_preview() || ! is_singular() ) return;
        global $post;
        if ( ! $post || empty( $post->ID ) ) return;
        $post_id = absint( $post->ID );
        if ( ! in_array( get_post_type( $post_id ), $this->gatable_post_types(), true ) ) return;
        if ( current_user_can( 'manage_options' ) ) return;

        // Explicit BuzzJuice role/subscription gate first.
        $config = $this->get_access_config( $post_id );
        if ( $config['enabled'] ) {
            $result = $this->user_can_access( $post_id );
            if ( ! $result['allowed'] ) {
                $this->deny_access( $post_id, $result );
                return;
            }
        }

        // Preserve existing prerequisite metadata and intercept the old plugin's
        // denial before its wp_die handler can render.
        if ( $this->has_existing_prerequisites( $post_id ) ) {
            $prereq = $this->evaluate_existing_prerequisites( $post_id );
            if ( ! $prereq['allowed'] ) {
                $this->deny_prerequisite( $post_id, $prereq );
                return;
            }
        }

        // Unified LearnDash-item -> post type content gate. This supports
        // courses, lessons, topics and quizzes as the completion requirement.
        if ( $this->enforce_gated_content_index_v2( $post_id ) ) {
            return;
        }

        // Existing course-to-post gate retained for old installations.
        $this->enforce_legacy_mapping( $post_id );
    }

    /* ---------------------------------------------------------------------
     * EXISTING LEARNDASH STEP PREREQUISITES
     * ------------------------------------------------------------------ */

    private function get_prereq_data( $post_id ) {
        $enabled = get_post_meta( $post_id, '_ld_step_prereq_enabled', true );
        $mode = get_post_meta( $post_id, '_ld_step_prereq_mode', true );
        $raw = get_post_meta( $post_id, '_ld_step_prereq', true );

        if ( '' === $enabled && '' === $raw ) {
            $enabled = get_post_meta( $post_id, '_ld_lesson_prereq_enabled', true );
            $mode = get_post_meta( $post_id, '_ld_lesson_prereq_mode', true );
            $raw = get_post_meta( $post_id, '_ld_lesson_prereq_ids', true );
        }
        return array( 'enabled' => ! empty( $enabled ) || ! empty( $raw ), 'mode' => 'all' === strtolower( (string) $mode ) ? 'all' : 'any', 'raw' => $raw );
    }

    private function has_existing_prerequisites( $post_id ) {
        $data = $this->get_prereq_data( $post_id );
        return $data['enabled'] && ! empty( $data['raw'] );
    }

    private function normalize_prereq_steps( $raw ) {
        if ( ! is_array( $raw ) ) {
            $raw = array( $raw );
        }
        $out = array();
        foreach ( $raw as $entry ) {
            $type = '';
            $id = 0;
            if ( is_string( $entry ) && false !== strpos( $entry, ':' ) ) {
                list( $type, $id ) = array_pad( explode( ':', $entry, 2 ), 2, 0 );
                $type = sanitize_key( $type );
                $id = absint( $id );
            } elseif ( is_array( $entry ) && isset( $entry['type'], $entry['id'] ) ) {
                $type = sanitize_key( $entry['type'] );
                $id = absint( $entry['id'] );
            } else {
                // Very old lesson-only storage sometimes contained numeric IDs.
                $type = 'sfwd-lessons';
                $id = absint( $entry );
            }
            if ( $id > 0 ) {
                $out[] = array( 'type' => $type, 'id' => $id );
            }
        }
        return $out;
    }

    private function evaluate_existing_prerequisites( $post_id ) {
        $data = $this->get_prereq_data( $post_id );
        $steps = $this->normalize_prereq_steps( $data['raw'] );
        if ( empty( $steps ) ) return array( 'allowed' => true, 'missing' => array(), 'mode' => $data['mode'] );

        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            $missing = array();
            foreach ( $steps as $step ) $missing[] = $this->prereq_descriptor( $step['id'], $step['type'] );
            return array( 'allowed' => false, 'missing' => $missing, 'mode' => $data['mode'], 'reason' => 'login_required' );
        }

        $missing = array();
        $completed = 0;
        foreach ( $steps as $step ) {
            if ( $this->is_step_complete_compatible( $user_id, $step['id'], $step['type'] ) ) {
                $completed++;
            } else {
                $missing[] = $this->prereq_descriptor( $step['id'], $step['type'] );
            }
        }
        $allowed = 'all' === $data['mode'] ? ( $completed === count( $steps ) ) : ( $completed > 0 );
        return array( 'allowed' => $allowed, 'missing' => $missing, 'mode' => $data['mode'], 'total' => count( $steps ), 'completed' => $completed );
    }

    private function prereq_descriptor( $id, $type = '' ) {
        $type = $type ? $type : get_post_type( $id );
        return array( 'id' => absint( $id ), 'title' => get_the_title( $id ), 'type' => $type, 'url' => get_permalink( $id ) );
    }

    private function is_step_complete_compatible( $user_id, $step_id, $post_type ) {
        $step_course_id = function_exists( 'learndash_get_course_id' ) ? absint( @learndash_get_course_id( $step_id ) ) : 0;
        if ( ! $step_course_id ) {
            foreach ( array( 'course_id', '_course_id', '_lesson_course', 'lesson_course' ) as $key ) {
                $candidate = absint( get_post_meta( $step_id, $key, true ) );
                if ( $candidate ) { $step_course_id = $candidate; break; }
            }
        }

        $helper_result = null;
        try {
            if ( 'sfwd-lessons' === $post_type && function_exists( 'learndash_is_lesson_complete' ) ) {
                $helper_result = (bool) @learndash_is_lesson_complete( $user_id, $step_id, $step_course_id );
            } elseif ( 'sfwd-topic' === $post_type && function_exists( 'learndash_is_topic_complete' ) ) {
                $helper_result = (bool) @learndash_is_topic_complete( $user_id, $step_id, $step_course_id );
            } elseif ( 'sfwd-quiz' === $post_type && function_exists( 'learndash_is_quiz_complete' ) ) {
                $helper_result = (bool) @learndash_is_quiz_complete( $user_id, $step_id, $step_course_id );
            }
        } catch ( Throwable $e ) {
            $helper_result = null;
        }
        // Match the existing prerequisite plugin's precedence: a LearnDash
        // helper result is authoritative even when it is false.
        if ( null !== $helper_result ) return $helper_result;

        $progression = null;
        if ( function_exists( 'learndash_user_progress_is_step_complete' ) && $step_course_id ) {
            try { $progression = (bool) @learndash_user_progress_is_step_complete( $user_id, $step_course_id, $step_id ); } catch ( Throwable $e ) { $progression = null; }
        }
        if ( null !== $progression ) return $progression;

        // Mirror the installed learndash-step-requirements.php fallback chain.
        global $wpdb;
        $activity_table = $wpdb->prefix . 'learndash_user_activity';
        $table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $activity_table ) ) );
        if ( $table_exists ) {
            $statuses = array( 'completed', 'complete', 'passed', 'finished', 'completed-manual', 'manual' );
            $placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );
            $sql = $wpdb->prepare(
                "SELECT COUNT(1) FROM {$activity_table} WHERE user_id = %d AND post_id = %d AND activity_status IN ({$placeholders})",
                array_merge( array( absint( $user_id ), absint( $step_id ) ), $statuses )
            );
            if ( (int) $wpdb->get_var( $sql ) > 0 ) return true;
        }
        $legacy_key = $post_type . '_completed';
        $completed = get_user_meta( $user_id, $legacy_key, true );
        if ( is_array( $completed ) && in_array( $step_id, $completed, true ) ) return true;

        $type_key = 'sfwd-lessons' === $post_type ? 'lessons' : ( 'sfwd-topic' === $post_type ? 'topics' : ( 'sfwd-quiz' === $post_type ? 'quizzes' : '' ) );
        if ( $type_key && $step_course_id ) {
            $progress = get_user_meta( $user_id, 'course_progress', true );
            $value = null;
            if ( is_array( $progress ) && isset( $progress[ $step_course_id ][ $type_key ][ $step_id ] ) ) {
                $value = $progress[ $step_course_id ][ $type_key ][ $step_id ];
            }
            if ( null === $value ) {
                $alt = get_user_meta( $user_id, 'course_progress_' . $step_course_id, true );
                if ( is_array( $alt ) && isset( $alt[ $type_key ][ $step_id ] ) ) $value = $alt[ $type_key ][ $step_id ];
            }
            if ( null !== $value ) return $this->completion_value( $value );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'learndash_user_activity';
        if ( $this->table_exists( $table ) ) {
            $statuses = array( 'completed', 'complete', 'passed', 'finished', 'completed-manual', 'manual' );
            $placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );
            $sql = $wpdb->prepare( "SELECT COUNT(1) FROM {$table} WHERE user_id=%d AND post_id=%d AND activity_status IN ({$placeholders})", array_merge( array( $user_id, $step_id ), $statuses ) );
            if ( intval( $wpdb->get_var( $sql ) ) > 0 ) {
                $interpret = apply_filters( 'bz_ld_activity_table_found_indicates_complete', true );
                return (bool) $interpret;
            }
        }

        $legacy_key = $post_type . '_completed';
        $completed = get_user_meta( $user_id, $legacy_key, true );
        return is_array( $completed ) && in_array( $step_id, array_map( 'absint', $completed ), true );
    }

    private function table_exists( $table ) {
        global $wpdb;
        return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) === $table;
    }

    private function completion_value( $value ) {
        return $value === 1 || $value === '1' || true === $value || ( is_string( $value ) && in_array( strtolower( $value ), array( 'complete', 'completed', 'finished', 'passed' ), true ) );
    }

    /* ---------------------------------------------------------------------
     * LEGACY COURSE COMPLETION / RESUME
     * ------------------------------------------------------------------ */

    private function enforce_legacy_mapping( $post_id ) {
        $index = get_option( self::INDEX_OPTION, null );
        if ( null === $index ) {
            $this->rebuild_legacy_index();
            $index = get_option( self::INDEX_OPTION, array() );
        }
        if ( empty( $index[ $post_id ] ) ) return;
        $course_ids = array_values( array_filter( array_map( 'absint', (array) $index[ $post_id ] ) ) );
        if ( empty( $course_ids ) ) return;
        $user_id = get_current_user_id();

        if ( ! $user_id ) {
            $this->render_interstitial( $post_id, get_permalink( $course_ids[0] ), $this->legacy_config(), 'Login Required', 'This content is available to registered learners. You will be redirected to the course in {delay} seconds.', 'Registered learner access' );
            return;
        }

        $any = false; $all = true; $require_all = false; $target = '';
        foreach ( $course_ids as $course_id ) {
            $meta = get_post_meta( $course_id, self::LEGACY_META, true );
            if ( is_array( $meta ) && 'all' === ( isset( $meta['logic'] ) ? $meta['logic'] : 'any' ) ) $require_all = true;
            $done = $this->is_course_complete( $course_id, $user_id );
            $any = $any || $done; $all = $all && $done;
            if ( ! $this->has_started_course( $course_id, $user_id ) ) { $target = get_permalink( $course_id ); break; }
            $mode = is_array( $meta ) && isset( $meta['mode'] ) ? $meta['mode'] : 'resume';
            if ( 'course' === $mode ) { $target = get_permalink( $course_id ); break; }
            $target = $this->resolve_resume_link( $user_id, $course_id ) ?: get_permalink( $course_id );
            break;
        }
        if ( $require_all ? $all : $any ) return;
        $this->render_interstitial( $post_id, $target, $this->legacy_config(), 'Continue Your Course', 'This {content_type} is available after completing the required course. You will be redirected to your next course step in {delay} seconds.', 'Complete the required course' );
    }

    private function is_course_complete( $course_id, $user_id ) {
        if ( function_exists( 'learndash_course_completed' ) ) return (bool) learndash_course_completed( $user_id, $course_id );
        $progress = get_user_meta( $user_id, 'course_progress', true );
        return is_array( $progress ) && isset( $progress[ $course_id ] ) && ! empty( $progress[ $course_id ]['completed'] );
    }

    private function has_started_course( $course_id, $user_id ) {
        if ( function_exists( 'learndash_user_get_course_progress' ) ) {
            $progress = learndash_user_get_course_progress( $user_id, $course_id );
            return ! empty( $progress );
        }
        return false;
    }

    private function resolve_resume_link( $user_id, $course_id ) {
        if ( function_exists( 'learndash_course_get_next_step' ) ) {
            $next = learndash_course_get_next_step( $course_id, 0, $user_id );
            if ( $next ) return get_permalink( $next );
        }
        if ( function_exists( 'learndash_get_course_steps' ) ) {
            $steps = learndash_get_course_steps( $course_id );
            foreach ( (array) $steps as $step_id ) {
                $type = get_post_type( $step_id );
                if ( 'sfwd-lessons' === $type && function_exists( 'learndash_is_lesson_complete' ) && ! learndash_is_lesson_complete( $user_id, $step_id, $course_id ) ) return get_permalink( $step_id );
                if ( 'sfwd-topic' === $type && function_exists( 'learndash_is_topic_complete' ) && ! learndash_is_topic_complete( $user_id, $step_id, $course_id ) ) return get_permalink( $step_id );
            }
        }
        return '';
    }

    /* ---------------------------------------------------------------------
     * REDIRECT / INTERSTITIAL
     * ------------------------------------------------------------------ */

    private function deny_access( $post_id, $result ) {
        $config = $result['config'];
        $target = $this->resolve_redirect( $post_id, $config, $result );
        $requirement = $this->describe_requirement( $config );
        $heading = $config['heading'] ?: $this->default_requirement_heading( $config );
        $message = $config['custom_context'] ? $config['message'] : '';
        $this->render_interstitial( $post_id, $target, $config, $heading, $message, $requirement );
    }

    private function deny_prerequisite( $post_id, $prereq ) {
        $missing_items = ! empty( $prereq['missing'] ) ? (array) $prereq['missing'] : array();
        $missing = ! empty( $missing_items[0] ) ? $missing_items[0] : array();
        $target = ! empty( $missing['url'] ) ? $missing['url'] : $this->parent_or_course_url( $post_id );

        $labels = array();
        foreach ( $missing_items as $item ) {
            $title = ! empty( $item['title'] ) ? $item['title'] : 'Required LearnDash content';
            $type  = ! empty( $item['type'] ) ? $this->human_type( $item['type'] ) : 'LearnDash content';
            $labels[] = $title . ' (' . $type . ')';
        }
        if ( empty( $labels ) ) $labels[] = 'the required prerequisite';

        $requirement = ( 'all' === $prereq['mode'] )
            ? 'completion of all required items: ' . implode( ', ', $labels )
            : 'completion of any required item: ' . implode( ', ', $labels );

        $config = $this->legacy_config();
        $config['heading'] = 'Pre-requisite Required';
        $config['continue_label'] = 'Continue to {redirect_label}';
        $message = '<h4>{content_title}</h4> <br>{content_type} requires completion of the following:

{requirement}.

You will be redirected to {redirect_label} in:';
        $this->render_interstitial( $post_id, $target, $config, $config['heading'], $message, $requirement );
    }

    private function legacy_config() {
        $config = $this->default_config();
        $config = $this->effective_config( $config );
        // The original plugin used the course mapping delay; the unified gate
        // now defaults to 20 seconds. Existing legacy records keep their delay.
        return $config;
    }

    private function resolve_redirect( $post_id, $config, $result = array() ) {
        if ( 'url' === $config['redirect_mode'] && $config['redirect_url'] ) return $config['redirect_url'];
        if ( 'page' === $config['redirect_mode'] && $config['redirect_page'] ) {
            $url = get_permalink( $config['redirect_page'] );
            if ( $url ) return $url;
        }
        // If a subscription role is one of the unsatisfied requirements, keep
        // the original BuzzJuice subscription destination as the default.
        $subscription_failed = isset( $result['subscription_ok'] ) && false === $result['subscription_ok'];
        $completion_failed = isset( $result['completion_ok'] ) && false === $result['completion_ok'];
        if ( ! empty( $config['require_subscription'] ) && $subscription_failed ) {
            return home_url( '/subscribe/' );
        }

        // A LearnDash completion requirement has a more useful default target:
        // the first required item that the learner has not yet completed.
        if ( $completion_failed && ! empty( $config['completion_requirements'] ) ) {
            $user_id = get_current_user_id();
            foreach ( $config['completion_requirements'] as $req ) {
                $required_id = absint( isset( $req['post_id'] ) ? $req['post_id'] : 0 );
                $required_type = isset( $req['post_type'] ) ? sanitize_key( $req['post_type'] ) : '';
                if ( ! $required_id || ! $required_type ) continue;

                $complete = false;
                if ( $required_type === $this->course_type() ) {
                    if ( $user_id && function_exists( 'learndash_course_completed' ) ) {
                        try { $complete = (bool) learndash_course_completed( $user_id, $required_id ); } catch ( Throwable $e ) { $complete = false; }
                    }
                } elseif ( $user_id ) {
                    $complete = $this->is_step_complete_compatible( $user_id, $required_id, $required_type );
                }

                if ( ! $complete ) {
                    $required_url = get_permalink( $required_id );
                    if ( $required_url ) return $required_url;
                }
            }
        }

        // Existing LearnDash context.
        $course_id = $this->get_course_id( $post_id );
        if ( $course_id ) {
            $resume = $this->resolve_resume_link( get_current_user_id(), $course_id );
            if ( $resume ) return $resume;
            $course_url = get_permalink( $course_id );
            if ( $course_url ) return $course_url;
        }
        return $this->parent_or_course_url( $post_id );
    }

    private function original_subscription_redirect() {
        // Compatibility constants/options used by previous BuzzJuice builds.
        $candidates = array(
            defined( 'BZJ_LD_GATE_SUBSCRIPTION_REDIRECT' ) ? BZJ_LD_GATE_SUBSCRIPTION_REDIRECT : '',
            get_option( 'bzj_ld_subscription_redirect_url', '' ),
            get_option( 'bzj_subscription_redirect_url', '' ),
            get_option( 'bzj_ld_gate_redirect_url', '' ),
        );
        foreach ( $candidates as $url ) {
            $url = esc_url_raw( $url );
            if ( $url ) return $url;
        }
        // Final fallback intentionally points to the existing subscription
        // page if it can be resolved by common BuzzJuice slugs.
        $subscribe_url = home_url( '/subscribe/' );
        if ( $subscribe_url ) return $subscribe_url;
        foreach ( array( 'subscription-plans', 'subscriptions', 'subscription' ) as $slug ) {
            $page = get_page_by_path( $slug );
            if ( $page ) return get_permalink( $page );
        }
        return home_url( '/' );
    }

    private function render_interstitial( $current_id, $target_url, $config, $heading, $message, $requirement ) {
        nocache_headers();
        header( 'X-Robots-Tag: noindex, nofollow, noarchive', true );
        status_header( 200 );

        $current_url = get_permalink( $current_id );
        $target_url = $this->safe_target_url( $target_url );
        $target_url = $this->resolve_redirect_loop( $current_url, $target_url, $current_id );
        if ( ! $target_url ) $target_url = home_url( '/' );

        $delay = max( 0, min( self::MAX_DELAY, (float) ( isset( $config['delay_seconds'] ) ? $config['delay_seconds'] : self::DEFAULT_DELAY ) ) );
        $content_title = get_the_title( $current_id );
        $content_type = $this->human_type( get_post_type( $current_id ) );
        $course_id = $this->get_course_id( $current_id );
        $course_title = $course_id ? get_the_title( $course_id ) : '';
        $redirect_label = $this->url_label( $target_url );
        if ( empty( $config['custom_context'] ) ) {
            // Generate a contextual default message. Subscription gates use a
            // member-oriented presentation; completion/prerequisite gates use
            // a requirement-oriented presentation.
            $heading = $heading ?: ( ! empty( $config['require_subscription'] ) && empty( $config['completion_requirements'] ) && empty( $config['require_roles'] ) ? 'Subscription Required' : 'Access Required' );
            if ( ! empty( $config['require_subscription'] ) && empty( $config['completion_requirements'] ) && empty( $config['require_roles'] ) ) {
                $message = '<h4>{content_title}</h4> <br>{content_type} is only available to:

{requirement}.

You will be redirected to {redirect_label} in:';
            } elseif ( ! empty( $config['completion_requirements'] ) && empty( $config['require_subscription'] ) && empty( $config['require_roles'] ) ) {
                $message = '<h4>{content_title}</h4> <br>{content_type} requires completion of the following:

{requirement}.

You will be redirected to {redirect_label} in:';
            } else {
                $message = '<h4>{content_title}</h4> <br>{content_type} requires:

{requirement}.

You will be redirected to {redirect_label} in:';
            }
        } else {
            $heading = $config['heading'] ?: ( $heading ?: 'Access Required' );
            $message = $config['message'] ?: ( $message ?: 'This {content_type} requires {requirement}. You will be redirected to {redirect_label} in {delay} seconds.' );
        }
        $message = strtr( $message, array(
            '{content_title}' => esc_html( $content_title ),
            '{content_type}' => esc_html( $content_type ),
            '{requirement}' => esc_html( $requirement ),
            '{redirect_label}' => esc_html( $redirect_label ),
            '{delay}' => esc_html( (string) (int) ceil( $delay ) ),
            '{course_title}' => esc_html( $course_title ),
        ) );
        $message = wp_kses_post( wpautop( $message ) );
        $continue = strtr( $config['continue_label'] ?: 'Continue to {redirect_label}', array( '{redirect_label}' => $redirect_label ) );
        $back = $config['back_label'] ?: '← Go Back';
        $back_url = wp_get_referer();
        if ( ! $back_url || $this->same_url( $back_url, $current_url ) ) {
            $back_url = $this->parent_url( $current_url );
        }
        if ( $this->same_url( $back_url, $target_url ) || $this->same_url( $back_url, $current_url ) ) {
            $back_url = '';
        }
        $home = home_url( '/' );

        do_action( 'bzj_ld_gate_interstitial_shown', get_current_user_id(), $this->get_course_id( $current_id ), $current_id, $target_url );

        if ( $delay <= 0 ) {
            $this->redirect_now( $target_url );
            exit;
        }
        ?>
        <!doctype html><html <?php language_attributes(); ?>><head>
            <meta charset="<?php bloginfo( 'charset' ); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <meta name="robots" content="noindex,nofollow,noarchive">
            <title><?php echo esc_html( $heading ); ?> | <?php bloginfo( 'name' ); ?></title>
            <style>
                html,body{margin:0;padding:0;min-height:100%;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:#f5f7fa;color:#1d2327}
                body{display:flex;align-items:center;justify-content:center;padding:24px;box-sizing:border-box}.bzj-wrap{width:min(620px,100%);background:#fff;border:1px solid #dfe3e8;border-radius:18px;box-shadow:0 12px 40px rgba(0,0,0,.08);padding:38px 32px;text-align:center}.bzj-lock{font-size:42px;line-height:1;margin-bottom:16px}.bzj-title{font-size:28px;margin:0 0 18px}.bzj-message{font-size:17px;line-height:1.65}.bzj-count{font-size:46px;font-weight:700;margin:12px 0 16px}.bzj-rule{letter-spacing:4px;color:#bbb;margin:8px 0 22px}.bzj-actions{display:flex;gap:12px;justify-content:center;flex-wrap:wrap}.bzj-actions a{display:inline-block;text-decoration:none;padding:12px 18px;border-radius:9px;border:1px solid #c9ced3}.bzj-actions .primary{background:#1d2327;color:#fff;border-color:#1d2327}.bzj-note{font-size:13px;color:#646970;margin-top:18px}@media(max-width:520px){.bzj-wrap{padding:28px 20px}.bzj-title{font-size:24px}}
            </style>
        </head><body>
            <main class="bzj-wrap" role="main" aria-live="polite">
                <div class="bzj-lock">🔒</div>
                <h1 class="bzj-title"><?php echo esc_html( $heading ); ?></h1>
                <div class="bzj-message"><?php echo $message; ?></div>
                <div class="bzj-count" id="bzj-count"><?php echo esc_html( (string) (int) ceil( $delay ) ); ?></div>
                <div class="bzj-rule">━━━━━━━━━━━━━</div>
                <div class="bzj-actions">
                    <a class="primary" href="<?php echo esc_url( $target_url ); ?>"><?php echo esc_html( $continue ); ?></a>
                    <a href="<?php echo esc_url( $back_url ?: $home ); ?>" <?php echo $back_url ? '' : 'data-browser-back="1"'; ?>><?php echo esc_html( $back ); ?></a>
                </div>
                <div class="bzj-note">You can continue immediately, or return to the previous page.</div>
            </main>
            <script>
            (function(){
                var target=<?php echo wp_json_encode( $target_url ); ?>, n=<?php echo (int) ceil( $delay ); ?>, el=document.getElementById('bzj-count');
                var back=document.querySelector('[data-browser-back="1"]');
                if(back){back.addEventListener('click',function(e){if(window.history.length>1){e.preventDefault();history.back();}});}
                function tick(){ if(n<=0){window.location.replace(target);return;} el.textContent=n; n--; window.setTimeout(tick,1000); }
                tick();
            })();
            </script>
        </body></html>
        <?php
        exit;
    }

    private function resolve_redirect_loop( $current_url, $target_url, $current_id ) {
        if ( ! $target_url || ! $this->same_url( $current_url, $target_url ) ) return $target_url;

        $candidate = $current_url;
        for ( $i = 0; $i < 6; $i++ ) {
            $parent = $this->parent_url( $candidate );
            if ( $parent && ! $this->same_url( $parent, $candidate ) && ! $this->same_url( $parent, $current_url ) ) {
                return $parent;
            }
            $candidate = $parent;
            if ( ! $candidate || $this->same_url( $candidate, home_url( '/' ) ) ) break;
        }

        $course_id = $this->get_course_id( $current_id );
        if ( $course_id ) {
            $course_url = get_permalink( $course_id );
            if ( $course_url && ! $this->same_url( $course_url, $current_url ) ) return $course_url;
        }

        return home_url( '/' );
    }

    private function redirect_now( $url ) {
        $url = esc_url_raw( $url );
        if ( ! $url ) $url = home_url( '/' );
        wp_redirect( $url, 302, 'BuzzJuice LearnDash Access Gate' );
        exit;
    }

    private function safe_target_url( $url ) {
        $url = esc_url_raw( $url );
        if ( ! $url ) return '';
        $scheme = strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) );
        if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) return '';
        return $url;
    }

    private function same_url( $a, $b ) {
        return untrailingslashit( (string) $a ) === untrailingslashit( (string) $b );
    }

    private function parent_url( $url ) {
        $parts = wp_parse_url( $url );
        if ( empty( $parts['path'] ) ) return home_url( '/' );
        $path = trim( $parts['path'], '/' );
        if ( ! $path ) return home_url( '/' );
        $segments = explode( '/', $path );
        array_pop( $segments );
        $parent = '/' . ( $segments ? implode( '/', $segments ) . '/' : '' );
        $scheme = isset( $parts['scheme'] ) ? $parts['scheme'] . '://' : '';
        $host = isset( $parts['host'] ) ? $parts['host'] : wp_parse_url( home_url( '/' ), PHP_URL_HOST );
        return $scheme . $host . $parent;
    }

    private function parent_or_course_url( $post_id ) {
        $course_id = $this->get_course_id( $post_id );
        if ( $course_id && get_permalink( $course_id ) ) return get_permalink( $course_id );
        return home_url( '/' );
    }

    private function get_course_id( $post_id ) {
        if ( function_exists( 'learndash_get_course_id' ) ) {
            $id = absint( learndash_get_course_id( $post_id ) );
            if ( $id ) return $id;
        }
        $post_type = get_post_type( $post_id );
        if ( $post_type === $this->course_type() ) return $post_id;
        return 0;
    }

    private function default_requirement_heading( $config ) {
        $has_role = 'none' !== $config['role_source'];
        $has_completion = ! empty( $config['completion_requirements'] );
        if ( $has_role && ! $has_completion && 'subscription' === $config['role_source'] ) return 'Subscription Required';
        if ( $has_role && ! $has_completion && 'custom' === $config['role_source'] ) return 'Role Required';
        if ( $has_completion && ! $has_role ) return 'Pre-requisite Required';
        if ( $has_role && $has_completion ) return 'Access Required';
        return 'Access Required';
    }

    private function describe_requirement( $config ) {
        $parts = array();
        if ( ! empty( $config['require_subscription'] ) ) {
            $roles = function_exists( 'bzj_allowed_subscription_roles' ) ? (array) bzj_allowed_subscription_roles() : array();
            $labels = array();
            $all = wp_roles()->roles;
            foreach ( $roles as $role ) {
                $role = sanitize_key( $role );
                if ( isset( $all[ $role ]['name'] ) ) $labels[] = translate_user_role( $all[ $role ]['name'] );
            }
            if ( $labels ) {
                if ( count( $labels ) === 1 ) $parts[] = $labels[0] . ' members';
                else { $last = array_pop( $labels ); $parts[] = implode( ', ', $labels ) . ' or ' . $last . ' members'; }
            } else $parts[] = 'an active BuzzJuice subscription';
        }
        if ( ! empty( $config['require_roles'] ) && ! empty( $config['roles'] ) ) {
            $all = wp_roles()->roles; $labels = array();
            foreach ( $config['roles'] as $role ) $labels[] = isset( $all[ $role ]['name'] ) ? translate_user_role( $all[ $role ]['name'] ) : ucwords( str_replace( '_', ' ', $role ) );
            $parts[] = ( 'all' === $config['role_logic'] ? 'all of: ' : 'any of: ' ) . implode( ', ', $labels );
        }
        if ( ! empty( $config['completion_requirements'] ) ) {
            $items = array();
            foreach ( $config['completion_requirements'] as $req ) {
                $id = absint( isset( $req['post_id'] ) ? $req['post_id'] : 0 );
                if ( ! $id ) continue;
                $title = get_the_title( $id );
                $items[] = $title ? $title : $this->human_type( isset( $req['post_type'] ) ? $req['post_type'] : '' ) . ' #' . $id;
            }
            if ( $items ) $parts[] = ( 'all' === $config['completion_logic'] ? 'completion of all of: ' : 'completion of any of: ' ) . implode( ', ', $items );
        }
        if ( empty( $parts ) ) return 'the required access condition';
        if ( count( $parts ) === 1 ) return $parts[0];
        return implode( 'any' === $config['requirement_logic'] ? ' or ' : ' and ', $parts );
    }

    private function url_label( $url ) {
        $post_id = url_to_postid( $url );
        if ( $post_id && get_the_title( $post_id ) ) return get_the_title( $post_id );
        $path = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );
        if ( ! $path ) return 'the requested destination';
        return ucwords( str_replace( array( '-', '_' ), ' ', basename( $path ) ) );
    }

    private function human_type( $type ) {
        $map = array( 'page' => 'Page', 'sfwd-courses' => 'Course', 'sfwd-lessons' => 'Lesson', 'sfwd-topic' => 'Topic', 'sfwd-quiz' => 'Assessment', 'sfwd-question' => 'Question', 'sfwd-assignment' => 'Assignment', 'sfwd-certificates' => 'Certificate' );
        return isset( $map[ $type ] ) ? $map[ $type ] : ucwords( str_replace( array( '-', '_' ), ' ', (string) $type ) );
    }

    /* ---------------------------------------------------------------------
     * GLOBAL ADMIN SETTINGS
     * ------------------------------------------------------------------ */

    public function admin_menu() {
        add_options_page(
            __( 'BuzzJuice Access Gate', 'bzj-ld-access-gate' ),
            __( 'BuzzJuice Access Gate', 'bzj-ld-access-gate' ),
            'manage_options',
            'bzj-ld-access-gate',
            array( $this, 'render_global_settings' )
        );
    }

    public function register_global_settings() {
        register_setting( 'bzj_ld_access_gate', 'bzj_ld_access_gate_global', array(
            'type' => 'array',
            'sanitize_callback' => function( $value ) {
                $value = is_array( $value ) ? $value : array();
                return array(
                    'delay_seconds' => max( 0, min( self::MAX_DELAY, isset( $value['delay_seconds'] ) ? (float) $value['delay_seconds'] : self::DEFAULT_DELAY ) ),
                    'heading' => isset( $value['heading'] ) ? sanitize_text_field( $value['heading'] ) : '',
                    'continue_label' => isset( $value['continue_label'] ) ? sanitize_text_field( $value['continue_label'] ) : 'Continue to {redirect_label}',
                    'back_label' => isset( $value['back_label'] ) ? sanitize_text_field( $value['back_label'] ) : '← Go Back',
                );
            },
        ) );
    }

    public function render_global_settings() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        $g = $this->global_interstitial_config();
        ?>
        <div class="wrap"><h1><?php esc_html_e( 'BuzzJuice Access Gate', 'bzj-ld-access-gate' ); ?></h1>
        <p><?php esc_html_e( 'These are the single global interstitial defaults used by the unified gate. All gates use one global interstitial countdown. Individual content may override its redirect and message, but the countdown is configured once here.', 'bzj-ld-access-gate' ); ?></p>
        <form method="post" action="options.php">
        <?php settings_fields( 'bzj_ld_access_gate' ); ?>
        <table class="form-table"><tr><th>Automatic redirect delay</th><td><input type="number" min="0" max="60" step="0.5" name="bzj_ld_access_gate_global[delay_seconds]" value="<?php echo esc_attr( $g['delay_seconds'] ); ?>"> seconds</td></tr>
        <tr><th>Default heading</th><td><input class="regular-text" name="bzj_ld_access_gate_global[heading]" value="<?php echo esc_attr( $g['heading'] ); ?>"><p class="description">Leave blank to use a contextual heading such as Subscription Required or Completion Required.</p></td></tr>
        <tr><th>Continue button</th><td><input class="regular-text" name="bzj_ld_access_gate_global[continue_label]" value="<?php echo esc_attr( $g['continue_label'] ); ?>"></td></tr>
        <tr><th>Go Back button</th><td><input class="regular-text" name="bzj_ld_access_gate_global[back_label]" value="<?php echo esc_attr( $g['back_label'] ); ?>"></td></tr></table>
        <?php submit_button(); ?></form></div>
        <?php
    }

    /* ---------------------------------------------------------------------
     * ADMIN ASSETS / REST
     * ------------------------------------------------------------------ */

    public function ajax_search_gated_content() {
        check_ajax_referer( 'bzj_ld_access_gate_search', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
        $type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : 'page';
        $q = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
        if ( ! post_type_exists( $type ) || ! in_array( $type, $this->gatable_post_types(), true ) ) wp_send_json_success( array() );
        $posts = get_posts( array( 'post_type' => $type, 'post_status' => array( 'publish', 'private', 'draft' ), 'posts_per_page' => 30, 's' => $q, 'orderby' => 'title', 'order' => 'ASC' ) );
        $items = array();
        foreach ( $posts as $post ) $items[] = array( 'id' => $post->ID, 'text' => get_the_title( $post->ID ) . ' (#' . $post->ID . ')' );
        wp_send_json_success( $items );
    }

    public function admin_assets( $hook ) {
        if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) return;
        if ( ! $this->is_learn_dash_type( get_post_type( $this->current_post_id() ) ) ) return;
        // LearnDash normally supplies Select2. We only add minimal CSS/JS if
        // it is available; no external CDN dependency is required.
        if ( function_exists( 'wp_enqueue_script' ) ) {
            wp_enqueue_script( 'select2' );
            wp_enqueue_style( 'select2' );
        }
    }

    public function register_rest_routes() {
        register_rest_route( self::REST_NAMESPACE, '/check/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array( $this, 'rest_check' ),
            'permission_callback' => function( WP_REST_Request $request ) {
                $id = absint( $request['id'] );
                return $id && current_user_can( 'edit_post', $id );
            },
        ) );
    }

    public function rest_check( WP_REST_Request $request ) {
        $post_id = absint( $request['id'] );
        return rest_ensure_response( array(
            'version' => self::VERSION,
            'post_id' => $post_id,
            'post_type' => get_post_type( $post_id ),
            'access_gate' => $this->get_access_config( $post_id ),
            'legacy_gate' => get_post_meta( $post_id, self::LEGACY_META, true ),
            'prerequisites' => $this->get_prereq_data( $post_id ),
            'user_access' => $this->user_can_access( $post_id ),
            'completion_requirements' => $this->get_access_config( $post_id )['completion_requirements'],
        ) );
    }
}

BZJ_LD_Access_Gate::init();

}