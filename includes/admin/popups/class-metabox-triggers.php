<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class PUM_Popup_Triggers_Metabox {
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_metabox' ) );
		add_action( 'print_media_templates', array( __CLASS__, 'media_templates' ) );
	}
	/**
	 * Register the metabox for popup post type.
	 *
	 * @since 1.4
	 * @return void
	 */
	public static function register_metabox() {
		add_meta_box( 'pum_popup_triggers', __( 'Triggers', 'popup-maker' ), array( __CLASS__, 'render_metabox' ), 'popup', 'normal', 'high' );
	}

	/**
	 * Display Metabox
	 *
	 * @since 1.4
	 * @return void
	 */
	public static function render_metabox() {
		global $post; ?>
		<div id="pum_popup_trigger_fields" class="popmake_meta_table_wrap">
			<button type="button" class="button button-primary add-new"><?php _e( 'Add New', 'popup-maker' ); ?></button>
			<?php do_action( 'pum_popup_triggers_metabox_before', $post->ID ); ?>
			<table id="pum_popup_triggers_list" class="form-table">
				<thead>
					<tr>
						<th><?php _e( 'Type', 'popup-maker' ); ?></th>
						<th><?php _e( 'Options', 'popup-maker' ); ?></th>
						<th><?php _e( 'Cookie', 'popup-maker' ); ?></th>
						<th><?php _e( 'Actions', 'popup-maker' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr data-index="0">
						<td><?php _e( 'Auto Open', 'popup-maker' ); ?>
							<input class="popup_triggers_field_type" type="hidden" name="popup_triggers[0][type]" value="auto_open" />
							<input class="popup_triggers_field_options" type="hidden" name="popup_triggers[0][options]" value="<?php esc_html_e( json_encode( array(
								'delay' => 0
							) ) ); ?>" />
							<input class="popup_triggers_field_cookie" type="hidden" name="popup_triggers[0][cookie]" value="<?php esc_html_e( json_encode( array(
								'name' => 'custom_cookie',
								'trigger' => 'manual',
								'time' => '1 day',
								'session' => 1,
								'path' => '/',
								'key' => ''
							) ) ); ?>" />
						</td>
						<td>Delay: 0</td>
						<td>On Close / 1 Month</td>
						<td class="actions">
							<i class="edit dashicons dashicons-edit"></i>
							<i class="remove dashicons dashicons-no"></i>
						</td>
					</tr>
				</tbody>
			</table>
			<?php do_action( 'pum_popup_triggers_metabox_after', $post->ID ); ?>
		</div><?php
	}

	public static function modal( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'id' => 'default',
			'title' => '',
			'class' => '',
			'cancel_button' => true,
			'cancel_button_text' => __( 'Cancel', 'popup-maker' ),
			'save_button' => true,
			'save_button_text' => __( 'Add', 'popup-maker' ),
		) );
		?>
		<div id="pum_<?php echo $args['id']; ?>_modal" class="pum-modal-background <?php esc_attr_e( $args['class'] ); ?>">

			<div class="pum-modal-wrap">

				<form class="pum-form" tabindex="-1">

					<div class="pum-modal-header">

						<?php if ( $args['title'] != '' ) { ?>
						<span class="pum-modal-title"><?php echo $args['title']; ?></span>
						<?php } ?>
						<div class="pum-modal-close">
							<span class="screen-reader-text"><?php _e( 'Close', 'popup-maker' ); ?></span>
						</div>
					</div>

					<div class="pum-modal-content">
						<?php echo $args['content']; ?>
					</div>

					<?php if ( $args['save_button'] || $args['cancel_button'] ) { ?>
					<div class="pum-modal-footer submitbox">
						<?php if ( $args['cancel_button'] ) { ?>
						<div class="cancel">
							<a class="submitdelete" href="#"><?php echo $args['cancel_button_text']; ?></a>
						</div>
						<?php } ?>
						<?php if ( $args['save_button'] ) { ?>
							<div class="pum-submit">
								<span class="spinner"></span>
								<button class="button button-primary"><?php echo $args['save_button_text']; ?></button>
							</div>
						<?php } ?>
					</div>
					<?php } ?>
				</form>
			</div>
		</div><?php
	}

	public static function media_templates() { ?>
		<script type="text/template" id="pum_trigger_add_type_templ"><?php
			ob_start(); ?>
			<select id="popup_trigger_add_type">
				<?php foreach ( apply_filters( 'pum_trigger_types', array(
					__( 'Auto Open', 'popup-maker' ) => 'auto_open',
					__( 'Click', 'popup-maker' ) => 'click_open',
				) ) as $option => $value ) : ?>
					<option value="<?php echo $value; ?>"><?php echo $option; ?></option>
				<?php endforeach ?>
			</select><?php
			$content = ob_get_clean();

			static::modal( array(
				'id' => 'trigger_add_type',
				'title' => __( 'Choose what type of trigger to add?', 'popup-maker' ),
				'content' => $content
			) ); ?>
		</script>
		<script type="text/template" id="pum_trigger_row_templ">
			<?php static::render_row( array(
				'index' => '<%= index %>',
				'type' => '<%= type %>',
				'labels' => array(
					'triggers' => array(
						'open' => 'On Open',
	                    'close:' => 'On Close',
	                    'manual' => 'Manual',
	                    'disabled' => 'Disabled',
					),
				),
				'columns' => array(
					'type' => '<%= I10n.labels.triggers[type] %>',
					'options' => '<%= pumTriggerColumnDescription(type, "options", options) %>',
					'cookie' => '<%= pumTriggerColumnDescription(type, "cookie", cookie) %>',
				),
				'options' => '<%= encodeURIComponent(JSON.stringify(options)) %>',
				'cookie' => '<%= encodeURIComponent(JSON.stringify(cookie)) %>',
			) ); ?>
		</script>
		<script type="text/template" id="pum_trigger_click_open_editor_modal_templ"><?php
			ob_start(); ?>

			<input type="hidden" name="type" class="type" value="click_open" />
			<input type="hidden" name="index" class=index" value="<%= index %>" />
			<input type="hidden" name="cookie" class=cookie" value="" />

			<div class="pum-tabs-container vertical-tabs tabbed-form">
				<ul class="tabs">
					<li class="tab">
						<a href="#click_open_options"><?php _e( 'Options', 'popup-maker' ); ?></a>
					</li>
				</ul>

				<div id="click_open_options" class="tab-content">
					<div class="field">
						<label for="pum_trigger_options_extra_selectors">
							<?php _e( 'Extra CSS Selectors', 'popup-maker' ); ?>
						</label>
						<input type="text" placeholder="<?php _e( '.my-class, #button2', 'popup-maker' ); ?>" name="options[extra_selectors]" id="pum_trigger_options_extra_selectors" value="<%= options.extra_selectors %>"/>
						<p class="description"><?php _e( 'This allows custom css classes, ids or selector strings to trigger the popup when clicked. Separate multiple selectors using commas.', 'popup-maker' ); ?></p>
					</div>
				</div>
			</div><?php
			$content = ob_get_clean();

			static::modal( array(
				'id' => 'trigger_click_open_editor',
				'title' => '<%= title %>',
				'class' => 'tabbed-content trigger-editor',
				'save_button_text' => '<%= save_button_text %>',
				'content' => $content
			) ); ?>
		</script>
		<script type="text/template" id="pum_trigger_auto_open_editor_modal_templ"><?php
			ob_start(); ?>

			<input type="hidden" name="type" class="type" value="auto_open" />
			<input type="hidden" name="index" class=index" value="<%= index %>" />

			<div class="pum-tabs-container vertical-tabs tabbed-form">
				<ul class="tabs">
					<li class="tab">
						<a href="#auto_open_options"><?php _e( 'Options', 'popup-maker' ); ?></a>
					</li>
					<li class="tab">
						<a href="#auto_open_cookie"><?php _e( 'Cookies', 'popup-maker' ); ?></a>
					</li>
				</ul>

				<div id="auto_open_options" class="tab-content">
					<div class="field">
						<label for="pum_trigger_options_delay"><?php _e( 'Delay', 'popup-maker' ); ?></label>
						<input type="text" readonly
						       value="<%= options.delay %>"
						       name="options[delay]"
						       id="pum_trigger_options_delay"
						       class="popmake-range-manual"
						       step="500"
						       min="0"
						       max="10000"
							/>
						<span class="range-value-unit regular-text">ms</span>
						<p class="description"><?php _e( 'The delay before the popup will open in milliseconds.', 'popup-maker' ); ?></p>
					</div>
				</div>
				<div id="auto_open_cookie" class="tab-content">
					<div class="field">
						<label for="pum_trigger_cookie_trigger">
							<?php _e( 'Cookie Trigger', 'popup-maker' ); ?>
						</label>
						<select name="cookie[trigger]">
							<?php foreach ( apply_filters( 'pum_cookie_triggers', array(
								__( 'On Open', 'popup-maker' )  => 'open',
								__( 'On Close', 'popup-maker' ) => 'close',
								__( 'Manual', 'popup-maker' )   => 'manual',
								__( 'Disabled', 'popup-maker' ) => 'disabled',
							) ) as $option => $value ) : ?>
								<option
									value="<?php echo $value; ?>"
									<% if(cookie.trigger === '<?php echo $value; ?>') { %>selected="selected"<% } %>
									><?php echo $option; ?></option>
							<?php endforeach ?>
						</select>
						<p class="description"><?php _e( 'When do you want to create the cookie.', 'popup-maker' ) ?></p>
					</div>
					<div class="field">
						<label for="pum_trigger_cookie_session">
							<?php _e( 'Use Session Cookie?', 'popup-maker' ); ?>
						</label>
						<input type="checkbox" value="1" id="pum_trigger_cookie_session" name="cookie[session]" <% if (cookie.session) { %>checked="checked"<% } %> />
						<label class="description" for="pum_trigger_cookie_session"><?php _e( 'Session cookies expire when the user closes their browser.', 'popup-maker' ) ?></label>
					</div>
					<div class="field not-session-cookie">
						<label for="pum_trigger_cookie_time">
							<?php _e( 'Cookie Time', 'popup-maker' ); ?>
						</label>
						<input type="text" class="regular-text" name="cookie[time]" id="pum_trigger_cookie_time" value="<%= cookie.time %>"/>
						<p class="description"><?php _e( 'Enter a plain english time before cookie expires. <br/>Example "364 days 23 hours 59 minutes 59 seconds" will reset just before 1 year exactly.', 'popup-maker' ) ?></p>
					</div>
					<div class="field not-session-cookie">
						<label for="pum_trigger_cookie_path">
							<?php _e( 'Sitewide Cookie', 'popup-maker' ); ?>
						</label>
						<input type="checkbox" value="/" name="cookie[path]" id="pum_trigger_cookie_path" <% if (cookie.path === '/') { %>checked="checked"<% } %> />
						<label for="pum_trigger_cookie_path" class="description"><?php _e( 'This will prevent the popup from auto opening on any page until the cookie expires.', 'popup-maker' ); ?></label>
					</div>
					<div class="field not-session-cookie">
						<label for="pum_trigger_cookie_key">
							<?php _e( 'Cookie Key', 'popup-maker' ); ?>
						</label>
						<input type="text" value="<%= cookie.key %>" name="cookie[key]" id="pum_trigger_cookie_key"/>
						<button type="button" class="popmake-reset-cookie-key popmake-reset-auto-open-cookie-key button large-button"><?php _e( 'Reset', 'popup-maker' ); ?></button>
						<p class="description"><?php _e( 'This changes the key used when setting and checking cookies. Resetting this will cause all existing cookies to be invalid.', 'popup-maker' ); ?></p>
					</div>
				</div>
			</div><?php
			$content = ob_get_clean();

			static::modal( array(
				'id' => 'trigger_auto_open_editor',
				'title' => '<%= title %>',
				'class' => 'tabbed-content trigger-editor',
				'save_button_text' => '<%= save_button_text %>',
				'content' => $content
			) ); ?>
		</script>
<?php
	}

	public static function render_row( $row = array() ) {
		$row = wp_parse_args( $row, array(
			'index' => 0,
			'type' => 'auto_open',
			'columns' => array(
				'type' => __( 'Auto Open', 'popup-maker' ),
				'options' => __( 'Delay: 0', 'popup-maker' ),
				'cookie' => __( 'On Close / 1 Month', 'popup-maker' ),
			),
			'options' => array(
				'delay' => 0
			),
			'cookie' => array(
				'name' => 'custom_cookie',
				'trigger' => 'close',
				'time' => '1 month',
				'session' => 0,
				'path' => '/',
				'key' => ''
			)
		) );
		?>
		<tr data-index="<?php echo $row['index']; ?>">
			<td><?php echo $row['columns']['type']; ?>
				<input class="popup_triggers_field_type" type="hidden" name="popup_triggers[<?php echo $row['index']; ?>][type]" value="<?php echo $row['type']; ?>" />
				<input class="popup_triggers_field_options" type="hidden" name="popup_triggers[<?php echo $row['index']; ?>][options]" value="<?php echo maybe_json_attr( $row['options'] ); ?>" />
				<input class="popup_triggers_field_cookie" type="hidden" name="popup_triggers[<?php echo $row['index']; ?>][cookie]" value="<?php echo maybe_json_attr( $row['cookie'] ); ?>" />
			</td>
			<td><?php echo $row['columns']['options']; ?></td>
			<td><?php echo $row['columns']['cookie']; ?></td>
			<td class="actions">
				<i class="edit dashicons dashicons-edit"></i>
				<i class="remove dashicons dashicons-no"></i>
			</td>
		</tr>
		<?php
	}

}
PUM_Popup_Triggers_Metabox::init();