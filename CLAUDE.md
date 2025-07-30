# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Popup Maker is a WordPress plugin for creating and managing popups. It's a mature codebase with both legacy PHP and modern React components, using a monorepo structure with multiple packages.

## Build Commands

### Development
- `npm run start` - Start development build with webpack
- `npm run start:hot` - Start development with hot module replacement
- `npm run build` - Build all assets for production
- `npm run build:production` - Production build with NODE_ENV=production

### Testing
- `npm run test:e2e` - Run Playwright end-to-end tests
- `npm run test:e2e:debug` - Run e2e tests in debug UI mode
- `npm run test:unit` - Run Jest unit tests
- `npm run test:unit:watch` - Run unit tests in watch mode
- `composer run tests` - Run PHPUnit tests
- `composer run coverage` - Generate test coverage reports

### Code Quality
- `npm run lint:js` - Lint TypeScript/JavaScript files
- `npm run lint:style` - Lint SCSS/CSS files  
- `npm run format` - Format code with wp-prettier
- `composer run lint` - Run PHPCS on PHP files
- `composer run format` - Auto-fix PHP code with PHPCBF
- `composer run phpstan` - Run PHPStan static analysis

### Package Management
- `npm run packages:build:tsc` - Build TypeScript for all packages
- `npm run validate:dep-tree` - Validate package dependency tree
- `npm run clean:deps:auto` - Clean up package dependencies

## Architecture

### PHP Structure
- **PSR-4 Namespace**: `PopupMaker\` maps to `classes/` directory
- **Legacy Code**: `includes/` contains backwards-compatible functions and deprecated classes
- **Modern Controllers**: Located in `classes/Controllers/` using dependency injection via `Plugin\Core`
- **Models**: `classes/Model/` for data objects (Popup, Theme, etc.)
- **Services**: `classes/Services/` for business logic and external integrations
- **Repository Pattern**: `classes/Repository/` for data access abstraction

### JavaScript/TypeScript Packages
- **Monorepo**: All packages in `packages/` directory with individual `package.json`
- **Build Output**: Compiled to `dist/packages/` with webpack
- **Modern Admin**: React components using `@wordpress/data` stores
- **Legacy Frontend**: jQuery-based popup engine in `assets/js/src/site/`

### Key Packages
- `core-data` - WordPress data stores for popups, themes, settings
- `fields` - Reusable form field components  
- `components` - UI component library
- `cta-admin` & `cta-editor` - Call-to-action admin interfaces
- `block-editor` & `block-library` - Gutenberg block support

### Database
- **Custom Tables**: `DB/Subscribers.php` for subscriber management
- **Post Types**: Uses WordPress posts for popups and themes
- **Options**: Settings stored via WordPress options API
- **Caching**: Custom caching layer in `Cache.php` and transients

## Development Patterns & APIs

### PHP Extension APIs

#### Adding Custom Triggers
```php
// Register a custom trigger
function my_plugin_register_triggers( $triggers ) {
    $triggers['scroll_percentage'] = [
        'name'            => __( 'Scroll Percentage', 'my-plugin' ),
        'modal_title'     => __( 'Scroll Trigger Settings', 'my-plugin' ),
        'settings_column' => sprintf( '<strong>%1$s</strong>: %2$s%%', __( 'Percentage', 'my-plugin' ), '{{data.percentage}}' ),
        'fields'          => [
            'general' => [
                'percentage' => [
                    'label'   => __( 'Scroll Percentage', 'my-plugin' ),
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
}
add_filter( 'pum_registered_triggers', 'my_plugin_register_triggers' );
```

#### Adding Custom Conditions
```php
// Register a custom condition
function my_plugin_register_conditions( $conditions ) {
    $conditions['user_membership'] = [
        'name'     => __( 'User Membership Level', 'my-plugin' ),
        'group'    => __( 'User', 'my-plugin' ),
        'callback' => 'my_plugin_check_membership_condition',
        'fields'   => [
            'level' => [
                'label'   => __( 'Membership Level', 'my-plugin' ),
                'type'    => 'select',
                'options' => [
                    'basic'   => __( 'Basic', 'my-plugin' ),
                    'premium' => __( 'Premium', 'my-plugin' ),
                    'vip'     => __( 'VIP', 'my-plugin' ),
                ],
            ],
        ],
    ];
    return $conditions;
}
add_filter( 'pum_registered_conditions', 'my_plugin_register_conditions' );

// Condition callback function
function my_plugin_check_membership_condition( $settings ) {
    $user_level = get_user_meta( get_current_user_id(), 'membership_level', true );
    return $user_level === $settings['level'];
}
```

#### Adding Custom Cookies
```php
// Register a custom cookie
function my_plugin_register_cookies( $cookies ) {
    $cookies['video_completion'] = [
        'name'   => __( 'Video Completion', 'my-plugin' ),
        'fields' => [
            'general' => [
                'video_id' => [
                    'label' => __( 'Video ID', 'my-plugin' ),
                    'type'  => 'text',
                ],
                'time'     => [
                    'label'   => __( 'Time', 'my-plugin' ),
                    'type'    => 'measure',
                    'unit'    => 'minutes',
                    'default' => 60,
                ],
            ],
        ],
    ];
    return $cookies;
}
add_filter( 'pum_registered_cookies', 'my_plugin_register_cookies' );
```

#### Asset Enqueuing
```php
// Enqueue assets through Popup Maker's asset cache system
function my_plugin_enqueue_assets() {
    // Frontend script
    pum_enqueue_script( 
        'my-plugin-frontend', 
        plugins_url( '/js/frontend.js', __FILE__ ), 
        [ 'popup-maker-site' ],
        '1.0.0'
    );
    
    // Admin script
    if ( is_admin() ) {
        pum_enqueue_script( 
            'my-plugin-admin', 
            plugins_url( '/js/admin.js', __FILE__ ), 
            [ 'popup-maker-admin' ],
            '1.0.0'
        );
    }
}
add_action( 'pum_enqueue_scripts', 'my_plugin_enqueue_assets' );
```

### JavaScript Frontend Integration

#### Form Integration Pattern
```javascript
// Integrate a new form plugin with Popup Maker
(function ($) {
    'use strict';
    
    const formProvider = 'my-form-plugin';
    
    // Listen for your form's success event
    $(document).on('my_form_success', function (event, formData) {
        const $form = $(event.target);
        const formId = $form.data('form-id');
        const formInstanceId = $form.data('instance-id');
        
        // Use PUM's form integration system
        if (window.PUM && window.PUM.integrations) {
            window.PUM.integrations.formSubmission($form, {
                formProvider,
                formId,
                formInstanceId,
                extras: {
                    formData: formData
                }
            });
        }
    });
})(jQuery);
```

#### Custom Trigger Implementation
```javascript
// Frontend trigger implementation
(function ($, PUM) {
    'use strict';
    
    // Register trigger handler through the hooks system
    PUM.hooks.addFilter('popupMaker.triggers', function(triggers) {
        triggers.scroll_percentage = function(settings, popup) {
            const percentage = parseInt(settings.percentage, 10);
            
            function checkScroll() {
                const scrollPercent = Math.round(
                    ($(window).scrollTop() / ($(document).height() - $(window).height())) * 100
                );
                
                if (scrollPercent >= percentage) {
                    PUM.open(popup.id);
                    $(window).off('scroll', checkScroll);
                }
            }
            
            $(window).on('scroll', checkScroll);
        };
        
        return triggers;
    });
    
})(jQuery, window.PUM);
```

#### Using PUM Hooks System and Events
```javascript
// Use the PUM hooks system and jQuery events for extensibility
(function ($) {
    // jQuery events on popup elements
    $(document).on('pumBeforeOpen', '.pum', function () {
        console.log('Popup opening:', this.id);
        // Custom logic here
    });
    
    $(document).on('pumAfterOpen', '.pum', function () {
        // Track analytics
        if (typeof gtag !== 'undefined') {
            gtag('event', 'popup_view', {
                popup_id: this.id
            });
        }
    });
    
    // PUM hooks system for form integration
    if (window.PUM && window.PUM.hooks) {
        PUM.hooks.addAction('pum.integration.form.success', function (form, args) {
            // Custom form success handling
            console.log('Form submitted:', args);
        });
    }
})(jQuery);
```

### TypeScript/React Admin Extensions

#### CTA Admin Quick Actions
```typescript
// packages/my-extension/src/index.ts
import { registerListQuickAction } from '@popup-maker/cta-admin';
import { __ } from '@wordpress/i18n';

// Register a custom quick action
registerListQuickAction({
    name: 'my-custom-action',
    group: 'general',
    priority: 15,
    render: ({ values }) => (
        <button
            type="button"
            className="button"
            onClick={() => {
                // Custom action logic
                console.log('Custom action for CTA:', values.id);
            }}
        >
            {__('My Action', 'my-plugin')}
        </button>
    ),
});
```

#### Data Store Integration
```typescript
// Access Popup Maker data stores
import { useSelect, useDispatch } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';

// Note: Popup Maker also has a custom 'callToActionStore' for CTA-specific functionality

function MyComponent() {
    // Get Call to Actions using WordPress core data
    const { ctas, isLoading } = useSelect((select) => ({
        ctas: select(coreDataStore).getEntityRecords('postType', 'pum_cta'),
        isLoading: !select(coreDataStore).hasFinishedResolution(
            'getEntityRecords',
            ['postType', 'pum_cta']
        ),
    }), []);
    
    // Dispatch actions
    const { saveEntityRecord } = useDispatch(coreDataStore);
    
    const handleSave = (ctaData) => {
        saveEntityRecord('postType', 'pum_cta', ctaData);
    };
    
    if (isLoading) return <div>Loading...</div>;
    
    return (
        <div>
            {ctas?.map(cta => (
                <div key={cta.id}>{cta.title.rendered}</div>
            ))}
        </div>
    );
}
```

#### Using Fields
```typescript
// Using field components from @popup-maker/fields
import { TextField, SelectField, CheckboxField } from '@popup-maker/fields';

function MyComponent() {
    const [value, setValue] = useState('');
    
    return (
        <TextField
            field={{
                id: 'my-field',
                label: 'My Field',
                placeholder: 'Enter value',
                required: true
            }}
            value={value}
            onChange={setValue}
        />
    );
}
```

### WordPress Data Store Patterns

#### Entity Selectors
```typescript
// Efficient entity data access
import { createRegistrySelector, createSelector } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';

// Get all popups with memoization
const getPopups = createRegistrySelector((select) =>
    createSelector(
        () => select(coreDataStore).getEntityRecords('postType', 'popup'),
        () => [
            select(coreDataStore).getEntityRecords('postType', 'popup'),
            select(coreDataStore).hasFinishedResolution(
                'getEntityRecords',
                ['postType', 'popup']
            )
        ]
    )
);

// Get popup by ID with status
const getPopupWithStatus = createRegistrySelector((select) =>
    createSelector(
        (_state, id) => ({
            popup: select(coreDataStore).getEntityRecord('postType', 'popup', id),
            isSaving: select(coreDataStore).isSavingEntityRecord('postType', 'popup', id),
            hasEdits: select(coreDataStore).hasEditsForEntityRecord('postType', 'popup', id),
        }),
        (_state, id) => [id]
    )
);
```

#### Thunk Actions
```typescript
// Async actions with error handling
const savePopupWithNotification = (id, data) => async ({ dispatch, select, registry }) => {
    try {
        dispatch.setLoading(true);
        
        // Save using core store
        await registry.dispatch(coreDataStore).saveEntityRecord(
            'postType',
            'popup',
            { id, ...data }
        );
        
        // Show success notification
        registry.dispatch('core/notices').createSuccessNotice(
            __('Popup saved successfully!', 'popup-maker')
        );
        
    } catch (error) {
        // Handle error
        registry.dispatch('core/notices').createErrorNotice(
            __('Failed to save popup.', 'popup-maker')
        );
    } finally {
        dispatch.setLoading(false);
    }
};
```

### PHP Field Definition System

Popup Maker uses a comprehensive field system for building forms in triggers, conditions, cookies, and settings. Fields are defined as PHP arrays with specific properties.

#### Available Field Types

##### Text Input Fields
- **text** - Standard text input
- **password** - Password input (masked)
- **email** - Email input with validation
- **search** - Search input field
- **url** - URL input with validation
- **tel** - Telephone number input
- **hidden** - Hidden input field

##### Numeric Input Fields
- **number** - Number input with min/max/step
- **range** - HTML5 range slider
- **rangeslider** - Enhanced range with manual input and unit support
- **measure** - Measurement field with unit selector

##### Selection Fields
- **select** - Dropdown select field
- **radio** - Radio button group
- **checkbox** - Single checkbox
- **multicheck** - Multiple checkboxes
- **postselect** / **post_select** - WordPress post selector
- **taxonomyselect** / **taxonomy_select** - WordPress taxonomy term selector
- **objectselect** - Generic object selector (posts, taxonomies, users)

##### Text Area & Editor
- **textarea** - Multi-line text input
- **rich_editor** - WordPress TinyMCE editor (limited support)

##### Special Fields
- **button** - Button element
- **heading** - Section heading (non-input)
- **html** - Raw HTML content
- **hook** - Execute WordPress action hook
- **license_key** - License key input for extensions
- **cookie_key** - Special field for cookie key management

##### Upload & Media (Limited Support)
- **upload** - File upload field
- **color** - Color picker

#### Field Properties

All fields support these base properties:

```php
[
    // Basic Properties
    'id'             => 'field_id',           // Unique field identifier
    'type'           => 'text',               // Field type (required)
    'label'          => __('Label', 'text-domain'), // Field label
    'desc'           => __('Description', 'text-domain'), // Help text
    'name'           => 'field_name',         // Input name attribute
    'section'        => 'main',               // Section grouping
    
    // Value Properties
    'std'            => '',                   // Default value
    'value'          => null,                 // Current value
    'placeholder'    => '',                   // Placeholder text
    
    // Validation & State
    'required'       => false,                // Required field
    'readonly'       => false,                // Read-only state
    'disabled'       => false,                // Disabled state
    'private'        => false,                // Private field (hidden from UI)
    
    // Display Properties
    'class'          => '',                   // CSS class names
    'size'           => 'regular',            // Field size (small, regular, large)
    'desc_position'  => 'bottom',             // Description position (top, bottom)
    'priority'       => 10,                   // Display priority
    'doclink'        => '',                   // Documentation URL
]
```

#### Type-Specific Properties

##### Select Fields
```php
[
    'options'        => [],                   // Array of value => label pairs
    'multiple'       => false,                // Allow multiple selection
    'select2'        => null,                 // Enable Select2 enhancement
    'allow_blank'    => true,                 // Allow empty selection
    'as_array'       => false,                // Return value as array
]
```

##### Numeric Fields (number, range, rangeslider)
```php
[
    'min'            => 0,                    // Minimum value
    'max'            => 100,                  // Maximum value
    'step'           => 1,                    // Step increment
    'force_minmax'   => false,                // Enforce min/max limits
    'unit'           => 'px',                 // Display unit
]
```

##### Textarea
```php
[
    'rows'           => 5,                    // Number of rows
    'cols'           => 50,                   // Number of columns
]
```

##### Checkbox
```php
[
    'checkbox_val'   => 1,                    // Value when checked
]
```

##### Post/Taxonomy Select
```php
[
    'post_type'      => 'post',               // Post type(s) to query
    'taxonomy'       => 'category',           // Taxonomy to query
    'object_type'    => 'post_type',          // Object type (post_type, taxonomy)
    'object_key'     => 'post',               // Object key
]
```

##### Measure Field
```php
[
    'unit'           => 'px',                 // Default unit
    'units'          => [                     // Available units
        'px'  => 'px',
        '%'   => '%',
        'em'  => 'em',
        'rem' => 'rem',
    ],
]
```

##### Button
```php
[
    'button_type'    => 'submit',             // Button type (submit, button, reset)
]
```

##### Hook
```php
[
    'hook'           => 'action_name',        // WordPress action to execute
]
```

#### Complete Field Example

```php
// Trigger with multiple field types
$triggers['custom_trigger'] = [
    'name'            => __('Custom Trigger', 'text-domain'),
    'modal_title'     => __('Custom Trigger Settings', 'text-domain'),
    'settings_column' => sprintf('<strong>%s</strong>: %s', __('Value', 'text-domain'), '{{data.my_value}}'),
    'fields'          => [
        'general' => [
            'my_text' => [
                'label'       => __('Text Input', 'text-domain'),
                'type'        => 'text',
                'desc'        => __('Enter some text', 'text-domain'),
                'std'         => 'default value',
                'placeholder' => __('Type here...', 'text-domain'),
                'required'    => true,
            ],
            'my_number' => [
                'label'       => __('Number Range', 'text-domain'),
                'type'        => 'rangeslider',
                'min'         => 0,
                'max'         => 100,
                'step'        => 5,
                'unit'        => '%',
                'std'         => 50,
                'desc'        => __('Select a percentage', 'text-domain'),
            ],
            'my_posts' => [
                'label'       => __('Select Posts', 'text-domain'),
                'type'        => 'postselect',
                'post_type'   => ['post', 'page'],
                'multiple'    => true,
                'as_array'    => true,
                'select2'     => true,
                'placeholder' => __('Choose posts...', 'text-domain'),
            ],
            'my_measure' => [
                'label'       => __('Size', 'text-domain'),
                'type'        => 'measure',
                'std'         => '10px',
                'desc'        => __('Set the size with units', 'text-domain'),
            ],
        ],
        'advanced' => [
            'enable_feature' => [
                'label'       => __('Enable Feature', 'text-domain'),
                'type'        => 'checkbox',
                'desc'        => __('Check to enable', 'text-domain'),
            ],
        ],
    ],
];
```

#### Field Rendering Process

1. Fields are processed through `PUM_Utils_Fields::parse_field()` which normalizes options
2. Rendering happens via `PUM_Utils_Fields::render_field()` which:
   - First checks for custom action: `do_action("pum_{$type}_field", $args)`
   - Then checks for method: `PUM_Form_Fields::{$type}_callback()`
   - Then checks for function: `pum_{$type}_callback()`
   - Falls back to: `PUM_Form_Fields::missing_callback()`

#### Custom Field Types

To add a custom field type:

```php
// Method 1: Using action hook
add_action('pum_myfield_field', function($args) {
    $value = $args['value'] ?? $args['std'] ?? '';
    ?>
    <input type="text" 
           id="<?php echo esc_attr($args['id']); ?>" 
           name="<?php echo esc_attr($args['name']); ?>" 
           value="<?php echo esc_attr($value); ?>"
           class="my-custom-field <?php echo esc_attr($args['class']); ?>" />
    <?php
});

// Method 2: Using callback function
function pum_myfield_callback($args) {
    // Render field HTML
}
```

### Extension Development Best Practices
- Use `pum_` prefixes for all public functions and hooks
- Check for Popup Maker existence before calling functions
- Follow WordPress coding standards (enforced by PHPCS)
- Use dependency injection over global access
- Leverage `@wordpress/data` stores for admin interfaces
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