import { createContext, useContext } from '@wordpress/element';

export type SkeletonVariant = 'initial' | 'refresh';

export type SkeletonLoadingKeys = Partial< Record< string, boolean > >;

export interface SkeletonContextValue {
	isLoading: boolean;
	variant: SkeletonVariant;
	loadingKeys?: SkeletonLoadingKeys;
}

const defaultValue: SkeletonContextValue = {
	isLoading: false,
	variant: 'initial',
};

const SkeletonContext = createContext< SkeletonContextValue | null >( null );

export interface SkeletonProviderProps {
	isLoading: boolean;
	variant?: SkeletonVariant;
	loadingKeys?: SkeletonLoadingKeys;
	className?: string;
	children: React.ReactNode;
}

/**
 * Supplies loading state to skeleton primitives and composed placeholders.
 */
export const SkeletonProvider: React.FC< SkeletonProviderProps > = ( {
	isLoading,
	variant = 'initial',
	loadingKeys,
	className,
	children,
} ) => {
	const value: SkeletonContextValue = {
		isLoading,
		variant,
		loadingKeys,
	};

	const rootClass = className
		? `pum-skeleton ${ className }`
		: 'pum-skeleton';

	return (
		<SkeletonContext.Provider value={ value }>
			<div
				className={ rootClass }
				data-skeleton-variant={ variant }
				aria-hidden={ isLoading ? undefined : true }
			>
				{ children }
			</div>
		</SkeletonContext.Provider>
	);
};

export const useSkeleton = (): SkeletonContextValue => {
	const ctx = useContext( SkeletonContext );
	return ctx ?? defaultValue;
};

/**
 * True when the provider is loading and the section key is not opted out.
 */
export const useSkeletonSection = ( sectionKey: string ): boolean => {
	const { isLoading, loadingKeys } = useSkeleton();
	if ( ! isLoading ) {
		return false;
	}
	if ( loadingKeys === undefined ) {
		return true;
	}
	if ( sectionKey in loadingKeys ) {
		return loadingKeys[ sectionKey ] === true;
	}
	return true;
};
