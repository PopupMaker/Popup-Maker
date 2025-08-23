# Popup Maker Gutenberg Layout Generator

Generates professional popup layouts as Gutenberg blocks ready for immediate use in the block editor.

## Overview

This command creates complete popup content using WordPress blocks that can be:
- Copied and pasted directly into the popup editor
- Imported as a JSON file for distribution
- Used as a starting point for customization

## Usage Examples

**Basic Usage:**
- `/pm-gutenberg-layout newsletter signup`
- `/pm-gutenberg-layout exit intent discount`
- `/pm-gutenberg-layout cookie notice`

**With Customization:**
- `/pm-gutenberg-layout newsletter --heading "Join Our Community" --button "Subscribe"`
- `/pm-gutenberg-layout discount --code "SAVE20" --timer "2 hours"`

## Available Layout Types

### 1. Newsletter Signup
**Usage:** `/pm-gutenberg-layout newsletter`

**Generated Content:**
```html
<!-- wp:group {"style":{"spacing":{"padding":{"all":"32px"}}},"backgroundColor":"white","layout":{"type":"constrained","contentSize":"400px"}} -->
<div class="wp-block-group has-white-background-color has-background" style="padding:32px">
  
  <!-- wp:image {"align":"center","width":"80px","height":"80px","sizeSlug":"thumbnail","style":{"border":{"radius":"50%"}}} -->
  <figure class="wp-block-image aligncenter size-thumbnail is-resized" style="border-radius:50%">
    <img src="{{NEWSLETTER_ICON}}" alt="Newsletter" width="80" height="80"/>
  </figure>
  <!-- /wp:image -->

  <!-- wp:heading {"textAlign":"center","level":2,"style":{"typography":{"fontSize":"24px","fontWeight":"600"},"spacing":{"margin":{"top":"16px","bottom":"8px"}}}} -->
  <h2 class="wp-block-heading has-text-align-center" style="margin-top:16px;margin-bottom:8px;font-size:24px;font-weight:600">{{HEADING:Join Our Newsletter}}</h2>
  <!-- /wp:heading -->

  <!-- wp:paragraph {"align":"center","style":{"color":{"text":"#666666"},"spacing":{"margin":{"bottom":"24px"}}}} -->
  <p class="has-text-align-center" style="color:#666666;margin-bottom:24px">{{DESCRIPTION:Get exclusive updates, tips, and special offers delivered to your inbox.}}</p>
  <!-- /wp:paragraph -->

  <!-- wp:html -->
  <form class="pum-newsletter-form" style="display:flex;flex-direction:column;gap:12px;">
    <input type="email" placeholder="{{EMAIL_PLACEHOLDER:Enter your email address}}" required style="padding:12px;border:1px solid #ddd;border-radius:4px;font-size:16px;">
    <button type="submit" style="background:{{BUTTON_COLOR:#007cba}};color:white;border:none;padding:12px 24px;border-radius:4px;font-size:16px;font-weight:600;cursor:pointer;">{{BUTTON_TEXT:Subscribe Now}}</button>
  </form>
  <!-- /wp:html -->

  <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"12px"},"color":{"text":"#888888"},"spacing":{"margin":{"top":"16px"}}}} -->
  <p class="has-text-align-center" style="color:#888888;margin-top:16px;font-size:12px">{{PRIVACY_TEXT:We respect your privacy. Unsubscribe anytime.}}</p>
  <!-- /wp:paragraph -->

</div>
<!-- /wp:group -->
```

### 2. Exit Intent Discount
**Usage:** `/pm-gutenberg-layout discount`

**Generated Content:**
```html
<!-- wp:group {"style":{"spacing":{"padding":{"all":"40px"}},"border":{"radius":"8px"}},"backgroundColor":"red","textColor":"white","layout":{"type":"constrained","contentSize":"450px"}} -->
<div class="wp-block-group has-white-color has-red-background-color has-text-color has-background" style="border-radius:8px;padding:40px">

  <!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"28px","fontWeight":"700"},"spacing":{"margin":{"bottom":"8px"}}}} -->
  <h1 class="wp-block-heading has-text-align-center" style="margin-bottom:8px;font-size:28px;font-weight:700">{{HEADLINE:Wait! Don't Miss Out}}</h1>
  <!-- /wp:heading -->

  <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"18px"},"spacing":{"margin":{"bottom":"24px"}}}} -->
  <p class="has-text-align-center" style="margin-bottom:24px;font-size:18px">{{OFFER_TEXT:Get {{DISCOUNT:20%}} off your order before you leave!}}</p>
  <!-- /wp:paragraph -->

  <!-- wp:group {"style":{"border":{"width":"2px","style":"dashed","color":"#ffffff"},"spacing":{"padding":{"all":"20px"}},"border":{"radius":"4px"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"center"}} -->
  <div class="wp-block-group" style="border-color:#ffffff;border-style:dashed;border-width:2px;border-radius:4px;padding:20px">
    <!-- wp:paragraph {"style":{"typography":{"fontSize":"14px","textTransform":"uppercase","letterSpacing":"1px"}}} -->
    <p style="font-size:14px;text-transform:uppercase;letter-spacing:1px">Use Code:</p>
    <!-- /wp:paragraph -->
    
    <!-- wp:paragraph {"style":{"typography":{"fontSize":"24px","fontWeight":"700","letterSpacing":"2px"}}} -->
    <p style="font-size:24px;font-weight:700;letter-spacing:2px">{{COUPON_CODE:SAVE20}}</p>
    <!-- /wp:paragraph -->
  </div>
  <!-- /wp:group -->

  <!-- wp:html -->
  <div class="pum-countdown-timer" style="text-align:center;margin:20px 0;">
    <div style="font-size:14px;margin-bottom:8px;">{{TIMER_TEXT:Offer expires in:}}</div>
    <div style="font-size:24px;font-weight:700;font-family:monospace;">{{COUNTDOWN_TIME:05:00}}</div>
  </div>
  <!-- /wp:html -->

  <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"24px"}}}} -->
  <div class="wp-block-buttons" style="margin-top:24px">
    <!-- wp:button {"backgroundColor":"white","textColor":"red","style":{"border":{"radius":"25px"},"typography":{"fontWeight":"600"}},"className":"is-style-fill"} -->
    <div class="wp-block-button is-style-fill">
      <a class="wp-block-button__link has-red-color has-white-background-color has-text-color has-background wp-element-button" style="border-radius:25px;font-weight:600">{{CTA_BUTTON:Claim My Discount}}</a>
    </div>
    <!-- /wp:button -->
  </div>
  <!-- /wp:buttons -->

</div>
<!-- /wp:group -->
```

### 3. Cookie Notice
**Usage:** `/pm-gutenberg-layout cookie`

**Generated Content:**
```html
<!-- wp:group {"style":{"spacing":{"padding":{"all":"20px"}},"position":{"type":"sticky","top":"0px"},"zIndex":"999"},"backgroundColor":"black","textColor":"white","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","alignItems":"center"}} -->
<div class="wp-block-group has-white-color has-black-background-color has-text-color has-background" style="padding:20px;position:sticky;top:0px;z-index:999">

  <!-- wp:paragraph {"style":{"typography":{"fontSize":"14px"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
  <p style="margin-top:0;margin-bottom:0;font-size:14px">{{COOKIE_TEXT:We use cookies to enhance your experience. By continuing to visit this site you agree to our use of cookies.}} <a href="{{PRIVACY_URL:#}}" style="color:#ffffff;text-decoration:underline;">{{PRIVACY_LINK:Privacy Policy}}</a></p>
  <!-- /wp:paragraph -->

  <!-- wp:buttons {"layout":{"type":"flex","flexWrap":"nowrap"},"style":{"spacing":{"blockGap":"8px"}}} -->
  <div class="wp-block-buttons" style="gap:8px">
    <!-- wp:button {"backgroundColor":"white","textColor":"black","style":{"border":{"radius":"4px"},"typography":{"fontSize":"14px"}},"size":"small"} -->
    <div class="wp-block-button has-custom-font-size is-small" style="font-size:14px">
      <a class="wp-block-button__link has-black-color has-white-background-color has-text-color has-background wp-element-button" style="border-radius:4px">{{ACCEPT_BUTTON:Accept}}</a>
    </div>
    <!-- /wp:button -->

    <!-- wp:button {"style":{"border":{"width":"1px","color":"#ffffff","radius":"4px"},"typography":{"fontSize":"14px"}},"textColor":"white","className":"is-style-outline","size":"small"} -->
    <div class="wp-block-button has-custom-font-size is-style-outline is-small" style="font-size:14px">
      <a class="wp-block-button__link has-white-color has-text-color wp-element-button" style="border-color:#ffffff;border-width:1px;border-radius:4px">{{DECLINE_BUTTON:Decline}}</a>
    </div>
    <!-- /wp:button -->
  </div>
  <!-- /wp:buttons -->

</div>
<!-- /wp:group -->
```

### 4. Product Announcement
**Usage:** `/pm-gutenberg-layout product`

**Generated Content:**
```html
<!-- wp:group {"style":{"spacing":{"padding":{"all":"32px"}}},"backgroundColor":"white","layout":{"type":"constrained","contentSize":"500px"}} -->
<div class="wp-block-group has-white-background-color has-background" style="padding:32px">

  <!-- wp:image {"align":"center","width":"120px","height":"120px","sizeSlug":"medium","style":{"border":{"radius":"8px"}}} -->
  <figure class="wp-block-image aligncenter size-medium is-resized" style="border-radius:8px">
    <img src="{{PRODUCT_IMAGE}}" alt="{{PRODUCT_NAME}}" width="120" height="120"/>
  </figure>
  <!-- /wp:image -->

  <!-- wp:heading {"textAlign":"center","level":2,"style":{"typography":{"fontSize":"26px","fontWeight":"600"},"spacing":{"margin":{"top":"20px","bottom":"12px"}}}} -->
  <h2 class="wp-block-heading has-text-align-center" style="margin-top:20px;margin-bottom:12px;font-size:26px;font-weight:600">{{PRODUCT_TITLE:Introducing Our Latest Product}}</h2>
  <!-- /wp:heading -->

  <!-- wp:paragraph {"align":"center","style":{"color":{"text":"#666666"},"spacing":{"margin":{"bottom":"24px"}}}} -->
  <p class="has-text-align-center" style="color:#666666;margin-bottom:24px">{{PRODUCT_DESCRIPTION:Discover the features that make this our most innovative product yet.}}</p>
  <!-- /wp:paragraph -->

  <!-- wp:list {"style":{"spacing":{"margin":{"bottom":"24px"}}}} -->
  <ul style="margin-bottom:24px">
    <!-- wp:list-item -->
    <li>{{FEATURE_1:Revolutionary design and functionality}}</li>
    <!-- /wp:list-item -->

    <!-- wp:list-item -->
    <li>{{FEATURE_2:Enhanced user experience}}</li>
    <!-- /wp:list-item -->

    <!-- wp:list-item -->
    <li>{{FEATURE_3:Industry-leading performance}}</li>
    <!-- /wp:list-item -->
  </ul>
  <!-- /wp:list -->

  <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
  <div class="wp-block-buttons">
    <!-- wp:button {"backgroundColor":"blue","style":{"border":{"radius":"6px"},"typography":{"fontWeight":"600"}}} -->
    <div class="wp-block-button">
      <a class="wp-block-button__link has-blue-background-color has-background wp-element-button" style="border-radius:6px;font-weight:600">{{CTA_BUTTON:Learn More}}</a>
    </div>
    <!-- /wp:button -->
  </div>
  <!-- /wp:buttons -->

  <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"12px"},"color":{"text":"#888888"},"spacing":{"margin":{"top":"16px"}}}} -->
  <p class="has-text-align-center" style="color:#888888;margin-top:16px;font-size:12px">{{FOOTER_TEXT:Available now with free shipping}}</p>
  <!-- /wp:paragraph -->

</div>
<!-- /wp:group -->
```

### 5. Video Popup
**Usage:** `/pm-gutenberg-layout video`

### 6. Contact Form
**Usage:** `/pm-gutenberg-layout contact`

### 7. Social Proof
**Usage:** `/pm-gutenberg-layout testimonial`

### 8. Survey Form
**Usage:** `/pm-gutenberg-layout survey`

## Variable System

Templates use `{{VARIABLE}}` or `{{VARIABLE:Default Value}}` syntax for customization:

- `{{VARIABLE}}` - Must be provided by user
- `{{VARIABLE:Default}}` - Uses default if not provided
- Command flags can override variables: `--heading "Custom Title"`

## Command Flags

**Common Flags:**
- `--heading "text"` - Override heading content
- `--description "text"` - Override description
- `--button "text"` - Override button text
- `--color "#hex"` - Override primary color
- `--size "small|medium|large"` - Template size

**Layout-Specific Flags:**
- Newsletter: `--email-placeholder`, `--privacy-text`
- Discount: `--code`, `--discount`, `--timer`
- Cookie: `--privacy-url`, `--accept`, `--decline`
- Product: `--features`, `--image`

## Output Format

The command provides output in multiple formats:

1. **Raw HTML** - Ready to paste into block editor
2. **JSON Export** - For importing into other sites
3. **Copy-to-Clipboard** - One-click copying
4. **Variable Summary** - List of available customizations

## Usage Notes

- Generated content uses WordPress core blocks for maximum compatibility
- All layouts are mobile-responsive by default
- Variables allow easy customization without code editing
- Content can be further customized in the block editor
- Includes semantic markup for accessibility
- Uses Popup Maker CSS classes for styling integration