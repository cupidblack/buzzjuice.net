<?php

namespace GiveP2P\P2P\Helpers;

use WP_Error;

/**
 * Class FileUpload
 * @package GiveP2P\P2P\Helpers
 *
 * Helper class used to handle file upload
 */
class FileUpload {
	/**
	 * @param  array  $file  - $_FILES upload array
	 * @param  int  $postId
	 *
	 * @return int|WP_Error
	 */
	public function handleFile( $file, $postId = 0 ) {
		// Dependencies
		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require ABSPATH . 'wp-admin/includes/image.php';
			require ABSPATH . 'wp-admin/includes/file.php';
			require ABSPATH . 'wp-admin/includes/media.php';
		}

		return media_handle_sideload( $file[ 'file' ], $postId );
	}
}
