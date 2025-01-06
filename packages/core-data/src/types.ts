import type { CallToActionsStore } from './call-to-actions/types';
import type { PopupsStore } from './popups/types';

type StoreConfig = {
	'popup-maker/call-to-actions': {
		store: CallToActionsStore;
		name: 'popup-maker/call-to-actions';
	};
	'popup-maker/popups': {
		store: PopupsStore;
		name: 'popup-maker/popups';
	};
};

type StoreMap = {
	[ K in keyof StoreConfig ]: StoreConfig[ K ][ 'store' ];
} & Record< string, CallToActionsStore | PopupsStore >;

type StoreKeys = keyof StoreMap;

type UnwrapStore< T > = T extends { Selectors: any; Actions: any } ? T : never;
type StoreSelectors< K extends StoreKeys > = UnwrapStore<
	StoreMap[ K ]
>[ 'Selectors' ];
type StoreActions< K extends StoreKeys > = UnwrapStore<
	StoreMap[ K ]
>[ 'Actions' ];

declare module '@wordpress/data' {
	export function select< K extends StoreKeys >(
		key: K
	): StoreSelectors< K >;
	export function select( key: any ): any;

	export function dispatch< K extends StoreKeys >(
		key: K
	): StoreActions< K >;
	export function useDispatch< K extends StoreKeys >(
		key: K
	): StoreActions< K >;

	export function useSelect< T >(
		selector: (
			select: {
				< K extends StoreKeys >( key: K ): StoreSelectors< K >;
				( key: any ): any;
			},
		) => T,
		deps?: any[]
	): T;
}

export type EditorId = 'new' | number | undefined;

export type AppNotice = {
	id: string;
	message: string;
	type: 'success' | 'error' | 'warning' | 'info';
	isDismissible?: boolean;
	closeDelay?: number;
};

export type OmitFirstArg< F > = F extends (
	x: any,
	...args: infer P
) => infer R
	? ( ...args: P ) => R
	: never;

export type OmitFirstArgs< O > = {
	[ K in keyof O ]: OmitFirstArg< O[ K ] >;
};

export type RemoveReturnType< F > = F extends ( ...args: infer P ) => any
	? ( ...args: P ) => void
	: never;

export type RemoveReturnTypes< O > = {
	[ K in keyof O ]: RemoveReturnType< O[ K ] >;
};
