/**
 * External Dependencies
 */
import classnames from 'classnames';

/**
 * WordPress Dependencies
 */
import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { InspectorControls } from '@wordpress/block-editor';
import { Icon, Panel, PanelBody, PanelRow, Tooltip } from '@wordpress/components';
import { createHigherOrderComponent } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import PopupSelectControl from '../../components/popup-select-control';
import GearIcon from '../../icons/gears';

/**
 * Either allowedBlocks or excludedBlocks should be used, not both.
 *
 * @type {Array}
 */
const allowedBlocks = [];
const excludedBlocks = pum_block_editor_vars.popup_trigger_excluded_blocks || [
	'core/nextpage',
];

function isAllowedForBlockType( name ) {
	if ( ! allowedBlocks.length && ! excludedBlocks.length ) {
		return true;
	}

	if ( allowedBlocks.length ) {
		return allowedBlocks.includes( name );
	}

	if ( excludedBlocks.length ) {
		return ! excludedBlocks.includes( name );
	}

	return true;
}

/**
 * Add custom attribute for mobile visibility.
 *
 * @param {Object} settings Settings for the block.
 *
 * @return {Object} settings Modified settings.
 */
function addAttributes( settings ) {
	//check if object exists for old Gutenberg version compatibility
	//add allowedBlocks restriction
	if ( typeof settings.attributes !== 'undefined' && isAllowedForBlockType( settings.name ) ) {
		settings.attributes = Object.assign( settings.attributes, {
			openPopupId: {
				type: 'string',
				default: '',
			},
		} );
	}

	return settings;
}

/**
 * Add mobile visibility controls on Advanced Block Panel.
 *
 * @param {Function} BlockEdit Block edit component.
 *
 * @return {Function} BlockEdit Modified block edit component.
 */
const withAdvancedControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		const { name, attributes, setAttributes, isSelected } = props;
		const { openPopupId } = attributes;

		return (
			<>
				<BlockEdit { ...props } />
				{ isSelected && isAllowedForBlockType( name ) && (
					<InspectorControls>
						<Panel>
							<PanelBody
								title={ __( 'Popup Controls', 'popup-maker' ) }
								icon={ GearIcon }
								initialOpen={ false }
							>
								<PanelRow>
									{ __( 'These settings allow you to control popups with this block.', 'popup-maker' ) }
								</PanelRow>
								<PanelRow>
									<PopupSelectControl
										label={ <>
											{ __( 'Open Popup', 'popup-maker' ) }
											<Tooltip
												position="top"
												text={ __( 'This method does not work well with all block types.', 'popup-maker' ) }
											>
												<a href="https://docs.wppopupmaker.com/article/395-trigger-click-open-overview-methods" target="_blank" rel="noopener noreferrer">
													<Icon
														size="16"
														icon="editor-help"
														title={ __( 'Open documentation', 'popup-maker' ) }
														style={ {
															verticalAlign: 'middle',
														} }
													/>
												</a>
											</Tooltip>
										</> }
										value={ openPopupId }
										onChange={ ( popupId ) => setAttributes( { openPopupId: popupId } ) }
										help={ __( 'Open a popup when clicking this block', 'popup-maker' ) }
									/>
								</PanelRow>
							</PanelBody>
						</Panel>

					</InspectorControls>
				) }
			</>
		);
	};
}, 'withAdvancedControls' );

/**
 * Add custom element class in save element.
 *
 * @param {Object} extraProps     Block element.
 * @param {Object} blockType      Blocks object.
 * @param {Object} attributes     Blocks attributes.
 *
 * @return {Object} extraProps Modified block element.
 */
function applyTriggerClass( extraProps, blockType, attributes ) {
	const { openPopupId } = attributes;

	//check if attribute exists for old Gutenberg version compatibility
	//add class only when visibleOnMobile = false
	//add allowedBlocks restriction
	if ( typeof openPopupId !== 'undefined' && openPopupId > 0 && isAllowedForBlockType( blockType.name ) ) {
		extraProps.className = classnames( extraProps.className, 'popmake-' + openPopupId );
	}

	return extraProps;
}

//add filters

addFilter(
	'blocks.registerBlockType',
	'popup-maker/popup-trigger-attributes',
	addAttributes,
);

addFilter(
	'editor.BlockEdit',
	'popup-maker/popup-trigger-advanced-control',
	withAdvancedControls,
);

addFilter(
	'blocks.getSaveContent.extraProps',
	'popup-maker/applyTriggerClass',
	applyTriggerClass,
);
