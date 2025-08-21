---
name: "Create Popup Maker Cookie"
description: "Creates a new cookie type for controlling popup behavior and user experience"
version: "1.0.0"
author: "Popup Maker"
category: "popup-maker"
---

# 🍪 Create Popup Maker Cookie

This template creates a complete cookie system for Popup Maker to control when and how popups appear based on user interactions.

## 📋 Step 1: Gather Requirements

Let me help you create a new cookie type. I'll need some information:

**Cookie Details:**
- What should we call this cookie? (e.g. "After Video Watched")
- What's the unique ID? (e.g. "video_watched", auto-generated from name)
- What event should set this cookie? (popup close, form submission, custom action, etc.)
- How long should it last? (session, days, custom duration)
- What should prevent the popup from showing?

**Cookie Behavior:**
- Should it have custom settings/fields?
- Does it need JavaScript handling?
- Should it integrate with specific forms or events?

## 🎯 Step 2: Cookie Types & Events

### Standard Events
- **on_popup_close** - Set when popup is closed
- **on_popup_open** - Set when popup opens  
- **on_popup_conversion** - Set when user converts
- **form_submission** - Set when form is submitted

### Custom Events
- Video completion
- Time on page thresholds  
- User interactions
- External system events

## 💻 Code Generation

Based on your requirements, I'll generate complete cookie code:

### Basic Cookie Registration:

```php
/**
 * Register {{COOKIE_NAME}} cookie
 */
function {{FUNCTION_PREFIX}}_register_cookies( $cookies ) {
    $cookies['{{COOKIE_ID}}'] = [
        'name'   => __( '{{COOKIE_NAME}}', '{{TEXT_DOMAIN}}' ),
        'fields' => {{COOKIE_FIELDS}},
    ];

    return $cookies;
}
add_filter( 'pum_registered_cookies', '{{FUNCTION_PREFIX}}_register_cookies' );
```

### Advanced Cookie with Custom Fields:

```php
/**
 * Register {{COOKIE_NAME}} cookie with custom settings
 */
function {{FUNCTION_PREFIX}}_register_advanced_cookies( $cookies ) {
    $cookies['{{COOKIE_ID}}'] = [
        'name'   => __( '{{COOKIE_NAME}}', '{{TEXT_DOMAIN}}' ),
        'fields' => array_merge_recursive(
            // Standard cookie fields (time, session, etc.)
            PUM_Cookies::instance()->cookie_fields(),
            [
                'general' => [
                    {{CUSTOM_FIELDS}}
                ],
            ]
        ),
    ];

    return $cookies;
}
add_filter( 'pum_registered_cookies', '{{FUNCTION_PREFIX}}_register_advanced_cookies' );
```

## 🔧 Common Cookie Patterns

### Form Submission Cookie:
```php
$cookies['custom_form_submission'] = [
    'name'   => __( 'Custom Form Submission', '{{TEXT_DOMAIN}}' ),
    'fields' => array_merge_recursive(
        PUM_Cookies::instance()->cookie_fields(),
        [
            'general' => [
                'form_selector' => [
                    'type'        => 'text',
                    'label'       => __( 'Form Selector', '{{TEXT_DOMAIN}}' ),
                    'desc'        => __( 'CSS selector for the form', '{{TEXT_DOMAIN}}' ),
                    'placeholder' => '.my-form, #contact-form',
                ],
                'event_type' => [
                    'type'    => 'select',
                    'label'   => __( 'Trigger Event', '{{TEXT_DOMAIN}}' ),
                    'options' => [
                        'submit'  => __( 'Form Submit', '{{TEXT_DOMAIN}}' ),
                        'success' => __( 'Form Success', '{{TEXT_DOMAIN}}' ),
                        'error'   => __( 'Form Error', '{{TEXT_DOMAIN}}' ),
                    ],
                    'std' => 'submit',
                ],
            ],
        ]
    ),
];
```

### Time-Based Cookie:
```php
$cookies['time_on_page'] = [
    'name'   => __( 'Time on Page', '{{TEXT_DOMAIN}}' ),
    'fields' => array_merge_recursive(
        PUM_Cookies::instance()->cookie_fields(),
        [
            'general' => [
                'time_threshold' => [
                    'type'  => 'number',
                    'label' => __( 'Time Threshold (seconds)', '{{TEXT_DOMAIN}}' ),
                    'desc'  => __( 'Set cookie after user spends this much time on page', '{{TEXT_DOMAIN}}' ),
                    'min'   => 1,
                    'std'   => 30,
                ],
                'reset_on_new_page' => [
                    'type'  => 'checkbox',
                    'label' => __( 'Reset Timer on New Page', '{{TEXT_DOMAIN}}' ),
                    'desc'  => __( 'Start timing over when user navigates to different page', '{{TEXT_DOMAIN}}' ),
                ],
            ],
        ]
    ),
];
```

### Video Interaction Cookie:
```php
$cookies['video_watched'] = [
    'name'   => __( 'Video Watched', '{{TEXT_DOMAIN}}' ),
    'fields' => array_merge_recursive(
        PUM_Cookies::instance()->cookie_fields(),
        [
            'general' => [
                'video_selector' => [
                    'type'        => 'text',
                    'label'       => __( 'Video Selector', '{{TEXT_DOMAIN}}' ),
                    'placeholder' => 'video, .video-player, iframe[src*="youtube"]',
                ],
                'watch_percentage' => [
                    'type'  => 'rangeslider',
                    'label' => __( 'Watch Percentage', '{{TEXT_DOMAIN}}' ),
                    'desc'  => __( 'Percentage of video that must be watched', '{{TEXT_DOMAIN}}' ),
                    'min'   => 0,
                    'max'   => 100,
                    'step'  => 5,
                    'unit'  => '%',
                    'std'   => 50,
                ],
            ],
        ]
    ),
];
```

## 🎬 JavaScript Cookie Handling

I'll generate JavaScript to handle cookie setting:

### Basic Cookie Setting:
```javascript
(function($, PUM) {
    'use strict';

    /**
     * {{COOKIE_NAME}} cookie handler
     */
    function {{COOKIE_FUNCTION}}(settings) {
        var cookieName = 'pum-{{POPUP_ID}}-{{COOKIE_ID}}';
        var cookieTime = settings.cookie_time || 30; // Days
        
        // Set the cookie
        PUM.setCookie(cookieName, 'true', cookieTime);
        
        // Optional: Trigger custom event
        $(document).trigger('pum_{{COOKIE_ID}}_set', [settings]);
    }

    // Hook into specific events
    {{EVENT_HANDLERS}}

})(jQuery, window.PUM);
```

### Form Submission Handler:
```javascript
// Handle custom form submissions
$(document).on('{{EVENT_TYPE}}', '{{FORM_SELECTOR}}', function(e) {
    var $form = $(this);
    var popupId = $form.closest('.pum').data('popup-id');
    
    if (!popupId) return;
    
    // Set cookie for this popup
    var cookieName = 'pum-' + popupId + '-{{COOKIE_ID}}';
    PUM.setCookie(cookieName, 'true', {{COOKIE_TIME}});
    
    // Optional: Close popup after setting cookie
    $('#pum-' + popupId).popmake('close');
    
    // Trigger custom event for tracking
    $(document).trigger('pum_custom_form_cookie_set', {
        popup_id: popupId,
        form: $form,
        cookie_name: cookieName
    });
});
```

### Video Tracking Handler:
```javascript
// Video watch percentage tracking
function setupVideoTracking(settings) {
    var videoSelector = settings.video_selector;
    var watchPercentage = parseInt(settings.watch_percentage, 10) || 50;
    var popupId = settings.popup_id;
    
    $(videoSelector).each(function() {
        var $video = $(this);
        var video = this;
        var tracked = false;
        
        // Handle different video types
        if (video.tagName === 'VIDEO') {
            // HTML5 video
            $video.on('timeupdate', function() {
                if (tracked) return;
                
                var percent = (video.currentTime / video.duration) * 100;
                if (percent >= watchPercentage) {
                    tracked = true;
                    var cookieName = 'pum-' + popupId + '-{{COOKIE_ID}}';
                    PUM.setCookie(cookieName, 'true', settings.cookie_time);
                    
                    $(document).trigger('pum_video_watched', {
                        video: video,
                        percentage: percent,
                        popup_id: popupId
                    });
                }
            });
        } else if ($video.is('iframe[src*="youtube"]')) {
            // YouTube iframe (requires postMessage API)
            setupYouTubeTracking($video, watchPercentage, popupId, settings);
        }
    });
}
```

### Time-Based Handler:
```javascript
// Time on page tracking
function setupTimeTracking(settings) {
    var timeThreshold = parseInt(settings.time_threshold, 10) * 1000; // Convert to ms
    var resetOnNewPage = settings.reset_on_new_page;
    var popupId = settings.popup_id;
    var startTime = Date.now();
    
    function checkTime() {
        var timeSpent = Date.now() - startTime;
        
        if (timeSpent >= timeThreshold) {
            var cookieName = 'pum-' + popupId + '-{{COOKIE_ID}}';
            PUM.setCookie(cookieName, 'true', settings.cookie_time);
            
            $(document).trigger('pum_time_threshold_reached', {
                time_spent: timeSpent,
                popup_id: popupId
            });
            
            // Stop checking
            clearInterval(timeChecker);
        }
    }
    
    var timeChecker = setInterval(checkTime, 1000); // Check every second
    
    // Reset on page navigation if enabled
    if (resetOnNewPage) {
        $(window).on('beforeunload', function() {
            clearInterval(timeChecker);
        });
    }
}
```

## 🔗 Integration with Triggers

### Trigger Integration Example:
```javascript
// In your trigger JavaScript, check for cookie
if (PUM.getCookie('pum-' + popupId + '-{{COOKIE_ID}}')) {
    return; // Don't trigger if cookie exists
}

// Or use built-in cookie checking
if ($popup.popmake('checkCookies', settings)) {
    return; // Popup blocked by cookies
}
```

### Custom Cookie Checking:
```php
/**
 * Custom cookie check function
 */
function {{FUNCTION_PREFIX}}_check_custom_cookie( $popup_id, $cookie_id ) {
    $cookie_name = "pum-{$popup_id}-{$cookie_id}";
    return isset( $_COOKIE[ $cookie_name ] ) && $_COOKIE[ $cookie_name ] === 'true';
}

// Use in conditions or triggers
if ( {{FUNCTION_PREFIX}}_check_custom_cookie( $popup_id, '{{COOKIE_ID}}' ) ) {
    return false; // Don't show popup
}
```

## 📦 Asset Registration

I'll automatically enqueue the JavaScript:

```php
/**
 * Enqueue {{COOKIE_NAME}} cookie scripts
 */
function {{FUNCTION_PREFIX}}_enqueue_cookie_scripts() {
    pum_enqueue_script(
        '{{SCRIPT_HANDLE}}',
        '{{SCRIPT_URL}}',
        [ 'popup-maker-site' ],
        '{{VERSION}}',
        true,
        {{PRIORITY}} // Priority for asset cache
    );
}
add_action( 'pum_enqueue_scripts', '{{FUNCTION_PREFIX}}_enqueue_cookie_scripts' );
```

## 🧪 Testing Your Cookie

### Admin Testing:
1. Edit popup → Cookie tab
2. Select your new cookie
3. Configure settings
4. Test trigger behavior

### Frontend Testing:
```javascript
// Check if cookie exists
console.log('Cookie exists:', PUM.getCookie('pum-123-{{COOKIE_ID}}'));

// Set cookie manually for testing
PUM.setCookie('pum-123-{{COOKIE_ID}}', 'true', 30);

// Clear cookie for testing
PUM.removeCookie('pum-123-{{COOKIE_ID}}');

// Test popup with cookie
$('#pum-123').popmake('checkCookies', settings);
```

### PHP Testing:
```php
// Check cookie on server side
if ( isset( $_COOKIE['pum-123-{{COOKIE_ID}}'] ) ) {
    echo 'Cookie is set: ' . $_COOKIE['pum-123-{{COOKIE_ID}}'];
}
```

## ⚙️ Advanced Features

### Cookie Synchronization:
```javascript
// Sync cookies across tabs/windows
$(window).on('focus', function() {
    // Check for cookies set in other tabs
    var cookieValue = PUM.getCookie('pum-{{POPUP_ID}}-{{COOKIE_ID}}');
    if (cookieValue && !window.{{COOKIE_ID}}_synced) {
        window.{{COOKIE_ID}}_synced = true;
        // Handle cookie sync logic
    }
});
```

### Cookie Analytics:
```javascript
// Track cookie events for analytics
$(document).on('pum_{{COOKIE_ID}}_set', function(e, settings) {
    // Google Analytics
    if (typeof gtag !== 'undefined') {
        gtag('event', 'popup_cookie_set', {
            'cookie_type': '{{COOKIE_ID}}',
            'popup_id': settings.popup_id
        });
    }
    
    // Facebook Pixel
    if (typeof fbq !== 'undefined') {
        fbq('track', 'PopupCookieSet', {
            cookie_type: '{{COOKIE_ID}}',
            popup_id: settings.popup_id
        });
    }
});
```

## 📚 Standard Cookie Fields Reference

```php
// Time-based fields
'cookie_time' => [
    'type'  => 'number',
    'label' => __( 'Cookie Duration (days)', '{{TEXT_DOMAIN}}' ),
    'desc'  => __( 'How long to remember this action', '{{TEXT_DOMAIN}}' ),
    'min'   => 1,
    'std'   => 30,
],

'session_cookie' => [
    'type'  => 'checkbox',
    'label' => __( 'Session Only', '{{TEXT_DOMAIN}}' ),
    'desc'  => __( 'Cookie expires when browser closes', '{{TEXT_DOMAIN}}' ),
],

// Path and domain
'cookie_path' => [
    'type'        => 'text',
    'label'       => __( 'Cookie Path', '{{TEXT_DOMAIN}}' ),
    'desc'        => __( 'Path where cookie is valid (leave empty for entire site)', '{{TEXT_DOMAIN}}' ),
    'placeholder' => '/',
],

'cookie_domain' => [
    'type'        => 'text',
    'label'       => __( 'Cookie Domain', '{{TEXT_DOMAIN}}' ),
    'desc'        => __( 'Domain for cookie (leave empty for current domain)', '{{TEXT_DOMAIN}}' ),
    'placeholder' => '.example.com',
],
```

## ✅ Best Practices

✅ **Do:**
- Use descriptive cookie names
- Set reasonable expiration times
- Respect user privacy preferences
- Provide clear cookie descriptions
- Test cookie behavior thoroughly
- Use secure cookies when possible
- Handle cookie failures gracefully

❌ **Don't:**
- Store sensitive information in cookies
- Set overly long expiration times
- Ignore user privacy settings
- Create too many cookies per popup
- Use cookies for critical functionality only

Ready to create your cookie? Share the details and I'll generate the complete implementation! 🍪✨