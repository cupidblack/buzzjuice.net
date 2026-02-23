import { createContext, useContext, useReducer } from 'react';

const Context = createContext( null );

export const CreateStore = ( reducers, initialState ) => {
	const reducersState = {};

	for ( let reducer in reducers ) {
		if ( 'state' in reducers[ reducer ] ) {
			reducersState[ reducer ] = reducers[ reducer ].state;
		}
	}

	return useReducer( ( state, action ) => {
		for ( let reducer in reducers ) {
			const newState = reducers[ reducer ].reducer( state[ reducer ], action );
			// Compare new state with old state
			if ( newState !== state[ reducer ] ) {
				return {
					...state,
					[ reducer ]: newState,
				};
			}
		}
		return state;
	}, {
		...initialState,
		...reducersState,
	} );
};

export const useStore = () => useContext( Context );
export const Provider = ( { children, store } ) => <Context.Provider value={ store }>{ children }</Context.Provider>;
export const createReducer = ( state, reducer ) => ( { state, reducer } );
export const createAction = ( type ) => {
	const action = ( payload = null ) => ( { type, payload } );
	action.type = type;
	return action;
};

export const useSelector = ( callback ) => {
	const [ state ] = useStore();
	return callback( state );
};
