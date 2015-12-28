<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class PUM_Popup_Cookies_Metabox
 *
 * @since 1.4
 */
class PUM_Popup_Cookies_Metabox {
	/**
	 * Initialize the needed actions & filters.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_metabox' ) );
		add_action( 'print_media_templates', array( __CLASS__, 'media_templates' ) );
		add_action( 'popmake_save_popup', array( __CLASS__, 'save_popup' ) );
	}

	/**
	 * Register the metabox for popup post type.
	 *
	 * @return void
	 */
	public static function register_metabox() {
		add_meta_box( 'pum_popup_cookies', __( 'Cookies', 'popup-maker' ), array( __CLASS__, 'render_metabox' ), 'popup', 'normal', 'high' );
	}

	/**
	 * Display Metabox
	 *
	 * @return void
	 */
	public static function render_metabox() {
		global $post; ?>
		<div id="pum_popup_cookie_fields" class="popmake_meta_table_wrap">
			<button type="button" class="button button-primary add-new"><?php _e( 'Add Cookie', 'popup-maker' ); ?></button>
			<?php do_action( 'pum_popup_cookies_metabox_before', $post->ID ); ?>
			<table id="pum_popup_cookies_list" class="form-table">
				<thead>
					<tr>
						<th><?php _e( 'Event', 'popup-maker' ); ?></th>
						<th><?php _e( 'Name', 'popup-maker' ); ?></th>
						<th><?php _e( 'Settings', 'popup-maker' ); ?></th>
						<th><?php _e( 'Actions', 'popup-maker' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$cookies         = PUM_Cookies::instance()->get_cookies();
					$current_cookies = pum_get_popup_cookies( $post->ID );
					if ( ! empty( $current_cookies ) ) {
						foreach ( $current_cookies as $key => $values ) {
							$cookie = $cookies[ $values['event'] ];
							static::render_row( array(
								'index' => esc_attr( $key ),
								'event' => esc_attr( $values['event'] ),
								'columns' => array(
									'event' => $cookie->get_label( 'name' ),
									'name' =>  $values['settings']['name'],
									'settings' => '<%= PUMCookies.getSettingsDesc(event, cookie_settings) %>',
								),
								'settings' => $values['settings'],
							) );
						}
					} ?>
				</tbody>
			</table>
			<?php do_action( 'pum_popup_cookies_metabox_after', $post->ID ); ?>
		</div><?php
	}

	public static function save_popup( $post_id ) {
		$cookies = array();
		if ( ! empty ( $_POST['popup_cookies'] ) ) {
			foreach ( $_POST['popup_cookies'] as $key => $cookie ) {
				$cookie['settings'] = PUM_Admin_Helpers::object_to_array( json_decode( stripslashes( $cookie['settings'] ) ) );
				$cookie['settings'] = PUM_Cookies::instance()->validate_cookie( $cookie['event'], $cookie['settings'] );
				$cookies[] = $cookie;
			}
		}
		update_post_meta( $post_id, 'popup_cookies', $cookies );
	}

	/**
	 *
	 */
	public static function media_templates() { ?>

		<script type="text/template" id="pum_cookie_row_templ">
			<?php static::render_row( array(
				'index' => '<%= index %>',
				'event' => '<%= event %>',
				'columns' => array(
					'event' => '<%= PUMCookies.getLabel(event) %>',
					'name' => '<%= cookie_settings.name %>',
					'settings' => '<%= PUMCookies.getSettingsDesc(event, cookie_settings) %>',
				),
				'settings' => '<%- JSON.stringify(cookie_settings) %>',
			) ); ?>
		</script>

		<script type="text/template" id="pum_cookie_add_event_templ"><?php
			ob_start(); ?>
			<select id="popup_cookie_add_event">
				<?php foreach ( PUM_Cookies::instance()->get_cookies() as $id => $cookie ) : ?>
					<option value="<?php echo $id; ?>"><?php echo $cookie->get_label( 'name' ); ?></option>
				<?php endforeach ?>
			</select><?php
			$content = ob_get_clean();

			PUM_Admin_Helpers::modal( array(
				'id' => 'pum_cookie_add_event_modal',
				'title' => __( 'What will trigger your cookie to be created?', 'popup-maker' ),
				'content' => $content
			) ); ?>
		</script>

		<?php foreach ( PUM_Cookies::instance()->get_cookies() as $id => $cookie ) { ?>
		<script type="text/template" class="pum-cookie-settings <?php esc_attr_e( $id ); ?> templ" id="pum_cookie_settings_<?php esc_attr_e( $id ); ?>_templ">

			<?php ob_start(); ?>

			<input type="hidden" name="event" class="event" value="<?php esc_attr_e( $id ); ?>"/>
			<input type="hidden" name="index" class=index" value="<%= index %>"/>

			<div class="pum-tabs-container vertical-tabs tabbed-form">

				<ul class="tabs">
					<?php
					/**
					 * Render Each settings tab.
					 */
					foreach ( $cookie->get_sections() as $tab => $args ) { if ( ! $args['hidden'] ) { ?>
						<li class="tab">
							<a href="#<?php esc_attr_e( $id . '_' . $tab ); ?>_settings"><?php esc_html_e( $args['title'] ); ?></a>
						</li>
					<?php } } ?>
				</ul>

				<?php
				/**
				 * Render Each settings tab contents.
				 */
				foreach ( $cookie->get_sections() as $tab => $args ) { ?>
					<div id="<?php esc_attr_e( $id . '_' . $tab ); ?>_settings" class="tab-content">
						<?php $cookie->render_templ_fields( $tab ); ?>
					</div>
				<?php } ?>

			</div><?php

			$content = ob_get_clean();

			PUM_Admin_Helpers::modal( array(
				'id' => 'pum_cookie_settings_' . $id,
				'title' =>  __( 'Cookie Settings', 'popup-maker' ),
				'class' => 'tabbed-content cookie-editor',
				'save_button_text' => '<%= save_button_text %>',
				'content' => $content
			) ); ?>
		</script><?php
		}

	}

	/**
	 * @param array $row
	 */
	public static function render_row( $row = array() ) {
		global $post;

		$row = wp_parse_args( $row, array(
			'index' => 0,
			'event' => 'on_popup_close',
			'columns' => array(
				'event' => __( 'On Popup Close', 'popup-maker' ),
				'name' => 'popmake-' . $post->ID,
				'settings' => __( 'Time: 1 Month', 'popup-maker' ),
			),
			'settings' => array(
				'name' => 'popmake-' . $post->ID,
				'key' => '',
				'session' => 0,
				'time' => '1 month',
				'path' => 1,
			),
		) );
		?>
		<tr data-index="<?php echo $row['index']; ?>">
			<td class="event-column">
				<span class="edit"><?php echo $row['columns']['event']; ?></span>
				<input class="popup_cookies_field_event" type="hidden" name="popup_cookies[<?php echo $row['index']; ?>][event]" value="<?php echo $row['event']; ?>" />
				<input class="popup_cookies_field_settings" type="hidden" name="popup_cookies[<?php echo $row['index']; ?>][settings]" value="<?php echo maybe_json_attr( $row['settings'], true ); ?>" />
			</td>
			<td class="name-column">
				<code>
					<?php echo $row['columns']['name']; ?>
				</code>
			</td>
			<td class="settings-column"><?php echo $row['columns']['settings']; ?></td>
			<td class="actions">
				<i class="edit dashicons dashicons-edit"></i>
				<i class="remove dashicons dashicons-no"></i>
			</td>
		</tr>
		<?php
	}

}
PUM_Popup_Cookies_Metabox::init();
