<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PopMake_Post_Editor {

	public static function init() {
		add_filter( 'mce_buttons', array( __CLASS__, 'editor_buttons' ), 10, 2 );
		add_filter( 'tiny_mce_before_init', array( __CLASS__, 'format_options' ) );
	}

	/**
	 * Add Buttons To WP Editor Toolbar.
	 */
	public static function editor_buttons( $buttons, $editor_id ){
		/* Add it as first item in the row */
		array_unshift( $buttons, 'styleselect' );
		return $buttons;
	}

	/**
	 * Add Dropcap options to the style_formats drop down.
	 */
	public static function format_options( $settings ){

		/* Our Own Custom Options */
		$popup_formats = array();

		foreach ( get_all_popups()->posts as $popup ) {
			$popup_formats[] = array(
				'title'   => $popup->post_title,
				'inline'  => 'span',
				'classes' => "popmake-{$popup->ID}"
			);
		}

		$new_formats = 	array(
			array(
				'title'   => __( 'Popup Trigger', 'popup-maker' ),
				'items' => $popup_formats
			)
		);

		/* Check if custom "style_formats" is enabled */
		if ( isset( $settings['style_formats'] ) ) {

			/* Get old style_format config */
			$old_formats = json_decode( $settings['style_formats'] );

			/* Merge it with our own */
			$new_formats = array_merge( $new_formats, $old_formats );
		}
		else {
			$new_formats = array_merge( self::default_formats(), $new_formats );
		}

		/* Add it in tinymce config as json data */
		$settings['style_formats'] = json_encode( $new_formats );
		return $settings;
	}

	public static function default_formats() {
		return array(
			array(
				'title'   => __( 'Headings' ),
				'items' => array(
					array(
						'title'   => __( 'Heading 1' ),
						'format'  => 'h1',
					),
					array(
						'title'   => __( 'Heading 2' ),
						'format'  => 'h2',
					),
					array(
						'title'   => __( 'Heading 3' ),
						'format'  => 'h3',
					),
					array(
						'title'   => __( 'Heading 4' ),
						'format'  => 'h4',
					),
					array(
						'title'   => __( 'Heading 5' ),
						'format'  => 'h5',
					),
					array(
						'title'   => __( 'Heading 6' ),
						'format'  => 'h6',
					),
				),
			),
			array(
				'title'   => 'Inline',
				'items' => array(
					array(
						'title'   => __( 'Bold' ),
						'format'  => 'bold',
						'icon'    => 'bold',
					),
					array(
						'title'   => __( 'Italic' ),
						'format'  => 'italic',
						'icon'    => 'italic',
					),
					array(
						'title'   => __( 'Underline' ),
						'format'  => 'underline',
						'icon'    => 'underline',
					),
					array(
						'title'   => __( 'Strikethrough' ),
						'format'  => 'strikethrough',
						'icon'    => 'strikethrough',
					),
					array(
						'title'   => __( 'Superscript' ),
						'format'  => 'superscript',
						'icon'    => 'superscript',
					),
					array(
						'title'   => __( 'Subscript' ),
						'format'  => 'subscript',
						'icon'    => 'subscript',
					),
					array(
						'title'   => __( 'Code' ),
						'format'  => 'code',
						'icon'    => 'code',
					),
				),
			),
			array(
				'title'   => 'Blocks',
				'items' => array(
					array(
						'title'   => __( 'Paragraph' ),
						'format'  => 'p',
					),
					array(
						'title'   => __( 'Blockquote' ),
						'format'  => 'blockquote',
					),
					array(
						'title'   => __( 'Div' ),
						'format'  => 'div',
					),
					array(
						'title'   => __( 'Pre' ),
						'format'  => 'pre',
					),
				),
			),
			array(
				'title'   => 'Alignment',
				'items' => array(
					array(
						'title'   => __( 'Left' ),
						'format'  => 'alignleft',
						'icon'    => 'alignleft',
					),
					array(
						'title'   => __( 'Center' ),
						'format'  => 'aligncenter',
						'icon'    => 'aligncenter',
					),
					array(
						'title'   => __( 'Right' ),
						'format'  => 'alignright',
						'icon'    => 'alignright',
					),
					array(
						'title'   => __( 'Justify' ),
						'format'  => 'alignjustify',
						'icon'    => 'alignjustify',
					),
				),
			),
		);
	}

}

PopMake_Post_Editor::init();

