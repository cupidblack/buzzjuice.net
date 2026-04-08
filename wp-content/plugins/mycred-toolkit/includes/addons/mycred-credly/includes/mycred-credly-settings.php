<?php
if ( ! defined( 'ABSPATH' ) ) {
exit;
}

if ( ! class_exists('MyCRED_Credly_Setting') ) :

	class MyCRED_Credly_Setting {

		/**
		 * Construct
		 * @since 1.0
		 * @version 1.0
		 */
		public function __construct() {
			add_action( 'mycred_after_core_prefs', array( $this, 'after_general_settings' ) );
			add_filter( 'mycred_save_core_prefs', array( $this, 'save_credly_settings' ), 90, 3 );
		}

		public function after_general_settings( $mycred = null ) {

			wp_enqueue_style( 'style-mycred-credly' );
			$credly_token = '';
			$prefs = mycred_get_option( 'mycred_pref_core' );

			if ( ! empty( $prefs ) ) {
				$credly_token = ! empty( $prefs['credly']['access_token'] ) ? $prefs['credly']['access_token'] : '';
				$credly_organization = ! empty( $prefs['credly']['organization_id'] ) ? $prefs['credly']['organization_id'] : '';
			}
			?>
			<?php wp_nonce_field( 'mycred_credly_authorize_nonce', 'mycred_credly_authorize_nonce_field' ); ?>
			<div id="mycred-credly-settings-container">
				<div class="mycred-ui-accordion">
					<div class="mycred-ui-accordion-header">
						<h4 class="mycred-ui-accordion-header-title">
							<span class="dashicons dashicon-mycred-credly static mycred-ui-accordion-header-icon"></span>
							<label><?php esc_html_e('Credly Badges', 'mycred-toolkit'); ?></label>
						</h4>
						<div class="mycred-ui-accordion-header-actions hide-if-no-js">
							<button type="button" aria-expanded="true">
								<span class="mycred-ui-toggle-indicator" aria-hidden="true"></span>
							</button>
						</div>
					</div>       
					<div class="mycred-ui" style="font-size: medium; padding: 20px;">
						<h4><?php esc_html_e('Credly Badges Integration', 'mycred-toolkit'); ?></h4>
						<div class="row">
							<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
								<div class="form-group">
									<label for="mycred_credly_token"><?php esc_html_e('Authorization Token', 'mycred-toolkit'); ?></label>
									<input type="password" id="mycred_credly_token" name="mycred_pref_core[credly][mycred_credly_token]" class="large-text" value="<?php echo esc_attr( $credly_token ); ?>" required />
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
								<div class="form-group">
									<label for="mycred_credly_organization"><?php esc_html_e('Organization ID', 'mycred-toolkit'); ?></label>
									<input type="password" id="mycred_credly_organization" name="mycred_pref_core[credly][mycred_credly_organization]" class="large-text" value="<?php echo esc_attr( $credly_organization ); ?>" required />
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

		public function save_credly_settings( $prefs, $post, $general ) {
			if ( ! isset( $prefs['credly'] ) ) {
				$prefs['credly'] = array();
			}
		  
			if ( isset( $post['credly']['mycred_credly_token'] ) ) {
				$prefs['credly']['access_token'] = sanitize_text_field( $post['credly']['mycred_credly_token'] );
			}
		  
			if ( isset( $post['credly']['mycred_credly_organization'] ) ) {
				$prefs['credly']['organization_id'] = sanitize_text_field( $post['credly']['mycred_credly_organization'] );
			}
			
			return $prefs;
		}
	}
	new MyCRED_Credly_Setting();

endif;
