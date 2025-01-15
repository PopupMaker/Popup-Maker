import BaseEditor from './base-editor';
import withDataStore from './with-data-store';

export * from './types';
export * from './base-editor';
export * from './with-modal';
export * from './with-data-store';
export * from './with-query-params';

/**
 * The default editor component.
 */
export const Editor = withDataStore( BaseEditor );

export default Editor;
