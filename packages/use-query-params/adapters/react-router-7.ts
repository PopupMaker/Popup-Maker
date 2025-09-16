// ReactRouter7Adapter.ts
import { useLocation, useNavigate } from 'react-router';

import type {
	PartialLocation,
	QueryParamAdapterComponent,
} from 'use-query-params';

export const ReactRouter7Adapter: QueryParamAdapterComponent = ( {
	children,
} ) => {
	const navigate = useNavigate();
	const location = useLocation();

	return children( {
		location,
		push: ( { search, state }: PartialLocation ) =>
			navigate( { search }, { state } ),
		replace: ( { search, state }: PartialLocation ) =>
			navigate( { search }, { replace: true, state } ),
	} );
};
