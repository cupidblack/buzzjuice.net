import React, { useState } from 'react';
import { __ } from '@wordpress/i18n';
import { dispatch } from '@wordpress/data';
import { getSetting } from '@woocommerce/settings';
import { store as noticesStore } from '@wordpress/notices';

export const myCredPartialPayment = ( { extensions, cart } ) => {

    const settings = getSetting( 'mycredwooplus_data', {} );
    const { createInfoNotice, removeNotice } = dispatch( noticesStore );
    if (!extensions || !extensions.mycred_wooplus) {
        console.error('mycred_wooplus is undefined');
        return null;
    }
    const { mycred_wooplus } = extensions;
    if( 1 !== mycred_wooplus.mycred_partial_payment_check ) {
        return;
    }
    if ( 'yes' === settings.reward_checkout_product_total || 'yes' === settings.reward_points_global ) {
        const noticeContent = mycred_wooplus.mycred_info_notice_content;
        if ( noticeContent ) {
            createInfoNotice(
                __(
                    noticeContent,
                    'mycredwooplus'
                ),
                {
                    id: 'mycred-wooplus-cart-notice',
                    isDismissible: false,
                    context: 'wc/cart',
                    
                }
            );
        } else {
            removeNotice( 'mycred-wooplus-cart-notice', 'wc/cart', );
        }
    }


    const disableWhenCheckout = mycred_wooplus.mycred_partial_payments_woo ? mycred_wooplus.mycred_partial_payments_woo.change_position === "cart" : true; 
    
    if ( disableWhenCheckout ) {
        return null;
    }

    const partialPayemntTitle       = mycred_wooplus.mycred_partial_payments_woo.title;
    const partialPayemntDesc        = mycred_wooplus.mycred_partial_payments_woo.desc;
    const minValue                  = mycred_wooplus.mycred_partial_payments_woo.min;
    const spanMinValue              = mycred_wooplus.spanMin;
    const mixValue                  = mycred_wooplus.mycred_partial_payments_woo.max;
    const discountWithExchange      = mycred_wooplus.mycred_partial_payments_woo.min * mycred_wooplus.mycred_partial_payments_woo.exchange;
    const inputClass                = mycred_wooplus.mycred_partial_payments_woo.selecttype === 'input' ? 'text' : 'range';
    const inputType                 = inputClass === 'text' ? 'number' : 'range';
    const partialPaymentBtnText     = mycred_wooplus.mycred_partial_payments_woo.button;
    const Counterstep               = mycred_wooplus.mycred_partial_payments_woo.step;
    const WooCommerceCurrency       = wc.priceFormat.getCurrency();
    var blockOnTop                  = mycred_wooplus.mycred_partial_payments_woo.position === 'before' ? 'order-top' : '';
    const availablePointTypes = mycred_wooplus.available_point_types;

    const [inputValue, setInputValue] = useState( minValue );

    const onInputChange = (e) => {
        if  ( '' != e.target.value ) {
            setInputValue(e.target.value);
        } 
    }

            return ( mycred_wooplus.mycred_partial_payment_possible &&
                <div id="mycred-partial-payment-woo" class={`${blockOnTop}`} style={{ borderColor: 'lightgrey', borderWidth: '0.5px 0 0', padding: '16px 0px' }}>
                    <h3>{__( partialPayemntTitle , 'mycredwooplus' )}</h3>
                    <p>{__( partialPayemntDesc , 'mycredwooplus' )}</p>
                     <label htmlFor="point-type-select">{__('Select Point Type:', 'mycredwooplus')}</label>
                {availablePointTypes.length > 0 ? (
                  <>
                    <label htmlFor="point-type-select">
                      {__('Select Point Type:', 'mycredwooplus')}
                    </label>
                    <select id="point-type-select" name="point_type">
                      <option value="">{__('Select Point Type', 'mycredwooplus')}</option>
                      {availablePointTypes.map(({ key, label }) => (
                        <option key={key} value={key}>{label}</option>
                      ))}
                    </select>
                  </>
                ) : (
                  <p>No Point type available</p>
                )}
                    <div id="mycred-partial-payment-wrapper" className={ "uses-" + mycred_wooplus.mycred_partial_payments_woo.selecttype }>
                        <div id="mycred-partial-payment-total">
                        <h2> 
                            <span>{__( spanMinValue, 'mycredwooplus' )}</span> 
                        </h2>
                        <p><span class="amount">{ WooCommerceCurrency.symbol + ' ' + discountWithExchange }</span> {__(' Discount', 'mycredwooplus')}</p>
                        </div>
                        <form id="mycred_partial_payment_form" action="" method="post">
                            <div id="mycred-range-selector">
                                <input 
                                    type={ inputType } 
                                    className={ "mycred-partial-payment-inpt input-" + inputType } 
                                    name={ "mycred_partial_payment[amount]" }
                                    min={ minValue }
                                    max={ mixValue }
                                    placeholder={ Counterstep ? "Increments of " + Counterstep : undefined }
                                    step={ Counterstep ? Counterstep : undefined }
                                    style={{ width: '100%' }}
                                    value={ inputValue }
                                    onChange={ onInputChange } 
                                />
                            </div>
                            <div id="mycred-range-action">
                                <input type="submit" name="submit" value={__( partialPaymentBtnText, 'mycredwooplus' )} />
                            </div>
                        </form>
                    </div>
                </div>          
        )
}