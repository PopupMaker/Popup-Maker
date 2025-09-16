import './styles.scss';

import clsx from 'clsx';
import { SlotFillProvider } from '@wordpress/components';
import type { AppLayoutProps } from '../../types';

export const AppLayout = ( { className, children }: AppLayoutProps ) => {
	return (
		<SlotFillProvider>
			<div className={ clsx( 'popup-maker-app-layout', className ) }>
				{ children }
			</div>
		</SlotFillProvider>
	);
};

export default AppLayout;
