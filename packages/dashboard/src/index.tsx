import './index.scss';

// Initialize advanced analytics widget when DOM is ready
document.addEventListener( 'DOMContentLoaded', () => {
	const container = document.getElementById( 'pum_analytics_basic' );

	if ( container ) {
		// Move the .pum-widget-badge from within the container to the end of the .postbox-header h2 text.
		const header = container.querySelector( '.postbox-header h2' );
		if ( header ) {
			const badge = container.querySelector( '.pum-widget-badge' );
			if ( badge ) {
				header.innerHTML = header.innerHTML.replace(
					badge.outerHTML,
					''
				);
				header.appendChild( badge );
				//Remove style attribute
				badge.removeAttribute( 'style' );
			}
		}
	}
} );
