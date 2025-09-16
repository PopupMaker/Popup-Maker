# Popup Maker Field Types Reference

## Overview

Comprehensive guide to all field types available in the Popup Maker ecosystem. Use this reference for implementing new features, CTAs, conditions, and triggers.

## Field Structure

### Base Field Configuration

```php
[
    // Core properties
    'id'                => 'field_id',           // Field identifier (required)
    'type'              => 'text',               // Field type (required)
    'label'             => __('Label', 'domain'), // Display label
    'desc'              => __('Description', 'domain'), // Help text

    // Common options
    'std'               => 'default_value',      // Default value
    'placeholder'       => 'Enter value...',    // Placeholder text
    'required'          => false,               // Required field
    'disabled'          => false,               // Disabled state
    'readonly'          => false,               // Read-only state
    'class'             => 'custom-class',      // CSS class
    'priority'          => 10,                  // Display order
    'private'           => false,               // Hide from UI

    // Dependencies (conditional display)
    'dependencies'      => [
        'other_field' => 'value',               // Show when other_field = value
        'trigger'     => ['exit_intent', 'scroll'], // Array values
    ],

    // Documentation
    'doclink'           => 'https://docs.url',  // Help link
]
```

## Core Field Types

### Text Input Fields

#### 📝 `text`

Basic text input field

```php
'field_name' => [
    'type'        => 'text',
    'label'       => __('Text Field', 'domain'),
    'placeholder' => 'Enter text...',
    'size'        => 'regular', // small|regular|large
    'std'         => 'default',
]
```

#### ✉️ `email`

Email validation input

```php
'email_field' => [
    'type'        => 'email',
    'label'       => __('Email', 'domain'),
    'placeholder' => 'user@example.com',
    'required'    => true,
]
```

#### 🔗 `url`

URL validation input

```php
'website' => [
    'type'        => 'url',
    'label'       => __('Website URL', 'domain'),
    'placeholder' => 'https://example.com',
]
```

#### 🔒 `password`

Password input field

```php
'api_key' => [
    'type'        => 'password',
    'label'       => __('API Key', 'domain'),
    'placeholder' => 'Enter API key...',
]
```

#### 📞 `tel`

Telephone input

```php
'phone' => [
    'type'        => 'tel',
    'label'       => __('Phone Number', 'domain'),
    'placeholder' => '+1 (555) 123-4567',
]
```

#### 🔍 `search`

Search input field

```php
'search_term' => [
    'type'        => 'search',
    'label'       => __('Search', 'domain'),
    'placeholder' => 'Search...',
]
```

#### 👁️‍🗨️ `hidden`

Hidden input field

```php
'hidden_value' => [
    'type' => 'hidden',
    'std'  => 'stored_value',
]
```

### Numeric Fields

#### 🔢 `number`

Number input with validation

```php
'quantity' => [
    'type'  => 'number',
    'label' => __('Quantity', 'domain'),
    'min'   => 1,
    'max'   => 100,
    'step'  => 1,
    'std'   => 1,
]
```

#### 📏 `range`

Range slider input

```php
'opacity' => [
    'type'  => 'range',
    'label' => __('Opacity', 'domain'),
    'min'   => 0,
    'max'   => 100,
    'step'  => 5,
    'std'   => 80,
]
```

#### 🎚️ `rangeslider`

Enhanced range slider with labels

```php
'scroll_percentage' => [
    'type'             => 'rangeslider',
    'label'            => __('Scroll Percentage', 'domain'),
    'min'              => 0,
    'max'              => 100,
    'step'             => 5,
    'std'              => 50,
    'unit'             => '%',
    'allowReset'       => true,
    'initialPosition'  => 25, // Different from std for display
]
```

#### 📐 `measure`

Value with unit selector

```php
'delay' => [
    'type'  => 'measure',
    'label' => __('Delay', 'domain'),
    'std'   => '1000ms',
    'units' => [
        'ms' => __('Milliseconds', 'domain'),
        's'  => __('Seconds', 'domain'),
    ],
    'min'   => 0,
    'max'   => 30000,
    'step'  => 100,
]
```

### Selection Fields

#### 📋 `select`

Dropdown selection

```php
'animation' => [
    'type'    => 'select',
    'label'   => __('Animation', 'domain'),
    'options' => [
        'fade'        => __('Fade', 'domain'),
        'slide'       => __('Slide', 'domain'),
        'bounce'      => __('Bounce', 'domain'),
        'custom'      => __('Custom', 'domain'),
    ],
    'std'        => 'fade',
    'multiple'   => false,
    'searchable' => false,
    'select2'    => true, // Enable Select2 enhancement
]
```

#### 🎯 `multiselect`

Multiple selection dropdown

```php
'post_types' => [
    'type'     => 'multiselect',
    'label'    => __('Post Types', 'domain'),
    'options'  => [
        'post'    => __('Posts', 'domain'),
        'page'    => __('Pages', 'domain'),
        'product' => __('Products', 'domain'),
    ],
    'multiple' => true,
    'as_array' => true,
    'std'      => ['post', 'page'],
]
```

#### 📻 `radio`

Radio button selection

```php
'position' => [
    'type'    => 'radio',
    'label'   => __('Position', 'domain'),
    'options' => [
        'top'    => __('Top', 'domain'),
        'center' => __('Center', 'domain'),
        'bottom' => __('Bottom', 'domain'),
    ],
    'std' => 'center',
]
```

#### ☑️ `checkbox`

Single checkbox

```php
'enabled' => [
    'type'         => 'checkbox',
    'label'        => __('Enable Feature', 'domain'),
    'heading'      => __('Feature Options', 'domain'), // Optional section heading
    'checkbox_val' => 1, // Value when checked
    'std'          => false,
]
```

#### ✅ `multicheck`

Multiple checkboxes

```php
'methods' => [
    'type'    => 'multicheck',
    'label'   => __('Methods', 'domain'),
    'options' => [
        'mouseleave' => __('Mouse Leave', 'domain'),
        'lostfocus'  => __('Lost Focus', 'domain'),
        'backbutton' => __('Back Button', 'domain'),
    ],
    'std' => ['mouseleave', 'lostfocus'],
]
```

### Content Fields

#### 📄 `textarea`

Multi-line text input

```php
'message' => [
    'type'        => 'textarea',
    'label'       => __('Message', 'domain'),
    'placeholder' => 'Enter message...',
    'rows'        => 5,
    'cols'        => 50,
    'allowHtml'   => true, // Allow HTML content
]
```

#### 🌐 `html`

Static HTML content

```php
'info_text' => [
    'type'    => 'html',
    'content' => '<p>' . __('Information about this feature.', 'domain') . '</p>',
]
```

#### 🎨 `color`

Color picker

```php
'background_color' => [
    'type'         => 'color',
    'label'        => __('Background Color', 'domain'),
    'std'          => '#ffffff',
    'disableAlpha' => false, // Allow transparency
]
```

#### 📅 `date`

Date picker

```php
'start_date' => [
    'type'  => 'date',
    'label' => __('Start Date', 'domain'),
    'std'   => date('Y-m-d'),
]
```

### WordPress Integration Fields

#### 📰 `postselect`

WordPress post selector

```php
'target_post' => [
    'type'        => 'postselect',
    'label'       => __('Target Post', 'domain'),
    'post_type'   => 'post', // or 'page', 'product', etc.
    'multiple'    => false,
    'placeholder' => __('Select post', 'domain'),
]
```

#### 🏷️ `taxonomyselect`

WordPress taxonomy selector

```php
'categories' => [
    'type'        => 'taxonomyselect',
    'label'       => __('Categories', 'domain'),
    'taxonomy'    => 'category',
    'multiple'    => true,
    'placeholder' => __('Select categories', 'domain'),
]
```

#### 👤 `userselect`

WordPress user selector

```php
'author' => [
    'type'        => 'userselect',
    'label'       => __('Author', 'domain'),
    'multiple'    => false,
    'placeholder' => __('Select user', 'domain'),
]
```

#### 🔧 `objectselect`

Generic object selector

```php
'custom_object' => [
    'type'        => 'objectselect',
    'label'       => __('Custom Object', 'domain'),
    'object_type' => 'post_type',
    'object_key'  => 'custom_post_type',
    'multiple'    => false,
]
```

#### 🔌 `customselect`

Custom endpoint selector

```php
'external_data' => [
    'type'        => 'customselect',
    'label'       => __('External Data', 'domain'),
    'entityType'  => 'custom_entity',
    'apiEndpoint' => '/wp-json/custom/v1/entities',
    'multiple'    => false,
]
```

### Special Fields

#### 🔑 `license_key`

License key input with validation

```php
'license' => [
    'type'           => 'license_key',
    'label'          => __('License Key', 'domain'),
    'license_status' => 'valid', // valid|invalid|expired
    'messages'       => [
        'valid'   => __('License is active.', 'domain'),
        'invalid' => __('License is invalid.', 'domain'),
    ],
    'expires' => '2025-12-31',
]
```

#### 🔘 `button`

Action button

```php
'save_settings' => [
    'type'        => 'button',
    'label'       => __('Save Settings', 'domain'),
    'button_type' => 'submit', // submit|button|reset
    'class'       => 'button-primary',
]
```

#### 📋 `tokenselect`

Token-based selection

```php
'tags' => [
    'type'        => 'tokenselect',
    'label'       => __('Tags', 'domain'),
    'options'     => [
        'tag1' => 'Tag One',
        'tag2' => 'Tag Two',
    ],
    'multiple'    => true,
    'placeholder' => __('Add tags...', 'domain'),
]
```

#### 🎯 `heading`

Section heading

```php
'section_heading' => [
    'type' => 'heading',
    'desc' => __('Advanced Settings', 'domain'),
]
```

#### 🪝 `hook`

WordPress hook execution

```php
'custom_hook' => [
    'type' => 'hook',
    'hook' => 'pum_custom_field_output',
]
```

### Field Layout & Organization

#### Section Structure

```php
$fields = [
    'general' => [
        'field1' => [...],
        'field2' => [...],
    ],
    'advanced' => [
        'field3' => [...],
        'separator1' => [
            'type' => 'separator',
            'desc' => __('Advanced Options', 'domain'),
        ],
        'field4' => [...],
    ],
];
```

#### Field Dependencies

```php
'conditional_field' => [
    'type'         => 'text',
    'label'        => __('Conditional Field', 'domain'),
    'dependencies' => [
        'trigger_type' => 'specific_value',
        'enabled'      => true,
        'methods'      => ['method1', 'method2'], // Array matching
    ],
]
```

## Field Validation & Sanitization

### Built-in Sanitization

Each field type has automatic sanitization:

- `text`, `email`, `url`: `sanitize_text_field()`
- `textarea`: `sanitize_textarea_field()`
- `number`, `range`: `intval()` or `floatval()`
- `checkbox`: `boolean` conversion
- `select`, `radio`: Option validation
- `multicheck`, `multiselect`: Array validation

### Custom Sanitization

```php
// Filter for custom sanitization
add_filter('pum_{$field_type}_sanitize', function($value, $args, $fields, $values) {
    // Custom sanitization logic
    return $sanitized_value;
}, 10, 4);
```

### Custom Field Types

```php
// Register custom field type
add_action('pum_{$custom_type}_field', function($args) {
    // Custom field rendering
    echo '<input type="custom" ...>';
});

// Custom sanitization
add_filter('pum_{$custom_type}_sanitize', function($value, $args) {
    return sanitize_custom_value($value);
}, 10, 2);
```

## Best Practices

### 🎯 Field Design

1. **Consistent Naming**: Use descriptive, consistent field IDs
2. **Logical Grouping**: Group related fields in sections
3. **Clear Labels**: Use actionable, descriptive labels
4. **Help Text**: Provide helpful descriptions
5. **Smart Defaults**: Set sensible default values

### 🔒 Security

1. **Always Sanitize**: Every field value must be sanitized
2. **Validate Options**: Ensure select values exist in options
3. **Escape Output**: Use `esc_attr()`, `esc_html()` for display
4. **Nonce Fields**: Include nonces for form submissions

### ⚡ Performance

1. **Lazy Loading**: Use dependencies to show/hide fields
2. **Efficient Options**: Avoid expensive queries in options
3. **Caching**: Cache expensive dropdown data
4. **Minimal Fields**: Only include necessary fields

### 🎨 UX Guidelines

1. **Progressive Disclosure**: Use dependencies for advanced options
2. **Field Validation**: Provide real-time feedback
3. **Clear Hierarchy**: Use headings and separators
4. **Responsive Design**: Ensure mobile compatibility

## TypeScript Types (Modern Fields)

```typescript
// Import field types
import type { FieldProps } from "@popup-maker/fields";

// Use in components
interface MyComponentProps {
  settings: CallToAction["settings"];
  updateSettings: (settings: Partial<CallToAction["settings"]>) => void;
}

// Field configuration
const fieldConfig: PartialFieldProps = {
  id: "my_field",
  type: "text",
  label: "My Field",
  value: settings.my_field,
  onChange: (value) => updateSettings({ my_field: value }),
};
```

## Migration Notes

### Legacy → Modern

- `std` → `default`
- `desc` → `help`
- `checkbox_val` → automatic boolean handling
- `select2` → `searchable`
- Manual rendering → React components

### PHP → TypeScript

- PHP arrays → TypeScript interfaces
- `pum_{$type}_callback` → React components
- Server-side validation → Client + server validation

---

_Generated for Popup Maker ecosystem. Last updated: 2025-08-24_
