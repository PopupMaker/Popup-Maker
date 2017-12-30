<?php
/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Shortcode_Subscribe
 */
class PUM_Shortcode_Subscribe extends PUM_Shortcode {

	/**
	 * @var int
	 */
	public $version = 2;

	/**
	 * @var bool
	 */
	public $ajax_rendering = true;

	/**
	 * @return string
	 */
	public function label() {
		return __( 'Subscription Form', 'popup-maker' );
	}

	/**
	 * @return string
	 */
	public function description() {
		return __( 'A customizable newsletter subscription form.', 'popup-maker' );
	}

	/**
	 * @return array
	 */
	public function post_types() {
		return array( 'page', 'post', 'popup' );
	}

	/**
	 * @return array
	 */
	public function tabs() {
		return apply_filters( 'pum_sub_form_shortcode_sections', array(
			'general'  => __( 'General', 'popup-maker' ),
			'fields'   => __( 'Fields', 'popup-maker' ),
			'labeling' => __( 'Labeling', 'popup-maker' ),
			'actions'  => __( 'Actions', 'popup-maker' ),
		) );
	}

	/**
	 * @return array
	 */
	public function fields() {
		return apply_filters( 'pum_sub_form_shortcode_fields', array(
			'general'  => array(
				'provider'       => array(
					'label'   => __( 'Service Provider', 'popup-maker' ),
					'desc'    => __( 'Choose which service provider to submit to.', 'popup-maker' ),
					'type'    => 'select',
					'options' => array_merge( array(
						'' => __( 'Default', 'popup-maker' ),
					), PUM_Newsletter_Providers::dropdown_list(), array(
						'none' => __( 'None', 'popup-maker' ),
					) ),
					'std'     => '',
				),
				'form_layout'    => array(
					'label'   => __( 'Form Layout', 'popup-maker' ),
					'desc'    => __( 'Choose a form layout.', 'popup-maker' ),
					'type'    => 'select',
					'options' => array(
						'block'  => __( 'Block', 'popup-maker' ),
						'inline' => __( 'Inline', 'popup-maker' ),
					),
				),
				'form_alignment' => array(
					'label'   => __( 'Form Alignment', 'popup-maker' ),
					'desc'    => __( 'Choose a form alignment.', 'popup-maker' ),
					'type'    => 'select',
					'options' => array(
						'left'   => __( 'Left', 'popup-maker' ),
						'center' => __( 'Center', 'popup-maker' ),
						'right'  => __( 'Right', 'popup-maker' ),
					),
				),
				'form_style'     => array(
					'label'   => __( 'Form Style', 'popup-maker' ),
					'desc'    => __( 'Choose how you want your form styled.', 'popup-maker' ),
					'type'    => 'select',
					'options' => array(
						''        => __( 'None', 'popup-maker' ),
						'default' => __( 'Default', 'popup-maker' ),
					),
				),
				'layout'         => array(
					'type' => 'hidden',
				),
				'style'          => array(
					'type' => 'hidden',
				),
			),
			'fields'   => array(
				'name_optional' => array(
					'label' => __( 'Name Optional', 'popup-maker' ),
					'desc'  => __( 'Makes the name field optional.', 'popup-maker' ),
					'type'  => 'checkbox',
				),
				'name_disabled' => array(
					'label' => __( 'Name Disabled', 'popup-maker' ),
					'desc'  => __( 'Removes the name field.', 'popup-maker' ),
					'type'  => 'checkbox',
				),
			),
			'labeling' => array(
				'disable_labels'       => array(
					'label' => __( 'Disable Labels', 'popup-maker' ),
					'desc'  => __( 'Disables the display of field labels.', 'popup-maker' ),
					'type'  => 'checkbox',
				),
				'heading_labels'       => array(
					'label' => __( 'Labels', 'popup-maker' ),
					'desc'  => __( 'Field label text', 'popup-maker' ),
					'type'  => 'heading',
				),
				'label_name'           => array(
					'label' => __( 'Name', 'popup-maker' ),
				),
				'label_email'          => array(
					'label' => __( 'Email', 'popup-maker' ),
				),
				'label_submit'         => array(
					'label' => __( 'Submit Button', 'popup-maker' ),
				),
				// Deprecated fields.
				'name_text'            => array(
					'type' => 'hidden',
				),
				'email_text'           => array(
					'type' => 'hidden',
				),
				'button_text'          => array(
					'type' => 'hidden',
				),
				'heading_placeholders' => array(
					'label' => __( 'Placeholders', 'popup-maker' ),
					'desc'  => __( 'Field placeholder text', 'popup-maker' ),
					'type'  => 'heading',
				),
				'placeholder_name'     => array(
					'label' => __( 'Name', 'popup-maker' ),
				),
				'placeholder_email'    => array(
					'label' => __( 'Email', 'popup-maker' ),
				),
			),
			'actions'  => array(
				'closepopup'       => array(
					'label' => __( 'Close Popup', 'popup-maker' ),
					'type'  => 'checkbox',
				),
				'closedelay'       => array(
					'label'        => __( 'Delay', 'popup-maker' ),
					'type'         => 'rangeslider',
					'min'          => 0,
					'max'          => 180,
					'step'         => 1,
					'unit'         => 's',
					'std'          => 0,
					'dependencies' => array(
						'closepopup' => true,
					),
				),
				'openpopup'        => array(
					'label' => __( 'Open Popup', 'popup-maker' ),
					'type'  => 'checkbox',
				),
				'openpopup_id'     => array(
					'label'        => __( 'Popup ID', 'popup-maker' ),
					'type'         => 'select',
					'options'      => array_flip( $this->get_popup_list() ),
					'std'          => 0,
					'dependencies' => array(
						'openpopup' => true,
					),
				),
				'redirect_enabled' => array(
					'label' => __( 'Redirect', 'popup-maker' ),
					'desc'  => __( 'Enable refreshing the page or redirecting after success.', 'popup-maker' ),
					'type'  => 'checkbox',
				),
				'redirect'         => array(
					'label'        => __( 'Redirect URL', 'popup-maker' ),
					'desc'         => __( 'Leave blank to refresh, or enter a url that users will be taken to after success.', 'popup-maker' ),
					'std'          => '',
					'dependencies' => array(
						'redirect_enabled' => true,
					),
				),
			),
		) );
	}

	public function get_popup_list() {
		$popup_list = array(
			0 => __( 'Select a popup', 'popup-maker' ),
		);

		$popups = PUM_Popups::get_all();

		foreach ( $popups->posts as $popup ) {
			$popup_list[ $popup->ID ] = $popup->post_title;
		}

		return $popup_list;
	}

	/**
	 * @return array
	 */
	public function defaults() {
		return apply_filters( 'pum_sub_form_shortcode_defaults', array(
			'provider'          => 'none',
			'name_optional'     => false,
			'name_disabled'     => false,
			'form_alignment'    => 'left',
			'form_layout'       => 'block',
			'form_style'        => 'default',
			'disable_labels'    => false,
			'label_name'        => __( 'Name', 'popup-maker' ),
			'label_email'       => __( 'Email', 'popup-maker' ),
			'label_submit'      => __( 'Subscribe', 'popup-maker' ),
			'placeholder_name'  => __( 'Name', 'popup-maker' ),
			'placeholder_email' => __( 'Email', 'popup-maker' ),
			'closepopup'        => false,
			'closedelay'        => 0,
			'openpopup'         => false,
			'openpopup_id'      => 0,
			'redirect_enabled'  => false,
			'redirect'          => '',
		) );
	}

	/**
	 * Shortcode handler
	 *
	 * @param  array $atts shortcode attributes
	 * @param  string $content shortcode content
	 *
	 * @return string
	 */
	public function handler( $atts, $content = null ) {
		$atts = $this->shortcode_atts( $atts );

		static $instance = 0;

		$instance ++;

		$atts['instance'] = $instance;

		ob_start();

		$data_attr = $this->data_attr( $atts );

		$classes = implode( ' ', array(
			'pum_sub_form',
			$atts['provider'],
			$atts['form_layout'],
			$atts['form_style'],
			'pum-sub-form',
			'pum-form',
			'pum-sub-form--provider-' . $atts['provider'],
			'pum-form--layout-' . $atts['form_layout'],
			'pum-form--style-' . $atts['form_style'],
			'pum-form--alignment-' . $atts['form_alignment'],
		) ); ?>


		<form class="<?php esc_attr_e( $classes ); ?>"
		      data-settings="<?php esc_attr_e( json_encode( $data_attr ) ); ?>">

			<?php do_action( 'pum_sub_form_before', $atts ); ?>

			<?php if ( ! $atts['name_disabled'] ) : ?>

				<div class="pum-form__field  pum-form__field--name  pum-sub-form-field  pum-sub-form-field--name">
					<?php if ( ! $atts['disable_labels'] ) : ?>
						<label class="pum-form__label  pum-sub-form-label"><?php echo $atts['label_name']; ?></label>
					<?php endif; ?>
					<input type="text" name="name" <?php if ( ! $atts['name_optional'] ) : ?> required <?php endif; ?>
					       placeholder="<?php esc_attr_e( $atts['placeholder_name'] ); ?>"/>
				</div>

			<?php endif; ?>

			<div class="pum-form__field  pum-form__field--email  pum-sub-form-field  pum-sub-form-field--email">
				<?php if ( ! $atts['disable_labels'] ) : ?>
					<label class="pum-form__label  pum-sub-form-label"><?php echo $atts['label_email']; ?></label>
				<?php endif; ?>
				<input type="email" name="email" required
				       placeholder="<?php esc_attr_e( $atts['placeholder_email'] ); ?>"/>
			</div>

			<?php do_action( 'pum_sub_form_fields', $atts ); ?>

			<?php do_action( 'pum_newsletter_fields', $atts ); ?>

			<input type="hidden" name="provider" value="<?php echo $atts['provider']; ?>"/>

			<div class="pum-form__field  pum-form__field--submit  pum-sub-form-field  pum-sub-form-field--submit">
				<button class="pum-form__submit  pum-sub-form-submit"><?php echo $atts['label_submit']; ?></button>
			</div>

			<?php do_action( 'pum_sub_form_after', $atts ); ?>
		</form>

		<?php

		//return content
		return ob_get_clean();
	}

	/**
	 * Process shortcode attributes.
	 *
	 * Also remaps and cleans old ones.
	 *
	 * @param $atts
	 *
	 * @return array
	 */
	public function shortcode_atts( $atts ) {
		$atts = parent::shortcode_atts( $atts );

		// Remap old atts.
		if ( ! empty( $atts['layout'] ) ) {
			$atts['form_layout'] = $atts['layout'];
		}
		if ( ! empty( $atts['style'] ) ) {
			$atts['form_style'] = $atts['style'];
		}

		if ( ! empty( $atts['name_text'] ) ) {
			$atts['label_name'] = $atts['name_text'];
		}
		if ( ! empty( $atts['email_text'] ) ) {
			$atts['label_email'] = $atts['email_text'];
		}
		if ( ! empty( $atts['button_text'] ) ) {
			$atts['label_submit'] = $atts['button_text'];
		}

		unset( $atts['layout'], $atts['style'], $atts['name_text'], $atts['email_text'], $atts['button_text'] );

		return $atts;
	}

	/**
	 * Returns array of fields & values that will be passed into data attr of the form.
	 *
	 * @param array $atts
	 *
	 * @return array
	 */
	public function data_attr( $atts = array() ) {
		$data = array();

		$data_attr_fields = $this->data_attr_fields();

		foreach ( $atts as $key => $val ) {
			if ( in_array( $key, $data_attr_fields ) ) {
				$data[ $key ] = $val;
			}
		}

		return $data;
	}

	/**
	 * Returns array of fields that will be passed into data attr of the form.
	 *
	 * @return mixed
	 */
	public function data_attr_fields() {
		return apply_filters( 'pum_sub_form_data_attr_fields', array(
			'closepopup',
			'closedelay',
			'openpopup',
			'openpopup_id',
			'redirect_enabled',
			'redirect',
		) );
	}

	/**
	 *
	 */
	public function _template() { ?>
		<script type="text/html" id="tmpl-pum-shortcode-view-<?php echo $this->tag(); ?>">
			<style>
				<?php // echo readfile( PUM_AVM::$DIR . 'assets/css/site.min.css' ); ?>
			</style>
			<p class="pum-avm-form-desc">
				<?php _e( 'Subscription Form Placeholder', 'popup-maker' ); ?>
			</p>
		</script><?php
	}

	/**
	 * The shortcode tag.
	 */
	public function tag() {
		return 'pum_sub_form';
	}

	public function _template_styles() {
		ob_start();
		include Popup_Maker::$DIR . 'assets/css/site.min.css';

		return ob_get_clean();
	}

}

