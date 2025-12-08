import PropTypes from 'prop-types';
import { useStore } from '@p2p/js/frontend/App/store';
import { LoginIcon } from '@p2p/Components/Icons';

import styles from './style.module.scss';

const { __ } = wp.i18n;

const ProfileLogin = ( { label } ) => {
	const [ { campaign } ] = useStore();

	return (
		<div className={ styles.container }>
			{ label && (
				<span className={ styles.label }>
					{ label }
				</span>
			) }
			<a className={ styles.link } href={ `${campaign.campaign_url}/login`}>
				{ __( 'Sign in', 'give-peer-to-peer' ) } <LoginIcon />
			</a>
		</div>
	)
}

ProfileLogin.propTypes = {
	label: PropTypes.string
}

export default ProfileLogin
