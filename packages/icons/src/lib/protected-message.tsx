/**
 * WordPress dependencies
 */
import { SVG, Path, Rect } from '@wordpress/primitives';

const ProtectedMessage = (
	<SVG
		xmlns="http://www.w3.org/2000/svg"
		viewBox="0 0 25 24"
		fill="none"
		className="pum-icon pum-icon--protected-message"
	>
		<Path
			d="M19.5 21H7.5L3.5 23V12C3.5 11.4696 3.71071 10.9609 4.08579 10.5858C4.46086 10.2107 4.96957 10 5.5 10H19.5C20.0304 10 20.5391 10.2107 20.9142 10.5858C21.2893 10.9609 21.5 11.4696 21.5 12V19C21.5 19.5304 21.2893 20.0391 20.9142 20.4142C20.5391 20.7893 20.0304 21 19.5 21Z"
			stroke="#000000"
			strokeWidth="2"
			strokeLinecap="round"
			strokeLinejoin="round"
		/>
		<Path
			d="M17.5 6C17.5131 4.68724 17.0042 3.42303 16.0853 2.48539C15.1664 1.54776 13.9128 1.01346 12.6 1H12.5C11.1872 0.986939 9.92303 1.4958 8.98539 2.41469C8.04776 3.33357 7.51346 4.58724 7.5 5.9V10"
			stroke="#000000"
			strokeWidth="2"
			strokeLinecap="round"
			strokeLinejoin="round"
		/>
		<Rect x="6.5" y="13" width="12" height="2" rx="1" fill="#000000" />
		<Rect x="6.5" y="16" width="7" height="2" rx="1" fill="#000000" />
	</SVG>
);

export default ProtectedMessage;
