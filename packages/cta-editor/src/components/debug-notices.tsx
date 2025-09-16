import { useSelect } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import { NOTICE_CONTEXT } from '@popup-maker/core-data';
import { DEBUG_MODE } from '../';

/**
 * Debug component to display all notices in the CTA editor context.
 * Only renders when DEBUG_MODE is enabled.
 */
export const DebugNotices = () => {
	const notices = useSelect( ( select ) => {
		return select( noticesStore ).getNotices( NOTICE_CONTEXT );
	}, [] );

	// Only render in debug mode
	if ( ! DEBUG_MODE ) {
		return null;
	}

	if ( notices.length === 0 ) {
		return (
			<div
				style={ {
					padding: '10px',
					background: '#f0f0f0',
					margin: '10px 0',
					fontSize: '12px',
					fontFamily: 'monospace',
				} }
			>
				<strong>
					{ `Debug: No notices found in context "${ NOTICE_CONTEXT }"` }
				</strong>
			</div>
		);
	}

	return (
		<div
			style={ {
				padding: '10px',
				background: '#ffeeee',
				margin: '10px 0',
				fontSize: '12px',
				fontFamily: 'monospace',
				border: '1px solid #cc1818',
			} }
		>
			<strong>
				{ `Debug: Notices in context "${ NOTICE_CONTEXT }":` }
			</strong>
			<ul style={ { margin: '5px 0 0 20px', padding: 0 } }>
				{ notices.map( ( notice ) => (
					<li key={ notice.id } style={ { marginBottom: '3px' } }>
						<strong>{ notice.id }</strong>: { notice.content }{ ' ' }
						(status: { notice.status })
					</li>
				) ) }
			</ul>
		</div>
	);
};

export default DebugNotices;
