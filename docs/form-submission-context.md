# Normalized form submission context

Popup Maker form integrations report successful submissions through
`pum_integrated_form_submission()` in PHP and
`PUM.integrations.formSubmission()` in JavaScript. The normalized envelope is
provider-independent, and extensions can attach their own namespaced data to
`context` without changing each provider integration.

## Contract

PHP integrations use snake-cased keys:

```php
pum_integrated_form_submission( [
	'form_provider'  => 'example',
	'form_id'        => 12,
	'submission_id'  => 'entry-456',
	'source_post_id' => 78,
	'source_url'     => 'https://example.com/guide/',
	'context'        => [
		'my_extension' => [
			'campaign_id' => 90,
		],
	],
] );
```

JavaScript integrations receive the camel-cased equivalents. When a provider
does not supply `submission_id` / `submissionId`, Popup Maker generates a UUID
for that one normalized event. Provider-native submission IDs are the reliable
correlation key when matching independently observed server and frontend
callbacks; independently generated IDs are not a cross-transport deduplication
mechanism.

`source_url` defaults to the sanitized request referrer in PHP and the current
page URL in JavaScript. PHP resolves `source_post_id` from the effective source
URL when an explicit numeric post ID is not supplied, including after an
extension replaces the URL. Both values remain nullable because referrers may
be unavailable and not every URL represents a WordPress post. A PHP `null`
source URL remains `null` when localized for browser replay rather than being
replaced with the post-redirect page URL.

## Extension context

PHP extensions can use the existing
`pum_integrated_form_submission_args` filter. Frontend extensions use the
`pum.integration.form.submissionArgs` filter, which runs after defaults and
popup resolution but before the form key, conversion event, and normalized
success action are produced.

```js
PUM.hooks.addFilter(
	'pum.integration.form.submissionArgs',
	( args ) => ( {
		...args,
		context: {
			...args.context,
			myExtension: { campaignId: 90 },
		},
	} )
);
```

Context and source values are descriptive metadata, not proof of identity or
authorization. Consumers must validate untrusted values before privileged
operations and apply their own privacy and retention policies before storing
submission data.
