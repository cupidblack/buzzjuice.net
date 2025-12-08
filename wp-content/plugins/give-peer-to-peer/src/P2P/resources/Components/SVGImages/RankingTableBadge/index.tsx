// @ts-ignore
import icon from './RankingTableBadge.svg';

import cx from 'classnames';
import styles from './style.module.scss';

const RankingTableBadge = ({rank}) => {
    const firstPlace = rank === 0;
    const secondPlace = rank === 1;
    const thirdPlace = rank === 2;
    return (
        <img
            className={cx(styles.image, {
                [styles.rankFirst]: firstPlace,
                [styles.rankSecond]: secondPlace,
                [styles.rankThird]: thirdPlace,
            })}
            src={icon}
            alt={'Top 3 Ranked Badge'}
        />
    );
};

export default RankingTableBadge;
