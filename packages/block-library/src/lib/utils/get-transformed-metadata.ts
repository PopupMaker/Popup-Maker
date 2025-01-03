/**
 * WordPress dependencies
 */
import type { BlockConfiguration } from '@wordpress/blocks';

type BlockMetadata = Omit< BlockConfiguration, 'name' > & {
	supports?: BlockConfiguration[ 'supports' ] & {
		color?: {
			gradients?: boolean;
		};
	};
};

/**
 * Transform the metadata attribute with only the values and bindings specified by each transform.
 * Returns `undefined` if the input metadata is falsy.
 *
 * @param {Object}   metadata         Original metadata attribute from the block that is being transformed.
 * @param {Object}   newBlockName     Name of the final block after the transformation.
 * @param {Function} bindingsCallback Optional callback to transform the `bindings` property object.
 * @return {Object|undefined} New metadata object only with the relevant properties.
 */
export function getTransformedMetadata(
	metadata: BlockMetadata,
	newBlockName: string,
	bindingsCallback?: ( bindings: any ) => Record< string, unknown >
) {
	if ( ! metadata ) {
		return;
	}

	// Fixed until an opt-in mechanism is implemented.
	const BLOCK_BINDINGS_SUPPORTED_BLOCKS = [ 'popup-maker/cta-button' ];
	// The metadata properties that should be preserved after the transform.
	const transformSupportedProps: string[] = [];
	// If it support bindings, and there is a transform bindings callback, add the `id` and `bindings` properties.
	if (
		BLOCK_BINDINGS_SUPPORTED_BLOCKS.includes( newBlockName ) &&
		bindingsCallback
	) {
		transformSupportedProps.push( 'id', 'bindings' );
	}

	// Return early if no supported properties.
	if ( ! transformSupportedProps.length ) {
		return;
	}

	const newMetadata = Object.entries( metadata ).reduce(
		( obj, [ prop, value ] ) => {
			// If prop is not supported, don't add it to the new metadata object.
			if ( ! transformSupportedProps.includes( prop ) ) {
				return obj;
			}
			obj[ prop ] =
				prop === 'bindings' && bindingsCallback
					? bindingsCallback( value )
					: value;
			return obj;
		},
		{}
	);

	// Return undefined if object is empty.
	return Object.keys( newMetadata ).length ? newMetadata : undefined;
}
