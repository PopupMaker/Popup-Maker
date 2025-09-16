// Core dependencies first
import './compatibility';

// Initialize jQuery plugins first
import './serialize-object';

// Initialize core popmake and methods
import './pum';
import './pum-callbacks';
import './pum-defaults';

// Extend core with utilities
import './pum-hooks';
import './pum-utilities';

// Add core features
import './pum-animations';
import './pum-cookie';
import './pum-cookies';
import './pum-conditions';
import './pum-analytics';
import './pum-accessibility';

// Add form handling
import './pum-forms';
import './pum-newsletter';

// Add integrations and triggers
import './pum-integrations';
import './pum-triggers';

// Add URL tracking functionality
import './pum-url-tracking';

// Add bindings last (after all features are loaded)
import './pum-binds';

// Debug tools
import './pum-debug';
