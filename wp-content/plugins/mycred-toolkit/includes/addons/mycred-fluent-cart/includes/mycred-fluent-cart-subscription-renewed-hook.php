<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('myCRED_FluentCart_Subscription_Renewed_Hook')) :
    class myCRED_FluentCart_Subscription_Renewed_Hook extends myCRED_Hook
    {
        public function __construct($hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY)
        {
            parent::__construct(array(
                'id' => 'fluentcart_subscription_renewed',
                'defaults' => array(
                    'creds' => 1,
                    'log' => __('%plural% for renewing a subscription in FluentCart', 'mycred-toolkit'),
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

        public function run()
        {
            add_action('fluent_cart/subscription_renewed', array($this, 'handle_subscription_renewed'), 10, 1);
        }

        public function specific_field_name($field = '')
        {
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
                $option_id .= '_' . $this->mycred_type;
            }

            return $option_id . '[hook_prefs][' . $this->id . ']' . $field . '[]';
        }

        public function handle_subscription_renewed($data)
        {
            if (!is_array($data) || empty($data['subscription'])) {
                return;
            }

            $subscription = $data['subscription'];
            $subscription_id = isset($subscription->id) ? absint($subscription->id) : 0;
            if ($subscription_id === 0) {
                return;
            }

            $user_id = $this->resolve_user_id($data, $subscription);
            if ($user_id === 0 || $this->core->exclude_user($user_id)) {
                return;
            }

            $product_id = $this->resolve_subscription_product_id($subscription);
            if ($product_id === 0) {
                return;
            }

            $renewal_order_id = $this->resolve_renewal_order_id($data, $subscription);

            $quantity = isset($subscription->quantity) ? absint($subscription->quantity) : 1;
            $quantity = max(1, $quantity);

            $prefs = $this->prefs;
            $reference = 'fluentcart_subscription_renewed';
            $specific_enabled = isset($prefs['check_specific_hook']) && (int) $prefs['check_specific_hook'] === 1;
            $specific_selected = isset($prefs['specific_products']['select_option']) && is_array($prefs['specific_products']['select_option'])
                ? array_values(array_filter(array_map('absint', $prefs['specific_products']['select_option'])))
                : array();

            for ($i = 1; $i <= $quantity; $i++) {
                $entry_id = $subscription_id . ':' . $product_id . ':' . $renewal_order_id . ':' . $i;
                $this->award_subscription_renewal_points($user_id, $product_id, $entry_id, $reference, $prefs, $specific_enabled, $specific_selected);
            }
        }

        private function award_subscription_renewal_points($user_id, $product_id, $entry_id, $reference, $prefs, $specific_enabled, $specific_selected)
        {
            $awarded_specific = false;

            if ($specific_enabled && !empty($specific_selected)) {
                $hook_index = array_search($product_id, $specific_selected);
                if (
                    $hook_index !== false &&
                    !empty($prefs['specific_products']['creds']) &&
                    isset($prefs['specific_products']['creds'][$hook_index])
                ) {
                    if (
                        !$this->over_hook_limit('specific_products', $reference, $user_id) &&
                        !$this->core->has_entry($reference, $entry_id, $user_id)
                    ) {
                        $specific_log = isset($prefs['specific_products']['log'][$hook_index]) && $prefs['specific_products']['log'][$hook_index] !== ''
                            ? $prefs['specific_products']['log'][$hook_index]
                            : $prefs['log'];

                        $this->core->add_creds(
                            $reference,
                            $user_id,
                            $prefs['specific_products']['creds'][$hook_index],
                            $specific_log,
                            $entry_id,
                            array('ref_type' => 'post'),
                            $this->mycred_type
                        );
                    }

                    $awarded_specific = true;
                }
            }

            if ($awarded_specific) {
                return;
            }

            if ($this->over_hook_limit('', $reference, $user_id) || $this->core->has_entry($reference, $entry_id, $user_id)) {
                return;
            }

            $this->core->add_creds(
                $reference,
                $user_id,
                $prefs['creds'],
                $prefs['log'],
                $entry_id,
                array('ref_type' => 'post'),
                $this->mycred_type
            );
        }

        private function resolve_user_id($data, $entity)
        {
            $customer = null;
            if (!empty($data['customer']) && is_object($data['customer'])) {
                $customer = $data['customer'];
            } elseif (is_object($entity) && isset($entity->customer) && is_object($entity->customer)) {
                $customer = $entity->customer;
            } elseif (is_object($entity) && method_exists($entity, 'customer')) {
                $customer = $entity->customer;
            }

            $user_id = 0;
            if ($customer && !empty($customer->user_id)) {
                $user_id = absint($customer->user_id);
            }
            if ($user_id === 0 && $customer && method_exists($customer, 'getWpUserId')) {
                $user_id = absint($customer->getWpUserId(true));
            }
            if ($user_id === 0 && $customer && !empty($customer->email)) {
                $wp_user = get_user_by('email', $customer->email);
                if ($wp_user && !empty($wp_user->ID)) {
                    $user_id = (int) $wp_user->ID;
                }
            }

            return $user_id;
        }

        private function resolve_subscription_product_id($subscription)
        {
            if (isset($subscription->product_id) && absint($subscription->product_id) > 0) {
                return absint($subscription->product_id);
            }

            if (isset($subscription->variation_id) && absint($subscription->variation_id) > 0) {
                return $this->resolve_product_id_from_variation(absint($subscription->variation_id));
            }

            return 0;
        }

        private function resolve_product_id_from_variation($variation_id)
        {
            global $wpdb;
            if (!isset($wpdb) || $variation_id <= 0) {
                return 0;
            }

            $table = $wpdb->prefix . 'fct_product_variations';
            $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
            if ($exists !== $table) {
                return 0;
            }

            $product_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT post_id FROM {$table} WHERE id = %d LIMIT 1",
                    absint($variation_id)
                )
            );

            return absint($product_id);
        }

        private function resolve_renewal_order_id($data, $subscription)
        {
            if (!empty($data['order']) && is_object($data['order']) && isset($data['order']->id)) {
                return absint($data['order']->id);
            }
            if (isset($subscription->bill_count) && absint($subscription->bill_count) > 0) {
                return absint($subscription->bill_count);
            }
            return time();
        }

        public function arrange_specific_data($specific_hook_data)
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

        public function preferences()
        {
            $prefs = $this->prefs;
            global $wpdb;

            $products = array();
            $variation_table = $wpdb->prefix . 'fct_product_variations';
            $posts_table = $wpdb->posts;

            $has_variation_table = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $variation_table));
            if ($has_variation_table === $variation_table) {
                $sql = $wpdb->prepare(
                    "SELECT p.ID, p.post_title
                     FROM {$posts_table} p
                     INNER JOIN {$variation_table} v ON p.ID = v.post_id
                     WHERE p.post_type = %s
                       AND p.post_status IN ('publish','draft','pending','private')
                       AND v.payment_type = %s
                     GROUP BY p.ID, p.post_title
                     ORDER BY p.post_title ASC",
                    'fluent-products',
                    'subscription'
                );
                $products = $wpdb->get_results($sql);
            }
            ?>
            <div class="hook-instance">
                <h3><?php esc_html_e('Renewed any subscription', 'mycred-toolkit'); ?></h3>
                <div class="row">
                    <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo esc_attr($this->field_id('creds')); ?>"><?php echo esc_html($this->core->plural()); ?></label>
                            <input type="text" name="<?php echo esc_attr($this->field_name('creds')); ?>"
                                   id="<?php echo esc_attr($this->field_id('creds')); ?>"
                                   value="<?php echo esc_attr($this->core->number($prefs['creds'])); ?>" class="form-control" />
                        </div>
                    </div>
                    <div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo esc_attr($this->field_id('log')); ?>"><?php esc_html_e('Log Template', 'mycred-toolkit'); ?></label>
                            <input type="text" name="<?php echo esc_attr($this->field_name('log')); ?>"
                                   id="<?php echo esc_attr($this->field_id('log')); ?>" value="<?php echo esc_attr($prefs['log']); ?>"
                                   class="form-control" />
                            <span class="description"><?php echo wp_kses_post($this->available_template_tags(array('general'))); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <?php
            $specific_data = array(
                array(
                    'creds' => 0,
                    'log' => __('%plural% for renewing a subscription for a specific product in FluentCart', 'mycred-toolkit'),
                    'select_option' => 0,
                ),
            );

            if (!empty($prefs['specific_products']['creds']) && is_array($prefs['specific_products']['creds'])) {
                $specific_data = $this->arrange_specific_data($prefs['specific_products']);
            }
            ?>
            <div class="hook-instance" id="specific-hook-fluentcart-subscription-renewed-products">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="hook-title">
                            <h3><?php esc_html_e('Renewed subscription for specific product', 'mycred-toolkit'); ?></h3>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <?php
                    $is_enabled = isset($prefs['check_specific_hook']) && (int) $prefs['check_specific_hook'] === 1;
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

                <?php foreach ($specific_data as $hook_idx => $label) : ?>
                    <div class="fluentcart_products_specific_row">
                        <div class="row">
                            <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label><?php echo esc_html($this->core->plural()); ?></label>
                                    <input type="text"
                                           name="<?php echo esc_attr($this->specific_field_name(array('specific_products' => 'creds'))); ?>"
                                           value="<?php echo esc_attr($this->core->number($label['creds'])); ?>" class="form-control" />
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label><?php esc_html_e('Select Product', 'mycred-toolkit'); ?></label>
                                    <select class="form-control mycred-fluentcart-products-options"
                                            name="<?php echo esc_attr($this->specific_field_name(array('specific_products' => 'select_option'))); ?>">
                                        <option value="0"><?php esc_html_e('Select Product', 'mycred-toolkit'); ?></option>
                                        <?php foreach ($products as $product) : ?>
                                            <option value="<?php echo esc_attr($product->ID); ?>" <?php selected((int) $label['select_option'], (int) $product->ID); ?>>
                                                <?php echo esc_html($product->post_title); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label><?php esc_html_e('Log Template', 'mycred-toolkit'); ?></label>
                                    <input type="text"
                                           name="<?php echo esc_attr($this->specific_field_name(array('specific_products' => 'log'))); ?>"
                                           value="<?php echo esc_attr($label['log']); ?>" class="form-control" />
                                    <span class="description"><?php echo wp_kses_post($this->available_template_tags(array('general'))); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 field_wrapper">
                                <div class="form-group textright">
                                    <button class="button button-small mycred-add-specific-fluentcart-products-hook add_button"
                                            type="button">Add More</button>
                                    <button class="button button-small mycred-remove-specific-fluentcart-products-hook"
                                            type="button">Remove</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="hook-instance">
                <h3><?php esc_html_e('Limit', 'mycred-toolkit'); ?></h3>
                <div class="row">
                    <div class="col-lg-5 col-md-5 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <?php add_filter('mycred_hook_limits', array($this, 'custom_limit')); ?>
                            <label for="<?php echo esc_attr($this->field_id('limit')); ?>"></label>
                            <?php echo wp_kses($this->hook_limit_setting($this->field_name('limit'), $this->field_id('limit'), esc_attr($prefs['limit'])), array(
                                'div' => array('class' => array()),
                                'input' => array(
                                    'type' => array(),
                                    'size' => array(),
                                    'class' => array(),
                                    'name' => array(),
                                    'id' => array(),
                                    'value' => array(),
                                ),
                                'select' => array(
                                    'name' => array(),
                                    'id' => array(),
                                    'class' => array(),
                                ),
                                'option' => array(
                                    'value' => array(),
                                    'selected' => array(),
                                ),
                            )); ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }

        public function sanitise_preferences($data)
        {
            $data['creds'] = (!empty($data['creds'])) ? floatval($data['creds']) : 0;
            $data['check_specific_hook'] = (isset($data['check_specific_hook']) && $data['check_specific_hook'] == '1') ? 1 : 0;
            $data['log'] = (!empty($data['log'])) ? sanitize_text_field($data['log']) : $this->defaults['log'];

            if (isset($data['limit']) && isset($data['limit_by'])) {
                $limit = sanitize_text_field($data['limit']);
                if ($limit === '') {
                    $limit = 0;
                }
                $data['limit'] = $limit . '/' . $data['limit_by'];
                unset($data['limit_by']);
            }

            if (isset($data['specific_products']) && is_array($data['specific_products'])) {
                foreach ((array) $data['specific_products']['creds'] as $key => $value) {
                    $data['specific_products']['creds'][$key] = floatval($value);
                    $data['specific_products']['log'][$key] = sanitize_text_field(isset($data['specific_products']['log'][$key]) ? $data['specific_products']['log'][$key] : '');
                    $data['specific_products']['select_option'][$key] = absint(isset($data['specific_products']['select_option'][$key]) ? $data['specific_products']['select_option'][$key] : 0);
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
