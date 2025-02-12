import {
	Button,
	DropdownMenu,
	MenuGroup,
	MenuItem,
} from '@wordpress/components';
import { __ } from '@popup-maker/i18n';
import { useRef } from '@wordpress/element';
import { lifesaver, login, pages, people } from '@wordpress/icons';

import { ControlledTabPanel } from '@popup-maker/components';
import { StringParam, useQueryParam } from '@popup-maker/use-query-params';

import type { TabComponent } from '@popup-maker/types';
import { getGlobalVars } from '../../utils';

type Props = {
	tabs: TabComponent[];
};

const Header = ( { tabs }: Props ) => {
	const { assetsUrl, adminUrl } = getGlobalVars();

	const [ view = 'callToActions', setView ] = useQueryParam(
		'view',
		StringParam
	);

	const btnRef = useRef< HTMLButtonElement | null >( null );

	// Hacked version of setTab to intercept when unsaved changes exist.
	const changeView = ( newView: string ) => {
		setView( newView );
	};

	return (
		<>
			<div className="popup-maker-call-to-actions-page__header">
				<h1 className="branding wp-heading-inline">
					<a
						href="https://wppopupmaker.com?utm_campaign=plugin-info&utm_source=plugin-call-to-actions-page&utm_medium=plugin-ui&utm_content=header-logo"
						target="_blank"
						rel="noopener noreferrer"
					>
						<img src={ `${ assetsUrl }images/mark.svg` } alt="" />
						{ __( 'Popup Maker', 'popup-maker' ) }
					</a>
				</h1>
				<ControlledTabPanel
					className="tabs"
					orientation="horizontal"
					selected={ view !== null ? view : undefined }
					onSelect={ ( tabName: string ) => {
						const currentTab = tabs.find(
							( t ) => t.name === tabName
						);

						if ( currentTab?.onClick ) {
							// Allow short circuiting of tab change.
							if ( false === currentTab.onClick() ) {
								return;
							}
						}

						changeView( tabName );
					} }
					tabs={ tabs }
				/>

				<DropdownMenu
					label={ __( 'Support', 'popup-maker' ) }
					icon={ lifesaver }
					toggleProps={ {
						as: ( { onClick } ) => (
							<Button
								icon={ lifesaver }
								variant="link"
								onClick={ onClick }
								className="components-tab-panel__tabs-item support-link"
							>
								<span ref={ btnRef }>
									{ __( 'Support', 'popup-maker' ) }
								</span>
							</Button>
						),
					} }
					popoverProps={ {
						noArrow: false,
						position: 'bottom left',
						className: 'popup-maker-settings-page__support-menu',
						anchor: {
							getBoundingClientRect: () =>
								btnRef?.current?.getBoundingClientRect(),
						} as Element,
					} }
				>
					{ ( { onClose } ) => (
						<>
							<MenuGroup>
								<MenuItem
									icon={ pages }
									// @ts-ignore - Undocumented, but accepts all button props.
									href="https://wppopupmaker.com/docs/?utm_campaign=plugin-support&utm_source=plugin-call-to-actions-page&utm_medium=plugin-ui&utm_content=view-documentation-link"
									target="_blank"
								>
									{ __(
										'View Documentation',
										'popup-maker'
									) }
								</MenuItem>
								<MenuItem
									icon={ people }
									// @ts-ignore - Undocumented, but accepts all button props.
									href="https://wppopupmaker.com/support/?utm_campaign=plugin-support&utm_source=plugin-call-to-actions-page&utm_medium=plugin-ui&utm_content=get-support-link"
									target="_blank"
								>
									{ __( 'Get Support', 'popup-maker' ) }
								</MenuItem>
							</MenuGroup>

							<MenuGroup>
								<MenuItem
									icon={ login }
									onClick={ () => {
										window.location.href = `${ adminUrl }options-general.php?page=grant-popup-maker-access`;
										onClose();
									} }
								>
									{ __(
										'Grant Support Access',
										'popup-maker'
									) }
								</MenuItem>
							</MenuGroup>
						</>
					) }
				</DropdownMenu>
			</div>
		</>
	);
};

export default Header;
