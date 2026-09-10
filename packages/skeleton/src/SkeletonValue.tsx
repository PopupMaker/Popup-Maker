export const skeletonValueClassName = 'pum-skeleton-value';

export interface SkeletonValueProps {
	width?: string | number;
	height?: number;
	className?: string;
}

/**
 * Inline shimmer placeholder for dynamic numeric or text values.
 * @param root0
 * @param root0.width
 * @param root0.height
 * @param root0.className
 */
export const SkeletonValue: React.FC< SkeletonValueProps > = ( {
	width = '3em',
	height = 14,
	className,
} ) => {
	const classes = className
		? `${ skeletonValueClassName } ${ className }`
		: skeletonValueClassName;

	return (
		<span
			className={ classes }
			style={ { width, height } }
			aria-hidden="true"
		/>
	);
};
