/**
 * WordPress dependencies
 */
import { BlockConfiguration, registerBlockType } from '@wordpress/blocks';

type BlockMetadata = Omit< BlockConfiguration, 'name' >;

export default function initBlock( block: {
	metadata: BlockMetadata;
	settings: Partial< BlockConfiguration >;
	name: string;
} ): ReturnType< typeof registerBlockType > {
	if ( ! block ) {
		return;
	}
	const { metadata, settings, name } = block;
	return registerBlockType( { name, ...metadata }, settings );
}
