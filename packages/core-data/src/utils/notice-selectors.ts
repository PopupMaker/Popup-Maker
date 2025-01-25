import { store as noticesStore } from '@wordpress/notices';
import { createRegistrySelector } from '@wordpress/data';

import type { WPNotice } from './notice-types';

/**
 * Create notice selectors.
 */
export const createNoticeSelectors = ( context: string ) => ( {
	/**
	 * Check if this store has notice functionality enabled.
	 * If false, we'll fall back to core notices with custom context.
	 */
	hasContextNotices: (): boolean => true,

	/**
	 * Get notices.
	 */
	getNotices: createRegistrySelector( ( select ) => () => {
		const notices = select( noticesStore ).getNotices( context );
		return notices || [];
	} ),

	/**
	 * Get notice by id.
	 */
	getNoticeById: createRegistrySelector( ( select ) => ( id: string ) => {
		const notices = select( noticesStore ).getNotices( context );
		return notices?.find( ( n ) => n.id === id );
	} ),
} );

export default createNoticeSelectors;
