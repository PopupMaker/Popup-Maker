# Popup Maker Trigger Implementation Guide

**Ultra-compressed reference for agents implementing custom popup triggers** 🎯

## Core Pattern

**PHP Registration** → **JS Implementation** → **Settings Integration**

```php
// 1. PHP: Hook into trigger registration filter
add_filter('pum_registered_triggers', function($triggers) {
    $triggers['my_trigger'] = [...]; // Config array
    return $triggers;
});
```

```js
// 2. JS: Add trigger function via filter
PUM.hooks.addFilter('popupMaker.triggers', (triggers) => ({
    ...triggers,
    my_trigger: function(settings) { /* logic */ }
}));
```

## PHP Registration Schema

```php
$triggers['trigger_id'] = [
    'name'            => __('Display Name', 'textdomain'),
    'modal_title'     => __('Trigger Settings', 'textdomain'), // Optional
    'settings_column' => sprintf('<strong>%1$s</strong>: %2$s', 
        __('Label', 'textdomain'), '{{data.field_name}}'), // Optional
    'fields' => [
        'general' => [/* field config */],
        'advanced' => [/* advanced fields */],
        'cookie' => [/* auto-added */]
    ]
];
```

### Field Types & Configuration

**Complete field reference:** @.claude/templates/pm/primers/field-types.md

**Quick field examples:**
- `'type' => 'rangeslider'` + `min|max|step|unit` for sliders
- `'type' => 'select'` + `'options' => []` for dropdowns  
- `'type' => 'postselect'` + `'post_type' => 'product'` for WP posts
- `'dependencies' => ['field' => 'value']` for conditional display

## JavaScript Implementation Patterns

### Basic Structure
```js
PUM.hooks.addFilter('popupMaker.triggers', (triggers) => ({
    ...triggers,
    trigger_name: function(settings) {
        const $popup = PUM.getPopup(this);
        
        // Standard checks pattern
        const maybeOpen = (label) => {
            if ($popup.popmake('state', 'isOpen') || 
                $popup.popmake('checkCookies', settings) || 
                !$popup.popmake('checkConditions')) {
                return false;
            }
            
            PUM.setLastOpenTrigger(label);
            $popup.popmake('open');
            return true;
        };
        
        // Your trigger logic here
    }
}));
```

### Common Trigger Mechanisms

**Time-based:**
```js
setTimeout(() => maybeOpen('Delay Trigger'), settings.delay);
```

**Event-based:**
```js
$(document).on('custom_event', () => maybeOpen('Custom Event'));
```

**Scroll-based:**
```js
$(window).on('scroll', () => {
    if ($(window).scrollTop() > settings.distance) {
        maybeOpen('Scroll Trigger');
    }
});
```

**Mouse-based:**
```js
$(document).on('mouseleave', (e) => {
    if (e.clientY <= settings.sensitivity) {
        maybeOpen('Exit Intent');
    }
});
```

**Form Integration:**
```js
PUM.hooks.addAction('pum.integration.form.success', (form, args) => {
    if (PUM.integrations.checkFormKeyMatches(settings.form, settings.formInstanceId, args)) {
        maybeOpen('Form Submission');
    }
});
```

## Modern TypeScript Pattern (Pro/Extensions)

```ts
import type { PopupMakerTriggers } from '../types';
const { hooks: { addFilter }, getPopup, setLastOpenTrigger } = window.PUM;

interface MyTriggerSettings {
    threshold: number;
    enabled_methods: string[];
}

addFilter('popupMaker.triggers', (triggers: PopupMakerTriggers) => ({
    ...triggers,
    my_trigger: function(triggerSettings: MyTriggerSettings) {
        const settings = { threshold: 50, ...triggerSettings };
        const $popup = getPopup(this as unknown as JQuery<HTMLElement>);
        
        // Implementation logic
    }
}));
```

## File Locations & Registration

**Core Triggers:** `/wp-content/plugins/popup-maker/classes/Triggers.php:167`
**Pro Triggers:** `/wp-content/plugins/popup-maker-pro/classes/Controllers/Popups/Triggers.php:24`
**Extension Pattern:** `YourPlugin/classes/Triggers.php` or `Controllers/Popups/Triggers.php`

**JS Core:** `/popup-maker/assets/js/src/site/plugins/pum-triggers.js`
**JS Pro:** `/popup-maker-pro/packages/frontend/src/triggers/`
**JS Extension:** `your-plugin/assets/js/src/site/plugins/pum-triggers.js`

## Real-World Examples

| Trigger Type | PHP Registration | JS Implementation | Key Features |
|---|---|---|---|
| **click_open** | `popup-maker/classes/Triggers.php:170` | `pum-triggers.js:66` | Selector-based, do_default option |
| **auto_open** | `popup-maker/classes/Triggers.php:196` | `pum-triggers.js:40` | Delay, cookie/condition checks |
| **exit_intent** | `popup-maker-pro/.../Triggers.php:37` | `exit-intent.ts:48` | Multi-method, device detection |
| **scroll** | `popup-maker-pro/.../Triggers.php:242` | `scroll.ts` | Distance/element, close_on_up |
| **form_submission** | `popup-maker/classes/Triggers.php:215` | `pum-triggers.js:153` | Integration system |
| **product_added_to_cart** | `ecommerce-popups/.../Triggers.php:34` | `triggers.ts:32` | Product filtering, AJAX hooks |
| **age_verification** | `age-verification/.../Triggers.php` | `pum-triggers.js:4` | Auto-open + close blocking |

## Integration Hooks & APIs

**Form Integration:** `PUM.integrations.formSubmission($form, data)`
**Ecommerce Events:** `PUM.hooks.doAction('popupMaker.ecommerce.itemAddedToCart', data)`
**Condition System:** `$popup.popmake('checkConditions')`
**Cookie System:** `$popup.popmake('checkCookies', settings)`

## Advanced Features

**Dependencies:** Field visibility based on other field values
**Localized Data:** PHP → JS via `wp_localize_script()` for dynamic options
**Mobile Detection:** Device-specific trigger methods
**History API:** Back button detection patterns
**External Integration:** Product selectors, form plugin hooks

## Quick Implementation Checklist

- [ ] PHP: Add to `pum_registered_triggers` filter with proper fields
- [ ] JS: Register via `popupMaker.triggers` filter with standard checks
- [ ] Test: Verify cookie/condition integration works
- [ ] Validate: Required fields, dependencies, mobile compatibility
- [ ] Document: Add doclink for complex configurations

**Pro Tip:** Study existing triggers in `/classes/Triggers.php` and `/assets/js/src/site/plugins/pum-triggers.js` for patterns! 🚀
