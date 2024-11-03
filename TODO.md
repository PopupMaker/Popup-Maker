# TODO
  
## PM Pro branch

- [ ] Add proper current_data_versions() versioning.

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
