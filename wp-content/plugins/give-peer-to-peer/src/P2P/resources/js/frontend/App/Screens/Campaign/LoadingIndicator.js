import { Spinner } from '@p2p/Components/Admin';
import styles from './LoadingIndicator.module.scss';

export const LoadingIndicator = () => (
	<div className={ styles.loadingIndicator }>
		<Spinner size="large" />
	</div>
);
