import { useEffect, useState } from 'react';
import { useSelector } from '@p2p/js/frontend/App/store';
import { useParams } from 'react-router-dom';
import API from '@p2p/js/api';

// Components
import { Button, Page } from '@p2p/Components';
import FormContainer from '@p2p/Components/FormContainer';
import { Card, CardBody, StripedGroup } from '@p2p/Components/Card';
import TeamProfileCard from '@p2p/Components/TeamProfileCard';
import Spinner from '@p2p/Components/Admin/Spinner';

import styles from './styles.module.scss';

const { __, sprintf } = wp.i18n;

const JoinTeamFundraiser = () => {
	const { team_id } = useParams();
	const campaign = useSelector( state => state.campaign );
	const [ state, setState ] = useState( {
		checkingUserPermissions: true,
		joining: false,
		error: null,
	} );

	useEffect( () => {
		API.post( '/fundraiser-can-join-team', { campaignId: campaign.campaign_id } ).then( ( { data } ) => {
			setState( previousState => {
				return {
					...previousState,
					checkingUserPermissions: false,
				}
			} );
		} ).catch( () => {
			location.replace( campaign.campaign_url + '/start-fundraising/' );
		} );
	}, [] );

	const handleJoin = () => {
		setState( previousState =>{
			return {
				...previousState,
				joining: true,
			}
		} );

		const data = {
			teamId: team_id,
			campaignId: campaign.campaign_id,
		};

		return API.post( '/fundraiser-join-team', data ).then( ( { data } ) => {
			location.href = campaign.campaign_url + data.redirect;
		} ).catch( ( error ) => {
			const { message } = error.response.data;

			setState( {
				joining: false,
				error: message,
			} );
		} );
	};

	return (
		<Page title={sprintf(__('Start fundraising for the %s', 'give-peer-to-peer'), campaign.campaign_title)}>
			<FormContainer
				title={ [
					__( 'Start fundraising for the', 'give-peer-to-peer' ),
					<br/>,
					campaign.campaign_title,
				] }
				image={ campaign.campaign_image }
			>
				{ state.checkingUserPermissions ? (
					<Spinner size="large"/>
				) : (
					<Card title={ __( 'Join Team', 'give-peer-to-peer' ) }>

						<StripedGroup>
							<CardBody>
								<TeamProfileCard teamId={ team_id } campaignUrl={ campaign.campaign_url }/>
							</CardBody>

							<CardBody style={ { padding: '30px' } }>
								<Button disabled={ state.joining } onClick={ handleJoin }>
									{ state.joining ? (
										<Spinner size="tiny"/>
									) : (
										<>
											{ __( 'Join Team', 'give-peer-to-peer' ) }
										</>
									) }
								</Button>

								{ state.error && (
									<div className={ styles.errorMessage }>
										{ state.error }
									</div>
								) }
							</CardBody>
						</StripedGroup>
					</Card>
				) }
			</FormContainer>
		</Page>
	);

};

export default JoinTeamFundraiser;


