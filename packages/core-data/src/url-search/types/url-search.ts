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
	title?: string | {
		raw?: string;
		rendered?: string;
	};
	url: string;
	source_url?: string;
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
