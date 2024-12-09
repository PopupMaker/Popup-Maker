import './styles.scss';

import $ from 'jquery';
import { AdminBar } from './AdminBar';

// Initialize the admin bar when the document is ready
$( () => {
	new AdminBar();
} );
