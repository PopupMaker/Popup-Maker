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
- **Namespaced Functions**: `includes/namespaced/` contains newer namespaced functions
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

#### Adding Advanced JavaScript Conditions
```php
// Register a JavaScript-based condition (evaluated in browser)
function my_plugin_register_advanced_conditions( $conditions ) {
    $conditions['device_orientation'] = [
        'name'     => __( 'Device Orientation', 'my-plugin' ),
        'group'    => __( 'Device', 'my-plugin' ),
        'advanced' => true, // Mark as JavaScript condition
        // No callback needed - handled in JavaScript
        'fields'   => [
            'orientation' => [
                'label'   => __( 'Orientation', 'my-plugin' ),
                'type'    => 'select',
                'options' => [
                    'portrait'  => __( 'Portrait', 'my-plugin' ),
                    'landscape' => __( 'Landscape', 'my-plugin' ),
                ],
            ],
        ],
    ];
    return $conditions;
}
add_filter( 'pum_registered_conditions', 'my_plugin_register_advanced_conditions' );

// Add JavaScript handler for the condition
add_action( 'wp_footer', function() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Register condition callback
        PUM.hooks.addFilter('popupMaker.conditionCallbacks', function(callbacks) {
            callbacks.device_orientation = function(settings) {
                var isLandscape = window.innerWidth > window.innerHeight;
                return (settings.orientation === 'landscape' && isLandscape) ||
                       (settings.orientation === 'portrait' && !isLandscape);
            };
            return callbacks;
        });
    });
    </script>
    <?php
});
```

**Note**: Conditions with `'advanced' => true` or no `'callback'` are evaluated client-side using JavaScript. This is useful for dynamic conditions based on browser state, user interactions, or device characteristics.

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
// Enqueue frontend assets through Popup Maker's asset cache system
function my_plugin_enqueue_assets() {
    // Frontend scripts that should be cached
    pum_enqueue_script( 
        'my-plugin-frontend', 
        plugins_url( '/js/frontend.js', __FILE__ ), 
        [ 'popup-maker-site' ],
        '1.0.0'
    );
    
    // Frontend styles for caching
    pum_enqueue_style(
        'my-plugin-frontend',
        plugins_url( '/css/frontend.css', __FILE__ ),
        [],
        '1.0.0'
    );
}
add_action( 'pum_enqueue_scripts', 'my_plugin_enqueue_assets' );

// For admin assets or non-popup assets, use standard WordPress functions
function my_plugin_admin_assets() {
    wp_enqueue_script(
        'my-plugin-admin',
        plugins_url( '/js/admin.js', __FILE__ ),
        [ 'jquery' ],
        '1.0.0'
    );
}
add_action( 'admin_enqueue_scripts', 'my_plugin_admin_assets' );
```

**Important**: Use `pum_enqueue_*` only for frontend assets that qualify for AssetCache. Use `wp_enqueue_*` for admin assets or assets that don't need caching.

### AssetCache System

Popup Maker's AssetCache system optimizes frontend performance by combining and caching all popup-related assets into single files.

#### How It Works
- **JavaScript**: Combined into `pum-site-scripts.js`
- **CSS**: Combined into `pum-site-styles.css`
- **Location**: Stored in `wp-content/uploads/pum/` (configurable)
- **Regeneration**: Automatic on popup/theme changes, settings updates, or extension changes

#### Benefits
1. **Performance**: Reduces HTTP requests, smaller total size, better caching
2. **Ad Blocker Bypass**: Randomized filenames avoid pattern-based blocking
3. **Smart Loading**: Only loads assets when popups are present on page
4. **Automatic Optimization**: Minification in production, deferred loading support

#### Adding Custom Assets to Cache
```php
// Add custom JavaScript with priority
add_filter( 'pum_generated_js', function( $js ) {
    $js['my-custom-js'] = [
        'content' => 'console.log("Custom JS");',
        'priority' => 5, // 0=core, 1-5=extensions, 10=default, 15-20=per-popup
    ];
    return $js;
});

// Add custom CSS
add_filter( 'pum_generated_css', function( $css ) {
    $css['my-custom-css'] = [
        'content' => '.my-popup { color: red; }',
        'priority' => 10,
    ];
    return $css;
});

// Add per-popup JavaScript
add_action( 'pum_generate_popup_js', function( $popup_id ) {
    if ( $popup_id === 123 ) {
        echo 'console.log("Popup 123 loaded");';
    }
});
```

### Registering Custom Call to Actions (CTAs)

CTAs allow popups to trigger conversions with tracking. Register custom CTAs by extending the base class:

```php
// Method 1: Using the filter
add_filter( 'popup_maker/registered_call_to_actions', function( $ctas ) {
    $ctas['my_custom_cta'] = new \MyPlugin\CallToAction\MyCustomCTA();
    return $ctas;
});

// Method 2: Using the action
add_action( 'popup_maker/register_call_to_actions', function( $cta_types ) {
    $cta_types->add( new \MyPlugin\CallToAction\MyCustomCTA() );
});

// Custom CTA Implementation
namespace MyPlugin\CallToAction;

use PopupMaker\Base\CallToAction;
use PopupMaker\Models\CallToAction as CTAModel;

class MyCustomCTA extends CallToAction {
    
    public $key = 'my_custom_cta';
    
    public function label(): string {
        return __( 'My Custom CTA', 'my-plugin' );
    }
    
    public function fields(): array {
        return [
            'general' => [
                'redirect_url' => [
                    'type'         => 'url',
                    'label'        => __( 'Redirect URL', 'my-plugin' ),
                    'required'     => true,
                    'dependencies' => [
                        'type' => 'my_custom_cta',
                    ],
                ],
            ],
        ];
    }
    
    public function action_handler( CTAModel $call_to_action, array $extra_args = [] ): void {
        // Always track conversion
        $call_to_action->track_conversion( $extra_args );
        
        // Get settings and perform action
        $redirect_url = $call_to_action->get_setting( 'redirect_url' );
        $this->safe_redirect( $redirect_url );
        exit;
    }
    
    public function validate_settings( array $settings ): \WP_Error|array|bool {
        // Validate required fields
        $validation = $this->validate_required_fields( $settings );
        if ( is_wp_error( $validation ) ) {
            return $validation;
        }
        
        // Custom validation
        if ( ! filter_var( $settings['redirect_url'], FILTER_VALIDATE_URL ) ) {
            return new \WP_Error( 'invalid_url', __( 'Invalid URL', 'my-plugin' ) );
        }
        
        return true;
    }
}
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

#### Popup Maker Custom Data Stores
Popup Maker provides several custom data stores in addition to using WordPress core data:

##### Available Stores
- `popup-maker/popups` - Popup management
- `popup-maker/call-to-actions` - CTA management  
- `popup-maker/settings` - Plugin settings
- `popup-maker/license` - License management
- `popup-maker/url-search` - URL search functionality

##### Using Popup Store
```typescript
import { popupStore, POPUP_STORE } from '@popup-maker/core-data';
import { useSelect, useDispatch } from '@wordpress/data';

function PopupManager() {
    // Selectors
    const { popups, popup, isLoading, error, hasEdits } = useSelect((select) => {
        const store = select(popupStore);
        // Or: const store = select(POPUP_STORE);
        return {
            popups: store.getPopups(),
            popup: store.getPopup(123), // Get specific popup
            isLoading: store.isResolving('getPopup', [123]),
            error: store.getFetchError(123),
            hasEdits: store.hasEdits(123),
            // Additional selectors:
            // isEditing: store.isEditorActive(),
            // editedValues: store.getCurrentEditorValues(),
            // canUndo: store.hasUndo(123),
            // canRedo: store.hasRedo(123),
        };
    }, []);

    // Actions
    const { 
        createPopup, 
        updatePopup, 
        deletePopup,
        editRecord,
        saveEditedRecord,
        undo,
        redo,
        resetRecordEdits
    } = useDispatch(popupStore);

    // Create new popup
    const handleCreate = async () => {
        const newPopup = await createPopup({
            title: 'New Popup',
            content: 'Popup content',
            status: 'draft',
            settings: {
                conditions: {
                    logicalOperator: 'or',
                    items: [],
                },
            },
        });
    };

    // Update existing popup
    const handleUpdate = async (id) => {
        await updatePopup({
            id,
            title: 'Updated Title',
            settings: { /* ... */ },
        });
    };

    // Edit mode with undo/redo
    const handleEdit = (id, edits) => {
        editRecord(id, edits);
    };

    const handleSaveEdits = async (id) => {
        await saveEditedRecord(id);
    };
}
```

###### Popup Store Selectors
- `getPopups()` - Get all popups
- `getPopup(id)` - Get specific popup by ID
- `getFiltered(predicate)` - Get filtered popups
- `getFetchError(id?)` - Get fetch error for popup or global error
- `getEditorId()` - Get currently editing popup ID
- `isEditorActive()` - Check if editor is active
- `getCurrentEditorValues()` - Get current editor values
- `hasEditedEntity(id)` - Check if popup has been edited
- `getEditedEntity(id)` - Get edited popup data
- `hasEdits(id)` - Check if popup has unsaved edits
- `hasUndo(id)` - Check if undo is available
- `hasRedo(id)` - Check if redo is available
- `getEditedPopup(id)` - Get popup with edits applied
- `getNotices()` - Get all notices
- `isResolving(selectorName, args?)` - Check if selector is loading

###### Popup Store Actions
- `createPopup(popup, validate?, withNotices?)` - Create new popup
- `updatePopup(popup, validate?, withNotices?)` - Update existing popup
- `deletePopup(id, forceDelete?)` - Delete popup
- `editRecord(id, edits)` - Start editing popup
- `saveEditedRecord(id, validate?, withNotices?)` - Save edited popup
- `undo(id)` - Undo last edit
- `redo(id)` - Redo last undone edit
- `resetRecordEdits(id)` - Reset all edits
- `updateEditorValues(values)` - Update editor values
- `changeEditorId(id)` - Change active editor popup
- `createNotice(notice)` - Create notice
- `removeNotice(id)` - Remove notice

##### Using Call to Actions Store
```typescript
import { callToActionStore, CALL_TO_ACTION_STORE } from '@popup-maker/core-data';
import { useSelect, useDispatch } from '@wordpress/data';

function CTAManager() {
    // Selectors
    const { ctas, cta, isEditing, hasEdits } = useSelect((select) => {
        const store = select(callToActionStore);
        // Or: const store = select(CALL_TO_ACTION_STORE);
        return {
            ctas: store.getCallToActions(),
            cta: store.getCallToAction(456),
            isEditing: store.isEditingCallToAction(456),
            hasEdits: store.hasEdits(456),
            // Additional selectors available similar to popup store
        };
    }, []);

    // Actions
    const { 
        createCallToAction,
        updateCallToAction,
        deleteCallToAction,
        editRecord,
        saveEditedRecord,
        undo,
        redo
    } = useDispatch(callToActionStore);

    // Work with CTAs
    const handleCreateCTA = async () => {
        const newCTA = await createCallToAction({
            title: 'New CTA',
            type: 'button',
            settings: { /* ... */ },
        });
    };
}
```

###### Call to Action Store Selectors
Similar to Popup Store with CTA-specific naming:
- `getCallToActions()` - Get all CTAs
- `getCallToAction(id)` - Get specific CTA
- `isEditingCallToAction(id)` - Check if CTA is being edited
- `getFiltered(predicate)` - Get filtered CTAs
- `hasEdits(id)` - Check for unsaved edits
- `hasUndo(id)` / `hasRedo(id)` - Undo/redo availability
- `isResolving(selectorName, args?)` - Loading state

###### Call to Action Store Actions
- `createCallToAction(cta, validate?, withNotices?)` - Create CTA
- `updateCallToAction(cta, validate?, withNotices?)` - Update CTA
- `deleteCallToAction(id, forceDelete?)` - Delete CTA
- `editRecord(id, edits)` - Edit CTA in memory
- `saveEditedRecord(id)` - Save edits to server
- `undo(id)` / `redo(id)` - Undo/redo edits

##### Using Settings Store
```typescript
import { settingsStore, useSettings } from '@popup-maker/core-data';

// Option 1: Using the custom hook (recommended)
function SettingsComponent() {
    const {
        settings,
        getSetting,
        updateSettings,
        saveSettings,
        isSaving,
        hasUnsavedChanges
    } = useSettings();

    // Get specific setting
    const analyticsEnabled = getSetting('analyticsEnabled', false);

    // Update and save settings
    const handleSave = async () => {
        updateSettings({ analyticsEnabled: true });
        await saveSettings();
    };
}

// Option 2: Using selectors/actions directly
function AlternativeSettings() {
    const settings = useSelect(select => 
        select(settingsStore).getSettings()
    );
    
    const { updateSettings, saveSettings } = useDispatch(settingsStore);
}
```

##### Using License Store
```typescript
import { licenseStore, useLicense } from '@popup-maker/core-data';

// Using the custom hook
function LicenseManager() {
    const {
        licenseData,
        licenseKey,
        licenseStatus,
        connect,
        updateLicenseKey,
        verifyLicense,
        isValid
    } = useLicense();

    const handleVerify = async () => {
        await verifyLicense(licenseKey);
    };
}
```

##### Using URL Search Store
```typescript
import { urlSearchStore } from '@popup-maker/core-data';

function URLSearchComponent() {
    const { results, isSearching } = useSelect((select) => {
        const store = select(urlSearchStore);
        return {
            results: store.getSearchResults('posts'),
            isSearching: store.isSearching('posts'),
        };
    });

    const { searchUrls } = useDispatch(urlSearchStore);

    const handleSearch = async (query) => {
        await searchUrls('posts', query);
    };
}
```

##### Store Registration
All Popup Maker stores are automatically registered when using the packages. However, if you need to manually register them:

```typescript
import { register } from '@wordpress/data';
import {
    popupStore,
    callToActionStore,
    settingsStore,
    licenseStore,
    urlSearchStore
} from '@popup-maker/core-data';

// Register stores (usually done automatically)
register(popupStore);
register(callToActionStore);
register(settingsStore);
register(licenseStore);
register(urlSearchStore);
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
   - Then checks for method: `PUM_Form_Fields::{$type}_callback()` (Note: This class doesn't exist, appears to be a bug)
   - Then checks for function: `pum_{$type}_callback()`
   - Falls back to: `PUM_Form_Fields::missing_callback()` (which also won't work due to missing class)

**Important**: Due to the bug referencing non-existent `PUM_Form_Fields` class, custom fields MUST use either:
- The action hook method: `add_action('pum_{$type}_field', $callback)`
- The function method: Define a function named `pum_{$type}_callback`

For rendering built-in field types, the actual implementation is in `PUM_Fields` class (`includes/legacy/class-pum-fields.php`) which has methods like `text_callback()`, `checkbox_callback()`, etc. These are typically called through `PUM_Fields::instance()->render_field()` or form instances that extend `PUM_Fields`.

#### Custom Field Types (PHP/Legacy)

To add a custom field type for PHP/Legacy admin forms:

```php
// Method 1: Using action hook (preferred for extensions)
add_action('pum_myfield_field', function($args) {
    // Get the PUM_Fields instance for field helpers
    $fields = PUM_Fields::instance();
    
    // Parse the value
    $value = isset($args['value']) ? $args['value'] : (isset($args['std']) ? $args['std'] : '');
    
    // Render field wrapper and label
    $fields->field_before($args);
    $fields->field_label($args);
    ?>
    
    <input type="text" 
           id="<?php echo esc_attr($args['id']); ?>" 
           name="<?php echo esc_attr($args['name']); ?>" 
           value="<?php echo esc_attr($value); ?>"
           placeholder="<?php echo esc_attr($args['placeholder']); ?>"
           class="<?php echo esc_attr($args['size']); ?>-text"
           <?php echo $args['required'] ? 'required' : ''; ?> />
    
    <?php
    // Render field description
    $fields->field_description($args);
    $fields->field_after();
});

// Method 2: Using callback function
function pum_myfield_callback($args) {
    $fields = PUM_Fields::instance();
    $value = isset($args['value']) ? $args['value'] : (isset($args['std']) ? $args['std'] : '');
    
    $fields->field_before($args);
    $fields->field_label($args);
    // ... render field HTML ...
    $fields->field_description($args);
    $fields->field_after();
}

// Method 3: For template fields (underscore.js templates)
add_action('pum_myfield_templ_field', function($args) {
    $fields = PUM_Fields::instance();
    
    $fields->field_before($args);
    $fields->field_label($args);
    ?>
    
    <input type="text" 
           id="<?php echo esc_attr($args['id']); ?>" 
           name="<?php echo esc_attr($args['name']); ?>" 
           value="{{data.<?php echo esc_attr($args['templ_name']); ?>}}"
           placeholder="<?php echo esc_attr($args['placeholder']); ?>"
           class="<?php echo esc_attr($args['size']); ?>-text" />
    
    <?php
    $fields->field_description($args);
    $fields->field_after();
});
```

#### Custom Field Types (React/Modern)

For modern React-based admin interfaces (CTA editor, block editor):

```typescript
// In packages/fields/src/lib/field.tsx, add your field type to the switch statement
// Or create a custom field component and use it directly

import { FieldPropsWithOnChange } from '@popup-maker/fields';

const MyCustomField = ({ 
    name, 
    value, 
    onChange, 
    placeholder, 
    required 
}: FieldPropsWithOnChange) => {
    return (
        <input
            type="text"
            name={name}
            value={value || ''}
            onChange={(e) => onChange(e.target.value)}
            placeholder={placeholder}
            required={required}
        />
    );
};

// Use in CTA editor or other React contexts
<Field
    type="myfield"
    name="custom_field"
    label={__('My Custom Field', 'popup-maker')}
    value={value}
    onChange={onChange}
/>
```

Note: The React field system is used in newer admin interfaces (CTA editor, block editor), while the PHP/Legacy system is used for popup/theme settings and older admin pages.

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
