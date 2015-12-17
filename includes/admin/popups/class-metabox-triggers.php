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
		add_action( 'popmake_save_popup', array( __CLASS__, 'save_popup' ) );
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
		global $post; ?>
		<div id="pum_popup_trigger_fields" class="popmake_meta_table_wrap">
			<button type="button" class="button button-primary add-new"><?php _e( 'Add Trigger', 'popup-maker' ); ?></button>
			<?php do_action( 'pum_popup_triggers_metabox_before', $post->ID ); ?>
			<table id="pum_popup_triggers_list" class="form-table">
				<thead>
					<tr>
						<th><?php _e( 'Type', 'popup-maker' ); ?></th>
						<th><?php _e( 'Settings', 'popup-maker' ); ?></th>
						<th><?php _e( 'Actions', 'popup-maker' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$triggers = PUM_Triggers::instance()->get_triggers();
					$current_triggers = popmake_get_popup_triggers( $post->ID );
					if ( ! empty( $current_triggers ) ) {
						foreach ( $current_triggers as $key => $values ) {
							$trigger = $triggers[ $values['type'] ];
							static::render_row( array(
								'index' => esc_attr( $key ),
								'type' => esc_attr( $values['type'] ),
								'columns' => array(
									'type' => $trigger->get_label('name'),
									'settings' => '<%= PUMTriggers.getSettingsDesc(type, trigger_settings) %>',
								),
								'settings' => $values['settings'],
							) );
						}
					} ?>
				</tbody>
			</table>
			<?php do_action( 'pum_popup_triggers_metabox_after', $post->ID ); ?>
		</div><?php
	}

	public static function save_popup( $post_id ) {
		$triggers = array();
		if ( ! empty ( $_POST['popup_triggers'] ) ) {
			foreach ( $_POST['popup_triggers'] as $key => $trigger ) {
				$trigger['settings'] = static::object_to_array( json_decode( stripslashes( $trigger['settings'] ) ) );
				$trigger['settings'] = PUM_Triggers::instance()->validate_trigger( $trigger['type'], $trigger['settings'] );
				$triggers[] = $trigger;
			}
		}
		update_post_meta( $post_id, 'popup_triggers', $triggers );
	}

	public static function object_to_array($obj) {
		if(is_object($obj)) $obj = (array) $obj;
		if(is_array($obj)) {
			$new = array();
			foreach($obj as $key => $val) {
				$new[$key] = static::object_to_array($val);
			}
		}
		else $new = $obj;
		return $new;
	}

	/**
	 *
	 */
	public static function media_templates() { ?>

		<script type="text/template" id="pum_trigger_row_templ">
			<?php static::render_row( array(
				'index' => '<%= index %>',
				'type' => '<%= type %>',
				'columns' => array(
					'type' => '<%= PUMTriggers.getLabel(type) %>',
					'settings' => '<%= PUMTriggers.getSettingsDesc(type, trigger_settings) %>',
				),
				'settings' => '<%- JSON.stringify(trigger_settings) %>',
			) ); ?>
		</script>

		<script type="text/template" id="pum_trigger_add_type_templ"><?php
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
		<script type="text/template" class="pum-trigger-settings <?php esc_attr_e( $id ); ?> templ" id="pum_trigger_settings_<?php esc_attr_e( $id ); ?>_templ">

			<?php ob_start(); ?>

			<input type="hidden" name="type" class="type" value="<?php esc_attr_e( $id ); ?>"/>
			<input type="hidden" name="index" class=index" value="<%= index %>"/>

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
						<?php $trigger->render_templ_fields( $tab ); ?>
					</div>
				<?php } ?>

			</div><?php

			$content = ob_get_clean();

			PUM_Admin_Helpers::modal( array(
				'id' => 'pum_trigger_settings_' . $id,
				'title' => $trigger->get_label( 'modal_title' ),
				'class' => 'tabbed-content trigger-editor',
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
		$row = wp_parse_args( $row, array(
			'index' => 0,
			'type' => 'auto_open',
			'columns' => array(
				'type' => __( 'Auto Open', 'popup-maker' ),
				'settings' => __( 'Delay: 0', 'popup-maker' ),
			),
			'settings' => array(
				'delay' => 0,
				'cookie' => array(
					'trigger' => 'close',
					'time' => '1 month',
					'session' => 0,
					'path' => '/',
					'key' => ''
				)
			),
		) );
		?>
		<tr data-index="<?php echo $row['index']; ?>">
			<td><span class="edit"><?php echo $row['columns']['type']; ?></span>
				<input class="popup_triggers_field_type" type="hidden" name="popup_triggers[<?php echo $row['index']; ?>][type]" value="<?php echo $row['type']; ?>" />
				<input class="popup_triggers_field_settings" type="hidden" name="popup_triggers[<?php echo $row['index']; ?>][settings]" value="<?php echo maybe_json_attr( $row['settings'], true ); ?>" />
			</td>
			<td><?php echo $row['columns']['settings']; ?></td>
			<td class="actions">
				<i class="edit dashicons dashicons-edit"></i>
				<i class="remove dashicons dashicons-no"></i>
			</td>
		</tr>
		<?php
	}

}
PUM_Popup_Triggers_Metabox::init();