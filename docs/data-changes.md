# Popup Maker Data Structure Evolution (v1.3 → v1.20.0)

**Analysis Date**: August 2024  
**Purpose**: Document data structure changes for proper versioning & migration system  
**Method**: Direct code analysis across major/minor version tags  

## Executive Summary

This document traces the evolution of data storage mechanisms in Popup Maker from v1.3 (earliest tagged version) through v1.20.0, documenting changes to inform migration strategy development.

**Key Findings**:
- **Storage Method**: Consistent use of WordPress options table with `popmake_settings` key
- **Database Version**: Tracked via `POPMAKE_DB_VERSION` / `$DB_VER` (1 → 8)
- **Major Transitions**: v1.4 class refactor, v1.7.0 architectural overhaul, v1.20.0 modern refactor
- **Options Framework**: Evolved from direct `get_option()` → `PUM_Options` → `PUM_Utils_Options`

---

## Version Analysis

### v1.3 (Baseline Analysis)

**Storage Infrastructure:**
- **Main Option Key**: `popmake_settings`
- **Storage Method**: WordPress `get_option()` / `update_option()` directly
- **Database Version**: `POPMAKE_DB_VERSION = '1.0'`
- **Plugin Version**: `POPMAKE_VERSION = '1.3'`

**Core Data Structures:**
- **Popups**: Custom post type `popup` with post meta storage
- **Themes**: Custom post type `popup_theme` with post meta storage  
- **Settings**: Stored in `popmake_settings` option
- **Taxonomies**: `popup_category`, `popup_tag`

**Key Functions:**
```php
// Direct option access pattern
$options = get_option('popmake_settings', array());
```

**Post Type Configuration:**
```php
'supports' => array('title', 'editor', 'revisions', 'author')
```

---

### v1.4 (Class Structure Introduction)

**Major Changes:**
- **New Classes**: Introduction of PUM_* class structure
- **Database Version**: Maintained at `POPMAKE_DB_VERSION = '1.0'`
- **Freemius Integration**: Analytics and licensing system

**Architecture Evolution:**
- Maintained backward compatibility with v1.3 data structures
- Added class-based initialization
- Settings still stored in `popmake_settings` option

---

### v1.5.0 (Settings Enhancement)

**Settings Expansion:**
- **License Key Handling**: New license management system
- **Debug Mode Settings**: Enhanced debugging capabilities
- **Database Version**: Increased to `POPMAKE_DB_VERSION = '6'` 

**Storage Patterns:**
```php
// Settings still accessed via direct option calls
$settings = get_option('popmake_settings', array());
```

**New Settings Categories:**
- License management
- Debug mode configuration
- Extended analytics options

---

### v1.7.0 (Architectural Overhaul) ⚡

**MAJOR REFACTOR - Critical Data Changes:**

**Database Version**: `$DB_VER = 6` (using new static property system)

**Class Architecture Revolution:**
- **New Main Class**: `Popup_Maker` with static properties
- **Autoloader System**: `pum_autoloader()` function
- **Options Abstraction**: `PUM_Options::get()` / `PUM_Options::update()`

**Data Access Evolution:**
```php
// OLD (v1.5.0 and earlier):
$value = get_option('popmake_settings')['key'];

// NEW (v1.7.0+):
$value = PUM_Options::get('key', 'default');
```

**Infrastructure Changes:**
- Plugin constants now handled via static class properties
- Deprecation of many direct option functions
- Introduction of centralized options management

**Migration Implications:**
- Options access methods changed (backward compatibility maintained)
- Class initialization pattern changed
- Static property system for plugin configuration

---

### v1.8.0 (Repository Pattern)

**Database Version**: `$DB_VER = 8` (**Major increment**)

**New Architecture Components:**
- **Repository Pattern**: 
  ```php
  $this->popups = new PUM_Repository_Popups();
  $this->themes = new PUM_Repository_Themes();
  ```
- **Options Framework**: Transitioned to `PUM_Utils_Options::get()`
- **Privacy Integration**: GDPR compliance system
- **New Helper Function**: `pum()` replacing deprecated `PopMake()`

**Storage Pattern Evolution:**
```php
// Options now accessed via Utils class
PUM_Utils_Options::get('key', 'default')
```

**Data Structure Additions:**
- Privacy compliance data structures
- Repository-based data access patterns

---

### v1.9.0 (Enhanced Options System)

**Database Version**: `$DB_VER = 8` (maintained)

**Options System Enhancement:**
- **New Methods**: `delete()`, `merge()`, `remap_keys()`
- **Enhanced Validation**: Better option validation and sanitization
- **PHP Version Requirement**: Minimum PHP 5.6

**New Option Methods:**
```php
PUM_Utils_Options::delete(['key1', 'key2']);
PUM_Utils_Options::merge($new_options);  
PUM_Utils_Options::remap_keys(['old_key' => 'new_key']);
```

**Migration Tools:**
- Built-in option remapping functionality
- Bulk option deletion capabilities
- Enhanced option merging system

---

### v1.10.0 (Feature Expansion)

**Database Version**: `$DB_VER = 8` (maintained)

**New Features:**
- **Cookie Shortcode**: `PUM_Shortcode_PopupCookie::init()`
- Enhanced shortcode system

---

### v1.11.0 (Telemetry & Scheduling)

**Database Version**: `$DB_VER = 8` (maintained)

**Major Additions:**
- **Telemetry System**: `PUM_Telemetry::init()`
- **Action Scheduler**: `/packages/action-scheduler/action-scheduler.php`
- **Scheduled Actions**: New utility functions for task scheduling

**Infrastructure Evolution:**
- Background task processing capability
- Usage analytics and telemetry collection
- Enhanced plugin ecosystem integration

---

### v1.16.3 (Modernization)

**Database Version**: `$DB_VER = 8` (maintained)

**Environmental Requirements:**
- **WordPress**: Minimum version raised to 4.9
- **PHP**: Maintained at 5.6
- **Package Name**: Changed from `POPMAKE` to `PopupMaker`

---

### v1.18.2 (Code Modernization)

**Database Version**: `$DB_VER = 8` (maintained)

**Code Standards Evolution:**
- **Array Syntax**: `array()` → `[]` throughout codebase
- **Plugin Headers**: Added `Requires PHP` and `Requires at least` headers
- **Extensions System**: Restructured with `new PUM_Extensions()`

---

### v1.19.1 (Filesystem Integration)

**Database Version**: `$DB_VER = 8` (maintained)

**New Utilities:**
- **Filesystem Functions**: `functions/utils/filesystem.php`
- **Modern PHP Syntax**: `dirname(__FILE__)` → `__DIR__`

---

### v1.20.0 (Modern Architecture) 🚀

**Database Version**: `$DB_VER = 8` (maintained)

**MAJOR ARCHITECTURAL MODERNIZATION:**

**New Configuration System:**
```php
function popup_maker_config() {
    return [
        'name' => __('Popup Maker', 'popup-maker'),
        'version' => '1.20.0',
        'option_prefix' => 'popup_maker',  // NEW PREFIX STRUCTURE
        'min_wp_ver' => '4.9.0',
        'min_php_ver' => '5.6.0',
        'future_wp_req' => '6.5.0',        // FUTURE PLANNING
        'future_php_req' => '7.4.0',
    ];
}
```

**Bootstrap Architecture:**
- `bootstrap.legacy.php` - Backward compatibility
- `bootstrap.php` - Modern initialization
- `includes/class-popup-maker.php` - Reorganized main class

**Options Class Evolution:**
```php
// Property name changes in PUM_Utils_Options:
$_prefix → $prefix
$_data → $data

// Function signature improvements:
get($key, $default) → get($key, $default_value)
```

**Namespace Introduction:**
```php
\PopupMaker\check_prerequisites()  // New namespace usage
```

**Migration Implications:**
- New configuration system may require migration of config-dependent code
- Bootstrap file structure changes affect extension initialization
- Option prefix structure potentially changed for new installations

---

## Data Migration Strategy Recommendations

### Database Version Tracking

**Current System:**
```php
$DB_VER = 8  // Last incremented in v1.8.0
```

**Recommended Enhancement:**
```php
function pum_current_data_versions() {
    return [
        'db_version' => 8,
        'options_structure' => 3,    // v1.7, v1.8, v1.20 changes
        'config_system' => 2,        // v1.20 config system
        'bootstrap' => 1,            // v1.20 bootstrap
    ];
}
```

### Critical Migration Points

1. **v1.3 → v1.7.0**: Options access pattern changes
2. **v1.7.0 → v1.8.0**: Database schema changes (DB_VER 6→8)  
3. **v1.8.0+**: Repository pattern adoption
4. **v1.20.0**: Configuration system overhaul

### Option Key Consistency

**Primary Storage Key**: `popmake_settings` (consistent v1.3 → v1.20.0)

**Access Pattern Evolution:**
```php
// v1.3-v1.5: Direct access
get_option('popmake_settings')

// v1.7.0: PUM_Options abstraction  
PUM_Options::get('key')

// v1.8.0+: Utils abstraction
PUM_Utils_Options::get('key')

// v1.20.0: Property name changes, same interface
PUM_Utils_Options::get('key', 'default_value')
```

### Recommended Migration Functions

```php
function pum_migrate_data_version($from_version, $to_version) {
    $migrations = [
        '6_to_8' => 'pum_migrate_v6_to_v8',
        'options_v2_to_v3' => 'pum_migrate_options_structure',
        'config_v1_to_v2' => 'pum_migrate_config_system',
    ];
    
    // Execute relevant migrations
}

function pum_get_current_data_version() {
    return [
        'db_version' => get_option('pum_db_version', 1),
        'options_structure' => get_option('pum_options_version', 1),
        'config_system' => get_option('pum_config_version', 1),
    ];
}
```

---

## Summary

**Storage Consistency**: The core storage mechanism (`popmake_settings` option) remained remarkably consistent throughout the plugin's evolution.

**Access Pattern Evolution**: The major changes were in *how* data is accessed rather than *where* it's stored, providing good backward compatibility.

**Migration Priorities**:
1. **v1.8.0 Database Changes**: The DB_VER increment from 6→8 represents the most significant data structure change
2. **v1.20.0 Modernization**: Configuration system changes may require migration for extensions/customizations
3. **Options Access Patterns**: Multiple evolution points for options access methods

**Recommended Actions**:
- Implement multi-dimensional version tracking beyond just `DB_VER`
- Create migration routines for v1.8.0 database changes
- Plan compatibility layer for v1.20.0 configuration system
- Develop automated migration testing for major version transitions

This analysis provides the foundation for implementing a comprehensive `current_data_versions()` system with proper migration pathway support.