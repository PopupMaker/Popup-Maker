# Popup Maker Cookie Events System --uc

Comprehensive guide for implementing cookie-based popup deactivation events across the PM ecosystem.

## Cookie System Architecture

### PHP Registration Pattern

Cookie events registered via `pum_registered_cookies` filter:

```php
// Basic registration
add_filter('pum_registered_cookies', function($cookies) {
    $cookies['my_event'] = [
        'name' => __('My Event', 'domain'),
    ];
    return $cookies;
});

// Advanced registration with custom fields
$cookies['form_submission'] = [
    'name'            => __('Form Submission', 'domain'),
    'modal_title'     => __('Form Cookie Settings', 'domain'), 
    'settings_column' => '<pre>{{data.form}}: {{data.time}}</pre>',
    'fields'          => array_merge_recursive(
        $default_cookie_fields, // From PUM_Cookies::cookie_fields()
        [
            'general' => [
                'form' => [
                    'type'    => 'select',
                    'label'   => __('Form Type', 'domain'),
                    'options' => get_available_forms(),
                    'std'     => 'any',
                ],
            ],
        ]
    ),
];
```

### JS Implementation Pattern

Frontend cookie handlers extend `$.fn.popmake.cookies`:

```javascript
$.extend($.fn.popmake.cookies, {
    my_event: function(settings) {
        var $popup = PUM.getPopup(this);
        
        // Listen for specific event
        $popup.on('myCustomEvent', function() {
            $popup.popmake('setCookie', settings);
        });
    }
});
```

## Core Cookie Types & Patterns

### Built-in Event Types

#### `on_popup_open`
→ **Trigger**: After popup opens  
→ **Hook**: `pumAfterOpen`  
→ **Use**: Frequency capping, single-show modals

#### `on_popup_close` 
→ **Trigger**: Before popup closes  
→ **Hook**: `pumBeforeClose`  
→ **Use**: Exit prevention, interaction tracking

#### `on_popup_conversion`
→ **Trigger**: User conversion event  
→ **Hook**: `pumConversion`  
→ **Use**: Goal achievement tracking

#### `form_submission`
→ **Trigger**: Form success + conditions  
→ **Integration**: `PUM.integrations.formSubmission()`  
→ **Fields**: `form`, `only_in_popup`  
→ **Use**: Form completion tracking

#### `manual`
→ **Trigger**: Programmatic via shortcode/JS  
→ **Hook**: `pumSetCookie`  
→ **Shortcode**: `[popup_cookie name="{{data.name}}" expires="{{data.time}}"]`

### Extension Event Types

#### Age Verification (@popup-maker-age-verification-modals)
```javascript
// PHP Registration
$cookies['age_verified'] = ['name' => __('Age Verified', 'domain')];

// JS Implementation  
$.extend($.fn.popmake.cookies, {
    age_verified: function(settings) {
        var $popup = PUM.getPopup(this);
        $popup.find('.pum-age-form')
            .on('verification.success', function() {
                $popup.popmake('setCookie', settings);
            });
    }
});
```

#### Login/Registration (@popup-maker-ajax-login-modals)
```javascript
// Events: login_successful, registration_successful
login_successful: function(settings) {
    var $popup = PUM.getPopup(this);
    $popup.find('.pum-alm-form')
        .on('login.success', function() {
            $popup.popmake('setCookie', settings);
        });
}
```

## Cookie Configuration System

### Default Cookie Fields (@.claude/templates/pm/primers/field-types.md)

```php
// Standard cookie fields (all events inherit)
PUM_Cookies::cookie_fields() => [
    'general' => [
        'name' => [
            'label' => __('Cookie Name', 'domain'),
            'type'  => 'text',
            'std'   => '',
        ],
        'time' => [
            'label' => __('Cookie Time', 'domain'), 
            'type'  => 'text',
            'std'   => '1 month',
            'desc'  => __('Plain english time', 'domain'),
        ],
    ],
    'advanced' => [
        'session' => [
            'label' => __('Use Session Cookie?', 'domain'),
            'type'  => 'checkbox', 
            'std'   => false,
        ],
        'path' => [
            'label' => __('Sitewide Cookie', 'domain'),
            'type'  => 'checkbox',
            'std'   => true,
        ],
        'key' => [
            'label' => __('Cookie Key', 'domain'),
            'type'  => 'cookie_key',
        ],
    ],
];
```

### Custom Event with Fields

```php
add_filter('pum_registered_cookies', function($cookies) {
    $cookies['ecommerce_purchase'] = [
        'name'   => __('Purchase Complete', 'domain'),
        'fields' => array_merge_recursive(
            PUM_Cookies::instance()->cookie_fields(),
            [
                'general' => [
                    'min_amount' => [
                        'type'  => 'number',
                        'label' => __('Minimum Amount', 'domain'),
                        'std'   => 0,
                        'min'   => 0,
                        'step'  => 0.01,
                    ],
                    'product_categories' => [
                        'type'     => 'multiselect',
                        'label'    => __('Product Categories', 'domain'),
                        'options'  => get_wc_product_categories(),
                        'multiple' => true,
                    ],
                ],
            ]
        ),
    ];
    return $cookies;
});
```

## Frontend Cookie API

### Core Methods

```javascript
// Set cookie with settings
$popup.popmake('setCookie', {
    name: 'custom_cookie',
    time: '30 days',
    session: false,
    path: true // sitewide
});

// Check if cookie exists
var hasUserSeen = $popup.popmake('checkCookies', {
    cookie_name: 'user_visited'
});

// Register cookie event for popup
$popup.popmake('addCookie', 'form_submission', {
    form: 'contact-form-7',
    only_in_popup: true,
    time: '1 week'
});
```

### Cookie Setting Mechanics

```javascript
// Via pm_cookie utility (globally available)
pm_cookie('cookie_name', true, '1 month', '/'); // Set
var value = pm_cookie('cookie_name'); // Get 
pm_remove_cookie('cookie_name'); // Remove

// Internal setCookie with pum_vars context
function setCookie(settings) {
    $.pm_cookie(
        settings.name,
        true,
        settings.session ? null : settings.time,
        settings.path ? pum_vars.home_url || '/' : null
    );
    pum.hooks.doAction('popmake.setCookie', settings);
}
```

### Hook Integration

```javascript
// Trigger form integration
PUM.hooks.addAction('pum.integration.form.success', function(form, args) {
    if (PUM.integrations.checkFormKeyMatches(settings.form, settings.formInstanceId, args)) {
        if ((settings.only_in_popup && args.popup.is($popup)) || !settings.only_in_popup) {
            $popup.popmake('setCookie', settings);
        }
    }
});
```

## New Cookie Implementation Pattern

### 1. PHP Registration

```php
<?php
class MyPlugin_Cookies {
    public static function init() {
        add_filter('pum_registered_cookies', [__CLASS__, 'register_cookies']);
    }

    public static function register_cookies($cookies) {
        return array_merge($cookies, [
            'custom_event' => [
                'name'            => __('Custom Event', 'domain'),
                'modal_title'     => __('Custom Event Settings', 'domain'),
                'settings_column' => '{{data.trigger_value}}: {{data.time}}',
                'fields'          => array_merge_recursive(
                    PUM_Cookies::instance()->cookie_fields(),
                    [
                        'general' => [
                            'trigger_value' => [
                                'type'  => 'text',
                                'label' => __('Trigger Value', 'domain'),
                                'std'   => '',
                            ],
                        ],
                    ]
                ),
            ],
        ]);
    }
}
MyPlugin_Cookies::init();
```

### 2. JS Event Handler

```javascript
(function($) {
    'use strict';

    $.extend($.fn.popmake.cookies, {
        custom_event: function(settings) {
            var $popup = PUM.getPopup(this);
            
            // Extended settings with defaults
            settings = $.extend({
                trigger_value: '',
                threshold: 1
            }, settings);

            // Custom event logic
            function checkCondition() {
                var currentValue = getCustomValue();
                if (currentValue >= settings.trigger_value) {
                    $popup.popmake('setCookie', settings);
                    $(document).off('customEvent', checkCondition);
                }
            }

            // Listen for custom events
            $(document).on('customEvent', checkCondition);
            
            // Also check immediately
            checkCondition();
        }
    });

}(jQuery));
```

### 3. Event Triggering

```javascript
// Trigger the cookie event
$(document).trigger('customEvent', {
    value: calculatedValue,
    popup_id: popup.id
});

// Or directly on popup
$popup.trigger('pumCustomEvent', eventData);
```

## Cookie Settings & Timing

### Time Formats
→ **Plain English**: "1 month", "30 days", "2 hours"  
→ **Parsed by**: `$.fn.popmake.utilities.strtotime()`  
→ **Session Mode**: `settings.session = true` → expires on browser close

### Path & Domain
→ **Sitewide**: `settings.path = true` → uses `pum_vars.home_url`  
→ **Page-specific**: `settings.path = false` → current page only  
→ **Domain**: Auto-set from `pum_vars.cookie_domain`

### Security & Storage
→ **Client-side only**: JS-readable, no HTTP-only flag  
→ **JSON encoding**: Complex values auto-serialized  
→ **Key generation**: Optional custom keys via `settings.key`

## Advanced Patterns

### Conditional Cookie Setting

```javascript
// Only set if specific conditions met
my_advanced_event: function(settings) {
    var $popup = PUM.getPopup(this);
    
    $popup.on('targetEvent', function(e, data) {
        // Validate conditions
        if (data.user_role === 'premium' && data.page_views > 5) {
            $popup.popmake('setCookie', settings);
        }
    });
}
```

### Cookie Combination Logic

```php
// Multiple cookie names for complex rules
'name' => 'seen_' . get_current_user_id() . '_popup_' . $popup_id,
'settings_column' => sprintf(
    '%s: %s | %s: %s',
    __('User', 'domain'), '{{data.user_id}}',
    __('Expires', 'domain'), '{{data.time}}'
),
```

### Integration with External Systems

```javascript
// WooCommerce purchase example
wc_purchase_complete: function(settings) {
    var $popup = PUM.getPopup(this);
    
    $(document.body).on('wc_checkout_success', function(e, order_data) {
        if (order_data.total >= settings.min_amount) {
            $popup.popmake('setCookie', $.extend(settings, {
                name: 'wc_purchased_' + order_data.order_id
            }));
        }
    });
}
```

## Testing & Debugging

### Debug Cookie State

```javascript
// Check all popup cookies
console.log('Popup cookies:', $popup.popmake('getSettings').cookies);

// Check specific cookie
console.log('Cookie exists:', $.pm_cookie('my_cookie'));

// List all cookies
console.log('All cookies:', document.cookie);
```

### Manual Cookie Management

```javascript
// Set test cookie
pm_cookie('test_cookie', 'test_value', '1 hour');

// Force cookie deletion  
pm_remove_cookie('unwanted_cookie');

// Clear all popup cookies (dev only)
Object.keys($.pm_cookie()).forEach(key => {
    if (key.startsWith('pum_')) pm_remove_cookie(key);
});
```

## File Locations & Registration

**Core Cookies:** `/wp-content/plugins/popup-maker/classes/Cookies.php:78`
**Extension Pattern:** `YourPlugin/classes/Cookies.php` or `Controllers/Cookies.php`

**JS Core:** `/popup-maker/assets/js/src/site/plugins/pum-cookies.js`
**JS Extensions:** `your-plugin/assets/js/src/site/plugins/pum-cookies.js`

## Real-World Examples

| Cookie Type | PHP Registration | JS Implementation | Key Features |
|---|---|---|---|
| **on_popup_open** | `popup-maker/classes/Cookies.php:85` | `pum-cookies.js:72` | After open event, frequency capping |
| **on_popup_close** | `popup-maker/classes/Cookies.php:82` | `pum-cookies.js:78` | Before close event, exit tracking |
| **form_submission** | `popup-maker/classes/Cookies.php:91` | `pum-cookies.js:90` | Form filtering, popup scoping |
| **manual** | `popup-maker/classes/Cookies.php:127` | `pum-cookies.js:128` | Shortcode trigger, programmatic |
| **age_verified** | `age-verification/.../pum-cookies.js:5` | Custom form events, verification states |
| **login_successful** | `ajax-login-modals/classes/Cookies.php:29` | `cookies.js:5` | Authentication tracking |
| **registration_successful** | `ajax-login-modals/classes/Cookies.php:33` | `cookies.js:12` | User onboarding flow |

## Integration Hooks & APIs

**Form Integration:** `PUM.integrations.checkFormKeyMatches(form, instanceId, args)`
**Cookie Setting:** `$popup.popmake('setCookie', settings)`
**Cookie Checking:** `$popup.popmake('checkCookies', {cookie_name: 'name'})`
**Manual Triggering:** `$popup.trigger('pumSetCookie')`

## Advanced Features

**Custom Fields:** Merge with `PUM_Cookies::cookie_fields()` for additional settings
**Conditional Logic:** Use settings validation in JS handlers  
**Form Integration:** Hook into `pum.integration.form.success` action
**Time Parsing:** Plain English via `strtotime()` compatibility
**Multi-cookie Names:** Dynamic naming with user/popup IDs

## Quick Implementation Checklist

- [ ] PHP: Add to `pum_registered_cookies` filter with proper fields
- [ ] JS: Extend `$.fn.popmake.cookies` with event handler
- [ ] Test: Verify cookie setting and timing works correctly
- [ ] Validate: Required fields, time parsing, sitewide options
- [ ] Document: Settings column template for admin display

## Best Practices

### 🎯 Event Naming
→ Use descriptive, action-based names  
→ Follow existing conventions (snake_case)  
→ Namespace extension events: `plugin_event_name`

### ⚡ Performance
→ Minimize DOM queries in handlers  
→ Use event delegation for dynamic content  
→ Remove event listeners when cookie set  
→ Cache expensive condition checks

### 🔒 Security
→ Validate all user inputs in JS  
→ Sanitize cookie names & values  
→ Never store sensitive data in cookies  
→ Use nonces for admin operations

### 🎨 UX
→ Provide clear field labels & descriptions  
→ Use smart defaults for common scenarios  
→ Group related settings logically  
→ Show cookie expiry in human-readable format

**Pro Tip:** Study existing cookies in `/classes/Cookies.php` and `/assets/js/src/site/plugins/pum-cookies.js` for patterns! 🚀
