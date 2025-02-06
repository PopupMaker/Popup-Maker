import { __ } from '@popup-maker/i18n';
import { callToActionStore, type EditableCta } from '@popup-maker/core-data';

import { Button } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';

import type { EditorHeaderActionContext } from '../../../registry';

const HistoryEditorHeaderAction = ( {
	values,
}: EditorHeaderActionContext< EditableCta > ) => {
	const isSaving = useSelect(
		( select ) =>
			select( callToActionStore ).isResolving( 'updateCallToAction' ),
		[]
	);

	const { hasUndo, hasRedo, hasEdits } = useSelect(
		( select ) => {
			if ( ! values.id ) {
				return {
					hasUndo: false,
					hasRedo: false,
					hasEdits: false,
				};
			}

			const store = select( callToActionStore );

			return {
				hasUndo: store.hasUndo( values.id ),
				hasRedo: store.hasRedo( values.id ),
				hasEdits: store.hasEdits( values.id ),
			};
		},
		// eslint-disable-next-line react-hooks/exhaustive-deps
		[ values, isSaving ]
	);

	const { undo, redo } = useDispatch( callToActionStore );

	return (
		<>
			{ hasEdits && (
				<>
					<Button
						disabled={ isSaving || ! hasUndo }
						variant="tertiary"
						icon={ 'undo' }
						aria-label={ __( 'Undo', 'popup-maker' ) }
						onClick={ () => undo( values.id ) }
					/>

					<Button
						disabled={ isSaving || ! hasRedo }
						variant="tertiary"
						icon={ 'redo' }
						aria-label={ __( 'Redo', 'popup-maker' ) }
						onClick={ () => redo( values.id ) }
					/>
				</>
			) }
		</>
	);
};

export default {
	id: 'history',
	priority: 99,
	render: HistoryEditorHeaderAction,
};
