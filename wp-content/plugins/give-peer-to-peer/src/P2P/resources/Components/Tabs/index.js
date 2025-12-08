import {useState} from 'react';
import PropTypes from 'prop-types';
import cx from 'classnames';

import styles from './style.module.scss';

export const Tabs = ({tabs}) => {
    const [currentTabIndex, setCurrentTabIndex] = useState(0);

    return (
        <div className={styles.tabs} part="give-tabs">
            <nav>
                <ul part="give-tabs__tab-list">
                    {tabs.map(({label}, index) => (
                        <li
                            key={index}
                            part={cx('give-tabs__tab', currentTabIndex === index && 'give-tabs__tab--active')}
                            className={cx(currentTabIndex === index && styles.tabactive)}
                            onClick={() => setCurrentTabIndex(index)}
                        >
                            {label}
                        </li>
                    ))}
                </ul>
            </nav>
            <div className={styles.tabPanel} part="give-tabs__tab-panel">
                {tabs[currentTabIndex].content}
            </div>
        </div>
    );
};
Tabs.propTypes = {
    tabs: PropTypes.array,
};
