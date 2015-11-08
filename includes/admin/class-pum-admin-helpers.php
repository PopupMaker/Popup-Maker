<?php




class PUM_Admin_Helpers {
	public static function modal( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'id' => 'default',
			'title' => '',
			'class' => '',
			'cancel_button' => true,
			'cancel_button_text' => __( 'Cancel', 'popup-maker' ),
			'save_button' => true,
			'save_button_text' => __( 'Add', 'popup-maker' ),
		) );
		?>
	<div id="<?php echo $args['id']; ?>" class="pum-modal-background <?php esc_attr_e( $args['class'] ); ?>">

		<div class="pum-modal-wrap">

			<form class="pum-form" tabindex="-1">

				<div class="pum-modal-header">

					<?php if ( $args['title'] != '' ) { ?>
						<span class="pum-modal-title"><?php echo $args['title']; ?></span>
					<?php } ?>
					<div class="pum-modal-close">
						<span class="screen-reader-text"><?php _e( 'Close', 'popup-maker' ); ?></span>
					</div>
				</div>

				<div class="pum-modal-content">
					<?php echo $args['content']; ?>
				</div>

				<?php if ( $args['save_button'] || $args['cancel_button'] ) { ?>
					<div class="pum-modal-footer submitbox">
						<?php if ( $args['cancel_button'] ) { ?>
							<div class="cancel">
								<a class="submitdelete" href="#"><?php echo $args['cancel_button_text']; ?></a>
							</div>
						<?php } ?>
						<?php if ( $args['save_button'] ) { ?>
							<div class="pum-submit">
								<span class="spinner"></span>
								<button class="button button-primary"><?php echo $args['save_button_text']; ?></button>
							</div>
						<?php } ?>
					</div>
				<?php } ?>
			</form>
		</div>
		</div><?php
	}

}

