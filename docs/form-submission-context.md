# Normalized form submission context

Popup Maker form integrations report successful submissions through
`pum_integrated_form_submission()` in PHP and
`PUM.integrations.formSubmission()` in JavaScript. Extensions can attach
portable, provider-independent data to the `context` object without coupling
their behavior to a specific form plugin.

```php
pum_integrated_form_submission( [
	'form_provider'  => 'example',
	'form_id'        => 12,
	'submission_id'  => 'entry-456',
	'source_post_id' => 78,
	'context'        => [
		'my_extension' => [
			'campaign_id' => 90,
		],
	],
] );
```

JavaScript integrations use camel-cased keys. The
`pum.integration.form.submissionArgs` filter runs after Popup Maker has built
the form key and resolved the popup, but before conversion and success handlers
run.

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

Context is descriptive, not proof of authorization. Consumers must validate
untrusted values before using them for privileged operations.
