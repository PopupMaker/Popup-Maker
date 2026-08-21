import './editor.scss';

import {
	RichTextToolbarButton,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { Button, Dropdown, SearchControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	applyFormat,
	getActiveFormat,
	insert,
	insertObject,
	registerFormatType,
	type RichTextValue,
} from '@wordpress/rich-text';
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

const ATOMIC_FORMAT = 'popup-maker/content-token';
const EDITABLE_FORMAT = 'popup-maker/editable-content-token';

interface FormatEditProps {
	isActive: boolean;
	isObjectActive: boolean;
	activeObjectAttributes?: Record< string, string >;
	value: RichTextValue;
	onChange: ( value: RichTextValue ) => void;
}

interface PickerProps {
	tokens: ContentTokenDefinition[];
	groups: ContentTokenGroup[];
	selectedId?: string;
	onInsert: ( token: ContentTokenDefinition ) => void;
	onClose: () => void;
}

const escapeText = ( value: string ): string =>
	value.replace(
		/[&<>"']/g,
		( character ) =>
			( {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				'"': '&quot;',
				"'": '&#039;',
			} )[ character ] ?? character
	);

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

const hasFormat = (
	formats: Array< { type?: string } > | undefined,
	type: string
) => !! formats?.some( ( format ) => format.type === type );

const activeFormatRange = (
	value: RichTextValue,
	type: string
): { start: number; end: number } | null => {
	const formats = value.formats as Array< Array< { type?: string } > >;
	if ( ! formats?.length ) {
		return null;
	}

	let index = Math.min( value.start ?? 0, formats.length - 1 );
	if ( ! hasFormat( formats[ index ], type ) && index > 0 ) {
		index--;
	}
	if ( ! hasFormat( formats[ index ], type ) ) {
		return null;
	}

	let start = index;
	let end = index + 1;
	while ( start > 0 && hasFormat( formats[ start - 1 ], type ) ) {
		start--;
	}
	while ( end < formats.length && hasFormat( formats[ end ], type ) ) {
		end++;
	}

	return { start, end };
};

const ContentTokenEdit = ( {
	isActive,
	isObjectActive,
	activeObjectAttributes,
	value,
	onChange,
}: FormatEditProps ) => {
	const tokens = contentTokens.useItems();
	const groups = contentTokenGroups.useItems();
	const context = useSelect( ( select ) => {
		const blockEditor = select( blockEditorStore );
		const editor = select( editorStore );
		return {
			surface: 'block-editor-rich-text',
			postId: editor.getCurrentPostId() as number,
			postType: editor.getCurrentPostType() as string,
			blockName: blockEditor.getSelectedBlock()?.name,
		} as ContentTokenContext;
	}, [] );
	const editableFormat = getActiveFormat( value, EDITABLE_FORMAT ) as
		| { attributes?: Record< string, string > }
		| undefined;
	const selectedId =
		activeObjectAttributes?.valueId ?? editableFormat?.attributes?.valueId;
	const available = tokens.filter( ( token ) =>
		isContentTokenAvailable( token, context )
	);

	return ! available.length && ! selectedId ? null : (
		<Dropdown
			className="pum-content-token-picker__dropdown"
			popoverProps={ { placement: 'bottom-start' } }
			renderToggle={ ( { isOpen, onToggle } ) => (
				<RichTextToolbarButton
					icon="editor-code"
					title={ __( 'Insert dynamic value', 'popup-maker' ) }
					isActive={
						isActive ||
						isObjectActive ||
						!! editableFormat ||
						isOpen
					}
					onClick={ onToggle }
				/>
			) }
			renderContent={ ( { onClose } ) => (
				<ContentTokenPicker
					tokens={ available }
					groups={ groups }
					selectedId={ selectedId }
					onClose={ onClose }
					onInsert={ ( token ) => {
						const range = editableFormat
							? activeFormatRange( value, EDITABLE_FORMAT )
							: null;
						const scoped = range
							? { ...value, start: range.start, end: range.end }
							: value;
						const preview = getContentTokenPreview(
							token,
							context
						);

						if ( 'editable' === token.interaction ) {
							const start = scoped.start ?? scoped.text.length;
							const inserted = insert(
								scoped,
								preview,
								start,
								scoped.end ?? start
							);
							onChange(
								applyFormat(
									inserted,
									{
										type: EDITABLE_FORMAT,
										attributes: { valueId: token.id },
									} as unknown as Parameters<
										typeof applyFormat
									>[ 1 ],
									start,
									start + preview.length
								)
							);
							return;
						}

						onChange(
							insertObject( scoped, {
								type: ATOMIC_FORMAT,
								attributes: { valueId: token.id },
								innerHTML: escapeText( preview ),
							} as unknown as Parameters<
								typeof insertObject
							>[ 1 ] )
						);
					} }
				/>
			) }
		/>
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
		tagName: 'data',
		className: 'pum-content-token',
		contentEditable: false,
		attributes: { valueId: 'value' },
		edit: ContentTokenEdit,
	} as unknown as Parameters< typeof registerFormatType >[ 1 ] );
};
