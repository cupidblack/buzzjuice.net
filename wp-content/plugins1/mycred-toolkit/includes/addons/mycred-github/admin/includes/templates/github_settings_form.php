<?php
$github_repositories = get_option('github_repositories');
$readonly = ( $github_repositories ) ? 'readonly' : '';
?>
<h1><?php echo esc_html(__('Github Hooks', 'mycred-toolkit')); ?></h1>
<div class="github-settings-wrapper">
  <div id="github-Hooks-setting" >
	<form method="post" action="options.php">
	  <?php settings_fields('github-account-info-setting'); ?>
	  <?php do_settings_sections('github-account-info-setting'); ?>
	  <table class="form-table">
		<tr valign="top">
		  <th scope="row"><?php echo esc_html(__('Github Token', 'mycred-toolkit')); ?></th>
		  <td>
			<input type="text" name="github_account_info[token]" required value="<?php echo isset(esc_attr( get_option('github_account_info') )['token']) ? esc_attr( get_option('github_account_info') )['token'] : '' ; ?>" <?php echo esc_html ($readonly ); ?> />
		  </td>
		  <?php
			if ($github_repositories) {
				?>
					  <td>
			  <button id="btn-disconnect" class="button button-default"><?php echo esc_html(__('Disconnect', 'mycred-toolkit')); ?></button>
			</td>
		  
			<?php } ?>
		</tr>
		<tr valign="top">
		  <th scope="row"><?php echo esc_html(__('GitHub Username', 'mycred-toolkit')); ?></th>
		  <td>
			<input type="text" name="github_account_info[username]" required value="<?php echo isset(esc_attr( get_option('github_account_info') )['username']) ? esc_attr( get_option('github_account_info') )['username'] : '' ; ?>" <?php echo esc_html( $readonly ); ?> />
		  </td>
		</tr>
		<?php
		if ($github_repositories) {
			?>
		  <tr valign='top'>
			   <th scope='row'><?php echo esc_html(__('Repository', 'mycred-toolkit')); ?> </th> 
			   <td> 
				  <select id='github_repositories' name='github_account_selected_repositories[]' multiple >
				  <?php
		  $selected_repository = get_option('github_account_selected_repositories');
					foreach ($github_repositories as $key => $value) {
					  $isSelected = in_array($value, $selected_repository) ? 'selected' : '';
						?>
			<option value='<?php echo esc_attr($value); ?>' <?php echo esc_attr($isSelected); ?> ><?php echo esc_attr($value); ?> </option>
					<?php } ?>
			</select>
			  </td>
			  <td>
				<button id="btn-refresh-repositories" class="button button-default" ><?php echo esc_html(__('Refresh Repositories', 'mycred-toolkit')); ?></button>
			  </td>
			</tr>
		<?php } ?>

	  </table>
	  <?php submit_button(); ?>

	</form>
  </div>
</div>
