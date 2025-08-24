# Popup Maker Condition Implementation Guide

**Ultra-compressed reference for agents implementing custom popup conditions** 🎯

## Core Pattern

**PHP Registration** → **Callback Logic** → **JS Implementation** (if advanced)

```php
// 1. PHP: Hook into condition registration filter
add_filter('pum_registered_conditions', function($conditions) {
    $conditions['my_condition'] = [...]; // Config array
    return $conditions;
});
```

```php
// 2. PHP Callback: Server-side evaluation
function my_condition_callback($condition = []) {
    // Evaluate condition logic
    return true|false;
}
```

```js
// 3. JS: Advanced conditions (optional)
window.my_condition = function(settings) {
    // Client-side evaluation
    return true|false;
};
```

## PHP Registration Schema

```php
$conditions['condition_id'] = [
    'name'     => __('Display Name', 'textdomain'),
    'group'    => __('Group Name', 'textdomain'),
    'callback' => 'callback_function', // PHP function or [Class, 'method']
    'fields'   => [
        'field_name' => [/* field config */],
        // Multiple field configurations
    ],
    'advanced' => false, // true = JS evaluation only
    'priority' => 10,    // Optional: sorting priority
];
```

### Field Types & Configuration

**Complete field reference:** @.claude/templates/pm/primers/field-types.md

**Quick field examples:**
- `'type' => 'select'` + `'options' => []` for dropdowns
- `'type' => 'postselect'` + `'post_type' => 'product'` for WP posts  
- `'type' => 'taxonomyselect'` + `'taxonomy' => 'category'` for terms
- `'type' => 'text'` for string input
- `'dependencies' => ['field' => 'value']` for conditional display

**Helper functions available:**
- `\PopupMaker\get_morethan_field()` - numeric comparison field
- `\PopupMaker\get_lessthan_field()` - numeric comparison field
- Custom helpers in extensions for consistent field patterns

## Condition Types & Evaluation

### PHP-Evaluated Conditions (Server-Side)

**When to use:** Static conditions that don't change after page load
- User roles, post types, taxonomies
- Server data (purchase history, user meta)
- WordPress conditionals (`is_home()`, `is_single()`, etc.)

```php
$conditions['user_has_role'] = [
    'group'    => __('User', 'textdomain'),
    'name'     => __('User Has Role', 'textdomain'),
    'fields'   => [
        'selected' => [
            'type'     => 'select',
            'options'  => wp_roles()->role_names,
            'multiple' => true,
            'as_array' => true,
        ],
    ],
    'callback' => function($condition) {
        $user_roles = wp_get_current_user()->roles;
        $required_roles = $condition['settings']['selected'] ?? [];
        return !empty(array_intersect($user_roles, $required_roles));
    },
];
```

### JS-Evaluated Conditions (Client-Side)

**When to use:** Dynamic conditions that require browser/user interaction
- Browser dimensions, device detection
- URL parameters, cookies, local storage  
- DOM elements, scroll position, user behavior
- Real-time data that changes after page load

```php
$conditions['browser_width'] = [
    'advanced' => true, // Marks for JS evaluation
    'group'    => __('Browser', 'textdomain'),
    'name'     => __('Browser Width', 'textdomain'),
    'fields'   => [
        'morethan' => \PopupMaker\get_morethan_field(['unit' => 'px']),
        'lessthan' => \PopupMaker\get_lessthan_field(['unit' => 'px']),
    ],
    // No PHP callback - handled in JavaScript
];
```

```js
// Global function for advanced condition
window.browser_width = function(settings) {
    const width = window.innerWidth;
    const morethan = parseInt(settings.morethan) || 0;
    const lessthan = parseInt(settings.lessthan) || 9999;
    
    return width > morethan && width < lessthan;
};
```

### Hybrid Conditions (Both PHP + JS)

**When to use:** Conditions that need server data + client logic
- Scheduled popups (server schedule + client time)
- User permissions + browser capabilities
- Product data + cart state

```php
$conditions['scheduled'] = [
    'group'    => __('Scheduling', 'textdomain'),
    'name'     => __('Is Scheduled', 'textdomain'),
    'fields'   => [
        'schedules' => [/* complex schedule fields */],
    ],
    'callback' => function($condition) {
        // PHP: Basic server-side validation
        return !empty($condition['settings']['schedules']);
    },
];
```

```js
// JS: Detailed client-side evaluation
window.scheduled = function(settings) {
    // Complex scheduling logic with timezone handling
    return evaluateSchedules(settings.schedules);
};
```

## Callback Implementation Patterns

### Basic PHP Callback

```php
function my_condition_callback($condition = []) {
    // Extract settings safely
    $settings = $condition['settings'] ?? [];
    $selected = $settings['selected'] ?? [];
    
    // Validate inputs
    if (empty($selected)) {
        return false;
    }
    
    // Implement logic
    return your_logic_here($selected);
}
```

### Class-Based Callback

```php
class MyConditionCallbacks {
    public static function post_type($condition = []) {
        global $post;
        
        $target = explode('_', $condition['target']);
        $modifier = array_pop($target);
        $post_type = implode('_', $target);
        $selected = $condition['settings']['selected'] ?? [];
        
        switch ($modifier) {
            case 'all':
                return is_singular($post_type);
            case 'selected':
                return is_singular($post_type) && in_array($post->ID, wp_parse_id_list($selected));
            default:
                return false;
        }
    }
}
```

### More/Less Than Pattern

```php
function numeric_comparison_callback($condition = []) {
    $settings = $condition['settings'] ?? [];
    $morethan = $settings['morethan'] ?? false;
    $lessthan = $settings['lessthan'] ?? false;
    
    $current_value = get_your_numeric_value();
    
    // Handle more than comparison
    if ($morethan !== false && $current_value <= intval($morethan)) {
        return false;
    }
    
    // Handle less than comparison  
    if ($lessthan !== false && $current_value >= intval($lessthan)) {
        return false;
    }
    
    return true;
}
```

## TypeScript/Modern JS Patterns

### Advanced Condition Interface

```ts
import type { PopupConditionCallback } from '@popup-maker/types';

interface MyConditionSettings {
    threshold: number;
    enabled_methods: string[];
    require_all?: boolean;
}

export const my_condition: PopupConditionCallback<MyConditionSettings> = ({
    threshold = 50,
    enabled_methods = [],
    require_all = false
}) => {
    // Type-safe implementation
    const current_value = getCurrentValue();
    
    return require_all 
        ? enabled_methods.every(method => checkMethod(method, threshold))
        : enabled_methods.some(method => checkMethod(method, threshold));
};

// Register the condition
window.my_condition = my_condition;
```

### Data Store Integration

```ts
import { getPopupOpenCounts, getPagesViewed } from '@popup-maker/data';

export const popup_views: PopupConditionCallback<{
    selected: string | number;
    morethan: string | false;
    lessthan: string | false;
}> = ({ selected, morethan = false, lessthan = false }) => {
    const popupID = parseInt(selected.toString());
    const popup_open_counts = getPopupOpenCounts();
    const view_count = popup_open_counts[popupID] ?? 0;
    
    if (!popupID) return false;
    
    return testMoreLessThan(view_count, morethan, lessthan, false);
};
```

## File Locations & Registration

### Core Conditions
**PHP Registration:** `/wp-content/plugins/popup-maker/classes/Conditions.php:486`  
**PHP Callbacks:** `/wp-content/plugins/popup-maker/classes/ConditionCallbacks.php`

### Pro Conditions  
**PHP Registration:** `/wp-content/plugins/popup-maker-pro/classes/Controllers/Popups/Conditions.php:53`  
**PHP Callbacks:** `/wp-content/plugins/popup-maker-pro/classes/Helpers/ConditionCallbacks.php`  
**JS Implementation:** `/popup-maker-pro/packages/frontend/src/conditions/advanced-targeting/callbacks.ts`

### Ecommerce Conditions
**WooCommerce:** `/wp-content/plugins/popup-maker-ecommerce-popups/classes/Controllers/Integration/WooCommerce/Conditions.php:64`  
**EDD:** `/wp-content/plugins/popup-maker-ecommerce-popups/classes/Controllers/Integration/EasyDigitalDownloads/Conditions.php`

### Extension Pattern
**PHP Registration:** `YourPlugin/classes/Conditions.php` or `Controllers/Popups/Conditions.php`  
**PHP Callbacks:** `YourPlugin/classes/ConditionCallbacks.php`  
**JS Implementation:** `your-plugin/assets/js/src/site/conditions/`

## Real-World Examples

| Condition Type | PHP Location | JS Location | Key Features |
|---|---|---|---|
| **is_front_page** | `popup-maker/classes/Conditions.php:489` | N/A | WordPress function, no fields |
| **user_has_role** | `popup-maker-pro/.../Conditions.php:61` | N/A | Multi-select, user data |
| **popup_views** | `popup-maker-pro/.../Conditions.php:86` | `advanced-targeting/callbacks.ts:22` | Advanced, analytics data |
| **browser_width** | `popup-maker-pro/.../Conditions.php:357` | `advanced-targeting/callbacks.ts:450+` | Advanced, client-side only |
| **product_in_cart** | `ecommerce-popups/.../Conditions.php:159` | N/A | Server data, multi-select |
| **cart_item_count** | `ecommerce-popups/.../Conditions.php:169` | JS callback needed | Advanced, real-time data |
| **scheduled** | `popup-maker-pro/.../Conditions.php:custom` | `conditions/scheduling/conditions.ts:133` | Hybrid, complex logic |

## Common Integration Patterns

### WordPress Integration
```php
// Post meta conditions
$conditions['post_has_meta'] = [
    'group'    => __('Post', 'textdomain'),
    'name'     => __('Post Has Meta Key', 'textdomain'),
    'fields'   => [
        'meta_key' => ['type' => 'text', 'label' => __('Meta Key')],
        'meta_value' => ['type' => 'text', 'label' => __('Meta Value (optional)')],
    ],
    'callback' => function($condition) {
        global $post;
        $meta_key = $condition['settings']['meta_key'] ?? '';
        $meta_value = $condition['settings']['meta_value'] ?? '';
        
        if (!$post || !$meta_key) return false;
        
        $current_value = get_post_meta($post->ID, $meta_key, true);
        
        return $meta_value ? ($current_value === $meta_value) : !empty($current_value);
    },
];
```

### E-commerce Integration
```php
// Purchase history conditions
$conditions['has_purchased_category'] = [
    'group'    => __('Purchase History', 'textdomain'),
    'name'     => __('Has Purchased from Category', 'textdomain'),
    'fields'   => [
        'selected' => [
            'type' => 'taxonomyselect',
            'taxonomy' => 'product_cat',
            'multiple' => true,
        ],
    ],
    'callback' => function($condition) {
        $user_id = get_current_user_id();
        if (!$user_id) return false;
        
        $categories = $condition['settings']['selected'] ?? [];
        return has_user_purchased_from_categories($user_id, $categories);
    },
];
```

### Cookie/Storage Integration
```js
// Client-side storage condition
window.has_visited_before = function(settings) {
    const visits = localStorage.getItem('site_visits') || '0';
    const visit_count = parseInt(visits);
    const required = parseInt(settings.minimum_visits) || 1;
    
    return visit_count >= required;
};
```

## Advanced Features

### Condition Groups & Sorting
```php
// Custom condition group ordering
add_filter('pum_condition_sort_order', function($order) {
    $order[__('My Custom Group', 'textdomain')] = 25;
    return $order;
});
```

### Dynamic Field Options
```php
$conditions['dynamic_select'] = [
    'fields' => [
        'selected' => [
            'type' => 'select',
            'options' => get_dynamic_options_callback(),
            'multiple' => true,
        ],
    ],
];

function get_dynamic_options_callback() {
    // Return array of options based on current context
    return get_posts(['post_type' => 'custom_type', 'numberposts' => -1]);
}
```

### Field Dependencies
```php
'fields' => [
    'condition_type' => [
        'type' => 'select',
        'options' => ['equals' => 'Equals', 'contains' => 'Contains'],
        'std' => 'equals',
    ],
    'search_value' => [
        'type' => 'text',
        'dependencies' => [
            'condition_type' => ['equals', 'contains'], // Show only if these values
        ],
    ],
    'regex_pattern' => [
        'type' => 'text',
        'dependencies' => [
            'condition_type' => 'regex', // Show only if regex selected
        ],
    ],
],
```

## Defensive Programming Patterns

### Input Validation
```php
function safe_condition_callback($condition = []) {
    // Validate condition structure
    if (!is_array($condition) || empty($condition['settings'])) {
        return false;
    }
    
    $settings = $condition['settings'];
    
    // Sanitize inputs
    $selected = array_map('sanitize_text_field', (array)($settings['selected'] ?? []));
    $numeric_value = absint($settings['numeric_value'] ?? 0);
    
    // Validate required fields
    if (empty($selected)) {
        return false;
    }
    
    // Your logic here...
}
```

### Error Handling
```js
window.safe_js_condition = function(settings) {
    try {
        // Your condition logic
        const result = evaluateCondition(settings);
        return Boolean(result);
    } catch (error) {
        console.warn('Condition evaluation error:', error);
        return false; // Fail safely
    }
};
```

### Third-Party Integration Safety
```php
// Safe plugin integration
function woocommerce_condition_callback($condition) {
    // Check if WooCommerce is active
    if (!function_exists('is_woocommerce')) {
        return false;
    }
    
    // Check if required WC functions exist
    if (!function_exists('WC') || !WC()->cart) {
        return false;
    }
    
    // Your WooCommerce logic...
}
```

## Testing & Debugging

### PHP Condition Testing
```php
// Test condition in isolation
function test_my_condition() {
    $condition = [
        'target' => 'my_condition',
        'settings' => [
            'selected' => ['value1', 'value2'],
            'morethan' => '5',
        ],
    ];
    
    $result = my_condition_callback($condition);
    error_log('Condition result: ' . ($result ? 'true' : 'false'));
}
```

### JS Condition Testing
```js
// Test condition in browser console
console.log('Testing condition:', window.my_condition({
    threshold: 100,
    enabled_methods: ['method1', 'method2']
}));
```

### Condition Debugging Hook
```php
add_action('pum_popup_conditions_check', function($popup_id, $conditions, $results) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log(sprintf(
            'Popup %d conditions: %s',
            $popup_id,
            json_encode(array_combine(array_column($conditions, 'target'), $results))
        ));
    }
}, 10, 3);
```

## Quick Implementation Checklist

- [ ] **Define condition type:** PHP-only, JS-only, or hybrid
- [ ] **Register via `pum_registered_conditions` filter** with proper fields
- [ ] **Implement callback:** PHP function/method or JS window function
- [ ] **Test basic functionality:** Ensure true/false logic works correctly
- [ ] **Validate inputs:** Sanitize user inputs, handle edge cases
- [ ] **Add field dependencies:** Show/hide fields based on other selections
- [ ] **Test integration:** Verify with other conditions and popup settings
- [ ] **Document usage:** Add contextual help or documentation links

## Performance Considerations

- **PHP conditions** evaluated once per page load → faster
- **JS conditions** evaluated each time popup checks → use caching
- **Complex queries** should be cached or optimized
- **Third-party integrations** should fail gracefully if plugins inactive
- **Large datasets** should use pagination or AJAX loading

**Pro Tip:** Study existing conditions in core/pro/extensions for established patterns. Server-side for static data, client-side for dynamic behavior! 🚀

## Field Helper Reference

### Core Helpers (Available in all contexts)

```php
// Located in: popup-maker/includes/namespaced/condition-helpers.php

\PopupMaker\get_morethan_field($args = [])
// Returns: Numeric "More Than (optional)" field
// Args: ['label' => 'Custom Label', 'unit' => 'px', etc.]

\PopupMaker\get_lessthan_field($args = [])
// Returns: Numeric "Less Than (optional)" field  
// Args: ['label' => 'Custom Label', 'unit' => 'px', etc.]

\PopupMaker\get_selected_field($post_type, $args = [])
// Returns: Multi-select postselect field for given post type
// Args: ['placeholder' => 'Custom placeholder', 'multiple' => false, etc.]

\PopupMaker\get_require_all_field($args = [])
// Returns: Checkbox field for "Require all" vs "any" logic
// Label: "Require all"
```

### Extension Helpers (Check namespace availability)

```php
// EcommercePopups namespace helpers
use function PopupMaker\EcommercePopups\ConditionCallbacks\Helpers\{
    get_morethan_field,
    get_lessthan_field,
    get_selected_field,
    get_require_all_field
};

// Similar functions with ecommerce-specific text domains
get_selected_field('product') // WooCommerce/EDD product selector
```

### Validation & Testing Helpers

```php
// Core testing functions

\PopupMaker\test_more_less_than($value, $morethan, $lessthan, $default_value = false)
// Tests numeric ranges: value > $morethan && value < $lessthan
// Returns: bool - true if within range

\PopupMaker\test_list_matches($items, $selected, $require_all)  
// Tests if selected items exist in a list
// $items: Available items array
// $selected: Required items array  
// $require_all: bool - all must match vs any match
// Returns: bool

\PopupMaker\test_items_match($items, $check_fn, $require_all = false)
// Advanced: Tests items against callback function
// $items: Array to test
// $check_fn: Callable that returns bool for each item
// $require_all: bool - all must pass vs any pass
// Returns: bool

// Examples of test_items_match usage:
test_items_match(
    $product_ids,
    function($product_id) use ($customer) {
        return $customer->has_purchased($product_id);
    },
    $require_all
);
```

### Helper Usage Patterns

```php
// Standard condition with range validation
$conditions['user_login_count'] = [
    'fields' => [
        'morethan' => \PopupMaker\get_morethan_field(['unit' => 'logins']),
        'lessthan' => \PopupMaker\get_lessthan_field(['unit' => 'logins']),
    ],
    'callback' => function($condition) {
        $settings = $condition['settings'] ?? [];
        $login_count = get_user_login_count(get_current_user_id());
        
        return \PopupMaker\test_more_less_than(
            $login_count,
            $settings['morethan'] ?? false,
            $settings['lessthan'] ?? false
        );
    },
];

// Multi-select with require all logic
$conditions['user_has_products'] = [
    'fields' => [
        'selected' => \PopupMaker\get_selected_field('product'),
        'require_all' => \PopupMaker\get_require_all_field(),
    ],
    'callback' => function($condition) {
        $settings = $condition['settings'] ?? [];
        $selected = $settings['selected'] ?? [];
        $require_all = !empty($settings['require_all']);
        
        $user_products = get_user_purchased_products(get_current_user_id());
        
        return \PopupMaker\test_list_matches($user_products, $selected, $require_all);
    },
];

// Advanced callback-based testing
$conditions['user_course_progress'] = [
    'fields' => [
        'selected' => \PopupMaker\get_selected_field('course'),
        'require_all' => \PopupMaker\get_require_all_field(),
        'min_progress' => ['type' => 'number', 'label' => 'Minimum Progress %'],
    ],
    'callback' => function($condition) {
        $settings = $condition['settings'] ?? [];
        $courses = $settings['selected'] ?? [];
        $require_all = !empty($settings['require_all']);
        $min_progress = intval($settings['min_progress'] ?? 80);
        $user_id = get_current_user_id();
        
        return \PopupMaker\test_items_match(
            $courses,
            function($course_id) use ($user_id, $min_progress) {
                $progress = get_user_course_progress($user_id, $course_id);
                return $progress >= $min_progress;
            },
            $require_all
        );
    },
];
```

## Condition Priority System

```php
'priority' => 1,  // Higher priority = evaluated first
'priority' => 10, // Default priority
'priority' => 20, // Lower priority = evaluated later
```

Use priority to ensure dependent conditions are evaluated in correct order.
