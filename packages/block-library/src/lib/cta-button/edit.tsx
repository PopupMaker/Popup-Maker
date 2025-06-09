/* eslint-disable @wordpress/i18n-text-domain */
import clsx from 'clsx';
import React, { useCallback } from 'react';

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
import { decodeEntities } from '@wordpress/html-entities';
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
	Notice,
	Panel,
	PanelBody,
	Icon,
} from '@wordpress/components';
import { __ } from '@popup-maker/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

import { useMergeRefs, useRefEffect } from '@wordpress/compose';
import { useEffect, useState, useRef } from '@wordpress/element';
import { displayShortcut, isKeyboardEvent, ENTER } from '@wordpress/keycodes';
import {
	linkOff,
	megaphone,
	edit,
	chevronDown,
	check,
	chevronUp,
	external,
	settings,
} from '@wordpress/icons';

import { CallToActionSelectControl } from '@popup-maker/components';
import { callToActionStore } from '@popup-maker/core-data';
import { Editor as BaseEditor, withModal } from '@popup-maker/cta-editor';

import { removeAnchorTag, useToolsPanelDropdownMenuProps } from '../utils';
import { NEW_TAB_TARGET, NOFOLLOW_REL } from './constants';

import type { BlockInstance } from '@wordpress/blocks';

const Editor = withModal( BaseEditor );

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
	const [ popoverAnchor, setPopoverAnchor ] = useState< HTMLElement | null >(
		null
	);

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
	const { selectedCTA } = useSelect(
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

	// Get current post information to conditionally add post ID to URL
	const { currentPostId, currentPostType } = useSelect(
		( select ) => ( {
			currentPostId: select( editorStore ).getCurrentPostId(),
			currentPostType: select( editorStore ).getCurrentPostType(),
		} ),
		[]
	);

	// Get localized home URL
	const homeUrl = window.popupMakerBlockLibrary?.homeUrl || '/';

	/**
	 * Helper function to generate CTA URLs with proper base URL and conditional parameters.
	 * Has direct access to component scope variables.
	 *
	 * @param {string}  ctaUuid - The CTA UUID
	 * @param {boolean} notrack - Whether to add notrack parameter
	 * @return {string} The generated CTA URL
	 */
	const generateCtaUrl = useCallback(
		( ctaUuid: string, notrack = false ): string => {
			const params = new URLSearchParams();
			params.set( 'cta', ctaUuid );

			// Add post ID if editing a popup
			if ( currentPostType === 'popup' && currentPostId ) {
				params.set( 'pid', currentPostId.toString() );
			}

			// Add notrack parameter if requested
			if ( notrack ) {
				params.set( 'notrack', '1' );
			}

			// Remove trailing slash from home URL and add query parameters
			const baseUrl = homeUrl.replace( /\/$/, '' );
			return `${ baseUrl }/?${ params.toString() }`;
		},
		[ currentPostId, currentPostType, homeUrl ]
	);

	const { createCallToAction, changeEditorId } =
		useDispatch( callToActionStore );

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
		if ( ctaId ) {
			if ( selectedCTA ) {
				// Generate CTA URL using helper function
				const ctaUrl = generateCtaUrl( selectedCTA.uuid );

				setAttributes( {
					url: ctaUrl,
					linkTarget: selectedCTA.settings.opensInNewTab
						? NEW_TAB_TARGET
						: undefined,
					rel: selectedCTA.settings.nofollow
						? NOFOLLOW_REL
						: undefined,
				} );
			} else {
				setAttributes( {
					url: undefined,
					linkTarget: undefined,
					rel: undefined,
				} );
			}
		}
	}, [
		ctaId,
		selectedCTA,
		currentPostType,
		currentPostId,
		homeUrl,
		setAttributes,
		generateCtaUrl,
	] );

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
	// 3. We have a valid anchor element
	const showPopover =
		isLinkTag &&
		isSelected &&
		( !! ctaId || isExplicitlyEditing ) &&
		!! popoverAnchor;

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
					changeEditorId( createdCta.id );
					setNewCta( createdCta.id );
					// Set editing state after the CTA is created, not before
					setIsExplicitlyEditing( true );
				} else {
					// Reset if creation failed
					setNewCta( false );
				}
			} else if ( typeof newCta === 'number' && newCta > 0 ) {
				// CTA was already created, just ensure editing state
				setIsExplicitlyEditing( true );
			}

			setForceRefresh( ( prev ) => prev + 1 );
		}

		createNewCTA();
	}, [ newCta, createCallToAction, changeEditorId ] );

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

	const ctaErrorFromStore = useSelect(
		( select ) => {
			const state = select( callToActionStore );
			const message = ctaId
				? state.getFetchError( ctaId ) || null
				: state.getFetchError() || null;

			return message === null
				? null
				: message.replace(
						'Invalid post ID.',
						__( 'Call to action not found.', 'popup-maker' )
				  );
		},
		[ ctaId ]
	);

	return (
		<>
			<div
				{ ...blockProps }
				className={ clsx( blockProps.className, {
					[ `has-custom-width wp-block-popup-maker-cta-button__width-${ width }` ]:
						width,
					[ `has-custom-font-size` ]: blockProps.style.fontSize,
				} ) }
				style={ {
					...blockProps.style,
					border: ctaErrorFromStore ? '2px solid red' : undefined,
				} }
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
					<div style={ { width: '300px', padding: '10px' } }>
						{ ctaErrorFromStore && (
							<div className="pum-cta-error-notice">
								<Notice status="error" isDismissible={ false }>
									{ ctaErrorFromStore }
								</Notice>
							</div>
						) }
						{ isExplicitlyEditing || ctaErrorFromStore ? (
							<Flex direction="column">
								<Flex align="center" justify="space-between">
									<FlexItem style={ { flexGrow: 1 } }>
										<CallToActionSelectControl
											value={
												ctaId && ! ctaErrorFromStore
													? ctaId
													: 0
											}
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
												// Close editing mode after selection
												setIsExplicitlyEditing( false );
											} }
											hideLabelFromVision
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
							</Flex>
						) : (
							selectedCTA && (
								<Flex direction="column" gap={ 3 }>
									{ /* Header with title and ID badge */ }
									<Flex
										justify="space-between"
										align="center"
									>
										<div
											style={ {
												fontSize: '16px',
												fontWeight: '500',
												color: '#1e1e1e',
												overflow: 'hidden',
												textOverflow: 'ellipsis',
												whiteSpace: 'nowrap',
											} }
											title={ decodeEntities(
												selectedCTA?.title?.rendered
											) }
										>
											{ decodeEntities(
												selectedCTA?.title?.rendered
											) }
										</div>

										<Button
											icon={ linkOff }
											variant="link"
											size="small"
											style={ {
												textDecoration: 'none',
												// color: '#1e1e1e',
											} }
											isDestructive={ true }
											onClick={ unlink }
											label={ __(
												'Remove',
												'popup-maker'
											) }
										></Button>
									</Flex>
									{ /* Action buttons */ }
									<Flex
										justify="space-between"
										align="center"
										style={ {
											color: '#1e1e1e',
										} }
									>
										<div
											style={ {
												fontSize: '12px',
												color: '#666',
												backgroundColor: '#f0f0f0',
												padding: '2px 8px',
												borderRadius: '12px',
												fontWeight: '500',
												flexGrow: 0.5,
												overflow: 'hidden',
												textOverflow: 'ellipsis',
												whiteSpace: 'nowrap',
											} }
										>
											ID: { selectedCTA?.id }
										</div>
										<Button
											icon={ external }
											variant="tertiary"
											style={ {
												borderRadius: '6px',
												textDecoration: 'none',
												color: '#1e1e1e',
												padding: '.25rem 0.75rem',
												// boxShadow:
												// 	'inset 0 0 0 1px #e4e4e7,0 0 0 currentColor',
												flexGrow: 1,
											} }
											/* Preview link with tracking bypass */
											href={ generateCtaUrl(
												selectedCTA.uuid,
												true
											) }
											target="_blank"
											rel="noopener noreferrer"
											showTooltip={ true }
											label={ __(
												'Preview Call To Action',
												'popup-maker'
											) }
										>
											{ __( 'Preview', 'popup-maker' ) }
										</Button>
										<Button
											icon={ edit }
											variant="tertiary"
											style={ {
												borderRadius: '6px',

												textDecoration: 'none',
												color: '#1e1e1e',
												padding: '.25rem 0.75rem',
												// boxShadow:
												// 	'inset 0 0 0 1px #e4e4e7,0 0 0 currentColor',
												flexGrow: 1,
											} }
											href={ `edit.php?post_type=popup&page=popup-maker-call-to-actions&edit=${ selectedCTA.id }` }
											target="_blank"
											label={ __(
												'Edit Call to Action',
												'popup-maker'
											) }
											showTooltip={ true }
										>
											{ __( 'Edit', 'popup-maker' ) }
										</Button>
									</Flex>

									{ /* Advanced settings toggle */ }
									<Button
										icon={ settings }
										variant="secondary"
										onClick={ () =>
											setShowAdvancedSettings(
												! showAdvancedSettings
											)
										}
										style={ {
											width: '100%',
											justifyContent: 'space-between',
											border: '1px solid #ddd',
											borderRadius: '6px',
											padding: '8px 12px',
										} }
										showTooltip={ true }
										label={ __(
											'Advanced Options',
											'popup-maker'
										) }
									>
										{ __(
											'Advanced Options',
											'popup-maker'
										) }
										<Icon
											icon={
												showAdvancedSettings
													? chevronUp
													: chevronDown
											}
										/>
									</Button>

									{ /* Advanced settings panel */ }
									{ showAdvancedSettings && (
										<Flex direction="column" gap={ 3 }>
											<ToggleControl
												label={ __(
													'Open in new window',
													'popup-maker'
												) }
												checked={ opensInNewTab }
												onChange={ ( value ) => {
													setAttributes( {
														linkTarget: value
															? NEW_TAB_TARGET
															: undefined,
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
											{ /* <ToggleControl
												label={ __(
													'Track clicks',
													'popup-maker'
												) }
												checked={ true }
												onChange={ () => {} }
												__nextHasNoMarginBottom
											/> */ }
										</Flex>
									) }
								</Flex>
							)
						) }
					</div>
				</Popover>
			) }
			{ /* TODO Add a panel for the CTA settings, including option to select the CTA to be used.	 */ }
			<InspectorControls group="settings">
				<Panel header={ __( 'CTA Settings', 'popup-maker' ) }>
					<PanelBody>
						<CallToActionSelectControl
							label={ __(
								'Choose a Call to Action',
								'popup-maker'
							) }
							value={ ctaId }
							placeholder={ __(
								'Search or create CTA…',
								'popup-maker'
							) }
							onChange={ async ( newId: number | string ) => {
								if ( newId === 'create_new' ) {
									setNewCta( true );
									return;
								}
								setAttributes( {
									ctaId: Number( newId ),
								} );
								// Ensure editing state is closed after selection from inspector
								setIsExplicitlyEditing( false );
							} }
							extraOptions={ [
								{
									value: 'create_new',
									label: __(
										'+ Create new CTA',
										'popup-maker'
									),
								},
							] }
						/>
					</PanelBody>
				</Panel>
			</InspectorControls>
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
					key={ newCta }
					id={ newCta }
					defaultValues={ { status: 'publish' } }
					onSave={ ( values ) => {
						setAttributes( { ctaId: values.id } );
					} }
					closeOnSave={ true }
					onClose={ () => {
						setNewCta( false );
					} }
				/>
			) }
		</>
	);
}

export default ButtonEdit;
/* eslint-enable @wordpress/i18n-text-domain */
