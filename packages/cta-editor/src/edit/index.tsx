import clsx from 'clsx';

import {
	Button,
	Modal,
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

import {
	Status,
	CALL_TO_ACTION_STORE,
	validateCallToAction,
} from '@popup-maker/core-data';

import useEditor from '../hooks/use-editor';
import GeneralTab from './general';

export const documenationUrl =
	'https://wppopupmaker.com/docs/?utm_campaign=documentation&utm_source=call-to-action-editor&utm_medium=plugin-ui&utm_content=footer-documentation-link';

import type { TabComponent } from '@popup-maker/types';
import type { CallToAction } from '@popup-maker/core-data';

export type EditProps = {
	onSave?: ( values: CallToAction ) => void;
	onClose?: () => void;
};

export type EditTabProps = EditProps & {
	values: CallToAction;
	updateValues: ( values: Partial< CallToAction > ) => void;
	updateSettings: ( settings: Partial< CallToAction[ 'settings' ] > ) => void;
};

const noop = () => {};

const Edit = ( { onSave = noop, onClose = noop }: EditProps ) => {
	const { tab, setTab, clearEditorParams } = useEditor();
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
	const {
		editorId,
		isEditorActive,
		values,
		isSaving,
		dispatchStatus,
		dispatchErrors,
	} = useSelect(
		( select ) => ( {
			editorId: select( CALL_TO_ACTION_STORE ).getEditorId(),
			values: select( CALL_TO_ACTION_STORE ).getEditorValues(),
			isEditorActive: select( CALL_TO_ACTION_STORE ).isEditorActive(),
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

	useEffect(
		() => {
			return clearEditorParams;
		},
		// eslint-disable-next-line react-hooks/exhaustive-deps
		[]
	);

	useEffect(
		() => {
			if ( ! triedSaving ) {
				return;
			}

			if (
				Status.Success === dispatchStatus.create ||
				Status.Success === dispatchStatus.update
			) {
				closeEditor();
				return;
			}

			const error = dispatchErrors.create ?? dispatchErrors.update;

			if ( typeof error !== 'undefined' ) {
				setErrorMessage( error );
			}
		},
		// eslint-disable-next-line react-hooks/exhaustive-deps
		[ dispatchStatus, dispatchErrors ]
	);

	// If the editor isn't active, return empty.
	if ( ! isEditorActive ) {
		return null;
	}

	// When no editorId, dont' show the editor.
	if ( ! editorId ) {
		return <>{ __( 'Editor requires a valid id', 'popup-maker' ) }</>;
	}

	// When no values, dont' show the editor.
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
		if ( ! editorId || ! values ) {
			return;
		}

		const exists = editorId === 'new' ? false : editorId > 0;

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
	 * Handles closing the editor and removing url params.
	 */
	const closeEditor = () => {
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

	// Define the modal title dynamically using editorId.
	const modalTitle = sprintf(
		// translators: 1. Id of set to edit.
		__( 'Call to Action Editor%s', 'popup-maker' ),
		editorId === 'new'
			? ': ' + __( 'New Call to Action', 'popup-maker' )
			: `: #${ values.id } - ${ values.title }`
	);

	return (
		<Modal
			title={ modalTitle }
			className="call-to-action-editor"
			onRequestClose={ () => closeEditor() }
			shouldCloseOnClickOutside={ false }
		>
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

			<div className="modal-actions">
				<Button
					text={ __( 'Cancel', 'popup-maker' ) }
					variant="tertiary"
					isDestructive={ true }
					onClick={ () => closeEditor() }
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
					{ editorId === 'new'
						? __( 'Add Call to Action', 'popup-maker' )
						: __( 'Save Call to Action', 'popup-maker' ) }
				</Button>

				<Button
					text={ __( 'Documentation', 'popup-maker' ) }
					href={ documenationUrl }
					target="_blank"
					icon={ link }
					iconSize={ 20 }
				/>
			</div>
		</Modal>
	);
};

export default Edit;
