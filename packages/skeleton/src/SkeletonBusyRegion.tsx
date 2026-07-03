import { Spinner } from '@wordpress/components';

export interface SkeletonBusyRegionProps {
	className?: string;
	ariaLabel: string;
	children: React.ReactNode;
	showSpinner?: boolean;
}

export const SkeletonBusyRegion: React.FC< SkeletonBusyRegionProps > = ( {
	className,
	ariaLabel,
	children,
	showSpinner = true,
} ) => (
	<div
		className={ className }
		aria-busy="true"
		aria-live="polite"
		aria-label={ ariaLabel }
	>
		{ children }
		{ showSpinner && (
			<div className="pum-skeleton__spinner">
				<Spinner />
			</div>
		) }
	</div>
);
