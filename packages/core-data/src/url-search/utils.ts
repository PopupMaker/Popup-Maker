import { appendUrlParams } from '../utils';

import type { SearchArgs } from './types';

export const getResourcePath = ( queryParams: SearchArgs = { search: '' } ) =>
	appendUrlParams( 'wp/v2/search', queryParams );
