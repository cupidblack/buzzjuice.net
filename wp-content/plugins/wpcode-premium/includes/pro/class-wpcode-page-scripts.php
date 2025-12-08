<?php
/**
 * Load page-specific scripts from the metabox.
 *
 * @package WPCode
 */

/**
 * Class WPCode_Page_Scripts.
 */
class WPCode_Page_Scripts {

	/**
	 * Is this page loaded on a mobile device?
	 *
	 * @var bool
	 */
	private $is_mobile;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_head', array( $this, 'maybe_load_header_scripts' ), 15 );
		add_action( 'wp_footer', array( $this, 'maybe_load_footer_scripts' ), 15 );
		add_action( 'wp_body_open', array( $this, 'maybe_load_body_scripts' ), 15 );
		add_action( 'wp_head', array( $this, 'maybe_prevent_global_header_scripts' ), 5 );
		add_action( 'wp_footer', array( $this, 'maybe_prevent_global_footer_scripts' ), 5 );
		add_action( 'wp_body_open', array( $this, 'maybe_prevent_global_body_scripts' ), 5 );
	}

	/**
	 * Check at the right time if we should attempt to load the scripts on this page.
	 *
	 * @return bool
	 */
	public function should_load() {
		return is_singular();
	}

	/**
	 * Load header scripts.
	 *
	 * @return void
	 */
	public function maybe_load_header_scripts() {
		$this->maybe_load_scripts();
	}

	/**
	 * Load footer scripts.
	 *
	 * @return void
	 */
	public function maybe_load_footer_scripts() {
		$this->maybe_load_scripts( 'footer' );
	}

	/**
	 * Load body scripts.
	 *
	 * @return void
	 */
	public function maybe_load_body_scripts() {
		$this->maybe_load_scripts( 'body' );
	}

	/**
	 * Prevent global header scripts.
	 *
	 * @return void
	 */
	public function maybe_prevent_global_header_scripts() {
		$this->maybe_prevent_global_scripts();
	}

	/**
	 * Prevent global footer scripts.
	 *
	 * @return void
	 */
	public function maybe_prevent_global_footer_scripts() {
		$this->maybe_prevent_global_scripts( 'footer' );
	}

	/**
	 * Prevent global body scripts.
	 *
	 * @return void
	 */
	public function maybe_prevent_global_body_scripts() {
		$this->maybe_prevent_global_scripts( 'body' );
	}

	/**
	 * Prevent global scripts.
	 *
	 * @param string $location The location to check.
	 *
	 * @return void
	 */
	public function maybe_prevent_global_scripts( $location = 'header' ) {
		$scripts = $this->get_scripts( $location );
		if ( $scripts['disable_global'] ) {
			add_filter( "disable_ihaf_{$location}", '__return_true' );
		}
	}

	/**
	 * Generic method to load scripts by location.
	 *
	 * @param string $location The location to load scripts for.
	 *
	 * @return void
	 */
	public function maybe_load_scripts( $location = 'header' ) {
		if ( ! $this->should_load() ) {
			return;
		}

		$scripts = $this->get_scripts( $location );

		if ( ! empty( $scripts['any'] ) ) {
			echo wpcode()->smart_tags->replace_tags_in_snippet( $scripts['any'], null, true );
		}

		if ( $this->is_mobile() && ! empty( $scripts['mobile'] ) ) {
			echo wpcode()->smart_tags->replace_tags_in_snippet( $scripts['mobile'], null, true );
		} elseif ( ! empty( $scripts['desktop'] ) ) {
			echo wpcode()->smart_tags->replace_tags_in_snippet( $scripts['desktop'], null, true );
		}
	}

	/**
	 * Get scripts by location and post id.
	 *
	 * @param $location
	 * @param $post_id
	 *
	 * @return array
	 */
	public function get_scripts( $location = 'header', $post_id = 0 ) {
		if ( 0 === $post_id ) {
			$post_id = get_the_ID();
		}

		return wp_parse_args(
			get_post_meta( $post_id, "_wpcode_{$location}_scripts", true ),
			array(
				'disable_global' => false,
				'any'            => '',
				'desktop'        => '',
				'mobile'         => '',
			)
		);
	}

	/**
	 * Is the current browser on a mobile device.
	 *
	 * @return bool
	 */
	public function is_mobile() {
		if ( ! isset( $this->is_mobile ) ) {
			$this->is_mobile = wp_is_mobile();
		}

		return $this->is_mobile;
	}
}
