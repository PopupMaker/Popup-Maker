# TODO

## ASAP
- [ ] We need to add detection of EDD & WooCommerce purchase urls within popups, flag a global admin notice to upgrade to PM Pro+ to enable purchase tracking & more.

- [ ] We need to probably ensure `?cta` links are not prefetched via plugins like WP Rocket or even core WP.
- [ ] We need to allow changing the `?cta=` query arg to something else.


- [ ] Make PopupMaker\Base\Model\Post copatible replacement for PUM_Abstract_Model_Post. Currently these might be divergent making moving Popups to the new one difficult without breakage. Alternatively we can migrate CTA's to the old model and then namespace it.
- [ ] Update `pum_install_example_popups` and others with new better examples in `includes/pum-install-functions.php`

## PM Pro branch

-   [ ] Add proper current_data_versions() versioning.

-   [ ] Add vanity CTA urls (by slug)

-   [ ] In core add new Go Pro page?
        -- popup-maker/classes/Services/Upgrader.php:70 - Update this to point to the correct upgrade page url.
        -- popup-maker/classes/Services/Connect.php:168 - Update this to point to the correct upgrade page url.

-   [ ] In core update Upsells
        -- popup-maker/classes/Upsell.php:72

-   [ ] Add licensing
-   [ ] Add plugin installer mechanisms
-   [ ] Add "Connect"
-   [ ] Add "Addon installer"
-   [x] Merge first extension (forced interaction)
-   [ ] Merge second extension (advanced theme builder)

## PM Pro Migrations

### Forced Interaction

#### v1.0.0

-   meta: `popup_close_disabled` -
    -   type: `bool`
    -   default: `false`
    -   Stored as `0` or `1`
    -   Accessor: popmake_get_popup_meta_group['close']('disabled')

#### v1.1.0 - Never Released

-   meta: popup_settings[ 'close_disabled' ]
    -   type: `bool`
    -   default: `false`l

#### Deleteable data

-   delete_option( 'pum_fi_version_data' );
