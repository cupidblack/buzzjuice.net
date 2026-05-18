import { __ } from '@wordpress/i18n';
import { getSetting } from '@woocommerce/settings';
import "./frontend.css";

const { registerCheckoutBlock } = wc.blocksCheckout;

const settings = getSetting('mycred_plus_data', {});

const Block = ({ extensions }) => {
    const { mycred_plus } = extensions;

    console.log( mycred_plus );
    
    return (
        <div>
            {Object.entries(mycred_plus)
                .filter(([key]) => key !== 'mycred')
                .map(([key, value]) => (
                    <div className="mycred-woo-fields-wrapper mycred-wooplus" data-type={`${key}`} style={{ display: 'none' }}>
                        <div className="mycred-woo-order-details">
                            <div className="mycred-woo-order-total">
                                <span className="mycred-woo-order-total-label">
                                    {__(value.mycred_woo_total_label, 'mycred-woocommerce-plus')}
                                </span>
                                <span className="mycred-woo-order-total-value">
                                    {value.mycred_woo_total}
                                </span>
                            </div>
                            <div className="mycred-woo-total-credit">
                                <span className="mycred-woo-total-credit-label">
                                    {__(value.mycred_woo_balance_label, 'mycred-woocommerce-plus')}
                                </span>
                                <span className="mycred-woo-total-credit-value">
                                    {value.mycred_woo_balance}
                                </span>
                            </div>
                        </div>
                    </div>
                ))}
        </div>
    );
    
};

registerCheckoutBlock({
    metadata: {
        name: "mycred-woocommerce/mycred-woo-plus-checkout-block",
        version: "1.0.0",
        title: "myCred WooCommerce",
        category: "woocommerce",
        parent: ["woocommerce/checkout-totals-block"],
        attributes: {
            lock: {
                type: "object",
                default: {
                    remove: true,
                    move: true
                }
            }
        },
        textdomain: "mycred-woocommerce-plus"
    },
    component: Block
});
