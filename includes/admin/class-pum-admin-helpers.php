<?php




class PUM_Admin_Helpers {
	public static function modal( $args = array() ) {
		$args = wp_parse_args( $args, array(
                'id'                 => 'default',
                'title'              => '',
                'description'        => '',
                'class'              => '',
                'cancel_button'      => true,
                'cancel_button_text' => __( 'Cancel', 'popup-maker' ),
                'save_button'        => true,
                'save_button_text'   => __( 'Add', 'popup-maker' ),
		) );
		?>
    <div id="<?php echo $args['id']; ?>" class="pum-modal-background <?php esc_attr_e( $args['class'] ); ?>" role="dialog" aria-hidden="true" aria-labelledby="<?php echo $args['id']; ?>-title" <?php if ( $args['description'] != '' ) { ?>aria-describedby="<?php echo $args['id']; ?>-description"<?php } ?>>

		<div class="pum-modal-wrap">

            <form class="pum-form">

				<div class="pum-modal-header">

					<?php if ( $args['title'] != '' ) { ?>
                        <span id="<?php echo $args['id']; ?>-title" class="pum-modal-title"><?php echo $args['title']; ?></span>
					<?php } ?>
                    <button type="button" class="pum-modal-close" aria-label="<?php _e( 'Close', 'popup-maker' ); ?>"></button>
				</div>

                <?php if ( $args['description'] != '' ) { ?>
                    <span id="<?php echo $args['id']; ?>-description" class="screen-reader-text"><?php echo $args['description']; ?></span>
                <?php } ?>

				<div class="pum-modal-content">
					<?php echo $args['content']; ?>
				</div>

				<?php if ( $args['save_button'] || $args['cancel_button'] ) { ?>
					<div class="pum-modal-footer submitbox">
						<?php if ( $args['cancel_button'] ) { ?>
							<div class="cancel">
                                <button type="button" class="submitdelete no-button" href="#"><?php echo $args['cancel_button_text']; ?></button>
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

	public static function object_to_array( $obj ) {
		if ( is_object( $obj ) ) {
			$obj = ( array ) $obj;
		}
		if ( is_array( $obj ) ) {
			$new = array();
			foreach( $obj as $key => $val ) {
				$new[ $key ] = PUM_Admin_Helpers::object_to_array( $val );
			}
		}
		else {
			$new = $obj;
		}
		return $new;
	}

}

