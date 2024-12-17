import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';

// Extend the core button block
import { settings as buttonSettings } from '@wordpress/block-library/build/button';

registerBlockType( 'popup-maker/cta', {
	...buttonSettings,
	title: __( 'PUM Call to Action', 'popup-maker' ),
	description: __(
		'Insert a Call to Action button that triggers popups.',
		'popup-maker'
	),
	category: 'popup-maker',
	icon: 'megaphone',
	attributes: {
		...buttonSettings.attributes,
		ctaId: {
			type: 'string',
			default: '',
		},
	},
	edit: ( { attributes, setAttributes } ) => {
		const { ctaId } = attributes;
		const blockProps = useBlockProps();

		// Get all CTAs
		const ctas = useSelect( ( select ) => {
			const posts = select( 'core' ).getEntityRecords(
				'postType',
				'pum_cta',
				{
					per_page: -1,
					status: 'publish',
				}
			);
			return posts;
		}, [] );

		// Create options for select control
		const ctaOptions = ctas
			? [
					{
						value: '',
						label: __( 'Select a Call to Action', 'popup-maker' ),
					},
					...ctas.map( ( cta ) => ( {
						value: cta.id.toString(),
						label: cta.title.rendered,
					} ) ),
			  ]
			: [];

		return (
			<>
				<InspectorControls>
					<PanelBody
						title={ __( 'Call to Action Settings', 'popup-maker' ) }
					>
						<SelectControl
							label={ __(
								'Select Call to Action',
								'popup-maker'
							) }
							value={ ctaId }
							options={ ctaOptions }
							onChange={ ( value ) =>
								setAttributes( { ctaId: value } )
							}
						/>
					</PanelBody>
				</InspectorControls>
				<div { ...blockProps }>
					{ buttonSettings.edit( { ...attributes, setAttributes } ) }
				</div>
			</>
		);
	},
	save: ( { attributes } ) => {
		return buttonSettings.save( { ...attributes } );
	},
} );
