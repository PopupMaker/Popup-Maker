import { registerListBulkAction } from '../registry';
import * as bulkActions from '../bulk-actions';

const init = () => {
	// Register core bluk list actions.
	Object.values( bulkActions ).forEach( ( action ) => {
		registerListBulkAction( action );
	} );
};

export default init;
