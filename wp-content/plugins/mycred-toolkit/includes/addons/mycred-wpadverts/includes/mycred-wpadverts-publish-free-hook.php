<?php
if (!defined('ABSPATH'))
    exit;

/**
 * Hook for WPAdverts Free Advert Publication
 * @since 1.0
 * @version 1.0
 */
if (!class_exists('myCRED_WPAdverts_Publish_Free_Hook')):
    class myCRED_WPAdverts_Publish_Free_Hook extends myCRED_Hook
    {

        /**
         * Construct
         */
        function __construct($hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY)
        {

            parent::__construct(array(
                'id' => 'wpadverts_publish_free_advert',
                'defaults' => array(
                    'creds' => 1,
                    'log' => __('%plural% for publishing a free advert', 'mycred-toolkit'),
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
            add_action('added_post_meta', array($this, 'mycred_wpadverts_publish_free_advert'), 10, 4);
            add_action('updated_post_meta', array($this, 'mycred_wpadverts_publish_free_advert'), 10, 4);
        }

        /**
         * Award points for WPAdverts Free Advert Publication
         */
        public function mycred_wpadverts_publish_free_advert($meta_id, $post_id, $meta_key, $meta_value)
        {
            // Only process adverts_price meta key
            if ($meta_key !== 'adverts_price') {
                return;
            }

            // Get the post
            $post = get_post($post_id);

            // Bail if not is an advert
            if (!$post || $post->post_type !== 'advert') {
                return;
            }

            // Bail if post is not published
            if ($post->post_status !== 'publish') {
                return;
            }

            // Check if advert is free - use the meta_value directly
            $price = (float) $meta_value;

            // Bail if not a free advert (price must be 0 or empty)
            if ($price > 0) {
                return;
            }

            $user_id = absint($post->post_author);

            // Bail if post does not has an author assigned
            if ($user_id === 0) {
                return;
            }

            // Exclude user check
            if ($this->core->exclude_user($user_id)) {
                return;
            }

            $reference = 'wpadverts_publish_free_advert';

            // Limit Check
            if ($this->over_hook_limit('', $reference, $user_id)) {
                return;
            }

            // Check for duplicate entry
            if ($this->core->has_entry($reference, $post_id, $user_id, array('ref_type' => 'post'), $this->mycred_type)) {
                return;
            }

            // Award points
            $this->core->add_creds(
                $reference,
                $user_id,
                $this->prefs['creds'],
                $this->prefs['log'],
                $post_id,
                array('ref_type' => 'post'),
                $this->mycred_type
            );

        }

        /**
         * Preference for WPAdverts Free Advert Hook
         * @since 1.0
         * @version 1.0
         */
        public function preferences()
        {
            $prefs = $this->prefs;
            ?>

            <div class="hook-instance">
                <h3>
                    <?php esc_html_e('Publishing a Free Advert', 'mycred-toolkit'); ?>
                </h3>
                <div class="row">
                    <div class="col-lg-2 col-md-6 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo esc_attr($this->field_id('creds')); ?>">
                                <?php echo esc_html($this->core->plural()); ?>
                            </label>
                            <input type="text" name="<?php echo esc_attr($this->field_name('creds')); ?>"
                                id="<?php echo esc_attr($this->field_id('creds')); ?>"
                                value="<?php echo esc_attr($this->core->number($prefs['creds'])); ?>" class="form-control" />
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo esc_attr($this->field_id('limit')); ?>">
                                <?php esc_html_e('Limit', 'mycred-toolkit'); ?>
                            </label>
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
                            <label for="<?php echo esc_attr($this->field_id('log')); ?>">
                                <?php esc_html_e('Log Template', 'mycred-toolkit'); ?>
                            </label>
                            <input type="text" name="<?php echo esc_attr($this->field_name('log')); ?>"
                                id="<?php echo esc_attr($this->field_id('log')); ?>" value="<?php echo esc_attr($prefs['log']); ?>"
                                class="form-control" />
                            <span class="description">
                                <?php echo $this->available_template_tags(array('general', 'post')); ?>
                            </span>
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
