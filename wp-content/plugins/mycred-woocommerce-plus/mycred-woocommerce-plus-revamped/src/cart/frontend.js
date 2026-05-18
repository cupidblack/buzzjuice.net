
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import { myCredPartialPayment } from './mycred-partial-payment.js';

// Global import
const { registerCheckoutBlock } = wc.blocksCheckout;

registerCheckoutBlock( {
    metadata,
    component: myCredPartialPayment
} );