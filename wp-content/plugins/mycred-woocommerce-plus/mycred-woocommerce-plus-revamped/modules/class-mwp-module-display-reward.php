<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MWP_Display_Reward_Module' ) ) :
	class MWP_Display_Reward_Module extends MWP_Module {
		
		public function __construct() {

			parent::__construct( 'MWP_Display_Reward_Module', array(
                'module_name' => 'mwp_display_reward',
                'defaults'    => array(
                 'single_product'         => 0,
                 'checkout_product_meta'  => 0,
                 'checkout_product_total' => 0,
                 'cart_product_meta'      => 0,
                 'cart_product_total'     => 0
             ),
                'add_tab'     => true,
                'title'       => __('Display Reward','mycred-woocommerce-plus'),
                'icon'        => 'dashicons-welcome-view-site',
                'tab_pos'     => 13
            ) );
		}

        public function module_init() {

            wp_enqueue_style( 'mwp-style' );

            add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'woocommerce_before_add_to_cart_button' ) );
            add_action( 'woocommerce_checkout_before_customer_details', array( $this, 'woocommerce_review_order_before_order_total' ), 10 );    
            add_action( 'woocommerce_before_cart_table', array( $this, 'woocommerce_review_order_before_order_total' ), 10 );
            add_action( 'wp_head', array( $this, 'wp_head' ) );
            add_action( 'woocommerce_before_add_to_cart_quantity', array( $this, 'display_dropdown_variation_add_cart' ) );
        }

        public function woocommerce_before_add_to_cart_button() {

            $product = wc_get_product( get_the_ID() );

            $options = get_option('mycred_pref_woo');
            $settings = $options['mwp_display_reward'];

            if ( $settings['single_product'] == '1' ) {

                if ( $product->is_type( 'simple' ) ) {

                    $mycred_rewards = get_post_meta( get_the_ID(), 'mycred_reward', true );
                    $i = 1;
                    if ( ! empty( $mycred_rewards ) ) {
                        $count = count( $mycred_rewards );
                    }
                    if ( $mycred_rewards ) {
                        echo '<div id="rewards_points_wrap">';

                        foreach ( $mycred_rewards as $mycred_reward_key => $mycred_reward_value ) {
                            $is_plural_reward = ( $mycred_reward_value < 2 );
                            $mycred_point_type_name = mycred_get_point_type_name( $mycred_reward_key, $is_plural_reward );
                            /* translators: %s: Earn points and name */
                            echo '<span class="rewards_span"> ' . esc_attr( sprintf( __( 'Earn %1$s %2$s', 'mycred-woocommerce-plus' ), $mycred_reward_value, $mycred_point_type_name ) ) . '</span>';
                        }
                        echo '</div>';
                    } 
                } else {
                    echo '<div id="rewards_points_wrap"></div>';
                }
            }
        }

        public function display_dropdown_variation_add_cart() {
            global $product;

            $options = get_option('mycred_pref_woo');
            $settings = $options['mwp_display_reward'];

            if ($product->is_type('variable') && $settings['single_product'] == '1') {
                ?>
                <script>
                    jQuery(document).ready(function ($) {
                        function call_rewards_points() {
                            if ('' != $('input.variation_id').val() && 0 != $('input.variation_id').val()) {
                                var var_id = $('input.variation_id').val();
                                var template = '';

                                // Store total points per point type
                                var total_points = {};

                                // Sum up rewards from mycred_variable_rewards
                                if (typeof (mycred_variable_rewards[var_id]) != 'undefined' && mycred_variable_rewards[var_id] != null) {
                                    $.each(mycred_variable_rewards[var_id], function (index, value) {
                                        if (!total_points[index]) {
                                            total_points[index] = 0;
                                        }
                                        total_points[index] += parseFloat(value);
                                    });
                                }

                                // Generate template with total rewards
                                $.each(total_points, function (index, value) {
                                    if (value > 0) {
                                        template += '<span class="rewards_span"> ' + label_Earn + ' ' + value + ' ' + mycred_point_types[index] + '</span>';
                                    }
                                });

                                document.getElementById("rewards_points_wrap").innerHTML = template;
                            } else {
                                document.getElementById("rewards_points_wrap").innerHTML = '';
                            }
                        }

                        call_rewards_points();
                        $('input.variation_id').change(function () {
                            call_rewards_points();
                        });
                    });
                </script>
                <?php
            }
        }

        public function wp_head() {
            if (is_product()) {
                $mycred_rewards_array = array();
                $product = wc_get_product(get_the_ID());
                $hooks_rewards_array = array();
                $mycred = mycred_get_types();

                if ( $product->is_type('variable') ) {
                    $available_variations = $product->get_available_variations();
                    foreach ($available_variations as $variation) {
                        $variation_id = $variation['variation_id'];
                        $mycred_rewards = get_post_meta($variation_id, '_mycred_reward', true);
                        $parent_reward = (array)get_post_meta(get_the_ID(), 'mycred_reward', true);

                        // Get variable-specific rewards
                        if (!empty($mycred_rewards)) {
                            $mycred_rewards_array[$variation_id] = $mycred_rewards;
                        } elseif (!empty($parent_reward)) {
                            $mycred_rewards_array[$variation_id] = $parent_reward;
                        }

                    }
                }

                if (!empty($mycred_rewards_array)) {
                    ?>
                    <script type="text/javascript">
                        var mycred_variable_rewards = <?php echo json_encode($mycred_rewards_array); ?>;
                        var mycred_point_types = <?php echo json_encode($mycred); ?>;
                        var label_Earn = <?php echo "'" . esc_html__('Earn ', 'mycred-woocommerce-plus') . "'"; ?>;
                    </script>
                    <?php
                }
            }
        }

        public function woocommerce_review_order_before_order_total() {

            /**
            * Action woocommerce_set_cart_cookies
            * 
            * @since 1.0
            **/
            do_action( 'woocommerce_set_cart_cookies', true );

            $mycred         = new myCRED_Settings();
            $decimal_format = $mycred->format['decimals'];

            $total_reward_point = array();
            $message   = '';
            $options = get_option('mycred_pref_woo');
            $settings = $options['mwp_display_reward'];

            foreach ( WC()->cart->get_cart() as $cart_item ) {

                $product = wc_get_product( $cart_item['product_id'] );
                
                if ( $product->is_type( 'variable' ) ) {

                    $mycred_rewards = get_post_meta( $cart_item['variation_id'], '_mycred_reward', true );

                } else {

                    $mycred_rewards = get_post_meta( $cart_item['product_id'], 'mycred_reward', true );

                }
                
                if ( $mycred_rewards ) {

                    foreach ( $mycred_rewards as $mycred_reward_key => $mycred_reward_value ) {

                        $mycred_hook_reward_value = get_hooks_rewards_for_product( $product->get_id(), $mycred_reward_key );

                        $calculated_reward_value = $mycred_reward_value * $cart_item['quantity'];

                        $total_reward_value = $calculated_reward_value + $mycred_hook_reward_value;

                        if ( isset( $total_reward_point[ $mycred_reward_key ] ) ) {

                            $total_reward_point[ $mycred_reward_key ]['total'] = $total_reward_point[ $mycred_reward_key ]['total'] + $mycred_reward_value * $cart_item['quantity'];

                        } else {
                            
                            $total_reward_point[ $mycred_reward_key ] = array(
                                'name'  => $mycred_reward_key,
                                'total' => $total_reward_value,
                            );

                        }

                    }

                } else {

                    $point_types = mycred_get_types(true);
                    foreach ( $point_types as $type_key => $type_label ) {
                        $mycred_hook_reward_value = get_hooks_rewards_for_product( $product->get_id(), $type_key ); 

                        if( $mycred_hook_reward_value > 0 ) {
                          $total_reward_point[ $type_key ] = array(
                            'name'  => $type_key,
                            'total' => $mycred_hook_reward_value,
                        );   
                      }

                  }

              }

          }


          $message .= __( 'Earn ', 'mycred-woocommerce-plus' );
          $i        = 1;
          $count    = count( $total_reward_point );

          if ( ! empty( $total_reward_point ) ) {

            foreach ( $total_reward_point as $mycred_reward_key => $mycred_reward_value ) {

                $mycred = mycred( $mycred_reward_key );

                if ( 1 == $count ) {

                    $message .= $mycred->format_creds( $mycred_reward_value['total'] ) . ' ' . $mycred->plural();

                } else {

                    if ( $i < $count ) {

                        $message .= $mycred->format_creds( $mycred_reward_value['total'] ) . ' ' . $mycred->plural() . ', ';

                    } else {

                        $message .= ' and ' . $mycred->format_creds( $mycred_reward_value['total'] ) . ' ' . $mycred->plural();

                    }

                }

                $i++;

            }
        }

        if ( ( is_cart() && 1 == $settings['cart_product_total'] ) || ( is_checkout() && 1 == $settings['checkout_product_total'] ) ) {

            if ( ! empty( $total_reward_point ) ) {

                wc_print_notice( __( $message, 'mycred-woocommerce-plus' ), $notice_type = 'notice' );

            }

        }
    }

    public function admin_settings( $core ) {
        $settings = $core->woocommerce[ $this->module_name ];
        $this->load_template( 
            'display_rewards_settings', 
            'display-rewards-module/admin-settings.php',
            array( 'settings' => $settings ) 
        );
    }

    public function sanitize_settings( $new_data, $data, $core ) {
        $new_data[ $this->module_name ]['single_product'] = isset( $data[ $this->module_name ]['single_product'] ) ? 1 : 0;
        $new_data[ $this->module_name ]['checkout_product_meta'] = isset( $data[ $this->module_name ]['checkout_product_meta'] ) ? 1 : 0;
        $new_data[ $this->module_name ]['checkout_product_total'] = isset( $data[ $this->module_name ]['checkout_product_total'] ) ? 1 : 0;
        $new_data[ $this->module_name ]['cart_product_meta'] = isset( $data[ $this->module_name ]['cart_product_meta'] ) ? 1 : 0;
        $new_data[ $this->module_name ]['cart_product_total'] = isset( $data[ $this->module_name ]['cart_product_total'] ) ? 1 : 0;

        return $new_data;
    }

}

endif;

function mwp_load_display_reward_module() {

   $module = new MWP_Display_Reward_Module();
   $module->load();

}
mwp_load_display_reward_module();