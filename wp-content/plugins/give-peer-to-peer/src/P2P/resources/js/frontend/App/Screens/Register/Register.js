import {Link} from 'react-router-dom';
import {useStore} from '@p2p/js/frontend/App/store';
// Components
import FormContainer from '@p2p/Components/FormContainer';
import {Button, Page} from '@p2p/Components';
import {Card, CardBody, CardFooter} from '@p2p/Components/Card';
import {BlockLink, BlockLinkGroup} from '@p2p/Components/BlockLink';
import {ArrowIcon, CreateATeamIcon, JoinAsIndividual, JoinATeamIcon} from '@p2p/Components/Icons';
import {SponsorGrid} from '@p2p/Components/SponsorGrid';
import {canFundraiserRegisterTeam, isCampaignHasTeams} from '@p2p/js/frontend/App/utils';
import SecureAndEncrypted from '@p2p/Components/SecureAndEncrypted';
import SignInPill from '@p2p/Components/SignInPill';

const {__, sprintf} = wp.i18n;

const Register = () => {
    const [{campaign, campaignStats, auth}] = useStore();
    const isRegistered = auth.user_id && !auth.fundraiser_id;

    return (
        <Page title={sprintf(__('Start fundraising for %s', 'give-peer-to-peer'), campaign.campaign_title)}>
            <FormContainer
                title={[__('Start fundraising for ', 'give-peer-to-peer'), <br />, campaign.campaign_title]}
                image={campaign.campaign_image}
            >
                <Card title={__('Start Fundraising', 'give-peer-to-peer')}>
                    <BlockLinkGroup>
                        {isCampaignHasTeams(campaignStats) && (
                            <Link
                                to="/register/teams/"
                                onClick={() => {
                                    sessionStorage.setItem('registerFormChoice', '/register/teams/');
                                }}
                            >
                                <BlockLink
                                    icon={<JoinATeamIcon />}
                                    title={__('Join a team', 'give-peer-to-peer')}
                                    description={__(
                                        'Have friends already fundraising? Help them reach their goals!',
                                        'give-peer-to-peer'
                                    )}
                                />
                            </Link>
                        )}

                        <Link
                            to="/register/individual/"
                            onClick={() => {
                                sessionStorage.setItem('registerFormChoice', '/register/individual/');
                            }}
                        >
                            <BlockLink
                                icon={<JoinAsIndividual />}
                                title={__('Join as an Individual', 'give-peer-to-peer')}
                                description={__(
                                    'Never underestimate the power of one. Start fundraising now.',
                                    'give-peer-to-peer'
                                )}
                            />
                        </Link>

                        {canFundraiserRegisterTeam(campaign) && (
                            <Link
                                to="/register/captain/"
                                onClick={() => {
                                    sessionStorage.setItem('registerFormChoice', '/register/captain/');
                                }}
                            >
                                <BlockLink
                                    icon={<CreateATeamIcon />}
                                    title={__('Create a Team', 'give-peer-to-peer')}
                                    description={__('Start a team and be the team captain!', 'give-peer-to-peer')}
                                />
                            </Link>
                        )}
                    </BlockLinkGroup>

                    <CardBody>
                        <p>{__('Want to simply make a donation?', 'give-peer-to-peer')}</p>
                        <Button
                            as={Link}
                            to="/donate/"
                            iconAfter={ArrowIcon}
                        >
                            {__('Donate Now', 'give-peer-to-peer')}
                        </Button>
                    </CardBody>

                    <CardBody style={{marginTop: 40}}>
                        <SignInPill />
                    </CardBody>

                    <CardFooter>
                        <SecureAndEncrypted />
                    </CardFooter>
                </Card>
            </FormContainer>
            {'enabled' === campaign.sponsors_enabled && <SponsorGrid />}
        </Page>
    );
};

export default Register;
