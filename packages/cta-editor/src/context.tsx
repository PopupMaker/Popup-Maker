import { StringParam, useQueryParams, withDefault } from 'use-query-params';

import { useDispatch, useSelect } from '@wordpress/data';
import {
	useMemo,
	useState,
	useEffect,
	useContext,
	createContext,
} from '@wordpress/element';

import { callToActionStore } from '@popup-maker/core-data';
import { SortDirection, type SortConfig } from '@popup-maker/components';

import type {
	CallToAction,
	CallToActionStore,
	EditableCta,
} from '@popup-maker/core-data';
import type { ActionCreatorsOf, ConfigOf } from '@wordpress/data/src/types';

type Filters = {
	status?: string;
	searchText?: string;
};

type ListContext = {
	callToActions: CallToAction< 'edit' >[];
	filteredCallToActions: CallToAction< 'edit' >[];
	updateCallToAction: ActionCreatorsOf<
		ConfigOf< CallToActionStore >
	>[ 'updateCallToAction' ];
	deleteCallToAction: ActionCreatorsOf<
		ConfigOf< CallToActionStore >
	>[ 'deleteCallToAction' ];
	bulkSelection: number[];
	setBulkSelection: ( bulkSelection: number[] ) => void;
	isLoading: boolean;
	isDeleting: boolean;
	filters: Filters;
	setFilters: ( filters: Partial< Filters > ) => void;
	sortConfig: SortConfig | null;
	setSortConfig: ( config: SortConfig | null ) => void;
};

const noop = () => {};

const defaultContext: ListContext = {
	callToActions: [],
	filteredCallToActions: [],
	bulkSelection: [],
	setBulkSelection: () => {},
	updateCallToAction: (
		// eslint-disable-next-line @typescript-eslint/no-unused-vars
		_callToAction: Partial< EditableCta > & { id: number },
		// eslint-disable-next-line @typescript-eslint/no-unused-vars
		_validate?: boolean
	) => Promise.resolve( false ),
	// eslint-disable-next-line @typescript-eslint/no-unused-vars
	deleteCallToAction: ( _id: number, _forceDelete: boolean = false ) =>
		Promise.resolve( false ),
	isLoading: false,
	isDeleting: false,
	filters: {
		status: 'all',
		searchText: '',
	},
	setFilters: noop,
	sortConfig: null,
	setSortConfig: () => {},
};

const Context = createContext< ListContext >( defaultContext );

const { Provider, Consumer } = Context as React.Context< ListContext >;

type ProviderProps = {
	value?: Partial< ListContext >;
	children: React.ReactNode;
};

export const ListProvider = ( { value = {}, children }: ProviderProps ) => {
	const [ bulkSelection, setBulkSelection ] = useState< number[] >( [] );
	const [ sortConfig, setSortConfig ] = useState< SortConfig | null >( null );

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
		const sel = select( callToActionStore );
		// CallToAction List & Load Status.
		return {
			callToActions: sel.getCallToActions(),
			isLoading: sel.isResolving( 'getCallToActions' ),
			isDeleting: sel.isResolving( 'deleteCallToAction' ),
		};
	}, [] );

	// Get action dispatchers.
	const { updateCallToAction, deleteCallToAction } =
		useDispatch( callToActionStore );

	// Filtered list of callToActions for the current status filter.
	const filteredCallToActions = useMemo( () => {
		if ( ! callToActions ) {
			return [];
		}

		const filtered = callToActions
			.filter( ( r ) =>
				filters.status === 'all' ? true : filters.status === r.status
			)
			.filter(
				( r ) =>
					! filters.searchText ||
					! filters.searchText.length ||
					r.title.rendered
						.toLowerCase()
						.indexOf( filters.searchText.toLowerCase() ) >= 0 ||
					( r.excerpt.rendered &&
						r.excerpt.rendered
							.toLowerCase()
							.indexOf( filters.searchText.toLowerCase() ) >= 0 )
			);

		// Apply sorting if sort config exists
		if ( sortConfig !== null ) {
			filtered.sort( ( a, b ) => {
				const aValue =
					sortConfig.key === 'type'
						? a.settings.type
						: a.title.rendered.toLowerCase();
				const bValue =
					sortConfig.key === 'type'
						? b.settings.type
						: b.title.rendered.toLowerCase();

				if ( aValue < bValue ) {
					return sortConfig.direction === SortDirection.ASC ? -1 : 1;
				}
				if ( aValue > bValue ) {
					return sortConfig.direction === SortDirection.ASC ? 1 : -1;
				}
				return 0;
			} );
		}

		return filtered;
	}, [ callToActions, filters, sortConfig ] );

	return (
		<Provider
			value={ {
				...value,
				bulkSelection,
				setBulkSelection,
				filters,
				setFilters,
				callToActions,
				filteredCallToActions,
				isLoading,
				isDeleting,
				updateCallToAction,
				deleteCallToAction,
				sortConfig,
				setSortConfig,
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
