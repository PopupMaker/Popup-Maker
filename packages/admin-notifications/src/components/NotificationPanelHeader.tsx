import { Button, Icon } from '@wordpress/components';
import { MarkColored } from '@popup-maker/icons';
import { __, _n, sprintf } from '@popup-maker/i18n';

interface Props {
	count: number;
	showCount: boolean;
	onClose: () => void;
}

export const NotificationPanelHeader = ( {
	count,
	showCount,
	onClose,
}: Props ): JSX.Element => {
	const countLabel = sprintf(
		/* translators: %d: number of unread notifications. */
		_n(
			'%d unread notification',
			'%d unread notifications',
			count,
			'popup-maker'
		),
		count
	);

	return (
		<header className="pum-notification-panel__header">
			<div className="pum-notification-panel__brand">
				<Icon
					icon={ MarkColored }
					size={ 28 }
					className="pum-notification-panel__mark"
				/>
				<div className="pum-notification-panel__brand-text">
					<span className="pum-notification-panel__brand-label">
						{ __( 'Popup Maker', 'popup-maker' ) }
					</span>
					<h2 className="pum-notification-panel__title">
						{ __( 'Notifications', 'popup-maker' ) }
						{ showCount && (
							<span
								className="pum-notification-panel__count"
								aria-label={ countLabel }
							>
								{ count }
							</span>
						) }
					</h2>
				</div>
			</div>
			<Button
				icon="no-alt"
				label={ __( 'Close', 'popup-maker' ) }
				showTooltip
				onClick={ onClose }
			/>
		</header>
	);
};
