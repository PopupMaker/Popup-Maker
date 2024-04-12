/**
 * External dependencies
 */
import classnames from 'classnames';
import { customAlphabet } from 'nanoid';
const nanoid = customAlphabet( '1234567890abcdef', 10 );

/**
 * WordPress dependencies
 */
import {
	RichText,
	useBlockProps,
	__experimentalGetBorderClassesAndStyles as getBorderClassesAndStyles,
	__experimentalGetColorClassesAndStyles as getColorClassesAndStyles,
	__experimentalGetSpacingClassesAndStyles as getSpacingClassesAndStyles,
	__experimentalGetShadowClassesAndStyles as getShadowClassesAndStyles,
	__experimentalGetElementClassName,
} from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
// import getColorAndStyleProps from './color-props';

export default function save( { attributes } ) {
	const {
		className,
		linkTarget,
		rel,
		text,
		title,
		width,
		textAlign,
		fontSize,
		style,
		type,
		uuid,
		pid,
	} = attributes;
	// const save = useBlockProps.save();

	const borderProps = getBorderClassesAndStyles( attributes );
	const colorProps = getColorClassesAndStyles( attributes );
	const spacingProps = getSpacingClassesAndStyles( attributes );
	const shadowProps = getShadowClassesAndStyles( attributes );

	console.log( {
		width,
		textAlign,
		fontSize,
		style,
	} );

	const wrapper = {
		style: {},
		className: classnames( {
			[ `has-text-align-${ textAlign }` ]: textAlign,
			// For backwards compatibility add style that isn't provided via
			// block support.
			'no-border-radius': style?.border?.radius === 0,
		} ),
	};

	const button = {
		style: {
			...colorProps.style,
			...borderProps.style,
			...spacingProps.style,
			...shadowProps.style,
		},
		className: classnames(
			'pum-cta-button__link',
			colorProps.className,
			borderProps.className,
			spacingProps.className,
			shadowProps.className,
			{
				'no-border-radius': style?.border?.radius === 0,
			},
			{
				[ `has-custom-width pum-cta-button__width-${ width }` ]: width,
				[ `has-custom-font-size` ]:
					fontSize || style?.typography?.fontSize,
			}
		),
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
		<div
			className={ classnames( [
				className,
				'pum-cta-button',
				'pum-cta-button--' + type,
			] ) }
			// { ...useBlockProps.save() }
		>
			<RichText.Content
				tagName="a"
				className={ button.className }
				href={ `?${ queryString }` }
				title={ title }
				style={ button.style }
				value={ text }
				target={ linkTarget }
				rel={ rel }
				data-pum-cta-type={ type }
			/>
		</div>
	);
}
