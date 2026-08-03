export type SkeletonBarModifier = 'pill' | 'button';

export interface SkeletonBarProps {
	width: string | number;
	height?: number;
	modifier?: SkeletonBarModifier;
	className?: string;
}

export const skeletonBarClassName = (
	modifier?: 'pill' | 'button',
	className?: string
): string => {
	const classes = [ 'pum-skeleton__bar' ];
	if ( modifier === 'pill' ) {
		classes.push( 'pum-skeleton__bar--pill' );
	}
	if ( modifier === 'button' ) {
		classes.push( 'pum-skeleton__bar--button' );
	}
	if ( className ) {
		classes.push( className );
	}
	return classes.join( ' ' );
};

export const SkeletonBar: React.FC< SkeletonBarProps > = ( {
	width,
	height = 14,
	modifier,
	className,
} ) => (
	<span
		className={ skeletonBarClassName( modifier, className ) }
		style={ { width, height } }
		aria-hidden="true"
	/>
);
