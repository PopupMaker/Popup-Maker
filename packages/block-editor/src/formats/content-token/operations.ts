import {
	applyFormat,
	create,
	getActiveObject,
	insert,
	insertObject,
	remove,
	removeFormat,
	type RichTextValue,
} from '@wordpress/rich-text';

export const ATOMIC_FORMAT = 'popup-maker/content-token';
export const EDITABLE_FORMAT = 'popup-maker/editable-content-token';

type TokenFormat = {
	type: string;
	attributes?: Record< string, string >;
	innerHTML?: string;
};

export type ContentTokenSelection = {
	kind: 'atomic' | 'editable';
	formatType: typeof ATOMIC_FORMAT | typeof EDITABLE_FORMAT;
	valueId: string;
	start: number;
	end: number;
	text: string;
};

const hasFormat = (
	formats: TokenFormat[] | undefined,
	type: string,
	valueId?: string
) =>
	!! formats?.some(
		( format ) =>
			format.type === type &&
			( ! valueId || format.attributes?.valueId === valueId )
	);

const findFormat = (
	formats: TokenFormat[] | undefined,
	type: string
): TokenFormat | undefined =>
	formats?.find( ( format ) => format.type === type );

const activeFormatRange = (
	value: RichTextValue,
	type: string
): { start: number; end: number } | null => {
	const formats = value.formats as Array< Array< TokenFormat > >;
	if ( ! formats?.length ) {
		return null;
	}

	let index = Math.min( value.start ?? 0, formats.length - 1 );
	if ( ! hasFormat( formats[ index ], type ) && index > 0 ) {
		index--;
	}
	const active = findFormat( formats[ index ], type );
	const valueId = active?.attributes?.valueId;
	if ( ! active || ! valueId ) {
		return null;
	}

	let start = index;
	let end = index + 1;
	while ( start > 0 && hasFormat( formats[ start - 1 ], type, valueId ) ) {
		start--;
	}
	while (
		end < formats.length &&
		hasFormat( formats[ end ], type, valueId )
	) {
		end++;
	}

	return { start, end };
};

const objectText = ( object: TokenFormat ): string => {
	if ( ! object.innerHTML ) {
		return '';
	}

	try {
		return create( { html: object.innerHTML } ).text;
	} catch {
		return '';
	}
};

/**
 * Resolve the complete token containing the current RichText selection.
 *
 * @param value RichText value to inspect.
 */
export const getContentTokenSelection = (
	value: RichTextValue
): ContentTokenSelection | null => {
	const object = getActiveObject( value ) as TokenFormat | undefined;
	if ( object?.type === ATOMIC_FORMAT && object.attributes?.valueId ) {
		return {
			kind: 'atomic',
			formatType: object.type,
			valueId: object.attributes.valueId,
			start: value.start,
			end: value.end,
			text: objectText( object ),
		};
	}

	const formatType = activeFormatRange( value, EDITABLE_FORMAT )
		? EDITABLE_FORMAT
		: undefined;
	if ( ! formatType ) {
		return null;
	}
	const range = activeFormatRange( value, formatType );
	if ( ! range ) {
		return null;
	}

	const format = (
		value.formats[ range.start ] as TokenFormat[] | undefined
	 )?.find( ( item ) => item.type === formatType );
	if ( ! format?.attributes?.valueId ) {
		return null;
	}

	return {
		kind: 'editable',
		formatType,
		valueId: format.attributes.valueId,
		...range,
		text: value.text.slice( range.start, range.end ),
	};
};

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

/**
 * Insert a new token or change the token containing the current selection.
 *
 * @param value       RichText value to modify.
 * @param valueId     Canonical token identifier.
 * @param interaction Token interaction mode.
 * @param preview     Safe fallback text shown in the editor.
 * @param selection   Existing token selection, when changing a token.
 */
export const applyContentToken = (
	value: RichTextValue,
	valueId: string,
	interaction: 'atomic' | 'editable',
	preview: string,
	selection = getContentTokenSelection( value )
): RichTextValue => {
	const range = selection
		? { start: selection.start, end: selection.end }
		: { start: value.start, end: value.end };

	if ( interaction === 'atomic' ) {
		return insertObject(
			value,
			{
				type: ATOMIC_FORMAT,
				attributes: { valueId },
				innerHTML: escapeText( preview ),
			} as never,
			range.start,
			range.end
		);
	}

	const format = {
		type: EDITABLE_FORMAT,
		attributes: { valueId },
	} as never;

	if ( selection?.kind === 'editable' ) {
		const unformatted = removeFormat(
			value,
			selection.formatType,
			range.start,
			range.end
		);
		return applyFormat( unformatted, format, range.start, range.end );
	}

	if ( selection?.kind === 'atomic' ) {
		const text = selection.text || preview;
		const inserted = insert( value, text, range.start, range.end );
		return applyFormat(
			inserted,
			format,
			range.start,
			range.start + text.length
		);
	}

	if ( range.start !== range.end ) {
		return applyFormat( value, format, range.start, range.end );
	}

	const inserted = insert( value, preview, range.start, range.end );
	return applyFormat(
		inserted,
		format,
		range.start,
		range.start + preview.length
	);
};

/**
 * Remove token behavior while preserving its readable text.
 *
 * @param value     RichText value to modify.
 * @param selection Token selection to unlink.
 * @param fallback  Fallback text for legacy objects with no readable content.
 */
export const unlinkContentToken = (
	value: RichTextValue,
	selection: ContentTokenSelection,
	fallback: string
): RichTextValue => {
	if ( selection.kind === 'editable' ) {
		return removeFormat(
			value,
			selection.formatType,
			selection.start,
			selection.end
		);
	}

	return insert(
		value,
		selection.text || fallback,
		selection.start,
		selection.end
	);
};

/**
 * Remove the complete token and its readable text.
 *
 * @param value     RichText value to modify.
 * @param selection Token selection to remove.
 */
export const removeContentToken = (
	value: RichTextValue,
	selection: ContentTokenSelection
): RichTextValue => remove( value, selection.start, selection.end );
