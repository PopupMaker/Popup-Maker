import { SkeletonBar, type SkeletonBarProps } from './SkeletonBar';

export const SkeletonPill: React.FC< SkeletonBarProps > = ( props ) => (
	<SkeletonBar { ...props } modifier="pill" />
);
