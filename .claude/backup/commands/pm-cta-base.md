---
name: "Create CTA Base Class"
description: "Creates a base class for CTA families to share common functionality and reduce code duplication"
version: "1.0.0"
author: "Popup Maker"
category: "popup-maker"
---

# 🏗️ Create Popup Maker CTA Base Class

This template creates a base class that multiple related CTAs can extend, promoting code reuse and consistent behavior patterns. Based on Pro extension patterns like `BaseRevenueAction`, `BaseLifterLMS`, and `BaseWooCommerce`.

## 📋 Step 1: Identify CTA Family Requirements

Let me help you create a CTA base class. I'll need some details about your CTA family:

**Base Class Details:**
- What's the family name? (e.g. "WooCommerce", "LifterLMS", "FluentCRM", "Revenue")  
- What functionality will be shared? (user handling, API calls, validation, tracking)
- What's the namespace? (e.g. `YourPlugin\CallToAction\WooCommerce\`)
- Which parent class to extend? (`PopupMaker\Base\CallToAction`, existing base)

**Common Features:**
- Revenue tracking and attribution?
- User authentication requirements?
- Third-party API integration?
- Form data processing?
- Notification systems?
- Redirect handling?

## 🎯 Step 2: Base Class Patterns

### Revenue Tracking Base (Pro Pattern)
For CTAs that generate revenue and need attribution tracking.

### Integration Base (Pro Pattern)
For CTAs that interact with third-party services.

### User Action Base (Pro Pattern)  
For CTAs that perform actions on users (enrollment, assignments, etc.).

### Form Processing Base
For CTAs that process form submissions.

## 💻 Code Generation

Based on your requirements, I'll generate:

### Revenue Tracking Base Class:
```php
<?php
/**
 * {{BASE_CLASS_NAME}} Base Class
 *
 * @package {{NAMESPACE}}
 * @since   {{VERSION}}
 */

namespace {{NAMESPACE}};

defined( 'ABSPATH' ) || exit;

/**
 * Abstract {{BASE_CLASS_NAME}} Base Class
 *
 * Provides shared functionality for {{FAMILY_NAME}} CTAs including:
 * - Revenue tracking and attribution
 * - Purchase validation
 * - Conversion tracking
 */
abstract class {{BASE_CLASS_NAME}} extends \PopupMaker\Base\CallToAction {

    /**
     * Enable revenue tracking features.
     *
     * @var \PopupMaker\Pro\Traits\PurchaseTracking
     */
    use \PopupMaker\Pro\Traits\PurchaseTracking;

    /**
     * Get common field definitions for all {{FAMILY_NAME}} CTAs.
     *
     * @return array<string,array<string,mixed>>
     */
    protected function get_common_fields(): array {
        return [
            'redirectTo' => [
                'type'        => 'select',
                'label'       => __( 'Redirect After Action', '{{TEXT_DOMAIN}}' ),
                'description' => __( 'Where to redirect users after successful action.', '{{TEXT_DOMAIN}}' ),
                'options'     => [
                    'stay'     => __( 'Stay on Current Page', '{{TEXT_DOMAIN}}' ),
                    'checkout' => __( 'Checkout Page', '{{TEXT_DOMAIN}}' ),
                    'cart'     => __( 'Cart Page', '{{TEXT_DOMAIN}}' ),
                    'custom'   => __( 'Custom URL', '{{TEXT_DOMAIN}}' ),
                ],
                'std'         => 'checkout',
                'priority'    => 90,
            ],
            'customRedirectUrl' => [
                'type'         => 'url',
                'label'        => __( 'Custom Redirect URL', '{{TEXT_DOMAIN}}' ),
                'description'  => __( 'URL to redirect to after successful action.', '{{TEXT_DOMAIN}}' ),
                'priority'     => 91,
                'dependencies' => [
                    'redirectTo' => 'custom',
                ],
            ],
        ];
    }

    /**
     * Get redirect URL based on settings.
     *
     * @param string $redirect_to Redirect setting value.
     * @param string $custom_url  Custom URL if redirect_to is 'custom'.
     * @return string
     */
    protected function get_redirect_url( string $redirect_to = 'stay', string $custom_url = '' ): string {
        switch ( $redirect_to ) {
            case 'checkout':
                return {{GET_CHECKOUT_URL_FUNCTION}}();
            case 'cart':
                return {{GET_CART_URL_FUNCTION}}();
            case 'custom':
                return esc_url_raw( $custom_url );
            default:
                return '';
        }
    }

    /**
     * Handle the CTA action with common revenue tracking.
     *
     * @param \PopupMaker\Models\CallToAction $call_to_action Call to action object.
     * @param array<string,mixed>             $extra_args     Additional data.
     * @return void
     */
    public function action_handler( \PopupMaker\Models\CallToAction $call_to_action, array $extra_args = [] ): void {
        // Validate settings before processing
        if ( ! $this->validate_settings( $call_to_action->get_settings() ) ) {
            $this->safe_redirect();
            return;
        }

        try {
            // Get initial values for revenue calculation
            $initial_value = $this->get_current_value();
            
            // Execute the specific action
            $success = $this->execute_action( $call_to_action, $extra_args );

            if ( $success ) {
                // Calculate added value for revenue tracking
                $added_value = max( $this->get_current_value() - $initial_value, 0 );
                
                // Track conversion with revenue data
                $call_to_action->track_conversion( array_merge( $extra_args, [
                    'added_value' => $added_value,
                    'action_type' => $this->get_action_type(),
                ] ) );

                // Handle post-action logic
                $this->post_action_handler( $call_to_action, $extra_args );
            }
        } catch ( \Exception $e ) {
            // Log error for debugging
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'CTA Error (' . $this->key . '): ' . $e->getMessage() );
            }
        }

        // Redirect based on settings
        $redirect_to = $call_to_action->get_setting( 'redirectTo', 'stay' );
        $custom_url = $call_to_action->get_setting( 'customRedirectUrl', '' );
        $redirect_url = $this->get_redirect_url( $redirect_to, $custom_url );
        
        $this->safe_redirect( $redirect_url );
    }

    /**
     * Get current value for revenue calculation.
     * Override in child classes for specific implementations.
     *
     * @return float
     */
    protected function get_current_value(): float {
        return 0.0;
    }

    /**
     * Get action type for tracking purposes.
     * Override in child classes.
     *
     * @return string
     */
    protected function get_action_type(): string {
        return 'unknown';
    }

    /**
     * Execute the specific CTA action.
     * Must be implemented by child classes.
     *
     * @param \PopupMaker\Models\CallToAction $call_to_action Call to action object.
     * @param array<string,mixed>             $extra_args     Additional data.
     * @return bool Success status.
     */
    abstract protected function execute_action( \PopupMaker\Models\CallToAction $call_to_action, array $extra_args = [] ): bool;

    /**
     * Handle post-action logic (notifications, cleanup, etc.).
     * Override in child classes as needed.
     *
     * @param \PopupMaker\Models\CallToAction $call_to_action Call to action object.
     * @param array<string,mixed>             $extra_args     Additional data.
     * @return void
     */
    protected function post_action_handler( \PopupMaker\Models\CallToAction $call_to_action, array $extra_args = [] ): void {
        // Default implementation - override in child classes
    }
}
```

### Integration Base Class (Pro Pattern):
```php
<?php
/**
 * {{INTEGRATION_NAME}} Base CTA Class
 *
 * @package {{NAMESPACE}}
 * @since   {{VERSION}}
 */

namespace {{NAMESPACE}};

defined( 'ABSPATH' ) || exit;

/**
 * Abstract {{INTEGRATION_NAME}} Base Class
 *
 * Provides shared functionality for {{INTEGRATION_NAME}} CTAs including:
 * - API connectivity validation
 * - User authentication
 * - Error handling
 * - Notification systems
 */
abstract class {{BASE_CLASS_NAME}} extends \PopupMaker\Base\CallToAction {

    /**
     * Mark that these CTAs require user login.
     *
     * @var bool
     */
    public $login_required = true;

    /**
     * Handle the CTA action with common integration patterns.
     *
     * @param \PopupMaker\Models\CallToAction $call_to_action Call to action object.
     * @param array<string,mixed>             $extra_args     Additional data.
     * @return void
     */
    public function action_handler( \PopupMaker\Models\CallToAction $call_to_action, array $extra_args = [] ): void {
        // Check if integration is available
        if ( ! $this->is_integration_active() ) {
            $this->safe_redirect();
            return;
        }

        // Validate required settings
        if ( ! $this->validate_settings( $call_to_action->get_settings() ) ) {
            $this->safe_redirect();
            return;
        }

        // Get user for action
        $user_id = $this->get_target_user_id( $call_to_action, $extra_args );
        if ( ! $user_id && $this->login_required ) {
            $login_url = wp_login_url( $call_to_action->get_setting( 'redirectUrl', '' ) );
            $this->safe_redirect( $login_url );
            return;
        }

        try {
            // Execute the specific integration action
            $success = $this->execute_integration_action( $call_to_action, $user_id, $extra_args );

            if ( $success ) {
                // Track successful conversion
                $call_to_action->track_conversion( $extra_args );

                // Send notifications if enabled
                if ( $call_to_action->get_setting( 'sendNotifications', false ) ) {
                    $this->send_notifications( $call_to_action, $user_id, $extra_args );
                }
            }
        } catch ( \Exception $e ) {
            // Log integration error
            error_log( sprintf(
                'Integration CTA Error (%s): %s',
                $this->key,
                $e->getMessage()
            ) );
        }

        // Redirect after action
        $redirect_url = $call_to_action->get_setting( 'redirectUrl', '' );
        $this->safe_redirect( $redirect_url );
    }

    /**
     * Get common field definitions.
     *
     * @return array<string,array<string,mixed>>
     */
    protected function get_common_fields(): array {
        return [
            'userSource' => [
                'type'        => 'select',
                'label'       => __( 'Target User', '{{TEXT_DOMAIN}}' ),
                'description' => __( 'Which user to perform the action on.', '{{TEXT_DOMAIN}}' ),
                'options'     => [
                    'current_user' => __( 'Current Logged-in User', '{{TEXT_DOMAIN}}' ),
                    'url_param'    => __( 'URL Parameter', '{{TEXT_DOMAIN}}' ),
                ],
                'std'         => 'current_user',
                'priority'    => 10,
            ],
            'urlParam' => [
                'type'         => 'text',
                'label'        => __( 'URL Parameter', '{{TEXT_DOMAIN}}' ),
                'description'  => __( 'URL parameter to identify the user.', '{{TEXT_DOMAIN}}' ),
                'priority'     => 11,
                'dependencies' => [
                    'userSource' => 'url_param',
                ],
            ],
            'sendNotifications' => [
                'type'        => 'checkbox',
                'label'       => __( 'Send Notifications', '{{TEXT_DOMAIN}}' ),
                'description' => __( 'Send {{INTEGRATION_NAME}} notifications for this action.', '{{TEXT_DOMAIN}}' ),
                'priority'    => 15,
            ],
            'redirectUrl' => [
                'type'        => 'url',
                'label'       => __( 'Redirect URL', '{{TEXT_DOMAIN}}' ),
                'description' => __( 'URL to redirect to after action (optional).', '{{TEXT_DOMAIN}}' ),
                'priority'    => 20,
            ],
        ];
    }

    /**
     * Get target user ID based on settings.
     *
     * @param \PopupMaker\Models\CallToAction $call_to_action Call to action object.
     * @param array<string,mixed>             $extra_args     Additional data.
     * @return int|null
     */
    protected function get_target_user_id( \PopupMaker\Models\CallToAction $call_to_action, array $extra_args = [] ): ?int {
        $user_source = $call_to_action->get_setting( 'userSource', 'current_user' );
        
        switch ( $user_source ) {
            case 'url_param':
                $param = $call_to_action->get_setting( 'urlParam', '' );
                return isset( $_GET[ $param ] ) ? (int) $_GET[ $param ] : null;
            
            case 'current_user':
            default:
                return get_current_user_id() ?: null;
        }
    }

    /**
     * Send notifications for the action.
     *
     * @param \PopupMaker\Models\CallToAction $call_to_action Call to action object.
     * @param int                             $user_id        User ID.
     * @param array<string,mixed>             $extra_args     Additional data.
     * @return void
     */
    protected function send_notifications( $call_to_action, int $user_id, array $extra_args = [] ): void {
        // Default implementation - override in child classes
        do_action( 'popup_maker/{{INTEGRATION_NAME}}/notification_sent', $call_to_action, $user_id, $extra_args );
    }

    /**
     * Check if the integration is active and available.
     * Must be implemented by child classes.
     *
     * @return bool
     */
    abstract protected function is_integration_active(): bool;

    /**
     * Execute the specific integration action.
     * Must be implemented by child classes.
     *
     * @param \PopupMaker\Models\CallToAction $call_to_action Call to action object.
     * @param int                             $user_id        User ID.
     * @param array<string,mixed>             $extra_args     Additional data.
     * @return bool Success status.
     */
    abstract protected function execute_integration_action( \PopupMaker\Models\CallToAction $call_to_action, int $user_id, array $extra_args = [] ): bool;
}
```

### Form Processing Base Class:
```php
<?php
/**
 * Form Processing Base CTA Class
 *
 * @package {{NAMESPACE}}
 * @since   {{VERSION}}
 */

namespace {{NAMESPACE}};

defined( 'ABSPATH' ) || exit;

/**
 * Abstract Form Processing Base Class
 *
 * Provides shared functionality for form-based CTAs including:
 * - Form data validation
 * - Sanitization
 * - CSRF protection
 * - File upload handling
 */
abstract class {{BASE_CLASS_NAME}} extends \PopupMaker\Base\CallToAction {

    /**
     * Handle the CTA action with form processing.
     *
     * @param \PopupMaker\Models\CallToAction $call_to_action Call to action object.
     * @param array<string,mixed>             $extra_args     Additional data.
     * @return void
     */
    public function action_handler( \PopupMaker\Models\CallToAction $call_to_action, array $extra_args = [] ): void {
        // Verify nonce for security
        if ( ! $this->verify_nonce() ) {
            wp_die( __( 'Security verification failed.', '{{TEXT_DOMAIN}}' ) );
        }

        // Get and validate form data
        $form_data = $this->get_form_data();
        $validation_result = $this->validate_form_data( $form_data, $call_to_action->get_settings() );

        if ( is_wp_error( $validation_result ) ) {
            // Handle validation errors
            $this->handle_form_errors( $validation_result );
            return;
        }

        try {
            // Process the form data
            $success = $this->process_form_data( $form_data, $call_to_action, $extra_args );

            if ( $success ) {
                // Track successful conversion
                $call_to_action->track_conversion( $extra_args );
                
                // Handle success redirect/response
                $this->handle_form_success( $call_to_action, $form_data );
            } else {
                $this->handle_form_failure( $call_to_action, $form_data );
            }
        } catch ( \Exception $e ) {
            // Log processing error
            error_log( sprintf(
                'Form CTA Error (%s): %s',
                $this->key,
                $e->getMessage()
            ) );
            
            $this->handle_form_failure( $call_to_action, $form_data );
        }
    }

    /**
     * Get common field definitions for form CTAs.
     *
     * @return array<string,array<string,mixed>>
     */
    protected function get_common_fields(): array {
        return [
            'requiredFields' => [
                'type'        => 'multicheck',
                'label'       => __( 'Required Fields', '{{TEXT_DOMAIN}}' ),
                'description' => __( 'Select which fields are required.', '{{TEXT_DOMAIN}}' ),
                'options'     => $this->get_available_fields(),
                'priority'    => 10,
            ],
            'successMessage' => [
                'type'        => 'text',
                'label'       => __( 'Success Message', '{{TEXT_DOMAIN}}' ),
                'description' => __( 'Message to show on successful submission.', '{{TEXT_DOMAIN}}' ),
                'std'         => __( 'Thank you! Your submission was successful.', '{{TEXT_DOMAIN}}' ),
                'priority'    => 15,
            ],
            'errorMessage' => [
                'type'        => 'text',
                'label'       => __( 'Error Message', '{{TEXT_DOMAIN}}' ),
                'description' => __( 'Message to show on submission failure.', '{{TEXT_DOMAIN}}' ),
                'std'         => __( 'Sorry, there was an error processing your submission.', '{{TEXT_DOMAIN}}' ),
                'priority'    => 16,
            ],
            'redirectAfterSuccess' => [
                'type'        => 'url',
                'label'       => __( 'Success Redirect URL', '{{TEXT_DOMAIN}}' ),
                'description' => __( 'URL to redirect to after successful submission (optional).', '{{TEXT_DOMAIN}}' ),
                'priority'    => 20,
            ],
        ];
    }

    /**
     * Verify nonce for form security.
     *
     * @return bool
     */
    protected function verify_nonce(): bool {
        $nonce_field = '{{NONCE_FIELD}}';
        $nonce_action = '{{NONCE_ACTION}}';
        
        return wp_verify_nonce( 
            $_POST[ $nonce_field ] ?? '', 
            $nonce_action 
        );
    }

    /**
     * Get sanitized form data.
     *
     * @return array<string,mixed>
     */
    protected function get_form_data(): array {
        // Basic sanitization - override for specific needs
        return array_map( 'sanitize_text_field', $_POST );
    }

    /**
     * Validate form data against CTA settings.
     *
     * @param array<string,mixed> $form_data Form data.
     * @param array<string,mixed> $settings  CTA settings.
     * @return true|\WP_Error
     */
    protected function validate_form_data( array $form_data, array $settings ): bool|\WP_Error {
        $errors = new \WP_Error();
        $required_fields = $settings['requiredFields'] ?? [];

        // Check required fields
        foreach ( $required_fields as $field ) {
            if ( empty( $form_data[ $field ] ) ) {
                $errors->add( 
                    'required_field', 
                    sprintf( __( 'The %s field is required.', '{{TEXT_DOMAIN}}' ), $field )
                );
            }
        }

        // Add custom validation
        $errors = $this->custom_validation( $form_data, $settings, $errors );

        return $errors->has_errors() ? $errors : true;
    }

    /**
     * Custom validation logic - override in child classes.
     *
     * @param array<string,mixed> $form_data Form data.
     * @param array<string,mixed> $settings  CTA settings.
     * @param \WP_Error           $errors    Error object.
     * @return \WP_Error
     */
    protected function custom_validation( array $form_data, array $settings, \WP_Error $errors ): \WP_Error {
        return $errors;
    }

    /**
     * Handle form validation errors.
     *
     * @param \WP_Error $errors Validation errors.
     * @return void
     */
    protected function handle_form_errors( \WP_Error $errors ): void {
        // Default error handling - override for custom behavior
        wp_die( $errors->get_error_message() );
    }

    /**
     * Handle successful form processing.
     *
     * @param \PopupMaker\Models\CallToAction $call_to_action Call to action object.
     * @param array<string,mixed>             $form_data      Form data.
     * @return void
     */
    protected function handle_form_success( \PopupMaker\Models\CallToAction $call_to_action, array $form_data ): void {
        $redirect_url = $call_to_action->get_setting( 'redirectAfterSuccess', '' );
        $success_message = $call_to_action->get_setting( 'successMessage', '' );

        if ( $redirect_url ) {
            $this->safe_redirect( $redirect_url );
        } elseif ( $success_message ) {
            echo '<div class="popup-maker-success">' . esc_html( $success_message ) . '</div>';
        }
    }

    /**
     * Handle form processing failure.
     *
     * @param \PopupMaker\Models\CallToAction $call_to_action Call to action object.
     * @param array<string,mixed>             $form_data      Form data.
     * @return void
     */
    protected function handle_form_failure( \PopupMaker\Models\CallToAction $call_to_action, array $form_data ): void {
        $error_message = $call_to_action->get_setting( 'errorMessage', __( 'An error occurred.', '{{TEXT_DOMAIN}}' ) );
        echo '<div class="popup-maker-error">' . esc_html( $error_message ) . '</div>';
    }

    /**
     * Get available fields for the form.
     * Must be implemented by child classes.
     *
     * @return array<string,string>
     */
    abstract protected function get_available_fields(): array;

    /**
     * Process the validated form data.
     * Must be implemented by child classes.
     *
     * @param array<string,mixed>             $form_data      Form data.
     * @param \PopupMaker\Models\CallToAction $call_to_action Call to action object.
     * @param array<string,mixed>             $extra_args     Additional data.
     * @return bool Success status.
     */
    abstract protected function process_form_data( array $form_data, \PopupMaker\Models\CallToAction $call_to_action, array $extra_args = [] ): bool;
}
```

## 🏗️ Child Class Implementation Examples

### Example: WooCommerce Add to Cart (Revenue Base)
```php
<?php
namespace YourPlugin\CallToAction\WooCommerce;

class AddToCart extends BaseRevenueAction {
    
    public $key = 'woocommerce_add_to_cart';
    public $version = 1;
    
    public function label(): string {
        return __( 'WooCommerce - Add to Cart', 'your-plugin' );
    }
    
    public function fields(): array {
        return [
            'general' => array_merge(
                [
                    'productId' => [
                        'type'      => 'postselect',
                        'label'     => __( 'Product', 'your-plugin' ),
                        'post_type' => 'product',
                        'multiple'  => false,
                        'priority'  => 5,
                    ],
                ],
                $this->get_common_fields()
            ),
        ];
    }
    
    protected function get_current_value(): float {
        return WC()->cart ? (float) WC()->cart->get_total( 'raw' ) : 0.0;
    }
    
    protected function get_action_type(): string {
        return 'add_to_cart';
    }
    
    protected function execute_action( \PopupMaker\Models\CallToAction $call_to_action, array $extra_args = [] ): bool {
        $product_id = $call_to_action->get_setting( 'productId', 0 );
        $quantity = 1;
        
        return (bool) WC()->cart->add_to_cart( $product_id, $quantity );
    }
}
```

### Example: LifterLMS Enrollment (Integration Base)
```php
<?php
namespace YourPlugin\CallToAction\LifterLMS;

class EnrollCourse extends BaseLifterLMS {
    
    public $key = 'lifterlms_enroll_course';
    public $version = 1;
    
    public function label(): string {
        return __( 'LifterLMS - Enroll in Course', 'your-plugin' );
    }
    
    public function fields(): array {
        return [
            'general' => array_merge(
                [
                    'courseId' => [
                        'type'      => 'postselect',
                        'label'     => __( 'Course', 'your-plugin' ),
                        'post_type' => 'course',
                        'multiple'  => false,
                        'priority'  => 5,
                    ],
                ],
                $this->get_common_fields()
            ),
        ];
    }
    
    protected function is_integration_active(): bool {
        return function_exists( 'llms' );
    }
    
    protected function execute_integration_action( \PopupMaker\Models\CallToAction $call_to_action, int $user_id, array $extra_args = [] ): bool {
        $course_id = $call_to_action->get_setting( 'courseId', 0 );
        
        if ( ! $course_id || ! $user_id ) {
            return false;
        }
        
        return llms_enroll_student( $user_id, $course_id );
    }
}
```

## 📂 File Structure

I'll organize the base classes properly:

```
{{PLUGIN_DIR}}/
├── classes/
│   └── CallToAction/
│       ├── {{INTEGRATION_NAME}}/
│       │   ├── Base{{INTEGRATION_NAME}}.php
│       │   ├── SpecificCTA1.php
│       │   ├── SpecificCTA2.php
│       │   └── SpecificCTA3.php
│       └── BaseRevenueAction.php (if revenue tracking)
```

## ✅ Benefits of Base Classes

✅ **Code Reuse**: Common functionality shared across related CTAs
✅ **Consistency**: Standardized behavior and field patterns  
✅ **Maintainability**: Changes to shared logic affect all child classes
✅ **Revenue Tracking**: Consistent attribution across CTA families
✅ **Testing**: Easier to test shared functionality once
✅ **Documentation**: Clear inheritance hierarchy

## 🧪 Testing Your Base Class

### Unit Test Example:
```php
<?php
class BaseRevenueActionTest extends WP_UnitTestCase {
    
    public function test_get_redirect_url() {
        $base = $this->getMockForAbstractClass( BaseRevenueAction::class );
        
        // Test checkout redirect
        $url = $base->get_redirect_url( 'checkout' );
        $this->assertStringContainsString( 'checkout', $url );
        
        // Test custom redirect
        $url = $base->get_redirect_url( 'custom', 'https://example.com' );
        $this->assertEquals( 'https://example.com', $url );
    }
}
```

## 📚 Best Practices

✅ **Do:**
- Use abstract methods for required functionality
- Provide sensible defaults in base methods
- Document inheritance relationships clearly
- Use traits for cross-cutting concerns (like revenue tracking)
- Implement proper error handling
- Follow WordPress coding standards

❌ **Don't:**
- Make base classes too specific to one use case
- Put business logic that varies by child class in base
- Create deep inheritance hierarchies
- Skip validation in base methods
- Ignore backward compatibility

Ready to create your base class family? Share the details and I'll generate the complete implementation! 🏗️