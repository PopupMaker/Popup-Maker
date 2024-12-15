import clsx from 'clsx';

import { withInstanceId } from '@wordpress/compose';
import { NavigableMenu } from '@wordpress/components';

import TabButton from './tab-button';

type Tab = {
	name: string;
	title: string | JSX.Element;
	className: string | string[];
	[ key: string ]: any;
};

type Props = {
	instanceId: string | number;
	orientation?: 'horizontal' | 'vertical';
	activeClass?: string;
	tabsClass?: string;
	tabClass?: string;
	className?: string | string[];
	tabs: Tab[];
	selected?: string | null;
	onSelect: ( tabKey: string ) => void;
	children?: ( selected: Tab ) => React.ReactNode;
};

const TabPanel = ( {
	instanceId,
	orientation = 'horizontal',
	activeClass = 'is-active',
	tabsClass = 'tabs',
	tabClass = 'tab',
	className,
	tabs,
	selected,
	onSelect,
	children,
}: Props ) => {
	const selectedTab = tabs.find( ( t ) => selected === t.name ) || tabs[ 0 ];
	const selectedId = `${ instanceId }-${ selectedTab?.name ?? 'none' }`;

	const handleClick = ( tabKey: string ) => {
		onSelect?.( tabKey );
	};

	const onNavigate = ( _childIndex: number, child: HTMLElement ) => {
		child.click();
	};

	return (
		<div className={ clsx( className, 'pum-' + orientation + '-tabs' ) }>
			<NavigableMenu
				role="tablist"
				orientation={ orientation }
				onNavigate={ onNavigate }
				className={ clsx( [
					tabsClass,
					'components-tab-panel__tabs',
				] ) }
			>
				{ tabs.map( ( tab ) => (
					<TabButton
						className={ clsx(
							tabClass,
							'components-tab-panel__tabs-item',
							'components-tab-panel__tab',
							tab.className,
							{
								[ activeClass ]: tab.name === selectedTab.name,
							}
						) }
						tabId={ `${ instanceId }-${ tab.name }` }
						aria-controls={ `${ instanceId }-${ tab.name }-view` }
						selected={ tab.name === selectedTab.name }
						key={ tab.name }
						onClick={ () => handleClick( tab.name ) }
						href={ tab?.href ?? undefined }
						target={ tab?.target ?? undefined }
					>
						{ tab.title }
					</TabButton>
				) ) }
			</NavigableMenu>
			{ selectedTab && (
				<div
					key={ selectedId }
					aria-labelledby={ selectedId }
					role="tabpanel"
					id={ `${ selectedId }-view` }
					className="components-tab-panel__tab-content"
					tabIndex={ 0 }
				>
					{ children && children( selectedTab ) }
				</div>
			) }
		</div>
	);
};

export default withInstanceId( TabPanel );
