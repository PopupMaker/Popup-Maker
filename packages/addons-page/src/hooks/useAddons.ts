import { useCallback, useEffect, useState } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { sprintf, __ } from '@popup-maker/i18n';

import type { Addon, AddonAction, AddonsPageConfig } from '../types';

export interface AddonsNotice {
	type: 'success' | 'error';
	title?: string;
	message: string;
	details?: string;
	actionFailure?: boolean;
}

export interface UseAddonsResult {
	addons: Addon[];
	loading: boolean;
	loadError: string | null;
	busySlugs: Record< string, boolean >;
	notice: AddonsNotice | null;
	clearNotice: () => void;
	runAction: ( addon: Addon, action: AddonAction ) => void;
}

/**
 * Fill defaults so records cached by an older plugin version (or a
 * partial API response) can never crash the UI on a missing field.
 *
 * @param record Add-on record to normalize.
 */
const normalizeAddon = ( record: Partial< Addon > ): Addon => ( {
	slug: '',
	name: '',
	description: '',
	longDescription: '',
	category: '',
	image: '',
	isProPlus: false,
	isUpsell: false,
	pluginBasename: '',
	version: '',
	installed: false,
	activated: false,
	networkActivated: false,
	...record,
	features: Array.isArray( record.features )
		? record.features.filter(
				( feature ): feature is string => 'string' === typeof feature
		  )
		: [],
} );

interface ParsedActionError {
	message: string;
	details: string;
}

const parseActionError = (
	error: unknown,
	fallback: string
): ParsedActionError => {
	if ( ! error || 'object' !== typeof error ) {
		return { message: fallback, details: '' };
	}

	// apiFetch could not parse the response — the request died mid-flight
	// (typically a fatal the server could not convert to JSON).
	if (
		'code' in error &&
		( 'invalid_json' === ( error as { code: unknown } ).code ||
			'fetch_error' === ( error as { code: unknown } ).code )
	) {
		return {
			message: __(
				'The add-on triggered a fatal error and the change was not applied.',
				'popup-maker'
			),
			details: '',
		};
	}

	const record = error as { message?: unknown; details?: unknown };

	return {
		message: 'string' === typeof record.message ? record.message : fallback,
		details: 'string' === typeof record.details ? record.details : '',
	};
};

const getErrorMessage = ( error: unknown, fallback: string ): string =>
	parseActionError( error, fallback ).message;

const getSuccessMessage = ( action: AddonAction, name: string ): string => {
	switch ( action ) {
		case 'deactivate':
			/* translators: %s: add-on name. */
			return sprintf( __( '%s deactivated.', 'popup-maker' ), name );
		default:
			/* translators: %s: add-on name. */
			return sprintf( __( '%s activated.', 'popup-maker' ), name );
	}
};

const getErrorTitle = ( action: AddonAction, name: string ): string => {
	switch ( action ) {
		case 'deactivate':
			return sprintf(
				/* translators: %s: add-on name. */
				__( '%s couldn’t be deactivated', 'popup-maker' ),
				name
			);
		default:
			return sprintf(
				/* translators: %s: add-on name. */
				__( '%s couldn’t be activated', 'popup-maker' ),
				name
			);
	}
};

export const useAddons = ( config: AddonsPageConfig ): UseAddonsResult => {
	const [ addons, setAddons ] = useState< Addon[] >( [] );
	const [ loading, setLoading ] = useState( true );
	const [ loadError, setLoadError ] = useState< string | null >( null );
	const [ busySlugs, setBusySlugs ] = useState< Record< string, boolean > >(
		{}
	);
	const [ notice, setNotice ] = useState< AddonsNotice | null >( null );

	const loadAddons = useCallback( () => {
		return apiFetch< Partial< Addon >[] >( { path: config.restPath } )
			.then( ( result ) => {
				setAddons(
					Array.isArray( result ) ? result.map( normalizeAddon ) : []
				);
				setLoadError( null );
			} )
			.catch( ( error: unknown ) => {
				setAddons( [] );
				setLoadError(
					getErrorMessage(
						error,
						__(
							'The add-on catalog could not be loaded. Please retry shortly.',
							'popup-maker'
						)
					)
				);
			} );
	}, [ config.restPath ] );

	useEffect( () => {
		setLoading( true );
		loadAddons().then( () => setLoading( false ) );
	}, [ loadAddons ] );

	const runAction = useCallback(
		( addon: Addon, action: AddonAction ) => {
			if ( 'upgrade' === action || busySlugs[ addon.slug ] ) {
				return;
			}

			setBusySlugs( ( current ) => ( {
				...current,
				[ addon.slug ]: true,
			} ) );
			setNotice( null );

			const name = addon.name || addon.slug;

			apiFetch( {
				path: `${ config.restPath }/${ action }`,
				method: 'POST',
				data: { slug: addon.slug },
			} )
				.then( () => {
					setNotice( {
						type: 'success',
						message: getSuccessMessage( action, name ),
					} );
					return loadAddons();
				} )
				.catch( ( error: unknown ) => {
					const parsed = parseActionError(
						error,
						__( 'The add-on action failed.', 'popup-maker' )
					);

					setNotice( {
						type: 'error',
						title: getErrorTitle( action, name ),
						message: parsed.message,
						details: parsed.details,
						actionFailure: true,
					} );

					// Errors often mean this tab's statuses are stale
					// (e.g. "already active" after changes in another tab)
					// — resync so the cards reflect reality.
					return loadAddons();
				} )
				.then( () => {
					setBusySlugs( ( current ) => {
						const next = { ...current };
						delete next[ addon.slug ];
						return next;
					} );
				} );
		},
		[ busySlugs, config.restPath, loadAddons ]
	);

	return {
		addons,
		loading,
		loadError,
		busySlugs,
		notice,
		clearNotice: useCallback( () => setNotice( null ), [] ),
		runAction,
	};
};
