import {
	Button,
	Notice,
	Spinner,
	TabPanel,
	ToggleControl,
} from '@wordpress/components';
import { link } from '@wordpress/icons';
import { __, sprintf } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { useEffect, useState } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import clsx from 'clsx';

import {
	Status,
	CALL_TO_ACTION_STORE,
	validateCallToAction,
	type CallToAction,
} from '@popup-maker/core-data';
import type { TabComponent } from '@popup-maker/types';

import useEditor from '../hooks/use-editor';
import GeneralTab from '../edit/general';
import { initCustomFields } from '../edit/custom-fields';
import { initLinkTypeFields } from '../edit/link-type-fields';

export const documenationUrl =
	'https://wppopupmaker.com/docs/?utm_campaign=documentation&utm_source=call-to-action-editor&utm_medium=plugin-ui&utm_content=footer-documentation-link';

export type EditorProps = {
	ctaId: number | 'new';
	onSave?: ( values: CallToAction ) => void;
	onClose?: () => void;
	showDocumentationLink?: boolean;
	showActions?: boolean;
};

export type EditorTabProps = {
	values: CallToAction;
	updateValues: ( values: Partial< CallToAction > ) => void;
	updateSettings: ( settings: Partial< CallToAction[ 'settings' ] > ) => void;
	onSave?: ( values: CallToAction ) => void;
	onClose?: () => void;
};

const noop = () => {};

// Initialize custom fields via filters.
initCustomFields();

// Initialize link type cta fields.
initLinkTypeFields();

export const Editor = ( {
	ctaId = 'new',
	onSave = noop,
	onClose = noop,
	showDocumentationLink = true,
	showActions = true,
}: EditorProps ) => {
	const { tab, setTab } = useEditor();
	const [ triedSaving, setTriedSaving ] = useState< boolean >( false );
	const [ errorMessage, setErrorMessage ] = useState<
		| string
		| {
				message: string;
				tabName?: string;
				field?: string;
				[ key: string ]: any;
		  }
		| null
	>( null );

	// Fetch needed data from the @popup-maker/core-data & @wordpress/data stores.
	const { values, isSaving, dispatchStatus, dispatchErrors } = useSelect(
		( select ) => ( {
			values: select( CALL_TO_ACTION_STORE ).getEditorValues(),
			isSaving: select( CALL_TO_ACTION_STORE ).isDispatching( [
				'createCallToAction',
				'updateCallToAction',
			] ),
			dispatchStatus: {
				create: select( CALL_TO_ACTION_STORE ).getDispatchStatus(
					'createCallToAction'
				),
				update: select( CALL_TO_ACTION_STORE ).getDispatchStatus(
					'updateCallToAction'
				),
			},
			dispatchErrors: {
				create: select( CALL_TO_ACTION_STORE ).getDispatchError(
					'createCallToAction'
				),
				update: select( CALL_TO_ACTION_STORE ).getDispatchError(
					'updateCallToAction'
				),
			},
		} ),
		[]
	);

	// Get action dispatchers.
	const {
		updateEditorValues: updateValues,
		createCallToAction,
		updateCallToAction,
		clearEditorData,
	} = useDispatch( CALL_TO_ACTION_STORE );

	useEffect( () => {
		if ( ! triedSaving ) {
			return;
		}

		if (
			Status.Success === dispatchStatus.create ||
			Status.Success === dispatchStatus.update
		) {
			handleClose();
			return;
		}

		const error = dispatchErrors.create ?? dispatchErrors.update;

		if ( typeof error !== 'undefined' ) {
			setErrorMessage( error );
		}
	}, [ dispatchStatus, dispatchErrors, triedSaving ] );

	// When no values, don't show the editor.
	if ( ! values ) {
		return (
			<>
				{ __(
					'Editor requires a valid call to action.',
					'popup-maker'
				) }
			</>
		);
	}

	/**
	 * Trigger the correct save action.
	 */
	function saveCallToAction() {
		if ( ! values ) {
			return;
		}

		const exists = ctaId === 'new' ? false : ctaId > 0;

		const valuesToSave = {
			...values,
			settings: {
				...values.settings,
			},
		};

		if ( exists ) {
			updateCallToAction( valuesToSave );
		} else {
			createCallToAction( valuesToSave );
		}

		setTriedSaving( true );
		setErrorMessage( null );

		onSave( valuesToSave );
	}

	/**
	 * Update settings for the given call to action.
	 *
	 * @param {Partial< CallToAction[ 'settings' ] >} newSettings Updated settings.
	 */
	const updateSettings = (
		newSettings: Partial< CallToAction[ 'settings' ] >
	) => {
		updateValues( {
			...values,
			settings: {
				...values?.settings,
				...newSettings,
			},
		} );
	};

	/**
	 * Handles closing the editor
	 */
	const handleClose = () => {
		clearEditorData();
		onClose();
	};

	// Define props passed to each child tab component.
	const componentProps = {
		values,
		updateValues,
		updateSettings,
		onSave,
		onClose,
	};

	/**
	 * Define the tabs to show in the editor.
	 *
	 * @param {TabComponent[]} tabs Array of tab components.
	 *
	 * @return {TabComponent[]} Array of tab components.
	 */
	const tabs: TabComponent[] = applyFilters(
		'popupMaker.callToActionEditor.tabs',
		[
			{
				name: 'general',
				title: __( 'General', 'popup-maker' ),
				comp: () => <GeneralTab { ...componentProps } />,
			},
		]
	) as TabComponent[];

	if ( typeof errorMessage === 'object' && errorMessage?.tabName?.length ) {
		const _tab = tabs.find( ( t ) => t.name === errorMessage.tabName );

		if ( _tab ) {
			tabs[ tabs.indexOf( _tab ) ].className = tabs[
				tabs.indexOf( _tab )
			].className
				? tabs[ tabs.indexOf( _tab ) ].className + ' error'
				: 'error';
		}
	}

	return (
		<div className="call-to-action-editor">
			<div
				className={ clsx( [
					'call-to-action-enabled-toggle',
					values.status === 'publish' ? 'enabled' : 'disabled',
				] ) }
			>
				<ToggleControl
					label={
						values.status === 'publish'
							? __( 'Enabled', 'popup-maker' )
							: __( 'Disabled', 'popup-maker' )
					}
					checked={ values.status === 'publish' }
					onChange={ ( checked ) =>
						updateValues( {
							...values,
							status: checked ? 'publish' : 'draft',
						} )
					}
				/>
			</div>

			{ errorMessage && (
				<Notice
					status="error"
					className="call-to-action-editor-error"
					onDismiss={ () => {
						setErrorMessage( null );
					} }
				>
					{ typeof errorMessage === 'string'
						? errorMessage
						: errorMessage.message }
				</Notice>
			) }

			<TabPanel
				orientation="vertical"
				initialTabName={ tab ?? 'general' }
				onSelect={ setTab }
				// @ts-ignore This is a bug in the @types/wordpress__components package.
				tabs={ tabs }
				className="editor-tabs"
			>
				{ ( { title, comp } ) =>
					typeof comp === 'undefined' ? title : comp()
				}
			</TabPanel>

			{ showActions && (
				<div className="editor-actions">
					<Button
						text={ __( 'Cancel', 'popup-maker' ) }
						variant="tertiary"
						isDestructive={ true }
						onClick={ handleClose }
						disabled={ isSaving }
						className="cancel-button"
					/>
					<Button
						variant="primary"
						disabled={ isSaving }
						onClick={ () => {
							const validation = validateCallToAction( values );
							if ( true !== validation ) {
								if ( typeof validation === 'object' ) {
									setErrorMessage( validation );
								}
								return;
							}
							saveCallToAction();
						} }
					>
						{ isSaving && <Spinner /> }
						{ ctaId === 'new'
							? __( 'Add Call to Action', 'popup-maker' )
							: __( 'Save Call to Action', 'popup-maker' ) }
					</Button>

					{ showDocumentationLink && (
						<Button
							text={ __( 'Documentation', 'popup-maker' ) }
							href={ documenationUrl }
							target="_blank"
							icon={ link }
							iconSize={ 20 }
						/>
					) }
				</div>
			) }
		</div>
	);
};

export default Editor;
