/**
 * External dependencies
 */
import classnames from 'classnames';
import { customAlphabet } from 'nanoid';
const nanoid = customAlphabet( '1234567890abcdef', 10 );

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback, useState, useEffect } from '@wordpress/element';
import {
	KeyboardShortcuts,
	PanelBody,
	RangeControl,
	TextControl,
	SelectControl,
	ToggleControl,
	ToolbarButton,
	ToolbarGroup,
	Popover,
} from '@wordpress/components';
import {
	BlockControls,
	InspectorControls,
	RichText,
	useBlockProps,
	__experimentalLinkControl as LinkControl,
	__experimentalUseEditorFeature as useEditorFeature,
} from '@wordpress/block-editor';
import { rawShortcut, displayShortcut } from '@wordpress/keycodes';
import { link, linkOff } from '@wordpress/icons';
import { createBlock } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { callToActions } from './utils';
import ColorEdit from './color-edit';
import getColorAndStyleProps from './color-props';

const NEW_TAB_REL = 'noreferrer noopener';
const MIN_BORDER_RADIUS_VALUE = 0;
const MAX_BORDER_RADIUS_VALUE = 50;
const INITIAL_BORDER_RADIUS_POSITION = 5;

const EMPTY_ARRAY = [];

function BorderPanel( { borderRadius = '', setAttributes } ) {
	const initialBorderRadius = borderRadius;
	const setBorderRadius = useCallback(
		( newBorderRadius ) => {
			if ( newBorderRadius === undefined ) {
				setAttributes( {
					borderRadius: initialBorderRadius,
				} );
			} else {
				setAttributes( { borderRadius: newBorderRadius } );
			}
		},
		[ setAttributes ]
	);
	return (
		<PanelBody title={ __( 'Border settings' ) }>
			<RangeControl
				value={ borderRadius }
				label={ __( 'Border radius' ) }
				min={ MIN_BORDER_RADIUS_VALUE }
				max={ MAX_BORDER_RADIUS_VALUE }
				initialPosition={ INITIAL_BORDER_RADIUS_POSITION }
				allowReset
				onChange={ setBorderRadius }
			/>
		</PanelBody>
	);
}

function URLPicker( {
	isSelected,
	url,
	setAttributes,
	opensInNewTab,
	onToggleOpenInNewTab,
} ) {
	const [ isURLPickerOpen, setIsURLPickerOpen ] = useState( false );
	const urlIsSet = !! url;
	const urlIsSetAndSelected = urlIsSet && isSelected;
	const openLinkControl = () => {
		setIsURLPickerOpen( true );
		return false; // prevents default behaviour for event
	};
	const unlinkButton = () => {
		setAttributes( {
			url: undefined,
			linkTarget: undefined,
			rel: undefined,
		} );
		setIsURLPickerOpen( false );
	};
	const linkControl = ( isURLPickerOpen || urlIsSetAndSelected ) && (
		<Popover
			position="bottom center"
			onClose={ () => setIsURLPickerOpen( false ) }
		>
			<LinkControl
				className="wp-block-navigation-link__inline-link-input"
				value={ { url, opensInNewTab } }
				onChange={ ( {
					url: newURL = '',
					opensInNewTab: newOpensInNewTab,
				} ) => {
					setAttributes( { url: newURL } );

					if ( opensInNewTab !== newOpensInNewTab ) {
						onToggleOpenInNewTab( newOpensInNewTab );
					}
				} }
			/>
		</Popover>
	);
	return (
		<>
			<BlockControls>
				<ToolbarGroup>
					{ ! urlIsSet && (
						<ToolbarButton
							name="link"
							icon={ link }
							title={ __( 'Link' ) }
							shortcut={ displayShortcut.primary( 'k' ) }
							onClick={ openLinkControl }
						/>
					) }
					{ urlIsSetAndSelected && (
						<ToolbarButton
							name="link"
							icon={ linkOff }
							title={ __( 'Unlink' ) }
							shortcut={ displayShortcut.primaryShift( 'k' ) }
							onClick={ unlinkButton }
							isActive={ true }
						/>
					) }
				</ToolbarGroup>
			</BlockControls>
			{ isSelected && (
				<KeyboardShortcuts
					bindGlobal
					shortcuts={ {
						[ rawShortcut.primary( 'k' ) ]: openLinkControl,
						[ rawShortcut.primaryShift( 'k' ) ]: unlinkButton,
					} }
				/>
			) }
			{ linkControl }
		</>
	);
}

function ButtonEdit( props ) {
	const {
		attributes,
		setAttributes,
		className,
		isSelected,
		onReplace,
		mergeBlocks,
	} = props;

	const {
		pid,
		uuid,
		type,
		borderRadius,
		linkTarget,
		placeholder,
		rel,
		text,
		url,
	} = attributes;

	/**
	 * The following chunk of code is for making sure all CTAs have unique uuids.
	 */
	// TODO REVIEW starting here this needs to be reconciled with index.js
	const isIdUnique = useSelect(
		( select ) =>
			select( 'core/block-editor' )
				.getBlocks()
				.filter(
					( { attributes: { uuid: otherUuid } } ) =>
						otherUuid !== undefined && otherUuid === uuid
				).length <= 1
	);

	/**
	 * Check for missing or invalid attributes, correct them and update.
	 */
	useEffect( () => {
		const update = {};

		if ( uuid === undefined || ! uuid.length || ! isIdUnique ) {
			update.uuid = nanoid();
		}

		if ( pid === undefined || ! pid || pid <= 0 ) {
			update.pid = wp.data.select( 'core/editor' ).getCurrentPostId();
		}

		if ( Object.keys( update ).length ) {
			setAttributes( update );
		}
	}, [ uuid, pid ] );
	// TODO REVIEW end here. See above ^^.

	const onSetLinkRel = useCallback(
		( value ) => {
			setAttributes( { rel: value } );
		},
		[ setAttributes ]
	);

	const onSetType = useCallback(
		( value ) => {
			setAttributes( { type: value } );
		},
		[ setAttributes ]
	);

	// const colors = useEditorFeature( 'color.palette' ) || EMPTY_ARRAY;
	const colors = EMPTY_ARRAY;

	const onToggleOpenInNewTab = useCallback(
		( value ) => {
			const newLinkTarget = value ? '_blank' : undefined;

			let updatedRel = rel;
			if ( newLinkTarget && ! rel ) {
				updatedRel = NEW_TAB_REL;
			} else if ( ! newLinkTarget && rel === NEW_TAB_REL ) {
				updatedRel = undefined;
			}

			setAttributes( {
				linkTarget: newLinkTarget,
				rel: updatedRel,
			} );
		},
		[ rel, setAttributes ]
	);

	const colorProps = getColorAndStyleProps( attributes, colors, true );
	const blockProps = useBlockProps();

	return (
		<>
			<ColorEdit { ...props } />
			<div { ...blockProps }>
				<RichText
					placeholder={ placeholder || __( 'Add text…' ) }
					value={ text }
					onChange={ ( value ) => setAttributes( { text: value } ) }
					withoutInteractiveFormatting
					className={ classnames(
						className,
						'pum-cta-button__link',
						colorProps.className,
						{
							'no-border-radius': borderRadius === 0,
						}
					) }
					style={ {
						borderRadius: borderRadius
							? borderRadius + 'px'
							: undefined,
						...colorProps.style,
					} }
					onSplit={ ( value ) =>
						createBlock( 'core/button', {
							...attributes,
							text: value,
						} )
					}
					onReplace={ onReplace }
					onMerge={ mergeBlocks }
					identifier="text"
				/>
			</div>

			{ 'link' === type && (
				<URLPicker
					url={ url }
					setAttributes={ setAttributes }
					isSelected={ isSelected }
					opensInNewTab={ linkTarget === '_blank' }
					onToggleOpenInNewTab={ onToggleOpenInNewTab }
				/>
			) }

			<InspectorControls>
				<SelectControl
					label={ __(
						'Which type of CTA would you like to use?',
						'popup-maker'
					) }
					options={ Object.values( callToActions ).map(
						( { key: value, label } ) => {
							return { label, value };
						}
					) }
					onChange={ ( value ) => {
						onSetType( value );
					} }
					value={ type }
				/>

				<BorderPanel
					borderRadius={ borderRadius }
					setAttributes={ setAttributes }
				/>
				<PanelBody title={ __( 'Link settings' ) }>
					<ToggleControl
						label={ __( 'Open in new tab' ) }
						onChange={ onToggleOpenInNewTab }
						checked={ linkTarget === '_blank' }
					/>
					<TextControl
						label={ __( 'Link rel' ) }
						value={ rel || '' }
						onChange={ onSetLinkRel }
					/>
				</PanelBody>
			</InspectorControls>
		</>
	);
}

export default ButtonEdit;
