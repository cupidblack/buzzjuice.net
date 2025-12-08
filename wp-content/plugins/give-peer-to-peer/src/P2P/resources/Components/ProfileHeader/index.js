import { useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { useStore } from '@p2p/js/frontend/App/store';
import { ArrowIcon, InfoIcon, GroupIcon } from '@p2p/Components/Icons';
import { Button } from '../Button';
import PlaceholderAvatar from '../PlaceholderAvatar';

import styles from './styles.module.scss';

const { __ } = wp.i18n;

const ProfileHeader = ( { name, avatar, campaign, SecondaryButton, children } ) => {
	const [{ fundraiser }] = useStore();

	return (
		<div className={styles.container}>
			<div className={styles.profileInfo}>
				<img className={styles.avatar} src={avatar || PlaceholderAvatar} alt={name} />

				<h1 className={styles.header}>{name}</h1>

				<div className={styles.pillWrap}>
					{fundraiser && fundraiser.team_id && fundraiser.team && (
						<p className={styles.fundraiserpill}>
							<GroupIcon height={24} width={24} />
							<span>
							{__( 'Member of', 'give-peer-to-peer' )}
								&nbsp;
								<a href={campaign.campaign_url + '/team/' + fundraiser.team_id} className={styles.campaignlink}>
								{fundraiser.team}
							</a>
						</span>
						</p>
					)}

					<p className={styles.fundraiserpill}>
						<InfoIcon height={24} width={24}/>
						<span>
						{__( 'Fundraising in support of the', 'give-peer-to-peer' )}
							&nbsp;
							<a href={campaign.campaign_url} className={styles.campaignlink}>
							{campaign.campaign_title}
						</a>
						</span>
					</p>

				</div>

				<div className={styles.buttongroup}>
					<Button as={Link} to="donate" style={{ minWidth: '200px' }}>
						{__( 'Donate Now', 'give-peer-to-peer' )} <ArrowIcon width={24} height={24} />
					</Button>
					{SecondaryButton}
				</div>

			</div>

			{children}

		</div>
	);
};

ProfileHeader.SubTitle = ( { title } ) => {
	return (
		<h2 className={styles.subtitle}>
			{title}
		</h2>
	);
};

class StoryContentModel {
	constructor( storyText ) {
		this.text = storyText.split('<!--more-->');
	}

	hasMore = () => this.text.length > 1;
	getExcerpt = () => this.text[0];
	getContent = () => this.text.join(' ');
}

ProfileHeader.Story = ( { story } ) => {
	const [ isReadMore, toggleReadMore ] = useState();
	const text = useMemo( () => new StoryContentModel( story ), [ story ] );

	return (
		<div className={ styles.story }>
			<div
				className={ styles.storyContainer }
				dangerouslySetInnerHTML={ { __html: isReadMore ? text.getContent() : text.getExcerpt() } }
			/>
			{ ! isReadMore && text.hasMore() && (
				<button onClick={toggleReadMore} className={styles.storyButton } >
					{ __( 'Read More', 'give-peer-to-peer' ) }
					<ArrowIcon height={ 18 } width={ 24 } />
				</button>
			) }
		</div>
	);
};

export default ProfileHeader;
