import {useStore} from '@p2p/js/frontend/App/store';
import {getAmountInCurrency} from '@p2p/js/utils';
import {getEndpoint, useFetcher} from '@p2p/js/api';
// Components
import {Page} from '@p2p/Components';
import FormContainer from '@p2p/Components/FormContainer';
import {Card, CardBody, CardFooter} from '@p2p/Components/Card';
import {ProfileCard, ProfileCardLink, ProfileCardStatus} from '@p2p/Components/ProfileCard';
import Spinner from '@p2p/Components/Admin/Spinner';
import {CreateATeamIcon, JoinATeamIcon} from '@p2p/Components/Icons';
import {canFundraiserRegisterTeam, isCampaignHasTeams} from '@p2p/js/frontend/App/utils';
import styles from './styles.module.scss';
import SecureAndEncrypted from '@p2p/Components/SecureAndEncrypted';
import PlaceholderTeamAvatar from '@p2p/Components/PlaceholderTeamAvatar';

const {__, _n, sprintf} = wp.i18n;

const StartFundraising = () => {
    const [{campaign, campaignStats}] = useStore();

    const {data, isLoading, isError} = useFetcher(
        getEndpoint('/get-fundraiser-info', {campaignId: campaign.campaign_id})
    );

    if (isError) {
        location.replace(campaign.campaign_url);
    }

    return (
        <Page title={sprintf(__('Start fundraising for the %s', 'give-peer-to-peer'), campaign.campaign_title)}>
            <FormContainer
                title={[__('Start fundraising for the', 'give-peer-to-peer'), <br />, campaign.campaign_title]}
                image={campaign.campaign_image}
            >
                {isLoading || isError ? (
                    <Spinner size="large" />
                ) : (
                    <Card
                        title={
                            data.fundraiser.is_approved
                                ? __("You're ready to begin fundraising!", 'give-peer-to-peer')
                                : __("You're almost ready to begin fundraising!", 'give-peer-to-peer')
                        }
                    >
                        <CardBody>
                            {data.fundraiser.is_approved ? (
                                <p className={styles.notice}>
                                    {' '}
                                    {__("You're ready to start fundraising!", 'give-peer-to-peer')}{' '}
                                </p>
                            ) : (
                                <p className={styles.notice}>
                                    {' '}
                                    {__(
                                        'We’ve notified a campaign administrator, and you will receive an email when your request has been approved.',
                                        'give-peer-to-peer'
                                    )}{' '}
                                </p>
                            )}
                            <ProfileCard>
                                <ProfileCard.Image
                                    src={data.fundraiser.profile_image}
                                    alt={data.fundraiser.fundraiser_name}
                                />
                                <ProfileCard.Body>
                                    <ProfileCard.Container>
                                        <ProfileCard.Title>{data.fundraiser.fundraiser_name}</ProfileCard.Title>

                                        {/* Show raised amount value only for approved fundraiser. */}
                                        {data.fundraiser.is_approved && (
                                            <span>
                                                {getAmountInCurrency(data.fundraiser.amount_raised)}
                                                {` `} {__('raised', 'give-peer-to-peer')}
                                            </span>
                                        )}
                                        <div>
                                            {data.fundraiser.is_approved ? (
                                                <ProfileCard.Button
                                                    as="a"
                                                    href={`${campaign.campaign_url}/fundraiser/${data.fundraiser.id}/`}
                                                >
                                                    {__('View Profile', 'give-peer-to-peer')}
                                                </ProfileCard.Button>
                                            ) : (
                                                <ProfileCard.Button
                                                    as="a"
                                                    href={`${campaign.campaign_url}/fundraiser/${data.fundraiser.id}/update/`}
                                                >
                                                    {__('Edit Profile', 'give-peer-to-peer')}
                                                </ProfileCard.Button>
                                            )}
                                        </div>
                                    </ProfileCard.Container>

                                    {data.fundraiser.is_approved ? (
                                        <ProfileCardStatus
                                            text={__('Approved', 'give-peer-to-peer')}
                                            color={'#28C77B'}
                                        />
                                    ) : (
                                        <ProfileCardStatus
                                            text={__('Pending', 'give-peer-to-peer')}
                                            color={'#F2994A'}
                                        />
                                    )}
                                </ProfileCard.Body>
                            </ProfileCard>

                            {data.team ? (
                                <ProfileCard>
                                    <ProfileCard.Image
                                        src={data.team.profile_image || PlaceholderTeamAvatar}
                                        alt={data.team.name}
                                    />

                                    <ProfileCard.Body>
                                        <ProfileCard.Container>
                                            <ProfileCard.Title>{data.team.name}</ProfileCard.Title>
                                            <div>
                                                {sprintf(
                                                    _n(
                                                        '%d Member',
                                                        '%d Members',
                                                        data.team.members,
                                                        'give-peer-to-peer'
                                                    ),
                                                    data.team.members
                                                )}
                                            </div>
                                            <div>
                                                {data.team.is_approved ? (
                                                    <ProfileCard.Button
                                                        as="a"
                                                        href={`${campaign.campaign_url}/team/${data.team.id}/`}
                                                    >
                                                        {__('View Team', 'give-peer-to-peer')}
                                                    </ProfileCard.Button>
                                                ) : (
                                                    <ProfileCard.Button
                                                        as="a"
                                                        href={`${campaign.campaign_url}/team/${data.team.id}/update/`}
                                                    >
                                                        {__('Edit Team', 'give-peer-to-peer')}
                                                    </ProfileCard.Button>
                                                )}
                                            </div>
                                        </ProfileCard.Container>
                                    </ProfileCard.Body>

                                    {data.team.is_approved ? (
                                        <ProfileCardStatus
                                            text={__('Approved', 'give-peer-to-peer')}
                                            color={'#28C77B'}
                                        />
                                    ) : (
                                        <ProfileCardStatus
                                            text={__('Pending', 'give-peer-to-peer')}
                                            color={'#F2994A'}
                                        />
                                    )}
                                </ProfileCard>
                            ) : (
                                <>
                                    {isCampaignHasTeams(campaignStats) && (
                                        <ProfileCardLink to="/select-team/">
                                            <ProfileCard.Icon>
                                                <JoinATeamIcon />
                                            </ProfileCard.Icon>

                                            <ProfileCard.Body>
                                                <ProfileCard.Title>
                                                    {__('Join a team', 'give-peer-to-peer')}
                                                </ProfileCard.Title>
                                                <ProfileCard.Text>
                                                    {__(
                                                        'Have friends already fundraising? Help them reach their goals!',
                                                        'give-peer-to-peer'
                                                    )}
                                                </ProfileCard.Text>
                                            </ProfileCard.Body>
                                        </ProfileCardLink>
                                    )}
                                    {canFundraiserRegisterTeam(campaign) && (
                                        <ProfileCardLink to="/create-team/">
                                            <ProfileCard.Icon>
                                                <CreateATeamIcon />
                                            </ProfileCard.Icon>

                                            <ProfileCard.Body>
                                                <ProfileCard.Title>
                                                    {__('Create a team', 'give-peer-to-peer')}
                                                </ProfileCard.Title>
                                                <ProfileCard.Text>
                                                    {__('Start a team and be the team captain!', 'give-peer-to-peer')}
                                                </ProfileCard.Text>
                                            </ProfileCard.Body>
                                        </ProfileCardLink>
                                    )}
                                </>
                            )}
                        </CardBody>

                        <CardBody style={{padding: '45px 0'}}>
                            <a href={campaign.campaign_url} className={styles.campaignPageLink}>
                                {__('Go to campaign page', 'give-peer-to-peer')}
                            </a>
                        </CardBody>

                        <CardFooter>
                            <SecureAndEncrypted />
                        </CardFooter>
                    </Card>
                )}
            </FormContainer>
        </Page>
    );
};

export default StartFundraising;
