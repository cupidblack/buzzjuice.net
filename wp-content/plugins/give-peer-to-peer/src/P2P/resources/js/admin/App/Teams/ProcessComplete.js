import {useMemo} from 'react';
import {useHistory, useParams} from 'react-router-dom';
import sanitizeHtml from 'sanitize-html';

import styles from '@p2p/js/admin/App/Teams/styles.module.scss';

import {Card, CardBody, CardFooter} from '@p2p/Components/Card';
import {getEndpoint, useFetcher} from '@p2p/js/api';
import {ErrorNotice, LoadingNotice} from '@p2p/Components/Admin';
import {ArrowIcon, CompletedIcon, NewWindowIcon} from '@p2p/Components/Icons';
import FormContainer from '@p2p/Components/Admin/FormContainer';
import {Button} from '@p2p/Components';

import {setFormNavigationIdAndProcessStep} from '@p2p/js/admin/App/Teams/utils';
import SecureAndEncrypted from '@p2p/Components/SecureAndEncrypted';

const {__, sprintf} = wp.i18n;

const ProcessComplete = ({campaign}) => {
    const history = useHistory();
    const {id: team_id} = useParams();

    setFormNavigationIdAndProcessStep('4');

    const {
        data: team,
        isLoading: isTeamLoading,
        isError: isTeamRequestError,
    } = useFetcher(getEndpoint('/get-team', {team_id}), {
        revalidateOnFocus: false,
        onError: () => {
            setTimeout(() => {
                alert('error');
            }, 3000);
        },
    });

    const sanitizedHTML = useMemo(
        () =>
            sanitizeHtml(
                __(
                    sprintf(
                        `You successfully added a new team to the <strong>%s</strong> campaign`,
                        campaign.campaign_title
                    ),
                    'give-peer-to-peer'
                ),
                {
                    allowedTags: ['strong'],
                }
            ),
        [campaign.campaign_title]
    );

    if (isTeamLoading) {
        return <LoadingNotice notice={__('Loading team', 'give-peer-to-peer')} />;
    }

    if (isTeamRequestError) {
        return (
            <ErrorNotice
                reload={false}
                notice={__('Unable to fetch the team. Check the logs for details.', 'give-peer-to-peer')}
            />
        );
    }

    return (
        <FormContainer
            title={sprintf(__('Creating a new team', 'give-peer-to-peer'))}
            teamImage={campaign.campaign_image}
            showStepNavigation
        >
            <Card title={__('Setup Complete', 'give-peer-to-peer')}>
                <CardBody style={{margin: 0, padding: '70px 0px'}}>
                    <div className={styles.svgContainer}>
                        <CompletedIcon color={campaign.primary_color} />
                    </div>
                    <div className={styles.setupCompleteWrapper}>
                        <p
                            dangerouslySetInnerHTML={{
                                __html: sanitizedHTML,
                            }}
                        />
                        <Button
                            onClick={(e) => {
                                e.preventDefault();
                                history.push(`/create-team`);
                            }}
                            iconAfter={ArrowIcon}
                        >
                            {__('Create another team', 'give-peer-to-peer')}
                        </Button>
                        <a className={styles.details} href={`${campaign.base_url}/team/${team.id}`} target="_blank">
                            {__('Go to team details', 'give-peer-to-peer')}
                            <span style={{marginLeft: 6}}>
                                <NewWindowIcon color={'#6b6b6b'} />
                            </span>
                        </a>
                    </div>
                </CardBody>

                <CardFooter>
                    <SecureAndEncrypted />
                </CardFooter>
            </Card>
        </FormContainer>
    );
};

export default ProcessComplete;
