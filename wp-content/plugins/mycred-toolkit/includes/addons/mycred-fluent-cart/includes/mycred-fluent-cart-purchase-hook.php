<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('myCRED_FluentCart_Purchase_Hook')) :
    class myCRED_FluentCart_Purchase_Hook extends myCRED_Hook
    {
        public function __construct($hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY)
        {
            parent::__construct(array(
                'id' => 'fluentcart_product_purchase',
                'defaults' => array(
                    'creds' => 1,
                    'log' => __('%plural% for purchasing a product in FluentCart', 'mycred-toolkit'),
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
            // Most reliable purchase completion events in FluentCart.
            add_action('fluent_cart/order_paid', array($this, 'handle_completed_order'), 10, 1);
            add_action('fluent_cart/order_paid_done', array($this, 'handle_completed_order'), 10, 1);

            // Additional compatibility events.
            add_action('fluent_cart/order_status_changed_to_completed', array($this, 'handle_completed_order'), 10, 1);
            add_action('fluent_cart/payment_status_changed_to_paid', array($this, 'handle_completed_order'), 10, 1);
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

        public function handle_completed_order($data)
        {
            if (!is_array($data) || empty($data['order'])) {
                return;
            }

            $order = $data['order'];
            $order_id = isset($order->id) ? absint($order->id) : 0;

            if (!$order_id) {
                return;
            }

            $user_id = 0;
            $customer = null;

            if (!empty($data['customer']) && is_object($data['customer'])) {
                $customer = $data['customer'];
            } elseif (is_object($order) && isset($order->customer) && is_object($order->customer)) {
                $customer = $order->customer;
            } elseif (is_object($order) && method_exists($order, 'customer')) {
                $customer = $order->customer;
            }

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

            if ($user_id === 0 || $this->core->exclude_user($user_id)) {
                return;
            }

            $items = array();
            if (!empty($order->order_items) && is_iterable($order->order_items)) {
                foreach ($order->order_items as $order_item) {
                    $items[] = $order_item;
                }
            } elseif (is_object($order) && method_exists($order, 'getProductItems')) {
                $product_items = $order->getProductItems();
                if (is_iterable($product_items)) {
                    foreach ($product_items as $order_item) {
                        $items[] = $order_item;
                    }
                }
            }

            if (empty($items)) {
                return;
            }

            $prefs = $this->prefs;
            $reference = 'fluentcart_product_purchase';
            $specific_enabled = isset($prefs['check_specific_hook']) && (int) $prefs['check_specific_hook'] === 1;
            $specific_selected = isset($prefs['specific_products']['select_option']) && is_array($prefs['specific_products']['select_option'])
                ? array_values(array_filter(array_map('absint', $prefs['specific_products']['select_option'])))
                : array();

            foreach ($items as $item) {
                $product_id = $this->get_order_item_product_id($item);
                if (!$product_id) {
                    continue;
                }

                $quantity = isset($item->quantity) ? absint($item->quantity) : 1;
                $quantity = max(1, $quantity);

                for ($i = 1; $i <= $quantity; $i++) {
                    $entry_id = $order_id . ':' . $product_id . ':' . $i;
                    $awarded_specific = false;

                    if ($specific_enabled && !empty($specific_selected)) {
                        $hook_index = $this->get_specific_product_hook_index($product_id, $item, $specific_selected);
                        if (
                            $hook_index !== false &&
                            !empty($prefs['specific_products']['creds']) &&
                            isset($prefs['specific_products']['creds'][$hook_index])
                        ) {
                            if (!$this->over_hook_limit('specific_products', $reference, $user_id) && !$this->core->has_entry($reference, $entry_id, $user_id)) {
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
                        continue;
                    }

                    if ($this->over_hook_limit('', $reference, $user_id) || $this->core->has_entry($reference, $entry_id, $user_id)) {
                        continue;
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
            }
        }

        private function get_specific_product_hook_index($product_id, $item, $specific_selected)
        {
            $candidate_ids = array($product_id);

            if (isset($item->post_id) && absint($item->post_id) > 0) {
                $candidate_ids[] = absint($item->post_id);
            }

            if (isset($item->object_id) && absint($item->object_id) > 0) {
                $candidate_ids[] = absint($item->object_id);
                $parent_product_id = $this->get_product_id_from_variation_id(absint($item->object_id));
                if ($parent_product_id > 0) {
                    $candidate_ids[] = $parent_product_id;
                }
            }

            $candidate_ids = array_values(array_unique(array_filter(array_map('absint', $candidate_ids))));

            foreach ($candidate_ids as $candidate_id) {
                $hook_index = array_search($candidate_id, $specific_selected);
                if ($hook_index !== false) {
                    return $hook_index;
                }
            }

            return false;
        }

        private function get_order_item_product_id($item)
        {
            if (isset($item->post_id) && absint($item->post_id) > 0) {
                return absint($item->post_id);
            }

            if (isset($item->object_id) && absint($item->object_id) > 0) {
                $parent_product_id = $this->get_product_id_from_variation_id(absint($item->object_id));
                if ($parent_product_id > 0) {
                    return $parent_product_id;
                }
            }

            return 0;
        }

        private function get_product_id_from_variation_id($variation_id)
        {
            global $wpdb;

            if (empty($variation_id) || !isset($wpdb)) {
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
            $products = get_posts(array(
                'post_type' => 'fluent-products',
                'post_status' => array('publish', 'draft', 'pending', 'private'),
                'numberposts' => -1,
                'orderby' => 'title',
                'order' => 'ASC',
            ));
            ?>
            <div class="hook-instance">
                <h3><?php esc_html_e('Purchased any product', 'mycred-toolkit'); ?></h3>
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
                    'log' => __('%plural% for purchasing a specific product in FluentCart', 'mycred-toolkit'),
                    'select_option' => 0,
                ),
            );

            if (!empty($prefs['specific_products']['creds']) && is_array($prefs['specific_products']['creds'])) {
                $specific_data = $this->arrange_specific_data($prefs['specific_products']);
            }
            ?>
            <div class="hook-instance" id="specific-hook-fluentcart-products">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="hook-title">
                            <h3><?php esc_html_e('Purchased specific product', 'mycred-toolkit'); ?></h3>
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

