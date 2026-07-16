<?php
if (!defined('ABSPATH'))
    exit;

/**
 * Hook for Meta Box - Save Specific Field (Post)
 * @since 1.0
 * @version 1.0
 */
if (!class_exists('myCRED_Meta_Box_Field_Post_Save_Hook')):
    class myCRED_Meta_Box_Field_Post_Save_Hook extends myCRED_Hook
    {

        /**
         * Construct
         */
        function __construct($hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY)
        {

            parent::__construct(array(
                'id' => 'meta_box_save_field_post',
                'defaults' => array(
                    'field_id' => '',
                    'field_value' => '',
                    'creds' => 1,
                    'log' => __('%plural% for saving specific meta field on post', 'mycred-toolkit'),
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
            add_action('updated_post_meta', array($this, 'mycred_meta_box_save_field_post'), 10, 4);
        }

        /**
         * Award points for Meta Box - Save Specific Field (Post)
         * @param int $meta_id Meta ID
         * @param int $post_id Post ID
         * @param string $meta_key Meta key
         * @param mixed $meta_value Meta value
         */
        public function mycred_meta_box_save_field_post($meta_id, $post_id, $meta_key, $meta_value)
        {
            // Check if field ID is set
            if (empty($this->prefs['field_id'])) {
                return;
            }

            $field_id = sanitize_text_field($this->prefs['field_id']);
            
            // Check if this is the field we're looking for
            if ($meta_key !== $field_id) {
                return;
            }

            // Check if Meta Box is active
            if (!function_exists('rwmb_get_object_fields')) {
                return;
            }

            // Get fields for this post
            $fields = rwmb_get_object_fields($post_id);
            if (empty($fields)) {
                return;
            }

            // Check if this meta key belongs to Meta Box
            if (!isset($fields[$meta_key])) {
                return;
            }

            // Check field value if specified (optional)
            if (!empty($this->prefs['field_value'])) {
                $expected_value = trim($this->prefs['field_value']);
                
                // Handle different value types
                if (is_array($meta_value)) {
                    // For arrays, serialize and compare, or check if expected value is in array
                    $saved_value = maybe_serialize($meta_value);
                    $saved_value = trim($saved_value);
                    
                    // Also check if expected value exists in array (for select multiple, checkbox, etc.)
                    if (!in_array($expected_value, $meta_value, true) && $saved_value !== $expected_value) {
                        return;
                    }
                } else {
                    // For single values, convert to string and compare
                    $saved_value = trim((string) $meta_value);
                    
                    // If values don't match, skip awarding points
                    if ($saved_value !== $expected_value) {
                        return;
                    }
                }
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

            $reference = 'meta_box_save_field_post';

            // Limit Check
            if ($this->over_hook_limit('', $reference, $user_id)) {
                return;
            }

            // Check for duplicate entry - prevent awarding points for the same save action
            if ($this->core->has_entry($reference, $meta_id, $user_id, array('ref_type' => 'post', 'field_id' => $field_id), $this->mycred_type)) {
                return;
            }

            // Award points
            $this->core->add_creds(
                $reference,
                $user_id,
                $this->prefs['creds'],
                $this->prefs['log'],
                $meta_id,
                array('ref_type' => 'post', 'field_id' => $field_id, 'post_id' => $post_id, 'post_type' => $post->post_type),
                $this->mycred_type
            );
        }

        /**
         * Preference for Meta Box Field Post Save Hook
         * @since 1.0
         * @version 1.0
         */
        public function preferences()
        {
            $prefs = $this->prefs;

            
            // Get all Meta Box fields from posts
            $fields = array();
            if (function_exists('rwmb_get_registry')) {
                $registry = rwmb_get_registry('meta_box');
                $all_meta_boxes = $registry->all();
                foreach ($all_meta_boxes as $meta_box) {
                    // Only get post meta boxes (check object_type)
                    $object_type = method_exists($meta_box, 'get_object_type') ? $meta_box->get_object_type() : 'post';
                    
                    if ($object_type !== 'post') {
                        continue;
                    }
                    
                    // Get post types - check both magic property and array
                    $post_types = isset($meta_box->meta_box['post_types']) ? $meta_box->meta_box['post_types'] : (isset($meta_box->post_types) ? $meta_box->post_types : array());
                    
                    if (empty($post_types)) {
                        continue;
                    }
                    
                    // Get fields - check both magic property and array
                    $meta_box_fields = isset($meta_box->meta_box['fields']) ? $meta_box->meta_box['fields'] : (isset($meta_box->fields) ? $meta_box->fields : array());
                    
                    if (!empty($meta_box_fields)) {
                        foreach ($meta_box_fields as $field) {
                            if (isset($field['id'])) {
                                $field_name = !empty($field['name']) ? $field['name'] : $field['id'];
                                $fields[$field['id']] = $field_name . ' (' . $field['id'] . ')';
                            }
                        }
                    }
                }
            }
            ?>

            <div class="hook-instance">
                <h3><?php esc_html_e('Saving Specific Field on Post', 'mycred-toolkit'); ?></h3>
                <div class="row">
                    <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo esc_attr($this->field_id('field_id')); ?>"><?php esc_html_e('Field ID', 'mycred-toolkit'); ?></label>
                            <?php if (!empty($fields)): ?>
                                <select name="<?php echo esc_attr($this->field_name('field_id')); ?>" id="<?php echo esc_attr($this->field_id('field_id')); ?>" class="form-control">
                                    <option value=""><?php esc_html_e('Select Field', 'mycred-toolkit'); ?></option>
                                    <?php foreach ($fields as $id => $name): ?>
                                        <option value="<?php echo esc_attr($id); ?>" <?php selected($prefs['field_id'], $id); ?>>
                                            <?php echo esc_html($name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <input type="text" name="<?php echo esc_attr($this->field_name('field_id')); ?>" id="<?php echo esc_attr($this->field_id('field_id')); ?>" value="<?php echo esc_attr($prefs['field_id']); ?>" class="form-control" placeholder="<?php esc_attr_e('Enter Field ID', 'mycred-toolkit'); ?>" />
                                <span class="description"><?php esc_html_e('Enter the Meta Box Field ID (found in your Meta Box field configuration)', 'mycred-toolkit'); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo esc_attr($this->field_id('field_value')); ?>"><?php esc_html_e('Field Value (Optional)', 'mycred-toolkit'); ?></label>
                            <input type="text" name="<?php echo esc_attr($this->field_name('field_value')); ?>" id="<?php echo esc_attr($this->field_id('field_value')); ?>" value="<?php echo esc_attr($prefs['field_value']); ?>" class="form-control" placeholder="<?php esc_attr_e('Leave empty to award for any value', 'mycred-toolkit'); ?>" />
                            <span class="description"><?php esc_html_e('Enter specific field value to match. Leave empty to award points for any value saved to this field.', 'mycred-toolkit'); ?></span>
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

            $data['field_id'] = (!empty($data['field_id'])) ? sanitize_text_field($data['field_id']) : '';
            $data['field_value'] = (!empty($data['field_value'])) ? sanitize_text_field($data['field_value']) : '';
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
