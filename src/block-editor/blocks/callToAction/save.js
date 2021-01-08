/**
 * External dependencies
 */
import classnames from 'classnames';
import { customAlphabet } from 'nanoid';
const nanoid = customAlphabet( '1234567890abcdef', 10 );

/**
 * WordPress dependencies
 */
import { RichText, useBlockProps } from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import getColorAndStyleProps from './color-props';

export default function save( { attributes } ) {
	const {
		borderRadius,
		linkTarget,
		rel,
		text,
		title,
		url,
		uuid,
		pid,
	} = attributes;
	const colorProps = getColorAndStyleProps( attributes );
	const buttonClasses = classnames(
		'pum-cta-button__link',
		colorProps.className,
		{
			'no-border-radius': borderRadius === 0,
		},
		'custom-class'
	);
	const buttonStyle = {
		borderRadius: borderRadius ? borderRadius + 'px' : undefined,
		...colorProps.style,
	};

	const urlParams = {
		pid: pid || wp.data.select( 'core/editor' ).getCurrentPostId(),
		uuid,
	};

	const queryString = Object.keys( urlParams )
		.map( ( key ) => {
			return (
				encodeURIComponent( key ) +
				'=' +
				encodeURIComponent( urlParams[ key ] )
			);
		} )
		.join( '&' );

	// The use of a `title` attribute here is soft-deprecated, but still applied
	// if it had already been assigned, for the sake of backward-compatibility.
	// A title will no longer be assigned for new or updated button block links.

	return (
		<div { ...useBlockProps.save() }>
			<RichText.Content
				tagName="a"
				className={ buttonClasses }
				href={ `?${ queryString }` }
				title={ title }
				style={ buttonStyle }
				value={ text }
				target={ linkTarget }
				rel={ rel }
			/>
		</div>
	);
}
