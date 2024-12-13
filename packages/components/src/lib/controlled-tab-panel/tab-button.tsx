import { Button } from '@wordpress/components';
import type { ButtonProps } from '@wordpress/components/build-types/button/types';

type Props = ButtonProps & {
	tabId: string;
	onClick: () => void;
	children: React.ReactNode;
	selected: boolean;
	className?: string;
};

const TabButton = ( {
	tabId,
	onClick,
	children,
	selected,
	...rest
}: Props ) => (
	<Button
		role="tab"
		tabIndex={ selected ? undefined : -1 }
		aria-selected={ selected }
		id={ tabId }
		onClick={ onClick }
		{ ...rest }
	>
		{ children }
	</Button>
);

export default TabButton;
