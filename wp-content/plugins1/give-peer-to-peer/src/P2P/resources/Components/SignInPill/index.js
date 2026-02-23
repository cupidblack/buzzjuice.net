import styles from './style.module.scss';
import { Link } from 'react-router-dom';
import { useStore } from '@p2p/js/frontend/App/store';

const { __ } = wp.i18n;

const SignInPill = () => {

	const [ { auth } ] = useStore();

	return (
		<div className={ styles.signinpillcontainer }>
			{ ! auth.user_id && (
				<div className={ styles.signinpill }>
					<span>{__('Already have an account?', 'give-peer-to-peer')}</span>
					<Link to="/login/">
						{__( 'Sign in', 'give-peer-to-peer' )}
					</Link>
				</div>
			)}
		</div>
	);
};

export default SignInPill;
