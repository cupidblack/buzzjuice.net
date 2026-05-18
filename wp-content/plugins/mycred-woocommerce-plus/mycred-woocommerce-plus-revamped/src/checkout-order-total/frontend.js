import metadata from './block.json';
import { __ } from '@wordpress/i18n';

// Global import
const { registerCheckoutBlock } = wc.blocksCheckout;

const Block = ( { extensions } ) => {
    if (!extensions || !extensions.mycred_wooplus) {
        console.error('mycred_wooplus is undefined');
        return null;
    }
    const { mycred_wooplus } = extensions;
    if( 1 !== mycred_wooplus.mycred_partial_payment_check ) {
        return;
    }
    const pointCost     = mycred_wooplus.mycred_partial_payments_woo.checkout_total === 'cart' || mycred_wooplus.mycred_partial_payments_woo.checkout_total === 'no';
    const userBalance   = mycred_wooplus.mycred_partial_payments_woo.checkout_balance === 'cart' || mycred_wooplus.mycred_partial_payments_woo.checkout_balance === 'no';
    var wrapperDisable  = pointCost && userBalance ? 'disable' : '';
    var lowBalance      = mycred_wooplus.low_balance;
    return (mycred_wooplus.mycred_partial_payment_possible &&
        <div className={`mycred-woo-fields-wrapper ${wrapperDisable}`}>
            { !pointCost && (
                <div className="mycred-woo-order-total">
                    <span 
                        className="mycred-woo-order-total-label-aleem"
                        style={{ flexGrow: 1 }}
                    >
                        {__(mycred_wooplus.mycred_partial_payments_woo.checkout_total_label, 'mycredwooplus')}
                    </span>
                    <span className={`mycred-woo-order-total-value-aleem ${lowBalance}`}>
                        {mycred_wooplus.cart_total}
                    </span>
                </div>
            )}
            { !userBalance && (
                <div className="mycred-woo-total-credit">
                    <span 
                        className="mycred-woo-total-credit-label-aleem"
                        style={{ flexGrow: 1 }}
                    >
                        {__( mycred_wooplus.mycred_partial_payments_woo.checkout_balance_label, 'mycredwooplus' )}
                    </span>
                    <span className="mycred-woo-total-credit-value-aleem">
                        {mycred_wooplus.mycred_total_balance}
                    </span>
                </div>
            )}
        </div>
    )
}

registerCheckoutBlock( {
    metadata,
    component: Block
} );