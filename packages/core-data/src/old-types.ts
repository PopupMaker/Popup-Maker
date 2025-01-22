// import type { PopupsStore } from './popups/types';
// import type { SettingsStore } from './settings/types';
// import type { LicenseStore } from './license/types';
// import type { URLSearchStore } from './url-search/types';

// export type StoreConfig = {
// 	'popup-maker/popups': {
// 		store: PopupsStore;
// 		name: 'popup-maker/popups';
// 	};
// 	'popup-maker/settings': {
// 		store: SettingsStore;
// 		name: 'popup-maker/settings';
// 	};
// 	'popup-maker/license': {
// 		store: LicenseStore;
// 		name: 'popup-maker/license';
// 	};
// 	'popup-maker/url-search': {
// 		store: URLSearchStore;
// 		name: 'popup-maker/url-search';
// 	};
// };

// export type StoreMap = {
// 	[ K in keyof StoreConfig ]: StoreConfig[ K ][ 'store' ];
// } & Record<
// 	string,
// 	PopupsStore | SettingsStore | LicenseStore | URLSearchStore
// >;

// export type StoreKeys = keyof StoreMap;

// type UnwrapStore< T > = T extends { Selectors: any; Actions: any } ? T : never;
// export type StoreSelectors< K extends StoreKeys > = UnwrapStore<
// 	StoreMap[ K ]
// >[ 'Selectors' ];
// export type StoreActions< K extends StoreKeys > = UnwrapStore<
// 	StoreMap[ K ]
// >[ 'Actions' ];

// export type AppNotice = {
// 	id: string;
// 	message: string;
// 	type: 'success' | 'error' | 'warning' | 'info';
// 	isDismissible?: boolean;
// 	closeDelay?: number;
// };

// declare module '@wordpress/data' {
// 	export function select< K extends StoreKeys >(
// 		key: K
// 	): StoreSelectors< K >;
// 	export function select( key: any ): any;

// 	export function dispatch< K extends StoreKeys >(
// 		key: K
// 	): StoreActions< K >;
// 	export function useDispatch< K extends StoreKeys >(
// 		key: K
// 	): StoreActions< K >;

// 	export function useSelect< T >(
// 		selector: ( select: {
// 			< K extends StoreKeys >( key: K ): StoreSelectors< K >;
// 			( key: any ): any;
// 		} ) => T,
// 		deps?: any[]
// 	): T;
// }

// declare module '@wordpress/data-controls' {
// 	export function select<
// 		K extends StoreKeys,
// 		S extends keyof StoreSelectors< K >,
// 	>(
// 		storeName: K,
// 		selectorName: S,
// 		...args: StoreSelectors< K >[ S ] extends ( ...args: infer P ) => any
// 			? P
// 			: []
// 	): Generator<
// 		{ type: 'SELECT'; storeName: K; selectorName: S; args: any[] },
// 		StoreSelectors< K >[ S ] extends ( ...args: any[] ) => infer R
// 			? R
// 			: never,
// 		any
// 	>;
// }
