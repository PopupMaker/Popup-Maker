import { store as noticesStore } from '@wordpress/notices';
import type { ThunkAction } from '../types';

/**
 * WP notice action.
 */
export type NoticeAction = {
	/**
	 * Notice action label.
	 */
	label: string;
	/**
	 * Notice action url.
	 */
	url: string | null;
	/**
	 * Notice action onClick.
	 */
	onClick: Function | null;
};

/**
 * Notice options.
 */
export type NoticeOptions = {
	/**
	 * Notice status.
	 */
	status?: string | undefined;
	/**
	 * Notice content.
	 */
	content: string | undefined;
	/**
	 * Notice context.
	 */
	context?: string | undefined;
	/**
	 * Notice id.
	 */
	id?: string | undefined;
	/**
	 * Notice is dismissible.
	 */
	isDismissible?: boolean | undefined;
	/**
	 * Notice type.
	 */
	type?: string | undefined;
	/**
	 * Notice speak.
	 */
	speak?: boolean | undefined;
	/**
	 * Notice actions.
	 */
	actions?: NoticeAction[] | undefined;
	/**
	 * Notice icon.
	 */
	icon?: string | undefined;
	/**
	 * Notice explicit dismiss.
	 */
	explicitDismiss?: boolean | undefined;
	/**
	 * Notice on dismiss.
	 */
	onDismiss?: Function | undefined;

	/**
	 * Notice close delay.
	 */
	closeDelay?: number | undefined;
};

/**
 * Create notice actions.
 */
export const createNoticeActions = ( context: string ) => ( {
	/**
	 * Create a notice.
	 */
	createNotice:
		(
			status: NoticeOptions[ 'status' ] = 'info',
			content: NoticeOptions[ 'content' ] = '',
			options: Omit< NoticeOptions, 'status' | 'content' > = {}
		): ThunkAction =>
		async ( { dispatch } ) => {
			dispatch( noticesStore ).createNotice( status, content, {
				...options,
				context,
			} );
		},

	/**
	 * Create an error notice.
	 */
	createErrorNotice:
		(
			content: string,
			options: Omit< NoticeOptions, 'status' | 'content' > = {}
		): ThunkAction =>
		async ( { dispatch } ) => {
			dispatch( noticesStore ).createNotice( 'error', content, {
				...options,
				context,
			} );
		},

	/**
	 * Create a success notice.
	 */
	createSuccessNotice:
		(
			content: string,
			options: Omit< NoticeOptions, 'status' | 'content' > = {}
		): ThunkAction =>
		async ( { dispatch } ) => {
			dispatch( noticesStore ).createNotice( 'success', content, {
				...options,
				context,
			} );
		},

	/**
	 * Remove a notice for a given context.
	 */
	removeNotice:
		( id: string ): ThunkAction =>
		async ( { dispatch } ) => {
			dispatch( noticesStore ).removeNotice( id, context );
		},

	/**
	 * Remove all notices for a given context.
	 */
	removeAllNotices:
		( ids?: string[] ): ThunkAction =>
		async ( { select, dispatch } ) => {
			if ( ids ) {
				dispatch( noticesStore ).removeNotices( ids, context );
			} else {
				const notices = select( noticesStore ).getNotices( context );
				const ids = notices.map( ( notice ) => notice.id );
				dispatch( noticesStore ).removeNotices( ids, context );
			}
		},
} );

export default createNoticeActions;
