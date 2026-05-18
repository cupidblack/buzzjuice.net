<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

wp_nonce_field( 'mwp_module_restrict_product_nonce', 'mwp-restrict-product-nonce' );
?>
<div class="mycred-ui-wrap mwp-restrict-product-wrapper">
	<?php  
    mycred_create_toggle_field( 
        array(
            'id'    => 'mwp-restrict-product',
            'name'  => 'mwp_restrict_product_pref[enable]',
            'label' => __( 'Enable', 'mycred-woocommerce-plus' )
        ), 
        $settings['enable'],
        $settings['enable']
    );
    ?>
    <div class="mwp-restrict-product-inner <?php echo esc_attr( ! empty( $settings['enable'] ) ? 'show' : '' );?>">
    	<hr class="mb-3">
	    <div class="form-group">
	        <label for="mwp-restrict-product-type"><?php esc_html_e( 'Based On', 'mycred-woocommerce-plus' );?></label>
		    <?php 
		    $options = array( '' => __( 'Select based on', 'mycred-woocommerce-plus' ) );

		    if ( $this->badge_enabled ) {
		    	$options['badge'] = __( 'Badge', 'mycred-woocommerce-plus' );
		    }

		    if ( $this->rank_enabled ) {
		    	$options['rank'] = __( 'Rank', 'mycred-woocommerce-plus' );
		    }

		    mycred_create_select_field( 
		        $options, 
		        $settings['type'],
		        array(
		            'name'  => 'mwp_restrict_product_pref[type]',
		            'id'    => 'mwp-restrict-product-type',
		            'class' => 'form-control'
		        )
		    );
		    ?>
		</div>
		<div class="form-group">
            <?php  
            mycred_create_input_field( array(
                'type'        => 'search',
                'id'          => 'mwp-restrict-product-search',
                'class'       => 'form-control',
                'placeholder' => 'Search'
            ) );
            ?>
		</div>
		<div class="mwp-restrict-product-content badge <?php echo esc_attr( $settings['type'] == 'badge' ? 'show' : '' );?>">
			<?php foreach ( $badges as $key => $badge ):?>
			<div class="form-group">
				<?php 
				mycred_create_toggle_field( 
			        array(
			            'id'    => 'mwp-restrict-product-badge-' . $key,
			            'name'  => 'mwp_restrict_product_pref[selected_ids][]',
			            'label' => $badge->post_title,
			            'after' => true, 
			            'class' => 'mwp-restrict-product-ids'
			        ), 
			        $badge->ID,
			        ( in_array( $badge->ID, $settings['selected_ids'] ) )
			    );
				?>
			</div>
			<?php endforeach;?>
			<p><i><?php esc_html_e( 'This product will be visible to users with selected badges only.', 'mycred-woocommerce-plus' );?></i></p>
		</div>
		<div class="mwp-restrict-product-content rank <?php echo esc_attr( $settings['type'] == 'rank' ? 'show' : '' );?>">
			<?php foreach ( $ranks as $rank_type ):?>
			<h3><?php echo esc_html( $rank_type['type']['label'] );?></h3>
			<div class="mwp-restrict-product-rank-types">
				<?php foreach ( $rank_type['ranks'] as $key => $rank ):?>
				<div class="form-group">
					<?php 
					mycred_create_toggle_field( 
				        array(
				            'id'    => 'mwp-restrict-product-rank-' . $key,
				            'name'  => 'mwp_restrict_product_pref[selected_ids][]',
				            'label' => $rank->post_title,
				            'after' => true, 
				            'class' => 'mwp-restrict-product-ids'
				        ), 
				        $rank->ID,
				        ( in_array( $rank->ID, $settings['selected_ids'] ) )
				    );
					?>
				</div>
				<?php endforeach;?>
			</div>
			<?php endforeach;?>
			<p><i><?php esc_html_e( 'This product will be visible to users with selected ranks only.', 'mycred-woocommerce-plus' );?></i></p>
		</div>
    </div>
</div>

