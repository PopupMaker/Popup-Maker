import { createRegistry } from '@popup-maker/registry';
import type {
	ContentTokenContext,
	ContentTokenDefinition,
	ContentTokenGroup,
} from './types';

const DEFAULT_GROUP = 'general';

export const contentTokenGroups = createRegistry< ContentTokenGroup >( {
	name: 'content-token-groups',
	defaultGroup: DEFAULT_GROUP,
} );

export const contentTokens = createRegistry< ContentTokenDefinition >( {
	name: 'content-tokens',
	defaultGroup: DEFAULT_GROUP,
} );

/**
 * Register or replace one human-readable token group.
 * @param group
 */
export const registerContentTokenGroup = ( group: ContentTokenGroup ): void => {
	contentTokenGroups.register( group );
	contentTokens.registerGroup( group.id, {
		label: group.label,
		priority: group.priority ?? 10,
	} );
};

/**
 * Register or replace one content token.
 * @param token
 */
export const registerContentToken = ( token: ContentTokenDefinition ): void => {
	contentTokens.register( token );
};

/**
 * Register a catalog without exposing registry implementation details.
 * @param tokens
 */
export const registerContentTokens = (
	tokens: ContentTokenDefinition[]
): void => tokens.forEach( registerContentToken );

const listAllows = ( value: string | undefined, allowed?: string[] ) =>
	! allowed?.length || ( !! value && allowed.includes( value ) );

/**
 * Check declarative and extension-owned availability for one editor context.
 * @param token
 * @param context
 */
export const isContentTokenAvailable = (
	token: ContentTokenDefinition,
	context: ContentTokenContext
): boolean => {
	const conditions = token.conditions;
	if ( conditions ) {
		if ( ! listAllows( context.surface, conditions.surfaces ) ) {
			return false;
		}
		if ( ! listAllows( context.postType, conditions.postTypes ) ) {
			return false;
		}
		if ( ! listAllows( context.blockName, conditions.blockTypes ) ) {
			return false;
		}
		if ( ! listAllows( context.attributeName, conditions.attributes ) ) {
			return false;
		}
		if (
			context.postType &&
			conditions.excludedPostTypes?.includes( context.postType )
		) {
			return false;
		}
		if (
			context.blockName &&
			conditions.excludedBlockTypes?.includes( context.blockName )
		) {
			return false;
		}
	}

	try {
		return token.isAvailable ? token.isAvailable( context ) : true;
	} catch {
		return false;
	}
};

/**
 * Resolve safe, readable editor content without exposing the canonical ID.
 * @param token
 * @param context
 */
export const getContentTokenPreview = (
	token: ContentTokenDefinition,
	context: ContentTokenContext
): string => {
	try {
		const preview =
			typeof token.preview === 'function'
				? token.preview( context )
				: token.preview;
		return preview?.trim() || token.label;
	} catch {
		return token.label;
	}
};
