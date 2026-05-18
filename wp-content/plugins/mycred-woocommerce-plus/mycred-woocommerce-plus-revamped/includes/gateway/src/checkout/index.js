
import { registerBlockType } from '@wordpress/blocks';

registerBlockType( {
	"name": "mycred-woocommerce-plus/mycred-woo-plus-checkout-block",
	"version": "1.0.0",
	"title": "myCred WooCommerce Plus",
	"category": "woocommerce",
    "parent": [ "woocommerce/checkout-totals-block" ],
	"attributes": {
		"lock": {
			"type": "object",
			"default": {
				"remove": true,
				"move": true
			}
		}
	},
	"textdomain": "mycred-woocommerce-plus",
}, {} );