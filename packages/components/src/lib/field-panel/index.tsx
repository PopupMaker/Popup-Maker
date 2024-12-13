import './editor.scss';

import { Panel, PanelBody } from '@wordpress/components';
import classNames, { type Argument as ClassNameType } from 'classnames';

type Props = {
	title: string;
	className?: ClassNameType;
	children: React.ReactNode;
};

const FieldPanel = ( { title, className, children }: Props ) => {
	return (
		<Panel
			header={ title }
			className={ classNames( [ 'components-field-panel', className ] ) }
		>
			<PanelBody opened>{ children }</PanelBody>
		</Panel>
	);
};

export default FieldPanel;
