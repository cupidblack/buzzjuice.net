import classNames from 'classnames';
import { useStore } from '@p2p/js/frontend/App/store';

import styles from './styles.module.scss';

const StepNavigation = () => {
	const [ { navigation } ] = useStore();
	const currentNavigationSet = sessionStorage.getItem( 'p2p-navigation-set' );

	const getCurrentNavigationSteps = () => {
		for ( const [ key, set ] of Object.entries( ...navigation.navigationSets ) ) {
			if ( key === currentNavigationSet ) {
				return set;
			}
		}
		return [];
	};

	return (
		<div role="group" aria-label="Progress">
 			<ol className={ styles.container }>
				{ getCurrentNavigationSteps().map( ( { name, step }, i ) => (
					<li
						key={ currentNavigationSet + i }
						className={ styles.step }
						aria-current={ navigation.currentStep === step ? "step" : null }
					>
						<div className={ classNames( styles.stepIndicator, { [ styles.active ]: navigation.currentStep === step } ) }>
							{ step }
						</div>
						<div className={ styles.stepTitle }>
							{ name }
						</div>
						<div className={ styles.separator }></div>
					</li>
				) ) }
			</ol>
		</div>
	);
};

export default StepNavigation;
