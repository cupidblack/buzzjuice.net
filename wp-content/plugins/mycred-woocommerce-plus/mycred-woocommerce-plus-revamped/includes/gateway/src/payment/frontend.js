import { getSetting } from '@woocommerce/settings';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';

const allGateways = getSetting( 'mycred_plus_data', {} );
const registerMyCredGateway = (key, gatewaySettings) => {

	const defaultLabel = __('Default Pay with myCRED', 'mycred-woocommerce-plus');
	const label = decodeEntities( gatewaySettings.title ) || defaultLabel;

	const icon = gatewaySettings.icon;
	
	const Content = () => {
		return decodeEntities( gatewaySettings.description || '' );
	};
	
	const Label = (props) => {
		const { PaymentMethodLabel } = props.components;
	
		return (
			<div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", width: "100%" }}>
				<PaymentMethodLabel text={label} />
				<img src={icon} />
			</div>
		);
	};

	const canMyCredMakePayment = ( { cart } ) => {
		if ( 'yes' === cart.extensions.mycred_wooplus.payment_gateway ) {
			return true;
		} else {
			return false;
		}
	};

	const myCred = {
		name: `mycred_${key}`,
		label: <Label />,
		content: <Content />,
		edit: <Content />,
		canMakePayment: () => { return true },
		ariaLabel: label,
		paymentMethodId: `mycred_${key}`,
		supports: {
			features: gatewaySettings.supports,
		},
	};
	
	registerPaymentMethod( myCred );
};

Object.entries(allGateways).forEach(([key, gatewaySettings]) => {
    registerMyCredGateway(key, gatewaySettings);
});
