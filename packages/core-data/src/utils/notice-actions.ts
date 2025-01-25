import { store as noticesStore } from '@wordpress/notices';

import type { ThunkAction, NoticeOptions } from './notice-types';

/**
 * Create notice actions.
 */
export const createNoticeActions = ( context: string ) => ( {
	/**
	 * Create a notice.
	 */
	createNotice:
		(
			/**
			 * Notice status.
			 */
			status: NoticeOptions[ 'status' ] = 'info',
			/**
			 * Notice content.
			 */
			content: NoticeOptions[ 'content' ] = '',
			/**
			 * Notice options.
			 */
			options: Omit< NoticeOptions, 'status' | 'content' > = {}
		): ThunkAction =>
		async ( { registry } ) => {
			registry.dispatch( noticesStore ).createNotice( status, content, {
				...options,
				context,
			} );
		},

	/**
	 * Create an error notice.
	 */
	createErrorNotice:
		(
			/**
			 * Notice content.
			 */
			content: string,
			/**
			 * Notice options.
			 */
			options: Omit< NoticeOptions, 'status' | 'content' > = {}
		): ThunkAction =>
		async ( { registry } ) => {
			registry.dispatch( noticesStore ).createNotice( 'error', content, {
				...options,
				context,
			} );
		},

	/**
	 * Create a success notice.
	 */
	createSuccessNotice:
		(
			/**
			 * Notice content.
			 */
			content: string,
			/**
			 * Notice options.
			 */
			options: Omit< NoticeOptions, 'status' | 'content' > = {}
		): ThunkAction =>
		async ( { registry } ) => {
			registry
				.dispatch( noticesStore )
				.createNotice( 'success', content, {
					...options,
					context,
				} );
		},

	/**
	 * Remove a notice for a given context.
	 */
	removeNotice:
		(
			/**
			 * Notice ID.
			 */
			id: string
		): ThunkAction =>
		async ( { registry } ) => {
			registry.dispatch( noticesStore ).removeNotice( id, context );
		},

	/**
	 * Remove all notices for a given context.
	 */
	removeAllNotices:
		(
			/**
			 * Notice IDs.
			 */
			ids?: string[]
		): ThunkAction =>
		async ( { registry } ) => {
			if ( ids ) {
				registry.dispatch( noticesStore ).removeNotices( ids, context );
			} else {
				const notices = registry
					.select( noticesStore )
					.getNotices( context );
				const ids = notices.map( ( notice ) => notice.id );
				registry.dispatch( noticesStore ).removeNotices( ids, context );
			}
		},
} );

export default createNoticeActions;
