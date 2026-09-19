<?php
/**
 * The template for members activate
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/activate.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */
bp_nouveau_activation_hook( 'before', 'page' ); ?>

<div class="page" id="activate-page">

	<?php
	bp_nouveau_template_notices();
	bp_nouveau_activation_hook( 'before', 'content' );



?>

<div id="login-block" style="display: none;">
    <?php get_template_part('templates/login-block'); ?>
</div>
<script>
jQuery(document).ready(function($) {
  if ($("aside.bp-feedback.bp-messages.bp-template-notice.error").length) {
    $("#login-block").show();
    $("#activate-page > p").hide();
    $("form#activation-form").hide();
  }
});
</script>

    <?php



	if ( bp_account_was_activated() ) {

		if ( isset( $_GET['e'] ) ) { ?>
			<p><?php esc_html_e( 'Your account was activated successfully! Your account details have been sent to you in a separate email.', 'buddyboss' ); ?></p>
		<?php } else { ?>
			<p><?php esc_html_e( 'Your account was activated successfully! You can now log in with the username and password you provided when you signed up.', 'buddyboss' ); ?></p>
			<?php
		}

/*		printf(
			'<p><a class="button button-primary" href="%1$s">%2$s</a></p>',
			esc_url( wp_login_url( bp_get_root_domain() ) ),
			esc_html__( 'Log In', 'buddyboss' )
		);
*/		
		// Show inline login form after successful activation
        get_template_part( 'templates/login-block' ); // or wherever login.php is located in your child theme
		
	} else {
		?>
		
		
		
        <?php
        $activation_key = bp_get_current_activation_key();
        ?>
        
        <p><?php esc_html_e( 'Please provide a valid activation key.', 'buddyboss' ); ?></p>
        
        <form action="" method="post" class="standard-form" id="activation-form">
        	<label for="key"><?php esc_html_e( 'Activation Key:', 'buddyboss' ); ?></label>
        
        	<input
        		type="text"
        		name="key"
        		id="key"
        		value="<?php echo esc_attr( $activation_key ); ?>"
        	/>
        
        	<?php
        	/**
        	 * Fires before the activation submit button.
        	 *
        	 * @since BuddyBoss 2.5.60
        	 */
        	do_action( 'bb_before_activate_submit_buttons' );
        	?>
        
        	<p class="submit">
        		<input
        			type="submit"
        			name="submit"
        			value="<?php esc_attr_e( 'Activate', 'buddyboss' ); ?>"
        		/>
        	</p>
        
        	<?php if ( empty( $activation_key ) ) : ?>
        		<div class="bzj-activation-login-notice" style="margin-top: 15px; text-align: center;">
        			<p style="margin-bottom: 10px;">
        				<?php esc_html_e( "Your account might already be active if the 'Activation Key' field is blank.", 'buddyboss' ); ?>
        			</p>
        
        			<a
        				href="<?php echo esc_url( home_url( '/wp-login.php' ) ); ?>"
        				class="button"
        				style="display: inline-block; background-color: #0066cc; color: #ffffff; padding: 5px 20px; border-radius: 20px; text-decoration: none;"
        			>
        				<?php esc_html_e( 'Login Here', 'buddyboss' ); ?>
        			</a>
        		</div>
        	<?php endif; ?>
        
        </form>



		<?php
	}

	bp_nouveau_activation_hook( 'after', 'content' );
	?>

</div><!-- .page -->

<?php bp_nouveau_activation_hook( 'after', 'page' ); ?>
