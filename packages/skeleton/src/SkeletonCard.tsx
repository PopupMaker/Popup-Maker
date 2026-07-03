export interface SkeletonCardProps {
	className?: string;
	minHeight?: number | string;
}

export const SkeletonCard: React.FC< SkeletonCardProps > = ( {
	className,
	minHeight,
} ) => (
	<div
		className={
			className
				? `pum-skeleton__card ${ className }`
				: 'pum-skeleton__card'
		}
		style={ minHeight !== undefined ? { minHeight } : undefined }
		aria-hidden="true"
	/>
);
