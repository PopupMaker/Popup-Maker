/**
 * External dependencies
 */
import clsx from 'clsx';
import React from 'react';

/**
 * WordPress dependencies
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

const legacySupports = {
	anchor: true,
	align: [ 'wide', 'full' ],
	html: false,
	__experimentalExposeControlsToChildren: true,
	color: {
		gradients: true,
		text: false,
		__experimentalDefaultControls: {
			background: true,
		},
	},
	spacing: {
		blockGap: [ 'horizontal', 'vertical' ],
		padding: true,
		margin: [ 'top', 'bottom' ],
		__experimentalDefaultControls: {
			blockGap: true,
		},
	},
	typography: {
		fontSize: true,
		lineHeight: true,
		__experimentalFontFamily: true,
		__experimentalFontWeight: true,
		__experimentalFontStyle: true,
		__experimentalTextTransform: true,
		__experimentalTextDecoration: true,
		__experimentalLetterSpacing: true,
		__experimentalDefaultControls: {
			fontSize: true,
		},
	},
	__experimentalBorder: {
		color: true,
		radius: true,
		style: true,
		width: true,
		__experimentalDefaultControls: {
			color: true,
			radius: true,
			style: true,
			width: true,
		},
	},
	layout: {
		allowSwitching: false,
		allowInheriting: false,
		default: {
			type: 'flex',
		},
	},
	interactivity: {
		clientNavigation: true,
	},
};

const deprecated = [
	{
		apiVersion: 3,
		attributes: {},
		supports: legacySupports,
		save( { attributes, className } ) {
			const { fontSize, style } = attributes;

			const blockProps = useBlockProps.save( {
				className: clsx(
					className,
					// Legacy markup included the core/buttons class so core styles reached children.
					'wp-block-buttons',
					{
						'has-custom-font-size':
							fontSize || style?.typography?.fontSize,
					}
				),
			} );

			const innerBlocksProps = useInnerBlocksProps.save( blockProps );

			return <div { ...innerBlocksProps } />;
		},
		migrate( attributes, innerBlocks ) {
			return [ attributes, innerBlocks ];
		},
	},
];

export default deprecated;
