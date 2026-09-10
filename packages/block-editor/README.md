# `block-editor`

Block editor scripts.

## Content tokens

Core owns the editor projection so free features, Pro, Pro+ add-ons, and third-party extensions share one picker and saved format. A feature registers readable groups and token definitions; it continues to own server-side resolution.

```ts
import {
	registerContentToken,
	registerContentTokenGroup,
} from '@popup-maker/block-editor';

registerContentTokenGroup( {
	id: 'lead-magnet',
	label: 'Lead Magnet',
	priority: 20,
} );

registerContentToken( {
	id: 'lead_magnet.presentation.headline',
	group: 'lead-magnet',
	label: 'Headline',
	description: 'The assigned Lead Magnet headline.',
	interaction: 'atomic',
	preview: 'Free conversion guide',
	conditions: {
		surfaces: [ 'block-editor-rich-text' ],
		blockTypes: [ 'core/heading', 'core/paragraph' ],
	},
} );
```

Definitions support:

- human-readable groups, labels, descriptions, and priorities;
- atomic placeholders or editable fallback content;
- optional static or contextual text previews;
- surface, post type, block type, and RichText attribute constraints;
- exclusion lists and an extension-owned runtime availability predicate;
- an optional Classic-editor representation.

Registration payloads deliberately use named, JSON-safe fields rather than
positional tuples or encoded flags. This keeps definitions readable whether
they originate in JavaScript, localized PHP arrays, or an extension API.

All declarative condition groups are ANDed; entries within one list are ORed. Runtime checks fail closed. The shared format stores a stable ID in a semantic `<data>` element and keeps readable text in its content, so saved content remains understandable when the owning feature is inactive.
