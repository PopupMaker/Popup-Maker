import React from 'react';
import clsx from 'clsx';
import { sprintf, __ } from '@popup-maker/i18n';

import { getStatusKey } from '../lib/status';
import ActionButton from './ActionButton';
import AddonIcon from './AddonIcon';
import StatusIndicator from './StatusIndicator';

import type { Addon, AddonAction, AddonsPageConfig } from '../types';

interface Props {
	addon: Addon;
	busy: boolean;
	config: AddonsPageConfig;
	onAction: ( addon: Addon, action: AddonAction ) => void;
	onOpen: ( addon: Addon ) => void;
}

const AddonCard = ( { addon, busy, config, onAction, onOpen }: Props ) => {
	const status = getStatusKey( addon, busy );

	return (
		<article
			className={ clsx(
				'pum-addons__card',
				`pum-addons__card--${ status }`,
				{ 'pum-addons__card--pro-plus-tier': addon.isProPlus }
			) }
		>
			<button
				type="button"
				className="pum-addons__card-main"
				aria-label={ sprintf(
					/* translators: %s: add-on name. */
					__( 'View details for %s', 'popup-maker' ),
					addon.name || addon.slug
				) }
				onClick={ () => onOpen( addon ) }
			>
				<span className="pum-addons__card-heading">
					<AddonIcon addon={ addon } />
					<span className="pum-addons__title-wrap">
						<span className="pum-addons__title">
							{ addon.name || addon.slug }
						</span>
						{ addon.isUpsell && (
							<span
								className={ clsx( 'pum-addons__tier-badge', {
									'pum-addons__tier-badge--pro-plus':
										addon.isProPlus,
								} ) }
							>
								{ addon.isProPlus
									? __( 'Pro+', 'popup-maker' )
									: __( 'Pro', 'popup-maker' ) }
							</span>
						) }
					</span>
				</span>
				<span className="pum-addons__description">
					{ addon.description }
				</span>
			</button>
			<div className="pum-addons__card-footer">
				<StatusIndicator
					status={ status }
					proPlus={ addon.isProPlus }
				/>
				<ActionButton
					addon={ addon }
					busy={ busy }
					config={ config }
					onAction={ onAction }
				/>
			</div>
		</article>
	);
};

export default AddonCard;
