import { store as noticesStore } from '@wordpress/notices';
import { createRegistrySelector } from '@wordpress/data';

/**
 * Create notice selectors.
 */
export const createNoticeSelectors = ( context: string ) => ( {
	/**
	 * Check if this store has notice functionality enabled.
	 * If false, we'll fall back to core notices with custom context.
	 */
	hasContextNotices: (): true => true,

	/**
	 * Get notices.
	 */
	getNotices: createRegistrySelector( ( select ) => () => {
		return select( noticesStore ).getNotices( context );
	} ),

	/**
	 * Get notice by id.
	 */
	getNoticeById: createRegistrySelector( ( select ) => ( id: string ) => {
		const notices = select( noticesStore ).getNotices( context );
		return notices.find( ( notice ) => notice.id === id ) || null;
	} ),
} );

export default createNoticeSelectors;
