# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with the Popup Maker WordPress plugin.

## Project Overview

Popup Maker is a mature WordPress plugin for creating popups. It uses both legacy PHP and modern React components in a monorepo structure.

## Quick Start Commands

```bash
# Setup
npm install && composer install

# Development
npm run start              # Watch mode
npm run start:hot          # Hot module replacement
npm run build              # Development build
npm run build:production   # Production build

# Testing
npm run test:e2e          # Playwright E2E tests
npm run test:e2e:debug    # Debug mode with UI
npm run test:unit         # Jest unit tests
composer run tests        # PHPUnit tests
composer run coverage     # Test coverage

# Code Quality
npm run lint:js           # ESLint
npm run lint:style        # Stylelint
npm run format            # Prettier
composer run lint         # PHPCS
composer run format       # PHPCBF
composer run phpstan      # Static analysis
```

## Architecture

### PHP Structure (PSR-4: `PopupMaker\`)

-   **Modern**: `classes/` - Namespaced classes (Repository, Model, Service patterns)
-   **Legacy**: `includes/` - Backward-compatible functions, some namespaced functions within `includes/namespaced/`
-   **Service Container**: Pimple for dependency injection

### JavaScript/TypeScript

-   **Modern**: `packages/` - Monorepo with `@popup-maker/*` packages
-   **Legacy**: `assets/js/src/` - jQuery-based code
-   **Build**: Webpack → `dist/`

### Key Packages

-   `core-data` - Data stores (popups, CTAs, settings)
-   `fields` - Form field components
-   `cta-admin` / `cta-editor` - CTA interfaces
-   `block-editor` - Gutenberg integration

## Extension APIs

### Custom Triggers

```php
add_filter('pum_registered_triggers', function($triggers) {
    $triggers['scroll_percentage'] = [
        'name'            => __('Scroll Percentage', 'my-plugin'),
        'modal_title'     => __('Scroll Trigger Settings', 'my-plugin'),
        'settings_column' => sprintf('<strong>%1$s</strong>: %2$s%%', __('Percentage', 'my-plugin'), '{{data.percentage}}'),
        'fields'          => [
            'general' => [
                'percentage' => [
                    'label'   => __('Scroll Percentage', 'my-plugin'),
                    'type'    => 'rangeslider',
                    'min'     => 0,
                    'max'     => 100,
                    'step'    => 5,
                    'default' => 50,
                ],
            ],
        ],
    ];
    return $triggers;
});
```

### Custom Conditions

```php
// PHP-evaluated condition
add_filter('pum_registered_conditions', function($conditions) {
    $conditions['user_membership'] = [
        'name'     => __('User Membership Level', 'my-plugin'),
        'group'    => __('User', 'my-plugin'),
        'callback' => 'my_plugin_check_membership_condition', // PHP callback
        'fields'   => [
            'level' => [
                'label'   => __('Membership Level', 'my-plugin'),
                'type'    => 'select',
                'options' => [
                    'basic'   => __('Basic', 'my-plugin'),
                    'premium' => __('Premium', 'my-plugin'),
                ],
            ],
        ],
    ];
    return $conditions;
});

function my_plugin_check_membership_condition($settings) {
    $user_level = get_user_meta(get_current_user_id(), 'membership_level', true);
    return $user_level === $settings['level'];
}

// JavaScript-evaluated condition
$conditions['device_orientation'] = [
    'name'     => __('Device Orientation', 'my-plugin'),
    'advanced' => true, // Mark for JavaScript evaluation
    // No callback - handled in JavaScript
];
```

**Note**: Conditions with `'advanced' => true` or no `'callback'` are evaluated client-side with a matching global window[function_name] function.

### Custom CTAs

```php
namespace MyPlugin\CallToAction;

use PopupMaker\Base\CallToAction;
use PopupMaker\Models\CallToAction as CTAModel;

class MyCustomCTA extends CallToAction {

    public $key = 'my_custom_cta';

    public function label(): string {
        return __('My Custom CTA', 'my-plugin');
    }

    public function fields(): array {
        return [
            'general' => [
                'redirect_url' => [
                    'type'         => 'url',
                    'label'        => __('Redirect URL', 'my-plugin'),
                    'required'     => true,
                    'dependencies' => [
                        'type' => 'my_custom_cta',
                    ],
                ],
            ],
        ];
    }

    public function action_handler(CTAModel $call_to_action, array $extra_args = []): void {
        // Always track conversion first
        $call_to_action->track_conversion($extra_args);

        // Perform your action
        $redirect_url = $call_to_action->get_setting('redirect_url');
        $this->safe_redirect($redirect_url);
        exit;
    }

    public function validate_settings(array $settings): \WP_Error|array|bool {
        // Validate required fields
        $validation = $this->validate_required_fields($settings);
        if (is_wp_error($validation)) {
            return $validation;
        }

        // Custom validation
        if (!filter_var($settings['redirect_url'], FILTER_VALIDATE_URL)) {
            return new \WP_Error('invalid_url', __('Invalid URL', 'my-plugin'));
        }

        return true;
    }
}

// Register CTA
add_filter('popup_maker/registered_call_to_actions', function($ctas) {
    $ctas['my_custom_cta'] = new \MyPlugin\CallToAction\MyCustomCTA();
    return $ctas;
});
```

### Asset Caching System

```php
// Frontend assets (will be cached)
add_action('pum_enqueue_scripts', function() {
    pum_enqueue_script(
        'my-plugin-frontend',
        plugins_url('/js/frontend.js', __FILE__),
        ['popup-maker-site'],
        '1.0.0'
    );

    pum_enqueue_style(
        'my-plugin-frontend',
        plugins_url('/css/frontend.css', __FILE__),
        [],
        '1.0.0'
    );
});

// Admin assets (not cached)
add_action('admin_enqueue_scripts', function() {
    wp_enqueue_script(
        'my-plugin-admin',
        plugins_url('/js/admin.js', __FILE__),
        ['jquery'],
        '1.0.0'
    );
});
```

**AssetCache Details**:

-   Combined files: `pum-site-scripts.js` & `pum-site-styles.css`
-   Location: `wp-content/uploads/pum/`
-   Priority: 0=core, 1-5=extensions, 10=default, 15-20=per-popup
-   Auto-regenerates on popup/theme changes

## Frontend JavaScript APIs

### Form Integration

```javascript
( function ( $ ) {
	$( document ).on( 'my_form_success', function ( event, formData ) {
		const $form = $( event.target );

		if ( window.PUM && window.PUM.integrations ) {
			window.PUM.integrations.formSubmission( $form, {
				formProvider: 'my-form-plugin',
				formId: $form.data( 'form-id' ),
				formInstanceId: $form.data( 'instance-id' ),
				extras: {
					formData: formData,
				},
			} );
		}
	} );
} )( jQuery );
```

### Custom Trigger (Frontend)

```javascript
( function ( $, PUM ) {
	PUM.hooks.addFilter( 'popupMaker.triggers', function ( triggers ) {
		triggers.scroll_percentage = function ( settings, popup ) {
			const percentage = parseInt( settings.percentage, 10 );

			function checkScroll() {
				const scrollPercent = Math.round(
					( $( window ).scrollTop() /
						( $( document ).height() - $( window ).height() ) ) *
						100
				);

				if ( scrollPercent >= percentage ) {
					PUM.open( popup.id );
					$( window ).off( 'scroll', checkScroll );
				}
			}

			$( window ).on( 'scroll', checkScroll );
		};

		return triggers;
	} );
} )( jQuery, window.PUM );
```

## React/TypeScript APIs

### Data Stores

```typescript
import {
	popupStore,
	callToActionStore,
	settingsStore,
} from '@popup-maker/core-data';
import { useSelect, useDispatch } from '@wordpress/data';

// Popup Store
const { popups, popup } = useSelect( ( select ) => {
	const store = select( popupStore );
	return {
		popups: store.getPopups(),
		popup: store.getPopup( 123 ),
		isLoading: store.isResolving( 'getPopup', [ 123 ] ),
		hasEdits: store.hasEdits( 123 ),
	};
} );

const { createPopup, updatePopup, deletePopup, undo, redo } =
	useDispatch( popupStore );

// Settings Store (with custom hook)
import { useSettings } from '@popup-maker/core-data';

const { settings, getSetting, updateSettings, saveSettings } = useSettings();
```

### Store Methods Reference

**Popup Store**

-   Selectors: `getPopups()`, `getPopup(id)`, `hasEdits(id)`, `hasUndo(id)`, `hasRedo(id)`
-   Actions: `createPopup()`, `updatePopup()`, `deletePopup()`, `editRecord()`, `saveEditedRecord()`, `undo()`, `redo()`

**CTA Store**

-   Selectors: `getCallToActions()`, `getCallToAction(id)`, `isEditingCallToAction(id)`
-   Actions: `createCallToAction()`, `updateCallToAction()`, `deleteCallToAction()`

## Field System

### Field Types

-   **Text**: `text`, `email`, `url`, `password`, `hidden`
-   **Numeric**: `number`, `range`, `rangeslider`, `measure`
-   **Selection**: `select`, `radio`, `checkbox`, `multicheck`
-   **WordPress**: `postselect`, `taxonomyselect`, `objectselect`
-   **Special**: `textarea`, `button`, `heading`, `html`, `hook`, `license_key`

### Field Properties

```php
[
    // Required
    'id'    => 'field_id',
    'type'  => 'text',
    'label' => __('Label', 'text-domain'),

    // Common
    'desc'        => __('Help text', 'text-domain'),
    'std'         => 'default value',
    'placeholder' => 'Enter value...',
    'required'    => true,

    // Type-specific
    'min'         => 0,      // number, range, rangeslider
    'max'         => 100,    // number, range, rangeslider
    'step'        => 5,      // number, range, rangeslider
    'options'     => [],     // select, radio, multicheck
    'multiple'    => true,   // select, postselect
    'post_type'   => 'post', // postselect
    'taxonomy'    => 'tag',  // taxonomyselect
]
```

### Extension Development Best Practices
- Use `pum_` prefixes for all public functions and hooks
- Use custom `\PopupMaker\{ExtensionName}\` namespace for classes/functions/hooks
- Check for Popup Maker existence before calling functions
- Follow WordPress coding standards (enforced by PHPCS)
- Use dependency injection over global access
- **Prioritize `@popup-maker/*` packages over `@wordpress/*` when available** (better optimization and consistency)
- Use Popup Maker's data stores (`popupStore`, `callToActionStore`) instead of WordPress core data when possible
- Use PUM.hooks system for frontend extensibility
- Follow `.cursor/rules/pm-best-practices.mdc` guidelines

## Testing Strategy

### E2E Tests
- Playwright tests in `tests/e2e/`
- Test popup functionality and admin workflows
- Run against local WordPress environment

### Unit Tests
- Jest for JavaScript/TypeScript in `tests/unit/`
- PHPUnit for PHP in `tests/php/tests/`
- Mockery for PHP mocking

### Code Quality
- PHPStan for static analysis
- PHPCS for WordPress coding standards
- ESLint for JavaScript/TypeScript
- Stylelint for CSS/SCSS

## Dependency Management

### PHP Dependencies
- Composer with vendor prefixing via Strauss
- Dependencies compiled to `vendor-prefixed/` with `PopupMaker\Vendor\` namespace
- Run `composer install` to set up prefixed dependencies

### JavaScript Dependencies
- NPM workspace for monorepo packages
- WordPress scripts for build tooling
- Custom webpack plugins for asset optimization

## Legacy Considerations

- `includes/` contains legacy functions for backwards compatibility
- Gradual migration from jQuery to React for admin interfaces
- `popmake_` prefixed functions are deprecated
- Asset cache system maintains compatibility with older extensions

## Workflow Notes

### Package Management Considerations
- When adding new packages, we have to update webpack config, tsconfigs, dependency extraction plugin package list AND Assets.php appropriately
