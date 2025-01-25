import { __ } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';
import { store as coreDataStore } from '@wordpress/core-data';

import { storeHasNotices } from './notice-types';

import type { BaseEntityRecords } from '@wordpress/core-data';
import type {
	ThunkContext,
	ThunkAction,
	StoreDescriptor,
} from './entity-types';

type ActionMap< T extends BaseEntityRecords.BaseEntity< 'edit' > > = ReturnType<
	typeof createBaseActions< T >
>;

type RemapKeys< T, M extends Partial< Record< keyof T, string > > > = {
	[ K in keyof T as K extends keyof M ? never : K ]: T[ K ];
} & {
	[ P in keyof M as NonNullable< M[ P ] > ]: P extends keyof T
		? T[ P ]
		: never;
};

const createBaseActions = <
	T extends
		BaseEntityRecords.BaseEntity< 'edit' > = BaseEntityRecords.BaseEntity< 'edit' >,
>(
	name: string
) => {
	const DEFAULT_QUERY = { per_page: -1, context: 'view' } as const;
	const NOTICE_CONTEXT = `${ name }-editor`;

	/**
	 * Helper to handle notices with fallback to core notices
	 */
	const handleNotice = async (
		context: ThunkContext< StoreDescriptor >,
		options: {
			type: 'success' | 'error';
			message: string;
		}
	) => {
		if ( storeHasNotices( context ) ) {
			context.dispatch.createNotice( options.type, options.message, {
				context: NOTICE_CONTEXT,
			} );
		} else {
			// Fallback to core notices
			await context.registry
				.dispatch( noticesStore )
				.createNotice( options.type, options.message, {
					context: NOTICE_CONTEXT,
				} );
		}
	};

	return {
		create:
			(
				entity: Partial< T >,
				validate?: (
					entity: Partial< T >
				) => true | { message: string }
			): ThunkAction< T | boolean > =>
			async ( context ) => {
				const { registry } = context;

				try {
					if ( validate ) {
						const validation = validate( entity );
						if ( validation !== true ) {
							await handleNotice( context, {
								type: 'error',
								message: validation.message,
							} );
							return false;
						}
					}

					const result: boolean = await registry
						.dispatch( coreDataStore )
						.saveEntityRecord( 'postType', name, entity );

					if ( result ) {
						await handleNotice( context, {
							type: 'success',
							message: __( 'Entity created successfully' ),
						} );
					}

					return result;
				} catch ( error ) {
					await handleNotice( context, {
						type: 'error',
						message:
							error instanceof Error
								? error.message
								: __( 'Failed to create entity' ),
					} );
					throw error;
				}
			},

		edit:
			( id: number, edits: Partial< T > ): ThunkAction =>
			async ( context ) => {
				const { registry } = context;
				try {
					await registry
						.dispatch( coreDataStore )
						.editEntityRecord( 'postType', name, id, edits );
				} catch ( error ) {
					console.error( 'Edit failed:', error );
					await handleNotice( context, {
						type: 'error',
						message: __( 'Failed to edit entity' ),
					} );
				}
			},

		save:
			(
				id: number,
				validate?: ( entity: T ) => true | { message: string }
			): ThunkAction< boolean > =>
			async ( context ) => {
				const { registry } = context;

				try {
					const entity = registry
						.select( coreDataStore )
						.getEditedEntityRecord( 'postType', name, id ) as
						| T
						| false;

					if ( entity && validate ) {
						const validation = validate( entity );
						if ( validation !== true ) {
							await handleNotice( context, {
								type: 'error',
								message: validation.message,
							} );

							return false;
						}
					}

					const result: boolean = await registry
						.dispatch( coreDataStore )
						.saveEntityRecord( 'postType', name, id );

					if ( result ) {
						await handleNotice( context, {
							type: 'success',
							message: __( 'Entity saved successfully' ),
						} );
					}

					return result;
				} catch ( error ) {
					console.error( 'Save failed:', error );
					await handleNotice( context, {
						type: 'error',
						message: __( 'Failed to save entity' ),
					} );
					throw error;
				}
			},

		delete:
			( id: number, force = false ): ThunkAction =>
			async ( context ) => {
				const { registry } = context;
				try {
					await registry
						.dispatch( coreDataStore )
						.deleteEntityRecord( 'postType', name, id, { force } );

					await handleNotice( context, {
						type: 'success',
						message: __( 'Entity deleted successfully' ),
					} );
				} catch ( error ) {
					console.error( 'Delete failed:', error );
					await handleNotice( context, {
						type: 'error',
						message: __( 'Failed to delete entity' ),
					} );
					throw error;
				}
			},

		invalidateList:
			( query?: any ): ThunkAction =>
			async ( context ) => {
				const { registry } = context;

				await registry
					.dispatch( coreDataStore )
					// @ts-expect-error
					.invalidateResolution( 'getEntityRecords', [
						'postType',
						name,
						query ?? DEFAULT_QUERY,
					] );
			},

		resetEdits:
			( id: number ): ThunkAction =>
			async ( { registry } ) => {
				await registry
					.dispatch( coreDataStore )
					.editEntityRecord( 'postType', name, id, {} );
			},

		undo:
			(): ThunkAction =>
			async ( { registry } ) =>
				await registry.dispatch( coreDataStore ).undo(),

		redo:
			(): ThunkAction =>
			async ( { registry } ) =>
				await registry.dispatch( coreDataStore ).redo(),
	};
};

export const createPostTypeActions = <
	T extends BaseEntityRecords.BaseEntity< 'edit' >,
	M extends Partial< Record< keyof ActionMap< T >, string > > = {},
>(
	name: string,
	mapping?: M
): RemapKeys< ActionMap< T >, M > => {
	const baseActions = createBaseActions< T >( name );

	if ( ! mapping ) {
		return baseActions as RemapKeys< ActionMap< T >, M >;
	}

	const remappedActions = { ...baseActions };

	for ( const [ key, newKey ] of Object.entries( mapping ) ) {
		if ( key in baseActions && newKey ) {
			Object.defineProperty(
				remappedActions,
				newKey,
				Object.getOwnPropertyDescriptor( baseActions, key ) || {}
			);
			delete remappedActions[ key as keyof typeof baseActions ];
		}
	}

	return remappedActions as RemapKeys< ActionMap< T >, M >;
};

export default createPostTypeActions;
