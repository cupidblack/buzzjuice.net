import cx from 'classnames';
import Stats from '../Stats';
import styles from './styles.module.scss';

/**
 * The stats container used for the Team and Fundraiser pages. If you plan on
 * using this for more than those views and you use Stats with less or more than
 * 3 items, you will need to find a new solution for adding margin and placing
 * the Stats component in the CSS.
 */
const StatsContainer = ( { coverImage, children } ) => {
	return (
		<div className={ styles.coverImage } style={ { backgroundImage: `url(${coverImage})` } }>
			<div className={ styles.tintOverlay }></div>
			<div className={ styles.content }>
				{ children }
			</div>
		</div>
	);
};

StatsContainer.Stats = ({className, ...rest}) => <Stats className={cx(className, styles.stats)} {...rest} />;
StatsContainer.Stats.propTypes = Stats.propTypes;

StatsContainer.Content = ( { children } ) => {
	return (
		<div className={ styles.info }>
			{ children }
		</div>
	);
};

StatsContainer.InfoText = ( { children } ) => {
	return (
		<div className={ styles.infoText }>
			<strong>
				{ children }
			</strong>
		</div>
	);
};

StatsContainer.ShareButtons = ( { children } ) => {
	return (
		<div className={ styles.shareButtons }>
			{ children }
		</div>
	);
};

export default StatsContainer;
