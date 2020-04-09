/**
 * WordPress dependencies
 */
import { registerFormatType } from '@wordpress/rich-text';
/**
 * Internal dependencies
 */
import * as trigger from './popup-trigger';

[
	trigger,
].forEach( ( { name, settings } ) => registerFormatType( name, settings ) );
