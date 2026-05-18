<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( 'mycred_rank' == $post->post_type ) {
	wp_nonce_field( 'mwdpbr_woo_ranks_cats', 'mwdpbr_woo_ranks_cats' );
	$selected_cats = get_post_meta( $post->ID, 'mycred_woo_ranks_cats', true );
} else {
	wp_nonce_field( 'mwdpbr_woo_badges_cats', 'mwdpbr_woo_badges_cats' );
	$selected_cats = get_post_meta( $post->ID, 'mycred_woo_badges_cats', true );
}


?>
<table width="100%">
	<tr>
		<td colspan="10" >
			<p style="font-weight: 600;">
				<?php echo esc_html__( 'You can select the categories to hide the related products from users.', 'mycred-woocommerce-plus
' );?>
			</p>
		</td>
	</tr>
	<tr><td style="width: 25%"><?php echo esc_html__( 'WooCommerce Product Categories', 'mycred-woocommerce-plus
' ); ?></td></tr>
	<tr>
		<td>
			<?php 
			$categories = array();
			foreach ( $this->mwdpbr_get_woocommerce_cats() as $key => $category ) {
				$categories[ $category->slug ] = $category->name;
			}
			if ( 'mycred_rank' == $post->post_type ) {
				mycred_create_select_field( 
					$categories, 
					$selected_cats,
					array(
						'name'     => 'woo_ranks_cats[]',
						'id'       => 'mwdpbr_categories',
						'class'    => 'mycred-select2',
						'multiple' => 'multiple',
						'style'    => 'width: 69%'
					)
				);
			} else {
				mycred_create_select_field( 
					$categories, 
					$selected_cats,
					array(
						'name'     => 'woo_badges_cats[]',
						'id'       => 'mwdpbr_categories',
						'class'    => 'mycred-select2',
						'multiple' => 'multiple',
						'style'    => 'width: 69%'
					)
				);
			}
			?>
		</td>
	</tr>
</table>
<script>
	<?php $select_category = __( 'Select categories', 'mycred-woocommerce-plus
' ); ?>
	jQuery(document).ready(function($) {
		$('.mwdpbr_categories').select2({
			placeholder: '<?php echo esc_attr( $select_category ) ; ?>'
		});
	});
</script>
<?php