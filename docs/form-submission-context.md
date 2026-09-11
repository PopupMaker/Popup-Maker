# Normalized form submission context

Popup Maker form integrations report successful submissions through
`pum_integrated_form_submission()` in PHP and
`PUM.integrations.formSubmission()` in JavaScript. The normalized envelope is
provider-independent, and extensions can attach their own namespaced data to
`context` without changing each provider integration.

The envelope also carries a `phases` policy. Observation is unconditional;
actions, Core conversion tracking, and frontend effects are independently
authorized.

## Contract

PHP integrations use snake-cased keys:

```php
pum_integrated_form_submission( [
	'form_provider'   => 'example',
	'form_id'         => 12,
	'submission_id'   => 'entry-456',
	'native_entry_id' => 'entry-456',
	'fields'          => [
		'email' => 'person@example.test',
	],
	'source_post_id'  => 78,
	'source_url'      => 'https://example.com/guide/',
	'context'         => [
		'my_extension' => [
			'campaign_id' => 90,
		],
	],
] );
```

`native_entry_id` identifies the provider-owned persisted entry when the
provider supplies one. `fields` contains the server-observed submitted values
available to PHP observation and action consumers. Providers must dispatch
these values through the existing normalized success call; features must not
add parallel provider hooks.

## Processing phases

The normalized phase map is:

```php
[
	'actions'  => true,
	'tracking' => true,
	'frontend' => true,
]
```

Normal browser POST requests default all three phases to `true`. Server-side
AJAX and REST callbacks default to `actions: true`, `tracking: false`, and
`frontend: false`. Provider callbacks that use a custom transport outside
WordPress's AJAX endpoint can pass `ajax: true` to receive the same defaults.
This lets extensions act on a provider-confirmed success without counting the
conversion before the provider's browser success event or trying to replay
browser behavior from an asynchronous response.

Callers may pass explicit phases to `pum_integrated_form_submission()`. The
`pum_integrated_form_submission_phases` PHP filter and
`pum.integration.form.phases` JavaScript filter can adjust the final policy.
`tracked: true` is an authoritative receipt and always forces tracking off.
The PHP phase filter runs once after the complete submission-args filter. Both
the legacy popup conversion count and the form conversion counters consume
that same resolved tracking decision.

Use these observation hooks for capture, diagnostics, or consumers that must
see every normalized success, including suppressed repeats:

- PHP: `pum_integrated_form_submission`
- JavaScript: `pum.integration.form.observed`

Use these gated hooks for action runners:

- PHP: `pum_integrated_form_submission_actions`
- JavaScript: `pum.integration.form.actions`

The action hooks only fire when `phases.actions` is true. The new browser
`observed` hook is unconditional. The existing
`pum.integration.form.success` hook remains the provider-confirmed frontend
success/effect hook and fires only when `phases.frontend` is authorized. Core
analytics observes `observed` and honors `phases.tracking`; Core cookies, form
triggers, and popup close behavior remain on `success`.

The PHP and JavaScript action hooks are runtime-specific contracts, not two
halves of one guaranteed-once action. An AJAX provider may confirm success on
the server and later emit a browser success without exposing the same native
receipt to JavaScript; both runtimes therefore default actions on. Server-side
actions should subscribe only to the PHP action hook. Browser-only actions
should subscribe only to the JavaScript action hook. A consumer that
intentionally listens to both must provide its own cross-runtime idempotency
key. Popup Maker does not treat independently generated UUIDs as proof that
the callbacks represent the same submission.

On a non-AJAX POST, PHP performs authorized server actions and tracking. The
localized browser replay is marked `tracked: true` with actions and tracking
disabled, while frontend effects remain enabled. This preserves cookies,
triggers, and popup behavior without repeating server actions or conversions.

JavaScript keeps a bounded, request-local history of the 100 most recent native
submission keys (`provider + form + submission ID`). A repeated native key has
all three phases disabled but still reaches the observation hook. Generated
UUIDs identify one normalized event only and are never used to claim
cross-transport deduplication.

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

Submitted `fields`, `raw_fields`, and `native_entry_id` are server-only. Popup
Maker removes them from localized non-AJAX frontend replay data so submitted
PII and provider-admin identity are not exposed in page source. The existing
`submissionId` remains available to the browser for provider-native
cross-runtime deduplication.

## Public extension points

| Name | Runtime | Purpose |
| --- | --- | --- |
| `pum_integrated_form_submission_args` | PHP filter | Extend or normalize the envelope before phases resolve. |
| `pum_integrated_form_submission_phases` | PHP filter | Authorize actions, tracking, and frontend replay independently. |
| `pum_integrated_form_submission` | PHP action | Observe every normalized server success. |
| `pum_integrated_form_submission_actions` | PHP action | Run authorized server actions. |
| `pum.integration.form.submissionArgs` | JS filter | Extend or normalize the browser envelope. |
| `pum.integration.form.phases` | JS filter | Authorize browser actions, tracking, and frontend effects independently. |
| `pum.integration.form.observed` | JS action | Observe every normalized browser success, including suppressed repeats. |
| `pum.integration.form.success` | JS action | Run existing provider-confirmed frontend success effects when authorized. |
| `pum.integration.form.actions` | JS action | Run authorized browser actions. |
