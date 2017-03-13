<?php



// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class PUM_Popup_Triggers_Metabox
 *
 * @since 1.4
 */
class PUM_Popup_Triggers_Metabox {
	/**
	 * Initialize the needed actions & filters.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_metabox' ) );
		add_action( 'print_media_templates', array( __CLASS__, 'media_templates' ) );
		add_action( 'pum_save_popup', array( __CLASS__, 'save_popup' ) );
	}

	/**
	 * Register the metabox for popup post type.
	 *
	 * @return void
	 */
	public static function register_metabox() {
		add_meta_box( 'pum_popup_triggers', __( 'Triggers', 'popup-maker' ), array( __CLASS__, 'render_metabox' ), 'popup', 'normal', 'high' );
	}

	/**
	 * Display Metabox
	 *
	 * @return void
	 */
	public static function render_metabox() {
        global $post;


        $triggers         = PUM_Triggers::instance()->get_triggers();
        $current_triggers = pum_get_popup_triggers( $post->ID );
        $has_triggers     = boolval( count( $current_triggers ) );

        ?>
    <div id="pum_popup_trigger_fields" class="popmake_meta_table_wrap <?php echo $has_triggers ? 'has-triggers' : ''; ?>">

		<button type="button" class="button button-primary add-new no-button"><?php _e( 'Add New Trigger', 'popup-maker' ); ?></button>

		<p>
			<strong>
				<?php _e( 'Triggers are what make your popup open.', 'popup-maker' ); ?>
				<a href="<?php echo esc_url( 'http://docs.wppopupmaker.com/article/141-triggers?utm_medium=inline-doclink&utm_campaign=ContextualHelp&utm_source=plugin-popup-editor&utm_content=triggers-intro' ); ?>" target="_blank" class="pum-doclink dashicons dashicons-editor-help"></a>
			</strong>
		</p>

		<div id="pum_popup_triggers_list" class="triggers-list">

	        <?php do_action( 'pum_popup_triggers_metabox_before', $post->ID ); ?>

            <table class="form-table">
                <thead>
                <tr>
                    <th><?php _e( 'Type', 'popup-maker' ); ?></th>
                    <th><?php _e( 'Cookie', 'popup-maker' ); ?></th>
                    <th><?php _e( 'Settings', 'popup-maker' ); ?></th>
                    <th><?php _e( 'Actions', 'popup-maker' ); ?></th>
                </tr>
                </thead>
                <tbody><?php
                if ( ! empty( $current_triggers ) ) {
                    foreach ( $current_triggers as $key => $values ) {
                        if ( ! isset( $triggers[ $values['type'] ] ) ) {
                            continue;
                        }
                        $trigger = $triggers[ $values['type'] ];
	                    self::render_row( array(
                                'index'    => esc_attr( $key ),
                                'type'     => esc_attr( $values['type'] ),
                                'columns'  => array(
                                        'type'     => $trigger->get_label( 'name' ),
                                        'cookie'   => isset( $values['settings']['cookie']['name'] ) ? $values['settings']['cookie']['name'] : '',
                                        'settings' => '{{PUMTriggers.getSettingsDesc(data.type, data.trigger_settings)}}',
                                ),
                                'settings' => $values['settings'],
                        ) );
                    }
                } ?>
                </tbody>
            </table>

            <?php do_action( 'pum_popup_triggers_metabox_after', $post->ID ); ?>

        </div>

        <div class="no-triggers">
            <div class="pum-field select pum-select2">
                <label for="pum-first-trigger"><?php _e( 'Choose a type of trigger to get started.', 'popup-maker' ); ?></label>
                <select id="pum-first-trigger" data-placeholder="<?php _e( 'Select a trigger type.', 'popup-maker' ); ?>">
                    <?php foreach ( $triggers as $id => $trigger ) : ?>
                        <option value="<?php echo $id; ?>"><?php echo $trigger->get_label( 'name' ); ?></option>
                    <?php endforeach ?>
                </select>
            </div>
        </div>


        </div><?php
	}

	public static function save_popup( $post_id ) {
		$triggers = array();
		if ( ! empty ( $_POST['popup_triggers'] ) ) {
			foreach ( $_POST['popup_triggers'] as $key => $trigger ) {
				$trigger['settings'] = PUM_Admin_Helpers::object_to_array( json_decode( stripslashes( $trigger['settings'] ) ) );
				$trigger['settings'] = PUM_Triggers::instance()->validate_trigger( $trigger['type'], $trigger['settings'] );
				$triggers[] = $trigger;
			}
		}
		update_post_meta( $post_id, 'popup_triggers', $triggers );
	}

	/**
	 *
	 */
	public static function media_templates() {
        if ( ! popmake_is_admin_popup_page() ) {
            return;
        } ?>
		<script type="text/html" id="tmpl-pum-trigger-row">
			<?php self::render_row( array(
				'index' => '{{data.index}}',
				'type' => '{{data.type}}',
				'columns' => array(
                        'type'     => '{{PUMTriggers.getLabel(data.type)}}',
                        'cookie'   => "{{PUMTriggers.cookie_column_value(data.trigger_settings.cookie.name)}}",
                        'settings' => '{{PUMTriggers.getSettingsDesc(data.type, data.trigger_settings)}}',
				),
				'settings' => '{{JSON.stringify(data.trigger_settings)}}',
			) ); ?>
		</script>

		<script type="text/html" id="tmpl-pum-trigger-add-type"><?php
			ob_start(); ?>
			<select id="popup_trigger_add_type">
				<?php foreach ( PUM_Triggers::instance()->get_triggers() as $id => $trigger ) : ?>
					<option value="<?php echo $id; ?>"><?php echo $trigger->get_label( 'name' ); ?></option>
				<?php endforeach ?>
			</select><?php
			$content = ob_get_clean();

			PUM_Admin_Helpers::modal( array(
				'id' => 'pum_trigger_add_type_modal',
				'title' => __( 'Choose what type of trigger to add?', 'popup-maker' ),
				'content' => $content
			) ); ?>
		</script>

		<?php foreach ( PUM_Triggers::instance()->get_triggers() as $id => $trigger ) { ?>
		<script type="text/html" id="tmpl-pum-trigger-settings-<?php esc_attr_e( $id ); ?>" class="pum-trigger-settings tmpl" data-trigger="<?php esc_attr_e( $id ); ?>">

			<?php ob_start(); ?>

			<input type="hidden" name="type" class="type" value="<?php esc_attr_e( $id ); ?>" />
			<input type="hidden" name="index" class="index" value="{{data.index}}" />

			<div class="pum-tabs-container vertical-tabs tabbed-form">

				<ul class="tabs">
					<?php
					/**
					 * Render Each settings tab.
					 */
					foreach ( $trigger->get_sections() as $tab => $args ) { ?>
						<li class="tab">
							<a href="#<?php esc_attr_e( $id . '_' . $tab ); ?>_settings"><?php esc_html_e( $args['title'] ); ?></a>
						</li>
					<?php } ?>
				</ul>

				<?php
				/**
				 * Render Each settings tab contents.
				 */
				foreach ( $trigger->get_sections() as $tab => $args ) { ?>
					<div id="<?php esc_attr_e( $id . '_' . $tab ); ?>_settings" class="tab-content">
						<?php $trigger->render_templ_fields_by_section( $tab ); ?>
					</div>
				<?php } ?>

			</div><?php

			$content = ob_get_clean();

			PUM_Admin_Helpers::modal( array(
				'id' => 'pum_trigger_settings_' . $id,
				'title' => $trigger->get_label( 'modal_title' ),
				'class' => 'tabbed-content trigger-editor',
				'save_button_text' => '{{data.save_button_text}}',
				'content' => $content
			) ); ?>
		</script><?php
		}

	}

	/**
	 * @param array $row
	 */
	public static function render_row( $row = array() ) {
		$row = wp_parse_args( $row, array(
			'index' => 0,
			'type' => 'auto_open',
			'columns' => array(
				'type' => __( 'Auto Open', 'popup-maker' ),
				'cookie' => 'popmake-123',
				'settings' => __( 'Delay: 0', 'popup-maker' ),
			),
			'settings' => array(
				'delay' => 0,
				'cookie' => array(
					'name' => 'popmake-123',
				)
			),
		) );
		?>
		<tr data-index="<?php echo $row['index']; ?>">
            <td class="type-column">
                <button type="button" class="edit no-button link-button" aria-label="<?php _e( 'Edit this trigger', 'popup-maker' ); ?>"><?php echo $row['columns']['type']; ?></button>
				<input class="popup_triggers_field_type" type="hidden" name="popup_triggers[<?php echo $row['index']; ?>][type]" value="<?php echo $row['type']; ?>" />
				<input class="popup_triggers_field_settings" type="hidden" name="popup_triggers[<?php echo $row['index']; ?>][settings]" value="<?php echo maybe_json_attr( $row['settings'], true ); ?>" />
			</td>
			<td class="cookie-column">
				<code><?php
                    if ( is_array( $row['columns']['cookie'] ) ) {
                        echo implode( ',', $row['columns']['cookie'] );
                    } else {
                        echo $row['columns']['cookie'];
                    } ?>
				</code>
			</td>
			<td class="settings-column"><?php echo $row['columns']['settings']; ?></td>
			<td class="actions">
                <button type="button" class="edit dashicons dashicons-edit no-button" aria-label="<?php _e( 'Edit this trigger', 'popup-maker' ); ?>"></button>
                <button type="button" class="remove dashicons dashicons-no no-button" aria-label="<?php _e( 'Delete` this trigger', 'popup-maker' ); ?>"></button>
			</td>
		</tr>
		<?php
	}

}
PUM_Popup_Triggers_Metabox::init();
