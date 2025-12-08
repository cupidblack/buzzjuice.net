<?php
/**
 * Smart tags class for the pro version.
 *
 * @package WPCode
 */

/**
 * WPCode_Smart_Tags_Pro class.
 */
class WPCode_Smart_Tags_Pro extends WPCode_Smart_Tags {

	/**
	 * Add filters to replace the tags in the snippet code.
	 *
	 * @return void
	 */
	public function hooks() {
		add_filter( 'wpcode_snippet_output_html', array( $this, 'replace_tags_in_snippet' ), 10, 2 );
		add_filter( 'wpcode_snippet_output_text', array( $this, 'replace_tags_in_snippet' ), 10, 2 );
		add_filter( 'wpcode_snippet_output_js', array( $this, 'replace_tags_in_snippet' ), 10, 2 );
		add_filter( 'wpcode_snippet_output_universal', array( $this, 'replace_tags_in_snippet' ), 10, 2 );
	}

	/**
	 * Method to get the id to avoid passing parameters to the core function.
	 *
	 * @return int
	 */
	public function get_the_ID() {
		return get_the_ID();
	}

	/**
	 * Method to get the title to avoid passing parameters to the core function.
	 *
	 * @return string
	 */
	public function get_the_title() {
		return get_the_title();
	}

	/**
	 * Get a comma-separated list of categories to replace the smart tag [categories].
	 *
	 * @return string
	 */
	public function tag_value_categories() {
		return wp_strip_all_tags( get_the_category_list( ',' ) );
	}

	/**
	 * Get the current user email.
	 *
	 * @return string
	 */
	public function tag_value_email() {
		return $this->get_user_detail( 'user_email' );
	}

	/**
	 * Get the first name tag.
	 *
	 * @return string
	 */
	public function tag_value_first_name() {
		return $this->get_user_detail( 'first_name' );
	}

	/**
	 * Get the last name tag.
	 *
	 * @return string
	 */
	public function tag_value_last_name() {
		return $this->get_user_detail( 'last_name' );
	}

	/**
	 * Get an user detail if loggedin.
	 *
	 * @param string $detail The key of the user detail.
	 *
	 * @return int|mixed|string
	 */
	private function get_user_detail( $detail ) {
		if ( ! is_user_logged_in() ) {
			return '';
		}

		$user = wp_get_current_user();

		return isset( $user->$detail ) ? $user->$detail : '';
	}

	/**
	 * Check if WooCommerce is installed & active on the site.
	 *
	 * @return bool
	 */
	public function woocommerce_available() {
		return class_exists( 'woocommerce' );
	}

	/**
	 * Check if Yoast SEO is installed & active on the site.
	 *
	 * @return bool
	 */
	public function yoast_seo_available() {
		return class_exists( 'WPSEO_Admin' );
	}

	/**
	 * Check if All in One SEO is installed & active on the site.
	 *
	 * @return bool
	 */
	public function aioseo_available() {
		return class_exists( 'AIOSEO\Plugin\AIOSEO' );
	}

	/**
	 * Check if MemberPress Courses is installed & active on the site.
	 *
	 * @return bool
	 */
	public function memberpress_courses_available() {
		return class_exists( 'memberpress\courses\models\Course' );
	}

	/**
	 * Check if MemberPress is installed & active on the site.
	 *
	 * @return bool
	 */
	public function memberpress_available() {
		return class_exists( 'MeprProduct' );
	}

	/**
	 * Get the Woo order, if available.
	 *
	 * @return bool|WC_Order|WC_Order_Refund
	 */
	public function get_wc_order() {
		if ( ! $this->woocommerce_available() ) {
			return false;
		}

		global $wp;

		// Check cart class is loaded or abort.
		if ( is_null( WC()->cart ) ) {
			return false;
		}

		if ( empty( $wp->query_vars['order-received'] ) ) {
			return false;
		}

		$order_id = $wp->query_vars['order-received'];

		return wc_get_order( $order_id );
	}

	/**
	 * Return the WC order number, if available.
	 *
	 * @return string|int
	 */
	public function tag_value_wc_order_number() {
		$order = $this->get_wc_order();

		if ( ! $order ) {
			return '';
		}

		return $order->get_order_number();
	}

	/**
	 * Return the WC order subtotal,  if available.
	 *
	 * @return string|float
	 */
	public function tag_value_wc_order_subtotal() {
		$order = $this->get_wc_order();

		if ( ! $order ) {
			return '';
		}

		return $order->get_subtotal();
	}

	/**
	 * Return the WC order total, if available.
	 *
	 * @return string|float
	 */
	public function tag_value_wc_order_total() {
		$order = $this->get_wc_order();

		if ( ! $order ) {
			return '';
		}

		return $order->get_total();
	}

	/**
	 * Get the WooCommerce product, if available.
	 *
	 * @return bool|WC_Product
	 */
	public function get_wc_product() {
		if ( ! $this->woocommerce_available() ) {
			return false;
		}

		global $product;

		// If the global product is available, use it.
		if ( is_object( $product ) && $product instanceof WC_Product ) {
			return $product;
		}

		// Try to get the product from the current post.
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return false;
		}

		// Check if the current post is a product.
		if ( get_post_type( $post_id ) !== 'product' ) {
			return false;
		}

		return wc_get_product( $post_id );
	}

	/**
	 * Return the product SKU.
	 *
	 * @return string
	 */
	public function tag_value_wc_product_sku() {
		$product = $this->get_wc_product();

		if ( ! $product ) {
			return '';
		}

		return $product->get_sku();
	}

	/**
	 * Return the product regular price.
	 *
	 * @return string
	 */
	public function tag_value_wc_product_price() {
		$product = $this->get_wc_product();

		if ( ! $product ) {
			return '';
		}

		return $product->get_regular_price();
	}

	/**
	 * Return the product sale price.
	 *
	 * @return string
	 */
	public function tag_value_wc_product_sale_price() {
		$product = $this->get_wc_product();

		if ( ! $product ) {
			return '';
		}

		return $product->get_sale_price();
	}

	/**
	 * Return the product formatted price HTML.
	 *
	 * @return string
	 */
	public function tag_value_wc_product_price_html() {
		$product = $this->get_wc_product();

		if ( ! $product ) {
			return '';
		}

		return $product->get_price_html();
	}

	/**
	 * Return whether the product is in stock.
	 *
	 * @return string
	 */
	public function tag_value_wc_product_in_stock() {
		$product = $this->get_wc_product();

		if ( ! $product ) {
			return '';
		}

		return $product->is_in_stock() ? 'yes' : 'no';
	}

	/**
	 * Return the product stock quantity.
	 *
	 * @return string|int
	 */
	public function tag_value_wc_product_stock_quantity() {
		$product = $this->get_wc_product();

		if ( ! $product ) {
			return '';
		}

		return $product->get_stock_quantity();
	}

	/**
	 * Return the product short description.
	 *
	 * @return string
	 */
	public function tag_value_wc_product_short_description() {
		$product = $this->get_wc_product();

		if ( ! $product ) {
			return '';
		}

		return $product->get_short_description();
	}

	/**
	 * Return the product featured image URL.
	 *
	 * @return string
	 */
	public function tag_value_wc_product_image() {
		$product = $this->get_wc_product();

		if ( ! $product ) {
			return '';
		}

		$image_id = $product->get_image_id();
		if ( ! $image_id ) {
			return '';
		}

		$image = wp_get_attachment_image_src( $image_id, 'full' );
		return isset( $image[0] ) ? $image[0] : '';
	}

	/**
	 * Return the product categories as a comma-separated list.
	 *
	 * @return string
	 */
	public function tag_value_wc_product_category() {
		$product = $this->get_wc_product();

		if ( ! $product ) {
			return '';
		}

		$categories = get_the_terms( $product->get_id(), 'product_cat' );
		if ( ! $categories || is_wp_error( $categories ) ) {
			return '';
		}

		$category_names = array();
		foreach ( $categories as $category ) {
			$category_names[] = $category->name;
		}

		return implode( ', ', $category_names );
	}

	/**
	 * Return the product tags as a comma-separated list.
	 *
	 * @return string
	 */
	public function tag_value_wc_product_tags() {
		$product = $this->get_wc_product();

		if ( ! $product ) {
			return '';
		}

		$tags = get_the_terms( $product->get_id(), 'product_tag' );
		if ( ! $tags || is_wp_error( $tags ) ) {
			return '';
		}

		$tag_names = array();
		foreach ( $tags as $tag ) {
			$tag_names[] = $tag->name;
		}

		return implode( ', ', $tag_names );
	}

	/**
	 * Return the product average rating.
	 *
	 * @return string|float
	 */
	public function tag_value_wc_product_rating() {
		$product = $this->get_wc_product();

		if ( ! $product ) {
			return '';
		}

		return $product->get_average_rating();
	}

	/**
	 * Return the product review count.
	 *
	 * @return string|int
	 */
	public function tag_value_wc_product_review_count() {
		$product = $this->get_wc_product();

		if ( ! $product ) {
			return '';
		}

		return $product->get_review_count();
	}

	/**
	 * Return the product gallery image URLs as a comma-separated list.
	 *
	 * @return string
	 */
	public function tag_value_wc_product_gallery() {
		$product = $this->get_wc_product();

		if ( ! $product ) {
			return '';
		}

		$gallery_ids = $product->get_gallery_image_ids();
		if ( empty( $gallery_ids ) ) {
			return '';
		}

		$gallery_urls = array();
		foreach ( $gallery_ids as $gallery_id ) {
			$image = wp_get_attachment_image_src( $gallery_id, 'full' );
			if ( isset( $image[0] ) ) {
				$gallery_urls[] = $image[0];
			}
		}

		return implode( ', ', $gallery_urls );
	}

  /**
	 * Get the custom field value.
	 *
	 * @param array $parameters Array of extracted parameters.
	 *
	 * @return string
	 */
	public function tag_value_custom_field( $parameters = array() ) {
		if ( empty( $parameters['custom_field'] ) ) {
			return '';
		}

		// Let's see if we can find a meta with that key.
		$meta = get_post_meta( get_the_ID(), $parameters['custom_field'], true );

		// If we found a meta, let's return it.
		if ( ! empty( $meta ) ) {
			return $meta;
		}

		return '';
	}

	/**
	 * Replace smart tags in the code passed.
	 *
	 * @param string         $code The code to replace the smart tags in.
	 * @param WPCode_Snippet $snippet The snippet object.
	 * @param bool           $replace_old_format Whether to replace the old format of smart tags or not.
	 *
	 * @return string
	 */
	public function replace_tags_in_snippet( $code, $snippet = null, $replace_old_format = false ) {

		$tags = $this->get_tags_to_replace( $snippet );

		$smart_tags = $this->get_all_smart_tags( $code );

		foreach ( $smart_tags as $smart_tag_in_code => $smart_tag_key ) {
			$parameters = $this->get_parameters_from_tag( $smart_tag_in_code );
			if ( ! isset( $tags[ $smart_tag_key ] ) ) {
				continue;
			}
			$parameters['smart_tag_key'] = $smart_tag_key;
			$parameters['snippet']       = $snippet;

			$function = $tags[ $smart_tag_key ];

			$code = str_replace( $smart_tag_in_code, call_user_func( $function, $parameters ), $code );
		}

		if ( $replace_old_format ) {
			// The initial version of this used square brackets instead of curly braces.
			// We need to support that for backwards compatibility.
			foreach ( $tags as $tag => $function ) {
				$code = str_replace( '[' . $tag . ']', call_user_func( $function ), $code );
			}
		}

		return $code;
	}

	/**
	 * Get all smart tags in the content.
	 *
	 * @param string $content Content.
	 *
	 * @return array
	 * @since 1.6.7
	 */
	private function get_all_smart_tags( $content ) {

		/**
		 * A smart tag should start and end with a curly brace.
		 * ([a-z0-9_]+) a smart tag name and also the first capturing group. Lowercase letters, digits, and an  underscore.
		 * (|[ =][^\n}]*) - second capturing group:
		 * | no characters at all or the following:
		 * [ =][^\n}]* space or equal sign and any number of any characters except new line and closing curly brace.
		 */
		preg_match_all( '~{([a-z0-9_]+)(|[ =][^\n}]*)}~', $content, $smart_tags );

		return array_combine( $smart_tags[0], $smart_tags[1] );
	}

	/**
	 * Extract parameters from smart tag.
	 *
	 * @param string $tag The tag with parameters.
	 *
	 * @return array
	 */
	public function get_parameters_from_tag( $tag ) {
		preg_match_all( '/(\w+)=(["\'])(.+?)\2/', $tag, $attributes );
		$parameters = array_combine( $attributes[1], $attributes[3] );

		return $parameters;
	}

	/**
	 * Parse the tags data and return just tag > function pairs.
	 *
	 * @param WPCode_Snippet $snippet The snippet object.
	 *
	 * @return array
	 */
	public function get_tags_to_replace( $snippet = null ) {
		$tags_data = $this->get_tags();

		$tags = array();
		foreach ( $tags_data as $category_data ) {
			foreach ( $category_data['tags'] as $tag => $tag_details ) {
				$tags[ $tag ] = $tag_details['function'];
			}
		}

		if ( $snippet instanceof WPCode_Snippet && ! empty( $snippet->attributes ) ) {
			// Let's see if we have any shortcode attributes to use as smart tags.
			foreach ( $snippet->attributes as $attribute => $value ) {
				$tags[ 'attr_' . $attribute ] = array( $this, 'get_shortcode_attribute_value' );
			}
		}

		return $tags;
	}

	/**
	 * Upgrade notice data.
	 *
	 * @return array
	 */
	public function upgrade_notice_data() {
		if ( ! empty( wpcode()->license->get( is_multisite() && is_network_admin() ) ) ) {
			return array();
		}

		return array(
			'title'  => __( 'Smart Tags are a Premium feature', 'wpcode-premium' ),
			'text'   => __( 'Please add your license key in the Settings Panel to unlock all pro features.', 'wpcode-premium' ),
			'button' => __( 'Add License Key Now', 'wpcode-premium' ),
			'link'   => add_query_arg(
				array(
					'page' => 'wpcode-settings',
				),
				admin_url( 'admin.php' )
			),
		);
	}

	/**
	 * Get the id of the current post/page author.
	 *
	 * @return string
	 */
	public function tag_value_author_id() {
		return $this->get_author_detail( 'ID' );
	}

	/**
	 * Get the name of the current post/page author.
	 *
	 * @return string
	 */
	public function tag_value_author_name() {
		return $this->get_author_detail( 'display_name' );
	}

	/**
	 * Get the posts URL for the current post author.
	 *
	 * @return string
	 */
	public function tag_value_author_url() {
		return $this->get_author_detail( 'posts_url' );
	}

	/**
	 * Get the login URL.
	 *
	 * @return string
	 */
	public function tag_value_login_url() {
		return wp_login_url();
	}

	/**
	 * Get the logout URL.
	 *
	 * @return string
	 */
	public function tag_value_logout_url() {
		return wp_logout_url();
	}

	/**
	 * Get the permalink of the current post/page.
	 *
	 * @return string
	 */
	public function tag_value_permalink() {
		return get_permalink();
	}

	/**
	 * Get the detail of the current post/page author.
	 *
	 * @param string $detail The detail to get (id, name, etc) from WP_User.
	 *
	 * @return int|mixed|string
	 */
	public function get_author_detail( $detail ) {
		// Get the current post author from the global object.
		$author = get_post_field( 'post_author', get_the_ID() );

		// If we don't have an author, return an empty string.
		if ( ! $author ) {
			return '';
		}

		// Get the author details.
		$author_data = get_userdata( $author );

		if ( 'posts_url' === $detail ) {
			return get_author_posts_url( $author_data->ID );
		}

		// If we don't have the requested detail, return an empty string.
		if ( ! isset( $author_data->{$detail} ) ) {
			return '';
		}

		// Return the requested detail.
		return $author_data->{$detail};
	}

	/**
	 * Get the shortcode attribute value for the current snippet.
	 *
	 * @param array $parameters Parameters passed to the smart tag.
	 *
	 * @return string
	 */
	public function get_shortcode_attribute_value( $parameters = array() ) {
		if ( isset( $parameters['snippet'] ) && isset( $parameters['smart_tag_key'] ) && $parameters['snippet'] instanceof WPCode_Snippet ) {
			$snippet = $parameters['snippet'];
			$key     = substr_replace( $parameters['smart_tag_key'], '', 0, 5 );

			if ( isset( $snippet->attributes[ $key ] ) ) {
				return $snippet->attributes[ $key ];
			}
		}

		return '';
	}

	/**
	 * Get the post excerpt.
	 *
	 * @return string
	 */
	public function tag_value_excerpt() {
		$post = get_post();
		if ( ! $post ) {
			return '';
		}

		if ( ! empty( $post->post_excerpt ) ) {
			return wp_strip_all_tags( $post->post_excerpt );
		}

		// If no excerpt is set, generate one from the content.
		$excerpt = wp_trim_words( wp_strip_all_tags( $post->post_content ), 55, '...' );
		return $excerpt;
	}

	/**
	 * Get the full post content.
	 *
	 * @return string
	 */
	public function tag_value_content() {
		$post = get_post();
		if ( ! $post ) {
			return '';
		}

		// Return sanitized content.
		return wp_kses_post( $post->post_content );
	}

	/**
	 * Get the URL of the featured image.
	 *
	 * @return string
	 */
	public function tag_value_featured_image() {
		$post_id = get_the_ID();
		if ( ! $post_id || ! has_post_thumbnail( $post_id ) ) {
			return '';
		}

		$image_id = get_post_thumbnail_id( $post_id );
		$image    = wp_get_attachment_image_src( $image_id, 'full' );

		return isset( $image[0] ) ? $image[0] : '';
	}

	/**
	 * Get the post tags as a comma-separated list.
	 *
	 * @return string
	 */
	public function tag_value_tags() {
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return '';
		}

		$tags = get_the_tags( $post_id );
		if ( ! $tags || is_wp_error( $tags ) ) {
			return '';
		}

		$tag_names = array();
		foreach ( $tags as $tag ) {
			$tag_names[] = $tag->name;
		}

		return implode( ', ', $tag_names );
	}

	/**
	 * Get the post publish date.
	 *
	 * @return string
	 */
	public function tag_value_date_published() {
		$post = get_post();
		if ( ! $post ) {
			return '';
		}

		return get_the_date( '', $post->ID );
	}

	/**
	 * Get the post modification date.
	 *
	 * @return string
	 */
	public function tag_value_date_modified() {
		$post = get_post();
		if ( ! $post ) {
			return '';
		}

		return get_the_modified_date( '', $post->ID );
	}

	/**
	 * Get the author's bio/description.
	 *
	 * @return string
	 */
	public function tag_value_author_bio() {
		return $this->get_author_detail( 'description' );
	}

	/**
	 * Get the website's name.
	 *
	 * @return string
	 */
	public function tag_value_site_name() {
		return get_bloginfo( 'name' );
	}

	/**
	 * Get the website's home URL.
	 *
	 * @return string
	 */
	public function tag_value_site_url() {
		return home_url();
	}

	/**
	 * Get the post type.
	 *
	 * @return string
	 */
	public function tag_value_post_type() {
		$post = get_post();
		if ( ! $post ) {
			return '';
		}

		$post_type = get_post_type( $post );
		if ( ! $post_type ) {
			return '';
		}

		// Get the post type object to display the label instead of the slug.
		$post_type_object = get_post_type_object( $post_type );
		if ( ! $post_type_object ) {
			return $post_type;
		}

		return $post_type_object->labels->singular_name;
	}

	/**
	 * Get the SEO title from Yoast SEO.
	 *
	 * @return string
	 */
	public function tag_value_seo_title() {
		if ( ! $this->yoast_seo_available() ) {
			return '';
		}

		$post = get_post();
		if ( ! $post ) {
			return '';
		}

		$seo_title = get_post_meta( $post->ID, '_yoast_wpseo_title', true );

		return $seo_title;
	}

	/**
	 * Get the SEO description from Yoast SEO.
	 *
	 * @return string
	 */
	public function tag_value_seo_description() {
		if ( ! $this->yoast_seo_available() ) {
			return '';
		}

		$post = get_post();
		if ( ! $post ) {
			return '';
		}

		$seo_description = get_post_meta( $post->ID, '_yoast_wpseo_metadesc', true );

		return $seo_description;
	}

	/**
	 * Get the SEO title from All in One SEO.
	 *
	 * @return string
	 */
	public function tag_value_aioseo_title() {
		if ( ! $this->aioseo_available() ) {
			return '';
		}

		$post = get_post();
		if ( ! $post ) {
			return '';
		}

		// Get the post metadata object.
		$meta_data = aioseo()->meta->metaData->getMetaData( $post->ID );

		// Get the SEO title.
		if ( ! empty( $meta_data->title ) ) {
			return $meta_data->title;
		}

		return '';
	}

	/**
	 * Get the SEO description from All in One SEO.
	 *
	 * @return string
	 */
	public function tag_value_aioseo_description() {
		if ( ! $this->aioseo_available() ) {
			return '';
		}

		$post = get_post();
		if ( ! $post ) {
			return '';
		}

		// Get the post metadata object.
		$meta_data = aioseo()->meta->metaData->getMetaData( $post->ID );

		// Get the SEO description.
		if ( ! empty( $meta_data->description ) ) {
			return $meta_data->description;
		}

		return '';
	}

	/**
	 * Check if Easy Digital Downloads is installed & active on the site.
	 *
	 * @return bool
	 */
	public function edd_available() {
		return class_exists( 'Easy_Digital_Downloads' ) || class_exists( 'EDD_Download' );
	}

	/**
	 * Get the current download object if available
	 *
	 * @return EDD_Download|false
	 */
	private function get_current_download() {
		if ( ! $this->edd_available() ) {
			return false;
		}

		$download_id = get_the_ID();
		if ( ! $download_id ) {
			return false;
		}

		// Check if the current post is a download.
		if ( get_post_type( $download_id ) !== 'download' ) {
			return false;
		}

		return new EDD_Download( $download_id );
	}

	/**
	 * Get the download file name
	 *
	 * @return string
	 */
	public function tag_value_edd_file_name() {
		$download = $this->get_current_download();
		if ( ! $download ) {
			return '';
		}

		$files = $download->get_files();
		if ( empty( $files ) ) {
			return '';
		}

		$file = reset( $files );
		return isset( $file['name'] ) ? $file['name'] : '';
	}

	/**
	 * Get the download file URL
	 *
	 * @return string
	 */
	public function tag_value_edd_file_url() {
		$download = $this->get_current_download();
		if ( ! $download ) {
			return '';
		}

		$files = $download->get_files();
		if ( empty( $files ) ) {
			return '';
		}

		$file = reset( $files );
		return isset( $file['file'] ) ? $file['file'] : '';
	}

	/**
	 * Get the download price
	 *
	 * @return string
	 */
	public function tag_value_edd_file_price() {
		$download = $this->get_current_download();
		if ( ! $download ) {
			return '';
		}

		return edd_get_download_price( $download->ID );
	}

	/**
	 * Get the download notes
	 *
	 * @return string
	 */
	public function tag_value_edd_file_notes() {
		$download = $this->get_current_download();
		if ( ! $download ) {
			return '';
		}

		return $download->get_notes();
	}

	/**
	 * Get the current course if available
	 *
	 * @return \memberpress\courses\models\Course|false
	 */
	private function get_current_course() {
		if ( ! $this->memberpress_courses_available() ) {
			return false;
		}

		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return false;
		}

		// Check if the current post is a course.
		if ( get_post_type( $post_id ) !== 'mpcs-course' ) {
			return false;
		}

		return new \memberpress\courses\models\Course( $post_id );
	}

	/**
	 * Get the course price (via MemberPress memberships)
	 *
	 * @return string
	 */
	public function tag_value_course_price() {
		$course = $this->get_current_course();
		if ( ! $course ) {
			return '';
		}

		// Get memberships associated with this course.
		$memberships = $course->memberships();
		if ( empty( $memberships ) ) {
			return '';
		}

		// Return the price of the first membership.
		$membership = reset( $memberships );
		return $membership->price;
	}

	/**
	 * Get the course stock/availability
	 * Note: MemberPress Courses doesn't have a built-in stock concept like WooCommerce
	 * This is a placeholder that returns whether the course is enabled
	 *
	 * @return string
	 */
	public function tag_value_course_stock() {
		$course = $this->get_current_course();
		if ( ! $course ) {
			return '';
		}

		return 'enabled' === $course->status ? __( 'Available', 'insert-headers-and-footers' ) : __( 'Unavailable', 'insert-headers-and-footers' );
	}

	/**
	 * Get the course instructor name
	 *
	 * @return string
	 */
	public function tag_value_course_instructor() {
		$course = $this->get_current_course();
		if ( ! $course ) {
			return '';
		}

		$post = get_post( $course->ID );
		if ( ! $post ) {
			return '';
		}

		$author_id = $post->post_author;
		$author    = get_userdata( $author_id );

		// Check if there's a custom instructor name in course settings.
		$instructor_name = $course->certificates_instructor_name;
		if ( ! empty( $instructor_name ) && 'John Smith' !== $instructor_name ) {
			return $instructor_name;
		}

		return $author ? $author->display_name : '';
	}

	/**
	 * Get the current membership product being viewed.
	 * This is different from the previous implementation which got the user's active memberships.
	 * This gets the membership product that is currently being displayed on the page.
	 *
	 * @return MeprProduct|false
	 */
	private function get_current_membership_product() {
		if ( ! $this->memberpress_available() ) {
			return false;
		}

		// Get the current post.
		$current_post = MeprUtils::get_current_post();

		// Check if the current post is a membership product.
		if ( $current_post && MeprProduct::$cpt === $current_post->post_type ) {
			return new MeprProduct( $current_post->ID );
		}

		// If we're not on a membership page, check if a product ID is specified in the request.
		if ( isset( $_REQUEST['membership_id'] ) && is_numeric( $_REQUEST['membership_id'] ) ) { // phpcs:ignore
			return new MeprProduct( intval( $_REQUEST['membership_id'] ) ); // phpcs:ignore
		}

		// If we're on a transaction page, get the product from the transaction.
		if ( isset( $_REQUEST['trans_num'] ) ) { // phpcs:ignore
			$txn  = new MeprTransaction();
			$data = MeprTransaction::get_one_by_trans_num( sanitize_text_field( wp_unslash( $_REQUEST['trans_num'] ) ) ); // phpcs:ignore
			$txn->load_data( $data );

			if ( $txn->id && $txn->product_id ) {
				return new MeprProduct( $txn->product_id );
			}
		}

		return false;
	}

	/**
	 * Get the membership price.
	 *
	 * @return string
	 */
	public function tag_value_membership_price() {
		$product = $this->get_current_membership_product();

		if ( ! $product || null === $product->ID ) {
			return '';
		}

		$mepr_options    = MeprOptions::fetch();
		$currency_symbol = isset( $mepr_options->currency_symbol ) ? $mepr_options->currency_symbol : '$';
		return $currency_symbol . number_format( $product->price, 2 );
	}

	/**
	 * Get the membership registration URL.
	 *
	 * @return string
	 */
	public function tag_value_membership_url() {
		$product = $this->get_current_membership_product();

		if ( ! $product || null === $product->ID ) {
			return '';
		}

		return $product->url();
	}

	/**
	 * Get the membership billing type (one-time, recurring, etc).
	 *
	 * @return string
	 */
	public function tag_value_membership_billing_type() {
		$product = $this->get_current_membership_product();

		if ( ! $product || null === $product->ID ) {
			return '';
		}

		if ( $product->is_one_time_payment() ) {
			return __( 'One-time payment', 'insert-headers-and-footers' );
		} else {
			return __( 'Subscription', 'insert-headers-and-footers' );
		}
	}

	/**
	 * Get the membership period type (weekly, monthly, yearly, lifetime).
	 *
	 * @return string
	 */
	public function tag_value_membership_period_type() {
		$product = $this->get_current_membership_product();

		if ( ! $product || null === $product->ID ) {
			return '';
		}

		return $product->period_type;
	}

	/**
	 * Get the membership period (number of periods).
	 *
	 * @return string
	 */
	public function tag_value_membership_period() {
		$product = $this->get_current_membership_product();

		if ( ! $product || null === $product->ID ) {
			return '';
		}

		return $product->period;
	}
}
