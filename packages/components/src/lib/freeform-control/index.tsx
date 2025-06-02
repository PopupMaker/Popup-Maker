import './editor.scss';

import clsx from 'clsx';

import { __ } from '@popup-maker/i18n';
import { BaseControl } from '@wordpress/components';
import { useEffect, useRef } from '@wordpress/element';
import { F10, isKeyboardEvent } from '@wordpress/keycodes';
import { debounce, useInstanceId } from '@wordpress/compose';

import { noop } from '@popup-maker/utils';

type Props< T extends string > = {
	label?: string | React.ReactNode;
	placeholder?: string;
	value: T;
	onChange: ( value: T ) => void;
	className?: clsx.ClassValue;
	minHeight?: number;
};

declare global {
	interface Window {
		tinymce: any;
		wpEditorL10n: {
			tinymce: {
				baseURL: string;
				suffix: string;
				settings: any;
			};
		};
		wp: {
			oldEditor: {
				initialize: ( id: string, settings: any ) => void;
				remove: ( id: string ) => void;
			};
			blocks?: unknown;
		};
	}
}

const {
	wp,
	tinymce,
	wpEditorL10n = {
		tinymce: {
			baseURL: '',
			suffix: '',
			settings: {},
		},
	},
} = window;

// function isTmceEmpty( editor ) {
// 	// When tinyMce is empty the content seems to be:
// 	// <p><br data-mce-bogus="1"></p>
// 	// avoid expensive checks for large documents
// 	const body = editor.getBody();
// 	if ( body.childNodes.length > 1 ) {
// 		return false;
// 	} else if ( body.childNodes.length === 0 ) {
// 		return true;
// 	}
// 	if ( body.childNodes[ 0 ].childNodes.length > 1 ) {
// 		return false;
// 	}
// 	return /^\n?$/.test( body.innerText || body.textContent );
// }

const FreeFormEditControl = < T extends string >( props: Props< T > ) => {
	const instanceId = useInstanceId( FreeFormEditControl );
	const {
		label,
		// placeholder,
		value,
		onChange = noop,
		className,
		minHeight = 100,
	} = props;
	const didMount = useRef( false );

	/**
	 * Resets the editor content to the value prop.
	 */
	useEffect( () => {
		if ( ! didMount.current ) {
			return;
		}

		const editor = tinymce.get( `editor-${ instanceId }` );
		const currentContent = editor?.getContent();

		if ( currentContent !== value ) {
			editor.setContent( value || '' );
		}
	}, [ value, instanceId ] );

	useEffect( () => {
		const { baseURL, suffix } = wpEditorL10n.tinymce;

		didMount.current = true;

		tinymce.EditorManager.overrideDefaults( {
			base_url: baseURL,
			suffix,
		} );

		function onSetup( editor ) {
			let bookmark;

			if ( value ) {
				editor.on( 'loadContent', () => editor.setContent( value ) );
			}

			editor.on( 'blur', () => {
				bookmark = editor.selection.getBookmark( 2, true );
				// There is an issue with Chrome and the editor.focus call in core at https://core.trac.wordpress.org/browser/trunk/src/js/_enqueues/lib/link.js#L451.
				// This causes a scroll to the top of editor content on return from some content updating dialogs so tracking
				// scroll position until this is fixed in core.
				const scrollContainer = document.querySelector(
					'.interface-interface-skeleton__content'
				);
				const scrollPosition = scrollContainer?.scrollTop;

				// Only update attributes if we aren't multi-selecting blocks.
				// Updating during multi-selection can overwrite attributes of other blocks.
				// if ( ! getMultiSelectedBlockinstanceIds()?.length ) {
				onChange( editor.getContent() );
				// }

				editor.once( 'focus', () => {
					if ( bookmark ) {
						editor.selection.moveToBookmark( bookmark );
						if (
							scrollContainer &&
							scrollContainer?.scrollTop !== scrollPosition
						) {
							scrollContainer.scrollTop = scrollPosition || 0;
						}
					}
				} );

				return false;
			} );

			editor.on( 'mousedown touchstart', () => {
				bookmark = null;
			} );

			const debouncedOnChange = debounce( () => {
				const newValue = editor.getContent();

				if ( newValue !== editor._lastChange ) {
					editor._lastChange = newValue;
					onChange( newValue );
				}
			}, 250 );

			editor.on( 'Paste Change input Undo Redo', debouncedOnChange );

			// We need to cancel the debounce call because when we remove
			// the editor (onUnmount) this callback is executed in
			// another tick. This results in setting the content to empty.
			editor.on( 'remove', debouncedOnChange.cancel );

			editor.on( 'keydown', ( event ) => {
				if ( isKeyboardEvent.primary( event, 'z' ) ) {
					// Prevent the gutenberg undo kicking in so TinyMCE undo stack works as expected.
					event.stopPropagation();
				}

				// if (
				// 	( event.keyCode === BACKSPACE ||
				// 		event.keyCode === DELETE ) &&
				// 	isTmceEmpty( editor )
				// ) {
				// 	// Delete the block.
				// 	onReplace( [] );
				// 	event.preventDefault();
				// 	event.stopImmediatePropagation();
				// }

				const { altKey } = event;
				/*
				 * Prevent Mousetrap from kicking in: TinyMCE already uses its own
				 * `alt+f10` shortcut to focus its toolbar.
				 */
				if ( altKey && event.keyCode === F10 ) {
					event.stopPropagation();
				}
			} );

			editor.on( 'init', () => {
				const rootNode = editor.getBody();
				// Create the toolbar by refocussing the editor.

				if ( rootNode.ownerDocument.activeElement === rootNode ) {
					rootNode.blur();
					editor.focus();
				}
			} );
		}

		function initialize() {
			const { settings } = wpEditorL10n.tinymce;
			wp.oldEditor.initialize( `editor-${ instanceId }`, {
				tinymce: {
					...settings,
					inline: true,
					// toolbar_persist: true,
					content_css: false,
					fixed_toolbar_container: `#toolbar-${ instanceId }`,
					setup: onSetup,
					// toolbar:
					// 	'undo redo | bold italic underline strikethrough | fontselect fontsizeselect formatselect | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist checklist | forecolor backcolor casechange permanentpen formatpainter removeformat | pagebreak | charmap emoticons | fullscreen  preview save print | insertfile image media pageembed template link anchor codesample | a11ycheck ltr rtl | showcomments addcomment',
				},
			} );
		}

		function onReadyStateChange() {
			if ( document.readyState === 'complete' ) {
				initialize();
			}
		}

		if ( document.readyState === 'complete' ) {
			initialize();
		} else {
			document.addEventListener( 'readystatechange', onReadyStateChange );
		}

		return () => {
			document.removeEventListener(
				'readystatechange',
				onReadyStateChange
			);
			wp.oldEditor.remove( `editor-${ instanceId }` );
		};
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	function focus() {
		const editor = tinymce.get( `editor-${ instanceId }` );
		if ( editor ) {
			editor.focus();
		}
	}

	function onToolbarKeyDown( event ) {
		// Prevent WritingFlow from kicking in and allow arrows navigation on the toolbar.
		event.stopPropagation();
		// Prevent Mousetrap from moving focus to the top toolbar when pressing `alt+f10` on this block toolbar.
		event.nativeEvent.stopImmediatePropagation();
	}

	// Disable reasons:
	//
	// jsx-a11y/no-static-element-interactions
	//  - the toolbar itself is non-interactive, but must capture events
	//    from the KeyboardShortcuts component to stop their propagation.

	/* eslint-disable jsx-a11y/no-static-element-interactions */

	return (
		<BaseControl
			id={ `freeform-edit-control-${ instanceId }` }
			label={ label }
			className={ clsx( [
				'component-freeform-edit-control',
				className,
			] ) }
			__nextHasNoMarginBottom
		>
			<div
				key="toolbar"
				id={ `toolbar-${ instanceId }` }
				className="block-library-classic__toolbar"
				onClick={ focus }
				onKeyDown={ onToolbarKeyDown }
				data-placeholder={ __(
					'Click here to edit this text.',
					'popup-maker'
				) }
			/>
			<div
				key="editor"
				id={ `editor-${ instanceId }` }
				style={ { minHeight } }
				className="wp-block-freeform block-library-rich-text__tinymce"
			/>
		</BaseControl>
	);
	/* eslint-enable jsx-a11y/no-static-element-interactions */
};

export default FreeFormEditControl;
