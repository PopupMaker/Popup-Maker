<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class PUM_Popup_Conditions_Metabox
 *
 * @since 1.4
 */
class PUM_Popup_Conditions_Metabox {
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
		add_meta_box( 'pum_popup_conditions', __( 'Conditions', 'popup-maker' ), array(
			__CLASS__,
			'render_metabox'
		), 'popup', 'normal', 'high' );
	}

	/**
	 * Display Metabox
	 *
	 * @return void
	 */
	public static function render_metabox() {
		global $post; ?>
		<div id="pum_popup_condition_fields" class="popmake_meta_table_wrap">
		<?php do_action( 'pum_popup_conditions_metabox_before', $post->ID ); ?>


		<div id="pum-popup-conditions" class="pum-popup-conditions">
			<section class="pum-alert-box" style="display:none"></section>
			<section class="facet-builder">
				<div class="facet-groups">
					<div class="facet-group-wrap">
						<section class="facet-group">
							<div class="facet-list">
								<div class="facet">
									<div class="facet-col">
										<select class="type">
											<option value="0">Select a Condition ...</option>
											<optgroup label="People">
												<option value="10">Customer Name</option>
												<option value="20">Customer Email</option>
												<option value="140">Help Scout User</option>
											</optgroup>
										</select>
									</div>
									<div class="facet-options">
										<div class="facet-col">
											<select class="operator">
												<option value="8">Is not in the last</option>
												<option value="7">Is in the last</option>
											</select>
										</div>
										<div class="facet-col">
											<input style="float:left; width:90px;" type="text"
											       class="value input-xsmall count">
											<select style="width:90px" class="value-mod value units">
												<option value=":h">Hours</option>
												<option value=":d">Days</option>
											</select>
										</div>
									</div>
									<div class="facet-actions">
										<a href="javascript:void(0)" class="remove remove-facet" rel="tooltip"
										   data-placement="bottom" data-original-title="Remove line" tabindex="-1">
											<i class="dashicons dashicons-dismiss"></i>
										</a>
									</div>
								</div>
								<div class="facet">
									<i class="badge or">or</i>

									<div class="facet-col">
										<select class="type facet-select">
											<option value="0">Select a Condition ...</option>
											<optgroup label="People">
												<option value="10">Customer Name</option>
												<option value="20">Customer Email</option>
												<option value="140">Help Scout User</option>
											</optgroup>
										</select>
									</div>
									<div class="facet-options">
										<div></div>
									</div>
									<div class="facet-actions">
										<a href="javascript:void(0)" class="remove remove-facet" rel="tooltip"
										   data-placement="bottom" data-original-title="Remove line" tabindex="-1">
											<i class="dashicons dashicons-dismiss"></i>
										</a>
									</div>
								</div>
							</div>
							<div class="add-or">
								<a href="javascript:void(0)" class="add addFacet" tabindex="-1">+ OR</a>
							</div>
						</section>
						<p class="and">
							<em>AND</em>
						</p>
					</div>
					<div class="facet-group-wrap">
						<section class="facet-group">
							<div class="facet-list">
								<div class="facet">
									<div class="facet-col">
										<select class="type">
											<option value="0">Select a Condition ...</option>
											<optgroup label="People">
												<option value="10">Customer Name</option>
												<option value="20">Customer Email</option>
												<option value="140">Help Scout User</option>
											</optgroup>
										</select>
									</div>
									<div class="facet-options">
										<div></div>
									</div>
									<div class="facet-actions">
										<a href="javascript:void(0)" class="remove remove-facet" rel="tooltip"
										   data-placement="bottom" data-original-title="Remove line" tabindex="-1">
											<i class="dashicons dashicons-dismiss"></i>
										</a>
									</div>
								</div>
							</div>
							<div class="add-or">
								<a href="javascript:void(0)" class="add addFacet" tabindex="-1">+ OR</a>
							</div>
						</section>
						<p class="and">
							<a href="javascript:void(0);" class="addCondition" tabindex="-1">+ AND</a>
						</p>
					</div>
				</div>
			</section>
			<section class="form-actions"></section>
		</div>


		<?php do_action( 'pum_popup_conditions_metabox_after', $post->ID ); ?>
		</div><?php
	}

	public static function save_popup( $post_id ) {
		$conditions = array();
		if ( ! empty ( $_POST['popup_conditions'] ) ) {
			foreach ( $_POST['popup_conditions'] as $key => $condition ) {
				$condition['settings'] = PUM_Admin_Helpers::object_to_array( json_decode( stripslashes( $condition['settings'] ) ) );
				$condition['settings'] = PUM_Conditions::instance()->validate_condition( $condition['event'], $condition['settings'] );
				$conditions[]          = $condition;
			}
		}
		update_post_meta( $post_id, 'popup_conditions', $conditions );
	}

	/**
	 *
	 */
	public static function media_templates() { ?>

		<script type="text/template" id="pum_condition_row_templ">
			<?php static::render_row( array(
				'index'    => '<%= index %>',
				'event'    => '<%= event %>',
				'columns'  => array(
					'event'    => '<%= PUMConditions.getLabel(event) %>',
					'name'     => '<%= condition_settings.name %>',
					'settings' => '<%= PUMConditions.getSettingsDesc(event, condition_settings) %>',
				),
				'settings' => '<%- JSON.stringify(condition_settings) %>',
			) ); ?>
		</script>

		<script type="text/template" id="pum_condition_add_event_templ"><?php
			ob_start(); ?>
			<select id="popup_condition_add_event">
				<?php foreach ( PUM_Conditions::instance()->get_conditions() as $id => $condition ) : ?>
					<option value="<?php echo $id; ?>"><?php echo $condition->get_label( 'name' ); ?></option>
				<?php endforeach ?>
			</select><?php
			$content = ob_get_clean();

			PUM_Admin_Helpers::modal( array(
				'id'      => 'pum_condition_add_event_modal',
				'title'   => __( 'What will trigger your condition to be created?', 'popup-maker' ),
				'content' => $content
			) ); ?>
		</script>

		<?php foreach ( PUM_Conditions::instance()->get_conditions() as $id => $condition ) { ?>
			<script type="text/template" class="pum-condition-settings <?php esc_attr_e( $id ); ?> templ"
			        id="pum_condition_settings_<?php esc_attr_e( $id ); ?>_templ">

				<?php ob_start(); ?>

				<input type="hidden" name="event" class="event" value="<?php esc_attr_e( $id ); ?>"/>
				<input type="hidden" name="index" class=index" value="<%= index %>"/>

				<div class="pum-tabs-container vertical-tabs tabbed-form">

					<ul class="tabs">
						<?php
						/**
						 * Render Each settings tab.
						 */
						foreach ( $condition->get_sections() as $tab => $args ) {
							if ( ! $args['hidden'] ) { ?>
								<li class="tab">
									<a href="#<?php esc_attr_e( $id . '_' . $tab ); ?>_settings"><?php esc_html_e( $args['title'] ); ?></a>
								</li>
							<?php }
						} ?>
					</ul>

					<?php
					/**
					 * Render Each settings tab contents.
					 */
					foreach ( $condition->get_sections() as $tab => $args ) { ?>
						<div id="<?php esc_attr_e( $id . '_' . $tab ); ?>_settings" class="tab-content">
							<?php $condition->render_templ_fields( $tab ); ?>
						</div>
					<?php } ?>

				</div><?php

				$content = ob_get_clean();

				PUM_Admin_Helpers::modal( array(
					'id'               => 'pum_condition_settings_' . $id,
					'title'            => __( 'Condition Settings', 'popup-maker' ),
					'class'            => 'tabbed-content condition-editor',
					'save_button_text' => '<%= save_button_text %>',
					'content'          => $content
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
			'index'    => 0,
			'event'    => 'on_popup_close',
			'columns'  => array(
				'event'    => __( 'On Popup Close', 'popup-maker' ),
				'name'     => 'popmake-' . $post->ID,
				'settings' => __( 'Time: 1 Month', 'popup-maker' ),
			),
			'settings' => array(
				'name'    => 'popmake-' . $post->ID,
				'key'     => '',
				'session' => 0,
				'time'    => '1 month',
				'path'    => 1,
			),
		) );
		?>
		<tr data-index="<?php echo $row['index']; ?>">
			<td class="event-column">
				<span class="edit"><?php echo $row['columns']['event']; ?></span>
				<input class="popup_conditions_field_event" type="hidden"
				       name="popup_conditions[<?php echo $row['index']; ?>][event]"
				       value="<?php echo $row['event']; ?>"/>
				<input class="popup_conditions_field_settings" type="hidden"
				       name="popup_conditions[<?php echo $row['index']; ?>][settings]"
				       value="<?php echo maybe_json_attr( $row['settings'], true ); ?>"/>
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

PUM_Popup_Conditions_Metabox::init();
