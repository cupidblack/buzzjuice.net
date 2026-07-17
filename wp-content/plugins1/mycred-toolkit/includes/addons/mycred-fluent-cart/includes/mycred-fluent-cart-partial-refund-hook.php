<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('myCRED_FluentCart_Partial_Refund_Hook')) :
    class myCRED_FluentCart_Partial_Refund_Hook extends myCRED_Hook
    {
        public function __construct($hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY)
        {
            parent::__construct(array(
                'id' => 'fluentcart_order_partial_refund',
                'defaults' => array(
                    'creds' => 1,
                    'log'   => __('%plural% for order partially refunded in FluentCart', 'mycred-toolkit'),
                    'limit' => '0/x',
                )
            ), $hook_prefs, $type);
        }

        public function run()
        {
            add_action('fluent_cart/order_partially_refunded', array($this, 'mycred_fluentcart_partial_refund'), 10, 1);
        }

        public function mycred_fluentcart_partial_refund($data)
        {
            if (!is_array($data) || empty($data['order'])) {
                return;
            }

            $order = $data['order'];
            $order_id = isset($order->id) ? absint($order->id) : 0;
            if ($order_id === 0) {
                return;
            }

            $user_id = 0;
            $customer = null;

            if (!empty($data['customer']) && is_object($data['customer'])) {
                $customer = $data['customer'];
            } elseif (is_object($order) && isset($order->customer) && is_object($order->customer)) {
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

            $reference = 'fluentcart_order_partial_refund';

            if ($this->over_hook_limit('', $reference, $user_id)) {
                return;
            }

            if ($this->core->has_entry($reference, $order_id, $user_id, array(), $this->mycred_type)) {
                return;
            }

            $this->core->add_creds(
                $reference,
                $user_id,
                $this->prefs['creds'],
                $this->prefs['log'],
                $order_id,
                array('ref_type' => 'post'),
                $this->mycred_type
            );
        }

        public function preferences()
        {
            $prefs = $this->prefs;
            ?>
            <div class="hook-instance">
                <h3><?php esc_html_e('Order Partially Refunded', 'mycred-toolkit'); ?></h3>
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
                                    'div' => array('class' => array()),
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
                            <span class="description"><?php echo $this->available_template_tags(array('general')); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }

        public function sanitise_preferences($data)
        {
            $data['creds'] = (!empty($data['creds'])) ? floatval($data['creds']) : 0;
            $data['log'] = (!empty($data['log'])) ? sanitize_text_field($data['log']) : $this->defaults['log'];

            if (isset($data['limit']) && isset($data['limit_by'])) {
                $limit = sanitize_text_field($data['limit']);
                if ($limit === '') {
                    $limit = 0;
                }
                $data['limit'] = $limit . '/' . $data['limit_by'];
                unset($data['limit_by']);
            }

            return $data;
        }
    }
endif;

