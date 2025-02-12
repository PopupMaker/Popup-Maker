import BaseEditor from './base-editor';
import { withDataStore } from './hocs';

export * from './hocs';
export { default as BaseEditor } from './base-editor';

/**
 * The default editor component.
 */
export const Editor = withDataStore( BaseEditor );

export default Editor;
