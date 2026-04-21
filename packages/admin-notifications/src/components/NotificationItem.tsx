import { useState, useEffect, useRef } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { Modal } from '@wordpress/components';
import { __ } from '@popup-maker/i18n';
import clsx from 'clsx';

import { STORE_NAME } from '../store';
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

			{ ( notification.message || notification.html ) && (
				<div
					className="pum-notification-item__body"
					// eslint-disable-next-line react/no-danger
					dangerouslySetInnerHTML={ renderHTML(
						notification.message || notification.html
					) }
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
