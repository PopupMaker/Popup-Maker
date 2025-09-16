import './filters.scss';

import { Fragment, useCallback, useRef } from '@wordpress/element';
import { useList } from '../../../context';
import { useListFilters } from '../../../registry';

const ListFilters = () => {
	const {
		filters = {},
		setFilters,
		callToActions = [],
		filteredCallToActions = [],
	} = useList();

	const registeredFilters = useListFilters();
	const lastGroup = useRef< string | undefined >( undefined );

	/**
	 * Separates new groups of options with a separator (currently returns null).
	 * @param {Object} props
	 * @param {string} props.group
	 */
	const GroupSeparator = useCallback( ( { group }: { group?: string } ) => {
		if ( ! group || group === lastGroup.current ) {
			return null;
		}

		const previousGroup = lastGroup.current;
		lastGroup.current = group;

		// Currently returning null as we don't need separators yet,
		// but following the pattern for future extensibility
		return previousGroup ? null : null;
	}, [] );

	const renderContent = useCallback( () => {
		lastGroup.current = undefined;

		return (
			<>
				{ registeredFilters.map(
					( { id, group, render: FilterComponent } ) => (
						<Fragment key={ id }>
							<GroupSeparator group={ group } />
							<FilterComponent
								filters={ filters }
								setFilters={ setFilters }
								onClose={ () => {} }
								items={ callToActions }
								filteredItems={ filteredCallToActions }
							/>
						</Fragment>
					)
				) }
			</>
		);
	}, [
		registeredFilters,
		filters,
		setFilters,
		callToActions,
		filteredCallToActions,
		GroupSeparator,
	] );

	if ( registeredFilters.length === 0 ) {
		return null;
	}

	return <div className="list-table-filters">{ renderContent() }</div>;
};

export default ListFilters;
