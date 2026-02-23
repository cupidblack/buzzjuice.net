<?php
//trying to get the wp-load php independent of this file's path - the separator is wp-content , if your folder is named otherwise, rename it here too.
$absolute_path = __FILE__;
$path_to_file = explode( 'wp-content', $absolute_path );
$path_to_wp = $path_to_file[0];

// Access WordPress
require_once( $path_to_wp . 'wp-load.php' );