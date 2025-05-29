import { registerBlockType } from '@wordpress/blocks';

[].forEach( ( { name, settings } ) => registerBlockType( name, settings ) );
