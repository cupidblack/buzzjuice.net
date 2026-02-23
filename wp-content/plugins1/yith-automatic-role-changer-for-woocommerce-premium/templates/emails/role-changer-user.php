<?php
/**
 * Email for customer notification of role granted to user
 *
 * @var string                        $email_heading
 * @var YITH_Role_Changer_Admin_Email $email
 * @var string                        $message
 * @var array                         $roles
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\AutomaticRoleChanger\Templates
 */

defined( 'ABSPATH' ) || exit;
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php echo wp_kses_post( wpautop( wptexturize( $message ) ) ); ?>

<?php
do_action( 'woocommerce_email_footer', $email );
