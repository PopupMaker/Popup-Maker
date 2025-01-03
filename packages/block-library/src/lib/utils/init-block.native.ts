/**
 * WordPress dependencies
 */
import { BlockConfiguration, registerBlockType } from '@wordpress/blocks';

type BlockMetadata = Omit< BlockConfiguration, 'name' > & {
	supports?: BlockConfiguration[ 'supports' ] & {
		color?: {
			gradients?: boolean;
		};
	};
};

const ALLOWED_BLOCKS_GRADIENT_SUPPORT = [ 'popup-maker/cta-button' ];

export default function initBlock( block: {
	metadata: BlockMetadata;
	settings: Partial< BlockConfiguration >;
	name: string;
} ): ReturnType< typeof registerBlockType > {
	if ( ! block ) {
		return;
	}
	const { metadata, settings, name } = block;
	const { supports } = metadata;

	return registerBlockType(
		{
			name,
			...metadata,
			// Gradients support only available for blocks listed in ALLOWED_BLOCKS_GRADIENT_SUPPORT.
			...( ! ALLOWED_BLOCKS_GRADIENT_SUPPORT.includes( name ) &&
			supports?.color?.gradients
				? {
						supports: {
							...supports,
							color: { ...supports.color, gradients: false },
						},
				  }
				: {} ),
		},
		settings
	);
}
