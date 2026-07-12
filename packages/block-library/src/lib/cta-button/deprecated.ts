/**
 * External dependencies
 */
import clsx from 'clsx';
import React from 'react';

/**
 * WordPress dependencies
 */
import {
	RichText,
	useBlockProps,
	getTypographyClassesAndStyles,
	// @ts-expect-error
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalGetBorderClassesAndStyles as getBorderClassesAndStyles,
	// @ts-expect-error
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalGetColorClassesAndStyles as getColorClassesAndStyles,
	// @ts-expect-error
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalGetSpacingClassesAndStyles as getSpacingClassesAndStyles,
	// @ts-expect-error
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalGetShadowClassesAndStyles as getShadowClassesAndStyles,
} from '@wordpress/block-editor';

interface DeprecatedButtonAttributes {
	tagName?: string;
	type?: string;
	textAlign?: string;
	fontSize?: string;
	linkTarget?: string;
	rel?: string;
	style?: {
		border?: {
			radius?: number;
		};
		typography?: {
			fontSize?: string;
		};
	};
	text?: string;
	title?: string;
	url?: string;
	width?: number;
}

interface DeprecatedSaveProps {
	attributes: DeprecatedButtonAttributes;
	className?: string;
}

const saveWithClasses =
	( baseClasses: string[] ) =>
	( { attributes, className }: DeprecatedSaveProps ) => {
		const {
			tagName,
			type,
			textAlign,
			fontSize,
			linkTarget,
			rel,
			style,
			text,
			title,
			url,
			width,
		} = attributes;

		const TagName = tagName || 'a';
		const isButtonTag = 'button' === TagName;
		const buttonType = type || 'button';
		const borderProps = getBorderClassesAndStyles( attributes );
		const colorProps = getColorClassesAndStyles( attributes );
		const spacingProps = getSpacingClassesAndStyles( attributes );
		const shadowProps = getShadowClassesAndStyles( attributes );
		// @ts-expect-error
		const typographyProps = getTypographyClassesAndStyles( attributes );

		const buttonClasses = clsx(
			baseClasses,
			colorProps.className,
			borderProps.className,
			typographyProps.className,
			{
				[ `has-text-align-${ textAlign }` ]: textAlign,
				'no-border-radius': style?.border?.radius === 0,
				[ `has-custom-font-size` ]:
					fontSize || style?.typography?.fontSize,
			}
		);
		const buttonStyle = {
			...borderProps.style,
			...colorProps.style,
			...spacingProps.style,
			...shadowProps.style,
			...typographyProps.style,
			writingMode: undefined,
		};

		const wrapperClasses = clsx( className, {
			[ `has-custom-width wp-block-popup-maker-cta-button__width-${ width }` ]:
				Boolean( width ),
		} );

		return React.createElement(
			'div',
			useBlockProps.save( { className: wrapperClasses } ),
			React.createElement( RichText.Content, {
				tagName: TagName,
				type: isButtonTag ? buttonType : null,
				className: buttonClasses,
				href: isButtonTag ? null : url,
				title,
				style: buttonStyle,
				value: text,
				target: isButtonTag ? null : linkTarget,
				rel: isButtonTag ? null : rel,
			} )
		);
	};

const deprecated = [
	{
		apiVersion: 3,
		attributes: {},
		save: saveWithClasses( [
			'wp-block-popup-maker-cta-button__link',
			'wp-element-button',
		] ),
		migrate( attributes: DeprecatedButtonAttributes ) {
			return attributes;
		},
	},
	{
		apiVersion: 3,
		attributes: {},
		save: saveWithClasses( [ 'wp-block-popup-maker-cta-button__link' ] ),
		migrate( attributes: DeprecatedButtonAttributes ) {
			return attributes;
		},
	},
];

export default deprecated;
