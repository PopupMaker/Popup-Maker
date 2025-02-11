/* eslint-disable @wordpress/i18n-text-domain */
import clsx from 'clsx';
import React from 'react';

import {
	RichText,
	BlockControls,
	useBlockProps,
	InspectorControls,
	// @ts-ignore - Experimental components
	AlignmentControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	// __experimentalGetElementClassName as getElementClassName,
	// @ts-expect-error
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalUseColorProps as useColorProps,
	// @ts-expect-error
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalUseBorderProps as useBorderProps,
	// @ts-expect-error
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalGetShadowClassesAndStyles as useShadowProps,
	// @ts-expect-error
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalGetSpacingClassesAndStyles as useSpacingProps,
	getTypographyClassesAndStyles as useTypographyProps,
	// @ts-expect-error
	useSettings,
	store as blockEditorStore,
	// @ts-ignore - Experimental components
	useBlockEditingMode,
} from '@wordpress/block-editor';
import {
	cloneBlock,
	createBlock,
	getDefaultBlockName,
} from '@wordpress/blocks';
import {
	Flex,
	Button,
	Popover,
	FlexItem,
	TextControl,
	ToolbarButton,
	ToggleControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanel as ToolsPanel,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';
import { __ } from '@popup-maker/i18n';
import { useSelect, useDispatch } from '@wordpress/data';

import { useMergeRefs, useRefEffect } from '@wordpress/compose';
import { useEffect, useState, useRef } from '@wordpress/element';
import { displayShortcut, isKeyboardEvent, ENTER } from '@wordpress/keycodes';
import { linkOff, megaphone, edit, chevronDown, check } from '@wordpress/icons';

import { EntitySelectControl } from '@popup-maker/components';
import { callToActionStore } from '@popup-maker/core-data';
import { Editor as BaseEditor, withModal } from '@popup-maker/cta-editor';

import { removeAnchorTag, useToolsPanelDropdownMenuProps } from '../utils';
import { NEW_TAB_TARGET, NOFOLLOW_REL } from './constants';

import type { BlockInstance } from '@wordpress/blocks';

export const Editor = withModal( BaseEditor );

interface ButtonAttributes {
	tagName: string;
	textAlign?: string;
	linkTarget?: string;
	placeholder?: string;
	rel?: string;
	style?: {
		border?: {
			radius?: number;
		};
	};
	text: string;
	url?: string;
	width?: number;
	metadata?: any;
	ctaId?: number;
}

interface ButtonEditProps {
	attributes: ButtonAttributes;
	setAttributes: ( attrs: Partial< ButtonAttributes > ) => void;
	className?: string;
	isSelected: boolean;
	onReplace: ( blocks: any[] ) => void;
	mergeBlocks: ( forward: boolean ) => void;
	clientId: string;
	context: any;
}

interface WidthPanelProps {
	selectedWidth?: number;
	setAttributes: ( attrs: Partial< ButtonAttributes > ) => void;
}

/**
 * Fill missing types from block editor store.
 */
interface BlockEditorSelectors {
	getBlock: ( clientId: string ) => BlockInstance;
	getBlockRootClientId: ( clientId: string ) => string;
	getBlockIndex: ( clientId: string ) => number;
}

function useEnter( props: { content: string; clientId: string } ) {
	const { replaceBlocks, selectionChange } = useDispatch( blockEditorStore );
	const { getBlock, getBlockRootClientId, getBlockIndex } = useSelect(
		( select ) => {
			const store = select( blockEditorStore ) as BlockEditorSelectors;

			return {
				getBlock: store.getBlock,
				getBlockRootClientId: store.getBlockRootClientId,
				getBlockIndex: store.getBlockIndex,
			};
		},
		[ props.clientId ]
	);

	const propsRef = useRef( props );
	propsRef.current = props;

	return useRefEffect( ( element: HTMLElement ) => {
		function onKeyDown( event: KeyboardEvent ) {
			if ( event.defaultPrevented || event.keyCode !== ENTER ) {
				return;
			}
			const { content, clientId } = propsRef.current;
			if ( content.length ) {
				return;
			}
			event.preventDefault();
			const topParentListBlock = getBlock(
				getBlockRootClientId( clientId )
			);
			const blockIndex = getBlockIndex( clientId );
			const head = cloneBlock( {
				...topParentListBlock,
				innerBlocks: topParentListBlock.innerBlocks.slice(
					0,
					blockIndex
				),
			} );
			const middle = createBlock( getDefaultBlockName() || '' );
			const after = topParentListBlock.innerBlocks.slice(
				blockIndex + 1
			);
			const tail = after.length
				? [
						cloneBlock( {
							...topParentListBlock,
							innerBlocks: after,
						} ),
				  ]
				: [];
			replaceBlocks(
				topParentListBlock.clientId,
				[ head, middle, ...tail ],
				1
			);
			// We manually change the selection here because we are replacing
			// a different block than the selected one.
			selectionChange( middle.clientId );
		}

		element.addEventListener( 'keydown', onKeyDown );
		return () => {
			element.removeEventListener( 'keydown', onKeyDown );
		};
	}, [] );
}

function WidthPanel( { selectedWidth, setAttributes }: WidthPanelProps ) {
	const dropdownMenuProps = useToolsPanelDropdownMenuProps() as {
		label: string;
	};

	return (
		<ToolsPanel
			label={ __( 'Settings', 'default' ) }
			resetAll={ () => setAttributes( { width: undefined } ) }
			dropdownMenuProps={ dropdownMenuProps }
			// as="div"
		>
			<ToolsPanelItem
				label={ __( 'Button width', 'default' ) }
				isShownByDefault
				hasValue={ () => !! selectedWidth }
				onDeselect={ () => setAttributes( { width: undefined } ) }
				as="div"
				// @ts-ignore - Property does exist.
				__nextHasNoMarginBottom
			>
				<ToggleGroupControl
					label={ __( 'Button width', 'default' ) }
					value={ selectedWidth }
					onChange={ ( newWidth ) =>
						setAttributes( { width: Number( newWidth ) } )
					}
					isBlock
					__next40pxDefaultSize
					__nextHasNoMarginBottom
				>
					{ [ 25, 50, 75, 100 ].map( ( widthValue ) => {
						return (
							<ToggleGroupControlOption
								key={ widthValue }
								value={ widthValue }
								label={ `${ widthValue }%` }
							/>
						);
					} ) }
				</ToggleGroupControl>
			</ToolsPanelItem>
		</ToolsPanel>
	);
}

function ButtonEdit( props: ButtonEditProps ) {
	const {
		attributes,
		setAttributes,
		className,
		isSelected,
		onReplace,
		mergeBlocks,
		clientId,
		context,
	} = props;

	const {
		tagName,
		textAlign,
		linkTarget,
		placeholder,
		rel,
		style,
		text,
		url,
		width,
		metadata,
		ctaId,
	} = attributes;

	const TagName = tagName || 'a';

	/**
	 * Flag: Whether the user is explicitly editing the CTA.
	 */
	const [ isExplicitlyEditing, setIsExplicitlyEditing ] = useState( false );

	/**
	 * Flag: Whether to show the editor for creating a new CTA.
	 */
	const [ newCta, setNewCta ] = useState< number | boolean >( false );

	/**
	 * Flag: Whether the user is editing a CTA.
	 *
	 * True when selected and has CTA or when explicitly editing.
	 */
	const isEditingCTA = isSelected && ( isExplicitlyEditing || !! ctaId );

	/**
	 * State: The anchor element for the popover.
	 *
	 * REVIEW: Use internal state instead of a ref to make sure that the component \
	 * REVIEW: re-renders when the popover's anchor updates.
	 */
	const [ popoverAnchor, setPopoverAnchor ] = useState< HTMLElement >();

	/**
	 * Function: Handle keydown events.
	 *
	 * @param {React.KeyboardEvent< HTMLDivElement >} event Keyboard event.
	 */
	function onKeyDown( event: React.KeyboardEvent< HTMLDivElement > ) {
		if ( isKeyboardEvent.primary( event, 'k' ) ) {
			event.preventDefault();
			startEditing();
		} else if ( isKeyboardEvent.primaryShift( event, 'k' ) ) {
			unlink();
			richTextRef.current?.focus();
		}
	}

	// Refs.
	const ref = useRef< HTMLElement >( null );
	const richTextRef = useRef< HTMLInputElement >( null );

	// Block editor props.
	const blockProps = useBlockProps( {
		ref: useMergeRefs( [ setPopoverAnchor, ref ] ),
		onKeyDown,
	} );
	const borderProps = useBorderProps( attributes );
	const colorProps = useColorProps( attributes );
	const spacingProps = useSpacingProps( attributes );
	const shadowProps = useShadowProps( attributes );

	// Block editor state.
	const blockEditingMode = useBlockEditingMode();

	// CTA settings.
	const opensInNewTab = linkTarget === NEW_TAB_TARGET;
	const nofollow = !! rel?.includes( NOFOLLOW_REL );
	const isLinkTag = 'a' === TagName;

	// Get available CTAs from the store
	const { ctas, selectedCTA } = useSelect(
		( select ) => ( {
			ctas: select( callToActionStore ).getCallToActions(),
			selectedCTA: ctaId
				? select( callToActionStore ).getCallToAction( ctaId )
				: undefined,
			// recentlyFetchedCtas:
			// 	select( CALL_TO_ACTION_STORE ).isDispatching(
			// 		'getCallToActions'
			// 	),
		} ),
		[ ctaId ]
	);

	const { createCallToAction } = useDispatch( callToActionStore );

	function startEditing() {
		setIsExplicitlyEditing( true );
	}

	function unlink() {
		setAttributes( {
			ctaId: undefined,
			url: undefined,
			linkTarget: undefined,
			rel: undefined,
		} );
		setIsExplicitlyEditing( false );
	}

	useEffect( () => {
		if ( ! isSelected ) {
			setIsExplicitlyEditing( false );
		}
	}, [ isSelected ] );

	// Update URL when CTA changes
	useEffect( () => {
		if ( ctaId && selectedCTA ) {
			setAttributes( {
				url: `?cta=${ selectedCTA.uuid }`,
				linkTarget: selectedCTA.settings.opensInNewTab
					? NEW_TAB_TARGET
					: undefined,
				rel: selectedCTA.settings.nofollow ? NOFOLLOW_REL : undefined,
			} );
		}
	}, [ ctaId, selectedCTA ] );

	const useEnterRef = useEnter( { content: text, clientId } );
	const mergedRef = useMergeRefs( [
		useEnterRef,
		richTextRef,
	] ) as unknown as React.ForwardedRef< keyof HTMLElementTagNameMap >;

	const [ showAdvancedSettings, setShowAdvancedSettings ] = useState( false );

	// Show popover when:
	// 1. Link tag is selected AND
	// 2. Either:
	//    a. A CTA is selected (showing view mode) OR
	//    b. User is explicitly editing (showing edit mode)
	const showPopover =
		isLinkTag && isSelected && ( !! ctaId || isExplicitlyEditing );

	const [ _forceRefresh, setForceRefresh ] = useState( 0 );

	// If a new CTA is created, don't show the popover
	useEffect( () => {
		async function createNewCTA() {
			if ( false === newCta ) {
				return;
			}

			if ( true === newCta ) {
				const createdCta = await createCallToAction( {
					title: __( 'New call to action', 'popup-maker' ),
					status: 'publish',
				} );

				if ( createdCta ) {
					setNewCta( createdCta.id );
				}
			}

			setForceRefresh( ( prev ) => prev + 1 );
			setIsExplicitlyEditing( true );
		}

		createNewCTA();
	}, [ newCta, createCallToAction ] );

	const [ fluidTypographySettings, layout ] = useSettings(
		'typography.fluid',
		'layout'
	);

	const typographyProps = useTypographyProps( attributes, {
		// @ts-expect-error
		typography: {
			fluid: fluidTypographySettings,
		},
		layout: {
			wideSize: layout?.wideSize,
		},
	} );

	return (
		<>
			<div
				{ ...blockProps }
				className={ clsx( blockProps.className, {
					[ `has-custom-width wp-block-popup-maker-cta-button__width-${ width }` ]:
						width,
					[ `has-custom-font-size` ]: blockProps.style.fontSize,
				} ) }
			>
				<RichText
					ref={ mergedRef }
					aria-label={ __( 'Button text', 'default' ) }
					placeholder={ placeholder || __( 'Add text…', 'default' ) }
					value={ text }
					onChange={ ( value ) =>
						setAttributes( {
							text: removeAnchorTag( value ),
						} )
					}
					// @ts-ignore Property does exist.
					withoutInteractiveFormatting
					className={ clsx(
						className,
						'wp-block-popup-maker-cta-button__link',
						colorProps.className,
						borderProps.className,
						typographyProps.className,
						{
							[ `has-text-align-${ textAlign }` ]: textAlign,
							// For backwards compatibility add style that isn't
							// provided via block support.
							'no-border-radius': style?.border?.radius === 0,
							[ `has-custom-font-size` ]:
								blockProps.style.fontSize,
						}
					) }
					style={ {
						...borderProps.style,
						...colorProps.style,
						...spacingProps.style,
						...shadowProps.style,
						...typographyProps.style,
						writingMode: undefined,
					} }
					onReplace={ onReplace }
					onMerge={ mergeBlocks }
					identifier="text"
				/>
			</div>
			<BlockControls group="block">
				{ blockEditingMode === 'default' && (
					<AlignmentControl
						value={ textAlign }
						onChange={ ( nextAlign ) => {
							setAttributes( { textAlign: nextAlign } );
						} }
					/>
				) }
				{ ! selectedCTA && isLinkTag && (
					<ToolbarButton
						icon={ megaphone }
						title={ __( 'Add Call to Action', 'popup-maker' ) }
						shortcut={ displayShortcut.primary( 'k' ) }
						onClick={ (
							event:
								| React.MouseEvent< HTMLButtonElement >
								| React.MouseEvent< HTMLAnchorElement >
						) => {
							event.preventDefault();
							startEditing();
						} }
					/>
				) }
				{ selectedCTA && isLinkTag && (
					<ToolbarButton
						icon={ linkOff }
						title={ __( 'Remove Call to Action', 'popup-maker' ) }
						shortcut={ displayShortcut.primaryShift( 'k' ) }
						onClick={ unlink }
						isActive
					/>
				) }
			</BlockControls>
			{ showPopover && (
				<Popover
					placement="bottom"
					onClose={ () => {
						setIsExplicitlyEditing( false );
						( richTextRef.current as any )?.focus?.();
					} }
					anchor={ popoverAnchor }
					focusOnMount={ isEditingCTA ? 'firstElement' : false }
					__unstableSlotName="__unstable-block-tools-after"
					shift
					className="block-editor-link-control"
				>
					<div style={ { width: '350px', padding: '10px' } }>
						{ isExplicitlyEditing ? (
							<Flex direction="column">
								<Flex align="center" justify="space-between">
									<FlexItem style={ { flexGrow: 1 } }>
										<EntitySelectControl
											value={ ctaId || 0 }
											onChange={ async (
												newId: number | string
											) => {
												if ( newId === 'create_new' ) {
													setNewCta( true );

													return;
												}
												setAttributes( {
													ctaId: Number( newId ),
												} );
											} }
											hideLabelFromVision
											entityKind="postType"
											entityType="pum_cta"
											placeholder={ __(
												'Search or create CTA…',
												'popup-maker'
											) }
											multiple={ false }
											extraOptions={ [
												{
													value: 'create_new',
													label: __(
														'+ Create new CTA',
														'popup-maker'
													),
												},
											] }
											// forceRefresh={ forceRefresh }
										/>
									</FlexItem>
									<FlexItem>
										<Button
											icon={ check }
											label={ __(
												'Save',
												'popup-maker'
											) }
											onClick={ () =>
												setIsExplicitlyEditing( false )
											}
										/>
									</FlexItem>
								</Flex>

								{ !! ctaId && (
									<>
										<Flex justify="flex-start">
											<Button
												icon={ chevronDown }
												onClick={ () =>
													setShowAdvancedSettings(
														! showAdvancedSettings
													)
												}
											>
												{ __(
													'Advanced',
													'popup-maker'
												) }
											</Button>
										</Flex>

										{ showAdvancedSettings && (
											<Flex direction="column" gap={ 2 }>
												<ToggleControl
													label={ __(
														'Open in new tab',
														'popup-maker'
													) }
													checked={ opensInNewTab }
													onChange={ ( value ) => {
														const newLinkTarget =
															value
																? NEW_TAB_TARGET
																: undefined;
														setAttributes( {
															linkTarget:
																newLinkTarget,
														} );
													} }
													__nextHasNoMarginBottom
												/>
												<ToggleControl
													label={ __(
														'Add rel="nofollow"',
														'popup-maker'
													) }
													checked={ nofollow }
													onChange={ ( value ) => {
														setAttributes( {
															rel: value
																? NOFOLLOW_REL
																: undefined,
														} );
													} }
													__nextHasNoMarginBottom
												/>
											</Flex>
										) }
									</>
								) }
							</Flex>
						) : (
							selectedCTA && (
								<Flex align="center">
									<FlexItem
										style={ {
											flexShrink: 0,
										} }
									>
										{ /* TODO: Add conversion metrics here when available */ }
										{ `${ selectedCTA?.title?.rendered } (#${ selectedCTA?.id })` }
									</FlexItem>
									<Flex justify="flex-end">
										<Button
											icon={ edit }
											label={ __(
												'Edit',
												'popup-maker'
											) }
											onClick={ () =>
												setIsExplicitlyEditing( true )
											}
										/>
										<Button
											icon={ linkOff }
											label={ __(
												'Remove',
												'popup-maker'
											) }
											onClick={ unlink }
										/>
									</Flex>
								</Flex>
							)
						) }
					</div>
				</Popover>
			) }
			<InspectorControls>
				<WidthPanel
					selectedWidth={ width }
					setAttributes={ setAttributes }
				/>
			</InspectorControls>
			<InspectorControls group="advanced">
				{ isLinkTag && (
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Link rel', 'default' ) }
						value={ rel || '' }
						onChange={ ( newRel ) =>
							setAttributes( { rel: newRel } )
						}
					/>
				) }
			</InspectorControls>

			{ typeof newCta === 'number' && newCta > 0 && (
				<Editor
					id={ newCta }
					defaultValues={ {
						status: 'publish',
					} }
					onSave={ ( values ) => {
						setAttributes( {
							ctaId: values.id,
						} );
					} }
					onClose={ () => {
						setNewCta( false );
					} }
				/>
			) }
		</>
	);
}

export default ButtonEdit;
