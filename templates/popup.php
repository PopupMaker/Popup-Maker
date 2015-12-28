<div id="pum-<?php pum_popup_ID(); ?>" class="<?php pum_popup_classes(); ?>" <?php pum_popup_data_attr(); ?>>

	<div id="popmake-<?php pum_popup_ID(); ?>" class="<?php pum_popup_classes( null, 'container' ); ?>">

		<?php do_action( 'pum_popup_before_title' ); ?>
		<?php do_action( 'popmake_popup_before_inner' ); // Backward compatibility. ?>


		<?php
		/**
		 * Render the title if not empty.
		 */
		?>
		<?php if ( pum_get_popup_title() != '' ) : ?>
			<div class="<?php pum_popup_classes( null, 'title' ); ?>">
				<?php pum_popup_title(); ?>
			</div>
		<?php endif; ?>


		<?php do_action( 'pum_popup_before_content' ); ?>


		<?php
		/**
		 * Render the content.
		 */
		?>
		<div class="<?php pum_popup_classes( null, 'content' ); ?>">
			<?php pum_popup_content(); ?>
		</div>


		<?php do_action( 'pum_popup_after_content' ); ?>
		<?php do_action( 'popmake_popup_after_inner' ); // Backward compatibility. ?>


		<?php
		/**
		 * Render the close button if needed.
		 */
		?>
		<?php if ( pum_show_close_button() ) : ?>
		<span class="<?php pum_popup_classes( null, 'close' ); ?>">
			<?php pum_popup_close_text(); ?>
		</span>
		<?php endif; ?>

	</div>

</div>
