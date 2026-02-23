import {Link} from 'react-router-dom';
import styles from './styles.module.scss';
import {ArrowIcon} from '@p2p/Components/Icons';
import {Button} from '@p2p/Components/Button';
import PlaceholderAvatar from '@p2p/Components/PlaceholderAvatar';

const ProfileCard = ({children}) => {
    return <div className={styles.profileCard}>{children}</div>;
};

const ProfileCardLink = ({children, to}) => {
    return (
        <Link to={to}>
            <div className={styles.containerLink}>
                <div className={styles.column}>{children}</div>
                <div className={styles.column}>
                    <ArrowIcon width={34} />
                </div>
            </div>
        </Link>
    );
};

const ProfileCardStatus = ({text, color}) => {
    return (
        <div className={styles.status}>
            <span>
                <svg width="8" height="8" viewBox="0 0 8 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="4" cy="4" r="4" fill={color} />
                </svg>
            </span>
            <span>{text}</span>
        </div>
    );
};

ProfileCard.Body = ({children}) => {
    return <div className={styles.content}>{children}</div>;
};

ProfileCard.Image = ({src, alt, ...rest}) => {
    return <img src={src || PlaceholderAvatar} alt={alt} className={styles.image} {...rest} />;
};

ProfileCard.Icon = ({children}) => {
    return <div className={styles.icon}>{children}</div>;
};

ProfileCard.Title = ({children}) => {
    return <div className={styles.title}>{children}</div>;
};

ProfileCard.Text = ({children}) => {
    return <div className={styles.text}>{children}</div>;
};

ProfileCard.Button = ({children, ...rest}) => {
    return (
        <Button size={Button.sizes.tiny} iconAfter={ArrowIcon} {...rest}>
            {children}
        </Button>
    );
};

ProfileCard.Container = ({children}) => {
    return <div className={styles.container}>{children}</div>;
};

export {ProfileCard, ProfileCardLink, ProfileCardStatus};
