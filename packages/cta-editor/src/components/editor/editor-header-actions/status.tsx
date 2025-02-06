import { __ } from '@popup-maker/i18n';
import { callToActionStore, type EditableCta } from '@popup-maker/core-data';

import { ToggleControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';

import type { EditorHeaderActionContext } from '../../../registry';
import clsx from 'clsx';

const StatusEditorHeaderAction = ( {
	values,
}: EditorHeaderActionContext< EditableCta > ) => {
	const isSaving = useSelect(
		( select ) =>
			select( callToActionStore ).isResolving( 'updateCallToAction' ),
		[]
	);

	const { updateEditorValues } = useDispatch( callToActionStore );

	return (
		<div
			className={ clsx( [
				'call-to-action-enabled-toggle',
				values?.status === 'publish' ? 'enabled' : 'disabled',
			] ) }
			style={ {
				minWidth: '11.5ch',
			} }
		>
			<ToggleControl
				disabled={ isSaving }
				label={
					values?.status === 'publish'
						? __( 'Enabled', 'popup-maker' )
						: __( 'Disabled', 'popup-maker' )
				}
				checked={ values?.status === 'publish' }
				onChange={ ( checked ) =>
					updateEditorValues( {
						id: values.id,
						status: checked ? 'publish' : 'draft',
					} )
				}
				__nextHasNoMarginBottom
			/>
		</div>
	);
};

export default {
	id: 'status',
	priority: 100,
	render: StatusEditorHeaderAction,
};
