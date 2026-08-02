import React, { useState } from 'react';

import type { Addon } from '../types';

const DASHICONS: Record< string, string > = {
	'popup-maker-age-verification-modals': 'shield',
	'popup-maker-ajax-login-modals': 'admin-users',
	'popup-maker-aweber-integration': 'email-alt',
	'popup-maker-ecommerce-popups': 'cart',
	'popup-maker-geotargeting': 'location-alt',
	'popup-maker-leaving-notices': 'external',
	'popup-maker-lms-popups': 'welcome-learn-more',
	'popup-maker-mailchimp-integration': 'email-alt',
	'popup-maker-remote-content': 'cloud',
	'popup-maker-secure-idle-user-logout': 'lock',
	'popup-maker-terms-conditions-popups': 'media-text',
	'popup-maker-videos': 'video-alt3',
};

const safeImageUrl = ( value: string ): string => {
	try {
		const url = new URL( value, window.location.origin );
		return 'https:' === url.protocol ||
			url.origin === window.location.origin
			? url.toString()
			: '';
	} catch ( error ) {
		return '';
	}
};

const AddonIcon = ( { addon }: { addon: Addon } ) => {
	const [ imageFailed, setImageFailed ] = useState( false );
	const imageUrl = safeImageUrl( addon.image );

	return (
		<span className="pum-addons__icon" aria-hidden="true">
			{ imageUrl && ! imageFailed ? (
				<img
					src={ imageUrl }
					alt=""
					onError={ () => setImageFailed( true ) }
				/>
			) : (
				<span
					className={ `dashicons dashicons-${
						DASHICONS[ addon.slug ] ?? 'admin-plugins'
					}` }
				/>
			) }
		</span>
	);
};

export default AddonIcon;
