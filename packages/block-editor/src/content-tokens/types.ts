import type { PopupMaker } from '@popup-maker/registry';

/** Editor surfaces capable of hosting a registered content token. */
export type ContentTokenSurface =
	| 'block-editor-rich-text'
	| 'block-editor-control'
	| 'classic-editor';

/** How authors interact with a token's readable content. */
export type ContentTokenInteraction = 'atomic' | 'editable';

/** Context supplied by the editor surface before showing or inserting a token. */
export interface ContentTokenContext {
	surface: ContentTokenSurface;
	postId?: number;
	postType?: string;
	blockName?: string;
	attributeName?: string;
}

/** Declarative conditions are ANDed; values within each list are ORed. */
export interface ContentTokenConditions {
	surfaces?: ContentTokenSurface[];
	postTypes?: string[];
	blockTypes?: string[];
	attributes?: string[];
	excludedPostTypes?: string[];
	excludedBlockTypes?: string[];
}

/** Human-readable picker grouping. */
export interface ContentTokenGroup extends PopupMaker.RegistryItem {
	label: string;
	description?: string;
}

/** One portable content token projected into editor surfaces. */
export interface ContentTokenDefinition extends PopupMaker.RegistryItem {
	label: string;
	description?: string;
	type?: string;
	interaction?: ContentTokenInteraction;
	conditions?: ContentTokenConditions;
	/** Browser-safe preview/fallback text, or a contextual text resolver. */
	preview?:
		| string
		| ( ( context: ContentTokenContext ) => string | undefined );
	/** Optional runtime check for extension-owned editor state. */
	isAvailable?: ( context: ContentTokenContext ) => boolean;
	/** Portable Classic-editor representation when the provider supports one. */
	classicToken?: string;
}
