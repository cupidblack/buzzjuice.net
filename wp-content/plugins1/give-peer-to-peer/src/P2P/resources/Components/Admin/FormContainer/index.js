import {Fragment} from 'react';
import PropTypes from 'prop-types';
import styles from '../../FormContainer/FormContainer.module.scss';
import StepNavigation from '@p2p/Components/Admin/StepNavigation';

const FormContainer = ({children, title, teamImage, showStepNavigation = false}) => {
    const getTitle = () => {
        if (Array.isArray(title)) {
            return title.map((item) => <Fragment key={item}>{item}</Fragment>);
        }

        return title;
    };

    return (
        <div className={styles.container}>
            <div className={styles.header}>
                <h1 className={styles.title}>{getTitle()}</h1>
            </div>
            {showStepNavigation && <StepNavigation/>}
            <div className={styles.content} style={{backgroundImage: `url(${teamImage})`}}>
                <div className={styles.inner}>{children}</div>
                <div className={styles.tintOverlay}/>
            </div>
        </div>
    );
};

FormContainer.propTypes = {
    children: PropTypes.node,
    teamName: PropTypes.string,
    teamImage: PropTypes.string,
    showStepNavigation: PropTypes.bool,
};

export default FormContainer;
