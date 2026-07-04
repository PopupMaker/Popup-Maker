import { __ } from '@popup-maker/i18n';
import { applyFilters } from '@wordpress/hooks';
import { SkeletonBar } from '@popup-maker/skeleton';

import type { EditorTab } from '../../types';

/**
 * Loading placeholder matching the editor layout.
 *
 * Mirrors BaseEditor: single-column content when only one tab is registered,
 * with a tab sidebar when there are multiple.
 *
 * Rendered inside the editor modal while an instant-open editor waits for
 * its record to resolve.
 */
export const EditorSkeleton = (): JSX.Element => {
	// Same registry BaseEditor uses to decide whether tabs render.
	const tabs = applyFilters(
		'popupMaker.callToActionEditor.tabs',
		[]
	) as EditorTab[];

	const showSidebar = tabs.length > 1;

	const content = (
		<div className="editor-tabs__content-skeleton">
			{ /* Title & description fields. */ }
			<div className="editor-tabs__field-skeleton">
				<SkeletonBar width="15%" height={ 13 } />
				<SkeletonBar width="100%" height={ 48 } />
			</div>
			<div className="editor-tabs__field-skeleton">
				<SkeletonBar width="20%" height={ 13 } />
				<SkeletonBar width="100%" height={ 36 } />
			</div>
			{ /* Remaining fields. */ }
			{ [ 1, 2, 3 ].map( ( i ) => (
				<div key={ i } className="editor-tabs__field-skeleton">
					<SkeletonBar width="30%" height={ 13 } />
					<SkeletonBar width="100%" height={ 36 } />
				</div>
			) ) }
		</div>
	);

	return (
		<div
			className="call-to-action-editor call-to-action-editor--loading"
			aria-busy="true"
			aria-label={ __( 'Loading call to action…', 'popup-maker' ) }
		>
			<div className="editor-tabs-container">
				{ showSidebar ? (
					<div className="editor-tabs editor-tabs--skeleton">
						<div className="editor-tabs__sidebar-skeleton">
							{ [ 90, 70, 80 ].map( ( width, i ) => (
								<SkeletonBar
									key={ i }
									width={ `${ width }%` }
									height={ 20 }
								/>
							) ) }
						</div>
						{ content }
					</div>
				) : (
					content
				) }
			</div>
		</div>
	);
};

export default EditorSkeleton;
