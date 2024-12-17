import { name } from './index';

import {
	applyFormat,
	insert,
	slice,
	getTextContent,
} from '@wordpress/rich-text';

import type { RichTextValue } from '@wordpress/rich-text';
import type { RichTextFormat } from '@wordpress/rich-text/build-types/types';
import type {
	TriggerFormat,
	TriggerFormatAttributes,
	TriggerFormatOptions,
} from './types';

/**
 * Creates a new RichTextValue with the trigger format applied to the given text.
 *
 * @param {RichTextValue}  value
 * @param {string}         text
 * @param {RichTextFormat} format
 * @param {number}         start
 *
 * @return {RichTextValue} Formatted value
 */
export function insertFormattedText(
	value: RichTextValue,
	text: string,
	format: RichTextFormat,
	start: number
): RichTextValue {
	// First insert the new text at the cursor position
	const inserted = insert( value, text, start );

	// Then apply the format to the newly inserted text
	return applyFormat( inserted, format, start, start + text.length );
}

/**
 * Applies a format to existing text without changing the text content.
 *
 * @param {RichTextValue}  value
 * @param {RichTextFormat} format
 *
 * @return {RichTextValue} Formatted value
 */
export function addFormatToText(
	value: RichTextValue,
	format: RichTextFormat
): RichTextValue {
	// If no range is provided, format will be applied to the entire value
	return applyFormat( value, format );
}

/**
 * Updates attributes of text that already has a format applied.
 *
 * @param {RichTextValue}  value
 * @param {RichTextFormat} format
 *
 * @return {RichTextValue} Formatted value
 */
export function updateFormattedText(
	value: RichTextValue,
	format: RichTextFormat
): RichTextValue {
	// Get the boundaries of the active format
	const boundary = getFormatBoundary( value, {
		type: format.type,
	} );

	// If no boundary found, apply to entire selection
	if ( ! boundary.start && ! boundary.end ) {
		return applyFormat( value, format );
	}

	console.log( 'applyFormat', {
		value,
		format,
		start: boundary.start ?? undefined,
		end: boundary.end ?? undefined,
	} );

	// Apply the format to the entire format boundary range
	return applyFormat(
		value,
		format,
		boundary.start ?? undefined,
		boundary.end ?? undefined
	);
}

/**
 * Get the text content from the current selection.
 *
 * @param {RichTextValue} value
 *
 * @return {string} Selected text
 */
export function getSelectedText( value: RichTextValue ): string {
	return getTextContent( slice( value ) );
}

/**
 * Generates the format object that will be applied to the trigger text.
 *
 * @param {TriggerFormatOptions} options The options.
 *
 * @return {TriggerFormat} The final format object.
 */
export const createTriggerFormat = ( {
	popupId = 0,
	doDefault = false,
}: TriggerFormatOptions ): TriggerFormat => ( {
	type: name,
	attributes: {
		class: `popmake-${ popupId } ${ doDefault ? 'pum-do-default' : '' }`,
		popupId: popupId.toString(),
		doDefault: doDefault ? '1' : '0',
	},
} );

export const triggerOptionsFromFormatAttrs = (
	attributes: TriggerFormatAttributes
) => ( {
	popupId: parseInt( attributes.popupId ?? '0' ),
	doDefault: attributes.doDefault === '1',
} );

/**
 * Get the start and end boundaries of a given format from a rich text value.
 *
 * @param {RichTextValue} value      the rich text value to interrogate.
 * @param {string}        format     the identifier for the target format (e.g. `core/link`, `core/bold`).
 * @param {number?}       startIndex optional startIndex to seek from.
 * @param {number?}       endIndex   optional endIndex to seek from.
 * @return {Object}	object containing start and end values for the given format.
 */
export function getFormatBoundary(
	value: RichTextValue,
	format: RichTextFormat,
	startIndex: number = value.start,
	endIndex: number = value.end
): {
	start: number | null;
	end: number | null;
} {
	const EMPTY_BOUNDARIES = {
		start: null,
		end: null,
	};

	const { formats } = value;
	let targetFormat: { type: string };
	let initialIndex: number;

	if ( ! formats?.length ) {
		return EMPTY_BOUNDARIES;
	}

	// Clone formats to avoid modifying source formats.
	const newFormats = formats.slice();

	const formatAtStart = newFormats[ startIndex ]?.find(
		( { type } ) => type === format.type
	);

	const formatAtEnd = newFormats[ endIndex ]?.find(
		( { type } ) => type === format.type
	);

	const formatAtEndMinusOne = newFormats[ endIndex - 1 ]?.find(
		( { type } ) => type === format.type
	);

	if ( formatAtStart ) {
		// Set values to conform to "start"
		targetFormat = formatAtStart;
		initialIndex = startIndex;
	} else if ( formatAtEnd ) {
		// Set values to conform to "end"
		targetFormat = formatAtEnd;
		initialIndex = endIndex;
	} else if ( formatAtEndMinusOne ) {
		// This is an edge case which will occur if you create a format, then place
		// the caret just before the format and hit the back ARROW key. The resulting
		// value object will have start and end +1 beyond the edge of the format boundary.
		targetFormat = formatAtEndMinusOne;
		initialIndex = endIndex - 1;
	} else {
		return EMPTY_BOUNDARIES;
	}

	const index = newFormats[ initialIndex ].indexOf( targetFormat );

	const walkingArgs = [ newFormats, initialIndex, targetFormat, index ];

	// Walk the startIndex "backwards" to the leading "edge" of the matching format.
	startIndex = walkToStart( ...walkingArgs );

	// Walk the endIndex "forwards" until the trailing "edge" of the matching format.
	endIndex = walkToEnd( ...walkingArgs );

	// Safe guard: start index cannot be less than 0.
	startIndex = startIndex < 0 ? 0 : startIndex;

	// // Return the indicies of the "edges" as the boundaries.
	return {
		start: startIndex,
		end: endIndex,
	};
}

/**
 * Walks forwards/backwards towards the boundary of a given format within an
 * array of format objects. Returns the index of the boundary.
 *
 * @param {Array}  formats         the formats to search for the given format type.
 * @param {number} initialIndex    the starting index from which to walk.
 * @param {Object} targetFormatRef a reference to the format type object being sought.
 * @param {number} formatIndex     the index at which we expect the target format object to be.
 * @param {string} direction       either 'forwards' or 'backwards' to indicate the direction.
 * @return {number} the index of the boundary of the given format.
 */
function walkToBoundary(
	formats: Array< any >,
	initialIndex: number,
	targetFormatRef: object,
	formatIndex: number,
	direction: string
): number {
	let index = initialIndex;

	const directions = {
		forwards: 1,
		backwards: -1,
	};

	const directionIncrement = directions[ direction ] || 1; // invalid direction arg default to forwards
	const inverseDirectionIncrement = directionIncrement * -1;

	while (
		formats[ index ] &&
		formats[ index ][ formatIndex ] === targetFormatRef
	) {
		// Increment/decrement in the direction of operation.
		index = index + directionIncrement;
	}

	// Restore by one in inverse direction of operation
	// to avoid out of bounds.
	index = index + inverseDirectionIncrement;

	return index;
}

const partialRight =
	( fn, ...partialArgs ) =>
	( ...args ) =>
		fn( ...args, ...partialArgs );

const walkToStart = partialRight( walkToBoundary, 'backwards' );

const walkToEnd = partialRight( walkToBoundary, 'forwards' );
