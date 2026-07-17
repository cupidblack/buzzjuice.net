<?php
if (!defined('ABSPATH'))
    exit;

/**
 * Hook for Presto Player - Video Percent Range
 * Awards points when user watches a video within a specified percentage range
 * @since 1.0
 * @version 1.0
 */
if (!class_exists('myCRED_Presto_Player_Percent_Range_Hook')):
    class myCRED_Presto_Player_Percent_Range_Hook extends myCRED_Hook
    {

        /**
         * Construct
         */
        function __construct($hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY)
        {

            parent::__construct(array(
                'id' => 'presto_player_video_percent_range',
                'defaults' => array(
                    'creds' => 1,
                    'min_percent' => 25,
                    'max_percent' => 75,
                    'log' => __('%plural% for watching video to percentage range', 'mycred-toolkit'),
                    'limit' => '0/x',
                    'check_specific_hook' => 0,
                    'specific_videos' => array(
                        'creds' => array(),
                        'min_percent' => array(),
                        'max_percent' => array(),
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
            // Hook into Presto Player progress
            add_action('presto_player_progress', array($this, 'mycred_presto_player_percent_range_progress'), 10, 3);

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
         * Award points for Video Percent Range
         */
        public function mycred_presto_player_percent_range_progress($id, $percent, $visit_time)
        {
            $user_id = get_current_user_id();

            // Bail if user is not logged in
            if ($user_id == 0) {
                return;
            }

            // Exclude user check
            if ($this->core->exclude_user($user_id)) {
                return;
            }

            $prefs = $this->prefs;
            $reference = 'presto_player_video_percent_range';
            $video_id = absint($id);

            // Get the post_id from the presto_player_videos table
            $post_id = $this->get_presto_player_post_id($video_id);

            // Check if specific hook is enabled
            $specific_enabled = isset($prefs['check_specific_hook']) && $prefs['check_specific_hook'] == 1;

            if ($specific_enabled && !empty($prefs['specific_videos']['select_option']) && $post_id) {
                // Normalize the select_option array to integers for comparison
                $specific_post_ids = array_map('absint', $prefs['specific_videos']['select_option']);

                // Check if this POST is in the specific videos list
                $hook_index = array_search($post_id, $specific_post_ids);

                if ($hook_index !== false) {
                    // Video is in specific list - check specific percent range

                    // Get the configured min and max percent for this specific video
                    $min_percent = isset($prefs['specific_videos']['min_percent'][$hook_index])
                        ? absint($prefs['specific_videos']['min_percent'][$hook_index])
                        : 25;
                    $max_percent = isset($prefs['specific_videos']['max_percent'][$hook_index])
                        ? absint($prefs['specific_videos']['max_percent'][$hook_index])
                        : 75;

                    // Check if current progress is within the range (inclusive)
                    if ($percent < $min_percent || $percent > $max_percent) {
                        return;
                    }

                    // Validate that credits are configured for this video
                    if (
                        !empty($prefs['specific_videos']['creds']) &&
                        isset($prefs['specific_videos']['creds'][$hook_index]) &&
                        $prefs['specific_videos']['creds'][$hook_index] != 0 &&
                        $prefs['specific_videos']['creds'][$hook_index] != ''
                    ) {
                        // Check limit for specific videos using post_id
                        if ($this->over_hook_limit('specific_videos', $reference . '_' . $post_id, $user_id)) {
                            return;
                        }

                        // Check if user already got points for this specific video
                        if ($this->core->has_entry($reference, $post_id, $user_id)) {
                            return;
                        }

                        $creds = $prefs['specific_videos']['creds'][$hook_index];
                        $log = isset($prefs['specific_videos']['log'][$hook_index]) && !empty($prefs['specific_videos']['log'][$hook_index])
                            ? $prefs['specific_videos']['log'][$hook_index]
                            : (isset($prefs['log']) ? $prefs['log'] : '%plural% for watching video');

                        $this->core->add_creds(
                            $reference,
                            $user_id,
                            $creds,
                            $log,
                            $post_id, // Use post_id for reference
                            array('ref_type' => 'post'),
                            $this->mycred_type
                        );
                    }

                    // Important: Return here to prevent falling through to general awards
                    return;
                }

            }

            // General Award - Only if NOT in specific videos list OR specific hooks not enabled

            // Get the configured general percent range
            $general_min_percent = isset($prefs['min_percent']) ? absint($prefs['min_percent']) : 25;
            $general_max_percent = isset($prefs['max_percent']) ? absint($prefs['max_percent']) : 75;

            // Check if current progress is within the range (inclusive)
            if ($percent < $general_min_percent || $percent > $general_max_percent) {
                return;
            }

            // Check if general credits are configured
            if (empty($prefs['creds']) || $prefs['creds'] == 0) {
                return;
            }

            // Check limit for general hook
            if ($this->over_hook_limit('', $reference, $user_id)) {
                return;
            }

            // Use post_id if found, otherwise use video_id
            $reference_id = $post_id ? $post_id : $video_id;

            // Check if user already got points for this video
            if ($this->core->has_entry($reference, $reference_id, $user_id)) {
                return;
            }

            $this->core->add_creds(
                $reference,
                $user_id,
                $prefs['creds'],
                $prefs['log'],
                $reference_id,
                array('ref_type' => 'post'),
                $this->mycred_type
            );
        }

        /**
         * Get the pp_video_block post_id from presto_player_videos table
         * 
         * @param int $video_id The video ID from the presto_player_progress action
         * @return int|false The post ID if found, false otherwise
         */
        private function get_presto_player_post_id($video_id)
        {
            global $wpdb;

            $table_name = $wpdb->prefix . 'presto_player_videos';

            // Check if table exists
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
                return false;
            }

            // Query to get post_id for this video_id
            $post_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT post_id FROM $table_name WHERE id = %d AND deleted_at IS NULL",
                    $video_id
                )
            );

            if ($post_id && $post_id > 0) {
                return absint($post_id);
            }

            return false;
        }

        /**
         * Arrange specific hook data for display
         */
        public function mycred_presto_player_percent_range_arrange_data($specific_hook_data)
        {
            $hook_data = array();
            if (isset($specific_hook_data['creds']) && is_array($specific_hook_data['creds'])) {
                foreach ($specific_hook_data['creds'] as $key => $value) {
                    $hook_data[$key]['creds'] = $value;
                    $hook_data[$key]['min_percent'] = isset($specific_hook_data['min_percent'][$key]) ? $specific_hook_data['min_percent'][$key] : 25;
                    $hook_data[$key]['max_percent'] = isset($specific_hook_data['max_percent'][$key]) ? $specific_hook_data['max_percent'][$key] : 75;
                    $hook_data[$key]['log'] = isset($specific_hook_data['log'][$key]) ? $specific_hook_data['log'][$key] : '';
                    $hook_data[$key]['select_option'] = isset($specific_hook_data['select_option'][$key]) ? $specific_hook_data['select_option'][$key] : '';
                }
            }
            return $hook_data;
        }

        /**
         * Preference for Presto Player Percent Range Hook
         * @since 1.0
         * @version 1.0
         */
        public function preferences()
        {
            $prefs = $this->prefs;

            // Fetch Presto Player Blocks
            $args = array(
                'post_type' => 'pp_video_block',
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'orderby' => 'title',
                'order' => 'ASC'
            );
            $videos = get_posts($args);

            ?>

            <!-- General Video Percent Range Rewards Starts -->
            <div class="hook-instance">
                <h3><?php esc_html_e('Watch any video to percent range', 'mycred-toolkit'); ?></h3>
                <div class="row">
                    <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo $this->field_id('creds'); ?>"><?php echo $this->core->plural(); ?></label>
                            <input type="text" name="<?php echo $this->field_name('creds'); ?>"
                                id="<?php echo $this->field_id('creds'); ?>"
                                value="<?php echo $this->core->number($prefs['creds']); ?>" class="form-control" />
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label
                                for="<?php echo $this->field_id('min_percent'); ?>"><?php esc_html_e('Minimum', 'mycred-toolkit'); ?></label>
                            <input type="number" name="<?php echo $this->field_name('min_percent'); ?>"
                                id="<?php echo $this->field_id('min_percent'); ?>"
                                value="<?php echo isset($prefs['min_percent']) ? absint($prefs['min_percent']) : 25; ?>"
                                class="form-control" min="1" max="100" />
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label
                                for="<?php echo $this->field_id('max_percent'); ?>"><?php esc_html_e('Maximum', 'mycred-toolkit'); ?></label>
                            <input type="number" name="<?php echo $this->field_name('max_percent'); ?>"
                                id="<?php echo $this->field_id('max_percent'); ?>"
                                value="<?php echo isset($prefs['max_percent']) ? absint($prefs['max_percent']) : 75; ?>"
                                class="form-control" min="1" max="100" />
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
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
            <!-- General Video Percent Range Rewards Ends -->

            <!-- Specific Video Percent Range Rewards Starts -->
            <?php
            $specific_data = array(
                array(
                    'creds' => 0,
                    'min_percent' => 25,
                    'max_percent' => 75,
                    'log' => __('%plural% for watching video to percent range', 'mycred-toolkit'),
                    'select_option' => 0
                ),
            );

            if (!empty($prefs['specific_videos']['creds']) && count($prefs['specific_videos']['creds']) > 0) {
                $specific_data = $this->mycred_presto_player_percent_range_arrange_data($prefs['specific_videos']);
            }

            ?>
            <div class="hook-instance" id="specific-hook-presto-player-percent-range">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="hook-title">
                            <h3><?php esc_html_e('Watch specific video to percent range', 'mycred-toolkit'); ?></h3>
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
                    <div class="presto_player_percent_range_specific_row">
                        <div class="row">
                            <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label
                                        for="<?php echo $this->field_id(array('specific_videos' => 'creds')); ?>"><?php echo $this->core->plural(); ?></label>
                                    <input type="text"
                                        name="<?php echo $this->specific_field_name(array('specific_videos' => 'creds')); ?>"
                                        value="<?php echo $this->core->number($label['creds']); ?>" class="form-control" />
                                </div>
                            </div>

                            <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label><?php esc_html_e('Min Percent', 'mycred-toolkit'); ?></label>
                                    <input type="number"
                                        name="<?php echo $this->specific_field_name(array('specific_videos' => 'min_percent')); ?>"
                                        value="<?php echo isset($label['min_percent']) ? absint($label['min_percent']) : 25; ?>"
                                        class="form-control" min="1" max="100" />
                                </div>
                            </div>

                            <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label><?php esc_html_e('Max Percent', 'mycred-toolkit'); ?></label>
                                    <input type="number"
                                        name="<?php echo $this->specific_field_name(array('specific_videos' => 'max_percent')); ?>"
                                        value="<?php echo isset($label['max_percent']) ? absint($label['max_percent']) : 75; ?>"
                                        class="form-control" min="1" max="100" />
                                </div>
                            </div>

                            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label><?php esc_html_e('Video', 'mycred-toolkit'); ?></label>
                                    <select class="form-control mycred-presto-player-percent-range-options"
                                        name="<?php echo $this->specific_field_name(array('specific_videos' => 'select_option')); ?>">

                                        <option value="0"><?php esc_html_e('Select Video', 'mycred-toolkit'); ?></option>
                                        <?php
                                        if (!empty($videos)) {
                                            foreach ($videos as $video) {
                                                $selected = (isset($label['select_option']) && $label['select_option'] == $video->ID) ? 'selected' : '';
                                                echo '<option value="' . esc_attr($video->ID) . '" ' . $selected . '>' . esc_html($video->post_title) . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label><?php esc_html_e('Log Template', 'mycred-toolkit'); ?></label>
                                    <input type="text"
                                        name="<?php echo $this->specific_field_name(array('specific_videos' => 'log')); ?>"
                                        value="<?php echo esc_attr($label['log']); ?>" class="form-control" />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 field_wrapper">
                                <div class="form-group textright">
                                    <button class="button button-small mycred-add-specific-presto-player-percent-range-hook add_button"
                                        type="button">Add More</button>
                                    <button class="button button-small mycred-remove-specific-presto-player-percent-range-hook"
                                        type="button">Remove</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
            <!-- Specific Video Percent Range Rewards Ends -->

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
            $data['min_percent'] = (!empty($data['min_percent'])) ? absint($data['min_percent']) : 25;
            $data['max_percent'] = (!empty($data['max_percent'])) ? absint($data['max_percent']) : 75;
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

            if (isset($data['specific_videos'])) {
                foreach ($data['specific_videos']['creds'] as $key => $value) {
                    $new_val = floatval($value);
                    $data['specific_videos']['creds'][$key] = $new_val;

                    $min_percent_val = isset($data['specific_videos']['min_percent'][$key]) ? $data['specific_videos']['min_percent'][$key] : 25;
                    $data['specific_videos']['min_percent'][$key] = absint($min_percent_val);

                    $max_percent_val = isset($data['specific_videos']['max_percent'][$key]) ? $data['specific_videos']['max_percent'][$key] : 75;
                    $data['specific_videos']['max_percent'][$key] = absint($max_percent_val);

                    $log_val = isset($data['specific_videos']['log'][$key]) ? $data['specific_videos']['log'][$key] : '';
                    $data['specific_videos']['log'][$key] = sanitize_text_field($log_val);

                    $opt_val = isset($data['specific_videos']['select_option'][$key]) ? $data['specific_videos']['select_option'][$key] : 0;
                    $data['specific_videos']['select_option'][$key] = intval($opt_val);
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
