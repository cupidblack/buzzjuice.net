import { __ } from '@wordpress/i18n';
import "./frontend.css";

const { registerPlugin } = wp.plugins;
const { ExperimentalOrderMeta } = wc.blocksCheckout;

const Block = ( { extensions } ) => {
    if (!extensions || !extensions.mycred_wooplus) {
        console.error('mycred_wooplus is undefined');
        return null;
    }
    const { mycred_wooplus } = extensions;
    if( 1 !== mycred_wooplus.mycred_partial_payment_check ) {
        return;
    }
    const pointCost     = mycred_wooplus.mycred_partial_payments_woo.checkout_total === 'checkout' || mycred_wooplus.mycred_partial_payments_woo.checkout_total === 'no';
    const userBalance   = mycred_wooplus.mycred_partial_payments_woo.checkout_balance === 'checkout' || mycred_wooplus.mycred_partial_payments_woo.checkout_balance === 'no';
    var wrapperDisable  = pointCost && userBalance ? 'disable' : '';
    var lowBalance      = mycred_wooplus.low_balance;
    return ( mycred_wooplus.mycred_partial_payment_possible &&
        <div class={`mycred-woo-fields-wrapper ${wrapperDisable}`}>
            { !pointCost && (
                <div class="mycred-woo-order-total">
                    <span class="mycred-woo-order-total-label-aleem">{__(mycred_wooplus.mycred_partial_payments_woo.checkout_total_label, 'mycredwooplus')}</span>
                    <span class={`mycred-woo-order-total-value-aleem ${lowBalance}`}>{mycred_wooplus.cart_total}</span>
                </div>
            )}
            { !userBalance && (
                <div class="mycred-woo-total-credit">
                    <span class="mycred-woo-total-credit-label-aleem">{__( mycred_wooplus.mycred_partial_payments_woo.checkout_balance_label, 'mycredwooplus' )}</span>
                    <span class="mycred-woo-total-credit-value-aleem">{mycred_wooplus.mycred_total_balance}</span>
                </div>
            )}
        </div>
    )
}

const render = () => {
	return (
		<ExperimentalOrderMeta>
			<Block />
		</ExperimentalOrderMeta>
	);
};

registerPlugin( 'mycredwooplus', {
    render,
    scope: 'woocommerce-checkout',
} );