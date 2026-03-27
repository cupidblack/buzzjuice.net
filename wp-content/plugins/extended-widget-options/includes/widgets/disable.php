<?php
/**
 * Disable Widgets on Admin
 *
 * @copyright   Copyright (c) 2016, Jeffrey Carandang
 * @since       4.0
 */

function widgetopts_disable_sidebar_widgets(){
    global $widget_options, $pagenow;

    if( $pagenow != 'options-general.php' && 'activate' == $widget_options['disable_widgets'] ){
        $widgets 	= ( isset( $widget_options['settings']['disable_widgets'] ) ) ? $widget_options['settings']['disable_widgets'] : array();
        if ( ! empty( $widgets ) ) {
    		foreach ( array_keys( $widgets ) as $widget_class ) {
    			unregister_widget( $widget_class );
    		}
    	}
    }
}
add_action( 'widgets_init', 'widgetopts_disable_sidebar_widgets', 100 );

?>
