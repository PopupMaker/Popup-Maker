/**
 * WordPress dependencies
 */
import { SVG, Path, Rect } from '@wordpress/primitives';

const Block = (
	<SVG
		xmlns="http://www.w3.org/2000/svg"
		viewBox="0 0 24 24"
		fill="none"
		className="pum-icon pum-icon--block"
	>
		<Path
			d="M3.75 9C3.75 8.30964 4.30964 7.75 5 7.75H19C19.6904 7.75 20.25 8.30964 20.25 9V17C20.25 17.6904 19.6904 18.25 19 18.25H5C4.30964 18.25 3.75 17.6904 3.75 17V9Z"
			fill="white"
			stroke="#000000"
			strokeWidth="1.5"
		/>
		<Rect x="6" y="5" width="5" height="3" fill="#000000" />
		<Rect x="13" y="5" width="5" height="3" fill="#000000" />
	</SVG>
);

export default Block;
