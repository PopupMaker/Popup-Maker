import type { ComponentType } from 'react';

import BaseEditor from './base-editor';
import { withDataStore } from './hocs';
import type { EditorWithDataStoreProps } from './hocs/with-data-store';

export * from './hocs';
export { default as BaseEditor } from './base-editor';

/**
 * The default editor component.
 */
export const Editor: ComponentType< EditorWithDataStoreProps > =
	withDataStore( BaseEditor );

export default Editor;
