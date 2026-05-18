import { useState } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

export const MyCredWooPlusCoupons = ( { couponData, cartData } ) => { 

    const couponTitle = couponData.mycred_partial_payments_woo.coupon_title;
    const couponDesc = couponData.mycred_partial_payments_woo.coupon_desc;
    const couponMax = couponData.mycred_partial_payments_woo.coupon_max;

    const [formValues, setFormValues] = useState({
        amount: '0',
    });
    
    return (
        <div class="mycred-coupon" style={{borderColor:"lightgrey", borderWidth:"0.5px 0 0", padding:"16px 0px"}}>
            <h3>{__( couponTitle , 'mycredwooplus' )}</h3>
            <p>{__( couponDesc , 'mycredwooplus' )}</p>
            <p class="mycred-user-balance">{__( 'Your current balance is: ' + couponData.mycred_total_balance , 'mycredwooplus' )}</p>
            <p>{__( 'You can only create: ' + couponMax + ' amount coupon.' , 'mycredwooplus' )}</p>
            <p class="mycred-coupon-code"></p>
            {couponData.mycred_total_balance != 0 ? (
                <form
                    id="mycred_coupon_form"
                    action=""
                    method="post"
                >
                    <label>{__( 'Amount' , 'mycredwooplus' )}</label>
                    <input
                        type="number"
                        class="mycred-coupon-amount"
                        size="5"
                        name="mycred_to_woo[amount]"
                        value={formValues.amount}
                        onChange={(e) =>
                            setFormValues({
                                ...formValues,
                                amount: e.target.value,
                            })
                        }
                    />
                    <div id="mycred-coupon-action">
                        <input type="submit" name="submit" value="Create Coupon" />
                    </div>
                </form>
            ) : (
                <p>{__( 'Not enough points to create coupons.', 'mycredpartwoo' )}</p>
            )}
        </div>
    );

}