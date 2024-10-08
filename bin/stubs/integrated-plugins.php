<?php
/**
 * Integrated Form Plugin stubs.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 *
 * phpcs:disable
 */

/**
 * Caldera Forms.
 */
define( 'CFCORE_VER', '1.0.0' );

/**
 * Caldera_Forms_Forms
 */
class Caldera_Forms_Forms {
	/**
	 * Get a form.
	 *
	 * @param string $form_id Form ID.
	 *
	 * @return array{ID:string,name:string,description:string}
	 */
	public static function get_form( $form_id ) {}

	/**
	 * Get all forms.
	 *
	 * @param bool $irrelevant Irrelevant.
	 *
	 * @return array<array{ID:int,name:string}>
	 */
	public static function get_forms( $irrelevant = false ) {}
}

/**
 * Contact Form 7
 */

/**
 * Enqueue CF7 styles.
 *
 * @return void
 */
function wpcf7_enqueue_styles() {}

/**
 * Get the current contact form.
 *
 * @return WPCF7_ContactForm|null
 */
function wpcf7_get_current_contact_form() {}

/**
 * WPCF7_ContactForm class.
 */
class WPCF7_ContactForm {
	/**
	 * Get the form ID.
	 *
	 * @return int
	 */
	public function id() {}
}

/**
 * Formidable Forms.
 */
class FrmForm {
	/**
	 * Get a form.
	 *
	 * @param string $form_id Form ID.
	 *
	 * @return object{id:int,name:string}
	 */
	public static function getOne( $form_id ) {}

	/**
	 * Get all forms.
	 *
	 * @param bool $irrelevant Irrelevant.
	 *
	 * @return array<array{ID:string,name:string}>
	 */
	public static function getAll( $irrelevant = false ) {}
}

/**
 * Gravity Forms.
 */

/**
 * Get a value from the query string.
 *
 * @param string              $key — The key
 * @param array<string,mixed> $arr The array to search through. If null, checks query strings. Defaults to null.
 * 
 * @return string — The value. If none found, empty string.
 */
function rgget( $key, $arr = null ) {}

/**
 * GFAPI class.
 */
class GFAPI {
	/**
	 * Get a form.
	 *
	 * @param string $form_id Form ID.
	 *
	 * @return array{id:int,title:string}|array{id:int,title:string}[]
	 */
	public static function get_forms( $form_id = null ) {}
}

/**
 * GFFormSettings class.
 */
class GFFormSettings {
	/**
	 * Page header.
	 *
	 * @param string $title Title.
	 *
	 * @return void
	 */
	public static function page_header( $title ) {}

	/**
	 * Page footer.
	 *
	 * @return void
	 */
	public static function page_footer() {}
}

/**
 * MailChimp for WordPress.
 */
define( 'MC4WP_VERSION', '1.0.0' );

/**
 * MC4WP Get Forms.
 *
 * @return array<array{ID:int,name:string}>
 */
function mc4wp_get_forms() {}

/**
 * MC4WP Get Form.
 *
 * @param string $form_id Form ID.
 *
 * @return array<array{ID:int,name:string}>
 */
function mc4wp_get_form( $form_id ) {}

/**
 * Class MC4WP_Form
 */
class MC4WP_Form {
	/** @var int */
	public $ID;
	/** @var string */
	public $name;
}

/**
 * Ninja Forms.
 *
 * @return Ninja_Forms
 */
function Ninja_Forms() {
}

/**
 * Ninja Forms class.
 */
class Ninja_Forms {
	/**
	 * Form factory.
	 *
	 * @param string $form_id Form ID.
	 *
	 * @return Ninja_Forms_Form_Factory
	 */
	public function form( $form_id ) {}
}

/**
 * Ninja Forms Form Factory.
 */
class Ninja_Forms_Form_Factory {
	/**
	 * Get a form.
	 *
	 * @param string $form_id Form ID.
	 *
	 * @return Ninja_Forms_Form
	 */
	public static function get( $form_id ) {}

	/**
	 * Get all forms.
	 *
	 * @return Ninja_Forms_Form[]
	 */
	public static function get_forms() {}
}

/**
 * Ninja Forms Form.
 */
class Ninja_Forms_Form {

	/**
	 * Get form ID.
	 *
	 * @return int
	 */
	public function get_id() {}

	/**
	 * Get form setting.
	 *
	 * @param string $key Setting key.
	 *
	 * @return mixed
	 */
	public function get_setting( $key ) {}
}

/**
 * Abstract for NF extension actions.
 */
class NF_Abstracts_Action {
	/**
	 * @var string
	 */
	public $_nicename;

	/**
	 * @var array<string,mixed>
	 */
	public $_settings;

	public function __construct() {}
}

/**
 * Pirate Forms.
 */

/**
 * Pirate Forms Util.
 */
class PirateForms_Util {
	/**
	 * Get default form options.
	 *
	 * @param int|null $form_id
	 *
	 * @return array{ID:int,name:string,description:string}|null
	 */
	public static function get_form_options( $form_id = null ) {}
}

/**
 * WP Forms.
 */

define( 'WPFORMS_VERSION', '1.0.0' );

/**
 * WP Forms.
 *
 * @return WP_Forms
 */
function wpforms() {}

/**
 * WP Forms class.
 */
class WP_Forms {
	/**
	 * Form factory.
	 *
	 * @var WP_Forms_Form_Factory
	 */
	public $form;
}

/**
 * WP Forms Form Factory.
 */
class WP_Forms_Form_Factory {
	/**
	 * Get a form.
	 *
	 * @param string $form_id Form ID.
	 * @param array<string,mixed>  $args    Arguments.
	 *
	 * @return WP_Forms_Form
	 */
	public function get( $form_id, $args = [] ) {}
}

/**
 * WP Forms Form.
 */
class WP_Forms_Form {
	/**
	 * The ID of the form.
	 *
	 * @var int
	 */
	public $ID = 0;

	/**
	 * The title of the form.
	 *
	 * @var string
	 */
	public $post_title = '';
}

/**
 * WS Forms
 */
define( 'WS_FORM_VERSION', '1.0.0' );

/**
 * Get all forms.
 *
 * @return array<array{id:int,label:string}>
 */
function wsf_form_get_all() {}

/**
 * Get a form.
 *
 * @param int|string $form_id Form ID.
 *
 * @return array{id:int,label:string}
 */
function wsf_form_get_object( $form_id ) {}

/**
 * Class WS_Form_Submit
 */
class WS_Form_Submit {
	/**
	 * @var int
	 */
	public $form_id;
}

/** 
 * @param string $key
 * 
 * @return ($key is 'forms' ? FluentForms_Forms : mixed)
*/
function fluentFormApi( $key ) {}

class FluentForms_Forms {
	/**
	 * Get a form.
	 *
	 * @param string $form_id Form ID.
	 *
	 * @return object{id:int,title:string}
	 */
	public function find( $form_id ) {}

	/**
	 * Get all forms.
	 *
	 * @param bool $irrelevant Irrelevant.
	 *
	 * @return array<object{id:int,title:string}>
	 */
	public function get_forms( $irrelevant = false ) {}
}
