import { __ } from '@popup-maker/i18n';
import clsx from 'clsx';

import type { NotificationCategory } from '../types';

type FilterKey = 'all' | NotificationCategory;

interface Filter {
	key: FilterKey;
	label: string;
}

// Order mirrors the design's filter chip row.
const FILTERS: Filter[] = [
	{ key: 'all', label: __( 'All', 'popup-maker' ) },
	{ key: 'feature', label: __( 'What’s New', 'popup-maker' ) },
	{ key: 'recommendation', label: __( 'Tips', 'popup-maker' ) },
	{ key: 'announcement', label: __( 'News', 'popup-maker' ) },
	{ key: 'offer', label: __( 'Offers', 'popup-maker' ) },
];

interface Props {
	counts: Record< FilterKey, number >;
	active: FilterKey;
	onChange: ( filter: FilterKey ) => void;
}

export const NotificationFilters = ( {
	counts,
	active,
	onChange,
}: Props ): JSX.Element => (
	<div className="pum-notification-filters">
		{ FILTERS.map( ( filter ) => {
			const count = counts[ filter.key ] || 0;

			// Hide empty category chips. "all" always shows.
			if ( filter.key !== 'all' && count === 0 ) {
				return null;
			}

			return (
				<button
					type="button"
					key={ filter.key }
					data-filter={ filter.key }
					className={ clsx( 'pum-notification-chip', {
						'is-active': active === filter.key,
					} ) }
					onClick={ () => onChange( filter.key ) }
				>
					{ filter.key !== 'all' && (
						<span className="pum-notification-chip__dot" />
					) }
					{ filter.label }
					<span className="pum-notification-chip__count">
						{ count }
					</span>
				</button>
			);
		} ) }
	</div>
);
