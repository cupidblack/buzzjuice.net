import { useState } from 'react';
import PropTypes from 'prop-types';
import { ReactMultiEmail, isEmail } from 'react-multi-email';

import './styles.module.scss';

const MultiEmailSelect = ( { emails, onUpdate } ) => {

	const [ state, setState ] = useState( { emails } );

	return (
		<ReactMultiEmail
			emails={ state.emails }
			onChange={ ( emails ) => {
				setState( { emails } );

				if ( typeof onUpdate === 'function' ) {
					onUpdate( emails );
				}
			} }
			validateEmail={ email => isEmail( email ) }
			getLabel={ (
				email,
				index,
				removeEmail,
			) => {
				return (
					<div data-tag key={ index }>
						{ email }
						<span data-tag-handle onClick={ () => removeEmail( index ) }>
									x
								</span>
					</div>
				);
			} }
		/>
	);
};

MultiEmailSelect.propTypes = {
	emails: PropTypes.array,
	onUpdate: PropTypes.func,
};

MultiEmailSelect.defaultProps = {
	emails: [],
}

export default MultiEmailSelect;
