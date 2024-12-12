import type { OmitFirstArgs, RemoveReturnTypes } from '../types';

export type SearchArgTypes = 'post' | 'term' | 'post-format' | string;

export type SearchArgs = {
	search: string;
	context?: 'view' | 'embed';
	page?: number;
	per_page?: number | string;
	type?: SearchArgTypes | SearchArgTypes[];
	subtype?: 'any' | 'post' | 'page' | 'category' | 'post_tag' | string;
	isInitialSuggestions?: boolean;
};

export type SearchOptions = Omit< SearchArgs, 'search' | 'per_page' > & {
	perPage?: number;
};

export type WPLinkSearchResult = {
	id?: number;
	title?: string;
	url: string;
	type?: string;
	subtype?: string | undefined;
	meta?: {
		kind?: string;
	};
};

export type URLSearchQuery = {
	text: string;
	results: WPLinkSearchResult[];
	xtotal: number;
};

export type URLSearchState = {
	currentQuery?: string;
	searchResults?: WPLinkSearchResult[];
	queries: Record< URLSearchQuery[ 'text' ], URLSearchQuery >;
	// Boilerplate
	dispatchStatus?: {
		[ Property in URLSearchStore[ 'ActionNames' ] ]?: {
			status: string;
			error: string;
		};
	};
	error?: string;
};

export interface URLSearchStore {
	StoreKey:
		| 'popup-paker/url-search'
		| typeof import('../url-search/index').URL_SEARCH_STORE
		| typeof import('../url-search/index').urlSearchStore;
	State: URLSearchState;
	Actions: RemoveReturnTypes< typeof import('../url-search/actions') >;
	Selectors: OmitFirstArgs< typeof import('../url-search/selectors') >;
	ActionNames: keyof URLSearchStore[ 'Actions' ];
	SelectorNames: keyof URLSearchStore[ 'Selectors' ];
}
