import { useState, useEffect, useRef } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { Modal } from '@wordpress/components';
import { __ } from '@popup-maker/i18n';
import clsx from 'clsx';

import { STORE_NAME } from '../store';
import { clearOpenQueryParam } from '../utils/open-state';
import type { Notification, NotificationAction } from '../types';

// Human-friendly category labels.
const CATEGORY_LABELS: Record< string, string > = {
	feature: __( 'What’s New', 'popup-maker' ),
	recommendation: __( 'Tip for you', 'popup-maker' ),
	announcement: __( 'Announcement', 'popup-maker' ),
	offer: __( 'Offer', 'popup-maker' ),
	warning: __( 'Heads up', 'popup-maker' ),
};

// Category → default dashicon (overridable via notification.icon).
const CATEGORY_ICONS: Record< string, string > = {
	feature: 'lightbulb',
	recommendation: 'chart-line',
	announcement: 'megaphone',
	offer: 'tickets-alt',
	warning: 'warning',
};

// HTML from `title`, `message`, and `html` fields is sanitized server-side
// via wp_kses in the REST controller before reaching this component.
const renderHTML = ( html: string ) => ( { __html: html } );

declare global {
	interface Window {
		pum_review_api_url?: string;
		pum_review_uuid?: string | null;
		pum_review_trigger?: {
			group?: string;
			code?: string;
			pri?: string | number;
		};
	}
}

const saveDismissReviewRequest = (
	notification: Notification,
	reason: string
): void => {
	if (
		'review_request' !== notification.code ||
		typeof window.pum_review_api_url === 'undefined'
	) {
		return;
	}

	const body = new window.URLSearchParams( {
		trigger_group: window.pum_review_trigger?.group || '',
		trigger_code: window.pum_review_trigger?.code || '',
		reason: reason && reason !== 'dismiss' ? reason : 'maybe_later',
		uuid: window.pum_review_uuid || '',
	} );

	window
		.fetch( window.pum_review_api_url, {
			method: 'POST',
			body,
			keepalive: true,
			credentials: 'omit',
		} )
		.catch( () => {} );
};

interface Props {
	notification: Notification;
}

export const NotificationItem = ( { notification }: Props ): JSX.Element => {
	const { dismiss } = useDispatch( STORE_NAME );
	const [ isDismissing, setIsDismissing ] = useState( false );
	const [ iframeUrl, setIframeUrl ] = useState< string | null >( null );
	const [ iframeTitle, setIframeTitle ] = useState< string >( '' );
	const pendingDismiss = useRef< number | null >( null );

	useEffect( () => {
		return () => {
			if ( pendingDismiss.current !== null ) {
				window.clearTimeout( pendingDismiss.current );
			}
		};
	}, [] );

	const handleDismiss = () => {
		setIsDismissing( true );
		/*
		 * Wait for exit animation, then fire the close affordance.
		 * Passing an empty action string signals "user clicked the corner X"
		 * so the server can decide permanence on its own (never inherits a
		 * declared "Not now" snooze TTL).
		 */
		pendingDismiss.current = window.setTimeout(
			() => dismiss( notification.code, '' ),
			260
		);
	};

	const handleAction = (
		e: React.MouseEvent< HTMLButtonElement | HTMLAnchorElement >,
		action: NotificationAction
	) => {
		if ( action.type === 'iframe' && action.href ) {
			// Open the linked URL inside an in-panel modal iframe instead
			// of navigating away. Preferred for changelogs, docs, etc.
			e.preventDefault();
			setIframeUrl( action.href );
			setIframeTitle( action.text );
			return;
		}

		if ( action.type === 'link' && action.href ) {
			// Native anchor navigates — no additional handling needed.
			return;
		}
		e.preventDefault();
		// The server derives `expires` from the declared action on the
		// current alert definition — we only forward `code` + `action`.
		dismiss( notification.code, action.action || 'dismiss' );
	};

	// Legacy notices (review_request, etc.) embed action links inside
	// their `message` / `html` fields with class="pum-dismiss" and
	// data-reason="...". The old admin-bar JS handler is scoped to a
	// DOM container we don't render, so we wire these through our own
	// dismiss flow here.
	const handleLegacyClick = (
		e: React.MouseEvent< HTMLDivElement >
	): void => {
		const target = e.target as HTMLElement | null;
		const anchor = target?.closest?.( 'a' ) as HTMLAnchorElement | null;
		if ( ! anchor ) {
			return;
		}

		const isDismiss = anchor.classList.contains( 'pum-dismiss' );
		const href = anchor.getAttribute( 'href' ) || '';

		if ( isDismiss ) {
			// Let "Ok, you deserve it" and similar external-destination
			// dismiss links navigate (they go to wordpress.org review
			// page in a new tab) while still recording the dismissal.
			const reason = anchor.dataset.reason || 'maybe_later';

			if ( 'review_request' === notification.code ) {
				saveDismissReviewRequest( notification, reason );
			}

			if ( href && href !== '#' ) {
				// Fire dismiss in the background; let native nav happen.
				dismiss( notification.code, reason );
			} else {
				e.preventDefault();
				dismiss( notification.code, reason );
			}
		}

		// Non-dismiss anchors: native nav, nothing to do.
	};

	// Post-render: normalize links inside legacy body fields. External
	// HTTP(S) links get target=_blank + rel="noopener noreferrer" so
	// they never unload the admin page.
	const bodyRef = useRef< HTMLElement | null >( null );
	useEffect( () => {
		const anchors = document.querySelectorAll< HTMLAnchorElement >(
			`[data-code="${ CSS.escape( notification.code ) }"] .pum-notification-item__body a`
		);
		anchors.forEach( ( a ) => {
			const href = a.getAttribute( 'href' ) || '';
			const isExternal = /^https?:\/\//i.test( href );
			if ( isExternal && ! a.hasAttribute( 'target' ) ) {
				a.setAttribute( 'target', '_blank' );
			}
			if ( isExternal && ! a.hasAttribute( 'rel' ) ) {
				a.setAttribute( 'rel', 'noopener noreferrer' );
			}
		} );
	}, [ notification.code, notification.message, notification.html ] );

	// Silence unused-var lint; ref is kept for future per-item scoping.
	void bodyRef;

	const iconSlug =
		notification.icon ||
		CATEGORY_ICONS[ notification.category ] ||
		'admin-generic';
	const categoryLabel =
		CATEGORY_LABELS[ notification.category ] || notification.category;

	// Choose CTA style:
	// - offer-primary → chip
	// - primary (non-offer) → link with underline-grow
	// - non-primary → muted
	const ctaClassFor = ( action: NotificationAction ) => {
		if ( action.primary ) {
			return notification.category === 'offer'
				? 'pum-notification-cta pum-notification-cta--chip'
				: 'pum-notification-cta pum-notification-cta--link';
		}
		return 'pum-notification-cta pum-notification-cta--muted';
	};

	return (
		<article
			className={ clsx(
				'pum-notification-item',
				`pum-notification-item--${ notification.category }`,
				{ 'is-dismissing': isDismissing }
			) }
			data-code={ notification.code }
			data-category={ notification.category }
		>
			<span className="pum-notification-item__icon" aria-hidden="true">
				<span className={ `dashicons dashicons-${ iconSlug }` } />
			</span>

			<div className="pum-notification-item__head">
				<span className="pum-notification-item__category">
					{ categoryLabel }
				</span>
				{ notification.subtitle && (
					<span className="pum-notification-item__time">
						{ notification.subtitle }
					</span>
				) }
			</div>

			{ notification.dismissible && (
				<button
					type="button"
					className="pum-notification-item__dismiss"
					aria-label={ __( 'Dismiss', 'popup-maker' ) }
					onClick={ handleDismiss }
				>
					<span className="dashicons dashicons-no-alt" />
				</button>
			) }

			{ notification.title && (
				<h3
					className="pum-notification-item__title"
					// eslint-disable-next-line react/no-danger
					dangerouslySetInnerHTML={ renderHTML( notification.title ) }
				/>
			) }

			{ notification.message && (
				<div
					className="pum-notification-item__body"
					onClick={ handleLegacyClick }
					// eslint-disable-next-line react/no-danger
					dangerouslySetInnerHTML={ renderHTML( notification.message ) }
				/>
			) }

			{ notification.html && (
				<div
					className="pum-notification-item__body pum-notification-item__body--legacy"
					onClick={ handleLegacyClick }
					// eslint-disable-next-line react/no-danger
					dangerouslySetInnerHTML={ renderHTML( notification.html ) }
				/>
			) }

			{ notification.actions.length > 0 && (
				<div className="pum-notification-item__actions">
					{ notification.actions.map( ( action, idx ) => {
						const key = `${ notification.code }-a-${ idx }`;
						if ( action.type === 'link' && action.href ) {
							const external =
								action.external === true ||
								action.target === '_blank';
							// Internal same-tab navigation unloads the page.
							// Strip the open-state query param before the
							// navigator reads it so the destination page
							// doesn't auto-reopen the panel.
							const onLinkClick = external
								? undefined
								: () => clearOpenQueryParam();
							return (
								<a
									key={ key }
									href={ action.href }
									target={
										external ? '_blank' : undefined
									}
									rel={
										external
											? 'noopener noreferrer'
											: undefined
									}
									className={ ctaClassFor( action ) }
									onClick={ onLinkClick }
								>
									{ action.text }
								</a>
							);
						}
						return (
							<button
								key={ key }
								type="button"
								className={ ctaClassFor( action ) }
								onClick={ ( e ) => handleAction( e, action ) }
							>
								{ action.text }
							</button>
						);
					} ) }
				</div>
			) }

			{ iframeUrl && (
				<Modal
					className="pum-notification-item__iframe-modal"
					// No visible title — the iframe provides its own header.
					// `__experimentalHideHeader` removes the Modal header
					// bar including the close button; we render our own
					// dismiss affordance as an overlay. `contentLabel`
					// supplies an accessible name for screen readers that
					// would otherwise have no way to announce this dialog.
					__experimentalHideHeader
					contentLabel={
						iframeTitle || __( 'Details', 'popup-maker' )
					}
					onRequestClose={ () => setIframeUrl( null ) }
					shouldCloseOnClickOutside
					shouldCloseOnEsc
				>
					<button
						type="button"
						className="pum-notification-item__iframe-close"
						aria-label={ __( 'Close', 'popup-maker' ) }
						onClick={ () => setIframeUrl( null ) }
					>
						<span className="dashicons dashicons-no-alt" />
					</button>
					<iframe
						title={
							iframeTitle || __( 'Details', 'popup-maker' )
						}
						src={ iframeUrl }
						className="pum-notification-item__iframe"
						sandbox="allow-scripts allow-same-origin allow-popups allow-forms"
					/>
				</Modal>
			) }
		</article>
	);
};
