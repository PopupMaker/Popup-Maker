import React from 'react';
import clsx from 'clsx';
import { __ } from '@popup-maker/i18n';

import AddonCard from './AddonCard';

import type { Addon, AddonAction, AddonsPageConfig } from '../types';

interface Props {
	addons: Addon[];
	title: string;
	featured?: boolean;
	busySlugs: Record< string, boolean >;
	config: AddonsPageConfig;
	onAction: ( addon: Addon, action: AddonAction ) => void;
	onOpen: ( addon: Addon ) => void;
}

const AddonSection = ( {
	addons,
	title,
	featured,
	busySlugs,
	config,
	onAction,
	onOpen,
}: Props ) => {
	return (
		<section
			className={ clsx( 'pum-addons__section', {
				'pum-addons__section--pro-plus': featured,
			} ) }
		>
			<div className="pum-addons__section-heading">
				<h2>{ title }</h2>
				{ featured && (
					<span className="pum-addons__pro-plus-badge">
						{ __( 'Available with Pro+', 'popup-maker' ) }
					</span>
				) }
				<span className="pum-addons__section-rule" />
			</div>
			<div className="pum-addons__grid">
				{ addons.map( ( addon ) => (
					<AddonCard
						key={ addon.slug }
						addon={ addon }
						busy={ Boolean( busySlugs[ addon.slug ] ) }
						config={ config }
						onAction={ onAction }
						onOpen={ onOpen }
					/>
				) ) }
			</div>
		</section>
	);
};

export default AddonSection;
