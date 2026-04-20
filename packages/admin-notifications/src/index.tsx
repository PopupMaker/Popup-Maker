import './styles.scss';

import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';

import { registerStore } from './store';
import { NotificationPanel } from './components/NotificationPanel';
import { consumeOpenFlag, hasOpenQueryParam } from './utils/open-state';

export { NotificationPanel } from './components/NotificationPanel';
export { NotificationItem } from './components/NotificationItem';
export { store, STORE_NAME } from './store';
export type {
	Notification,
	NotificationAction,
	NotificationCategory,
	NotificationType,
} from './types';

const PANEL_ROOT_ID = 'pum-notifications-panel-root';

// Either a deep link OR a one-shot localStorage flag set by a prior marker
// click opens the panel on mount. The flag is consumed whenever checked.
const shouldOpenOnMount = (): boolean =>
	hasOpenQueryParam() || consumeOpenFlag();

const mountContainer = (): HTMLElement => {
	let el = document.getElementById( PANEL_ROOT_ID );
	if ( ! el ) {
		el = document.createElement( 'div' );
		el.id = PANEL_ROOT_ID;
		document.body.appendChild( el );
	}
	return el;
};

export const init = (): void => {
	registerStore();

	const root = mountContainer();
	createRoot( root ).render(
		<NotificationPanel initiallyOpen={ shouldOpenOnMount() } />
	);
};

domReady( init );
