import { Fragment, useRef } from '@wordpress/element';

import { useEditorHeaderActions } from '../../registry';

import type { EditableCta } from '@popup-maker/core-data';
import type { EditorHeaderActionContext } from '../../registry';

const EditorHeaderActions = ( {
	values,
	closeModal,
}: EditorHeaderActionContext< EditableCta > & {
	closeModal: () => void;
} ) => {
	const lastGroup = useRef< string | undefined >( undefined );

	const headerActions = useEditorHeaderActions();

	const headerActionsContext: EditorHeaderActionContext< EditableCta > = {
		values,
		closeModal,
	};

	/**
	 * Separates new groups of options with a horizontal line.
	 * @param {Object} props
	 * @param {string} props.group
	 */
	const GroupSeparator = ( { group }: { group?: string } ) => {
		if ( ! group || group === lastGroup.current ) {
			return null;
		}

		const previousGroup = lastGroup.current;
		lastGroup.current = group;

		return previousGroup ? <span>|</span> : null;
	};

	const renderContent = () => {
		lastGroup.current = undefined;

		return (
			<>
				{ headerActions.map( ( { id, group, render: Component } ) => {
					return (
						<Fragment key={ id }>
							<GroupSeparator group={ group } />
							<Component { ...headerActionsContext } />
						</Fragment>
					);
				} ) }
			</>
		);
	};

	return <>{ renderContent() }</>;
};

export default EditorHeaderActions;
