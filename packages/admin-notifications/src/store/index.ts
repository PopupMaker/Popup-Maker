import { createReduxStore, register } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

import type { Notification } from '../types';

export const STORE_NAME = 'popup-maker/admin-notifications';

interface State {
	items: Notification[];
	isLoading: boolean;
	hasLoaded: boolean;
	error: string | null;
}

const DEFAULT_STATE: State = {
	items: [],
	isLoading: false,
	hasLoaded: false,
	error: null,
};

type Action =
	| { type: 'FETCH_START' }
	| { type: 'FETCH_SUCCESS'; items: Notification[] }
	| { type: 'FETCH_ERROR'; error: string }
	| { type: 'DISMISS_LOCAL'; code: string };

const reducer = ( state: State = DEFAULT_STATE, action: Action ): State => {
	switch ( action.type ) {
		case 'FETCH_START':
			return { ...state, isLoading: true, error: null };
		case 'FETCH_SUCCESS':
			return {
				...state,
				items: action.items,
				isLoading: false,
				hasLoaded: true,
			};
		case 'FETCH_ERROR':
			return {
				...state,
				isLoading: false,
				hasLoaded: true,
				error: action.error,
			};
		case 'DISMISS_LOCAL':
			return {
				...state,
				items: state.items.filter(
					( item ) => item.code !== action.code
				),
			};
		default:
			return state;
	}
};

const actions = {
	fetchStart: () => ( { type: 'FETCH_START' } ) as const,
	fetchSuccess: ( items: Notification[] ) =>
		( { type: 'FETCH_SUCCESS', items } ) as const,
	fetchError: ( error: string ) =>
		( { type: 'FETCH_ERROR', error } ) as const,
	dismissLocal: ( code: string ) =>
		( { type: 'DISMISS_LOCAL', code } ) as const,

	fetchNotifications:
		() =>
		async ( { dispatch }: { dispatch: typeof actions } ) => {
			dispatch.fetchStart();
			try {
				const items = ( await apiFetch( {
					path: '/popup-maker/v2/notifications',
				} ) ) as Notification[];
				dispatch.fetchSuccess( Array.isArray( items ) ? items : [] );
			} catch ( err ) {
				const message =
					err instanceof Error ? err.message : 'Unknown error';
				dispatch.fetchError( message );
			}
		},

	dismiss:
		( code: string, action: string = '' ) =>
		async ( { dispatch }: { dispatch: typeof actions } ) => {
			// Optimistic local removal — panel feels instant.
			dispatch.dismissLocal( code );
			try {
				await apiFetch( {
					path: '/popup-maker/v2/notifications/dismiss',
					method: 'POST',
					data: { code, action },
				} );
			} catch ( err ) {
				// eslint-disable-next-line no-console
				console.warn( '[PM] dismiss failed', err );
				/*
				 * Re-sync with the server so the optimistic removal is
				 * reverted if the dismissal didn't persist. Otherwise the
				 * user sees phantom state until the next page load.
				 */
				dispatch.fetchNotifications();
			}
		},
};

const selectors = {
	getNotifications: ( state: State ): Notification[] => state.items,
	getCount: ( state: State ): number => state.items.length,
	isLoading: ( state: State ): boolean => state.isLoading,
	hasLoaded: ( state: State ): boolean => state.hasLoaded,
	getError: ( state: State ): string | null => state.error,
};

/**
 * Shape of the selector surface exposed via `select( STORE_NAME )`.
 *
 * Each raw selector takes `state` as its first arg; `@wordpress/data`
 * binds state for us so consumers call the selector without it. Mirror
 * that here so `useSelect` callers stay type-safe when the store
 * adds/removes selectors.
 */
export type NotificationsSelectors = {
	getNotifications: () => Notification[];
	getCount: () => number;
	isLoading: () => boolean;
	hasLoaded: () => boolean;
	getError: () => string | null;
};

export const store = createReduxStore( STORE_NAME, {
	reducer,
	actions,
	selectors,
} );

export const registerStore = (): void => {
	register( store );
};
