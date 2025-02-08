import {
	registerListBulkAction,
	registerListFilter,
	registerListOption,
	registerListQuickAction,
} from '../registry';
import * as bulkActions from './list-bulk-actions';
import * as quickActions from './list-quick-actions';
import * as listOptions from './list-options';
import * as listFilters from './list-filters';

const init = () => {
	// Register core bulk list actions.
	Object.values( bulkActions ).forEach( ( action ) => {
		registerListBulkAction( action );
	} );

	// Register core list options.
	Object.values( listOptions ).forEach( ( option ) => {
		registerListOption( option );
	} );

	// Register core list quick actions.
	Object.values( quickActions ).forEach( ( action ) => {
		registerListQuickAction( action );
	} );

	// Register core list filters.
	Object.values( listFilters ).forEach( ( filter ) => {
		registerListFilter( filter );
	} );
};

export default init;
