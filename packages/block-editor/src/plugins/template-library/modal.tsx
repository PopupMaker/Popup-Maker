/**
 * Popup template picker modal.
 */

import { __, sprintf } from '@wordpress/i18n';
import { Button, Modal, SearchControl, Notice } from '@wordpress/components';
import { useMemo, useState } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { parse } from '@wordpress/blocks';
import { lock } from '@wordpress/icons';
import {
	// @ts-expect-error
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalBlockPreview as BlockPreview,
} from '@wordpress/block-editor';

import { applyRecommendedSettings, canApplySettings } from './apply-settings';
import type { PopupTemplate, TemplateLibraryData } from './types';

const TRIGGER_LABELS: Record< string, string > = {
	auto_open: __( 'Time delay', 'popup-maker' ),
	click_open: __( 'Click', 'popup-maker' ),
	form_submission: __( 'Form submission', 'popup-maker' ),
	exit_intent: __( 'Exit intent', 'popup-maker' ),
	scroll: __( 'Scroll', 'popup-maker' ),
	time_on_site: __( 'Time on site', 'popup-maker' ),
};

const COOKIE_LABELS: Record< string, string > = {
	on_popup_close: __( 'Set cookie when popup is closed', 'popup-maker' ),
	on_popup_open: __( 'Set cookie when popup opens', 'popup-maker' ),
	on_popup_conversion: __( 'Set cookie on conversion', 'popup-maker' ),
	form_submission: __( 'Set cookie on form submission', 'popup-maker' ),
	manual: __( 'Manual cookie', 'popup-maker' ),
};

interface TemplateCardProps {
	template: PopupTemplate;
	tierLabel: string;
	onSelect: ( template: PopupTemplate ) => void;
}

const TemplateCard = ( {
	template,
	tierLabel,
	onSelect,
}: TemplateCardProps ) => {
	const blocks = useMemo(
		() => ( template.content ? parse( template.content ) : [] ),
		[ template.content ]
	);

	return (
		<button
			type="button"
			className={ `pum-template-library__card ${
				template.proRequired ? 'pum-template-library__card--locked' : ''
			}` }
			onClick={ () => onSelect( template ) }
			aria-label={
				template.proRequired
					? sprintf(
							/* translators: %s: template name. */
							__( '%s (upgrade required)', 'popup-maker' ),
							template.name
					  )
					: sprintf(
							/* translators: %s: template name. */
							__( 'Insert %s template', 'popup-maker' ),
							template.name
					  )
			}
		>
			<div className="pum-template-library__card-preview">
				{ template.proRequired ? (
					<div className="pum-template-library__card-lock">
						<Button
							icon={ lock }
							variant="link"
							tabIndex={ -1 }
							label={ __( 'Upgrade required', 'popup-maker' ) }
						/>
					</div>
				) : (
					<BlockPreview
						blocks={ blocks }
						viewportWidth={ template.viewportWidth || 480 }
					/>
				) }
			</div>
			<div className="pum-template-library__card-footer">
				<span className="pum-template-library__card-title">
					{ template.name }
				</span>
				{ 'free' !== template.tier && (
					<span
						className={ `pum-template-library__badge pum-template-library__badge--${ template.tier }` }
					>
						{ tierLabel }
					</span>
				) }
			</div>
			<span className="pum-template-library__card-description">
				{ template.description }
			</span>
		</button>
	);
};

interface ApplySettingsStepProps {
	template: PopupTemplate;
	onFinish: ( apply: boolean ) => void;
}

const ApplySettingsStep = ( {
	template,
	onFinish,
}: ApplySettingsStepProps ) => {
	const { triggers, cookies, notes } = template.recommended;

	return (
		<div className="pum-template-library__apply">
			<p>
				{ __(
					'This template ships with recommended popup settings. Apply them now? You can adjust everything afterward in the Popup Settings box.',
					'popup-maker'
				) }
			</p>
			<ul className="pum-template-library__apply-list">
				{ triggers.map( ( trigger, i ) => (
					<li key={ `t-${ i }` }>
						{ sprintf(
							/* translators: %s: trigger type label. */
							__( 'Trigger: %s', 'popup-maker' ),
							TRIGGER_LABELS[ trigger.type ] ?? trigger.type
						) }
					</li>
				) ) }
				{ cookies.map( ( cookie, i ) => (
					<li key={ `c-${ i }` }>
						{ COOKIE_LABELS[ cookie.event ] ?? cookie.event }
					</li>
				) ) }
			</ul>
			{ notes && (
				<Notice status="info" isDismissible={ false }>
					{ notes }
				</Notice>
			) }
			<div className="pum-template-library__apply-actions">
				<Button variant="primary" onClick={ () => onFinish( true ) }>
					{ __( 'Apply settings', 'popup-maker' ) }
				</Button>
				<Button variant="tertiary" onClick={ () => onFinish( false ) }>
					{ __( 'Skip', 'popup-maker' ) }
				</Button>
			</div>
		</div>
	);
};

interface TemplateLibraryModalProps {
	data: TemplateLibraryData;
	onClose: () => void;
}

const TemplateLibraryModal = ( {
	data,
	onClose,
}: TemplateLibraryModalProps ) => {
	const [ category, setCategory ] = useState< string >( 'all' );
	const [ search, setSearch ] = useState( '' );
	const [ applyStep, setApplyStep ] = useState< PopupTemplate | null >(
		null
	);

	const { insertBlocks, resetBlocks } = useDispatch( 'core/block-editor' );
	const { createSuccessNotice } = useDispatch( 'core/notices' );

	const { blocks: currentBlocks, popupId } = useSelect( ( select ) => {
		const blockEditor = select( 'core/block-editor' ) as unknown as {
			getBlocks: () => {
				name: string;
				attributes?: Record< string, unknown >;
			}[];
		};
		const editor = select( 'core/editor' ) as unknown as {
			getCurrentPostId: () => number;
		};

		return {
			blocks: blockEditor.getBlocks(),
			popupId: editor.getCurrentPostId(),
		};
	}, [] );

	const tierLabel = ( template: PopupTemplate ): string =>
		'pro_plus' === template.tier
			? data.i10n.proPlusLabel
			: data.i10n.proLabel;

	const filtered = useMemo( () => {
		const term = search.trim().toLowerCase();

		return data.templates.filter( ( template ) => {
			if ( 'all' !== category && template.category !== category ) {
				return false;
			}

			if ( ! term ) {
				return true;
			}

			const haystack = [
				template.name,
				template.description,
				...( template.keywords || [] ),
			]
				.join( ' ' )
				.toLowerCase();

			return haystack.includes( term );
		} );
	}, [ data.templates, category, search ] );

	const insertTemplate = ( template: PopupTemplate ) => {
		const blocks = parse( template.content );

		const editorIsEmpty =
			currentBlocks.length === 0 ||
			( currentBlocks.length === 1 &&
				'core/paragraph' === currentBlocks[ 0 ].name &&
				! (
					currentBlocks[ 0 ].attributes?.content as
						| { length?: number }
						| undefined
				 )?.length );

		if ( editorIsEmpty ) {
			resetBlocks( blocks );
		} else {
			insertBlocks( blocks );
		}

		createSuccessNotice(
			sprintf(
				/* translators: %s: template name. */
				__( '“%s” template inserted.', 'popup-maker' ),
				template.name
			),
			{ type: 'snackbar' }
		);

		const hasRecommendations =
			template.recommended.triggers.length > 0 ||
			template.recommended.cookies.length > 0;

		if ( hasRecommendations && canApplySettings() ) {
			setApplyStep( template );
			return;
		}

		onClose();
	};

	const handleSelect = ( template: PopupTemplate ) => {
		if ( template.proRequired ) {
			window.open( template.upgradeUrl, '_blank', 'noopener' );
			return;
		}

		insertTemplate( template );
	};

	const finishApplyStep = ( apply: boolean ) => {
		if ( apply && applyStep ) {
			const applied = applyRecommendedSettings(
				applyStep.recommended,
				popupId
			);

			if ( applied.triggers || applied.cookies ) {
				createSuccessNotice(
					__(
						'Recommended settings added. Review them in the Popup Settings box, then save.',
						'popup-maker'
					),
					{ type: 'snackbar' }
				);
			}
		}

		setApplyStep( null );
		onClose();
	};

	return (
		<Modal
			title={
				applyStep
					? __( 'Recommended settings', 'popup-maker' )
					: __( 'Popup Templates', 'popup-maker' )
			}
			onRequestClose={ onClose }
			className="pum-template-library"
			shouldCloseOnClickOutside={ false }
		>
			{ applyStep ? (
				<ApplySettingsStep
					template={ applyStep }
					onFinish={ finishApplyStep }
				/>
			) : (
				<div className="pum-template-library__browser">
					<div className="pum-template-library__sidebar">
						<SearchControl
							label={ __( 'Search templates', 'popup-maker' ) }
							value={ search }
							onChange={ setSearch }
						/>
						<ul className="pum-template-library__categories">
							<li>
								<Button
									variant="link"
									className={
										'all' === category ? 'is-active' : ''
									}
									onClick={ () => setCategory( 'all' ) }
								>
									{ __( 'All templates', 'popup-maker' ) }
								</Button>
							</li>
							{ Object.entries( data.categories ).map(
								( [ slug, label ] ) => (
									<li key={ slug }>
										<Button
											variant="link"
											className={
												slug === category
													? 'is-active'
													: ''
											}
											onClick={ () =>
												setCategory( slug )
											}
										>
											{ label }
										</Button>
									</li>
								)
							) }
						</ul>
					</div>
					<div className="pum-template-library__grid">
						{ filtered.map( ( template ) => (
							<TemplateCard
								key={ template.slug }
								template={ template }
								tierLabel={ tierLabel( template ) }
								onSelect={ handleSelect }
							/>
						) ) }
						{ ! filtered.length && (
							<p className="pum-template-library__empty">
								{ __(
									'No templates match your search.',
									'popup-maker'
								) }
							</p>
						) }
					</div>
				</div>
			) }
		</Modal>
	);
};

export default TemplateLibraryModal;
