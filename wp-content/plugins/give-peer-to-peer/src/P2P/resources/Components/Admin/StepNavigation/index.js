import classNames from 'classnames';
import {getFormNavigationSteps} from './stepNavigation'

import styles from '../../StepNavigation/styles.module.scss';

const StepNavigation = () => {
    const currentNavigationSet = sessionStorage.getItem('p2p-admin-form-navigation-set');
    const currentStep = sessionStorage.getItem('p2p-admin-form-progress-step');

    const currentNavigationSteps = getFormNavigationSteps(currentNavigationSet);

    return (
        <div role="group" aria-label="Progress">
            <ol className={styles.container}>
                {currentNavigationSteps.map(({name, step}) => (
                    <li
                        key={step}
                        className={styles.step}
                        aria-current={currentStep === step.toString() ? "step" : null}
                    >
                        <div
                            className={classNames(styles.stepIndicator, {[styles.active]: currentStep === step.toString()})}>
                            {step}
                        </div>
                        <div className={styles.stepTitle}>
                            {name}
                        </div>
                        <div className={styles.separator}></div>
                    </li>
                ))
                }
            </ol>
        </div>
    );
};

export default StepNavigation;
