# Popup Maker Integration Orchestrator

Creates a comprehensive third-party plugin integration with hooks, filters, and coordinated functionality across multiple components.

## Overview

This command orchestrates the creation of a complete integration with third-party plugins, handling:
- Plugin detection and compatibility
- Integration class architecture
- Hooks and filters coordination
- Asset management
- Configuration and settings
- Documentation and examples

## Pre-Requirements Analysis

**Plugin Detection**
```php
// Check if target plugin is active
if (!class_exists('{{PLUGIN_CLASS}}') && !function_exists('{{PLUGIN_FUNCTION}}')) {
    return; // Integration not needed
}
```

**Dependency Mapping**
- Target plugin version requirements
- Popup Maker version compatibility  
- Required PHP version and extensions
- Asset dependencies (CSS/JS)

## Integration Architecture

### 1. Main Integration Class

**File**: `includes/integrations/class-pum-{{plugin-slug}}-integration.php`

```php
<?php
/**
 * {{PLUGIN_NAME}} Integration
 *
 * @package PopupMaker
 * @subpackage Integrations/{{PLUGIN_NAME}}
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class PUM_{{PLUGIN_CLASS}}_Integration
 */
class PUM_{{PLUGIN_CLASS}}_Integration {

    /**
     * Initialize the integration
     */
    public static function init() {
        // Plugin detection
        if (!self::is_plugin_active()) {
            return;
        }

        // Hook into WordPress
        add_action('init', [__CLASS__, 'setup_hooks']);
        add_action('admin_init', [__CLASS__, 'setup_admin_hooks']);
        
        // Integration-specific initialization
        self::setup_{{plugin_slug}}_hooks();
    }

    /**
     * Check if the target plugin is active
     */
    public static function is_plugin_active() {
        return class_exists('{{PLUGIN_CLASS}}') || function_exists('{{PLUGIN_FUNCTION}}');
    }

    /**
     * Setup general hooks
     */
    public static function setup_hooks() {
        // Conditions
        add_filter('pum_condition_types', [__CLASS__, 'register_conditions']);
        
        // Triggers  
        add_filter('pum_trigger_types', [__CLASS__, 'register_triggers']);
        
        // Cookies
        add_filter('pum_cookie_types', [__CLASS__, 'register_cookies']);
        
        // CTAs
        add_filter('pum_cta_types', [__CLASS__, 'register_ctas']);
        
        // Assets
        add_action('pum_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
        add_action('pum_enqueue_styles', [__CLASS__, 'enqueue_styles']);
    }

    /**
     * Setup admin-specific hooks
     */
    public static function setup_admin_hooks() {
        // Settings integration
        add_filter('pum_settings_tabs', [__CLASS__, 'add_settings_tab']);
        add_filter('pum_settings_fields', [__CLASS__, 'add_settings_fields']);
        
        // Admin assets
        add_action('admin_enqueue_scripts', [__CLASS__, 'admin_enqueue_scripts']);
    }

    /**
     * Setup plugin-specific hooks
     */
    public static function setup_{{plugin_slug}}_hooks() {
        // Hook into target plugin's actions
        add_action('{{plugin_hook_prefix}}_action', [__CLASS__, 'handle_{{plugin_slug}}_action']);
        add_filter('{{plugin_hook_prefix}}_filter', [__CLASS__, 'modify_{{plugin_slug}}_data']);
        
        // Revenue tracking (if applicable)
        if (method_exists('PUM_Tracking', 'track_conversion')) {
            add_action('{{plugin_hook_prefix}}_purchase', [__CLASS__, 'track_conversion']);
        }
    }

    /**
     * Register integration-specific conditions
     */
    public static function register_conditions($conditions) {
        $conditions['{{plugin_slug}}_condition'] = [
            'class' => 'PUM_Condition_{{PLUGIN_CLASS}}_{{CONDITION_TYPE}}',
            'file'  => 'conditions/{{plugin-slug}}-{{condition-type}}.php',
        ];
        
        return $conditions;
    }

    /**
     * Register integration-specific triggers
     */
    public static function register_triggers($triggers) {
        $triggers['{{plugin_slug}}_trigger'] = [
            'class' => 'PUM_Trigger_{{PLUGIN_CLASS}}_{{TRIGGER_TYPE}}',
            'file'  => 'triggers/{{plugin-slug}}-{{trigger-type}}.php',
        ];
        
        return $triggers;
    }

    /**
     * Register integration-specific cookies
     */
    public static function register_cookies($cookies) {
        $cookies['{{plugin_slug}}_cookie'] = [
            'class' => 'PUM_Cookie_{{PLUGIN_CLASS}}_{{COOKIE_TYPE}}',
            'file'  => 'cookies/{{plugin-slug}}-{{cookie-type}}.php',
        ];
        
        return $cookies;
    }

    /**
     * Register integration-specific CTAs
     */
    public static function register_ctas($ctas) {
        $ctas['{{plugin_slug}}_cta'] = [
            'class' => 'PUM_CTA_{{PLUGIN_CLASS}}_{{CTA_TYPE}}',
            'file'  => 'ctas/{{plugin-slug}}-{{cta-type}}.php',
        ];
        
        return $ctas;
    }

    /**
     * Enqueue frontend scripts
     */
    public static function enqueue_scripts() {
        wp_enqueue_script(
            'pum-{{plugin-slug}}-integration',
            PUM_URL . '/assets/js/integrations/{{plugin-slug}}.min.js',
            ['popup-maker-site'],
            PUM_VER,
            true
        );
        
        // Localize script with integration data
        wp_localize_script('pum-{{plugin-slug}}-integration', 'pum_{{plugin_slug}}_vars', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('pum_{{plugin_slug}}_nonce'),
            'settings' => self::get_integration_settings(),
        ]);
    }

    /**
     * Enqueue frontend styles
     */
    public static function enqueue_styles() {
        wp_enqueue_style(
            'pum-{{plugin-slug}}-integration',
            PUM_URL . '/assets/css/integrations/{{plugin-slug}}.min.css',
            ['popup-maker'],
            PUM_VER
        );
    }

    /**
     * Enqueue admin scripts
     */
    public static function admin_enqueue_scripts($hook) {
        if (!in_array($hook, ['post.php', 'post_new.php', 'popup-maker_page_pum-settings'])) {
            return;
        }

        wp_enqueue_script(
            'pum-{{plugin-slug}}-admin',
            PUM_URL . '/assets/js/admin/integrations/{{plugin-slug}}.min.js',
            ['pum-admin-general'],
            PUM_VER,
            true
        );
    }

    /**
     * Add settings tab
     */
    public static function add_settings_tab($tabs) {
        $tabs['{{plugin_slug}}'] = [
            'id'       => '{{plugin_slug}}',
            'label'    => '{{PLUGIN_NAME}}',
            'icon'     => 'dashicons-{{plugin-icon}}',
            'priority' => 50,
        ];
        
        return $tabs;
    }

    /**
     * Add settings fields
     */
    public static function add_settings_fields($fields) {
        $fields['{{plugin_slug}}'] = [
            'enable_integration' => [
                'type'    => 'checkbox',
                'label'   => __('Enable {{PLUGIN_NAME}} Integration', 'popup-maker'),
                'desc'    => __('Enable popup integration with {{PLUGIN_NAME}}.', 'popup-maker'),
                'std'     => false,
            ],
            'tracking_enabled' => [
                'type'    => 'checkbox', 
                'label'   => __('Enable Conversion Tracking', 'popup-maker'),
                'desc'    => __('Track conversions from {{PLUGIN_NAME}} actions.', 'popup-maker'),
                'std'     => true,
            ],
            'advanced_targeting' => [
                'type'    => 'checkbox',
                'label'   => __('Advanced Targeting', 'popup-maker'),
                'desc'    => __('Use {{PLUGIN_NAME}} data for advanced popup targeting.', 'popup-maker'),
                'std'     => false,
            ],
        ];
        
        return $fields;
    }

    /**
     * Get integration settings
     */
    public static function get_integration_settings() {
        return [
            'enabled'            => pum_get_option('{{plugin_slug}}_enable_integration', false),
            'tracking_enabled'   => pum_get_option('{{plugin_slug}}_tracking_enabled', true),
            'advanced_targeting' => pum_get_option('{{plugin_slug}}_advanced_targeting', false),
        ];
    }

    /**
     * Handle plugin-specific action
     */
    public static function handle_{{plugin_slug}}_action($data) {
        // Integration logic here
        do_action('pum_{{plugin_slug}}_action_handled', $data);
    }

    /**
     * Modify plugin data for popup integration
     */
    public static function modify_{{plugin_slug}}_data($data) {
        // Data modification logic
        return apply_filters('pum_{{plugin_slug}}_data_modified', $data);
    }

    /**
     * Track conversion from plugin action
     */
    public static function track_conversion($order_data) {
        if (!class_exists('PUM_Tracking')) {
            return;
        }

        PUM_Tracking::track_conversion([
            'source'      => '{{plugin_slug}}',
            'value'       => $order_data['total'] ?? 0,
            'currency'    => $order_data['currency'] ?? 'USD',
            'popup_id'    => $order_data['popup_id'] ?? 0,
            'user_id'     => get_current_user_id(),
            'session_id'  => PUM_Utils_Cookies::get('pum_session_key'),
            'meta'        => [
                '{{plugin_slug}}_order_id' => $order_data['order_id'] ?? '',
                '{{plugin_slug}}_product'  => $order_data['product'] ?? '',
            ],
        ]);
    }
}

// Initialize the integration
PUM_{{PLUGIN_CLASS}}_Integration::init();
```

### 2. Component Files Structure

**Conditions**: `includes/conditions/{{plugin-slug}}-*.php`
**Triggers**: `includes/triggers/{{plugin-slug}}-*.php` 
**Cookies**: `includes/cookies/{{plugin-slug}}-*.php`
**CTAs**: `includes/ctas/{{plugin-slug}}-*.php`

### 3. Asset Files

**Frontend JS**: `assets/js/integrations/{{plugin-slug}}.js`
```javascript
(function($) {
    'use strict';

    // Integration namespace
    window.PUM_{{PLUGIN_UPPER}}_Integration = {
        
        init: function() {
            this.bindEvents();
            this.setupTracking();
        },

        bindEvents: function() {
            // Bind to plugin events
            $(document).on('{{plugin_event}}', this.handlePluginEvent);
            
            // Bind to Popup Maker events  
            $(document).on('pumAfterOpen', this.handlePopupOpen);
            $(document).on('pumBeforeClose', this.handlePopupClose);
        },

        handlePluginEvent: function(e, data) {
            // Handle integration logic
            PUM.trigger('{{plugin_slug}}_event', [e, data]);
        },

        handlePopupOpen: function(e, data) {
            // Integration-specific popup open logic
            if (data.settings.{{plugin_slug}}_integration) {
                // Execute integration actions
            }
        },

        setupTracking: function() {
            if (pum_{{plugin_slug}}_vars.settings.tracking_enabled) {
                // Setup conversion tracking
            }
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        PUM_{{PLUGIN_UPPER}}_Integration.init();
    });

})(jQuery);
```

**Admin JS**: `assets/js/admin/integrations/{{plugin-slug}}.js`
**Frontend CSS**: `assets/css/integrations/{{plugin-slug}}.css`
**Admin CSS**: `assets/css/admin/integrations/{{plugin-slug}}.css`

## Implementation Checklist

### Phase 1: Core Integration
- [ ] Create main integration class
- [ ] Implement plugin detection
- [ ] Setup basic hooks and filters
- [ ] Add settings tab and fields

### Phase 2: Components  
- [ ] Create integration-specific conditions
- [ ] Create integration-specific triggers
- [ ] Create integration-specific cookies
- [ ] Create integration-specific CTAs

### Phase 3: Assets & UI
- [ ] Build frontend JavaScript integration
- [ ] Build admin JavaScript enhancements
- [ ] Create integration-specific CSS
- [ ] Implement admin UI components

### Phase 4: Advanced Features
- [ ] Implement conversion tracking
- [ ] Add advanced targeting options
- [ ] Setup data synchronization
- [ ] Create API endpoints (if needed)

### Phase 5: Testing & Documentation
- [ ] Unit tests for integration class
- [ ] Integration tests with target plugin
- [ ] User documentation and examples
- [ ] Developer hooks documentation

## Real-World Examples

### WooCommerce Integration Patterns
```php
// Product-based conditions
add_action('woocommerce_add_to_cart', [__CLASS__, 'handle_add_to_cart']);

// Order completion tracking
add_action('woocommerce_thankyou', [__CLASS__, 'track_purchase']);

// Customer targeting
add_filter('pum_condition_woocommerce_customer_data', [__CLASS__, 'get_customer_data']);
```

### LMS Integration Patterns  
```php
// Course enrollment triggers
add_action('llms_user_enrolled_in_course', [__CLASS__, 'handle_enrollment']);

// Progress-based conditions
add_filter('pum_condition_lms_progress', [__CLASS__, 'check_progress']);

// Achievement tracking
add_action('llms_user_earned_achievement', [__CLASS__, 'track_achievement']);
```

### CRM Integration Patterns
```php
// Contact synchronization
add_action('pum_form_submission', [__CLASS__, 'sync_contact']);

// Segmentation conditions
add_filter('pum_condition_crm_segment', [__CLASS__, 'check_segment']);

// Campaign tracking
add_action('pum_cta_executed', [__CLASS__, 'track_campaign']);
```

## Variables Reference

**Required Variables:**
- `{{PLUGIN_NAME}}` - Human readable plugin name
- `{{PLUGIN_CLASS}}` - Plugin's main class name
- `{{PLUGIN_FUNCTION}}` - Plugin's detection function
- `{{plugin-slug}}` - Lowercase slug with hyphens
- `{{plugin_slug}}` - Lowercase slug with underscores  
- `{{PLUGIN_UPPER}}` - Uppercase slug for JavaScript
- `{{plugin_hook_prefix}}` - Plugin's hook prefix
- `{{plugin-icon}}` - Dashicon for settings tab

**Component Variables:**
- `{{CONDITION_TYPE}}` - Specific condition name
- `{{TRIGGER_TYPE}}` - Specific trigger name  
- `{{COOKIE_TYPE}}` - Specific cookie name
- `{{CTA_TYPE}}` - Specific CTA name

This orchestrator template ensures comprehensive integration with third-party plugins while maintaining Popup Maker's architecture and performance standards.