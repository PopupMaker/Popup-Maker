import React from 'react';

/**
 * External dependencies
 */
import clsx from 'clsx';

/**
 * WordPress dependencies
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

export default function save( { attributes, className } ) {
	const { fontSize, style } = attributes;

	const blockProps = useBlockProps.save( {
		className: clsx(
			className,
			// Add class to allow for core/buttons styles too properly fall through for children.
			'wp-block-buttons',
			{
				'has-custom-font-size': fontSize || style?.typography?.fontSize,
			}
		),
	} );

	const innerBlocksProps = useInnerBlocksProps.save( blockProps );

	return <div { ...innerBlocksProps } />;
}
