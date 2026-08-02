import { __ } from '@popup-maker/i18n';

import type {
	Addon,
	AddonAction,
	AddonStatus,
	AddonsPageConfig,
	FilterKey,
} from '../types';

export const getStatusKey = ( addon: Addon, busy: boolean ): AddonStatus => {
	if ( busy ) {
		return 'working';
	}

	if ( addon.networkActivated ) {
		return 'network';
	}

	if ( addon.activated ) {
		return 'active';
	}

	if ( addon.installed ) {
		return 'installed';
	}

	return addon.isUpsell ? 'locked' : 'unavailable';
};

export const getStatusLabel = (
	status: AddonStatus,
	proPlus = false
): string => {
	switch ( status ) {
		case 'working':
			return __( 'Working…', 'popup-maker' );
		case 'network':
			return __( 'Network Active', 'popup-maker' );
		case 'active':
			return __( 'Active', 'popup-maker' );
		case 'locked':
			return proPlus
				? __( 'Pro+ required', 'popup-maker' )
				: __( 'Pro required', 'popup-maker' );
		case 'installed':
			return __( 'Inactive', 'popup-maker' );
		default:
			return __( 'Unavailable', 'popup-maker' );
	}
};

export interface ActionDescriptor {
	action: AddonAction | null;
	disabled: boolean;
	label: string;
}

export const getActionDescriptor = (
	addon: Addon,
	busy: boolean,
	config: AddonsPageConfig
): ActionDescriptor => {
	if ( busy ) {
		return {
			action: null,
			disabled: true,
			label: __( 'Working…', 'popup-maker' ),
		};
	}

	if ( addon.networkActivated ) {
		return {
			action: null,
			disabled: true,
			label: __( 'Network Active', 'popup-maker' ),
		};
	}

	if ( addon.activated ) {
		return {
			action: 'deactivate',
			disabled: ! config.canActivate,
			label: __( 'Deactivate', 'popup-maker' ),
		};
	}

	if ( addon.installed ) {
		return {
			action: 'activate',
			disabled: ! config.canActivate,
			label: __( 'Activate', 'popup-maker' ),
		};
	}

	if ( addon.isUpsell ) {
		return {
			action: 'upgrade',
			disabled: false,
			label: addon.isProPlus
				? __( 'Get Pro+', 'popup-maker' )
				: __( 'Get Pro', 'popup-maker' ),
		};
	}

	return {
		action: null,
		disabled: true,
		label: __( 'Unavailable', 'popup-maker' ),
	};
};

export const matchesFilter = (
	status: AddonStatus,
	filter: FilterKey
): boolean => {
	switch ( filter ) {
		case 'active':
			return 'active' === status || 'network' === status;
		case 'installed':
			return (
				'active' === status ||
				'network' === status ||
				'installed' === status ||
				'working' === status
			);
		case 'locked':
			return 'locked' === status;
		default:
			return true;
	}
};

export const matchesQuery = (
	addon: Addon,
	query: string,
	categories: Record< string, string >
): boolean => {
	const trimmed = query.trim().toLowerCase();

	if ( ! trimmed ) {
		return true;
	}

	return [ addon.name, addon.description, categories[ addon.category ] ?? '' ]
		.join( ' ' )
		.toLowerCase()
		.includes( trimmed );
};
