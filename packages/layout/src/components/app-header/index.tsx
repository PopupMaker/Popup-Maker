import './styles.scss';

import { useRef, useMemo } from '@wordpress/element';
import {
	Button,
	DropdownMenu,
	MenuGroup,
	MenuItem,
} from '@wordpress/components';
import { __ } from '@popup-maker/i18n';
import { lifesaver } from '@wordpress/icons';
import { ControlledTabPanel } from '@popup-maker/components';
import type { AppHeaderProps, SupportMenuItem } from '../../types';
import {
	HeaderStartSlot,
	HeaderEndSlot,
	HeaderActionsSlot,
	SupportMenuSlot,
} from '../../slots';
import {
	getNavTabs,
	getShowSupportMenu,
	getSupportMenuItems,
	mapNavTabsForHeader,
} from '../../utils/layoutVars';

export const AppHeader = ( {
	title = __( 'Popup Maker', 'popup-maker' ),
	brandingUrl = 'https://wppopupmaker.com?utm_campaign=plugin-info&utm_source=plugin-admin-header&utm_medium=plugin-ui&utm_content=header-logo',
	tabs = [],
	currentTab,
	onTabChange,
	navTabId,
	supportMenuItems = [],
	showSupport = true,
}: AppHeaderProps ): JSX.Element => {
	const btnRef = useRef< HTMLButtonElement | null >( null );
	const { assetsUrl } = window.popupMaker.globalVars;
	const navTabs = getNavTabs();
	const hasLayoutNav = navTabs.length > 0;
	const headerTabs = hasLayoutNav ? mapNavTabsForHeader() : tabs;
	const headerCurrentTab = hasLayoutNav && navTabId ? navTabId : currentTab;
	const layoutSupportItems = useMemo( () => getSupportMenuItems(), [] );
	const showSupportMenu = showSupport && getShowSupportMenu();
	const supportItems = [ ...layoutSupportItems, ...supportMenuItems ];
	const groupedItems = supportItems.reduce(
		( acc, item ) => {
			const group = item.group || 'primary';
			if ( ! acc[ group ] ) {
				acc[ group ] = [];
			}
			acc[ group ].push( item );
			return acc;
		},
		{} as Record< string, SupportMenuItem[] >
	);

	return (
		<div className="popup-maker-app-header">
			<HeaderStartSlot />

			<h1 className="branding wp-heading-inline">
				<a
					href={ brandingUrl }
					target="_blank"
					rel="noopener noreferrer"
				>
					<img src={ `${ assetsUrl }images/mark.svg` } alt="" />
					{ title }
				</a>
			</h1>

			{ headerTabs.length > 0 && (
				<ControlledTabPanel
					className="tabs"
					orientation="horizontal"
					selected={ headerCurrentTab || null }
					onSelect={ ( tabName: string ) => {
						const tab = headerTabs.find( ( t ) => t.name === tabName );

						if ( tab?.onClick ) {
							// Allow short circuiting of tab change
							if ( false === tab.onClick() ) {
								return;
							}
						}

						if ( tab?.href ) {
							window.location.assign( tab.href );
							return;
						}

						onTabChange?.( tabName );
					} }
					tabs={ headerTabs }
				/>
			) }

			<div className="popup-maker-app-header__actions">
				<HeaderActionsSlot />

				{ showSupportMenu && (
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
							className: 'popup-maker-support-menu',
							anchor: {
								getBoundingClientRect: () =>
									btnRef?.current?.getBoundingClientRect(),
							} as Element,
						} }
					>
						{ ( { onClose } ) => (
							<>
								{ Object.entries( groupedItems ).map(
									( [ group, items ] ) => (
										<MenuGroup key={ group }>
											{ items.map( ( item ) => (
												<MenuItem
													key={ item.label }
													icon={ item.icon }
													// @ts-ignore - Undocumented, but accepts all button props.
													href={ item.href }
													target={ item.target }
													onClick={ () => {
														if ( item.onClick ) {
															item.onClick();
														}
														if ( ! item.href ) {
															onClose();
														}
													} }
												>
													{ item.label }
												</MenuItem>
											) ) }
										</MenuGroup>
									)
								) }
								<SupportMenuSlot fillProps={ { onClose } } />
							</>
						) }
					</DropdownMenu>
				) }
			</div>

			<HeaderEndSlot />
		</div>
	);
};

export default AppHeader;
