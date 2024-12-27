import { StringParam, useQueryParams, withDefault } from 'use-query-params';

import { useDispatch, useSelect } from '@wordpress/data';
import {
	useMemo,
	useState,
	useEffect,
	useContext,
	createContext,
} from '@wordpress/element';

import { CALL_TO_ACTION_STORE } from '@popup-maker/core-data';

import type {
	CallToActionsState,
	CallToActionsStore,
} from '@popup-maker/core-data';

type Filters = {
	status?: string;
	searchText?: string;
};

type ListContext = {
	callToActions: CallToActionsState[ 'callToActions' ];
	filteredCallToActions: CallToActionsState[ 'callToActions' ];
	updateCallToAction: CallToActionsStore[ 'Actions' ][ 'updateCallToAction' ];
	deleteCallToAction: CallToActionsStore[ 'Actions' ][ 'deleteCallToAction' ];
	bulkSelection: number[];
	setBulkSelection: ( bulkSelection: number[] ) => void;
	isLoading: boolean;
	isDeleting: boolean;
	filters: Filters;
	setFilters: ( filters: Partial< Filters > ) => void;
};

const noop = () => {};

const defaultContext: ListContext = {
	callToActions: [],
	filteredCallToActions: [],
	bulkSelection: [],
	setBulkSelection: noop,
	updateCallToAction: noop,
	deleteCallToAction: noop,
	isLoading: false,
	isDeleting: false,
	filters: {
		status: 'all',
		searchText: '',
	},
	setFilters: noop,
};

const Context = createContext< ListContext >( defaultContext );

const { Provider, Consumer } = Context as React.Context< ListContext >;

type ProviderProps = {
	value?: Partial< ListContext >;
	children: React.ReactNode;
};

export const ListProvider = ( { value = {}, children }: ProviderProps ) => {
	const [ bulkSelection, setBulkSelection ] = useState< number[] >( [] );

	// Allow initiating the editor directly from a url.
	const [ filters, setFilters ] = useQueryParams( {
		status: withDefault( StringParam, 'all' ),
		searchText: withDefault( StringParam, '' ),
	} );

	// Quick helper to reset all query params.
	const clearFilterParams = () =>
		setFilters( { status: undefined, searchText: undefined } );

	// Self clear query params when component is removed.
	useEffect(
		() => clearFilterParams,
		// eslint-disable-next-line react-hooks/exhaustive-deps
		[]
	);

	// Fetch needed data from the @popup-maker/core-data & @wordpress/data stores.
	const { callToActions, isLoading, isDeleting } = useSelect( ( select ) => {
		const sel = select( CALL_TO_ACTION_STORE );
		// CallToAction List & Load Status.
		return {
			callToActions: sel.getCallToActions(),
			// @ts-ignore temporarily ignore this for now.
			isLoading: sel.isResolving( 'getCallToActions' ),
			isDeleting: sel.isDispatching( 'deleteCallToAction' ),
		};
	}, [] );

	// Get action dispatchers.
	const { updateCallToAction, deleteCallToAction } =
		useDispatch( CALL_TO_ACTION_STORE );

	// Filtered list of callToActions for the current status filter.
	const filteredCallToActions = useMemo( () => {
		return callToActions
			.filter( ( r ) =>
				filters.status === 'all' ? true : filters.status === r.status
			)
			.filter(
				( r ) =>
					! filters.searchText ||
					! filters.searchText.length ||
					r.title
						.toLowerCase()
						.indexOf( filters.searchText.toLowerCase() ) >= 0 ||
					( r.description &&
						r.description
							.toLowerCase()
							.indexOf( filters.searchText.toLowerCase() ) >= 0 )
			);
	}, [ callToActions, filters ] );

	return (
		<Provider
			value={ {
				...value,
				filters,
				setFilters,
				bulkSelection,
				setBulkSelection,
				callToActions,
				filteredCallToActions,
				updateCallToAction,
				deleteCallToAction,
				isLoading,
				isDeleting,
			} }
		>
			{ children }
		</Provider>
	);
};

export { Consumer as ListConsumer };

export const useList = () => {
	const context = useContext( Context );

	return context;
};
