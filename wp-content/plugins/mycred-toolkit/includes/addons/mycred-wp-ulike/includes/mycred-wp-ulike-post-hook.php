<?php
if (!defined('ABSPATH'))
    exit;

/**
 * Hook for WP Ulike - User Likes Post
 * @since 1.0
 * @version 1.0
 */
if (!class_exists('myCRED_WP_Ulike_Post_Hook')):
    class myCRED_WP_Ulike_Post_Hook extends myCRED_Hook
    {

        /**
         * Construct
         */
        function __construct($hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY)
        {

            parent::__construct(array(
                'id' => 'wp_ulike_post_like',
                'defaults' => array(
                    'creds' => 1,
                    'log' => __('%plural% for liking a post', 'mycred-toolkit'),
                    'limit' => '0/x'
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
            add_action('wp_ulike_after_process', array($this, 'mycred_wp_ulike_post_like'), 10, 4);
        }

        /**
         * Award points for WP Ulike - User Likes Post
         */
        public function mycred_wp_ulike_post_like($id, $key, $user_id, $status)
        {
            // Only process post likes (_liked key)
            if ($key !== '_liked') {
                return;
            }

            // Only process 'like' actions, not 'unlike'
            if ($status !== 'like') {
                return;
            }

            // Must be logged in
            if (!is_user_logged_in() || !$user_id) {
                return;
            }

            // Exclude user check
            if ($this->core->exclude_user($user_id)) {
                return;
            }

            // Get post author ID
            $post_id = absint($id);
            $post_author_id = absint(get_post_field('post_author', $post_id));

            // Prevent authors from liking their own posts
            $user_id_int = absint($user_id);
            
            // If author ID is valid (not 0) and matches user ID, prevent awarding points
            if ($post_author_id > 0 && $user_id_int === $post_author_id) {
                return;
            }

            // Ensure we only award points to the user who likes, never to the author
            // This hook only rewards the liker, not the post author
            $award_user_id = $user_id_int;

            $reference = 'wp_ulike_post_like';

            // Limit Check
            if ($this->over_hook_limit('', $reference, $award_user_id)) {
                return;
            }

            // Check for duplicate entry - prevent awarding points for the same like
            if ($this->core->has_entry($reference, $post_id, $award_user_id, array('ref_type' => 'post'), $this->mycred_type)) {
                return;
            }

            // Award points only to the user who likes (not the author)
            $this->core->add_creds(
                $reference,
                $award_user_id,
                $this->prefs['creds'],
                $this->prefs['log'],
                $post_id,
                array('ref_type' => 'post'),
                $this->mycred_type
            );
        }

        /**
         * Preference for WP Ulike Post Hook
         * @since 1.0
         * @version 1.0
         */
        public function preferences()
        {
            $prefs = $this->prefs;
            ?>

            <div class="hook-instance">
                <h3><?php esc_html_e('Liking a Post', 'mycred-toolkit'); ?></h3>
                <div class="row">
                    <div class="col-lg-2 col-md-6 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo esc_attr($this->field_id('creds')); ?>"><?php echo esc_html($this->core->plural()); ?></label>
                            <input type="text" name="<?php echo esc_attr($this->field_name('creds')); ?>" id="<?php echo esc_attr($this->field_id('creds')); ?>" value="<?php echo esc_attr($this->core->number($prefs['creds'])); ?>" class="form-control" />
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo esc_attr($this->field_id('limit')); ?>"><?php esc_html_e('Limit', 'mycred-toolkit'); ?></label>
                            <?php
                            echo wp_kses(
                                $this->hook_limit_setting($this->field_name('limit'), $this->field_id('limit'), $prefs['limit']),
                                array(
                                    'div' => array(
                                        'class' => array()
                                    ),
                                    'input' => array(
                                        'type' => array(),
                                        'size' => array(),
                                        'class' => array(),
                                        'name' => array(),
                                        'id' => array(),
                                        'value' => array()
                                    ),
                                    'select' => array(
                                        'name' => array(),
                                        'id' => array(),
                                        'class' => array()
                                    ),
                                    'option' => array(
                                        'value' => array(),
                                        'selected' => array()
                                    )
                                )
                            );
                            ?>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo esc_attr($this->field_id('log')); ?>"><?php esc_html_e('Log Template', 'mycred-toolkit'); ?></label>
                            <input type="text" name="<?php echo esc_attr($this->field_name('log')); ?>" id="<?php echo esc_attr($this->field_id('log')); ?>" value="<?php echo esc_attr($prefs['log']); ?>" class="form-control" />
                            <span class="description"><?php echo $this->available_template_tags(array('general', 'post')); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <?php
        }

        /**
         * Sanitize Preferences
         */
        function sanitise_preferences($data)
        {

            $data['creds'] = (!empty($data['creds'])) ? floatval($data['creds']) : 0;
            $data['log'] = (!empty($data['log'])) ? sanitize_text_field($data['log']) : $this->defaults['log'];

            if (isset($data['limit']) && isset($data['limit_by'])) {
                $limit = sanitize_text_field($data['limit']);
                if ($limit == '') {
                    $limit = 0;
                }
                $data['limit'] = $limit . '/' . $data['limit_by'];
                unset($data['limit_by']);
            }

            return $data;
        }
    }
endif;

