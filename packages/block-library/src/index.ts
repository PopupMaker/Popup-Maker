/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
/**
 * Internal dependencies
 */
import * as cta from './calltoaction';

[ cta ].forEach( ( { name, settings } ) =>
	registerBlockType( name, settings )
);
