import './styles.scss';

import { useRef } from '@wordpress/element';
import {
	Button,
	DropdownMenu,
	MenuGroup,
	MenuItem,
} from '@wordpress/components';
import { __ } from '@popup-maker/i18n';
import { lifesaver, login, pages, people } from '@wordpress/icons';
import { ControlledTabPanel } from '@popup-maker/components';
import type { AppHeaderProps } from '../../types';
import {
	HeaderStartSlot,
	HeaderEndSlot,
	HeaderActionsSlot,
	SupportMenuSlot,
} from '../../slots';

export const AppHeader = ( {
	title = __( 'Popup Maker', 'popup-maker' ),
	brandingUrl = 'https://wppopupmaker.com?utm_campaign=plugin-info&utm_source=plugin-admin-header&utm_medium=plugin-ui&utm_content=header-logo',
	tabs = [],
	currentTab,
	onTabChange,
	supportMenuItems = [],
	showSupport = true,
	adminUrl = '',
}: AppHeaderProps ) => {
	const btnRef = useRef< HTMLButtonElement | null >( null );
	const { assetsUrl } = window.popupMaker.globalVars;
	// Default support menu items
	const defaultSupportItems = [
		{
			icon: pages,
			label: __( 'View Documentation', 'popup-maker' ),
			href: 'https://wppopupmaker.com/docs/?utm_campaign=plugin-support&utm_source=plugin-admin-header&utm_medium=plugin-ui&utm_content=view-documentation-link',
			target: '_blank',
			group: 'primary',
		},
		{
			icon: people,
			label: __( 'Get Support', 'popup-maker' ),
			href: 'https://wppopupmaker.com/support/?utm_campaign=plugin-support&utm_source=plugin-admin-header&utm_medium=plugin-ui&utm_content=get-support-link',
			target: '_blank',
			group: 'primary',
		},
		{
			icon: login,
			label: __( 'Grant Support Access', 'popup-maker' ),
			onClick: () => {
				if ( adminUrl ) {
					window.location.href = `${ adminUrl }options-general.php?page=grant-popup-maker-access`;
				}
			},
			group: 'secondary',
		},
		...supportMenuItems,
	];

	// Group support items
	const groupedItems = defaultSupportItems.reduce(
		( acc, item ) => {
			const group = item.group || 'primary';
			if ( ! acc[ group ] ) {
				acc[ group ] = [];
			}
			acc[ group ].push( item );
			return acc;
		},
		{} as Record< string, typeof defaultSupportItems >
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

			{ tabs.length > 0 && (
				<ControlledTabPanel
					className="tabs"
					orientation="horizontal"
					selected={ currentTab || null }
					onSelect={ ( tabName: string ) => {
						const tab = tabs.find( ( t ) => t.name === tabName );

						if ( tab?.onClick ) {
							// Allow short circuiting of tab change
							if ( false === tab.onClick() ) {
								return;
							}
						}

						onTabChange?.( tabName );
					} }
					tabs={ tabs }
				/>
			) }

			<div className="popup-maker-app-header__actions">
				<HeaderActionsSlot />

				{ showSupport && (
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
