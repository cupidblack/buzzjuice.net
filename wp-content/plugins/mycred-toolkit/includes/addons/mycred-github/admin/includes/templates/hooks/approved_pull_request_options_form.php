<div class="hook-instance">
  <div class="row">
	<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
	  <div class="form-group">
		<label for="<?php echo esc_attr($this->field_id('creds')); ?>"><?php echo esc_attr($this->core->plural()); ?></label>
		<input type="text" name="<?php echo esc_attr($this->field_name('creds')); ?>" id="<?php echo esc_attr($this->field_id('creds')); ?>" value="<?php echo esc_attr($this->core->number($prefs['creds'])); ?>" class="form-control" />
	  </div>
	</div>
	<div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
	  <div class="form-group">
		<label for="<?php echo esc_attr($this->field_id('limit')); ?>"><?php esc_html_e('Limit', 'mycred-toolkit'); ?></label>
		<?php echo $this->hook_limit_setting($this->field_name('limit'), $this->field_id('limit'), esc_attr( $prefs['limit'] ) ); ?>
	  </div>
	</div>
	<div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
	  <div class="form-group">
	  <br>
		<label for="<?php echo esc_attr($this->field_id('log')); ?>"><?php esc_html_e('Log Template', 'mycred-toolkit'); ?></label>
		<input type="text" name="<?php echo $this->field_name('log'); ?>" id="<?php echo $this->field_id('log'); ?>" placeholder="<?php esc_html_e('required', 'mycred-toolkit'); ?>" value="<?php echo esc_attr($prefs['log']); ?>" class="form-control" />
		<span class="description"><?php echo $this->available_template_tags(array( 'general' )); ?></span>
	  </div>
	</div>
	<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
			<div class="form-group">
	  <br>
				<label><?php esc_html_e( 'Available Shortcodes', 'mycred-toolkit' ); ?></label>
				<p class="form-control-static"><a href="http://codex.mycred.me/shortcodes/mycred_link/" target="_blank">[connect-with-github]</a></p>
			</div>
		</div>
  </div>
</div>