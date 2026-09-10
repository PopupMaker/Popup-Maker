import './editor.scss';

import {
	RichTextToolbarButton,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import {
	Button,
	Dropdown,
	Popover,
	SearchControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { useLayoutEffect, useMemo, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { linkOff, pencil } from '@wordpress/icons';
import { registerFormatType, type RichTextValue } from '@wordpress/rich-text';
import {
	contentTokenGroups,
	contentTokens,
	getContentTokenPreview,
	isContentTokenAvailable,
} from '../../content-tokens';
import type {
	ContentTokenContext,
	ContentTokenDefinition,
	ContentTokenGroup,
} from '../../content-tokens';
import {
	applyContentToken,
	ATOMIC_FORMAT,
	EDITABLE_FORMAT,
	getContentTokenSelection,
	removeContentToken,
	unlinkContentToken,
} from './operations';

interface FormatEditProps {
	isActive: boolean;
	isObjectActive: boolean;
	activeObjectAttributes?: Record< string, string >;
	value: RichTextValue;
	onChange: ( value: RichTextValue ) => void;
	onFocus?: () => void;
	contentRef?: { current: HTMLElement | null };
}

interface PickerProps {
	tokens: ContentTokenDefinition[];
	groups: ContentTokenGroup[];
	selectedId?: string;
	onInsert: ( token: ContentTokenDefinition ) => void;
	onClose: () => void;
}
const humanize = ( value: string ) =>
	value
		.replace( /[-_]+/g, ' ' )
		.replace( /\b\w/g, ( character ) => character.toLocaleUpperCase() );

const ContentTokenPicker = ( {
	tokens,
	groups,
	selectedId,
	onInsert,
	onClose,
}: PickerProps ) => {
	const [ query, setQuery ] = useState( '' );
	const groupMap = useMemo(
		() => new Map( groups.map( ( group ) => [ group.id, group ] ) ),
		[ groups ]
	);
	const visible = useMemo( () => {
		const needle = query.trim().toLocaleLowerCase();
		return needle
			? tokens.filter( ( token ) => {
					const group = groupMap.get( token.group ?? 'general' );
					return [
						token.label,
						token.description,
						token.type,
						token.id,
						group?.label,
					]
						.filter( Boolean )
						.join( ' ' )
						.toLocaleLowerCase()
						.includes( needle );
			  } )
			: tokens;
	}, [ groupMap, query, tokens ] );
	const grouped = useMemo( () => {
		const buckets = new Map< string, ContentTokenDefinition[] >();
		visible.forEach( ( token ) => {
			const group = token.group ?? 'general';
			buckets.set( group, [ ...( buckets.get( group ) ?? [] ), token ] );
		} );
		return [ ...buckets.entries() ];
	}, [ visible ] );

	return (
		<div className="pum-content-token-picker">
			<div className="pum-content-token-picker__search">
				<SearchControl
					label={ __( 'Search dynamic values', 'popup-maker' ) }
					placeholder={ __( 'Search values…', 'popup-maker' ) }
					value={ query }
					onChange={ setQuery }
					__nextHasNoMarginBottom
				/>
			</div>
			{ grouped.length ? (
				<div className="pum-content-token-picker__groups">
					{ grouped.map( ( [ groupId, items ] ) => (
						<section
							className="pum-content-token-picker__group"
							key={ groupId }
						>
							<h3 className="pum-content-token-picker__group-label">
								{ groupMap.get( groupId )?.label ??
									humanize( groupId ) }
							</h3>
							<ul className="pum-content-token-picker__list">
								{ items.map( ( token ) => (
									<li key={ token.id }>
										<Button
											className="pum-content-token-picker__button"
											isPressed={
												token.id === selectedId
											}
											onClick={ () => {
												onInsert( token );
												onClose();
											} }
										>
											<span className="pum-content-token-picker__label">
												{ token.label }
											</span>
											<span className="pum-content-token-picker__meta">
												{ token.id === selectedId
													? __(
															'Current selection',
															'popup-maker'
													  )
													: token.description ||
													  token.type }
											</span>
										</Button>
									</li>
								) ) }
							</ul>
						</section>
					) ) }
				</div>
			) : (
				<p className="pum-content-token-picker__empty">
					{ __( 'No matching dynamic values.', 'popup-maker' ) }
				</p>
			) }
		</div>
	);
};

interface PanelProps {
	token?: ContentTokenDefinition;
	tokens: ContentTokenDefinition[];
	groups: ContentTokenGroup[];
	onApply: ( token: ContentTokenDefinition ) => void;
	onRemove: () => void;
	onClose: () => void;
}

const ContentTokenPanel = ( {
	token,
	tokens,
	groups,
	onApply,
	onRemove,
	onClose,
}: PanelProps ) => {
	const [ isChoosing, setIsChoosing ] = useState( ! token );

	if ( isChoosing || ! token ) {
		return (
			<ContentTokenPicker
				tokens={ tokens }
				groups={ groups }
				selectedId={ token?.id }
				onInsert={ onApply }
				onClose={ onClose }
			/>
		);
	}

	return (
		<div className="pum-content-token-inspector">
			<div className="pum-content-token-inspector__summary">
				<strong>{ token.label }</strong>
				<span>
					{ token.description ||
						token.type ||
						humanize( token.group ?? 'general' ) }
				</span>
			</div>
			<div className="pum-content-token-inspector__actions">
				<Button
					icon={ pencil }
					label={ __( 'Change dynamic value', 'popup-maker' ) }
					showTooltip
					onClick={ () => setIsChoosing( true ) }
				/>
				<Button
					icon={ linkOff }
					label={ __( 'Remove dynamic value', 'popup-maker' ) }
					showTooltip
					onClick={ onRemove }
				/>
			</div>
		</div>
	);
};

const ContentTokenEdit = ( {
	isActive,
	isObjectActive,
	value,
	onChange,
	onFocus,
	contentRef,
}: FormatEditProps ) => {
	const tokens = contentTokens.useItems();
	const groups = contentTokenGroups.useItems();
	const [ tokenAnchor, setTokenAnchor ] = useState< HTMLElement | null >(
		null
	);
	const [ clickedTokenId, setClickedTokenId ] = useState< string | null >(
		null
	);
	const autoOpenedSelection = useRef< string | null >( null );
	const editorContext = useSelect( ( select ) => {
		const blockEditor = select( blockEditorStore );
		const editor = select( editorStore );
		return {
			surface: 'block-editor-rich-text',
			postId: editor.getCurrentPostId() as number,
			postType: editor.getCurrentPostType() as string,
			blockName: blockEditor.getSelectedBlock()?.name,
		} as ContentTokenContext;
	}, [] );
	const context = {
		...editorContext,
		attributeName:
			contentRef?.current?.getAttribute(
				'data-wp-block-attribute-key'
			) ?? undefined,
	};
	const selection = getContentTokenSelection( value );
	const selectedId = clickedTokenId ?? selection?.valueId;
	const selectedToken = tokens.find( ( token ) => token.id === selectedId );
	const available = tokens.filter( ( token ) =>
		isContentTokenAvailable( token, context )
	);
	const preview = selectedToken
		? getContentTokenPreview( selectedToken, context )
		: undefined;

	useLayoutEffect( () => {
		if ( ! isObjectActive || ! selectedId ) {
			autoOpenedSelection.current = null;
			return;
		}

		const selectionKey = `${ selectedId }:${ value.start }:${ value.end }`;
		if ( autoOpenedSelection.current === selectionKey ) {
			return;
		}

		const token = Array.from(
			contentRef?.current?.querySelectorAll< HTMLElement >(
				'.pum-content-token'
			) ?? []
		).find(
			( item ) =>
				( item.getAttribute( 'data-pum-token' ) ??
					item.getAttribute( 'value' ) ) === selectedId
		);
		if ( token ) {
			autoOpenedSelection.current = selectionKey;
			setClickedTokenId( selectedId );
			setTokenAnchor( token );
		}
	}, [ contentRef, isObjectActive, selectedId, value.end, value.start ] );

	useLayoutEffect( () => {
		const editableContent = contentRef?.current;
		if ( ! editableContent ) {
			return;
		}

		const handleClick = ( event: MouseEvent ) => {
			const target = event.target as HTMLElement;
			const token = target.closest(
				'.pum-content-token, .pum-content-token-editable'
			) as HTMLElement | null;
			if ( ! token || ! editableContent.contains( token ) ) {
				return;
			}

			const valueId =
				token.getAttribute( 'data-pum-token' ) ??
				token.getAttribute( 'value' );
			if ( valueId ) {
				setClickedTokenId( valueId );
				setTokenAnchor( token );
			}
		};

		editableContent.addEventListener( 'click', handleClick );
		return () =>
			editableContent.removeEventListener( 'click', handleClick );
	}, [ contentRef ] );

	const closeDirectPopover = () => {
		setTokenAnchor( null );
		setClickedTokenId( null );
	};

	const focusEditor = () => {
		window.requestAnimationFrame( () => onFocus?.() );
	};

	const applyToken = ( token: ContentTokenDefinition ) => {
		onChange(
			applyContentToken(
				value,
				token.id,
				token.interaction ?? 'atomic',
				getContentTokenPreview( token, context ),
				selection
			)
		);
		closeDirectPopover();
		focusEditor();
	};

	const removeToken = () => {
		if ( ! selection ) {
			return;
		}
		onChange(
			selection.kind === 'atomic'
				? removeContentToken( value, selection )
				: unlinkContentToken(
						value,
						selection,
						preview ?? selectedToken?.label ?? ''
				  )
		);
		closeDirectPopover();
		focusEditor();
	};

	const panel = ( onClose: () => void ) => (
		<ContentTokenPanel
			token={ selectedToken }
			tokens={ available }
			groups={ groups }
			onApply={ applyToken }
			onRemove={ removeToken }
			onClose={ onClose }
		/>
	);

	return ! available.length && ! selectedId ? null : (
		<>
			<Dropdown
				className="pum-content-token-picker__dropdown"
				popoverProps={ { placement: 'bottom-start' } }
				renderToggle={ ( { isOpen, onToggle } ) => (
					<RichTextToolbarButton
						icon="editor-code"
						title={
							selectedToken
								? __( 'Edit dynamic value', 'popup-maker' )
								: __( 'Insert dynamic value', 'popup-maker' )
						}
						isActive={
							isActive || isObjectActive || !! selection || isOpen
						}
						onClick={ () => {
							closeDirectPopover();
							onToggle();
						} }
					/>
				) }
				renderContent={ ( { onClose } ) => panel( onClose ) }
			/>
			{ tokenAnchor && selectedToken && (
				<Popover
					className="pum-content-token-inspector__popover"
					anchor={ tokenAnchor }
					position="bottom center"
					offset={ 8 }
					focusOnMount={ false }
					onClose={ closeDirectPopover }
				>
					{ panel( closeDirectPopover ) }
				</Popover>
			) }
		</>
	);
};

/** Register the shared atomic and editable RichText projections once. */
export const registerContentTokenFormats = (): void => {
	registerFormatType( EDITABLE_FORMAT, {
		title: __( 'Editable dynamic value', 'popup-maker' ),
		tagName: 'data',
		className: 'pum-content-token-editable',
		attributes: { valueId: 'value' },
		edit: () => null,
	} as unknown as Parameters< typeof registerFormatType >[ 1 ] );

	registerFormatType( ATOMIC_FORMAT, {
		title: __( 'Dynamic value', 'popup-maker' ),
		tagName: 'span',
		className: 'pum-content-token',
		attributes: { valueId: 'data-pum-token' },
		contentEditable: false,
		edit: ContentTokenEdit,
	} as unknown as Parameters< typeof registerFormatType >[ 1 ] );
};
