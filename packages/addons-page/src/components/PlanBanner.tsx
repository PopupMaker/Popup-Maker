import React from 'react';
import { Button } from '@wordpress/components';

import type { AddonsPageConfig } from '../types';

const PlanBanner = ( { config }: { config: AddonsPageConfig } ) => {
	if ( ! config.planTitle ) {
		return null;
	}

	const isWarning = 'warning' === config.planState;

	return (
		<div
			className={ `pum-addons__plan${
				isWarning ? ' pum-addons__plan--warning' : ''
			}` }
		>
			<span className="pum-addons__plan-mark" aria-hidden="true">
				{ config.planLogoUrl && (
					<img src={ config.planLogoUrl } alt="" />
				) }
			</span>
			<div className="pum-addons__plan-copy">
				<span className="pum-addons__plan-title-row">
					<span className="pum-addons__plan-title">
						{ config.planTitle }
					</span>
					{ isWarning && config.planStatus && (
						<span className="pum-addons__plan-status">
							{ config.planStatus }
						</span>
					) }
				</span>
				<span className="pum-addons__plan-sub">
					{ config.planSubtitle }
				</span>
			</div>
			{ config.upgradeLabel && config.upgradeUrl && (
				<Button
					variant="primary"
					className="pum-addons__plan-action"
					href={ config.upgradeUrl }
					{ ...( config.upgradeExternal
						? {
								target: '_blank',
								rel: 'noopener noreferrer',
						  }
						: {} ) }
				>
					{ config.upgradeLabel }
				</Button>
			) }
		</div>
	);
};

export default PlanBanner;
