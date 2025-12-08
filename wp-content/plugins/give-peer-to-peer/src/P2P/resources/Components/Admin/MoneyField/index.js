import { useState, useMemo, forwardRef } from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';
// Utils
import {
	getLocale,
	getCurrency,
	getCurrencySymbol,
	getCurrencyPosition,
} from '@p2p/js/utils';

import styles from './styles.module.scss';

const MoneyField = forwardRef( ( props, ref ) => {

	const { name, initialAmount, defaultAmount, onChange, error } = props;
	const amount = initialAmount ?? defaultAmount;

	const handleFormat = ( e ) => {
		onChange( e );
		e.target.value = formatNumber( e.target.value );
	};

    /**
     * @since 1.6.0 Updated to return empty string, allowing users to completely remove input value
     */
	const formatNumber = ( number ) => {
		try {
			const value = Intl.NumberFormat( getLocale(), {
				style: 'decimal',
				minimumFractionDigits: 0,
				maximumFractionDigits: 0,
			} ).format( parseFloat( number.replace(',', '') ) );

			if ( isNaN( parseFloat( value ) ) ) {
				if ( isNaN( parseFloat( defaultAmount ) ) ) {
					return 0;
				}

				return "";
			}

			return value;
		} catch ( err ) {
			console.log( err.message );
			return number;
		}
	};

	const classes = classNames( styles.moneySymbol, {
		[ styles.moneySymbolBefore ]: 'before' == getCurrencyPosition(),
        [styles.hasError]: error,
    } );

    const moneyFieldClass = classNames(styles.moneyField, {
        [styles.hasError]: error,
    })

	return (
		<div className={ styles.moneyInputContainer }>
			<span className={ classes }>
				{ getCurrencySymbol() }
			</span>
			<input
				ref={ ref }
				name={ name }
				type="text"
				className={ moneyFieldClass }
				onBlur={ handleFormat }
				onChange={ handleFormat }
				defaultValue={ formatNumber( amount.toString() ) }
			/>
		</div>
	);
} );

MoneyField.propTypes = {
	// Input name
	name: PropTypes.string,
	// default amount
	defaultAmount: PropTypes.oneOfType( [ PropTypes.string, PropTypes.number ] ).isRequired,
};

export default MoneyField;
