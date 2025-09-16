import {
	registerListBulkAction,
	registerListFilter,
	registerListOption,
	registerListQuickAction,
} from './registry';

import {
	listBulkActions,
	listQuickActions,
	listOptions,
	listFilters,
} from './registries';

const init = () => {
	// Register core bulk list actions.
	Object.values( listBulkActions ).forEach( ( action ) => {
		registerListBulkAction( action );
	} );

	// Register core list options.
	Object.values( listOptions ).forEach( ( option ) => {
		registerListOption( option );
	} );

	// Register core list quick actions.
	Object.values( listQuickActions ).forEach( ( action ) => {
		registerListQuickAction( action );
	} );

	// Register core list filters.
	Object.values( listFilters ).forEach( ( filter ) => {
		registerListFilter( filter );
	} );
};

export default init;
