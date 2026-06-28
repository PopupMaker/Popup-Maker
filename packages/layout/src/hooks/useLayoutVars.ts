import { useMemo } from '@wordpress/element';

import { resolveLayoutVars } from '../utils/layoutVars';

/**
 * Resolved layout vars after the JS filter pass.
 */
export const useLayoutVars = () => useMemo( () => resolveLayoutVars(), [] );
