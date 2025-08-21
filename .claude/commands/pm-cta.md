---
name: "Create Popup Maker CTA Type"
description: "Creates a complete Call-to-Action handler class with conversion tracking and 3rd party integration"
version: "1.0.0"
author: "Popup Maker"
category: "popup-maker"
---

# 🎯 Create Popup Maker CTA Type

This template creates a complete Call-to-Action (CTA) handler class that extends Popup Maker's base CTA system with full conversion tracking and 3rd party integration capabilities.

## 📋 Step 1: Gather Requirements

Let me help you create a new CTA type. I'll need some details:

**CTA Details:**
- What should we call this CTA? (e.g. "Subscribe to Newsletter")  
- What's the unique key? (e.g. "newsletter_subscribe", auto-generated from name)
- What action should it perform? (redirect, form submit, API call, 3rd party action)
- Should it require user login?
- Which plugin/extension will this belong to?

**CTA Behavior:**
- What settings/fields does it need?
- Should it integrate with specific plugins? (WooCommerce, forms, CRM, etc.)
- Does it need custom validation?
- Should it perform redirects or stay on page?
- Is this a revenue-generating CTA?

## 🎯 Step 2: CTA Types & Integration Patterns

### Common CTA Types
- **Redirect CTAs** - Navigate to URLs, posts, pages
- **Form CTAs** - Submit forms, integrate with form plugins
- **E-commerce CTAs** - Add to cart, checkout, account actions
- **Social CTAs** - Share, follow, like actions
- **Download CTAs** - File downloads, content gating
- **API CTAs** - External service integration
- **Revenue CTAs** - Purchase tracking, attribution, analytics

### Base Class Patterns (observed from existing code)
- **BasePlugin** - Plugin-specific shared functionality
- **BaseRevenueAction** - Revenue tracking and attribution
- **BaseIntegration** - 3rd party system integration
- **BaseForm** - Form submission handling

## 💻 Complete CTA Class Generation

Based on Popup Maker Pro/Extension patterns, I'll generate a full CTA class:

```php
<?php
/**
 * {{CTA_NAME}} CTA Handler
 *
 * @package   {{PACKAGE_NAME}}
 * @copyright {{COPYRIGHT}}
 */

namespace {{NAMESPACE}}\CallToAction{{PLUGIN_NAMESPACE}};

use PopupMaker\Base\CallToAction;
use PopupMaker\Models\CallToAction as CTAModel;
{{ADDITIONAL_IMPORTS}}

defined( 'ABSPATH' ) || exit;

/**
 * Class {{CLASS_NAME}}
 *
 * {{DESCRIPTION}}
 *
 * @since {{VERSION}}
 */
class {{CLASS_NAME}} extends {{BASE_CLASS}} {

    /**
     * Unique identifier token.
     *
     * @var string
     */
    public $key = '{{CTA_KEY}}';

    /**
     * Version of this CTA.
     *
     * @var int
     */
    public $version = 1;

    /**
     * Whether the CTA requires the user to be logged in.
     *
     * @var bool
     */
    public $login_required = {{LOGIN_REQUIRED}};

    /**
     * CTA display label.
     *
     * @return string
     */
    public function label(): string {
        return __( '{{CTA_NAME}}', '{{TEXT_DOMAIN}}' );
    }

    /**
     * CTA description for admin.
     *
     * @return string
     */
    public function description(): string {
        return __( '{{CTA_DESCRIPTION}}', '{{TEXT_DOMAIN}}' );
    }

    /**
     * CTA field configuration by tab.
     *
     * @return array<string, array<string, mixed>[]>
     */
    public function fields(): array {
        return [
            'general' => array_merge(
                {{BASE_FIELDS}},
                [
                    {{GENERAL_FIELDS}}
                ]{{FIELD_MERGE_PATTERN}}
            ),
            {{CUSTOM_TABS}}
        ];
    }

    /**
     * Handle the CTA action.
     *
     * CRITICAL: Always track conversion first!
     *
     * @param CTAModel             $call_to_action Call to action object.
     * @param array<string, mixed> $extra_args     Optional. Additional data passed to the handler.
     *
     * @return void
     */
    public function action_handler( CTAModel $call_to_action, array $extra_args = [] ): void {
        {{PLUGIN_CHECK}}

        // Validate required settings
        if ( ! $this->validate_settings( $call_to_action->get_settings() ) ) {
            $this->safe_redirect();
            return;
        }

        {{LOGIN_CHECK}}

        try {
            // Execute the specific action
            $success = $this->{{EXECUTE_METHOD}}( $call_to_action, $extra_args );

            if ( $success ) {
                // ALWAYS track conversion after successful action
                $call_to_action->track_conversion( $extra_args );
            }
        } catch ( \Exception $e ) {
            // Log error
            \PopupMaker\logging()->log( 'ERROR: {{PLUGIN_NAME}} ' . $this->label() . ' Error: ' . $e->getMessage() );
        }

        {{REDIRECT_LOGIC}}
    }

    /**
     * Validate CTA settings before saving.
     *
     * @param array<string, mixed> $settings The raw settings array to validate.
     *
     * @return true|\WP_Error|\WP_Error[]
     */
    public function validate_settings( array $settings ): \WP_Error|array|bool {
        // Validate required fields
        $validation = $this->validate_required_fields( $settings );
        if ( is_wp_error( $validation ) ) {
            return $validation;
        }

        {{CUSTOM_VALIDATION}}

        return true;
    }

    {{IMPLEMENTATION_METHODS}}
}
```

## 🔧 Specific CTA Implementations

### Revenue-Tracking CTA (E-commerce/LMS):
```php
/**
 * Revenue CTA with purchase tracking
 */
class PurchaseProduct extends BaseRevenueAction {
    
    use \PopupMaker\Pro\Traits\PurchaseTracking; // Enables revenue tracking

    public function action_handler( CTAModel $call_to_action, array $extra_args = [] ): void {
        // Check plugin availability
        if ( ! woocommerce_is_active() ) {
            $this->safe_redirect();
            return;
        }

        // Get settings
        $settings = $call_to_action->get_settings();
        $product_id = (int) $settings['product_id'];
        $quantity = (int) $settings['quantity'] ?? 1;

        try {
            // Add to cart
            $result = WC()->cart->add_to_cart( $product_id, $quantity );
            
            if ( $result ) {
                // Track conversion with purchase context
                $call_to_action->track_conversion( array_merge( $extra_args, [
                    'product_id' => $product_id,
                    'quantity'   => $quantity,
                    'cart_value' => WC()->cart->get_cart_contents_total(),
                ] ) );
                
                // Revenue attribution is handled by PurchaseTracking trait
                $this->safe_redirect( wc_get_checkout_url() );
            }
        } catch ( \Exception $e ) {
            \PopupMaker\logging()->log( 'Purchase CTA Error: ' . $e->getMessage() );
        }
    }
}
```

### Form Integration CTA:
```php
/**
 * Form submission CTA with plugin integration
 */
class SubmitToNewsletter extends BaseFormCTA {
    
    public function fields(): array {
        return [
            'general' => [
                'email_field' => [
                    'type'        => 'text',
                    'label'       => __( 'Email Field Selector', '{{TEXT_DOMAIN}}' ),
                    'desc'        => __( 'CSS selector for email input field', '{{TEXT_DOMAIN}}' ),
                    'placeholder' => 'input[name="email"], #email-field',
                    'required'    => true,
                ],
                'list_id' => [
                    'type'    => 'select',
                    'label'   => __( 'Mailing List', '{{TEXT_DOMAIN}}' ),
                    'options' => $this->get_mailing_lists(),
                    'required' => true,
                ],
            ],
        ];
    }

    protected function execute_form_action( CTAModel $call_to_action, array $extra_args = [] ): bool {
        $settings = $call_to_action->get_settings();
        $email = $this->extract_email_from_form( $settings['email_field'], $extra_args );
        
        if ( ! $email || ! is_email( $email ) ) {
            return false;
        }

        // Subscribe to newsletter
        return $this->subscribe_to_list( $email, $settings['list_id'] );
    }

    private function get_mailing_lists(): array {
        // Integrate with MailChimp, ConvertKit, etc.
        $lists = [];
        
        if ( class_exists( 'MailChimp_API' ) ) {
            // Get MailChimp lists
        }
        
        return $lists;
    }
}
```

### API Integration CTA:
```php
/**
 * External API integration CTA
 */
class WebhookNotification extends BaseAPICTA {
    
    public function fields(): array {
        return [
            'general' => [
                'webhook_url' => [
                    'type'        => 'url',
                    'label'       => __( 'Webhook URL', '{{TEXT_DOMAIN}}' ),
                    'desc'        => __( 'Endpoint to send notification to', '{{TEXT_DOMAIN}}' ),
                    'required'    => true,
                ],
                'payload_data' => [
                    'type'        => 'textarea',
                    'label'       => __( 'Payload Data (JSON)', '{{TEXT_DOMAIN}}' ),
                    'desc'        => __( 'JSON data to send. Use {{user_email}}, {{user_id}} placeholders', '{{TEXT_DOMAIN}}' ),
                    'rows'        => 6,
                    'placeholder' => '{"event": "popup_conversion", "user_id": "{{user_id}}", "popup_id": "{{popup_id}}"}',
                ],
                'timeout' => [
                    'type' => 'number',
                    'label' => __( 'Timeout (seconds)', '{{TEXT_DOMAIN}}' ),
                    'min'  => 1,
                    'max'  => 30,
                    'std'  => 5,
                ],
            ],
            'advanced' => [
                'headers' => [
                    'type'        => 'textarea',
                    'label'       => __( 'Custom Headers (JSON)', '{{TEXT_DOMAIN}}' ),
                    'desc'        => __( 'Additional HTTP headers as JSON object', '{{TEXT_DOMAIN}}' ),
                    'placeholder' => '{"Authorization": "Bearer your-token", "Content-Type": "application/json"}',
                    'rows'        => 3,
                ],
            ],
        ];
    }

    protected function execute_api_action( CTAModel $call_to_action, array $extra_args = [] ): bool {
        $settings = $call_to_action->get_settings();
        $webhook_url = $settings['webhook_url'];
        $payload_template = $settings['payload_data'] ?? '{}';
        $timeout = (int) $settings['timeout'] ?? 5;
        $custom_headers = json_decode( $settings['headers'] ?? '{}', true );

        // Replace placeholders
        $payload = $this->replace_placeholders( $payload_template, $extra_args );
        
        // Prepare request
        $args = [
            'method'  => 'POST',
            'timeout' => $timeout,
            'headers' => array_merge( [
                'Content-Type' => 'application/json',
                'User-Agent'   => 'PopupMaker/' . POPMAKE_VERSION,
            ], $custom_headers ),
            'body'    => $payload,
        ];

        // Send webhook
        $response = wp_remote_post( $webhook_url, $args );
        
        if ( is_wp_error( $response ) ) {
            \PopupMaker\logging()->log( 'Webhook Error: ' . $response->get_error_message() );
            return false;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        return $status_code >= 200 && $status_code < 300;
    }

    private function replace_placeholders( string $template, array $extra_args ): string {
        $replacements = [
            '{{user_id}}'    => get_current_user_id(),
            '{{user_email}}' => wp_get_current_user()->user_email ?? '',
            '{{popup_id}}'   => $extra_args['popup_id'] ?? 0,
            '{{timestamp}}'  => time(),
            '{{site_url}}'   => site_url(),
        ];

        return str_replace( array_keys( $replacements ), array_values( $replacements ), $template );
    }
}
```

## 📦 CTA Registration

I'll generate the registration code following Pro/Extension patterns:

```php
/**
 * Register {{CTA_NAME}} CTA
 */
function {{FUNCTION_PREFIX}}_register_cta( $ctas ) {
    $ctas['{{CTA_KEY}}'] = new \{{NAMESPACE}}\CallToAction\{{PLUGIN_NAMESPACE}}\{{CLASS_NAME}}();
    return $ctas;
}
add_filter( 'popup_maker/registered_call_to_actions', '{{FUNCTION_PREFIX}}_register_cta' );

/**
 * Initialize CTA registration
 */
function {{FUNCTION_PREFIX}}_init_ctas() {
    // Check plugin dependencies
    if ( ! function_exists( '{{PLUGIN_CHECK_FUNCTION}}' ) ) {
        return;
    }

    // Register the CTA
    add_filter( 'popup_maker/registered_call_to_actions', '{{FUNCTION_PREFIX}}_register_cta' );
}
add_action( 'plugins_loaded', '{{FUNCTION_PREFIX}}_init_ctas' );
```

## 📂 File Structure & Placement

I'll create the complete file structure following Pro/Extension patterns:

```
{{PLUGIN_DIR}}/
├── classes/
│   └── CallToAction/
│       └── {{PLUGIN_NAMESPACE}}/
│           ├── Base{{PLUGIN_NAME}}.php        # Base class (if needed)
│           ├── BaseRevenueAction.php          # Revenue tracking base
│           └── {{CLASS_NAME}}.php             # Your CTA class
├── includes/
│   └── cta-registration.php                   # Registration hooks
└── {{MAIN_FILE}}.php                          # Main plugin file
```

## 🧪 Testing Your CTA

### Admin Testing:
1. Edit popup → Call to Action tab
2. Select your new CTA type from dropdown
3. Configure all required settings
4. Test field dependencies and validation
5. Preview popup behavior

### Frontend Testing:
```php
// Test CTA execution manually
$cta_data = [
    'type' => '{{CTA_KEY}}',
    'settings' => [
        // Your test settings
    ],
];

// Simulate CTA click
do_action( 'pum_cta_executed', 123, $cta_data ); // popup_id, cta_data
```

### Debug Helper:
```php
// Add to action_handler for debugging
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    error_log( 'CTA {{CTA_KEY}} executed with settings: ' . print_r( $settings, true ) );
    error_log( 'Extra args: ' . print_r( $extra_args, true ) );
}
```

### Revenue Tracking Test (for revenue CTAs):
```php
// Check if conversion tracking works
add_action( 'popup_maker/cta/conversion_tracked', function( $cta_id, $popup_id, $data ) {
    error_log( "Conversion tracked - CTA: $cta_id, Popup: $popup_id, Data: " . print_r( $data, true ) );
}, 10, 3 );
```

## ⚙️ Advanced Features

### Conditional Field Display:
```php
'conditional_field' => [
    'type'         => 'text',
    'label'        => __( 'Conditional Field', '{{TEXT_DOMAIN}}' ),
    'dependencies' => [
        'action_type' => 'specific_value', // Show only when action_type = 'specific_value'
        'enabled'     => true,             // Show only when enabled checkbox is checked
    ],
],
```

### AJAX CTA Processing:
```php
/**
 * Handle AJAX CTA execution (for non-redirect CTAs)
 */
public function ajax_handler(): void {
    check_ajax_referer( '{{CTA_KEY}}_nonce', 'nonce' );

    $popup_id = (int) $_POST['popup_id'];
    $settings = $_POST['settings'] ?? [];

    // Sanitize settings
    $settings = $this->sanitize_ajax_settings( $settings );
    
    // Create temporary CTA model
    $cta_model = new CTAModel();
    $cta_model->set_popup_id( $popup_id );
    $cta_model->set_settings( $settings );

    // Execute action
    $result = $this->action_handler( $cta_model, ['ajax' => true] );

    // Return JSON response
    wp_send_json_success( [
        'message' => __( 'Action completed successfully', '{{TEXT_DOMAIN}}' ),
        'result'  => $result,
    ] );
}

// Register AJAX handlers
add_action( 'wp_ajax_{{CTA_KEY}}_action', [ $this, 'ajax_handler' ] );
add_action( 'wp_ajax_nopriv_{{CTA_KEY}}_action', [ $this, 'ajax_handler' ] );
```

### Attribution & Analytics Integration:
```php
// For revenue-generating CTAs, enhanced conversion tracking
public function action_handler( CTAModel $call_to_action, array $extra_args = [] ): void {
    // ... execution logic ...
    
    if ( $success ) {
        // Enhanced conversion data for attribution
        $conversion_data = [
            'cta_type'       => $this->key,
            'plugin_context' => '{{PLUGIN_NAME}}',
            'revenue_value'  => $this->get_revenue_value( $call_to_action ),
            'currency'       => $this->get_currency(),
            'utm_source'     => $_GET['utm_source'] ?? '',
            'utm_medium'     => $_GET['utm_medium'] ?? '',
            'utm_campaign'   => $_GET['utm_campaign'] ?? '',
            'referrer'       => wp_get_referer(),
            'user_agent'     => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ];

        $call_to_action->track_conversion( array_merge( $extra_args, $conversion_data ) );
    }
}

protected function get_revenue_value( CTAModel $call_to_action ): float {
    // Calculate expected revenue from this CTA
    // For WooCommerce: product price * quantity
    // For courses: course price
    // For subscriptions: recurring value
    return 0.0;
}
```

## ✅ Best Practices (From Pro/Extension Analysis)

✅ **Do:**
- Always call `track_conversion()` after successful action
- Use base classes for shared functionality (BaseWooCommerce, BaseLifterLMS, etc.)
- Validate and sanitize all inputs thoroughly
- Use `safe_redirect()` for all redirects
- Check plugin availability before execution
- Include proper error handling and logging
- Follow WordPress coding standards
- Use revenue tracking traits for purchase CTAs
- Implement proper field dependencies
- Provide clear field descriptions

❌ **Don't:**
- Skip conversion tracking for successful actions
- Use direct redirects without validation
- Ignore plugin dependency checks
- Store sensitive data in CTA settings
- Perform blocking operations in action_handler
- Skip input validation and sanitization
- Use global variables unnecessarily
- Hardcode URLs or paths

## 🔍 Field Types Reference (From Extension Analysis)

```php
// Product/Post Selection
'product_field' => [
    'type'      => 'postselect',
    'label'     => __( 'Select Product', '{{TEXT_DOMAIN}}' ),
    'post_type' => 'product', // or 'llms_course', 'llms_access_plan', etc.
    'multiple'  => false,
    'as_array'  => false,
],

// Redirect Options (WooCommerce pattern)
'redirect_to' => [
    'type'    => 'select',
    'label'   => __( 'Redirect After Action', '{{TEXT_DOMAIN}}' ),
    'options' => [
        'checkout' => __( 'Checkout Page', '{{TEXT_DOMAIN}}' ),
        'cart'     => __( 'Shopping Cart', '{{TEXT_DOMAIN}}' ),
        'back'     => __( 'Previous Page', '{{TEXT_DOMAIN}}' ),
        'custom'   => __( 'Custom URL', '{{TEXT_DOMAIN}}' ),
    ],
    'std' => 'checkout',
],

// Conditional Custom URL
'custom_url' => [
    'type'         => 'url',
    'label'        => __( 'Custom Redirect URL', '{{TEXT_DOMAIN}}' ),
    'dependencies' => [
        'redirect_to' => 'custom',
    ],
],

// User Selection (LifterLMS pattern)
'user_source' => [
    'type'    => 'select',
    'label'   => __( 'Target User', '{{TEXT_DOMAIN}}' ),
    'options' => [
        'current_user' => __( 'Current Logged-in User', '{{TEXT_DOMAIN}}' ),
        'url_param'    => __( 'From URL Parameter', '{{TEXT_DOMAIN}}' ),
        'form_field'   => __( 'From Form Field', '{{TEXT_DOMAIN}}' ),
    ],
],

// Quantity/Amount Fields
'quantity' => [
    'type' => 'number',
    'label' => __( 'Quantity', '{{TEXT_DOMAIN}}' ),
    'min'  => 1,
    'std'  => 1,
],

// Toggle Features
'send_notifications' => [
    'type'  => 'checkbox',
    'label' => __( 'Send Email Notifications', '{{TEXT_DOMAIN}}' ),
    'desc'  => __( 'Send system notifications for this action', '{{TEXT_DOMAIN}}' ),
],
```

Ready to create your CTA? Share the details and I'll generate the complete class with proper base class selection and all necessary methods! 🎯✨