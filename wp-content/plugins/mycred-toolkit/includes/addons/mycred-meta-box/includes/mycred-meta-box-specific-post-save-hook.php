<?php
if (!defined('ABSPATH'))
    exit;

/**
 * Hook for Meta Box - Save Specific Meta Box (Post)
 * @since 1.0
 * @version 1.0
 */
if (!class_exists('myCRED_Meta_Box_Specific_Post_Save_Hook')):
    class myCRED_Meta_Box_Specific_Post_Save_Hook extends myCRED_Hook
    {

        /**
         * Construct
         */
        function __construct($hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY)
        {

            parent::__construct(array(
                'id' => 'meta_box_save_specific_post',
                'defaults' => array(
                    'meta_box_id' => '',
                    'creds' => 1,
                    'log' => __('%plural% for saving specific meta box on post', 'mycred-toolkit'),
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
            // Hook into the specific meta box action if meta box ID is set
            if (!empty($this->prefs['meta_box_id'])) {
                $meta_box_id = sanitize_text_field($this->prefs['meta_box_id']);
                // Use a unique hook name to avoid duplicate registrations
                $hook_name = "rwmb_{$meta_box_id}_after_save_post";
                // Remove any existing hook first to prevent duplicates
                remove_action($hook_name, array($this, 'mycred_meta_box_save_specific_post'), 10);
                add_action($hook_name, array($this, 'mycred_meta_box_save_specific_post'), 10, 1);
            }
        }

        /**
         * Award points for Meta Box - Save Specific Meta Box (Post)
         * @param int $post_id Post ID
         */
        public function mycred_meta_box_save_specific_post($post_id)
        {
            // Check if meta box ID is set
            if (empty($this->prefs['meta_box_id'])) {
                return;
            }

            // Get current user
            $user_id = get_current_user_id();
            if (!$user_id) {
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

            // Verify post exists
            $post = get_post($post_id);
            if (!$post) {
                return;
            }

            // Skip autosave and revisions
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }

            if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
                return;
            }

            $meta_box_id = sanitize_text_field($this->prefs['meta_box_id']);
            $reference = 'meta_box_save_specific_post';

            // Limit Check
            if ($this->over_hook_limit('', $reference, $user_id)) {
                return;
            }

            // Check for duplicate entry - prevent awarding points for the same save action
            if ($this->core->has_entry($reference, $post_id, $user_id, array('ref_type' => 'post', 'meta_box_id' => $meta_box_id), $this->mycred_type)) {
                return;
            }

            // Award points
            $this->core->add_creds(
                $reference,
                $user_id,
                $this->prefs['creds'],
                $this->prefs['log'],
                $post_id,
                array('ref_type' => 'post', 'meta_box_id' => $meta_box_id, 'post_type' => $post->post_type),
                $this->mycred_type
            );
        }

        /**
         * Preference for Meta Box Specific Post Save Hook
         * @since 1.0
         * @version 1.0
         */
        public function preferences()
        {
            $prefs = $this->prefs;
            
            // Get all registered meta boxes
            $meta_boxes = array();
            if (function_exists('rwmb_get_registry')) {
                $registry = rwmb_get_registry('meta_box');
                $all_meta_boxes = $registry->all();
                foreach ($all_meta_boxes as $id => $meta_box) {
                    // Only get post meta boxes (check object_type)
                    $object_type = method_exists($meta_box, 'get_object_type') ? $meta_box->get_object_type() : 'post';
                    
                    if ($object_type !== 'post') {
                        continue;
                    }
                    
                    // Get title - check both magic property and array
                    $title = isset($meta_box->meta_box['title']) ? $meta_box->meta_box['title'] : (isset($meta_box->title) ? $meta_box->title : '');
                    $meta_boxes[$id] = !empty($title) ? $title : $id;
                }
            }
            ?>

            <div class="hook-instance">
                <h3><?php esc_html_e('Saving Specific Meta Box on Post', 'mycred-toolkit'); ?></h3>
                <div class="row">
                    <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo esc_attr($this->field_id('meta_box_id')); ?>"><?php esc_html_e('Meta Box ID', 'mycred-toolkit'); ?></label>
                            <?php if (!empty($meta_boxes)): ?>
                                <select name="<?php echo esc_attr($this->field_name('meta_box_id')); ?>" id="<?php echo esc_attr($this->field_id('meta_box_id')); ?>" class="form-control">
                                    <option value=""><?php esc_html_e('Select Meta Box', 'mycred-toolkit'); ?></option>
                                    <?php foreach ($meta_boxes as $id => $title): ?>
                                        <option value="<?php echo esc_attr($id); ?>" <?php selected($prefs['meta_box_id'], $id); ?>>
                                            <?php echo esc_html($title . ' (' . $id . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <input type="text" name="<?php echo esc_attr($this->field_name('meta_box_id')); ?>" id="<?php echo esc_attr($this->field_id('meta_box_id')); ?>" value="<?php echo esc_attr($prefs['meta_box_id']); ?>" class="form-control" placeholder="<?php esc_attr_e('Enter Meta Box ID', 'mycred-toolkit'); ?>" />
                                <span class="description"><?php esc_html_e('Enter the Meta Box ID (found in your Meta Box configuration)', 'mycred-toolkit'); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
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
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo esc_attr($this->field_id('log')); ?>"><?php esc_html_e('Log Template', 'mycred-toolkit'); ?></label>
                            <input type="text" name="<?php echo esc_attr($this->field_name('log')); ?>" id="<?php echo esc_attr($this->field_id('log')); ?>" value="<?php echo esc_attr($prefs['log']); ?>" class="form-control" />
                            <span class="description"><?php echo $this->available_template_tags(array('general')); ?></span>
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

            $data['meta_box_id'] = (!empty($data['meta_box_id'])) ? sanitize_text_field($data['meta_box_id']) : '';
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
