<?php

class PUM_Upsell {


	public static function init() {
		add_filter( 'pum_popup_close_settings_fields', array( __CLASS__, 'fi_promotion1' ) );
		add_filter( 'pum_popup_close_settings_fields', array( __CLASS__, 'fi_promotion2' ) );
		add_filter( 'pum_popup_targeting_settings_fields', array( __CLASS__, 'atc_promotion' ) );
		add_filter( 'pum_theme_overlay_settings_fields', array( __CLASS__, 'atb_promotion' ) );
	}

	/**
	 * @param $fields
	 *
	 * @return mixed
	 */
	public static function fi_promotion1( $fields ) {
		if ( pum_extension_enabled( 'forced-interaction' ) ) {
			return $fields;
		}

		ob_start();

		?>

		<div class="pum-upgrade-tip">
			<img src="<?php echo Popup_Maker::$URL; ?>/assets/images/upsell-icon-forced-interaction.png" />
			<?php printf( _x( 'Want to disable the close button? Check out %sForced Interaction%s!', '%s represent the opening & closing link html', 'popup-maker' ), '<a href="https://wppopupmaker.com/extensions/forced-interaction/?utm_source=plugin-theme-editor&utm_medium=text-link&utm_campaign=Upsell&utm_content=close-button-settings" target="_blank">', '</a>' ); ?>
		</div>

		<?php

		$html = ob_get_clean();

		$key = key( $fields );

		$fields[ $key ]['fi_promotion'] = array(
			'type'     => 'html',
			'content'  => $html,
			'priority' => 30,
		);

		return $fields;
	}

	/**
	 * @param $fields
	 *
	 * @return mixed
	 */
	public static function fi_promotion2( $fields ) {
		if ( pum_extension_enabled( 'forced-interaction' ) ) {
			return $fields;
		}

		ob_start();

		?>

		<div class="pum-upgrade-tip">
			<img src="<?php echo Popup_Maker::$URL; ?>/assets/images/upsell-icon-forced-interaction.png" />
			<?php printf( _x( 'Want to disable the close button? Check out %sForced Interaction%s!', '%s represent the opening & closing link html', 'popup-maker' ), '<a href="https://wppopupmaker.com/extensions/forced-interaction/?utm_source=plugin-theme-editor&utm_medium=text-link&utm_campaign=Upsell&utm_content=close-button-settings" target="_blank">', '</a>' ); ?>
		</div>

		<?php

		$html = ob_get_clean();

		$key = key( $fields );

		$fields[ $key ]['fi_promotion'] = array(
			'type'     => 'html',
			'content'  => $html,
			'priority' => 0,
		);

		return $fields;
	}

	public static function atc_promotion( $fields ) {
		if ( pum_extension_enabled( 'advanced-targeting-conditions' ) ) {
			return $fields;
		}

		ob_start();

		?>

		<div class="pum-upgrade-tip">
			<img src="<?php echo Popup_Maker::$URL; ?>/assets/images/logo.png" height="28" />
			<?php printf( __( 'Need more %sadvanced targeting%s options?', 'popup-maker' ), '<a href="https://wppopupmaker.com/extensions/advanced-targeting-conditions/?utm_campaign=Upsell&utm_source=plugin-popup-editor&utm_medium=text-link&utm_content=conditions-editor" target="_blank">', '</a>' ); ?>
		</div>

		<?php

		$html = ob_get_clean();
		$key  = key( $fields );


		$fields[ $key ]['atc_promotion'] = array(
			'type'     => 'html',
			'content'  => $html,
			'priority' => 30,
		);

		return $fields;
	}

	public static function atb_promotion( $fields ) {
		if ( pum_extension_enabled( 'advanced-theme-builder' ) ) {
			return $fields;
		}

		$tab = 'general';
		$subtab = 'background';
		$pri = 0;

		switch ( current_filter() ) {
			case 'pum_theme_overlay_settings_fields':
				$tab = 'overlay';
				break;
			case 'pum_theme_container_settings_fields':
				$tab = 'container';
				break;
			case 'pum_theme_title_settings_fields':
				$tab = 'title';
				break;
			case 'pum_theme_content_settings_fields':
				$tab = 'content';
				break;
			case 'pum_theme_close_settings_fields':
				$tab = 'close';
				break;
		}

		ob_start();

		?>

		<div class="pum-upgrade-tip">
			<img src="<?php echo Popup_Maker::$URL; ?>/assets/images/upsell-icon-advanted-theme-builder.png" height="28" />
			<?php printf( __( 'Want to use %sbackground images%s?', 'popup-maker' ), '<a href="https://wppopupmaker.com/extensions/advanced-theme-builder/?utm_campaign=Upsell&utm_source=plugin-theme-editor&utm_medium=text-link&utm_content=' . $tab . '-settings" target="_blank">', '</a>' ); ?>
		</div>

		<?php

		$html = ob_get_clean();

		$fields[ $tab ][ $subtab ]['atb_promotion'] = array(
			'type'     => 'html',
			'content'  => $html,
			'priority' => $pri,
		);

		return $fields;
	}

}