<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="mwp-history-container mwp-<?php echo esc_attr( $type_key );?>-history-container">
    <?php $logs->display(); ?>
    <?php if( $logs->have_entries() ):?>
    <div class="mwp-history-pagination-wrapper">
        <?php 
        $pagination = paginate_links(
            array(
                'base'      => str_replace( $big_number, '%#%', wc_get_endpoint_url( $type_key, $big_number ) ),
                'show_all'  => true,
                'prev_next' => true,
                'prev_text' => '«',
                'next_text' => '»',
                'current'   => $current_page,
                'total'     => $logs->max_num_pages
            )
        );

        if ( ! empty( $pagination ) ) {
            echo wp_kses_post( $pagination );
        }
        ?>
    </div>
    <?php endif;?>
</div>