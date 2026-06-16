/**
 * WordPress dependencies
 */
import { SVG, Circle, Path } from '@wordpress/primitives';

const NotificationsPeekHandle: JSX.Element = (
	<SVG
		xmlns="http://www.w3.org/2000/svg"
		viewBox="0 0 32 32"
		fill="none"
		className="pum-icon pum-icon--notifications-peek-handle"
	>
		<Circle
			cx="16"
			cy="16"
			r="11.5"
			stroke="#1A191B"
			strokeWidth="2"
		/>
		<Path
			d="M16 10.75 11.75 18.25h8.5L16 10.75z"
			fill="#FFFFFF"
			stroke="#1A191B"
			strokeWidth="1.25"
			strokeLinejoin="round"
		/>
	</SVG>
);

export default NotificationsPeekHandle;
