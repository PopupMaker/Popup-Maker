import { SkeletonBar, type SkeletonBarProps } from './SkeletonBar';

export const SkeletonButton: React.FC< SkeletonBarProps > = ( props ) => (
	<SkeletonBar { ...props } modifier="button" />
);
