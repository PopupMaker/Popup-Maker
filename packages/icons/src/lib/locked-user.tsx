/**
 * WordPress dependencies
 */
import { SVG, Path, G, Defs, Rect } from '@wordpress/primitives';

const lockedUser = (
	<SVG
		xmlns="http://www.w3.org/2000/svg"
		viewBox="0 0 25 24"
		fill="none"
		className="pum-icon pum-icon--locked-user"
	>
		<G clipPath="url(#clip0_203_600)">
			<Path
				d="M11.5 13C8.676 13 6.171 13.638 4.525 14.193C3.31 14.604 2.5 15.749 2.5 17.032V21C2.5 21 10.458 21 10.5 21"
				stroke="#000000"
				strokeWidth="2"
				strokeLinecap="round"
				strokeLinejoin="round"
			/>
			<Path
				d="M23.5 18H15.5V23H23.5V18Z"
				stroke="#000000"
				strokeWidth="2"
				strokeLinecap="round"
				strokeLinejoin="round"
			/>
			<Path
				d="M17.5 18V15C17.5 13.9 18.4 13 19.5 13C20.6 13 21.5 13.9 21.5 15V18"
				stroke="#000000"
				strokeWidth="2"
				strokeLinecap="round"
				strokeLinejoin="round"
			/>
			<Path
				d="M11.5 13C8.739 13 6.5 9.761 6.5 7V6C6.5 3.239 8.739 1 11.5 1C14.261 1 16.5 3.239 16.5 6V7C16.5 9.761 14.261 13 11.5 13Z"
				stroke="#000000"
				strokeWidth="2"
				strokeLinecap="round"
				strokeLinejoin="round"
			/>
		</G>
		<Defs>
			<clipPath id="clip0_203_600">
				<Rect width="24" height="24" transform="translate(0.5)" />
			</clipPath>
		</Defs>
	</SVG>
);

export default lockedUser;
