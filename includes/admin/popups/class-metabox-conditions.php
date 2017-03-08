<?php
/**
 * Fields
 *
 * @package     PUM
 * @subpackage  Classes/Admin/Popups/PUM_Popup_Conditions_Metabox
 * @copyright   Copyright (c) 2016, Daniel Iser
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.4
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
		add_action( 'print_media_templates', array( __CLASS__, 'media_templates' ) );
		add_action( 'pum_save_popup', array( __CLASS__, 'save_popup' ) );
	}

	/**
	 * Register the metabox for popup post type.
	 *
	 * @return void
	 */
	public static function register_metabox() {
		add_meta_box( 'pum_popup_conditions', __( 'Conditions', 'popup-maker' ), array(
			__CLASS__,
			'render_metabox',
		), 'popup', 'side', 'high' );
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
			<?php self::render_builder(); ?>
		</div>

		<?php do_action( 'pum_popup_conditions_metabox_after', $post->ID ); ?>
		</div><?php
	}

	public static function render_builder() {
		global $post;
		$condition_groups = pum_get_popup_conditions( $post->ID );
		$has_conditions   = boolval( count( $condition_groups ) );
		$group_count      = 0; ?>
		<div class="facet-builder <?php echo $has_conditions ? 'has-conditions' : ''; ?>">
			<p>
				<strong>
					<?php printf( __( 'Conditions are used to limit where and who will see a popup. %sLearn More!%s', 'popup-maker' ), '<a href="http://docs.wppopupmaker.com/article/140-conditions?utm_medium=inline-doclink&utm_campaign=ContextualHelp&utm_source=plugin-popup-editor&utm_content=conditions-intro" target="_blank">', '</a>' ); ?>
				</strong>
			</p>
			<section class="pum-alert-box" style="display:none"></section>
			<div class="facet-groups">
				<?php foreach ( $condition_groups as $group => $conditions ) : ?>
				<div class="facet-group-wrap" data-index="<?php echo $group_count; ?>">
					<section class="facet-group">
						<div class="facet-list"><?php
							$condition_count = 0;
							foreach ( $conditions as $values ) :
								if ( ! PUM_Conditions::instance()->get_condition( $values['target'] ) ) {
									continue;
								} ?>
							<div class="facet" data-index="<?php echo $condition_count; ?>" data-target="<?php esc_attr_e( $values['target'] ); ?>">

								<i class="or">or</i>


								<?php $checked = isset( $values['not_operand'] ) ? absint( $values['not_operand'] ) : false; ?>
								<div class="facet-col pum-field pum-condition-target select pum-select2<?php echo $checked ? ' not-operand-checked' : ''; ?>">
									<button type="button" class="pum-not-operand dashicons-before dashicons-warning no-button" aria-label="<?php _e( 'Enable the Not Operand', 'popup-maker' ); ?>">
										<input type="checkbox" name="popup_conditions[<?php echo $group_count; ?>][<?php echo $condition_count; ?>][not_operand]" value="1" <?php checked( $checked, 1 ); ?> />
									</button>
									<?php PUM_Conditions::instance()->conditions_selectbox( array(
										'id'      => "popup_conditions[$group_count][$condition_count][target]",
										'name'    => "popup_conditions[$group_count][$condition_count][target]",
										'current' => $values['target'],
									) ); ?>
								</div>

								<div class="facet-settings">
									<?php PUM_Conditions::instance()->get_condition( $values['target'] )->render_fields( $values ); ?>
								</div>

								<div class="facet-actions">
									<button type="button" class="remove remove-facet dashicons dashicons-dismiss no-button" aria-label="<?php _e( 'Remove Condition', 'popup-maker' ); ?>"></button>
								</div>

								</div><?php
								$condition_count ++; // Increment condition index.
							endforeach; ?>
						</div>
						<div class="add-or">
							<button type="button" class="add add-facet no-button link-button" aria-label="<?php _ex( 'Add another OR condition', 'aria-label for add new OR condition button', 'popup-maker' ); ?>"><?php _e( 'or', 'popup-maker' ); ?></button>
						</div>
					</section>
					<p class="and">
						<button type="button" class="add-facet no-button link-button" aria-label="<?php _ex( 'Add another AND condition group', 'aria-label for add new AND condition button', 'popup-maker' ); ?>"><?php _e( 'and', 'popup-maker' ); ?></button>
					</p>
					</div><?php

					$group_count ++; // Increment group index.

				endforeach; ?>
			</div>
			<div class="no-facet-groups">
				<label for="pum-first-condition"><?php _e( 'Choose a condition to get started.', 'popup-maker' ); ?></label>
				<div class="pum-field select pum-select2 pum-condition-target">
					<button type="button" class="pum-not-operand dashicons-before dashicons-warning no-button" aria-label="<?php _e( 'Enable the Not Operand', 'popup-maker' ); ?>">
						<input type="checkbox" id="pum-first-condition-operand" value="1" />
					</button>
					<?php PUM_Conditions::instance()->conditions_selectbox( array( 'id' => 'pum-first-condition' ) ); ?>
				</div>
			</div>
			<?php $popup = new PUM_Popup( $post->ID ); ?>
			<p>
				<label>
					<input type="checkbox" name="popup_mobile_disabled" value="1" <?php checked( $popup->mobile_disabled(), 1 ); ?> />
					<?php _e( 'Disable this popup on mobile devices.', 'popup-maker' ); ?>
				</label>
			</p>
			<p>
				<label>
					<input type="checkbox" name="popup_tablet_disabled" value="1" <?php checked( $popup->tablet_disabled(), 1 ); ?> />
					<?php _e( 'Disable this popup on tablet devices.', 'popup-maker' ); ?>
				</label>
			</p>

			<?php if ( ! class_exists( 'PUM_ATC' ) ) : ?>
				<p>
					<?php
					printf(
						__( 'Need more %sadvanced targeting%s options?', 'popup-maker' ),
						'<a href="https://wppopupmaker.com/extensions/advanced-targeting-conditions/?utm_campaign=Upsell&utm_source=plugin-popup-editor&utm_medium=text-link&utm_content=conditions-editor" target="_blank">',
						'</a>'
					);
					?></p>
			<?php endif; ?>

		</div>
		<?php
	}

	public static function save_popup( $post_id ) {
		$conditions = array();
		if ( ! empty ( $_POST['popup_conditions'] ) ) {
			foreach ( $_POST['popup_conditions'] as $group_key => $group ) {
				foreach ( $group as $condition_key => $condition ) {
					$validated = PUM_Conditions::instance()->validate_condition( $condition );
					if ( ! is_wp_error( $validated ) ) {
						$conditions[ $group_key ][ $condition_key ] = $validated;
					}
				}
			}
		}
		update_post_meta( $post_id, 'popup_conditions', $conditions );

		if ( ! empty ( $_POST['popup_mobile_disabled'] ) ) {
			update_post_meta( $post_id, 'popup_mobile_disabled', 1 );
		} else {
			delete_post_meta( $post_id, 'popup_mobile_disabled' );
		}

		if ( ! empty ( $_POST['popup_tablet_disabled'] ) ) {
			update_post_meta( $post_id, 'popup_tablet_disabled', 1 );
		} else {
			delete_post_meta( $post_id, 'popup_tablet_disabled' );
		}
	}

	/**
	 *
	 */
	public static function media_templates() {
		if ( ! popmake_is_admin_popup_page() ) {
			return;
		} ?>
		<script type="text/html" id="tmpl-pum-condition-group">
			<div class="facet-group-wrap" data-index="{{data.index}}">
				<section class="facet-group">
					<div class="facet-list">
						<#
							for(var i = 0; data.conditions.length > i; i++) {
							data.conditions[i].index = i;
							data.conditions[i].group = data.index;
							print(PUMConditions.templates.facet( data.conditions[i] ));
							} #>
					</div>
					<div class="add-or">
						<button type="button" class="add add-facet no-button" aria-label="<?php _ex( 'Add another OR condition', 'aria-label for add new OR condition button', 'popup-maker' ); ?>"><?php _e( 'or', 'popup-maker' ); ?></button>
					</div>
				</section>
				<p class="and">
					<button type="button" class="add-facet no-button" aria-label="<?php _ex( 'Add another AND condition group', 'aria-label for add new AND condition button', 'popup-maker' ); ?>"><?php _e( 'and', 'popup-maker' ); ?></button>
				</p>
			</div>
		</script>


		<?php
		/**
		 * Renders a _ template for a condition facet/row
		 *
		 * Passed Data
		 * index: current index inside group
		 * target: condition target
		 * settings: values for option fields
		 */
		?>
		<script type="text/html" id="tmpl-pum-condition-facet">
			<div class="facet" data-index="{{data.index}}" data-target="{{data.target}}">

				<i class="or">or</i>


				<div class="facet-col pum-field pum-condition-target select pum-select2 <# if (typeof data.not_operand !== 'undefined' && pumChecked(data.not_operand, '1')) print('not-operand-checked'); #>">
					<button type="button" class="pum-not-operand dashicons-before dashicons-warning no-button" aria-label="<?php _e( 'Enable the Not Operand', 'popup-maker' ); ?>">
						<input type="checkbox" name="popup_conditions[{{data.group}}][{{data.index}}][not_operand]" value="1"
						<# if (typeof data.not_operand !== 'undefined') print(pumChecked(data.not_operand, '1', true)); #> />
					</button>

					<select class="target facet-select" id="popup_conditions[{{data.group}}][{{data.index}}][target]" name="popup_conditions[{{data.group}}][{{data.index}}][target]">
						<option value=""><?php _e( 'Select a condition', 'popup-maker' ); ?></option>
						<?php foreach ( PUM_Conditions::instance()->get_conditions_by_group() as $group => $conditions ) : ?>
							<optgroup label="<?php echo esc_attr_e( $group ); ?>">
								<?php foreach ( $conditions as $id => $condition ) : ?>
									<option value="<?php echo $id; ?>" {{ pumSelected(data.target, '<?php echo $id; ?>', true) }}>
									<?php echo $condition->get_label( 'name' ); ?>
									</option>
								<?php endforeach ?>
							</optgroup>
						<?php endforeach ?>
					</select>
				</div>

				<div class="facet-settings">
					<#
						//settings.index = index;
						if (typeof data.target === 'string' && PUMConditions.templates.settings[ data.target ] !== undefined) {
						print(PUMConditions.templates.settings[ data.target ]( data.settings ));
						} #>
				</div>

				<div class="facet-actions">
					<button type="button" class="remove remove-facet dashicons dashicons-dismiss no-button" aria-label="<?php _e( 'Remove Condition', 'popup-maker' ); ?>"></button>
				</div>
			</div>
		</script>


		<?php foreach ( PUM_Conditions::instance()->get_conditions() as $id => $condition ) : ?>

			<script type="text/html" id="tmpl-pum-condition-settings-<?php esc_attr_e( $id ); ?>" class="pum-condition-settings tmpl" data-condition="<?php esc_attr_e( $id ); ?>">
				<?php
				/**
				 * Render Each settings tab contents.
				 */
				$condition->render_templ_fields(); ?>
			</script>

		<?php endforeach;

	}

}

PUM_Popup_Conditions_Metabox::init();
