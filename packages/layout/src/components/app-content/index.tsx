import './styles.scss';

import clsx from 'clsx';
import type { AppContentProps } from '../../types';

export const AppContent = ( { className, children }: AppContentProps ) => {
	return (
		<div className={ clsx( 'popup-maker-app-content', className ) }>
			{ children }
		</div>
	);
};

export default AppContent;
