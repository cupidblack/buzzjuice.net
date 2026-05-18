<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="hook-instance">
    <h3><?php esc_html_e( 'Points on first sale for Referrer', 'mycred-woocommerce-plus' ); ?></h3>
    <div class="row">
        <div class="col-lg-2 col-md-6 col-sm-12 col-xs-12">
            <div class="form-group">
                <label for="<?php echo esc_attr( $this->field_id( array( 'referrer' => 'creds' ) ) ); ?>"><?php echo esc_attr( $this->core->plural() ); ?></label>
                <input type="text" name="<?php echo esc_attr( $this->field_name( array( 'referrer' => 'creds' ) ) ); ?>" id="<?php echo esc_attr( $this->field_id( array( 'referrer' => 'creds' ) ) ); ?>" value="<?php echo esc_attr( $this->core->number( $prefs['referrer']['creds'] ) ); ?>" class="form-control" />
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
            <div class="form-group">
                <label for="<?php echo esc_attr( $this->field_id( array( 'referrer', 'limit' ) ) ); ?>"><?php esc_html_e( 'Limit', 'mycred-woocommerce-plus' ); ?></label>
                <?php $this->hook_limit_setting_e( $this->field_name( array( 'referrer', 'limit' ) ), $this->field_id( array( 'referrer', 'limit' ) ), $prefs['referrer']['limit'] ); ?>
            </div>
        </div>
        <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
            <div class="form-group">
                <label for="<?php echo esc_attr( $this->field_id( array( 'referrer' => 'log' ) ) ); ?>"><?php esc_html_e( 'Log template', 'mycred-woocommerce-plus' ); ?></label>
                <input type="text" name="<?php echo esc_attr( $this->field_name( array( 'referrer' => 'log' ) ) ); ?>" id="<?php echo esc_attr( $this->field_id( array( 'referrer' => 'log' ) ) ); ?>" value="<?php echo esc_attr( $prefs['referrer']['log'] ); ?>" class="form-control" />
                <span class="description"><?php echo wp_kses_post( $this->available_template_tags( array( 'general' ) ) ); ?></span>
                <span class="description"> <?php esc_html_e( 'add %product_name% in log template represent value of referred product in log' ); ?></span>
            </div>
        </div>
    </div>
</div>
<div class="hook-instance">
    <h3><?php esc_html_e( 'Points on first sale for Referee', 'mycred-woocommerce-plus' ); ?></h3>
    <div class="row">
        <div class="col-lg-2 col-md-6 col-sm-12 col-xs-12">
            <div class="form-group">
                <label for="<?php echo esc_attr( $this->field_id( array( 'referee' => 'creds' ) ) ); ?>"><?php echo esc_html( $this->core->plural() ); ?></label>
                <input type="text" name="<?php echo esc_attr( $this->field_name( array( 'referee' => 'creds' ) ) ); ?>" id="<?php echo esc_attr( $this->field_id( array( 'referee' => 'creds' ) ) ); ?>" value="<?php echo esc_attr( $this->core->number( $prefs['referee']['creds'] ) ); ?>" class="form-control" />
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
            <div class="form-group test">
                <label for="<?php echo esc_attr( $this->field_id( array( 'referee', 'limit' ) ) ); ?>"><?php esc_html_e( 'Limit', 'mycred-woocommerce-plus' ); ?></label>                          
                <?php $this->hook_limit_setting_e( $this->field_name( array( 'referee', 'limit' ) ), $this->field_id( array( 'referee', 'limit' ) ), $prefs['referee']['limit'] );?>
            </div>
        </div>
        <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
            <div class="form-group">
                <label for="<?php echo esc_attr( $this->field_id( array( 'referee' => 'log' ) ) ); ?>"><?php esc_html_e( 'Log template', 'mycred-woocommerce-plus' ); ?></label>
                <input type="text" name="<?php echo esc_attr( $this->field_name( array( 'referee' => 'log' ) ) ); ?>" id="<?php echo esc_attr( $this->field_id( array( 'referee' => 'log' ) ) ); ?>" value="<?php echo esc_attr( $prefs['referee']['log'] ); ?>" class="form-control" />
                <span class="description"><?php echo wp_kses_post( $this->available_template_tags( array( 'general' ) ) ); ?></span>
            </div>
        </div>
    </div>
</div>