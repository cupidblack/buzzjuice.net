/**
 * Checkout OTP React Block — using #email field
 */

/**
 * External dependencies
 */
import { useEffect, useState, useCallback } from '@wordpress/element';
import { TextControl, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { debounce } from 'lodash';

/**
 * Internal dependencies
 */
import { options } from './options';

export const Block = ( { checkoutExtensionData } ) => {
	const { setExtensionData } = checkoutExtensionData;

	const debouncedSetExtensionData = useCallback(
		debounce( ( namespace, key, value ) => {
			setExtensionData( namespace, key, value );
		}, 400 ),
		[ setExtensionData ]
	);

	/* Checkout store dispatch and select */
	const { setValidationErrors } = useDispatch( 'wc/store/checkout' );
	const { validationErrors } = useSelect( ( select ) => ( {
		validationErrors: select( 'wc/store/checkout' ).getValidationErrors() || [],
	} ) );

	/* State */
	const [ email, setEmail ] = useState( '' ); // synced with #email
	const [ sending, setSending ] = useState( false );
	const [ codeSent, setCodeSent ] = useState( false );
	const [ otp, setOtp ] = useState( '' );
	const [ verifying, setVerifying ] = useState( false );
	const [ isVerified, setIsVerified ] = useState( false );
	const [ errorMsg, setErrorMsg ] = useState( '' );
	const [ infoMsg, setInfoMsg ] = useState( '' );
	const [ resendSecondsLeft, setResendSecondsLeft ] = useState( 0 );
	const resendDelay = 30;

	const validateEmail = ( e ) => {
		const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
		return re.test( e );
	};
    useEffect( () => {
        const handleSubmit = ( e ) => {
            // Try to detect place order button (blocks use button[data-checkout-button])
            const placeOrderBtn = document.querySelector( '[data-checkout-button], button[name="woocommerce_checkout_place_order"]' );
            // If no button, do nothing
            if ( ! placeOrderBtn ) return;

            // Check session/extension data - we set this elsewhere when verified
            const isVerified = window?.cev_ajax_object?.isVerified || false;
            const verifiedEmail = window?.cev_ajax_object?.verifiedEmail || '';

            // If not verified, show a UI notice and prevent default
            if ( ! isVerified ) {
                e.preventDefault();
                e.stopPropagation();
                // fallback visual: use alert or a nicer notice in your block
                alert( window?.cev_ajax_object?.email_verification_required_msg || 'Please verify your email before placing the order.' );
                return false;
            }
        };

        // Listen for clicks on document and intercept clicks on order button
        document.addEventListener( 'click', handleSubmit, true );
        return () => document.removeEventListener( 'click', handleSubmit, true );
    }, [] );
	/* Validation error management for compulsory verification */
	useEffect( () => {
		const errorId = 'cev-email-verification-required';
		const hasError = validationErrors.some( ( error ) => error.id === errorId );

		// Show error if email is provided and valid but not verified
		if ( email && validateEmail( email ) && ! isVerified ) {
			if ( ! hasError ) {
				setValidationErrors( [
					...validationErrors,
					{
						id: errorId,
						message: __( 'Email verification is required', 'customer-email-verification' ),
						hidden: false,
					},
				] );
			}
		} else {
			// Clear the error if condition is no longer met
			if ( hasError ) {
				setValidationErrors( validationErrors.filter( ( error ) => error.id !== errorId ) );
			}
		}
	}, [ email, isVerified, validationErrors, setValidationErrors ] );

	/* Countdown timer for resend */
	useEffect( () => {
		let timer = null;
		if ( resendSecondsLeft > 0 ) {
			timer = setInterval( () => {
				setResendSecondsLeft( ( s ) => {
					if ( s <= 1 ) {
						clearInterval( timer );
						return 0;
					}
					return s - 1;
				} );
			}, 1000 );
		}
		return () => {
			if ( timer ) clearInterval( timer );
		};
	}, [ resendSecondsLeft ] );

	/* Sync with checkout #email field */
	useEffect( () => {
		const el = document.querySelector( '#email' );
		if ( el ) {
			setEmail( el.value || '' );

			const handler = ( e ) => {
				const val =
					e && e.target && typeof e.target.value !== 'undefined'
						? e.target.value
						: document.querySelector( '#email' )?.value || '';

				if ( isVerified && val !== ( window?.cev_ajax_object?.verifiedEmail || '' ) ) {
					setIsVerified( false );
					setExtensionData( 'shipping-workshop', 'isEmailVerified', false );
					setExtensionData( 'shipping-workshop', 'verifiedEmail', '' );
					if ( typeof window !== 'undefined' && window.cev_ajax_object ) {
						window.cev_ajax_object.isVerified = false;
						window.cev_ajax_object.verifiedEmail = '';
					}
				}

				setEmail( val );
			};

			el.addEventListener( 'input', handler );
			el.addEventListener( 'change', handler );

			return () => {
				el.removeEventListener( 'input', handler );
				el.removeEventListener( 'change', handler );
			};
		}
	}, [ isVerified, setExtensionData ] );

	/* Persist verified email to checkout data */
	useEffect( () => {
		if ( isVerified ) {
			setExtensionData( 'shipping-workshop', 'verifiedEmail', email );
			setExtensionData( 'shipping-workshop', 'isEmailVerified', true );
			if ( typeof window !== 'undefined' ) {
				window.cev_ajax_object = window.cev_ajax_object || {};
				window.cev_ajax_object.verifiedEmail = email;
				window.cev_ajax_object.isVerified = true;
			}
		}
	}, [ isVerified, email, setExtensionData ] );

	/* Send verification code */
	const sendCode = async () => {
		setErrorMsg( '' );
		setInfoMsg( '' );

		const emailEl = document.querySelector( '#email' );
		const currentEmail =
			emailEl && typeof emailEl.value !== 'undefined'
				? emailEl.value.trim()
				: email.trim();

		if ( ! currentEmail ) {
			setErrorMsg(
				window?.cev_ajax_object?.email_required_msg ||
					__( 'Email is required for verification', 'customer-email-verification' )
			);
			return;
		}
		if ( ! validateEmail( currentEmail ) ) {
			setErrorMsg(
				window?.cev_ajax_object?.invalid_email_msg ||
					__( 'Email is Invalid', 'customer-email-verification' )
			);
			return;
		}

		setSending( true );

		try {
			const body = new URLSearchParams();
			body.append( 'action', 'send_email_on_chekout_page' );
			body.append( 'email', currentEmail );
			body.append( 'security', window.cev_ajax_object.checkout_send_verification_email );

			const resp = await fetch( window.cev_ajax_object.ajax_url, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
				},
				body: body.toString(),
			} );
			const data = await resp.json();

			if ( data.verify === false ) {
				setCodeSent( true );
				setOtp( '' );
				setInfoMsg(
					window?.cev_ajax_object?.verification_send_msg ||
						__( 'Verification code sent', 'customer-email-verification' )
				);
				setResendSecondsLeft( resendDelay );
				setEmail( currentEmail );
			} else if (
				data.success === 'alredy_verify' ||
				data.success === 'already_verified' ||
				data.verify === 'already'
			) {
				setIsVerified( true );
				setCodeSent( false );
				setInfoMsg(
					window?.cev_ajax_object?.all_ready_email_verified_msg ||
						__( 'Email address is already verified', 'customer-email-verification' )
				);
				setEmail( currentEmail );
			} else if ( data.error ) {
				setErrorMsg( data.error );
			} else {
				setCodeSent( true );
				setResendSecondsLeft( resendDelay );
				setEmail( currentEmail );
			}
		} catch ( e ) {
			console.error( 'sendCode error', e );
			setErrorMsg( __( 'An error occurred sending the code.', 'customer-email-verification' ) );
		} finally {
			setSending( false );
		}
	};

	/* Resend */
	const resendCode = async ( wp_nonce ) => {
		if ( resendSecondsLeft > 0 ) return;
		setErrorMsg( '' );
		setInfoMsg( '' );

		try {
			const body = new URLSearchParams();
			body.append( 'action', 'checkout_page_send_verification_code' );
			body.append( 'email', email );
			body.append(
				'wp_nonce',
				wp_nonce || window?.cev_ajax_object?.wc_cev_email_guest_user || ''
			);

			const resp = await fetch( window.cev_ajax_object.ajax_url, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: body.toString(),
			} );
			const data = await resp.json();

			if ( data.cev_resend_limit_reached === 'true' ) {
				setErrorMsg( __( 'Resend limit reached.', 'customer-email-verification' ) );
			} else {
				setInfoMsg(
					window?.cev_ajax_object?.verification_send_msg ||
						__( 'Verification code resent', 'customer-email-verification' )
				);
				setResendSecondsLeft( resendDelay );
			}
		} catch ( e ) {
			console.error( 'resendCode error', e );
			setErrorMsg( __( 'An error occurred while resending.', 'customer-email-verification' ) );
		}
	};

	/* Cancel */
	const cancelVerification = () => {
		setCodeSent( false );
		setOtp( '' );
		setErrorMsg( '' );
		setInfoMsg( '' );
		setResendSecondsLeft( 0 );
	};

	/* Verify */
	const verifyOtp = async () => {
		setErrorMsg( '' );
		setInfoMsg( '' );

		if ( ! otp || otp.trim() === '' ) {
			setErrorMsg(
				window?.cev_ajax_object?.verification_code_error_msg ||
					__( 'Please enter the verification code', 'customer-email-verification' )
			);
			return;
		}

		setVerifying( true );

		try {
			const body = new URLSearchParams();
			body.append( 'action', 'checkout_page_verify_code' );
			body.append( 'pin', otp.trim() );
			body.append( 'security', window.cev_ajax_object.checkout_verify_code );

			const resp = await fetch( window.cev_ajax_object.ajax_url, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: body.toString(),
			} );
			const data = await resp.json();

			if ( data.success === 'true' || data.success === true ) {
				setIsVerified( true );
				setCodeSent( false );
				setOtp( '' );
				setInfoMsg(
					window?.cev_ajax_object?.email_verified_msg ||
						__( 'Email verified successfully', 'customer-email-verification' )
				);
				setExtensionData( 'shipping-workshop', 'verifiedEmail', email );
				setExtensionData( 'shipping-workshop', 'isEmailVerified', true );
			} else {
				setErrorMsg(
					window?.cev_ajax_object?.verification_code_error_msg ||
						__( 'Invalid verification code', 'customer-email-verification' )
				);
			}
		} catch ( e ) {
			console.error( 'verifyOtp error', e );
			setErrorMsg( __( 'An error occurred while verifying.', 'customer-email-verification' ) );
		} finally {
			setVerifying( false );
		}
	};

	return (
		<div className="wp-block-shipping-workshop-not-at-home">
			{ ! isVerified && ! codeSent && (
				<div style={ { marginTop: '1rem' } }>
					<Button isPrimary onClick={ sendCode } disabled={ sending }>
						{ sending
							? __( 'Sending…', 'customer-email-verification' )
							: window?.cev_ajax_object?.get_otp_btn_text ||
							  __( 'Send verification code', 'customer-email-verification' ) }
					</Button>

					{ infoMsg && (
						<div style={ { color: 'green', marginTop: '0.5rem' } }>{ infoMsg }</div>
					) }
					{ errorMsg && (
						<div style={ { color: '#dc2626', marginTop: '0.5rem' } }>{ errorMsg }</div>
					) }
				</div>
			) }

			{ codeSent && ! isVerified && (
				<div style={ { marginTop: '1rem' } } className="cev-pro-otp-ui">
					<label style={ { display: 'block', fontWeight: 500, marginBottom: '.25rem' } }>
						{ window?.cev_ajax_object?.email_verification_code_label ||
							__( 'Verification code', 'customer-email-verification' ) }
					</label>

					<TextControl
						value={ otp }
						onChange={ setOtp }
						placeholder={ __( 'Enter verification code', 'customer-email-verification' ) }
						type="text"
					/>

					<div
						style={ {
							marginTop: '.5rem',
							display: 'flex',
							gap: '.5rem',
							alignItems: 'center',
						} }
					>
						<Button isPrimary onClick={ verifyOtp } disabled={ verifying }>
							{ verifying
								? __( 'Verifying…', 'customer-email-verification' )
								: window?.cev_ajax_object?.verify_button_text ||
								  __( 'Verify', 'customer-email-verification' ) }
						</Button>

						{ resendSecondsLeft > 0 ? (
							<span style={ { fontSize: '.875rem', color: '#6b7280' } }>
								{ window?.cev_ajax_object?.count_down_text ||
									__( 'You can resend the code in', 'customer-email-verification' ) }{' '}
								{ resendSecondsLeft }s
							</span>
						) : (
							<a
								href="#"
								onClick={ ( e ) => {
									e.preventDefault();
									resendCode( window?.cev_ajax_object?.wc_cev_email_guest_user );
								} }
								style={ { fontSize: '.875rem' } }
							>
								{ window?.cev_ajax_object?.resend_verification_msg ||
									__( 'Resend Verification Code', 'customer-email-verification' ) }
							</a>
						) }

						<a
							href="#"
							onClick={ ( e ) => {
								e.preventDefault();
								cancelVerification();
							} }
							style={ {
								marginLeft: '.5rem',
								fontSize: '.875rem',
								color: '#dc2626',
							} }
						>
							{ window?.cev_ajax_object?.cancel_verification_text ||
								__( 'Cancel', 'customer-email-verification' ) }
						</a>
					</div>
				</div>
			) }

			{ isVerified && (
				<div style={ { marginTop: '0.75rem' } }>
					<small style={ { color: 'green' } }>
						{ window?.cev_ajax_object?.email_verified_msg ||
							__( 'Email verified', 'customer-email-verification' ) }{' '}
						— { email }
					</small>
				</div>
			) }
		</div>
	);
};

export default Block;