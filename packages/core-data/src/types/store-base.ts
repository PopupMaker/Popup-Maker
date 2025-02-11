import type {
	dispatch as wpDispatch,
	select as wpSelect,
} from '@wordpress/data';

import type {
	ActionCreator,
	ConfigOf,
	CurriedSelectorsOf,
	DataRegistry,
	DispatchReturn,
	ReduxStoreConfig,
	StoreDescriptor,
} from '@wordpress/data/src/types';

import type { PromiseReturnMethods, StateOf } from './utils';

/**
 * Select function with name.
 */
export type SelectFnWithName = ( storeName: string ) => unknown;

/**
 * Base Redux action shape
 */
export interface DispatchAction {
	type: string;
	[ key: string ]: any;
}

/**
 * You might also add overloads for dispatching raw actions or thunks.
 */
export type DispatchAny = ( action: DispatchAction ) => any;

/**
 * Combine your local store's curried selectors
 * 	with the function signatures for cross-store usage.
 *
 * - Object form: select.getTemperature()
 * - Function form: select((state) => state.temperature * 2)
 */
export type StoreSelect< S extends StoreDescriptor< any > | string > =
	CurriedSelectorsOf< S > &
		( < R >(
			selector: ( state: StateOf< ConfigOf< S > > ) => R // Function form: select((state) => state.temperature * 2)
		) => R );

/**
 * Resolve a store's selectors.
 *
 * Same as StoreSelect but returns a promise with the value.
 *
 * - Object form: await resolveSelect.getTemperature()
 * - Function form: await resolveSelect((state) => state.temperature * 2)
 */
export type StoreResolveSelect< S extends StoreDescriptor< any > | string > = {
	[ K in keyof CurriedSelectorsOf< S > ]: CurriedSelectorsOf< S >[ K ] extends (
		...args: any[]
	) => any
		? (
				...args: Parameters< CurriedSelectorsOf< S >[ K ] >
		  ) => Promise< ReturnType< CurriedSelectorsOf< S >[ K ] > >
		: never;
};

/**
 * Combine your local store's actions
 * 	with the function signatures for cross-store usage.
 *
 * - Object form: dispatch.myAction(...) as well
 * - Function form: dispatch({ type: 'MY_ACTION', value: ... })
 * - Promise form: dispatch( () => window.fetch(...) )
 */
export type StoreDispatch< S extends StoreDescriptor< any > | string > =
	DispatchReturn< S > & // Object form: dispatch.retrieveTemperature()
		( (
			action:
				| DispatchAction // Raw action: { type: 'SET_TEMPERATURE', temperature: value }
				| ( () => Promise< any > | any ) // Thunk: () => window.fetch(...)
		) => Promise< any > );

type BatchCallback = () => void | Promise< void >;

/**
 * Context for a store's thunk.
 */
export type StoreThunkContext< S extends StoreDescriptor< any > | string > = {
	select: StoreSelect< S >;
	resolveSelect: StoreResolveSelect< S >;
	dispatch: StoreDispatch< S >;
	registry: {
		select: typeof wpSelect & SelectFnWithName;
		resolveSelect: typeof wpSelect &
			( < T extends StoreDescriptor< any > | string >(
				storeNameOrDescriptor: T
			) => PromiseReturnMethods< ReturnType< typeof wpSelect > > );
		dispatch: typeof wpDispatch & DispatchAny;
		batch: < C extends BatchCallback >( callback: C ) => ReturnType< C >;
	} & DataRegistry;
};

/**
 * Helper type to extract selectors from a store config
 */
export type ExtractSelectors< T > = T extends ReduxStoreConfig<
	any,
	any,
	infer S
>
	? S extends Record< string, ( ...args: any[] ) => any >
		? S
		: never
	: never;

/**
 * Helper type to extract actions from a store config
 */
export type ExtractActions< T > = T extends ReduxStoreConfig<
	any,
	infer A,
	any
>
	? A extends Record< string, ActionCreator >
		? A
		: never
	: never;
