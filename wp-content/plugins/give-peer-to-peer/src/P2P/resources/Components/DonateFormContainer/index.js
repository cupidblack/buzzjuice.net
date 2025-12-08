import { Fragment, useEffect } from 'react';
import PropTypes from 'prop-types';
import { useStore } from '@p2p/js/frontend/App/store';
import { InfoIcon } from "../Icons";
import CampaignLogo from '@p2p/Components/CampaignLogo';

import styles from './style.module.scss';
import logoStyles from '../../css/frontend/logoContainer.module.scss';

const { __ } = wp.i18n;

const DonateFormContainer = ( { children, title, backgroundImage, logoImage, isCampaign } ) => {

	const [ { campaign } ] = useStore();

	useEffect(() => {
		document.getElementById("p2p-app").scrollIntoView();
	}, []);

	const getTitle = () => {
		if ( Array.isArray( title ) ) {
			return title.map( ( item ) => <Fragment key={item}>{item}</Fragment> )
		}

		return title;
	}

	return (
		<div className={ styles.container }>
			<header>
                <div className={ logoStyles.logocontainer }>
    				<CampaignLogo/>
					{ ! isCampaign && (
					<div className={ styles.fundraiserpill }>
						<InfoIcon/>
						<span>
							{ __( 'Fundraising in support of the', 'give-peer-to-peer' ) }
								&nbsp;
								<a href={ campaign.campaign_url } className={ styles.campaignlink }>
								{ campaign.campaign_title }
							</a>
						</span>
					</div>
					)}
				</div>
				<h1>{ getTitle() }</h1>
			</header>
			<div className={ styles.content } style={ { backgroundImage: `url(${backgroundImage})` } }>
				<div className={ styles.inner }>
					{ children }
				</div>
				<div className={ styles.tintOverlay }/>
			</div>
		</div>
	);
};

DonateFormContainer.propTypes = {
	children: PropTypes.node,
	title: PropTypes.oneOfType([PropTypes.string, PropTypes.array]).isRequired,
	image: PropTypes.string,
	isCampaign: PropTypes.bool
};

DonateFormContainer.defaultProps = {
	isCampaign: false,
}

export default DonateFormContainer;
