<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait MWP_Helper_Trait {

	protected $template_path = '';

	public function load_template( $name, $path, $data ) {

		if ( empty( $name ) || empty( $path ) ) {
			return;
		}

		$template_name = str_replace( '-', '_', str_replace( ' ', '_', $name ) );

		$this->template_path = apply_filters( 'mwp_' . $template_name . '_template', MYCRED_WOOPLUS_TEMPLATES_DIR . $path );

		do_action( 'mwp_before_' . $template_name, $data );

		$this->include_template( $data );

		do_action( 'mwp_after_' . $template_name, $data );

	}

	protected function include_template( $data ) {

		if ( file_exists( $this->template_path ) ) {

			extract( $data );
		
			include $this->template_path;

		}

	}

}