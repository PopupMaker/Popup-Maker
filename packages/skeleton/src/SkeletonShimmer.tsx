export interface SkeletonShimmerProps {
	className?: string;
	children: React.ReactNode;
}

/**
 * Wraps skeleton layout blocks that share the shimmer surface styles.
 */
export const SkeletonShimmer: React.FC< SkeletonShimmerProps > = ( {
	className,
	children,
} ) => (
	<div
		className={
			className
				? `pum-skeleton__shimmer ${ className }`
				: 'pum-skeleton__shimmer'
		}
	>
		{ children }
	</div>
);
