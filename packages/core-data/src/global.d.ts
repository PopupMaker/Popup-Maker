/**
 * Global window augmentations
 */
declare global {
	interface Window {
		wpApiSettings?: {
			nonce: string;
			root?: string;
			[ key: string ]: unknown;
		};
	}
}

export {};

// Import base types
// import type {
// 	StoreDescriptor,
// 	ConfigOf,
// 	ReduxStoreConfig,
// 	StoreRegistry as WPStoreRegistry,
// 	SelectFunction as WPSelectFunction,
// 	CurriedSelectorsOf,
// 	UseSelectReturn,
// 	UseDispatchReturn,
// 	DispatchReturn,
// } from '@wordpress/data/src/types';

// Import store types
// import type {
// 	StoreState as SettingsState,
// 	StoreActions as SettingsActions,
// 	StoreSelectors as SettingsSelectors,
// 	StoreResolvers as SettingsResolvers,
// } from './settings/types/store';

// import type { SETTINGS_STORE, SettingsStore, settingsStore } from './settings';

/**
 * Extend SelectFunction to handle our store names
 */
// export interface ExtendedSelectFunction extends WPSelectFunction {
// Add overload for our specific store
// ( store: typeof SETTINGS_STORE ): CurriedSelectorsOf< SettingsStore >;
// }

/**
 * Declare module augmentation for @wordpress/data
 */
// declare module '@wordpress/data' {
/**
 * Global store registry merging WordPress core stores with Popup Maker stores
 */
// export interface StoreRegistry extends WPStoreRegistry {
// Settings Store
// [ SETTINGS_STORE ]: SettingsStore;
// }
/**
 * Select function declarations
 */
// export function select< S extends keyof StoreRegistry >(
// 	store: S
// ): CurriedSelectorsOf< StoreRegistry[ S ] >;
// export function select( store: string ): any;
/**
 * Dispatch function declarations
 */
// export function dispatch< S extends keyof StoreRegistry >(
// 	store: S
// ): DispatchReturn< StoreRegistry[ S ] >;
// export function useDispatch< S extends keyof StoreRegistry >(
// 	store: S
// ): UseDispatchReturn< StoreRegistry[ S ] >;
/**
 * useSelect hook declaration
 */
// export function useSelect< T >(
// 	selector: ( select: {
// 		< S extends keyof StoreRegistry >(
// 			store: S
// 		): UseSelectReturn< StoreRegistry[ S ] >;
// 		( store: string ): any;
// 	} ) => T,
// 	deps?: any[]
// ): T;
// }

/**
 * Declare module augmentation for @wordpress/data
 */
// declare module '@wordpress/data' {
// 	export interface StoreRegistry extends RegisteredStores {}

// 	export function select< K extends keyof StoreRegistry >(
// 		key: K
// 	): ExtractSelectors< ConfigOf< StoreRegistry[ K ] > >;
// 	export function select( key: any ): any;

// 	export function dispatch< K extends keyof StoreRegistry >(
// 		key: K
// 	): ExtractActions< ConfigOf< StoreRegistry[ K ] > >;
// 	export function useDispatch< K extends keyof StoreRegistry >(
// 		key: K
// 	): ExtractActions< ConfigOf< StoreRegistry[ K ] > >;

// 	export function useSelect< T >(
// 		selector: ( select: {
// 			< K extends keyof StoreRegistry >(
// 				key: K
// 			): ExtractSelectors< ConfigOf< StoreRegistry[ K ] > >;
// 			( key: any ): any;
// 		} ) => T,
// 		deps?: any[]
// 	): T;
// }
