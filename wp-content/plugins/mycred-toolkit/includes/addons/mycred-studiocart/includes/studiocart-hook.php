<?php
if (!defined('ABSPATH'))
    exit;

/**
 * Hook for StudioCart Purchases
 * @since 1.0
 * @version 1.0
 */
if (!class_exists('myCRED_StudioCart_Hook')):
    class myCRED_StudioCart_Hook extends myCRED_Hook
    {

        /**
         * Construct
         */
        function __construct($hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY)
        {

            parent::__construct(array(
                'id' => 'studiocart_purchase',
                'defaults' => array(
                    'creds' => 1,
                    'log' => __('%plural% for purchasing a product', 'mycred-toolkit'),
                    'limit' => '0/x',
                    'check_specific_hook' => 0,
                    'specific_products' => array(
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
            // Hook into StudioCart order complete
            add_action('sc_order_complete', array($this, 'mycred_studiocart_purchase_completed'), 10, 3);
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
         * Award points for StudioCart Purchase
         */
        public function mycred_studiocart_purchase_completed($status, $order_data, $order_type)
        {
            global $wpdb;

            // Only process paid orders
            if ($status !== 'paid') {
                return;
            }

            // Get user from order data
            $user_id = 0;
            $order_id = isset($order_data['ID']) ? absint($order_data['ID']) : 0;
            $product_id = isset($order_data['product_id']) ? absint($order_data['product_id']) : 0;

            if (empty($order_id) || empty($product_id)) {
                return;
            }

            // Get user email from order meta
            $user_email = get_post_meta($order_id, '_sc_email', true);

            if (!empty($user_email)) {
                $user = get_user_by('email', strtolower($user_email));
                if ($user) {
                    $user_id = $user->ID;
                }
            }

            // If no user found, try to get from order data
            if (empty($user_id) && isset($order_data['user_id'])) {
                $user_id = absint($order_data['user_id']);
            }

            if (empty($user_id)) {
                return;
            }

            // Exclude user check
            if ($this->core->exclude_user($user_id)) {
                return;
            }

            $prefs = $this->prefs;
            $reference = 'studiocart_purchase';

            // Check if specific hook is enabled and product matches
            if (
                isset($prefs['check_specific_hook']) &&
                $prefs['check_specific_hook'] == '1' &&
                !empty($prefs['specific_products']['select_option']) &&
                in_array($product_id, $prefs['specific_products']['select_option'])
            ) {

                // Find the index of this product in the configuration
                $hook_index = array_search($product_id, $prefs['specific_products']['select_option']);

                if (
                    $hook_index !== false &&
                    !empty($prefs['specific_products']['creds']) &&
                    isset($prefs['specific_products']['creds'][$hook_index])
                ) {

                    // Specific Limit Check
                    if ($this->over_hook_limit('specific_products', $reference, $user_id)) {
                        return;
                    }

                    // Check for duplicate entry
                    if ($this->core->has_entry($reference, $order_id, $user_id, array('ref_type' => 'post'), $this->mycred_type)) {
                        return;
                    }

                    $creds = $prefs['specific_products']['creds'][$hook_index];
                    $log = isset($prefs['specific_products']['log'][$hook_index]) ? $prefs['specific_products']['log'][$hook_index] : $prefs['log'];

                    $this->core->add_creds(
                        $reference,
                        $user_id,
                        $creds,
                        $log,
                        $order_id,
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

            // Check for duplicate entry
            if ($this->core->has_entry($reference, $order_id, $user_id, array('ref_type' => 'post'), $this->mycred_type)) {
                return;
            }

            $this->core->add_creds(
                $reference,
                $user_id,
                $prefs['creds'],
                $prefs['log'],
                $order_id,
                array('ref_type' => 'post'),
                $this->mycred_type
            );
        }

        /**
         * Arrange specific hook data for display
         */
        public function mycred_studiocart_arrange_data($specific_hook_data)
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
         * Get StudioCart products
         */
        public function get_studiocart_products()
        {
            $args = array(
                'post_type' => 'sc_product',
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'orderby' => 'title',
                'order' => 'ASC'
            );
            return get_posts($args);
        }

        /**
         * Preference for StudioCart Hook
         * @since 1.0
         * @version 1.0
         */
        public function preferences()
        {
            $prefs = $this->prefs;

            // Fetch all StudioCart Products
            $products = $this->get_studiocart_products();

            ?>

            <!-- General Purchase Rewards Starts -->
            <div class="hook-instance">
                <h3><?php esc_html_e('Purchase any product', 'mycred-toolkit'); ?></h3>
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
            <!-- General Purchase Rewards Ends -->

            <!-- Specific Product Rewards Starts -->
            <?php
            $specific_data = array(
                array(
                    'creds' => 0,
                    'log' => __('%plural% for purchasing product', 'mycred-toolkit'),
                    'select_option' => 0
                ),
            );

            if (!empty($prefs['specific_products']['creds']) && count($prefs['specific_products']['creds']) > 0) {
                $specific_data = $this->mycred_studiocart_arrange_data($prefs['specific_products']);
            }

            ?>
            <div class="hook-instance" id="specific-hook-studiocart">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="hook-title">
                            <h3><?php esc_html_e('Purchase specific product', 'mycred-toolkit'); ?></h3>
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
                    <div class="studiocart_specific_row">
                        <div class="row">
                            <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label
                                        for="<?php echo $this->field_id(array('specific_products' => 'creds')); ?>"><?php echo $this->core->plural(); ?></label>
                                    <input type="text"
                                        name="<?php echo $this->specific_field_name(array('specific_products' => 'creds')); ?>"
                                        value="<?php echo $this->core->number($label['creds']); ?>" class="form-control" />
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label><?php esc_html_e('Product', 'mycred-toolkit'); ?></label>
                                    <select class="form-control mycred-studiocart-options"
                                        name="<?php echo $this->specific_field_name(array('specific_products' => 'select_option')); ?>">

                                        <option value="0"><?php esc_html_e('Select Product', 'mycred-toolkit'); ?></option>
                                        <?php
                                        if (!empty($products)) {
                                            foreach ($products as $product) {
                                                $selected = (isset($label['select_option']) && $label['select_option'] == $product->ID) ? 'selected' : '';
                                                echo '<option value="' . esc_attr($product->ID) . '" ' . $selected . '>' . esc_html($product->post_title) . '</option>';
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
                                        name="<?php echo $this->specific_field_name(array('specific_products' => 'log')); ?>"
                                        value="<?php echo esc_attr($label['log']); ?>" class="form-control" />
                                    <span
                                        class="description"><?php echo $this->available_template_tags(array('general', 'post')); ?></span>
                                </div>
                            </div>

                        </div>
                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 field_wrapper">
                                <div class="form-group textright">
                                    <button class="button button-small mycred-add-specific-studiocart-hook add_button" type="button">Add
                                        More</button>
                                    <button class="button button-small mycred-remove-specific-studiocart-hook"
                                        type="button">Remove</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
            <!-- Specific Product Rewards Ends -->

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

            if (isset($data['specific_products'])) {
                foreach ($data['specific_products']['creds'] as $key => $value) {
                    $new_val = floatval($value);
                    $data['specific_products']['creds'][$key] = $new_val;

                    $log_val = isset($data['specific_products']['log'][$key]) ? $data['specific_products']['log'][$key] : '';
                    $data['specific_products']['log'][$key] = sanitize_text_field($log_val);

                    $opt_val = isset($data['specific_products']['select_option'][$key]) ? $data['specific_products']['select_option'][$key] : 0;
                    $data['specific_products']['select_option'][$key] = intval($opt_val);
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
