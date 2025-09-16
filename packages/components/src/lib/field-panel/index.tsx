import './editor.scss';

import clsx from 'clsx';

import { Panel, PanelBody } from '@wordpress/components';

type Props = {
	title: string;
	className?: clsx.ClassValue;
	children: React.ReactNode;
};

const FieldPanel = ( { title, className, children }: Props ) => {
	return (
		<Panel
			header={ title }
			className={ clsx( [ 'components-field-panel', className ] ) }
		>
			<PanelBody opened>{ children }</PanelBody>
		</Panel>
	);
};

export default FieldPanel;
