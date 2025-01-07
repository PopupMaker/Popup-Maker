import { Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';

import { CALL_TO_ACTION_STORE } from '@popup-maker/core-data';
import { Editor } from '../components';

import type { CallToAction } from '@popup-maker/core-data';

export type EditProps = {
	onSave?: ( values: CallToAction ) => void;
	onClose?: () => void;
};

const noop = () => {};

const Edit = ( { onSave = noop, onClose = noop }: EditProps ) => {
	// Fetch needed data from the @popup-maker/core-data store.
	const { editorId, isEditorActive, values } = useSelect(
		( select ) => ( {
			editorId: select( CALL_TO_ACTION_STORE ).getEditorId(),
			values: select( CALL_TO_ACTION_STORE ).getEditorValues(),
			isEditorActive: select( CALL_TO_ACTION_STORE ).isEditorActive(),
		} ),
		[]
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

	return (
		<Modal
			title={
				editorId === 'new'
					? __( 'New Call to Action', 'popup-maker' )
					: `#${ values.id } - ${ values.title }`
			}
			className="call-to-action-editor-modal"
			onRequestClose={ onClose }
			shouldCloseOnClickOutside={ false }
		>
			<Editor
				ctaId={ editorId === 'new' ? 'new' : Number( editorId ) }
				onSave={ onSave }
				onClose={ onClose }
			/>
		</Modal>
	);
};

export default Edit;
