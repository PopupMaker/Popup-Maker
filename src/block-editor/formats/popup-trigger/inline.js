/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, createRef, useMemo } from '@wordpress/element';
import { ToggleControl, withNotices, withSpokenMessages } from '@wordpress/components';
import { BACKSPACE, DOWN, ENTER, LEFT, RIGHT, UP } from '@wordpress/keycodes';
import { getRectangleFromRange } from '@wordpress/dom';
import { applyFormat, create, insert, isCollapsed } from '@wordpress/rich-text';
/**
 * Internal dependencies
 */
import { createTriggerFormat } from './utils';
import TriggerPopover from '../../components/trigger-popover';
import PopupTriggerEditor from '../../components/trigger-popover/popup-trigger-editor';
import PopupTriggerViewer from '../../components/trigger-popover/popup-trigger-viewer';

const stopKeyPropagation = ( event ) => event.stopPropagation();

function isShowingInput( props, state ) {
	return props.addingTrigger || state.editTrigger;
}

const TriggerPopoverAtText = ( { isActive, addingTrigger, value, ...props } ) => {
	const anchorRect = useMemo( () => {
		const selection = window.getSelection();
		const range = selection.rangeCount > 0 ? selection.getRangeAt( 0 ) : null;
		if ( ! range ) {
			return;
		}

		if ( addingTrigger ) {
			return getRectangleFromRange( range );
		}

		let element = range.startContainer;

		// If the caret is right before the element, select the next element.
		element = element.nextElementSibling || element;

		while ( element.nodeType !== window.Node.ELEMENT_NODE ) {
			element = element.parentNode;
		}

		const closest = element.closest( 'span.popup-trigger' );
		if ( closest ) {
			return closest.getBoundingClientRect();
		}
	}, [ isActive, addingTrigger, value.start, value.end ] );

	if ( ! anchorRect ) {
		return null;
	}

	return <TriggerPopover anchorRect={ anchorRect } { ...props } />;
};

/**
 * Generates a Popover with a select field to choose a popup, inline with the Rich Text editors.
 */
class InlinePopupTriggerUI extends Component {
	constructor() {
		super( ...arguments );

		this.editTrigger = this.editTrigger.bind( this );
		this.setPopupID = this.setPopupID.bind( this );
		this.setDoDefault = this.setDoDefault.bind( this );
		this.onFocusOutside = this.onFocusOutside.bind( this );
		this.submitTrigger = this.submitTrigger.bind( this );
		this.resetState = this.resetState.bind( this );

		this.state = {
			doDefault: false,
			popupId: '',
		};
	}

	static getDerivedStateFromProps( props, state ) {
		const { activeAttributes } = props;
		const { popupId = '' } = activeAttributes;
		let { doDefault = false } = activeAttributes;

		// Convert string value to boolean for comparison.
		if ( window._.isString( doDefault ) ) {
			doDefault = '1' === doDefault;
		}

		if ( ! isShowingInput( props, state ) ) {
			const update = {};
			if ( popupId !== state.popupId ) {
				update.popupId = popupId;
			}

			if ( doDefault !== state.doDefault ) {
				update.doDefault = doDefault;
			}
			return Object.keys( update ).length ? update : null;
		}

		return null;
	}

	onKeyDown( event ) {
		if ( [ LEFT, DOWN, RIGHT, UP, BACKSPACE, ENTER ].indexOf( event.keyCode ) > -1 ) {
			// Stop the key event from propagating up to ObserveTyping.startTypingInTextField.
			event.stopPropagation();
		}
	}

	setPopupID( popupId ) {
		const { noticeOperations } = this.props;

		noticeOperations.removeNotice( 'missingPopupId' );

		if ( '' === popupId ) {
			noticeOperations.createNotice( {
				id: 'missingPopupId',
				status: 'error',
				content: __( 'Choose a popup or the trigger won\'t function.', 'popup-maker' ),
			} );
		}

		this.setState( { popupId } );
	}

	setDoDefault( doDefault ) {
		const { activeAttributes: { popupId = 0 }, value, onChange } = this.props;

		this.setState( { doDefault } );

		// Apply now if URL is not being edited.
		if ( ! isShowingInput( this.props, this.state ) ) {
			onChange( applyFormat( value, createTriggerFormat( {
				popupId,
				doDefault,
			} ) ) );
		}
	}

	editTrigger( event ) {
		this.setState( { editTrigger: true } );
		event.preventDefault();
	}

	submitTrigger( event ) {
		const { isActive, value, onChange, speak } = this.props;
		const { popupId, doDefault } = this.state;
		const format = createTriggerFormat( {
			popupId,
			doDefault,
		} );

		event.preventDefault();

		if ( isCollapsed( value ) && ! isActive ) {
			const toInsert = applyFormat( create( { text: __( 'Open Popup', 'popup-maker' ) } ), format, 0, __( 'Open Popup', 'popup-maker' ).length );
			onChange( insert( value, toInsert ) );
		} else {
			onChange( applyFormat( value, format ) );
		}

		this.resetState();

		if ( isActive ) {
			speak( __( 'Trigger edited.', 'popup-maker' ), 'assertive' );
		} else {
			speak( __( 'Trigger inserted.', 'popup-maker' ), 'assertive' );
		}
	}

	onFocusOutside() {
		this.resetState();
	}

	resetState() {
		this.props.stopAddingTrigger();
		this.setState( { editTrigger: false } );
	}

	render() {
		/**
		 * @constant {boolean} isActive              True when the cursor is inside an existing trigger
		 * @constant {boolean} addingTrigger         True when the user has clicked the add trigger button
		 * @constant {Object}  activeAttributes      Object containing the current attribute values for the selected text.
		 * @constant {Object}  value                 Object containing the current rich text selection object containing position & formats.
		 * @constant {Object}  value.activeFormats   Array of registered & active WPFormat objects.
		 * @constant {number}  value.formats         ?? Array of format history for the active text.
		 * @constant {number}  value.start           Start offset of selected text
		 * @constant {number}  value.end             End offset of selected text.
		 * @constant {string}  value.text            Selected text.
		 */
		const { isActive, /* activeAttributes, */ addingTrigger, value, noticeUI } = this.props;

		// If the user is not adding a trigger from the toolbar or actively inside render nothing.
		if ( ! isActive && ! addingTrigger ) {
			return null;
		}

		const { popupId, doDefault } = this.state;
		const showInput = isShowingInput( this.props, this.state );

		return (
			<TriggerPopoverAtText
				value={ value }
				isActive={ isActive }
				addingTrigger={ addingTrigger }
				onFocusOutside={ this.onFocusOutside }
				onClose={ this.resetState }
				noticeUI={ noticeUI }
				focusOnMount={ showInput ? 'firstElement' : false }
				renderSettings={ () => (
					<ToggleControl
						label={ __( 'Do default browser action?', 'popup-maker' ) }
						checked={ doDefault }
						onChange={ this.setDoDefault }
					/>
				) }
			>
				{ showInput ? (
					<PopupTriggerEditor
						className="editor-format-toolbar__link-container-content block-editor-format-toolbar__link-container-content"
						value={ popupId }
						onChangeInputValue={ this.setPopupID }
						onKeyDown={ this.onKeyDown }
						onKeyPress={ stopKeyPropagation }
						onSubmit={ this.submitTrigger }
					/>
				) : (
					<PopupTriggerViewer
						className="editor-format-toolbar__link-container-content block-editor-format-toolbar__link-container-content"
						onKeyPress={ stopKeyPropagation }
						popupId={ popupId }
						onEditLinkClick={ this.editTrigger }
						// linkClassName=""
					/>
				) }
			</TriggerPopoverAtText>
		);
	}
}

export default withSpokenMessages( withNotices( InlinePopupTriggerUI ) );
