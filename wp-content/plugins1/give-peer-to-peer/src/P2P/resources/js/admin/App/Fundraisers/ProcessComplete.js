import {useMemo} from 'react';
import {useHistory, useParams} from 'react-router-dom';
import sanitizeHtml from 'sanitize-html';

import styles from '@p2p/js/admin/App/Teams/styles.module.scss';

import {ArrowIcon, CompletedIcon, NewWindowIcon} from '@p2p/Components/Icons';
import FormContainer from '@p2p/Components/Admin/FormContainer';
import {Card, CardBody, CardFooter} from '@p2p/Components/Card';
import {Button} from '@p2p/Components';
import {ErrorNotice, LoadingNotice} from '@p2p/Components/Admin';

import {getEndpoint, useFetcher} from '@p2p/js/api';
import {goBackToTeamsListPage, setFormNavigationId, setFormNavigationStep} from '@p2p/js/admin/App/Teams/utils';
import SecureAndEncrypted from '@p2p/Components/SecureAndEncrypted';

const {__, sprintf} = wp.i18n;

const ProcessComplete = ({campaign}) => {
    const {id: fundraiserId} = useParams();
    const history = useHistory();

    setFormNavigationId('createFundraiser');
    setFormNavigationStep('3');

    const {
        data: fundraiser,
        isLoading,
        isError,
    } = useFetcher(getEndpoint('/get-fundraiser', {fundraiserId}), {
        revalidateOnFocus: false,
        onError: () => {
            setTimeout(() => {
                goBackToTeamsListPage(history);
            }, 3000);
        },
    });

    const sanitizedHTML = useMemo(
        () =>
            sanitizeHtml(
                __(
                    sprintf(
                        `You successfully added a new fundraiser to the <strong>%s</strong> campaign`,
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

    if (isLoading) {
        return <LoadingNotice notice={__('Loading team', 'give-peer-to-peer')} />;
    }

    if (isError) {
        return (
            <ErrorNotice
                reload={false}
                notice={__('Unable to fetch the team. Check the logs for details.', 'give-peer-to-peer')}
            />
        );
    }

    return (
        <FormContainer
            title={__('Create a new fundraiser', 'give-peer-to-peer')}
            teamImage={campaign.campaign_image}
            showStepNavigation={true}
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
                                history.push(`/create-wp-user`);
                            }}
                            iconAfter={ArrowIcon}
                        >
                            {__('Create another fundraiser', 'give-peer-to-peer')}
                        </Button>
                        <a
                            className={styles.details}
                            href={`${campaign.base_url}/fundraiser/${fundraiserId}`}
                            target="_blank"
                        >
                            {__('Go to fundraiser details', 'give-peer-to-peer')}
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
