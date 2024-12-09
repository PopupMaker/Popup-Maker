import $ from 'jquery';
import { __ } from '@wordpress/i18n';
import { finder } from '@medv/finder';
import { AdminBarText } from './types';

declare const popupMakerAdminBar:
	| {
			i18n: AdminBarText;
	  }
	| undefined;

interface ModalElements {
	container: HTMLElement;
	closeButton: JQuery;
	copyButton: JQuery;
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

	private createModal( selector: string ): ModalElements {
		const container = document.createElement( 'div' );
		container.innerHTML = `<div class="pum-modal">
			<div class="pum-modal-content">
				<div class="pum-modal-header">
					<div class="pum-logo"></div>
					<h4>${ this.text.results }</h4>
					<button class="pum-modal-close">
						<span class="dashicons dashicons-no-alt"></span>
						<span class="screen-reader-text">${ this.text.close }</span>
					</button>
				</div>
				<div class="pum-modal-body">
					<div class="pum-modal-copy">
						<p>${ selector }</p>
						<button class="copy-clipboard button button-secondary">
							<span class="dashicons dashicons-clipboard"></span>
							<span class="screen-reader-text">${ this.text.copy }</span>
						</button>
					</div>
				</div>
			</div>
		</div>`;

		document.body.appendChild( container );

		return {
			container,
			closeButton: $( '.pum-modal-close', container ),
			copyButton: $( '.copy-clipboard', container ),
		};
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

		const selector = finder( event.target );
		const modal = this.createModal( selector );

		modal.closeButton.on( 'click', () => {
			$( modal.container ).fadeOut( 200, () => {
				modal.container.remove();
			} );
		} );

		modal.copyButton.on( 'click', async () => {
			const success = await this.copyToClipboard( selector );
			if ( success ) {
				this.showCopiedNotice(
					$( '.pum-modal-body', modal.container )
				);
			}
		} );
	};

	private initialize(): void {
		$( document ).on(
			'click',
			'#wp-admin-bar-pum-get-selector',
			( event: JQuery.ClickEvent ) => {
				// eslint-disable-next-line no-alert
				if ( ! confirm( this.text.instructions ) ) {
					return;
				}

				event.preventDefault();
				event.stopPropagation();

				$( document ).one( 'click', this.handleSelectorClick );
			}
		);
	}
}
