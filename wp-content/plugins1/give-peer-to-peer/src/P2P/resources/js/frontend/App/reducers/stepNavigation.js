import { createReducer } from '../store';
import { setStep } from '../actions';

const { __ } = wp.i18n;

const stepNames = {
	findTeam: __( 'Find Team', 'give-peer-to-peer' ),
	register: __( 'Register', 'give-peer-to-peer' ),
	createTeam: __( 'Create Team', 'give-peer-to-peer' ),
	createProfile: __( 'Create Profile', 'give-peer-to-peer' ),
	startFundraising: __( 'Start Fundraising', 'give-peer-to-peer' ),
};

const initialState = {
	currentStep: 1,
	navigationSets: [
		{
			joinTeam: [
				{
					step: 1,
					name: stepNames.findTeam,
				},
				{
					step: 2,
					name: stepNames.register,
				},
				{
					step: 3,
					name: stepNames.createProfile,
				},
				{
					step: 4,
					name: stepNames.startFundraising,
				},
			],
			joinIndividual: [
				{
					step: 1,
					name: stepNames.register,
				},
				{
					step: 2,
					name: stepNames.createProfile,
				},
				{
					step: 3,
					name: stepNames.startFundraising,
				},
			],
			createTeam: [
				{
					step: 1,
					name: stepNames.register,
				},
				{
					step: 2,
					name: stepNames.createTeam,
				},
				{
					step: 3,
					name: stepNames.createProfile,
				},
				{
					step: 4,
					name: stepNames.startFundraising,
				},
			],
		}
	],
};

export const stepNavigationReducer = createReducer( initialState, ( state, action ) => {
	switch ( action.type ) {
		case setStep.type:
			return {
				...state,
				currentStep: action.payload,
			};

		default:
			return state;
	}
} );
