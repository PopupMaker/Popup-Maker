import { registerListBulkAction, registerListOption } from '../registry';
import * as bulkActions from './list-bulk-actions';
import * as listOptions from './list-options';

const init = () => {
	// Register core bluk list actions.
	Object.values( bulkActions ).forEach( ( action ) => {
		registerListBulkAction( action );
	} );

	// Register core list options.
	Object.values( listOptions ).forEach( ( option ) => {
		registerListOption( option );
	} );
};

export default init;
