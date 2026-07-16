<?php
if (!defined('ABSPATH'))
    exit;

/**
 * Hook for Favorites plugin
 * @since 1.0
 * @version 1.0
 */
if (!class_exists('myCRED_Favorites_Hook')):
    class myCRED_Favorites_Hook extends myCRED_Hook
    {

        /**
         * Construct
         */
        function __construct($hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY)
        {

            parent::__construct(array(
                'id' => 'favorites_favorite',
                'defaults' => array(
                    'creds' => 1,
                    'log' => __('%plural% for favoriting a post', 'mycred-toolkit'),
                    'limit' => '0/x',
                    'check_specific_hook' => 0,
                    'specific_posts' => array(
                        'creds' => array(),
                        'log' => array(),
                        'select_option' => array(),
                    ),
                )
            ), $hook_prefs, $type);
        }

        /**
         * Run
         * @since 1.0
         * @version 1.0
         */
        public function run()
        {
            // Hook into Favorites plugin after favorite action
            add_action('favorites_after_favorite', array($this, 'mycred_favorites_favorite'), 10, 4);
        }

        /**
         * Generate specific field name for repeatable fields
         */
        public function specific_field_name($field = '')
        {
            $hook_prefs_key = 'mycred_pref_hooks';

            if (is_array($field)) {
                $array = array();
                foreach ($field as $parent => $child) {
                    if (!is_numeric($parent)) {
                        $array[] = $parent;
                    }

                    if (!empty($child) && !is_array($child)) {
                        $array[] = $child;
                    }
                }
                $field = '[' . implode('][', $array) . ']';
            } else {
                $field = '[' . $field . ']';
            }

            $option_id = 'mycred_pref_hooks';
            if (!$this->is_main_type) {
                $option_id = $option_id . '_' . $this->mycred_type;
            }

            return $option_id . '[hook_prefs][' . $this->id . ']' . $field . '[]';
        }

        /**
         * Award points for Favoriting a Post
         */
        public function mycred_favorites_favorite($post_id, $status, $site_id, $user_id)
        {
            // Login is required
            if (!is_user_logged_in()) {
                return;
            }

            // Only award points for 'active' status (favorite action)
            // 'inactive' status means unfavorite
            if ($status !== 'active') {
                return;
            }

            $post_id = absint($post_id);
            $user_id = absint($user_id);

            if (empty($post_id) || empty($user_id)) {
                return;
            }

            // Exclude user check
            if ($this->core->exclude_user($user_id)) {
                return;
            }

            $prefs = $this->prefs;
            $reference = 'favorites_favorite';

            // Check if specific hook is enabled and post matches
            if (
                isset($prefs['check_specific_hook']) &&
                $prefs['check_specific_hook'] == '1' &&
                !empty($prefs['specific_posts']['select_option']) &&
                in_array($post_id, $prefs['specific_posts']['select_option'])
            ) {

                // Find the index of this post in the configuration
                $hook_index = array_search($post_id, $prefs['specific_posts']['select_option']);

                if (
                    $hook_index !== false &&
                    !empty($prefs['specific_posts']['creds']) &&
                    isset($prefs['specific_posts']['creds'][$hook_index])
                ) {

                    // Specific Limit Check
                    if ($this->over_hook_limit('specific_posts', $reference, $user_id)) {
                        return;
                    }

                    $creds = $prefs['specific_posts']['creds'][$hook_index];
                    $log = isset($prefs['specific_posts']['log'][$hook_index]) ? $prefs['specific_posts']['log'][$hook_index] : $prefs['log'];

                    $this->core->add_creds(
                        $reference,
                        $user_id,
                        $creds,
                        $log,
                        $post_id,
                        array('ref_type' => 'post'),
                        $this->mycred_type
                    );

                    return; // Exit after awarding specific points (exclusive behavior)
                }
            }

            // General Award

            // Check limit for general hook
            if ($this->over_hook_limit('', $reference, $user_id)) {
                return;
            }

            $this->core->add_creds(
                $reference,
                $user_id,
                $prefs['creds'],
                $prefs['log'],
                $post_id,
                array('ref_type' => 'post'),
                $this->mycred_type
            );
        }

        /**
         * Arrange specific hook data for display
         */
        public function mycred_favorites_arrange_data($specific_hook_data)
        {
            $hook_data = array();
            if (isset($specific_hook_data['creds']) && is_array($specific_hook_data['creds'])) {
                foreach ($specific_hook_data['creds'] as $key => $value) {
                    $hook_data[$key]['creds'] = $value;
                    $hook_data[$key]['log'] = isset($specific_hook_data['log'][$key]) ? $specific_hook_data['log'][$key] : '';
                    $hook_data[$key]['select_option'] = isset($specific_hook_data['select_option'][$key]) ? $specific_hook_data['select_option'][$key] : '';
                }
            }
            return $hook_data;
        }

        /**
         * Preference for Favorites Hook
         * @since 1.0
         * @version 1.0
         */
        public function preferences()
        {
            $prefs = $this->prefs;

            // Get post types enabled in Favorites plugin settings
            $post_types = array();
            $types = get_option('simplefavorites_display');

            if (!empty($types['posttypes']) && $types !== "") {
                foreach ($types['posttypes'] as $key => $type) {
                    // If favorites display is active, then add the post type
                    if (isset($type['display']) && $type['display'] == 'true') {
                        $post_types[] = $key;
                    }
                }
            }

            // Fallback to 'post' if no post types are enabled
            if (empty($post_types)) {
                $post_types = array('post');
            }

            // Fetch all published posts from enabled post types
            $args = array(
                'post_type' => $post_types,
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'orderby' => 'title',
                'order' => 'ASC'
            );
            $posts = get_posts($args);

            ?>

            <!-- General Favorite Rewards Starts -->
            <div class="hook-instance">
                <h3><?php esc_html_e('Favorite any post', 'mycred-toolkit'); ?></h3>
                <div class="row">
                    <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo $this->field_id('creds'); ?>"><?php echo $this->core->plural(); ?></label>
                            <input type="text" name="<?php echo $this->field_name('creds'); ?>"
                                id="<?php echo $this->field_id('creds'); ?>"
                                value="<?php echo $this->core->number($prefs['creds']); ?>" class="form-control" />
                        </div>
                    </div>
                    <div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label
                                for="<?php echo $this->field_id('log'); ?>"><?php esc_html_e('Log Template', 'mycred-toolkit'); ?></label>
                            <input type="text" name="<?php echo $this->field_name('log'); ?>"
                                id="<?php echo $this->field_id('log'); ?>" value="<?php echo esc_attr($prefs['log']); ?>"
                                class="form-control" />
                            <span class="description"><?php echo $this->available_template_tags(array('general', 'post')); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- General Favorite Rewards Ends -->

            <!-- Specific Post Rewards Starts -->
            <?php
            $specific_data = array(
                array(
                    'creds' => 0,
                    'log' => __('%plural% for favoriting post', 'mycred-toolkit'),
                    'select_option' => 0
                ),
            );

            if (!empty($prefs['specific_posts']['creds']) && count($prefs['specific_posts']['creds']) > 0) {
                $specific_data = $this->mycred_favorites_arrange_data($prefs['specific_posts']);
            }

            ?>
            <div class="hook-instance" id="specific-hook-favorites">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="hook-title">
                            <h3><?php esc_html_e('Favorite specific post', 'mycred-toolkit'); ?></h3>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <?php
                    $is_enabled = (isset($prefs['check_specific_hook']) && $prefs['check_specific_hook'] == 1);

                    mycred_create_toggle_field(
                        array(
                            'id' => $this->field_id('check_specific_hook'),
                            'name' => $this->field_name('check_specific_hook'),
                            'label' => __('Enable', 'mycred-toolkit'),
                            'after' => false,
                        ),
                        1,
                        $is_enabled
                    );
                    ?>

                </div>
                <?php
                foreach ($specific_data as $hook_idx => $label) {
                    ?>
                    <div class="favorites_specific_row">
                        <div class="row">
                            <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label
                                        for="<?php echo $this->field_id(array('specific_posts' => 'creds')); ?>"><?php echo $this->core->plural(); ?></label>
                                    <input type="text"
                                        name="<?php echo $this->specific_field_name(array('specific_posts' => 'creds')); ?>"
                                        value="<?php echo $this->core->number($label['creds']); ?>" class="form-control" />
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label><?php esc_html_e('Select Post', 'mycred-toolkit'); ?></label>
                                    <select class="form-control mycred-favorites-options"
                                        name="<?php echo $this->specific_field_name(array('specific_posts' => 'select_option')); ?>">

                                        <option value="0"><?php esc_html_e('Select Post', 'mycred-toolkit'); ?></option>
                                        <?php
                                        if (!empty($posts)) {
                                            foreach ($posts as $post) {
                                                $selected = (isset($label['select_option']) && $label['select_option'] == $post->ID) ? 'selected' : '';
                                                echo '<option value="' . esc_attr($post->ID) . '" ' . $selected . '>' . esc_html($post->post_title) . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label><?php esc_html_e('Log Template', 'mycred-toolkit'); ?></label>
                                    <input type="text"
                                        name="<?php echo $this->specific_field_name(array('specific_posts' => 'log')); ?>"
                                        value="<?php echo esc_attr($label['log']); ?>" class="form-control" />
                                    <span
                                        class="description"><?php echo $this->available_template_tags(array('general', 'post')); ?></span>
                                </div>
                            </div>

                        </div>
                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 field_wrapper">
                                <div class="form-group textright">
                                    <button class="button button-small mycred-add-specific-favorites-hook add_button" type="button">Add
                                        More</button>
                                    <button class="button button-small mycred-remove-specific-favorites-hook"
                                        type="button">Remove</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
            <!-- Specific Post Rewards Ends -->

            <!-- Hook Limit Starts -->
            <div class="hook-instance">
                <h3><?php esc_html_e('Limit', 'mycred-toolkit'); ?></h3>
                <div class="row">
                    <div class="col-lg-5 col-md-5 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <?php add_filter('mycred_hook_limits', array($this, 'custom_limit')); ?>
                            <label for="<?php echo $this->field_id('limit'); ?>"></label>
                            <?php echo $this->hook_limit_setting($this->field_name('limit'), $this->field_id('limit'), esc_attr($prefs['limit'])); ?>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Hook Limit Ends -->

            <?php
        }

        /**
         * Sanitize Preferences
         */
        function sanitise_preferences($data)
        {

            $data['creds'] = (!empty($data['creds'])) ? floatval($data['creds']) : 0;
            $data['check_specific_hook'] = (isset($data['check_specific_hook']) && $data['check_specific_hook'] == '1') ? 1 : 0;
            $data['log'] = (!empty($data['log'])) ? sanitize_text_field($data['log']) : $this->defaults['log'];

            if (isset($data['limit']) && isset($data['limit_by'])) {
                $limit = sanitize_text_field($data['limit']);
                if ($limit == '') {
                    $limit = 0;
                }
                $data['limit'] = $limit . '/' . $data['limit_by'];
                unset($data['limit_by']);
            }

            if (isset($data['specific_posts'])) {
                foreach ($data['specific_posts']['creds'] as $key => $value) {
                    $new_val = floatval($value);
                    $data['specific_posts']['creds'][$key] = $new_val;

                    $log_val = isset($data['specific_posts']['log'][$key]) ? $data['specific_posts']['log'][$key] : '';
                    $data['specific_posts']['log'][$key] = sanitize_text_field($log_val);

                    $opt_val = isset($data['specific_posts']['select_option'][$key]) ? $data['specific_posts']['select_option'][$key] : 0;
                    $data['specific_posts']['select_option'][$key] = intval($opt_val);
                }
            }

            return $data;
        }

        public function custom_limit()
        {
            return array(
                'x' => __('No limit', 'mycred-toolkit'),
                'd' => __('/ Day', 'mycred-toolkit'),
                'w' => __('/ Week', 'mycred-toolkit'),
                'm' => __('/ Month', 'mycred-toolkit'),
            );
        }
    }
endif;
