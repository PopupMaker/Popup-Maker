import $ from 'jquery';
import { __ } from '@popup-maker/i18n';
import { finder } from '@medv/finder';
import { AdminBarText } from './types';

declare const popupMakerAdminBar:
	| {
			i18n: AdminBarText;
	  }
	| undefined;

interface ModalOptions {
	title: string;
	content: string;
	closeText?: string;
	actions?: {
		primary?: {
			text: string;
			onClick: () => void;
		};
		secondary?: {
			text: string;
			onClick: () => void;
		};
	};
}

function createModal( options: ModalOptions ): HTMLElement {
	const container = document.createElement( 'div' );
	const actionsHtml = options.actions
		? `
		<div class="pum-modal-actions">
			${
				options.actions.primary
					? `
				<button class="button button-primary pum-modal-proceed">
					${ options.actions.primary.text }
				</button>
			`
					: ''
			}
			${
				options.actions.secondary
					? `
				<button class="button button-secondary pum-modal-cancel">
					${ options.actions.secondary.text }
				</button>
			`
					: ''
			}
		</div>
	`
		: '';

	container.innerHTML = `<div class="pum-modal pum-modal-overlay">
		<div class="pum-modal-content">
			<div class="pum-modal-header">
				<div class="pum-logo"></div>
				<h4>${ options.title }</h4>
				<button class="pum-modal-close">
					<span class="dashicons dashicons-no-alt"></span>
					<span class="screen-reader-text">${
						options.closeText || __( 'Close', 'popup-maker' )
					}</span>
				</button>
			</div>
			<div class="pum-modal-body">
				${ options.content }
				${ actionsHtml }
			</div>
		</div>
	</div>`;

	document.body.appendChild( container );

	// Setup close handlers
	const close = () => {
		// Remove all event listeners
		$( document ).off( 'keydown', handleEscKey );
		$( document ).off( 'mousedown', handleClickOutside );
		$( '.pum-modal-close, .pum-modal-cancel', container ).off(
			'click',
			close
		);
		$( '.pum-modal-proceed', container ).off(
			'click',
			handlePrimaryAction
		);
		$( '.pum-modal-cancel', container ).off(
			'click',
			handleSecondaryAction
		);

		// Fade out and remove
		$( container ).fadeOut( 200, () => {
			container.remove();
		} );
	};

	// Handle ESC key
	const handleEscKey = ( event: JQuery.KeyDownEvent ) => {
		if ( event.key === 'Escape' ) {
			close();
		}
	};

	// Handle click outside
	const handleClickOutside = ( event: JQuery.MouseDownEvent ) => {
		const $target = $( event.target );
		if ( $target.hasClass( 'pum-modal-overlay' ) ) {
			close();
		}
	};

	// Handle primary action
	const handlePrimaryAction = () => {
		if ( options.actions?.primary?.onClick ) {
			options.actions.primary.onClick();
		}
		close();
	};

	// Handle secondary action
	const handleSecondaryAction = () => {
		if ( options.actions?.secondary?.onClick ) {
			options.actions.secondary.onClick();
		}
		close();
	};

	// Bind all event handlers
	$( document ).on( 'keydown', handleEscKey );
	$( document ).on( 'mousedown', handleClickOutside );
	$( '.pum-modal-close, .pum-modal-cancel', container ).on( 'click', close );

	if ( options.actions?.primary ) {
		$( '.pum-modal-proceed', container ).on( 'click', handlePrimaryAction );
	}

	if ( options.actions?.secondary ) {
		$( '.pum-modal-cancel', container ).on(
			'click',
			handleSecondaryAction
		);
	}

	return container;
}

export class AdminBar {
	private readonly text: AdminBarText;
	private readonly defaultText: AdminBarText = {
		instructions: __(
			'After clicking ok, click the element you want a selector for.',
			'popup-maker'
		),
		results: __( 'Selector', 'popup-maker' ),
		copy: __( 'Copy', 'popup-maker' ),
		close: __( 'Close', 'popup-maker' ),
		copied: __( 'Copied to clipboard', 'popup-maker' ),
	};

	constructor() {
		this.text = popupMakerAdminBar?.i18n || this.defaultText;
		this.initialize();
	}

	private async copyToClipboard( text: string ): Promise< boolean > {
		try {
			if ( navigator.clipboard && window.isSecureContext ) {
				await navigator.clipboard.writeText( text );
				return true;
			}
			return this.fallbackCopyToClipboard( text );
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( 'Failed to copy text:', error );
			return this.fallbackCopyToClipboard( text );
		}
	}

	private fallbackCopyToClipboard( text: string ): boolean {
		const textarea = document.createElement( 'textarea' );
		try {
			textarea.value = text;
			textarea.style.position = 'fixed';
			textarea.style.opacity = '0';
			document.body.appendChild( textarea );
			textarea.select();
			const success = document.execCommand( 'copy' );
			return success;
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( 'Fallback copy failed:', error );
			return false;
		} finally {
			document.body.removeChild( textarea );
		}
	}

	private showCopiedNotice( modalBody: JQuery ): void {
		const notice = document.createElement( 'div' );
		notice.classList.add( 'notice', 'notice-success' );
		notice.innerHTML = `<p>${ this.text.copied }</p>`;
		modalBody.append( notice );

		setTimeout( () => {
			$( notice ).fadeOut( 200, () => {
				$( notice ).remove();
			} );
		}, 3000 );
	}

	private handleSelectorClick = async (
		event: JQuery.ClickEvent
	): Promise< void > => {
		event.preventDefault();
		event.stopPropagation();

		const selector = finder( event.target, {
			seedMinLength: 3,
			// optimizedMinLength: 2,
		} );
		const container = createModal( {
			title: this.text.results,
			content: `
				<div class="pum-modal-copy">
					<p>${ selector }</p>
					<button class="copy-clipboard button button-secondary">
						<span class="dashicons dashicons-clipboard"></span>
						<span class="screen-reader-text">${ this.text.copy }</span>
					</button>
				</div>
			`,
			closeText: this.text.close,
		} );

		// Setup copy handler
		$( '.copy-clipboard', container ).on( 'click', async () => {
			const success = await this.copyToClipboard( selector );
			if ( success ) {
				this.showCopiedNotice( $( '.pum-modal-body', container ) );
			}
		} );
	};

	private initialize(): void {
		const { PUM } = window;

		$( document ).on(
			'click',
			'#wp-admin-bar-pum-get-selector',
			( event: JQuery.ClickEvent ) => {
				event.preventDefault();
				event.stopPropagation();

				createModal( {
					title: __( 'Get Element Selector', 'popup-maker' ),
					content: this.text.instructions,
					closeText: this.text.close,
					actions: {
						primary: {
							text: __( 'Start Selection', 'popup-maker' ),
							onClick: () => {
								// Add small delay to prevent immediate trigger
								setTimeout( () => {
									$( document ).one(
										'click',
										this.handleSelectorClick
									);
								}, 250 );
							},
						},
						secondary: {
							text: __( 'Cancel', 'popup-maker' ),
							onClick: () => {}, // No-op since close is handled automatically
						},
					},
				} );
			}
		);

		$( document ).on(
			'click',
			'.pum-toolbar-action',
			( event: JQuery.ClickEvent ) => {
				event.preventDefault();
				event.stopPropagation();

				const href = $( event.target ).attr( 'href' );

				if ( ! href ) {
					return;
				}

				const [ action, popupId ] = href
					.split( '__' )[ 1 ]
					.split( '--' );

				switch ( action ) {
					case 'open':
						PUM.open( popupId );
						break;
					case 'close':
						PUM.close( popupId );
						break;
					case 'check-conditions':
						createModal( {
							title: __( 'Conditions Check', 'popup-maker' ),
							content: PUM.checkConditions( popupId )
								? __(
										'The conditions were met.',
										'popup-maker'
								  )
								: __(
										'The conditions were not met.',
										'popup-maker'
								  ),
						} );
						break;
					case 'reset-cookies':
						PUM.clearCookies( popupId );
						createModal( {
							title: __( 'Cookies Reset', 'popup-maker' ),
							content: __(
								'The cookies were reset successfully.',
								'popup-maker'
							),
						} );
						break;
				}
			}
		);
	}
}
