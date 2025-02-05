import { __ } from '@popup-maker/i18n';
import { useEditor } from '../../components';
import { Button } from '@wordpress/components';
import type { ListQuickActionContext } from '../../registry';

export const EditItemQuickAction = ( { values }: ListQuickActionContext ) => {
	// Get the shared method for setting editor Id & query params.
	const { setEditorId } = useEditor();

	return (
		<>
			{ `${ __( 'ID', 'popup-maker' ) }: ${ values.id }` }
			<Button
				text={ __( 'Edit', 'popup-maker' ) }
				variant="link"
				onClick={ () => setEditorId( values.id ) }
			/>
		</>
	);
};

export default {
	id: 'edit',
	group: 'general',
	priority: -1,
	render: EditItemQuickAction,
};
