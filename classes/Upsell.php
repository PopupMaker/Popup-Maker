<?php

class PUM_Upsell {


	public static function init() {

		if ( ! class_exists( 'Popup_Maker_Forced_Interaction' ) && ! class_exists( 'PUM_Forced_Interaction' ) ) {
			add_filter( 'pum_popup_close_settings_fields', array( __CLASS__, 'fi_promotion' ) );
		}

	}

	public static function fi_promotion( $fields ) {
		ob_start();

		?>

		<div class="pum-upgrade-tip">
			<img src="<?php echo Popup_Maker::$URL; ?>/assets/images/upsell-icon-forced-interaction.png" />
			<?php printf( _x( 'Want to disable the close button? Check out %sForced Interaction%s!', '%s represent the opening & closing link html', 'popup-maker' ), '<a href="https://wppopupmaker.com/extensions/forced-interaction/?utm_source=plugin-theme-editor&utm_medium=text-link&utm_campaign=Upsell&utm_content=close-button-settings" target="_blank">', '</a>' ); ?>
		</div>

		<?php

		$html = ob_get_clean();

		return array_merge( $fields, array(
			'fi_promotion' => array(
				'type'     => 'html',
				'content'  => $html,
				'priority' => 7,
			)
		) );
	}

}