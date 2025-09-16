import { Fragment, useRef } from '@wordpress/element';

import { useListQuickActions } from '../../../registry';
import type { CallToAction } from '@popup-maker/core-data';

type Props = {
	values: CallToAction< 'edit' >;
};

const ListQuickActions = ( { values }: Props ) => {
	const lastGroup = useRef< string | undefined >( undefined );

	const listQuickActions = useListQuickActions();

	const quickActionsContext = {
		values,
	};

	if ( listQuickActions.length === 0 ) {
		return null;
	}

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
				{ listQuickActions.map(
					( { id, group, render: Component } ) => {
						return (
							<Fragment key={ id }>
								<GroupSeparator group={ group } />
								<Component { ...quickActionsContext } />
							</Fragment>
						);
					}
				) }
			</>
		);
	};

	return <div className="item-actions">{ renderContent() }</div>;
};

export default ListQuickActions;
