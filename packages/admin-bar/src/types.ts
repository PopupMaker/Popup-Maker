export interface CssSelectorOptions {
	selectors: Array< 'id' | 'class' | 'tag' | 'nthchild' | 'attribute' >;
}

export interface SelectorResult {
	t: string | null; // tag
	i: string | null; // id
	c: string[] | null; // classes
	a: string[] | null; // attributes
	n: string | null; // nth-child
}

export interface AdminBarText {
	instructions: string;
	results: string;
	copy: string;
	close: string;
	copied: string;
}
