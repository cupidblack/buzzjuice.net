import React from 'react';
import {useHistory} from 'react-router-dom';
import PropTypes from 'prop-types';
import {AddIcon} from '@p2p/Components/Icons';

import styles from './styles.module.scss';

const {__} = wp.i18n;

const CreateButton = ({children, link}) => {
    const history = useHistory();

    return (
        <button
            className={styles.button}
            onClick={(e) => {
                e.preventDefault();
                history.push(link);
            }}
        >
            {children}
            <AddIcon />
        </button>
    );
};

CreateButton.propTypes = {
    // ButtonLink children
    children: PropTypes.node,
    // Fired on button click
    onClick: PropTypes.func,
    //Redirect
    link: PropTypes.string,
};

export default CreateButton;
