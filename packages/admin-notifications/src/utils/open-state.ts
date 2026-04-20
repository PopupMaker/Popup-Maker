/**
 * Open-state helpers for the notifications panel.
 *
 * Two complementary mechanisms:
 *   1. Query param — `?pum-notifications=open` — for same-page toggling,
 *      shareable deep links, and back-button support.
 *   2. localStorage flag — for cross-page navigation to the PM admin
 *      where the panel should auto-open on arrival. The flag has a
 *      short TTL so it can't trap future visits.
 */

export const OPEN_QUERY_PARAM = 'pum-notifications';
const OPEN_FLAG_KEY = 'pumOpenNotifications';
const OPEN_FLAG_TTL_MS = 30 * 1000;

interface OpenFlagPayload {
	v: number;
	exp: number;
}

/**
 * Strip `?pum-notifications=open` from the URL without reloading.
 */
export const clearOpenQueryParam = (): void => {
	if ( typeof window === 'undefined' || ! window.history?.replaceState ) {
		return;
	}

	try {
		const url = new URL( window.location.href );
		if ( ! url.searchParams.has( OPEN_QUERY_PARAM ) ) {
			return;
		}
		url.searchParams.delete( OPEN_QUERY_PARAM );
		window.history.replaceState( {}, '', url.toString() );
	} catch {
		// Noop — URL parsing failed, nothing to clean.
	}
};

/**
 * Push `?pum-notifications=open` onto the URL so the panel state
 * survives refresh and can be shared or bookmarked. Uses pushState so
 * the back button closes the panel without leaving the page.
 */
export const addOpenQueryParam = (): void => {
	if ( typeof window === 'undefined' || ! window.history?.pushState ) {
		return;
	}

	try {
		const url = new URL( window.location.href );
		if ( url.searchParams.get( OPEN_QUERY_PARAM ) === 'open' ) {
			return;
		}
		url.searchParams.set( OPEN_QUERY_PARAM, 'open' );
		window.history.pushState( {}, '', url.toString() );
	} catch {
		// Noop.
	}
};

/**
 * Whether the current URL carries the open query param.
 */
export const hasOpenQueryParam = (): boolean => {
	try {
		const url = new URL( window.location.href );
		return url.searchParams.get( OPEN_QUERY_PARAM ) === 'open';
	} catch {
		return false;
	}
};

/**
 * Record a short-lived "open the panel next page load" signal.
 */
export const setOpenFlag = (): void => {
	if ( typeof window === 'undefined' || ! window.localStorage ) {
		return;
	}
	try {
		const payload: OpenFlagPayload = {
			v: 1,
			exp: Date.now() + OPEN_FLAG_TTL_MS,
		};
		window.localStorage.setItem( OPEN_FLAG_KEY, JSON.stringify( payload ) );
	} catch {
		// Noop — private mode / quota exceeded.
	}
};

/**
 * Consume the open-on-next-load flag. Returns true iff a fresh,
 * unexpired flag was present. Always clears the key so it fires
 * at most once.
 */
export const consumeOpenFlag = (): boolean => {
	if ( typeof window === 'undefined' || ! window.localStorage ) {
		return false;
	}
	try {
		const raw = window.localStorage.getItem( OPEN_FLAG_KEY );
		if ( ! raw ) {
			return false;
		}
		window.localStorage.removeItem( OPEN_FLAG_KEY );
		const parsed = JSON.parse( raw ) as Partial< OpenFlagPayload >;
		if ( typeof parsed?.exp !== 'number' ) {
			return false;
		}
		return parsed.exp > Date.now();
	} catch {
		// Clear anything unparseable so it can't trap us.
		try {
			window.localStorage.removeItem( OPEN_FLAG_KEY );
		} catch {
			/* noop */
		}
		return false;
	}
};
