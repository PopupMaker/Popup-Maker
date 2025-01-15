import { initTypeField, initTypeLinkFields } from './general';
import { initCustomFields } from './custom-fields';

export const initFields = () => {
	initTypeField();
	initTypeLinkFields();
	initCustomFields();
};

export default initFields;
