import classnames from 'classnames';

import { useEffect, useState } from '@wordpress/element';

type FullScreenPanelProps = {
	className?: string;
	children: React.ReactNode;
};

const FullScreenPanel = ( props: FullScreenPanelProps ) => {
	const { className, children } = props;

	const [ isFull, setIsFull ] = useState( false );

	useEffect( () => {
		const html = document.querySelector( 'html' );

		if ( isFull ) {
			html?.classList.add( 'disable-scroll' );
		} else {
			html?.classList.remove( 'disable-scroll' );
		}
	}, [ isFull ] );

	const onClick = () => {
		setIsFull( ! isFull );
	};

	return (
		<div
			className={ classnames( 'fullscreen-panel', className, {
				'is-full-screen': isFull,
			} ) }
		>
			<button
				onClick={ onClick }
				className={ classnames(
					'maximize-fullscreen-panel',
					'dashicons',
					isFull
						? 'dashicons dashicons-no-alt'
						: 'dashicons-editor-expand'
				) }
			/>
			{ children }
		</div>
	);
};

export default FullScreenPanel;
