<?php

class WPCode_Admin_Page_Pixel_Pro extends WPCode_Admin_Page_Pixel {
	/**
	 * Get the overlay for the pixel settings.
	 *
	 * @return string
	 */
	public function get_pixel_overlay() {

		$addon_name = 'wpcode-pixel';

		if ( wpcode()->license->is_addon_allowed( $addon_name ) ) {
			// Is the addon installed?
			$addon_data  = wpcode()->addons->get_addon( $addon_name );
			$button_text = __( 'Install Addon Now', 'wpcode-premium' );
			$title       = __( 'The Conversion Pixels Addon is not installed', 'wpcode-premium' );
			if ( ! empty( $addon_data->installed ) ) {
				$button_text = __( 'Activate Addon', 'wpcode-premium' );
				$title       = __( 'The Conversion Pixels Addon is not active', 'wpcode-premium' );
			}

			// Show an upsell box with a button to install the plugin now.
			return self::get_upsell_box(
				$title,
				'<p>' . __( 'Install the plugin now to start tracking pixels from popular platforms such as Facebook, Google Ads, Pinterest and TikTok with WooCommerce, Easy Digital Downloads and MemberPress.', 'wpcode-premium' ) . '</p>',
				array(
					'text'       => $button_text,
					'tag'        => 'button',
					'class'      => 'wpcode-button wpcode-button-large wpcode-button-install-addon',
					'attributes' => array(
						'data-addon' => $addon_name,
					),
				)
			);
		} else {
			$text = sprintf(
				// translators: %1$s and %2$s are <u> tags.
				'<p>' . __( 'While you can always add pixels manually using code snippets, our Conversion Pixels addon helps you %1$ssave time%2$s while %1$sreducing errors%2$s. It lets you properly implement Facebook, Google, Pinterest, and TikTok ads tracking with deep integrations for eCommerce events, interaction measurement, and more. This addon is available on WPCode Plus plan or higher.', 'insert-headers-and-footers' ) . '</p>',
				'<u>',
				'</u>'
			);
			return self::get_upsell_box(
				__( 'Conversion Pixels Addon is not available on your plan', 'wpcode-premium' ),
				$text,
				array(
					'text' => __( 'Upgrade Now', 'wpcode-premium' ),
					'url'  => wpcode_utm_url( 'https://library.wpcode.com/account/downloads/', 'conversion-pixels', 'tab-' . $this->view, 'upgrade-to-pro' ),
				),
				array(),
				array(
					__( 'Seamless integration with WooCommerce, Easy Digital Downloads & MemberPress', 'insert-headers-and-footers' ),
					__( 'Works with Facebook, Google Ads, Pinterest, and TikTok', 'insert-headers-and-footers' ),
					__( 'No coding required', 'insert-headers-and-footers' ),
					__( '1-click setup for conversion tracking', 'insert-headers-and-footers' ),
				)
			);
		}
	}
}
