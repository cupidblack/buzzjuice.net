/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';

const render = () => {};

registerPlugin('mycred-woocommerce-plus', {
	render,
	scope: 'woocommerce-checkout',
});