<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$options = get_option( 'mycred_pref_woo' );
$mwp_coupons = $options['mwp_coupons'];
$email_field_enabled = isset( $mwp_coupons['email_field'] ) && $mwp_coupons['email_field'];
?>
<div class="mycred_coupons_badge_rank_container">
    <table class="mycred_coupons_badge_rank woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
        <thead>
            <tr>
                <th><span><?php echo esc_html__( 'Sno', 'mycred-woocommerce-plus' ); ?></span></th>
                <th><span><?php echo esc_html__( 'Coupon Code', 'mycred-woocommerce-plus' ); ?></span></th>
                <th><span><?php echo esc_html__( 'Amount', 'mycred-woocommerce-plus' ); ?></span></th>
                <th class="coupon_description"><span><?php echo esc_html__( 'Description', 'mycred-woocommerce-plus' ); ?></span></th>
                <th><span><?php echo esc_html__( 'Expiry date', 'mycred-woocommerce-plus' ); ?></span></th>
                <th><span><?php echo esc_html__( 'Status', 'mycred-woocommerce-plus' ); ?></span></th>
                <?php if ( $email_field_enabled ): ?>
                    <th><span><?php echo esc_html__( 'Email', 'mycred-woocommerce-plus' ); ?></span></th>
                <?php endif; ?>
                <th><span><?php echo esc_html__( 'Actions', 'mycred-woocommerce-plus' ); ?></span></th>
            </tr>
        </thead>
        <tbody>
        <?php if ( ! empty( $coupons ) ): ?>
            <?php foreach ( $coupons as $key => $coupon ):
                $coupon_status = mwp_coupon_status( $coupon->ID );
                $customer_email = get_post_meta( $coupon->ID, 'customer_email', true );
                if ( is_array( $customer_email ) ) {
                    $customer_email = implode( ', ', $customer_email ); // Convert array to comma-separated string
                }
                $is_unused = $status = __( 'Available', 'mycred-woocommerce-plus' );
                ?>
                <tr class="<?php echo esc_attr( strtolower( $coupon_status ) ); ?>">
                    <td><?php echo esc_html( $key + 1 ); ?></td>
                    <td class="coupon_code">
                        <span class="copoun_code_style"><?php echo esc_html( $coupon->post_title ); ?></span>
                    </td>
                    <td>
                        <?php
                        $coupon_amount  = get_post_meta( $coupon->ID, 'coupon_amount', true );
                        $dispaly_amount = $coupon_amount . '% Off';

                        if ( 'percent' != get_post_meta( $coupon->ID, 'discount_type', true ) ) {
                            $currency       = get_woocommerce_currency_symbol();
                            $dispaly_amount = $currency . $coupon_amount . ' Off';
                        }

                        echo esc_html( $dispaly_amount );
                        ?>
                    </td>
                    <td><?php echo esc_html( $coupon->post_excerpt ); ?></td>
                    <td>
                        <?php
                        $date_expires = get_post_meta( $coupon->ID, 'date_expires', true );
                        echo esc_html( ( ! empty( $date_expires ) ? $date_expires : '-' ) );
                        ?>
                    </td>    
                    <td><?php echo esc_html( $coupon_status ); ?></td>
                    <?php if ( $email_field_enabled ): ?>
                        <td><?php echo esc_html( ! empty( $customer_email ) ? $customer_email : '-' ); ?></td>
                    <?php endif; ?>
                        <td>
                        <?php if ( $coupon_status === 'Available' ): ?>
                            <form method="post" class="delete_coupon_form" action="">
                                <input type="hidden" name="coupon_id" value="<?php echo esc_attr( $coupon->ID ); ?>">
                                <button type="button" class="button delete_coupon_button"> <?php esc_html_e( 'Delete', 'mycred-woocommerce-plus' ); ?> </button>
                            </form>
                        <?php else: ?>
                            <span class="disabled"><?php echo esc_html__( 'N/A', 'mycred-woocommerce-plus' ); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td class="no_coupons_found" colspan="10">
                    <?php echo esc_html__( 'No coupons found.', 'mycred-woocommerce-plus' ); ?>
                </td>
            </tr>   
        <?php endif; ?>
        </tbody>
    </table>
    <?php echo do_shortcode('[mycred_coupon_code_generator]'); ?>
</div>

<?php
if ( isset( $_POST['delete_coupon'] ) && ! empty( $_POST['coupon_id'] ) ) {
    $coupon_id = intval( $_POST['coupon_id'] );
    wp_delete_post( $coupon_id, true );
    wp_redirect( esc_url( remove_query_arg( [ 'delete_coupon' ] ) ) ); 
    exit;
}
?>
