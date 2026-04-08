<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Github_Application_Setting extends myCRED_Module {

	public function __construct() {
		parent::__construct( 'Github_Application_Setting', array(
			'module_name' => 'Github_Application_Setting',
			'defaults'    => array(
				'foo'   => 1,
				'token'   => ''
			),
			'register'    => false,
			'add_to_core' => true
		) );
	}

	// Output our settings or content
	public function after_general_settings( $mycred = null ) {
	   $setting=$this->Github_Application_Setting; 
	   $github_app_client_id = ( isset($setting['github_app_client_id']) ? $setting['github_app_client_id'] : '' );
	   $github_app_client_secret= ( isset($setting['github_app_client_secret']) ? $setting['github_app_client_secret'] : '' );
	   $github_connect_redirect_url= ( isset($setting['github_connect_redirect_url']) ? $setting['github_connect_redirect_url'] : '' );
		?>
		<h4><span class="dashicons dashicons-admin-settings static"></span><?php esc_html_e('Github Application Settings', 'mycred-toolkit'); ?></h4>
			<div class="row">
							<table class="form-table">
								<tr valign="top">
									<th scope="row"><?php echo esc_html(__('Callback URL', 'mycred-toolkit')); ?></th>
									<td>
										<?php echo esc_url( admin_url( 'admin-ajax.php' ) . '?action=mg_github_callback' ) ; ?>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row"><?php echo esc_html(__('Client id', 'mycred-toolkit')); ?></th>
									<td>
										<input type="text" name="<?php echo esc_attr($this->field_name('github_app_client_id')); ?>" id="<?php echo esc_attr($this->field_id('github_app_client_id')); ?>" required value="<?php echo esc_attr($github_app_client_id); ?>"  />
										
									</td>
								</tr>
								<tr valign="top">
									<th scope="row"><?php echo esc_html(__('Client secret', 'mycred-toolkit')); ?></th>
									<td>
										<input type="password" name="<?php echo esc_attr($this->field_name('github_app_client_secret')); ?>" id="<?php echo esc_attr($this->field_id('github_app_client_secret')); ?>" required value="<?php echo esc_attr($github_app_client_secret); ?>" />
								  </td>
								</tr>
								<tr valign="top">
									<th scope="row"><?php echo esc_html(__('Connect redirect URL', 'mycred-toolkit')); ?></th>
									<td>
										<input type="text" name="<?php echo esc_attr($this->field_name('github_connect_redirect_url')); ?>" required value="<?php echo esc_attr($github_connect_redirect_url); ?>" id="<?php echo esc_attr($this->field_id('github_connect_redirect_url')); ?>"/>
									<p class="description"><?php echo esc_html(__('URL of page which user will redirect after click connect button ', 'mycred-toolkit')); ?></p>
									</td>
							</tr>
								
							</table>
							
			</div>
		<?php
	}

	// Sanitize our settings
	public function sanitize_extra_settings( $new_data, $data, $core ) {
		$new_data['Github_Application_Setting']['github_app_client_id'] = ( isset($data['Github_Application_Setting']['github_app_client_id']) ) ? $data['Github_Application_Setting']['github_app_client_id'] : '';
		$new_data['Github_Application_Setting']['github_app_client_secret'] = ( isset($data['Github_Application_Setting']['github_app_client_secret']) ) ? $data['Github_Application_Setting']['github_app_client_secret'] : '';
		$new_data['Github_Application_Setting']['github_connect_redirect_url'] = ( isset($data['Github_Application_Setting']['github_connect_redirect_url']) ) ? $data['Github_Application_Setting']['github_connect_redirect_url'] : '';
		update_option('github_app_client_id', $new_data['Github_Application_Setting']['github_app_client_id']);
		update_option('github_app_client_secret', $new_data['Github_Application_Setting']['github_app_client_secret']);
		update_option('github_connect_redirect_url', $new_data['Github_Application_Setting']['github_connect_redirect_url']);
	  return $new_data;
	}
}

$github_settings=new Github_Application_Setting();
$github_settings->load();