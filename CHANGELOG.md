# Popup Maker Changelog


## [v1.16.0 - 03/09/2021](https://github.com/PopupMaker/Popup-Maker/milestone/31)

* Feature: Add 'flush popup cache' link in admin bar menu [Issue #931](https://github.com/PopupMaker/Popup-Maker/issues/931)
* Improvement: Remove 'Tools -> System Info' page, use WP Site Health page instead. [Issue #862](https://github.com/PopupMaker/Popup-Maker/issues/862)
* Improvement: Accessibility forced focus can now focus main close button if nothing else is available. [Issue #943](https://github.com/PopupMaker/Popup-Maker/issues/943)
* Improvement: Updated admin form styles to match recent WP core changes. [Issue #707](https://github.com/PopupMaker/Popup-Maker/issues/707)
* Fix: Block previews failed to load for some 3rd party blocks. [Issue #868](https://github.com/PopupMaker/Popup-Maker/issues/868). Thanks @ndiego
* Fix: Bug preventing saving "Sitewide" cookie option unchecked. [Issue #642](https://github.com/PopupMaker/Popup-Maker/issues/642)
* Fix: Removed Action Scheduler library due to edge case issues with no reasonable long term solutions. [Issue #853](https://github.com/PopupMaker/Popup-Maker/issues/853)
* Fix: Added multiple sanity checks to code to prevent various edge cases resulting from improperly coded themes & plugins.
* Fix: Update integration with Contact Form 7 due to breaking changes in their recent v5.4 update. [Issue #946](https://github.com/PopupMaker/Popup-Maker/issues/946)

## [v1.15.0 - 01/12/2021](https://github.com/PopupMaker/Popup-Maker/milestone/30)

* Feature: Automatically enqueue popups when detected during page load [Issue #543](https://github.com/PopupMaker/Popup-Maker/issues/543)
* Improvement: Improvements to 'Extend' page [Issue #913](https://github.com/PopupMaker/Popup-Maker/issues/913)
* Improvement: Start working toward more consistent linking/documentation strategy
* Fix: Popup defaults() method only returns default values for checkbox fields [Issue #927](https://github.com/PopupMaker/Popup-Maker/issues/927)
* Fix: PUM_Telemetry triggers PHP warnings in (CLI) cron [Issue #919](https://github.com/PopupMaker/Popup-Maker/issues/919)
* Fix: pum-admin-bar Script Fails to Load [Issue #907](https://github.com/PopupMaker/Popup-Maker/issues/907) Thanks @fluxism!
* Fix: JS error: Uncaught TypeError: Cannot set property 'popups' of undefined [Issue #635](https://github.com/PopupMaker/Popup-Maker/issues/635)
* Fix: Undefined SCSS variable causing errors in building block components

## [v1.14.0 - 12/16/2020](https://github.com/PopupMaker/Popup-Maker/milestone/29)

* Improvement: Add a link to popups to admin bar [Issue #892](https://github.com/PopupMaker/Popup-Maker/issues/892)
* Improvement: Add a link to create a new popup to admin bar [Issue #892](https://github.com/PopupMaker/Popup-Maker/issues/892)
* Improvement: Add new welcome screen upon first activation [Issue #900](https://github.com/PopupMaker/Popup-Maker/issues/900)
* Improvement: Create example popup upon install [Issue #857](https://github.com/PopupMaker/Popup-Maker/issues/857)
* Improvement: Ensure subscribers table gets deleted if doing full deletion [Issue #895](https://github.com/PopupMaker/Popup-Maker/issues/895)
* Improvement: Add logging for DB table management and creation [Issue #879](https://github.com/PopupMaker/Popup-Maker/issues/879)
* Improvement: Set up continous integration for unit and integration tests [Issue #640](https://github.com/PopupMaker/Popup-Maker/issues/640)
* Improvement: Add filter to exclude blocks with isAllowedForBlockType [Issue #835](https://github.com/PopupMaker/Popup-Maker/issues/835)
* Fix: Backtick in settings page [Issue #904](https://github.com/PopupMaker/Popup-Maker/issues/904)

## v1.13.1 - 11/4/2020

* Fix: PHP error notice appears when submitting Gravity Forms without AJAX

## [v1.13.0 - 10/30/2020](https://github.com/PopupMaker/Popup-Maker/milestone/26)

* Feature: Conversion tracking [Issue #775](https://github.com/PopupMaker/Popup-Maker/issues/775)
* Feature: Bypass adblockers for tracking opens and conversions [Issue #783](https://github.com/PopupMaker/Popup-Maker/issues/783)
* Feature: Periodical suggestions to improve plugin usage [Issue #834](https://github.com/PopupMaker/Popup-Maker/issues/834)
* Improvement: Reduce clutter in All Popups table [Issue #878](https://github.com/PopupMaker/Popup-Maker/issues/878)
* Improvement: Switch tab navigation to NAV elements instead of H2 [Issue #818](https://github.com/PopupMaker/Popup-Maker/issues/818)
* Improvement: Set up PHPUnit for integration and unit tests [Issue #563](https://github.com/PopupMaker/Popup-Maker/issues/563)
* Improvement: Continuously deploy readme and screenshot changes [Issue #827](https://github.com/PopupMaker/Popup-Maker/issues/827)
* Fix: AJAX for Gravity Forms not setting cookies [Issue #706](https://github.com/PopupMaker/Popup-Maker/issues/706)
* Fix: Disabling asset cache causes form integrations not to load their assets [Issue #755](https://github.com/PopupMaker/Popup-Maker/issues/755)
* Fix: Form submission cookies are not being set for some form integrations [Issue #886](https://github.com/PopupMaker/Popup-Maker/issues/886)
* Fix: Some form integrations are calling both AJAX and PHP submission handlers [Issue #887](https://github.com/PopupMaker/Popup-Maker/issues/887)

## v1.12.0 - 09/29/2020

* Feature: Add the ability to turn on/off popups [Issue #544](https://github.com/PopupMaker/Popup-Maker/issues/544)
* Feature: Formidable Forms integration [Issue #750](https://github.com/PopupMaker/Popup-Maker/issues/750)
* Feature: New error log tab for viewing, downloading, and emptying the error log [Issue #575](https://github.com/PopupMaker/Popup-Maker/issues/575)
* Feature: New minimal popup theme for use with content created by page builders [Issue #759](https://github.com/PopupMaker/Popup-Maker/issues/759)
* Feature: Can now target WooCommerce "Subscriptions" account page. Thanks @bydrewpaul [Issue #849](https://github.com/PopupMaker/Popup-Maker/issues/849)
* Feature: Can now view all scheduled actions on the "Tools" page [Issue #859](https://github.com/PopupMaker/Popup-Maker/issues/859)
* Improvement: Add popup ID to the top of the popup editor [Issue #583](https://github.com/PopupMaker/Popup-Maker/issues/583)
* Improvement: Show default click trigger classes in trigger editor [Issue #584](https://github.com/PopupMaker/Popup-Maker/issues/584)
* Improvement: Show post ID in postselect drop-downs [Issue #660](https://github.com/PopupMaker/Popup-Maker/issues/660)
* Improvement: Label older form triggers/cookies as deprecated (or similar) [Issue #874](https://github.com/PopupMaker/Popup-Maker/issues/874)
* Fix: Form submission cookie not automatically setting "form" key [Issue #866](https://github.com/PopupMaker/Popup-Maker/issues/866)
* Fix: Trying to access 'private' key when the field is false error [Issue #873](https://github.com/PopupMaker/Popup-Maker/issues/873)
* Fix: _pum_subscribers table fails to create on MySQL 8.0.19+ due to VALUES keyword [Issue #876](https://github.com/PopupMaker/Popup-Maker/issues/876)

## v1.11.2 - 08/17/2020

* Fix: `wp_make_content_images_responsive` is deprecated, use `wp_filter_content_tags()` instead
* Fix: IE 11 does not support JS Promises
* Fix: Missing permission_callback on REST endpoint

## v1.11.1 - 07/22/2020

* Fix: Form submission cookies no longer set with Contact Form 7 5.2

## v1.11.0 - 06/25/2020

* Feature: Add new floating bar theme.
* Feature: New guided tour of popup editor for first time users.
* Feature: New href attribute on the popup_close shortcode for when setting the shortcode to use the `a` tag.
* Fix: Shortcode popup_close tag attribute not functioning properly.
* Tweak: Change popup_close tag setting to use a drop-down for more easily selecting which tag to use.
* Tweak: Improve explanation of cookies in new trigger modal.
* Tweak: Ensure all admin pages have visible page headings.
* Tweak: Simplify name and title fields in popup editor.
* Tweak: Add popup ID to quick links on All Popups.
* Tweak: Move CSS and JS for our admin bar node to external file.
* Tweak: Add our new optional telemetry system.

## v1.10.2 - 06/09/2020

* Fix: Popup Settings not working when WP Forms is active without forms
* Fix: Missing closing div in new [popup_cookie] shortcode.
* Fix: Shortcode popup_close tag attribute not functioning properly.

## v1.10.1 - 04/21/2020
* Fix: Typo in filter name caused extra p tags.
* Fix: Add wp version check to prevent calling block functions on older versions or classicpress.
* Fix: Font Awesome support now works for v4 fonts.

## v1.10.0 - 04/20/2020
* Feature: Display presets for top bar, bottom right slide-ins, full-screen popups & bottom left notifications to make it simple to get common setups done much quicker
* Feature: Popup Trigger inline text format for the block editor.
* Feature: Turn any block in Gutenberg block editor into a popup trigger.
* Feature: Font Awesome support added to close button text setting.
* Feature: Play a sound when a popup is opened. Choose from 5 included sounds or upload your own.
* Feature: Insert customizable [popup_cookie] shortcode on thank you pages when using non-integrated forms.
* Tweak: Add option to disable or adjust the padding-right added to body.
* Tweak: Remove Freemius integration from Popup Maker.
* Improvement: Detect file permission issues with Asset Caching functionality.
* Fix: Prevent popups from going off the screen when using center position for a tall popup.
* Fix: Bug in slide animation origin positioning for bottom or right origins.
* Fix: Bug where Middle Center caused tall popups to hang off the screen on small screens.
* Fix: Typo in admin editor CSS path.
* Fix: Bug on fresh installs where default theme's close position is wrong.

## v1.9.2 - 03/26/2020
* Tweak: Add support for WP 5.4's new method of adding custom fields to the nav menu editor.

## v1.9.1 - 02/13/2020
* Fix: JS error when MailChimp for WordPress was active but no forms on the page.

## v1.9.0 - 02/11/2020
* Feature: New Form Submission trigger with option to choose specific forms for integrated forms.
* Feature: New Form Submission cookie event with option to choose specific forms.
* Feature: New Close on Form Submission with optional delay.
* Feature: WP Forms integration.
* Feature: Caldera Forms integration.
* Feature: MailChimp for WordPress integration
* Improvement: Enhanced asset cache to identify issues with a site's filesystem.
* Improvement: Various changes to ensure PHP 7.4 compatibility.
* Improvement: Minimum PHP version updated to v5.6 to match WP core.
* Improvement: Simplified form integration interfaces to more easily support additional form plugins or custom integrations. Includes full AJAX & non-AJAX form support.
* Improvement: Added RTL CSS support.
* Improvement: Added new SVG admin menu icon which plays well with custom admin color schemes. Thanks @KZeni (Kurt Zenisek)
* Improvement: Simplified form integration apis.
* Improvement: Various performance improvements.
* Tweak: Remove unnecessary usage of esc_attr_e causing extra translation calls.
* Fix: Bug when accept language header is not supplied causes undefined index notice.
* Fix: Error caused by invalid post ID returned by CF7 when saving new forms.
* Fix: Bug when selecting more than 10 items in targeting rule post/page select fields.

## v1.8.14 - 10/24/2019
* Improvement: Updated nav menu editor walker class for adding custom fields to further improve compatibility.
* Fix: Ninja Forms popup actions missing.

## v1.8.13 - 10/11/2019
* Tweak: Added cap check to ensure only authorized users can access support debug text file. @Credit goes to Ilias Dimopoulos from Neurosoft S.A , RedyOps Team.

## v1.8.12 - 10/01/2019
* Improvement: Changed hook that we initialize Ninja Forms on so that it can be disabled from theme functions.php.
* Improvement: Don't load CF7 scripts if they are forced off.
* Improvement: Fixed some old options checks that were always being detected as true resulting in minor performance improvements.
* Tweak: Added nonce to the system info file download available under Popup Maker -> Tools and simplified the pum_actions system. @Credit goes to Ilias Dimopoulos from Neurosoft S.A , RedyOps Team.

## v1.8.11 - 08/18/2019
* Improvement: Trigger window resize event when popups open to fix issues with some sliders & JS sized content.
* Fix: Updated form value processing to prevent some edge cases where string values were converted to Infinity.
* Fix: Custom "already subscribed" messages in MailChimp integration were not working correctly.

## v1.8.10 - 07/06/2019
* Fix: PHP backward compatibility issue due to short array syntax usage.

## v1.8.9 - 07/04/2019
* Fix: Issue where popup titles wouldn't render.

## v1.8.8 - 06/30/2019
* Fix: Bug where red alert icons didn't go away when visiting the tabs from the "Extend" menu.

## v1.8.7 - 06/29/2019
* Tweak: Restricted the admin toolbar to only show under strict circumstances.
* Tweak: Updated available WooCommerce endpoints in our targeting conditions
* Fix: Issue with instance based shortcodes when asset caching was enabled but running on every request.

## v1.8.6 - 05/05/2019
* Fix: Typo in GDPR eraser that could sometimes result in errors when processing GDPR requests
* Fix: Added function exists check to prevent errors on WP 4.1

## v1.8.5 - 04/17/2019
* Tweak: Removed unused settings.
* Fix: Typo in method name that would generate errors in some extension migration routines.
* Fix: Issue when using class="" in our Popup Trigger shortcode would not get converted to classes on the element.
* Fix: Bug in JS due to missing default value.
* Fix: Bug older extensions caused by deprecated filter not getting loaded properly.

## v1.8.4 - 03/21/2019
* Improvement: Added content caching in the head to prevent second call to do_shortcode in the footer.
* Improvement: Added runtime model caching to reduce memory usage.

## v1.8.3 - 02/27/2019
* Fix: Added back deprecated function that got truncated previously.

## v1.8.2 - 02/25/2019
* Fix: Bug on older versions of PHP due to usage of [] rather than array().

## v1.8.1 - 02/22/2019
* Fix: Error on older versions of PHP when calling get_plugin_data on a plugin that wasn't installed.
* Fix: "Fatal error: Can not use method return value in write context" on older versions of PHP.

## v1.8.0 - 02/20/2019
* Feature: New popup theme settings:
  * New close button positions top center, bottom center, middle left & middle right.
  * New option to position close button outside of popup.
* Improvement: Add constant to disable logging.
* Improvement: Added complete uninstall option.
* Improvement: Added limited experimental support for Gutenberg editor when creating popups. Complete support in the works.
* Improvement: Added new unified alerts interface on PM dash pages. This will keep you up to date on required migration changes, new features & more.
* Improvement: Added new translation request for detected polyglot admins when their language doesn't have an updated Language Pack.
* Tweak: Removed option setting to 'Hide Admin Support Widget' which is no longer relevant.
* Tweak: Add constant to disable logging.
* Fix: Condition options for BuddyPress integration had values & labels switched.
* Fix: Bug with Gravity Forms Personal Data menu item missing.
* Fix: iOS Click overlay close not working.
* Fix: Analytics not working for themes with incorrect wp_footer usage.

## v1.7.30 - 09/06/2018
* Improvement: Further added methods to log unique messages only once.
* Tweak: Remove usage of Freemius.
* Fix: Added option to disable popups accessibility functionality to resolve some issues with focus trapping.
* Fix: Issues with log files growing too large. Max file size of 1MB and auto truncate to 250 lines now.
* Fix: Typo causing issues with Page Template condition.
* Fix: Typo in privacy link example text.
* Fix: Typo pointing to incorrect internal method call in new has_cookie method.
* Fix: Issues with fields not being readonly.

## v1.7.29 - 06/13/2018
* Improvement: Added new enabled() method for the PUM_AssetCache class that checks both is writable and not disabled.
* Improvement: Added option to disable just asset caching. This should help in the case your server is blocking the use of our JS from the /uploads/ folder with a 403 error.
* Fix: Bug caused by string representations of boolean values passed in our subscription forms.

## v1.7.28 - 06/10/2018
* Tweak: Improved validation of subscription form data and messaging.
* Fix: Bug with front end form serialization issue with single checkboxes (privacy field).

## v1.7.27 - 06/08/2018
* Improvement: Added additional variable checks to allow graceful failing during certain JS errors when page cache is out of date.

## v1.7.26 - 06/07/2018
* Fix: Add empty popups array to prevent errors due to page caching.

## v1.7.25 - 06/05/2018
* Tweak: Localized most variables earlier to prevent errors. Added in default values in case they do not get rendered to prevent fatal JS errors.
* Fix: Tweaked extension activation class to be compatible with PHP 5.2.
* Fix: Bug where boolean scalar values were changed to "" for json_encode.

## v1.7.24 - 06/04/2018
* Tweak: Updated subscriber table for existing sites that failed to add it properly before.

## v1.7.23 - 06/04/2018
* Improvement: Converted cookie privacy info to tabular rendering.
* Tweak: Improved update notice text.
* Fix: Issues with subscriber table not being created. Thanks @jnorell
* Fix: Bug not allowing more than one cookie for a trigger.
* Fix: Undefined index errors in shortcake/shortcode-ui integration.

## v1.7.22 - 05/25/2018
* Tweak: Updated Freemius library for GDPR optin support.
* Improvement: Made all popup loops more reliable.
* Fix: Error where objects were processed incorrectly.
* Fix: "Uncaught Error: Call to a member function get_setting() on boolean in /popup-maker/classes/AssetCache.php:314"

## v1.7.21 - 05/24/2018
* Tweak: Clear asset cache on settings save.
* Improvement: Check that post is singular to prevent Post Selected conditions from working on site index.
* Improvement: Remove jquery-cookie from assets as we no longer use or load it anywhere.
* Fix: Missing function errors if you don't have WordPress v4.9.6.
* Fix: Added better & safer json encoding function that properly sanitizes data for encoding to prevent empty strings for non english sites.

## v1.7.20 - 05/19/2018
* Feature: Support for GDPR Personal Data Exporter
* Feature: Support for GDPR Personal Data Eraser
* Feature: New privacy consent field for Subscription Forms for GDPR consent collection.
* Feature: GDPR privacy policy guide text added.
* Improvement: Updated dependency libs.
* Fix: Bug in subscriber tables if no popup ID was stored.

## v1.7.19 - 05/01/2018
* Version bump due to svn file add issues during last commit.

## v1.7.18 - 05/01/2018
* Fix: Typo in JS that may cause errors for some.

## v1.7.17 - 05/01/2018
* Improvement: Added popup option to disable automatic re-triggering of popup after non-ajax form submission.
* Improvement: Added notice when JS errors occur in Popup Maker admin interfaces with link to documentation for proper diagnosis & reporting.
* Tweak: Added asset cache reset on update of core version & db version.
* Tweak: Removed debug code.
* Tweak: Simplified the post type batch processor setup for extensions.
* Dev: Added base PUM_Extension_Activator class to standardize extension activation and various other things.

## v1.7.16 - 04/24/2018
* Tweak: Removed debug code.
* Fix: Issue with valueless shortcode attributes not processing properly.
* Fix: Issues where our scripts loaded before Ninja Forms scripts did and our integration didn't initialize.
* Dev: Added helper function to return array of shortcodes and data in usable format from any content.
* Dev: Added support for measure fields for shortcodes.

## v1.7.15 - 04/14/2018
* Improvement: Removed metadata from object models to reduce cache size as WordPress already has them cached.
* Tweak: Added new filter and corrected typo in existing ones for extension integrations.
* Fix: Bug for potentially missing variable.
* Fix: Bug when using WordPress older than v4.4 and viewing the subscribers table.
* Fix: Bug where google fonts didn't always get loaded correctly.
* Fix: Missing styles from Advanced Theme Builder due to misordering.

## v1.7.14 - 03/28/2018
* Fix: Obscure PHP error caused by method from interface was marked abstract in an abstract class inheriting the interface.
* Fix: Bug when jquery cookie is called from another plugin.
* Fix: Bug where form submit button triggered popup close when overlay click to close was enabled.
* Fix: Typo in previous patch for db_var not being updated properly.

## v1.7.13 - 03/27/2018
* Tweak: Added fallback methods for conditions using MobileDetect to prevent errors when for whatever reason it was not loaded properly.
* Tweak: Added value type check to prevent errors in popup data.
* Fix: Bug with accessibility forced focus when there is a link in the popup, causing the close button to focus the link before closing.
* Fix: Bug that caused issues with MC extensions JS loading properly.
* Fix: Added fail-safe in case variables were not properly declared on page for mce-buttons.js.
* Fix: Set a deprecated option on new installs for backward compatibility issues.
* Fix: Selector correction in z-index setting application.

## v1.7.12 - 03/21/2018
* Improvement: Added option to disable the shortcode ui.
* Tweak: Removed private popup type links from the nav menu editor.
* Fix: Bug with long term cached assets causing JS errors on nginx servers.
* Fix: Bug with support for custom popup z-index setting.
* Fix: Bug where NF loaded before Popup Maker and form actions were missing.
* Fix: Bugs in close delay settings for form integrations. Was in ms but needed to be in seconds.
* Fix: Bug where Yoast SEO plugin shows popups in the xml sitemaps and showing Yoast metabox on popup editor.

## v1.7.11 - 03/14/2018
* Fix: Bug where Middle Center option wouldn't stay selected after saving.
* Fix: Bug with incorrect field dependency for custom height & scrollable options.

## v1.7.10 - 03/14/2018
* Improvement: Further improved compatibility with shortcodes that echo/print rather than return content.
* Fix: Bug where cookies wouldn't always be set in Edge & Safari due to cookie path including the root url.
* Fix: Bug that changed the default tag for popup_trigger & popup_close shortcodes.
* Fix: Bug where extra close buttons didn't always work correctly.
* Fix: Removal of deprecated function that triggered warnings in PHP 7.2.

## v1.7.9 - 03/14/2018
* Improvement: Replaced usage of pumSerializeForm with pumSerializeObject which is more reliable.
* Fix: Bug where deprecated directory reference causes popup html not to render properly breaking popups that should have worked otherwise.
* Fix: Bug where checkbox defaults continuously applied making it impossible to uncheck them.

## v1.7.8 - 03/13/2018
* Improvement: Added output buffering to early calls to do_shortcode to prevent premature output in the head.
* Improvement: Added sanity checks to make sure only valid popup objects are used in some older template functions.

## v1.7.7 - 03/13/2018
* Fix: Removed jQuery.serializeJSON functionality which was unused and causing conflicts with WooCommerce.
* Fix: SSL Issues due to not specifying protocol.
* Fix: Error caused by invalid popup object being used in function.
* Fix: PHP 5.2 compatibility issue.

## v1.7.6 - 03/12/2018
* Fix: Undid previous changes from 1.7.1 and reworked in a new way to be backward compatible with existing extensions.

## v1.7.5 - 03/12/2018
* Fix: Sticky Popup Maker settings checkboxes that wouldn't uncheck after save.

## v1.7.4 - 03/12/2018
* Fix: Invalid method declaration error introduced by v1.7.2 patch to Shortcode core class.

## v1.7.3 - 03/12/2018
* Fix: Error due to usage of __CLASS__ rather than $this.
* Fix: Edge case where function returns can't be used inside empty().

## v1.7.2 - 03/12/2018
* Fix: Initialization variable wasn't set to true early enough.

## v1.7.1 - 03/12/2018
* Fix: Empty value errors.
* Fix: Missing function for 3rd party plugin backward compatibility (Elementor).

## v1.7.0 - 03/12/2018
This was a monster update, our largest to date in terms of improving existing functionality, reducing maintenance and the time it takes to implement new features in the future.

Noticeably there are a lot of interface changes with this version as we simplified from having many meta boxes in the popup editor to a new single panel interface.

Lastly we now have include our extendable subscription forms right in the free version. We currently don't provide support for mail/service providers in the free version, but have opened up our form API in the hopes that 3rd party developers will help us fill that gap. Don't fret though, submissions are stored in a custom table for retroactive syncing to lists or export (not yet available).

* Feature: Subscriber forms now included without a paid extension.
  * Provider API for easily extending forms to work with 3rd party providers.
  * New shortcode with tons of options built in.
  * Stores subscribers into a new custom table for import into your favorite system at a later time.
* Feature: Front end asset overhaul, now uses cached static assets.
  * All front end assets now combined into single js & css file.
  * Custom styles are now saved along with all core & extension styles eliminating inline style blocks.
  * Reduction of footprint means massively improved loading performance.
  * Dynamic file creation allows for some awesome upcoming features.
  * Now completely compatible with plugins like Autoptimize (Thanks Frank).
* Feature: Support for nearly any form, including non ajax forms.
  * Helper functions to integrate your 3rd party form plugins quickly.
  * Show thank you popups, set cookies & close popups with a delay after success (requires code).
  * Automatically reopen popup forms after refresh from a form submission.
* Improvement: Lots of text, label & description changes to be more intuitive.
* Improvement: Better 3rd party plugin support including page builders:
  * Popup post type is now public.
  * Better support for 3rd party shortcodes which require extra assets loaded (JS/CSS).
* Improvement: Adding trigger now gives optional choices to create a cookie, rather than being automatic.
* Improvement: New Popup Settings tabbed interface to help make settings more intuitive & easy to find on one screen.
  * Now all popup settings are stored in a single meta key reducing DB clutter.
* Improvement: New Popup Maker Settings tabbed interface to help make settings more intuitive & easy to find on one screen.
* Improvement: New Popup preview mode.
* Improvement: Better page builder support by changing popup post type arg for public to true.
* Improvement: Resource reduction & optimization.
  * Added class autoloader for core and extensions.
  * Greatly simplified code base & internal API structures.
  * Converted many internal APIs to use passive loading.
  * Added internal caching.
* Improvement: Integrated [WPJSF](https://github.com/danieliser/WP-JS-Form-Sample-Plugin) lib for easier maintenance and quicker updates of our admin forms.
* Improvement: Various improvements to smart select fields (jQuery select2) including:
  * Allow multiple page/post selections without reopening/searching again.
  * Properly highlights & shows selected items after save/reload.
  * Paginated/scroll based loading of more results over ajax.
  * Now shows list of recent "items" immediately upon clicking the field rather than requiring search.
* Improvement: Admin asset handling
  * Modularized admin assets for easier debugging & maintenance.
* Improvement: Popup Trigger shortcode can now use custom popup IDs.
* Improvement: Added new batch processing system for upgrades and other processes.
* Improvement: Removed a lot of old code.
* Improvement: Rebuilt Shortcode UI that should be more reliable.
* Improvement: Addressed most all PHP 7 notices.
* Improvement: iOS scrolling issue fixes.
* Improvement: Added support for KingComposer.
* Tweak: Support for subdirectory sites having their own sitewide cookies.
* Fix: Incorrect BuddyPress condition labels
* Fix: Bug when WPML isn't yet available.


## v1.6.7 - Rolled into v1.7.0
* Fix: Typo in JS event name prevented forceFocus for popups.
* Fix: JS errors when Marionette JS library on page without Ninja Forms.
* Fix: WPML missing variable errors.

## v1.6.6 - 07/29/2017
* Fix: Bug with closing forms using newest version of Gravity Forms.

## v1.6.5 - 07/16/2017
* Tweak: Added new popup class for theme names. Thanks @bluantinoo.
* Fix: Bug in menu popups save and render functionality not working correctly.
* Fix: Finally found issue where randomly assets tab checkboxes wouldn't uncheck & save properly.
* Fix: Sanitized active tab key against whitelist.
* Fix: Errors in w3c validation scans from form meta fields.
* Fix: Settings asset label mismatch.

## v1.6.4 - 07/07/2017
* Imporvement: Reworked all form integrations to be as DRY as possible making it more reliable.
* Tweak: Added sanity check in case previous filter mucks up the $item object variable in menu item filters causing warnings.
* Tweak: Disabled the open count & sorting when Popup Analytics is activated.
* Tweak: Added NF datepicker CSS fix.
* Tweak: Added media type to head styles to force optimization plugins to keep them in order.
* Tweak: Reverted to older method of click trigger assignment to better work with multiple popups on one trigger with conditions.
* Fix: Bug caused by use of a function some users host blocked.
* Fix: Bug caused by debug mode being enabled with a form success cookie.
* Fix: Bug when Gravity Form was not in popup but triggered a thank you popup.
* Fix: Bug with GForms closing popup after submission.
* Fix: Bug where CF7 Forms with required fields trigger popup to close without being filled properly.

## v1.6.3 - 05/19/2017
* Fix: Removed 3rd parameter from number_format as it only accepts 1, 2 or 4 arguments, not 3 per php.net documentation.

## v1.6.2 - 05/18/2017
* Fix: Bug caused by rounding to whole numbers in opacity values.

## v1.6.1 - 05/17/2017
* Improvement: Major improvements to the Shortcode UI (builder & in editor previews). Now supports true live rendering of PM shortcodes. This will be most apparent in upcoming extension updates.
* Fix: Forced decimal formatting in CSS output functions in case of locale changes to formatting. Fix thanks to @timhavinga


## v1.6.0 - 04/26/2017
* Feature: Added Gravity Forms direct integrations.
  * Close popup with delay when Gravity Form is submitted.
  * Trigger a thank you popup when Gravity Form is submitted.
  * Set cookies easily when the Gravity Form is in a popup.
* Feature: Added Contact Form 7 (CF7) direct integrations.
  * Close popup with delay when contact form 7 is submitted.
  * Trigger a thank you popup when contact form 7 is submitted.
  * Set cookies easily when the CF7 form is in a popup.
  * Forced CF7 assets to load when used in a popup on the off chance they don't automatically.
* Tweak: Increased action priority for condition registration in case plugins register post types late, such as PODs.
* Tweak: Moved popup theme styles to a very late position in the head to prevent them from being overwritten when minifying CSS.
* Fix: Bug where you couldn't enter values higher than the rangeslider max.
* Fix: JS error when creating a cookie before a trigger exists.

## v1.5.8 - 04/04/2017
* Fix: Error when extensions were active due to null values for checkboxes.


## v1.5.7 - 03/27/2017
* Improvement: Added option to disable the menu editors in case of a conflict.
* Fix: Forced 100% width on page select boxes to prevent them from being too small.
* Fix: Bug where checkboxes were not staying checked.

## v1.5.6 - 03/16/2017
* Feature: Admin Bar helper tool to assist in getting proper click trigger selectors easily.
* Improvement: Further tweaks for maximium compatibitlity with nav menu editor.
* Improvement: Added Popup option to nav menu editor Screen Options to easily hide them.
* Fix: Updated the freemius-sdk to fix an obscure secured php core function error.

## v1.5.5 - 03/13/2017
* Improvment: Used generic Nav Menu Editor Walker classes for better support. This should remove the notices from other plugins as well.
* Fix: Bug that causes click triggers to lag.

## v1.5.4 - 03/13/2017
* Fix: Typos in conditions.
* Fix: Moved class_exists checks to better handle possible missing class errors.

## v1.5.3 - 03/13/2017
* Improvement: Added a catch for any triggers not initialized at page load.
* Fix: Typo in multi check field template that led to admin JS errors.

## v1.5.2 - 03/10/2017
* Improvement: Added option to disable the admin bar Popups helper menu item.
* Improvement: Simplified the nav menu editor modification class to reduce un-needed translation strings.
* Fix: Added check for missing class in the nav menu editor walker classes.

## v1.5.1 - 03/09/2017
* Fix: PHP 5.2 Compatibility issue.

## v1.5.0 - 03/08/2017
* Feature: Position popups based on the click trigger. Tooltips & Popovers are now possible.
* Feature: Added new conditions for targeting children & grandchildren / ancestors of selected content.
* Feature: Added new settings to the Nav Menu editor to choose a popup that a menu item will trigger.
* Feature: Addded option to Disable on Tablets as well as mobile phones.
* Feature: Added WooCommerce is_wc_endpoint_url conditions.
* Feature: Added new click selector presets for quicker targeting & more user friendly.
* Feature: Added a new debug mode. Including:
  * Admin Bar with manual open, close & cookie resets for loaded popups.
* Improvement: New global JS functions for easily working with popups. PUM.open(123), PUM.close(123), PUM.clearCookies(123).
* Improvement: Added inline links to docs for various settings.
* Improvement: Reworked popup analytics to improving response times and decreasing server loads.
  * Moved Analytic tracking to the WP-API with a new endpoint.
  * Reduced number of queries by 75% for analytics tracking.
  * Added option to disable it entirely if absolutely neccessary.
* Improvement: Many improvements to JavaScript including object caching.
* Tweak: Creating a new trigger will automatically create a cookie and assign it if one doesn't exist.
* Tweak: Mobile Disable was also applied to tablets, now only to phones.
* Tweak: Removed readonly from rangesliders to make the fact you can manually enter any value more intuitive.
* Tweak: Use CSS to display a popup immediately if has trigger: auto open: delay 0.
* Tweak: Clicking elements in the visual theme preview will now scroll to the relevant section of settings.
* Fix: Bug in admin when editing a trigger, cookie field didn't repopulate properly.
* Fix: Bug where rangeslider values can be set to strings.
* Fix: Bug where links in the close button were not triggered even when do_default was enabled.
* Fix: Bug with scrollbar "flashing" when popup opens.

## v1.4.21 - 12/12/2016
* Feature: Added option to disable popup on mobile to comply with [Google's new interstital policy](https://webmasters.googleblog.com/2016/08/helping-users-easily-access-content-on.html).
* Tweak: Added additional paramter to the pum_popup_get_conditions filter.
* Tweak: Fixed possible false init of NF integration if NF is not enabled.
* Tweak: Added CSS override for Ninja Forms datepickers to properly layer them above popups.

## v1.4.20 - 10/13/2016
* Feature: Added [Ninja Forms](https://wppopupmaker.com/grab/ninja-forms?utm_source=readme-changelog&utm_medium=text-link&utm_campaign=Readme&utm_content=ninja-forms-features) success actions for opening & closing popups.
* Feature: Added new cookie event for successful submission of a [Ninja Forms](https://wppopupmaker.com/recommends/ninja-forms) form.
* Improvement: Added wp.hooks JS library, allowing actions & filters via our plugin JS.
* Tweaks: Added various admin css tweaks.

## v1.4.19 - 9/30/2016
* Feature: Added a do_default parameter to the trigger & close shortcodes. This allows making close buttons that also download a file.
* Improvement: Added support for JS (advanced) conditions & condition processing after checking for cookies.
* Improvement: Upgraded from jQuery-Cookie (modified) to JS-Cookie (modified) for more flexibility.
* Fix: Bug where color didn't update properly when first clicked in theme editor.
* Fix: Added prefix to admin pages to prevent conflicts.
* Fix: Removed usage of deprecated filter.

## v1.4.18 - 8/15/2016
* Fix: Bug with PHP 5.2 compatibility.
* Fix: Added missing post_type index condition callback.

## v1.4.17 - 8/14/2016
* Fix: Bug caused by using return value in write context.

## v1.4.16 - 8/14/2016
* Feature: New Condition: Pages: With Template. Thanks @cedmund.
* Feature: Option to Disable reposition on window resize/scroll.
* Improvement: Enable Visual Composer for Popups by default (VC 4.8+). Thanks @NoahjChampion.
* Improvement: Replaced usage of gif hex code with loading of an actual tracking gif to prevent security scanners from throwing false positives.
* Improvement: Changed default analytics response with a 204 no content heading, saving the need to load & return a tracking gif.
* Fix: Missing condition value bug fixed by adding sanity checks to conditions on get.
* Fix: Auto Height checkbox wouldn't stay unchecked.
* Fix: CSS class pum-open-overlay wasn't being removed from HTML element on popup close causing issues for next popup.
* Fix: Error in JS due to shortcodes: Uncaught Error: Syntax error, unrecognized expression.
* Fix: Issue where some custom post types not working with conditions.

## v1.4.15 - 7/20/2016
* Improvement: Only showed the aria-label attribute if the label will be shown.
* Tweak: Updated the Freemius SDK.
* Tweak: Updated the #popmake-{ID} selector to work at the end of a link.
* Fix: Bug where stackable popups would lose their scroll bar after one was closed.

## v1.4.14 - 7/14/2016
* Feature: Links with the url #popmake-{ID} will now trigger a popup when clicked. Links with this href will work similar to elements with the popmake-{ID} class.

## v1.4.13 - 6/26/2016
* Feature: Added 12 of the most commonly needed BuddyPress content types & targeting conditions. Target any BP content type. Now full support for BuddyPress.
* Tweak: Moved a few functions from the plugins_loaded action to the init action for minor compatibility benefits.
* Tweak: Removed Popup & Popup Theme Meta Revisioning as it adds unneeded clutter to the DB.

## v1.4.12 - 6/24/2016
* Improvement: Reduced translatable strings from 569 total to 439 which is about a 23% reduction which will reduce work for our translators.
* Tweak: Removed the welcome page and associated CSS, images etc. This cleans up some useless strings for translation.
* Fix: Bug where add_new cookie wasn't properly replaced for the first trigger.

## v1.4.11 - 6/10/2016
* Feature: New conditions for targeting posts & taxonomy by ID.
* Improvement: Added link to Conditions Documentation to the Conditions editor.
* Tweak: Namespaced jQuery.serializeObject to prevent conflicts with other plugins/themes in the admin editor.
* Fix: Bug on add new page/post and during post update.
* Fix: Bug in edit this theme link on page load.

## v1.4.10 - 5/23/2016
* Feature: Added Do Default option to the click triggers. Allows a triggers default browsers behavior to occur and still open a popup, such as a file link.
* Improvement: Added additional links to the theme editor for better visibility and to guide users there.
* Tweak: Older methods are only loaded when needed, this also removes usage of a deprecated filter.
* Tweak: All Pages now includes Home Page / Front Page.
* Tweak: A default click trigger is always added. (Like pre v1.4)
* Fix: Low z-index caused issues when the overlay is disabled.
* Fix: Bug where none animation couldn't be re-opened.
* Fix: Cleaned up issues allowing popup post type to be added directly to menus and sitemaps.
* Fix: Bug where auto height checkbox would not stay checked.

## v1.4.9 - 5/01/2016
* Improvement: Reduced front end queries by over 85%. Avgerage sites should now only have 2 to 3.
* Improvement: Added caching enhancements for even better performance on servers with page, object & query caching.
* Improvement: Added a fully namespaced version of Select2 for compatiblitiy while other plugins await updating. Will gracefully fall back to the non namespaced version when it no longer causes issues.
* Fix: Undefined 'amd' JS errors.
* Fix: The "Use Your Theme" font option was not working correctly.
* Fix: Removed leftover console.logs in our JavaScript.

## v1.4.8 - 4/27/2016
* Improvement: Sandboxed Select2 v4 since it breaks other plugins when loaded properly. v4 adds accessiblity enhancements that we are not going to sacrifice for compatiblity with plugins who have not updated to include it. This provides a safe alternative in the meantime.
* Tweak: Removed extra shortcode files.
* Tweak: Allow popup Click Triggers to target another popups close button. Close one triggers another etc.
* Fix: Bug caused by pum_shortcode_ui not loading properly everywhere.
* Fix: Bug in popup position calculation when the popup used Fixed Position and Disable Overlay

## v1.4.7 - 4/24/2016
* Improvement: Removed the old styles dropdown as it is no longer needed.
* Improvement: Added check for old versions of Select2 and replace them with latest which is backward compatible.
* Fix: Bug that caused Close button delay to not show the close button.
* Fix: Replaced usage of <% style JS template with <# & {{ for PHP asp_tags compatibility.

## v1.4.6 - 4/22/2016
* Fix: Bug in new post editor JS.
* Fix: Added filter to override permissions for upgrade routines.

## v1.4.5 - 4/21/2016
* Fix: Replaced all usage of static:: for PHP 5.2 compatiblity.
* Fix: Forced the latest version of Select2 to load on Popup Maker admin pages in the case that an older version was enqueued.

## v1.4.4 - 4/20/2016
* Fix: Version Bump to fix upgrade issues.

## v1.4.3 - 4/20/2016
* Fix: Removed extra whitespace before opening php tags.

## v1.4.2 - 4/20/2016
* Fix: Bug in popup maker deprecated filter caused by no defaults passed.

## v1.4.1 - 4/20/2016
* Fix: Bug in popup maker upgrade class for older versions of PHP.

## v1.4 - 4/20/2016
* Feature: Added basic analytics. Tracks how many unique opens each popup has.
* Feature: Added new Popup Maker shortcodes button to the editor with visual previews.
* Feature: Added option to reset popup open counts demand.
* Feature: New add / remove targeting conditions UI.
* Feature: Conditions can now be negative as well as grouped as AND / OR.
* Feature: New conditions for targeting posts & cpt by taxonomy. IE Posts with Tag / Category.
* Feature: New add / remove triggers UI that allows multiple of the same trigger per popup.
* Feature: Added a new add / remove cookies UI that manages cookies separate from triggers.
* Feature: Added 5 new built in themes.
* Feature: Added support for pods content types.
* Feature: Added full screen front end previews for admins and editors.
* Feature: Added additional WooCommerce conditions such as on checkout.
* Improvement: Added CSS resets to all core popup elements to ensure a reliable look.
* Improvement: Popups are now rendered with their own overlay. This allows the popup to scroll inside the overlay.
* Improvement: Cookie names can now be set to anything, including cookies from other plugins.
* Improvement: Triggers now support checking more than one cookie.
* Improvement: Accessibility & screen reader enhancements to the popups and admin.
* Improvement: Auto Focus the first element in the popup when it opens for screen readers.
* Improvement: Better JavaScript encapsulation and organization.
* Improvement: Added support for Select2 smart dropdowns for admin interfaces.
* Improvement: Added a more reliable upgrade routine system.
* Improvement: Added an option to disable popup taxonomies if not in use.
* Improvement: Added more reliable usage tracking via [Freemius](https://freemius.com/wordpress/).
* Tweak: Updated extensions page and added a list of plugins that work well with Popup Maker.
* Fixed: Super annoying fixed position checkbox glitch.
* Fixed: Missing check for disabled google fonts before loading them.
* Fixed: Bug where hidden about pages showed up when certain admin menu editing plugins were active.
* Fixed: Bug where default theme was not properly created on install.
* Fixed: Bug where non utf-8 characters were used in the name field and caused JS errors.
* Fixed: Bug where popup triggers inside their own popup would cause it to close and reopen when clicked.
* Dev: Introduced PUM_Fields a settings API that support _.js Templ fields.
* Dev: Added new action 'pum_styles' that can be used to render custom CSS.
* Dev: Added new PUM_Popup class with nearly all methods built in.
* Dev: Introduced new prefix pum_ rather than popmake_.

**v1.4 Change Set Statistics:**
365 Commits / 53 Major & Minor Issues Closed.
285 changed files with 20,437 additions and 3,607 deletions.

## v1.3.9 - 10/14/2015
* Feature: New shortcode - [popup_close] allows adding custom close buttons/text. Ex. [popup_close] Click Me [/popup_close].
* Improvement: Added SASS/SCSS files for the site & admin styles.
* Improvement: Added better support for current & legacy versions of Visual Composer.
* Improvement: Added check for preventClose class on a popup just before closing. If found the popup won't close.
* Fix: Fixed bug in theme editor that caused Google Font variants to not show up.
* Fix: Fixed bug in CSS generation where Google Font URL would become corrupted and cause a 404.
* Fix: Fixed bug where fixed position would show unchecked even if it was checked.
* Fix: Fixed bug in CSS that caused popup to appear below site on mobile.
* Fix: WP Multi Site: Fatal Error.

## v1.3.8 - 9/29/2015
* Fix: Updated links to documentation.
* Fix: Removed exploitable bug allowing script execution in the admin. Discovered 9/29/15 - Patched 9/29/15

## v1.3.7 - 9/21/2015
* Feature: Added support for Visual Composer to popups. (Backend Editor Only). Works Perfectly with Responsive Popups.
* Tweak: Disable position fixed on mobile screens for responsive popups.
* Tweak: Improved UI with better popup formats selection.
* Fix: Bug with default theme not properly being created.
* Fix: Bug where default & theme formats were overridden in the WP Editor.
* Fix: Bug with default theme not being used for [popup] shortcode.
* Fix: Bug with loading Google Fonts properly.
* Fix: Errors generated by incorrectly formatted colors in the editor.
* Fix: Bug with targeting conditions for categories.
* Fix: Bug in positioning left & right values. Credit to @invik for the solution.

## v1.3.6 - 8/25/2015
* Confirmed WP v4.3 compatibility.
* Tweak: Default theme is automatically used if a popup does not have one assigned.
* Fix:  UI bug where fixed position checkbox wouldn't stay checked.
* Fix: Bug with Theme Default values & v1.2 values not being merged.

## v1.3.5 - 8/18/2015
* Tweak: Corrected missing keys for required script checks.
* Fix: Error message caused by non array value from get_post_custom.
* Fix: Removed missing variable.
* Fix: Text corrections.

## v1.3.4 - 8/12/2015
* Fix: Added px to font-size & line-height.

## v1.3.3 - 8/12/2015
* Fix: Added current_action fallback function for older versions of WP.
* Fix: Theme CSS rendering incorrect font settings.

## v1.3.2 - 8/10/2015
* Tweak: Pause HTML5 Videos when popup closes.
* Fix: Prefixed several functions that collided with some themes.
* Fix: Changed default Close Height & Width to 0/auto.

## v1.3.1 - 8/8/2015
* Fix: Error in get_called_class alternate function for PHP 5.2
* Fix: Force theme css builder to check for empty themes.
* Fix: Bug where z-indexes were incorrectly set.

## v1.3 - 8/7/2015
* Feature: Added unlimited themes functionality to the core.
* Feature: Allow disabling of event.prevendDefault() for on click events by adding do-default class.
* Feature: Added support for session based cookies.
* Feature: Add Height & Width options to Close Button for better control.
* Feature: Theme styling is now rendered in the head via inline CSS with an option to disable in the case that popup styles have been moved to the theme stylesheet.
* Feature: Delay showing the close button after the popup opens. Set the delay in ms.
* Feature: Added stackable popups option to show more than one popup at a time. ( A stackable popup won't close other popups when its opened. )
* Feature: Added WooCommerce Targeting Conditions.
* Feature: Added new system info tab on the tools page to make debugging faster.
* Tweak: Change default responsive mobile size to 95%.
* Tweak: Change default z-index to 1999999999.
* Tweak: Add ability to pass a callback to the popmake('close') method.
* Tweak: Add namespace to click open event ('click.popmakeOpen').
* Tweak: Add $default arg to popmake_get_popup_meta_group function.
* Tweak: Auto close content tags using balanceTags().
* Tweak: Added new popmake_get_popup(), get_the_popup_ID(), popmake_get_the_popup_ID(), popmake_the_popup_ID() functions.
* Tweak: Check if popup is already open before auto opening.
* Tweak: Add ajax="true" to gravity forms shortcodes if not there.
* Tweak: Make auto open cookie key optional.
* Tweak: Disable fixed position for responsive sizes.
* Tweak: Compensate for Admin Bar when visible.
* Tweak: Added options to disable Support & Share admin widgets.
* Tweak: Added new filter popmake_popup_default_close_text to allow filtering of popup close text.
* Tweak: Added close text override on a per popup basis. New option under Close Settings.
* Tweak: Choosing a responsive size will automatically disable fixed position & scrollable content.
* Tweak: Unneeded data attributes are now removed to clean up html.
* Tweak: Meta has now been compressed into serialized arrays for popups and themes.
* Tweak: Added new Meta Field management class as a step toward a more maintainable code base.
* Fix: Add option to disable moving of popup to end of <body>.
* Fix: Corrected input type under Click-Open Settings meta box.
* Fix: Description cleanup for popup location.
* Fix: Correct French translation file name.
* Fix: Rewrote popup loop to not overwrite global $post breaking some content shortcodes.
* Fix: Bug when clicking publish with empty name field publish becomes unclickable again.
* Fix: Sitewide cookie option will not stay unchecked.
* Fix: Bug where popup & popup_theme meta was stored with other post types on revision.
* Fix: Bug in the popup_trigger shortcode with $content not being rendered properly.

## v1.2.2
* Added (string) typecast to prevent errors in wp_localize_script when passing integers.
* Added 100% French & Hungarian translations.
* Added partial German translation.
* Moved template.php require line to load for both admin and front end for use in ajax responses.
* Changed order of admin pages to allow extensions to load before settings/help/tools pages on menu.
* Added troubleshooting FAQ to readme.
* Added version to JS object for backward compatibility checks.
* Added check for preventOpen class before opening. This class will prevent the popup from opening.
* Corrected minWidth variable name.
* Added namespace to the auto open cookie event.
* Changed the last open trigger to use the jQuery object instead of xpath.
* Added an isScrolling variable to detect when the browser is actively scrolling.
* Checked isScrolling before adding overflow styles to the HTML element to prevent glitching.
* Temporarily removed the grow animations due to removal of Greensock Animation Platform.
* Removed Greensock Animation Platform dependancy.

## v1.2.1
* Fixed bug caused by null value passed to JS data attr.

## v1.2
* Added full screen preview for themes when editing using the Preview button.
* Added full screen preview for popup when editing using the Preview button.
* Added new shortcode 'popup_trigger' that allows users to easily add the correct popmake- class. Accepts id, tag & class parameters.
* Updated GSAP JS plugin to latest version.
* Removed jQuery.gsap.js usage.
* Added fallback list of Google Fonts for when API is unavailable.
* Setup extensions page to use a static list of extensions for the time being.
* Updated API url.
* Removed Popmake_Admin_Notices class as it was unused.
* Fixed bug where share metabox wouldn't stay hidden.
* Added function to prevent deletion of default theme.
* Fixed bug which caused Popup Maker menu to show to all users.

## v1.1.10
* Fixed invalid argument bug passed to google font foreach.
* Fixed CSS box-sizing cross browser support.

## v1.1.9
* Added %'s to reponsive sizes in size dropdown.
* Remove usage of the_content and the_content filters.
* Fixed responsive sizes.

## v1.1.8
* Fixed issue with admin menu position collisions.
* Fixed issue with banner not staying dismissed.
* Removed dependency jQuery.cookie
* Fixed bug in auto open when cookie was set before delay was up.
* Added new setCookie JS event. Used to manually set a popups cookies. Usage jQuery('#popmake-123').trigger('popmakeSetCookie');
* Added new z-index override values. This helps with theme compatibility and future multi popup capability.
* Added Blog Index support. Available under targeting conditions 'On Blog Index' & 'Exclude On Blog Index'.
* Added better responsive image handling.
* Added Admin Debug option for popups.
* Changed jquery-ui-position collission property to none to solve positioning issues.
* Disabled Popup Maker JS & CSS when no popups detected to load.
* Added new function popmake_enqueue_scripts() which allows manual enqueuing of scripts and styles.

## v1.1.7
* Fixed undefined function popmake_default_settings.
* Fixed specific pages not saving properly.
* Now removes ?autoplay parameter from Videos preventing them from playing again without interaction.

## v1.1.6
* Fixed bug in js not setting correct CSS value for min-width.
* Changed close link element tag from a > span.

## v1.1.5
* Fixed bug when clicking add selected buttons.
* Changed how popmake_popup_is_loadable works. It is now more organized and readable.
* Added 2 new Targeting Conditions: Search & 404.

## v1.1.4
* Fixed bug in scrollable content styles.
* Fixed bug in admin JS for duplicate input names.
* Changed Powered By Setting to Off by Default.
* Changed default permissions required to use theme builder.
* Fixed bug in targeting conditions.

## v1.1.3
* Fixed some incorrect links to resources and kb.
* Removed Auto Open Promotional Material ( as it is now included ).

## v1.1.2
* Further enhancements to ensure proper checking of Auto Open Enabled.

## v1.1.1
* Fixed bug in JS that didn't properly check if Auto Open was enabled.

## v1.1
* Added Importer for Easy Modal v2 - Availabe under Tools -> Import
* Added Easy Modal v2 Compatibility Option - Available under Settings -> Misc (This will allow all of your existing eModal classes to open the proper Popup once imported)
* Added custom selector functionality - Availabe on Modal editor (This will allow you to use your own css selectors that when clicked will trigger the popup to open. Ex. #main-menu li.menu-item-3 would cause the corresponding menu item to trigger that popup)

## v1.0.5
* Fixed bug caused by changes in v1.0.4.

## v1.0.4
* Admin UI Adjustments & Tweaks.
* Fixed bug in removing specific post types.
* Reformatted Code.
* Fixed incorrect variable.

## v1.0.3
* Fixed bug with recursive filter.
* Fixed bug caused by typo.
* Fixed bug in JS for removing specific post type posts.

## v1.0.2
* Resized Extension page images to load quicker on extensions page.
* Added last_open_popup proerty to popmake jQuery function.
* Resized Extension page images to load quicker on extensions page.
* Fixed misc Admin Styles.
* Corrected support links.
* Fixed Bug in Meta boxes on settings page.
* Renamed files appropriately.
* Added new section callback for settings API.
* Fixed small glitch in Opt In for Credit Link.

## v1.0.1
* Removed links to getting started from "Dashboard" Admin Menu.
* Added Line Height Setting to Both Title and Close, Allowing Perfect Circles for close button.
* Updated admin styles.
* Misc Admin changes, including new filters/hooks for upcoming extensions.

## v1.0.0
* Initial Release
