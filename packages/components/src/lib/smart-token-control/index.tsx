import classNames, { Argument as classNamesArg } from 'classnames';

import {
	Button,
	KeyboardShortcuts,
	Popover,
	BaseControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useInstanceId } from '@wordpress/compose';
import { close } from '@wordpress/icons';
import { forwardRef, useEffect, useRef, useState } from '@wordpress/element';
import { clamp, noop } from '@popup-maker/utils';

import './editor.scss';

import type { ForwardedRef } from 'react';
import type { KeyboardShortcutsProps } from '@wordpress/components/build-types/keyboard-shortcuts/types';

export type Token =
	| string
	| {
			value: string;
			[ key: string ]: any;
	  };

export type Props< T extends Token = Token > = {
	id?: string;
	value: T[];
	onChange: ( value: T[] ) => void;
	label?: string | JSX.Element;
	placeholder?: string;
	className?: classNamesArg;
	classes?: {
		container?: string;
		popover?: string;
		inputContainer?: string;
		tokens?: string;
		token?: string;
		tokenLabel?: string;
		tokenRemove?: string;
		textInput?: string;
		toggleSuggestions?: string;
		suggestions?: string;
		suggestion?: string;
	};
	multiple?: boolean;
	suggestions: string[];
	closeOnSelect?: boolean;
	renderToken?: ( token: T ) => JSX.Element | string;

	/**
	 * Render a suggestion.
	 *
	 * @param {string} suggestion suggestion to be rendered
	 * @return {JSX.Element | string}
	 */
	renderSuggestion?: ( suggestion: string ) => JSX.Element | string;

	/**
	 * Transform the value before adding it to the value array and calling onChange.
	 *
	 * @param {string} value string to be transformed
	 * @return {Token}
	 */
	saveTransform?: ( value: string ) => T;

	/**
	 * When the search input changes.
	 *
	 * @param {string} value selected suggestion
	 * @return {void}
	 */
	onInputChange?: ( value: string ) => void;
	hideLabelFromVision?: boolean;
	tokenOnComma?: boolean;
	extraKeyboardShortcuts?: KeyboardShortcutsProps[ 'shortcuts' ];
	messages?: {
		searchTokens?: string;
		noSuggestions?: string;
		removeToken?: string;
	};
};

type State = {
	inputText: string;
	isFocused: boolean;
	selectedSuggestion: number;
	popoverOpen: boolean;
	refocus: boolean;
};

const defaultClasses: Required< Props[ 'classes' ] > = {
	container: 'component-smart-token-control',
	popover: 'component-smart-token-control__suggestions-popover',
	inputContainer: 'component-smart-token-control__input',
	tokens: 'component-smart-token-control__tokens',
	token: 'component-smart-token-control__token',
	tokenLabel: 'component-smart-token-control__token-label',
	tokenRemove: 'component-smart-token-control__token-remove',
	textInput: 'component-smart-token-control__text-input',
	toggleSuggestions: 'component-smart-token-control__toggle',
	suggestions: 'component-smart-token-control__suggestions',
	suggestion: 'component-smart-token-control__suggestion',
};

const SmartTokenControl = < T extends Token = string >(
	{
		id,
		value,
		onChange,
		label,
		placeholder = __( 'Enter a value', 'popup-maker' ),
		className,
		tokenOnComma = false,
		classes = defaultClasses,
		renderToken = ( token ) => (
			<>{ typeof token === 'string' ? token : token.item }</>
		),
		renderSuggestion = ( suggestion: string ) => <>{ suggestion }</>,
		onInputChange = noop,
		saveTransform = ( newValue: string ) => newValue as T,
		closeOnSelect = false,
		hideLabelFromVision = false,
		extraKeyboardShortcuts = {},
		multiple = false,
		suggestions,
		messages = {
			searchTokens: __( 'Search', 'popup-maker' ),
			noSuggestions: __( 'No suggestions', 'popup-maker' ),
			removeToken: __( 'Remove token', 'popup-maker' ),
		},
	}: Props< T >,
	ref: ForwardedRef< Element >
) => {
	const elClasses = { ...defaultClasses, ...classes };

	const minQueryLength = 1;
	const instanceId = useInstanceId( SmartTokenControl );
	const wrapperRef = useRef< Element | null >( null );
	const inputRef = useRef< HTMLInputElement >( null );
	const selectedRef = useRef< HTMLDivElement | null >( null );

	const [ state, setState ] = useState< State >( {
		inputText: '',
		isFocused: false,
		selectedSuggestion: -1,
		popoverOpen: false,
		refocus: false,
	} );

	const { inputText, isFocused, selectedSuggestion, popoverOpen } = state;

	/**
	 * Get value from token.
	 *
	 * @param {Token} token token to get value from
	 * @return {string} value of token
	 */
	function getTokenValue( token: Token ): string {
		if ( 'object' === typeof token ) {
			return token.value;
		}

		return token;
	}

	/**
	 * Check if value contains token.
	 *
	 * @param {Token} token token to check
	 * @return {boolean} true if value contains token
	 */
	function valueContainsToken( token: Token ): boolean {
		return value.some( ( item ) => {
			return getTokenValue( token ) === getTokenValue( item );
		} );
	}

	/**
	 * Add new tokens to value.
	 *
	 * @param {string[]} tokens tokens to add
	 */
	function addNewTokens( tokens: string[] ) {
		const tokensToAdd = [
			...new Set(
				tokens
					.map( saveTransform )
					.filter( Boolean )
					.filter( ( token ) => ! valueContainsToken( token ) )
			),
		];

		if ( tokensToAdd.length > 0 ) {
			onChange( [ ...value, ...tokensToAdd ] );
		}
	}

	/**
	 * Add a new token to value.
	 *
	 * @param {string} token token to add
	 */
	function addNewToken( token: string ) {
		addNewTokens( [ token ] );

		setState( {
			...state,
			inputText: '',
			popoverOpen: closeOnSelect ? false : popoverOpen,
		} );
	}

	/**
	 * Delete a token from value.
	 *
	 * @param {Token} token token to delete
	 */
	function deleteToken( token: Token ) {
		setState( {
			...state,
			refocus: true,
		} );

		onChange(
			value.filter( ( item ) => {
				return getTokenValue( item ) !== getTokenValue( token );
			} )
		);
	}

	/**
	 * Update the input text.
	 *
	 * @param {string} text new input text
	 */
	const udpateInputText = ( text: string ) => {
		setState( {
			...state,
			inputText: text,
			popoverOpen: text.length >= minQueryLength,
		} );

		onInputChange( text );
	};

	/**
	 * Set the selected suggestion.
	 *
	 * @param {number} i index of selected suggestion
	 */
	const setSelectedSuggestion = ( i: number ) =>
		setState( {
			...state,
			selectedSuggestion: i,
		} );

	const maxSelectionIndex = suggestions.length;

	// Check if selectedSuggestion is higher than list length.
	// If it is higher, set it to 0 as they have new query results.
	// This prevents an extra state change.
	const currentIndex =
		selectedSuggestion > maxSelectionIndex ? 0 : selectedSuggestion;

	/**
	 * Ensure selected suggestion is visible in a scrollable list.
	 */
	useEffect( () => {
		setTimeout( () => {
			if ( selectedRef.current ) {
				selectedRef.current.scrollIntoView();
			}
		}, 25 );
	}, [ selectedSuggestion, popoverOpen ] );

	useEffect( () => {
		if ( state.refocus ) {
			setState( {
				...state,
				refocus: false,
			} );

			inputRef.current?.focus();
		}
	}, [ state, state.refocus ] );

	const keyboardShortcuts = {
		up: ( event: KeyboardEvent ) => {
			event.preventDefault();
			setState( {
				...state,
				// W3 Aria says to open the popover if query text is empty on up keypress.
				popoverOpen:
					inputText.length === 0 && ! popoverOpen
						? true
						: popoverOpen,
				// When at the top, skip to the last rule that isn't the upsell.
				selectedSuggestion: clamp(
					currentIndex - 1 >= 0
						? currentIndex - 1
						: maxSelectionIndex,
					0,
					maxSelectionIndex
				),
			} );
		},
		down: ( event: KeyboardEvent ) => {
			event.preventDefault();
			setState( {
				...state,
				// W3 Aria says to open the popover if query text is empty on down keypress.
				popoverOpen:
					inputText.length === 0 && ! popoverOpen
						? true
						: popoverOpen,
				// When at the top, skip to the last rule that isn't the upsell.
				selectedSuggestion: clamp(
					currentIndex + 1 <= maxSelectionIndex
						? currentIndex + 1
						: 0,
					0,
					maxSelectionIndex
				),
			} );
		},
		// Show popover.
		'alt+down': () =>
			setState( {
				...state,
				popoverOpen: true,
			} ),
		// If selected suggestion, choose it, otherwise close popover.
		enter: () => {
			if ( selectedSuggestion === -1 ) {
				return setState( {
					...state,
					popoverOpen: false,
				} );
			}

			addNewToken( suggestions[ currentIndex ] );
		},
		// Close the popover.
		escape: ( event: KeyboardEvent ) => {
			event.preventDefault();
			event.stopPropagation();
			setState( {
				...state,
				selectedSuggestion: -1,
				popoverOpen: false,
			} );
		},
		// Generate a token from the input text on comma.
		',': ( event: KeyboardEvent ) => {
			if ( ! tokenOnComma ) {
				return;
			}

			event.preventDefault();

			if ( inputText.length === 0 ) {
				return;
			}

			addNewToken( inputText );
		},
		...extraKeyboardShortcuts,
	};

	return (
		<KeyboardShortcuts shortcuts={ keyboardShortcuts }>
			<div
				id={
					id
						? `${ id }-wrapper`
						: `component-smart-token-control-${ instanceId }-wrapper`
				}
				className={ classNames( [
					elClasses.container,
					isFocused && 'is-focused',
					className,
				] ) }
				ref={ ( _ref ) => {
					wrapperRef.current = _ref;
					if ( ref && typeof ref === 'object' ) {
						ref.current = _ref;
					}
				} }
				onBlur={ ( event: FocusEventInit ) => {
					// If the blur event is coming from the popover, don't close it.
					if ( event.relatedTarget ) {
						const popover = event.relatedTarget as HTMLElement;
						if ( popover.classList.contains( elClasses.popover ) ) {
							return;
						}
					}

					setState( {
						...state,
						isFocused: false,
						popoverOpen: false,
					} );
				} }
			>
				<BaseControl
					id={
						id
							? id
							: `component-smart-token-control-${ instanceId }`
					}
					label={ label }
					hideLabelFromVision={ hideLabelFromVision }
				>
					<div
						className={ classNames( [
							elClasses.inputContainer,
							! multiple && value.length > 0 && 'input-disabled',
						] ) }
					>
						{ value.length > 0 && (
							<div className={ elClasses.tokens }>
								{ value.map( ( token ) => (
									<div
										className={ elClasses.token }
										key={ getTokenValue( token ) }
									>
										<div className={ elClasses.tokenLabel }>
											{ renderToken( token ) }
										</div>
										<Button
											className={ elClasses.tokenRemove }
											label={ messages.removeToken }
											icon={ close }
											onClick={ () =>
												deleteToken( token )
											}
										/>
									</div>
								) ) }
							</div>
						) }
						<input
							id={
								id
									? id
									: `component-smart-token-control-${ instanceId }`
							}
							type="text"
							className={ classNames( [ elClasses.textInput ] ) }
							placeholder={ placeholder }
							disabled={ ! multiple && value.length > 0 }
							ref={ inputRef }
							value={ inputText ?? '' }
							onChange={ ( event ) =>
								udpateInputText( event.target.value )
							}
							autoComplete="off"
							aria-autocomplete="list"
							aria-controls={
								id
									? `${ id }-listbox`
									: `${ instanceId }-listbox`
							}
							aria-activedescendant={ `sug-${ currentIndex }` }
							onFocus={ () => {
								setState( {
									...state,
									isFocused: true,
									popoverOpen:
										inputText.length >= minQueryLength,
								} );
							} }
							onClick={ () => {
								if ( ! popoverOpen ) {
									setState( {
										...state,
										popoverOpen: suggestions.length > 0,
									} );
								}
							} }
							onBlur={ ( event: FocusEventInit ) => {
								// If the blur event is coming from the popover, don't close it.
								const popover =
									event.relatedTarget as HTMLElement;
								if (
									popover &&
									popover.classList.contains(
										elClasses.popover
									)
								) {
									return;
								}

								setState( {
									...state,
									isFocused: false,
									popoverOpen: false,
								} );
							} }
						/>
					</div>
				</BaseControl>
				{ popoverOpen && (
					<Popover
						focusOnMount={ false }
						onClose={ () => setSelectedSuggestion( -1 ) }
						position="bottom right"
						anchor={ inputRef.current }
						className={ elClasses.popover }
					>
						<div
							className={ elClasses.suggestions }
							style={ {
								// Allows the popover to assume full width.
								width: inputRef.current?.clientWidth,
							} }
						>
							{ suggestions.length ? (
								suggestions.map( ( suggestion, i ) => (
									<div
										key={ i }
										id={ `sug-${ i }` }
										className={ classNames( [
											elClasses.suggestion,
											i === currentIndex &&
												'is-currently-highlighted',
											valueContainsToken( suggestion ) &&
												'is-selected',
										] ) }
										ref={
											i === currentIndex
												? selectedRef
												: undefined
										}
										onFocus={ () => {
											setSelectedSuggestion( i );
										} }
										onMouseDown={ ( event ) => {
											event.preventDefault();
											addNewToken( suggestions[ i ] );
										} }
										role="option"
										tabIndex={ i }
										aria-selected={ i === currentIndex }
									>
										{ renderSuggestion( suggestion ) }
									</div>
								) )
							) : (
								<div>{ messages.noSuggestions }</div>
							) }
						</div>
					</Popover>
				) }
			</div>
		</KeyboardShortcuts>
	);
};

export default forwardRef( SmartTokenControl ) as < T extends Token = string >(
	p: Props< T > & { ref?: ForwardedRef< Element > }
) => React.ReactElement;
