<?php
/**
 * Fields
 *
 * @package     PUM
 * @subpackage  Classes/Admin/Popups/PUM_Popup_Conditions_Metabox
 * @copyright   Copyright (c) 2016, Daniel Iser
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.4.0
 */

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
		//add_action( 'print_media_templates', array( __CLASS__, 'media_templates' ) );
		//add_action( 'popmake_save_popup', array( __CLASS__, 'save_popup' ) );
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
			<?php static::render_builder(); ?>
		</div>

		<?php do_action( 'pum_popup_conditions_metabox_after', $post->ID ); ?>
		</div><?php
	}

	public static function render_builder() {
		global $post;
		$conditions     = pum_get_popup_conditions( $post->ID );
		$has_conditions = boolval( count( $conditions ) );
		$group_count    = 0; ?>
		<script id="pum-popup-conditions-json">
			var pum_popup_conditions = <?php echo json_encode( $conditions ); ?>;
		</script>
		<div class="facet-builder <?php echo $has_conditions ? 'has-conditions' : ''; ?>">
			<section class="pum-alert-box" style="display:none"></section>
			<div class="facet-groups">
				<?php foreach ( $conditions as $group => $conditions ) : ?>
					<div class="facet-group-wrap">
					<section class="facet-group">
						<div class="facet-list"><?php
							$condition_count = 0;
							foreach ( $conditions as $values ) :
								$condition = PUM_Conditions::instance()->get_condition( $values['type'] ); ?>
								<div class="facet">
								<?php if ( $condition_count > 1 ) : ?>
									<i class="badge or">or</i>
							<?php endif; ?>
									<div class="facet-col">
										<?php PUM_Conditions::instance()->conditions_dropdown( array( 'name'  => 'popup_conditions[][type]',
										                                                              'value' => $values['type']
										) ); ?>
									</div>
									<div class="facet-options">
										<?php $condition->render_fields( $values ); ?>
									</div>
									<div class="facet-actions">
										<a href="javascript:void(0)" class="remove remove-facet" rel="tooltip"
										   data-placement="bottom" data-original-title="Remove line" tabindex="-1">
											<i class="dashicons dashicons-dismiss"></i>
										</a>
									</div>
								</div><?php
								$condition_count ++; // Increment condition index.
							endforeach; ?>
						</div>
						<div class="add-or">
							<a href="javascript:void(0)" class="add addFacet" tabindex="-1">+ OR</a>
						</div>
					</section>
					<p class="and">
						<?php if ( false ) { ?>
							<em>AND</em>
						<?php } else { ?>
							<a href="javascript:void(0);" class="addCondition" tabindex="-1">+ AND</a>
						<?php } ?>
					</p>
					</div><?php

					$group_count ++; // Increment group index.

				endforeach; ?>
			</div>
			<div class="no-facet-groups">
				<p>
					<strong><?php _e( 'Conditions limit where and who will see your popups.', 'popup-maker' ); ?></strong>
				</p>
				<label
					for="pum-first-condition"><?php _e( 'Choose a condition to get started.', 'popup-maker' ); ?></label>
				<?php PUM_Conditions::instance()->conditions_dropdown( array( 'id' => 'pum-first-condition' ) ); ?>
			</div>
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
							<?php $condition->render_templ_fields_by_section( $tab ); ?>
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

}

PUM_Popup_Conditions_Metabox::init();
