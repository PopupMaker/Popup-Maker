---
name: "Create Popup Maker Condition"
description: "Creates a new condition for controlling popup display with PHP or JavaScript evaluation"
version: "1.0.0"
author: "Popup Maker"
category: "popup-maker"
---

# 🎯 Create Popup Maker Condition

This template creates a complete condition for Popup Maker with support for both PHP-based and JavaScript-based evaluation.

## 📋 Step 1: Gather Requirements

I'll help you create a new condition. Let me ask a few questions to understand what you need:

**Condition Details:**
- What should we call this condition? (e.g. "User has specific role")
- What's the unique ID? (e.g. "user_role", auto-generated from name)
- Which group should it belong to? (User, Content, WordPress, WooCommerce, etc.)
- Should this be PHP-based or JavaScript-based evaluation?
- What's the purpose/description?

## 🔧 Step 2: Implementation Strategy

### PHP-Based Conditions
- Server-side evaluation during popup load
- Can access WordPress functions, user data, post data
- Better for security-sensitive conditions
- Evaluated once per page load

### JavaScript-Based Conditions  
- Client-side evaluation (advanced conditions)
- Can access DOM, user interactions, real-time data
- Good for dynamic conditions that change after page load
- Set `'advanced' => true` and no PHP callback

## 💻 Code Generation

Based on your requirements, I'll generate:

### For PHP Conditions:
```php
/**
 * Register {{CONDITION_NAME}} condition
 */
function {{FUNCTION_PREFIX}}_register_conditions( $conditions ) {
    $conditions['{{CONDITION_ID}}'] = [
        'group'    => __( '{{GROUP}}', '{{TEXT_DOMAIN}}' ),
        'name'     => __( '{{CONDITION_NAME}}', '{{TEXT_DOMAIN}}' ),
        'priority' => {{PRIORITY}},
        'callback' => '{{CALLBACK_FUNCTION}}',
        'fields'   => [
            {{FIELDS_CONFIG}}
        ],
    ];
    
    return $conditions;
}
add_filter( 'pum_registered_conditions', '{{FUNCTION_PREFIX}}_register_conditions' );

/**
 * {{CONDITION_NAME}} condition callback
 *
 * @param array $settings Condition settings
 * @return bool True if condition is met
 */
function {{CALLBACK_FUNCTION}}( $settings ) {
    // TODO: Implement your condition logic here
    // Example: Check user role
    if ( ! is_user_logged_in() ) {
        return false;
    }
    
    $user = wp_get_current_user();
    $required_role = $settings['role'] ?? '';
    
    return in_array( $required_role, $user->roles );
}
```

### For JavaScript Conditions:
```php
/**
 * Register {{CONDITION_NAME}} JavaScript condition
 */
function {{FUNCTION_PREFIX}}_register_js_conditions( $conditions ) {
    $conditions['{{CONDITION_ID}}'] = [
        'group'    => __( '{{GROUP}}', '{{TEXT_DOMAIN}}' ),
        'name'     => __( '{{CONDITION_NAME}}', '{{TEXT_DOMAIN}}' ),
        'advanced' => true, // Marks as JS-evaluated
        'fields'   => [
            {{FIELDS_CONFIG}}
        ],
    ];
    
    return $conditions;
}
add_filter( 'pum_registered_conditions', '{{FUNCTION_PREFIX}}_register_js_conditions' );
```

### JavaScript Implementation:
```javascript
// Global function for JavaScript condition evaluation
window.{{JS_FUNCTION_NAME}} = function( settings ) {
    // TODO: Implement your JavaScript condition logic
    // Example: Check viewport width
    var minWidth = parseInt( settings.min_width, 10 ) || 0;
    return window.innerWidth >= minWidth;
};
```

## 📂 File Placement

I'll place the PHP code in the appropriate location:
- **Extension/Plugin**: `includes/integrations/class-{{PLUGIN_SLUG}}-integration.php`
- **Theme Functions**: `functions.php`
- **Custom Plugin**: Your plugin's main file or conditions directory

JavaScript code will be enqueued via:
```php
add_action( 'pum_enqueue_scripts', function() {
    pum_enqueue_script(
        '{{SCRIPT_HANDLE}}',
        '{{SCRIPT_URL}}',
        [ 'popup-maker-site' ],
        '{{VERSION}}'
    );
});
```

## 🏗️ Common Field Types

Here are field configurations for common use cases:

```php
// Text input
'text_field' => [
    'type'        => 'text',
    'label'       => __( 'Text Value', '{{TEXT_DOMAIN}}' ),
    'desc'        => __( 'Enter the text to match', '{{TEXT_DOMAIN}}' ),
    'placeholder' => __( 'Enter text...', '{{TEXT_DOMAIN}}' ),
    'required'    => true,
],

// Select dropdown
'select_field' => [
    'type'    => 'select',
    'label'   => __( 'Choose Option', '{{TEXT_DOMAIN}}' ),
    'options' => [
        'option1' => __( 'Option 1', '{{TEXT_DOMAIN}}' ),
        'option2' => __( 'Option 2', '{{TEXT_DOMAIN}}' ),
    ],
    'std'     => 'option1',
],

// Number input
'number_field' => [
    'type'  => 'number',
    'label' => __( 'Number Value', '{{TEXT_DOMAIN}}' ),
    'min'   => 0,
    'max'   => 100,
    'step'  => 1,
    'std'   => 10,
],

// Checkbox
'checkbox_field' => [
    'type'  => 'checkbox',
    'label' => __( 'Enable Feature', '{{TEXT_DOMAIN}}' ),
    'desc'  => __( 'Check to enable this feature', '{{TEXT_DOMAIN}}' ),
],

// Post selector
'post_selector' => [
    'type'      => 'postselect',
    'label'     => __( 'Select Posts', '{{TEXT_DOMAIN}}' ),
    'post_type' => 'post',
    'multiple'  => true,
],
```

## ✅ Testing Your Condition

1. **Admin Test**: Go to popup editor → Conditions tab
2. **Verify**: Your condition appears in the {{GROUP}} group
3. **Configure**: Set up the condition with test values
4. **Frontend Test**: Visit pages where condition should/shouldn't trigger
5. **Debug**: Use browser console or error logs to troubleshoot

### Debug Helper:
```php
// Add to your callback for debugging
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    error_log( 'Condition {{CONDITION_ID}}: ' . ( $result ? 'TRUE' : 'FALSE' ) );
}
```

## 📖 Best Practices

✅ **Do:**
- Use descriptive condition names
- Validate and sanitize field inputs
- Handle edge cases gracefully
- Return boolean values consistently
- Add proper PHPDoc comments
- Use WordPress coding standards

❌ **Don't:**
- Perform expensive operations (database queries)
- Rely on external services (they might be down)
- Use global variables unnecessarily
- Skip input validation

## 🔍 Advanced Examples (From Pro Extensions)

### Advanced User Conditions (Pro Pattern):
```php
// Multiple field condition with advanced options
$conditions['user_has_role'] = [
    'group'    => __( 'User', 'popup-maker-pro' ),
    'name'     => __( 'Has Role', 'popup-maker-pro' ),
    'fields'   => [
        'selected' => [
            'placeholder' => __( 'Select User Roles', 'popup-maker-pro' ),
            'type'        => 'select',
            'select2'     => true,
            'multiple'    => true,
            'as_array'    => true,
            'options'     => wp_roles()->role_names,
        ],
    ],
    'callback' => [ 'YourClass', 'user_has_role' ],
];

// Range-based conditions (Pro pattern)
$conditions['popup_views'] = [
    'advanced' => true,
    'group'    => __( 'User', 'your-plugin' ),
    'name'     => __( 'Has Viewed Popup', 'your-plugin' ),
    'fields'   => [
        'selected' => [
            'type'      => 'postselect',
            'post_type' => 'popup',
            'multiple'  => false,
            'required'  => true,
        ],
        'morethan' => \PopupMaker\get_morethan_field( [
            'unit' => 'views',
        ] ),
        'lessthan' => \PopupMaker\get_lessthan_field( [
            'unit' => 'views',
        ] ),
    ],
];
```

### URL/Referrer Conditions (Pro Pattern):
```php
$conditions['url_regex'] = [
    'advanced' => true,
    'group'    => __( 'URL', 'your-plugin' ),
    'name'     => __( 'URL Regex Search', 'your-plugin' ),
    'fields'   => [
        'search' => [
            'label' => __( 'Valid RegExp', 'your-plugin' ),
            'type'  => 'text',
        ],
    ],
];

$conditions['referrer_is_search_engine'] = [
    'advanced' => true,
    'group'    => __( 'Referrer', 'your-plugin' ),
    'name'     => __( 'Referrer Is Search Engine', 'your-plugin' ),
    'fields'   => [
        'search' => [
            'placeholder' => __( 'Select Search Engines', 'your-plugin' ),
            'type'        => 'select',
            'select2'     => true,
            'multiple'    => true,
            'as_array'    => true,
            'options'     => [
                'www.google.com'   => __( 'Google', 'your-plugin' ),
                'www.bing.com'     => __( 'Bing', 'your-plugin' ),
                'search.yahoo.com' => __( 'Yahoo', 'your-plugin' ),
            ],
        ],
    ],
];
```

### JavaScript Advanced Conditions (Pro Pattern):
```php
// Custom JS function condition
$conditions['js_function'] = [
    'advanced' => true,
    'group'    => __( 'Custom', 'your-plugin' ),
    'name'     => __( 'Custom JS Function', 'your-plugin' ),
    'fields'   => [
        'function_name' => [
            'label'       => __( 'Custom Function Name', 'your-plugin' ),
            'placeholder' => __( 'callback_function', 'your-plugin' ),
            'type'        => 'text',
        ],
    ],
];

// HTML element conditions
$conditions['html_element_on_screen'] = [
    'advanced' => true,
    'group'    => __( 'HTML', 'your-plugin' ),
    'name'     => __( 'HTML Element On Screen', 'your-plugin' ),
    'fields'   => [
        'selector' => [
            'label' => __( 'Element Selector (jQuery/CSS)', 'your-plugin' ),
            'type'  => 'text',
        ],
        'entirely' => [
            'label' => __( 'Only if element is completely visible on screen', 'your-plugin' ),
            'type'  => 'checkbox',
        ],
    ],
];
```

### Device/Browser Conditions (Pro Pattern):
```php
$conditions['browser_width'] = [
    'advanced' => true,
    'group'    => __( 'Browser', 'your-plugin' ),
    'name'     => __( 'Browser Width', 'your-plugin' ),
    'fields'   => [
        'morethan' => \PopupMaker\get_morethan_field( [
            'unit' => 'px',
        ] ),
        'lessthan' => \PopupMaker\get_lessthan_field( [
            'unit' => 'px',
        ] ),
    ],
];

$conditions['device_is_brand'] = [
    'advanced' => true,
    'group'    => __( 'Device', 'your-plugin' ),
    'name'     => __( 'Device Is Brand', 'your-plugin' ),
    'fields'   => [
        'selected' => [
            'placeholder' => __( 'Select Brands', 'your-plugin' ),
            'type'        => 'select',
            'select2'     => true,
            'multiple'    => true,
            'as_array'    => true,
            'options'     => [
                'iPhone'     => 'iPhone',
                'BlackBerry' => 'BlackBerry',
                'Samsung'    => 'Samsung',
                // etc...
            ],
        ],
    ],
];
```

### Cookie Conditions (Pro Pattern):
```php
$conditions['cookie_exists'] = [
    'advanced' => true,
    'group'    => __( 'Cookie', 'your-plugin' ),
    'name'     => __( 'Cookie Exists', 'your-plugin' ),
    'fields'   => [
        'cookie_name' => [
            'label'       => __( 'Cookie Name', 'your-plugin' ),
            'placeholder' => __( 'my-cookie', 'your-plugin' ),
            'type'        => 'text',
        ],
    ],
];
```

Ready to implement your condition? Let me know the details and I'll generate the complete code! 🎉