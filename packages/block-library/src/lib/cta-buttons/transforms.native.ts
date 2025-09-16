/**
 * Internal dependencies
 */
import webTransforms from './transforms';
import transformationCategories from '../utils/transformation-categories.native';

const transforms = {
	...webTransforms,
	supportedMobileTransforms: transformationCategories.other,
};

export default transforms;
