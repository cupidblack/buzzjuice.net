import React, {useMemo} from 'react';

import cx from 'classnames';
import sanitizeHtml from 'sanitize-html';

import PlaceholderAvatar from '@p2p/Components/PlaceholderAvatar';
import {ArrowIcon} from '@p2p/Components/Icons';
import {__} from '@wordpress/i18n';
import ProgressBar from '@p2p/Components/ProgressBar';
import {Button} from '@p2p/Components';
import MemberIcon from '@p2p/Components/SVGImages/MembersIcon';
import TeamCaptainIcon from '@p2p/Components/SVGImages/TeamCaptainIcon';

import styles from '@p2p/Components/RankingCard/style.module.scss';
import campaign from '@p2p/js/frontend/App/Screens/Campaign/Campaign';

export interface RankingCardProps {
    link: string;
    viewLabel: string;
    badge: JSX.Element | null | undefined;
    name: string;
    amount: number;

    id?;
    columns?: string;
    sanitizedHTML?;
    fundraiserTotal?: string;
    fundraisersTotal?: string;
    teamTotal?: string;
    teamsTotal?: string;
    teamCaptain?: string;
    goal?: number;
    isCampaignBoard?: boolean;

    //Attributes
    profileImage: string;
    showAvatar: boolean;
    showGoal: boolean;
    showDescription?: boolean;
    showTeamInfo?: boolean;
    showCampaignDetails?: boolean;
    story?: string;
}

/**
 *
 *
 * @since 1.6.0
 */
const RankingCard = ({
    id,
    columns,
    viewLabel,
    badge,
    link,
    showAvatar,
    showGoal,
    showDescription,
    showTeamInfo,
    showCampaignDetails,
    name,
    profileImage,
    amount,
    goal,
    story,
    teamCaptain,
    fundraiserTotal,
    fundraisersTotal,
    teamTotal,
    teamsTotal,
    isCampaignBoard,
}: RankingCardProps) => {
    const isFullWidthCard = columns === 'fullWidth';

    const sanitizedStory = useMemo(() => {
        return sanitizeHtml(story, {allowedTags: []});
    }, [story]);

    return (
        <div
            className={cx(styles.rankingCard, {
                [styles.rankingCardFullWidth]: isFullWidthCard,
            })}
            part={'give-card'}
        >
            <div
                className={cx(styles.profile, {
                    [styles.hideElement]: isFullWidthCard && !showAvatar,
                    [styles.profileCampaignFullWidth]: isCampaignBoard,
                })}
            >
                {showAvatar && (
                    <img
                        className={cx(styles.avatar, {
                            [styles.campaignAvatar]: isCampaignBoard,
                        })}
                        part={'give-card__avatar'}
                        src={profileImage || PlaceholderAvatar}
                        alt="profile image"
                    />
                )}

                <div className={styles.nameAndBadge}>
                    {!isCampaignBoard && (
                        <>
                            {badge}
                            <h1 className={styles.name} part={'give-card__name'}>
                                {name}
                            </h1>
                        </>
                    )}

                    {showCampaignDetails && (
                        <div
                            className={cx(styles.additionalInfo, {
                                [styles.campaignListInfo]: isCampaignBoard,
                            })}
                        >
                            <h1 className={styles.name} part={'give-card__name'}>
                                {name}
                            </h1>

                            <div>
                                <span>
                                    <TeamCaptainIcon />
                                    <span> {__('Teams', 'give-peer-to-peer')}</span>
                                </span>
                                <span>{teamsTotal}</span>
                            </div>
                            <div>
                                <span>
                                    <MemberIcon />
                                    {__('Fundraisers', 'give-peer-to-peer')}
                                </span>
                                <span>{fundraisersTotal}</span>
                            </div>
                        </div>
                    )}
                </div>
            </div>

            <div className={styles.container}>
                <span className={styles.nameAndBadgeFullWidth}>
                    <h1 className={styles.name} part={'give-card__name'}>
                        {name}
                    </h1>
                    {badge}
                </span>
                <div className={styles.contentWrapper}>
                    {showGoal && (
                        <div
                            className={cx({
                                [styles.hideElement]: (isCampaignBoard || id === 'campaign') && isFullWidthCard,
                            })}
                        >
                            <ProgressBar sanitizedDetails amount={amount} goal={goal} />
                        </div>
                    )}

                    {showDescription && (
                        <span className={styles.story} part={'give-card__story'}>
                            <p
                                dangerouslySetInnerHTML={{
                                    __html: sanitizedStory,
                                }}
                            />
                        </span>
                    )}

                    {showGoal && isFullWidthCard && (id === 'campaign' || isCampaignBoard) && (
                        <div className={cx({[styles.hiddenOnColumns]: isCampaignBoard})}>
                            <ProgressBar sanitizedDetails amount={amount} goal={goal} />
                        </div>
                    )}

                    {showTeamInfo && (
                        <div className={styles.additionalInfo}>
                            <div>
                                <span>
                                    <TeamCaptainIcon />
                                    <span> {__('Team Captain', 'give-peer-to-peer')}</span>
                                </span>
                                <span>{teamCaptain ?? __('Not Available', 'give-peer-to-peer')}</span>
                            </div>
                            <div>
                                <span>
                                    <MemberIcon />
                                    <span> {__('Members', 'give-peer-to-peer')}</span>
                                </span>
                                <span>{fundraiserTotal}</span>
                            </div>
                        </div>
                    )}

                    {showCampaignDetails && (
                        <div
                            className={cx(styles.additionalInfo, {
                                [styles.additionalInfoCampaignGrid]: !isFullWidthCard,
                            })}
                        >
                            <div>
                                <span>
                                    <TeamCaptainIcon />
                                    <span> {__('Teams', 'give-peer-to-peer')}</span>
                                </span>
                                <span>{teamTotal ?? teamsTotal}</span>
                            </div>
                            <div>
                                <span>
                                    <MemberIcon />
                                    <span> {__('Fundraisers', 'give-peer-to-peer')}</span>
                                </span>
                                <span>{fundraiserTotal ?? fundraisersTotal}</span>
                            </div>
                        </div>
                    )}
                </div>
                <Button as="a" size={Button.sizes.tiny} href={link} iconAfter={ArrowIcon}>
                    {viewLabel}
                </Button>
            </div>
        </div>
    );
};

export default RankingCard;
