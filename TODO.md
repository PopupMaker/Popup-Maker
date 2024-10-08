# TODO

## Develop

- [ ] Check for any low hanging fruits in support, github & feature request board on site.
- [ ] Add global configs for PLANNED_PHP_VERSION and PLANNED_WP_VERSION
  - When set, the plugin will display a notice in the admin area if the current PHP or WP version is below the planned version, warning the user that they should update their PHP or WP version before the next minor release.
  - [ ] <https://github.com/GravityKit/GravityView/blob/develop/includes/class-gravityview-compatibility.php#L203-L233>

- [ ] Test all PHPCS changes so far, and push current release to WordPress.org as v1.20.0.
  
## PM Pro branch

- [ ] Move \PopupMaker\Plugin\Core to simply \PopupMaker\Core as there is no need for the Plugin namespace.

- [ ] In core add new Go Pro page?
  -- popup-maker/classes/Services/Upgrader.php:70 - Update this to point to the correct upgrade page url.
  -- popup-maker/classes/Services/Connect.php:168 - Update this to point to the correct upgrade page url.

- [ ] In core update Upsells
  -- popup-maker/classes/Upsell.php:72

- [ ] Add licensing
- [ ] Add plugin installer mechanisms
- [ ] Add "Connect"
- [ ] Add "Addon installer"
- [x] Merge first extension (forced interaction)
- [ ] Merge second extension (advanced theme builder)

## PM Pro Migrations

### Forced Interaction

#### v1.0.0

- meta: `popup_close_disabled` -
  - type: `bool`
  - default: `false`
  - Stored as `0` or `1`
  - Accessor: popmake_get_popup_meta_group['close']('disabled')

#### v1.1.0 - Never Released

- meta: popup_settings[ 'close_disabled' ]
  - type: `bool`
  - default: `false`l

#### Deleteable data

- delete_option( 'pum_fi_version_data' );
