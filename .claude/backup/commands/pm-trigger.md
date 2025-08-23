---
name: "Create Popup Maker Trigger"
description: "Creates a new trigger mechanism for popup activation with full frontend/backend integration"
version: "1.0.0"
author: "Popup Maker"
category: "popup-maker"
---

# ⚡ Create Popup Maker Trigger

This template creates a complete trigger system for Popup Maker with backend registration and frontend JavaScript handler.

## 📋 Step 1: Gather Requirements

Let me help you create a new trigger. I'll need some details:

**Trigger Details:**
- What should we call this trigger? (e.g. "Scroll Percentage")
- What's the unique ID? (e.g. "scroll_percentage", auto-generated from name)
- What type of trigger is this? (time-based, user-action, scroll, exit-intent, form-based, custom)
- Modal title for settings? (e.g. "Scroll Trigger Settings")
- Description for settings column? (e.g. "Scroll to {{data.percentage}}%")

**Trigger Behavior:**
- What should activate this trigger?
- What settings does it need? (delays, thresholds, selectors, etc.)
- Should it work on mobile?
- Does it need cookie integration?

## 🎯 Step 2: Trigger Types & Patterns

### Time-Based Triggers
- Auto-open after delay
- Inactivity detection
- Time on page thresholds

### User Action Triggers
- Click, hover, focus events
- Scroll behaviors
- Form interactions

### Exit Intent Triggers
- Mouse movement detection
- Browser events (beforeunload)
- Mobile exit detection

### Content-Based Triggers
- Element visibility
- Content consumption
- Reading progress

## 💻 Code Generation

Based on your requirements, I'll generate complete backend and frontend code:

### Backend Registration (PHP):

```php
/**
 * Register {{TRIGGER_NAME}} trigger
 */
function {{FUNCTION_PREFIX}}_register_triggers( $triggers ) {
    $triggers['{{TRIGGER_ID}}'] = [
        'name'            => __( '{{TRIGGER_NAME}}', '{{TEXT_DOMAIN}}' ),
        'modal_title'     => __( '{{MODAL_TITLE}}', '{{TEXT_DOMAIN}}' ),
        'settings_column' => sprintf(
            '<strong>%1$s</strong>: %2$s',
            __( '{{SETTING_LABEL}}', '{{TEXT_DOMAIN}}' ),
            '{{data.{{SETTING_KEY}}}}'
        ),
        'fields' => [
            'general' => [
                {{GENERAL_FIELDS}}
            ],
            'cookie' => [
                // Cookie settings are added automatically
            ],
            'advanced' => [
                {{ADVANCED_FIELDS}}
            ],
        ],
    ];

    return $triggers;
}
add_filter( 'pum_registered_triggers', '{{FUNCTION_PREFIX}}_register_triggers' );
```

### Frontend JavaScript Handler:

```javascript
(function($, PUM) {
    'use strict';

    // Method 1: Extend triggers object
    $.extend($.fn.popmake.triggers, {
        {{TRIGGER_ID}}: function(settings) {
            var $popup = PUM.getPopup(this);
            
            // Default settings
            settings = $.extend({
                {{DEFAULT_SETTINGS}}
            }, settings);

            {{TRIGGER_LOGIC}}
        }
    });

    // Method 2: Using PUM hooks (alternative approach)
    PUM.hooks.addAction('popmake.initialize', function() {
        {{ALTERNATIVE_INITIALIZATION}}
    });

})(jQuery, window.PUM);
```

## 🛠️ Common Trigger Patterns

### Time Delay Trigger:
```php
'fields' => [
    'general' => [
        'delay' => [
            'type'    => 'rangeslider',
            'label'   => __( 'Delay (seconds)', '{{TEXT_DOMAIN}}' ),
            'desc'    => __( 'Delay before popup opens', '{{TEXT_DOMAIN}}' ),
            'min'     => 0,
            'max'     => 60,
            'step'    => 0.5,
            'unit'    => __( 'sec', '{{TEXT_DOMAIN}}' ),
            'std'     => 5,
        ],
    ],
],
```

```javascript
{{TRIGGER_ID}}: function(settings) {
    var $popup = PUM.getPopup(this);
    var delay = parseFloat(settings.delay) * 1000; // Convert to milliseconds
    
    setTimeout(function() {
        // Check if popup can be opened
        if (!$popup.popmake('state', 'isOpen') && 
            !$popup.popmake('checkCookies', settings) && 
            $popup.popmake('checkConditions')) {
            $popup.popmake('open');
        }
    }, delay);
}
```

### Scroll Percentage Trigger:
```php
'fields' => [
    'general' => [
        'percentage' => [
            'type'  => 'rangeslider',
            'label' => __( 'Scroll Percentage', '{{TEXT_DOMAIN}}' ),
            'desc'  => __( 'Popup opens when user scrolls this percentage', '{{TEXT_DOMAIN}}' ),
            'min'   => 0,
            'max'   => 100,
            'step'  => 5,
            'unit'  => '%',
            'std'   => 50,
        ],
        'direction' => [
            'type'    => 'select',
            'label'   => __( 'Scroll Direction', '{{TEXT_DOMAIN}}' ),
            'options' => [
                'down' => __( 'Scroll Down', '{{TEXT_DOMAIN}}' ),
                'up'   => __( 'Scroll Up', '{{TEXT_DOMAIN}}' ),
                'both' => __( 'Both Directions', '{{TEXT_DOMAIN}}' ),
            ],
            'std' => 'down',
        ],
    ],
],
```

```javascript
scroll_percentage: function(settings) {
    var $popup = PUM.getPopup(this);
    var percentage = parseInt(settings.percentage, 10);
    var direction = settings.direction || 'down';
    var triggered = false;
    
    function checkScroll() {
        if (triggered) return;
        
        var scrollPercent = Math.round(
            ($(window).scrollTop() / ($(document).height() - $(window).height())) * 100
        );
        
        var shouldTrigger = false;
        if (direction === 'down' && scrollPercent >= percentage) {
            shouldTrigger = true;
        } else if (direction === 'up' && scrollPercent <= percentage) {
            shouldTrigger = true;
        } else if (direction === 'both' && 
                  (scrollPercent >= percentage || scrollPercent <= (100 - percentage))) {
            shouldTrigger = true;
        }
        
        if (shouldTrigger) {
            triggered = true;
            $(window).off('scroll', checkScroll);
            
            if (!$popup.popmake('checkCookies', settings) && 
                $popup.popmake('checkConditions')) {
                $popup.popmake('open');
            }
        }
    }
    
    $(window).on('scroll', checkScroll);
}
```

### Exit Intent Trigger:
```php
'fields' => [
    'general' => [
        'sensitivity' => [
            'type'  => 'rangeslider',
            'label' => __( 'Mouse Sensitivity', '{{TEXT_DOMAIN}}' ),
            'desc'  => __( 'How close to screen edge triggers exit intent', '{{TEXT_DOMAIN}}' ),
            'min'   => 1,
            'max'   => 50,
            'step'  => 1,
            'unit'  => 'px',
            'std'   => 10,
        ],
        'mobile_enabled' => [
            'type'  => 'checkbox',
            'label' => __( 'Enable on Mobile', '{{TEXT_DOMAIN}}' ),
            'desc'  => __( 'Trigger on mobile devices (uses scroll up detection)', '{{TEXT_DOMAIN}}' ),
        ],
    ],
],
```

```javascript
exit_intent: function(settings) {
    var $popup = PUM.getPopup(this);
    var sensitivity = parseInt(settings.sensitivity, 10) || 10;
    var mobileEnabled = settings.mobile_enabled;
    var triggered = false;
    
    // Desktop exit intent
    function handleMouseMove(e) {
        if (triggered) return;
        
        if (e.clientY <= sensitivity) {
            triggered = true;
            $(document).off('mousemove', handleMouseMove);
            
            if (!$popup.popmake('checkCookies', settings) && 
                $popup.popmake('checkConditions')) {
                $popup.popmake('open');
            }
        }
    }
    
    // Mobile exit intent (scroll up detection)
    function handleMobileExit() {
        if (triggered || !mobileEnabled) return;
        
        var scrollTop = $(window).scrollTop();
        if (scrollTop < 50) { // User scrolled to top quickly
            triggered = true;
            $(window).off('scroll', handleMobileExit);
            
            if (!$popup.popmake('checkCookies', settings) && 
                $popup.popmake('checkConditions')) {
                $popup.popmake('open');
            }
        }
    }
    
    // Check if mobile
    var isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    
    if (isMobile && mobileEnabled) {
        $(window).on('scroll', handleMobileExit);
    } else {
        $(document).on('mousemove', handleMouseMove);
    }
}
```

## 📦 Asset Registration

I'll automatically enqueue the JavaScript file:

```php
/**
 * Enqueue {{TRIGGER_NAME}} trigger scripts
 */
function {{FUNCTION_PREFIX}}_enqueue_trigger_scripts() {
    pum_enqueue_script(
        '{{SCRIPT_HANDLE}}',
        '{{SCRIPT_URL}}',
        [ 'popup-maker-site' ],
        '{{VERSION}}',
        true,
        {{PRIORITY}} // Priority for asset cache (5-10 for extensions)
    );
}
add_action( 'pum_enqueue_scripts', '{{FUNCTION_PREFIX}}_enqueue_trigger_scripts' );
```

## 📂 File Structure

I'll create the following files:

```
{{PLUGIN_DIR}}/
├── includes/
│   └── triggers/
│       └── class-{{TRIGGER_SLUG}}-trigger.php
├── assets/
│   └── js/
│       └── triggers/
│           └── {{TRIGGER_ID}}.js
└── {{MAIN_PLUGIN_FILE}}.php (registration hooks)
```

## 🧪 Testing Your Trigger

### Admin Testing:
1. Go to popup editor → Triggers tab
2. Select your new trigger from dropdown
3. Configure settings and save
4. Preview popup behavior

### Frontend Testing:
```javascript
// Debug helper - add to your trigger JavaScript
if (typeof console !== 'undefined') {
    console.log('{{TRIGGER_NAME}} trigger initialized with settings:', settings);
}

// Test trigger manually in console
PUM.getPopup(123).popmake('open'); // Replace 123 with popup ID
```

### PHP Debug:
```php
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    add_action( 'wp_footer', function() {
        echo '<script>console.log("{{TRIGGER_NAME}} trigger registered");</script>';
    } );
}
```

## ⚙️ Advanced Features

### Cookie Integration:
```javascript
// In your trigger, always check cookies
if ($popup.popmake('checkCookies', settings)) {
    return; // Don't trigger if cookies prevent it
}

// Set custom cookie when triggered
PUM.setCookie('{{TRIGGER_ID}}_triggered', 'true', settings.cookie_time);
```

### Mobile Optimization:
```javascript
// Check mobile and adjust behavior
var isMobile = window.innerWidth <= 768;
var isTouchDevice = 'ontouchstart' in window;

if (isMobile) {
    // Mobile-specific trigger logic
} else {
    // Desktop trigger logic
}
```

### Performance Considerations:
```javascript
// Debounce scroll/resize events
var debounceTimer;
function debouncedHandler() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(actualHandler, 100);
}

// Use requestAnimationFrame for smooth animations
function smoothHandler() {
    requestAnimationFrame(function() {
        // Your trigger logic
    });
}
```

## 📚 Field Reference

Common field types for trigger settings:

```php
// Delay/timing fields
'delay' => [
    'type' => 'rangeslider',
    'min'  => 0,
    'max'  => 60,
    'step' => 0.5,
    'unit' => 'sec',
],

// Element selector
'selector' => [
    'type'        => 'text',
    'label'       => __( 'CSS Selector', '{{TEXT_DOMAIN}}' ),
    'placeholder' => '.my-element, #my-id',
    'desc'        => __( 'CSS selector for target element', '{{TEXT_DOMAIN}}' ),
],

// Event type
'event_type' => [
    'type'    => 'select',
    'options' => [
        'click'     => 'Click',
        'hover'     => 'Hover',
        'focus'     => 'Focus',
        'mouseover' => 'Mouse Over',
    ],
],

// Mobile settings
'mobile_behavior' => [
    'type'    => 'select',
    'label'   => __( 'Mobile Behavior', '{{TEXT_DOMAIN}}' ),
    'options' => [
        'same'     => __( 'Same as Desktop', '{{TEXT_DOMAIN}}' ),
        'disabled' => __( 'Disabled on Mobile', '{{TEXT_DOMAIN}}' ),
        'custom'   => __( 'Custom Mobile Logic', '{{TEXT_DOMAIN}}' ),
    ],
],
```

## ✅ Best Practices

✅ **Do:**
- Always check cookies before triggering
- Use debouncing for scroll/resize events
- Test on mobile devices
- Provide clear setting descriptions
- Handle edge cases gracefully
- Use proper event cleanup
- Follow WordPress coding standards

❌ **Don't:**
- Trigger multiple times without checking
- Ignore mobile users
- Create memory leaks with events
- Skip cookie integration
- Use blocking operations
- Forget to validate settings

## 🔍 Advanced Pro Extension Patterns

### Exit Intent Trigger (Pro Pattern):
```php
$triggers['exit_intent'] = [
    'name'        => __( 'Exit Intent', 'popup-maker-pro' ),
    'modal_title' => sprintf( __( '%s Settings', 'popup-maker-pro' ), __( 'Exit Intent', 'popup-maker-pro' ) ),
    'fields'      => [
        'general'  => [
            'methods' => [
                'label'   => __( 'Methods', 'popup-maker-pro' ),
                'type'    => 'multicheck',
                'options' => [
                    'mouseleave'   => __( 'Mouse Leave', 'popup-maker-pro' ),
                    'lostfocus'    => __( 'Lost Browser Focus', 'popup-maker-pro' ),
                    'backbutton'   => __( 'Back Button', 'popup-maker-pro' ),
                    'linkclick'    => __( 'Link Click', 'popup-maker-pro' ),
                    'mobiletime'   => __( 'Time Delay (Mobile)', 'popup-maker-pro' ),
                    'mobilescroll' => __( 'Mobile Scroll', 'popup-maker-pro' ),
                ],
                'std'     => [ 'mouseleave', 'lostfocus' ],
            ],
        ],
        'advanced' => [
            'top_sensitivity' => [
                'label'        => __( 'Top Sensitivity', 'popup-maker-pro' ),
                'type'         => 'rangeslider',
                'std'          => 10,
                'min'          => 1,
                'max'          => 50,
                'unit'         => __( 'px', 'popup-maker' ),
                'dependencies' => [
                    'methods' => 'mouseleave',
                ],
            ],
        ],
    ],
];
```

### Advanced Scroll Trigger JavaScript:
```javascript
// Pro pattern - complex scroll calculations
function get_actual_scroll_distance() {
    return $(document).innerHeight() - window.innerHeight;
}

function get_current_scroll_percentage() {
    return window.pageYOffset / get_actual_scroll_distance();
}

$.fn.popmake.triggers.scroll = function (settings) {
    settings = $.extend({
        trigger_type: 'distance',
        distance: '75%',
        element_point: 'e_top-s_bottom',
        close_on_up: false
    }, settings);
    
    switch (settings.trigger_type) {
    case 'distance':
        var distanceUnit = settings.distance.replace(/[0-9]/g, '');
        var distance = settings.distance.replace(distanceUnit, '');
        
        switch (distanceUnit) {
        case "px":
            trigger_distance = distance;
            break;
        case "%":
            trigger_distance = get_actual_scroll_distance() * (distance / 100);
            break;
        }
        break;
    }
};
```

### Click Blocking Trigger Pattern:
```javascript
// From Terms & Conditions - advanced click interception
$.fn.popmake.triggers.click_block = function (triggerSettings) {
    var $popup = PUM.getPopup(this),
        triggerSelector = PUM.getClickTriggerSelector(this, triggerSettings);
    
    $(document)
        .off('click.pumTrigger', triggerSelector)
        .on('click.pumBlockAction', triggerSelector, function (event) {
            var $trigger = $(this),
                allowed = true;
            
            // Apply custom validation filters
            for (var key in triggerSettings.requirements) {
                if (!pum.hooks.applyFilters('pum.trigger.click_block.allowed.' + key, true, triggerSettings, $popup)) {
                    allowed = false;
                }
            }
            
            if (!allowed) {
                event.stopPropagation();
                event.preventDefault();
                $.fn.popmake.blocked_trigger = $trigger;
                $popup.popmake('open');
            }
        });
};

// Handle user flow continuation after popup closes
$(document).on('pumAfterClose', '.pum', function () {
    if ($.fn.popmake.blocked_trigger && $.fn.popmake.blocked_trigger.data('reclick')) {
        $.fn.popmake.blocked_trigger.data('reclick', false);
        $.fn.popmake.blocked_trigger.get(0).click();
    }
    $.fn.popmake.blocked_trigger = null;
});
```

### WooCommerce Integration Trigger:
```php
// Advanced third-party integration pattern
class WooCommerceTriggers extends Controller {
    
    public function init() {
        // Pass WC data to frontend
        add_filter( 'popup_maker_ecommerce_popups/frontend_localized_vars', [ $this, 'frontend_localized_vars' ] );
        
        // Hook into WC events
        add_filter( 'wc_add_to_cart_message_html', [ $this, 'hack_wc_add_to_cart_message_html' ], 10, 2 );
    }
    
    public function frontend_localized_vars( $vars ) {
        if ( function_exists( 'WC' ) && WC()->cart ) {
            $vars['woocommerce_cart_contents_count'] = WC()->cart->get_cart_contents_count();
        }
        $vars['woocommerce_products_added_to_cart'] = null;
        return $vars;
    }
    
    public function hack_wc_add_to_cart_message_html( $message, $products ) {
        // Inject product data for JavaScript triggers
        add_filter( 'popup_maker_ecommerce_popups/frontend_localized_vars', function ( $vars ) use ( $products ) {
            foreach ( $products as $product_id => $quantity ) {
                $vars['woocommerce_products_added_to_cart'][ $product_id ] = $quantity;
            }
            return $vars;
        } );
        return $message;
    }
}
```

### Age Verification Popup Behavior Modification:
```javascript
// Shows how to modify popup behavior during trigger
$.fn.popmake.triggers.age_verification = function (settings) {
    var $popup = PUM.getPopup(this);
    
    // Standard trigger checks
    if ($popup.popmake('state', 'isOpen') || 
        $popup.popmake('checkCookies', settings) || 
        !$popup.popmake('checkConditions')) {
        return;
    }
    
    // Modify popup behavior before opening
    $popup
        .on('pumBeforeOpen.age_verification', function () {
            // Hide close button for age verification
            $popup.find('.pum-content + .pum-close')
                .hide()
                .off('click.popmake click.pum');
        })
        .popmake('open', function () {
            $popup.off('pumBeforeOpen.age_verification');
        });
};
```

Ready to create your trigger? Share the details and I'll generate the complete implementation! 🚀