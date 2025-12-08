import { Fragment } from 'react';
import PropTypes from 'prop-types';

import StepNavigation from '@p2p/Components/StepNavigation';
import CampaignLogo from '@p2p/Components/CampaignLogo';
import styles from './FormContainer.module.scss';
import logoStyles from '../../css/frontend/logoContainer.module.scss';

const FormContainer = ( { children, title, image, showStepNavigation } ) => {

	const getTitle = () => {
		if ( Array.isArray( title ) ) {
			return title.map( ( item ) => <Fragment key={item}>{item}</Fragment> )
		}

		return title;
	}

	return (
		<div className={ styles.container }>
			<div className={ styles.header }>
                <div className={ logoStyles.logoContainer }>
                    <CampaignLogo/>
                    <h1 className={ styles.title }>{ getTitle() }</h1>
                </div>
			</div>
			{ showStepNavigation && <StepNavigation /> }
			<div className={ styles.content } style={ { backgroundImage: `url(${image})` } }>
				<div className={ styles.inner }>
					{ children }
				</div>
				<div className={ styles.tintOverlay }/>
			</div>
		</div>
	);
};

FormContainer.propTypes = {
	children: PropTypes.node,
	title: PropTypes.oneOfType([PropTypes.string, PropTypes.array]).isRequired,
	image: PropTypes.string,
	showStepNavigation: PropTypes.bool,
};

FormContainer.defaultProps = {
	showStepNavigation: false,
}

export default FormContainer;
