/**
 * WordPress dependencies
 */
import { createBlock } from '@wordpress/blocks';
import { getTransformedMetadata } from '../utils/get-transformed-metadata';
// import { __unstableCreateElement as createElement } from '@wordpress/rich-text';

interface HTMLBodyElement extends HTMLElement {}

type CreateElementFunction = (
	document: Document,
	html: string
) => HTMLBodyElement;

type CreateElementObject = {
	body: HTMLBodyElement;
};

type CreateElement = CreateElementFunction & CreateElementObject;

// Temporary till core removes unstableCreateElement
/**
 * Parse the given HTML into a body element.
 *
 * Note: The current implementation will return a shared reference, reset on
 * each call to `createElement`. Therefore, you should not hold a reference to
 * the value to operate upon asynchronously, as it may have unexpected results.
 *
 * @param {Document} document The HTML document to use to parse.
 * @param {string}   html     The HTML to parse.
 *
 * @return {HTMLBodyElement} Body element with parsed HTML.
 */
const createElement: CreateElement = ( (
	document: Document,
	html: string
): HTMLBodyElement => {
	// Because `createHTMLDocument` is an expensive operation, and with this
	// function being internal to `rich-text` (full control in avoiding a risk
	// of asynchronous operations on the shared reference), a single document
	// is reused and reset for each call to the function.
	if ( ! createElement.body ) {
		createElement.body =
			document.implementation.createHTMLDocument( '' ).body;
	}

	createElement.body.innerHTML = html;

	return createElement.body;
} ) as unknown as CreateElement;

const transforms = {
	from: [
		{
			type: 'block',
			isMultiBlock: true,
			blocks: [ 'popup-maker/cta-button', 'core/button' ],
			transform: ( buttons ) =>
				// Creates the cta-buttons block.
				createBlock(
					'popup-maker/cta-buttons',
					{},
					// Loop the selected buttons.
					buttons.map( ( attributes, name ) =>
						name === 'core/button'
							? createBlock( 'core/button', attributes )
							: createBlock(
									'popup-maker/cta-button',
									attributes
							  )
					)
				),
		},
		{
			type: 'block',
			isMultiBlock: true,
			blocks: [ 'core/buttons' ],
			transform: ( buttons ) =>
				// Creates the cta-buttons block.
				createBlock(
					'popup-maker/cta-buttons',
					{},
					// Loop the selected buttons.
					buttons.map( ( attributes ) =>
						createBlock( 'core/button', attributes )
					)
				),
		},
		{
			type: 'block',
			isMultiBlock: true,
			blocks: [ 'core/paragraph' ],
			transform: ( buttons ) =>
				// Creates the buttons block.
				createBlock(
					'popup-maker/cta-buttons',
					{},
					// Loop the selected buttons.
					buttons.map( ( attributes ) => {
						const { content, metadata } = attributes;
						const element = createElement( document, content );
						// Remove any HTML tags.
						const text = element.innerText || '';
						// Get first url.
						const link = element.querySelector( 'a' );
						const url = link?.getAttribute( 'href' );
						// Create singular button in the buttons block.
						return createBlock( 'popup-maker/cta-button', {
							text,
							url,
							metadata: getTransformedMetadata(
								metadata,
								'popup-maker/cta-button',
								( { content: contentBinding } ) => ( {
									text: contentBinding,
								} )
							),
						} );
					} )
				),
			isMatch: ( paragraphs ) => {
				return paragraphs.every( ( attributes ) => {
					const element = createElement(
						document,
						attributes.content
					);
					const text = element.innerText || '';
					const links = element.querySelectorAll( 'a' );
					return text.length <= 30 && links.length <= 1;
				} );
			},
		},
	],
};

export default transforms;
