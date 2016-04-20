<div id="popmake-<?php popmake_the_popup_ID(); ?>" class="<?php popmake_the_popup_classes(); ?>" <?php popmake_the_popup_data_attr(); ?>>

	<?php do_action('popmake_popup_before_inner'); ?>

	<?php if( popmake_get_the_popup_title() != '' ) : ?>
		<div class="popmake-title"><?php popmake_the_popup_title(); ?></div>
	<?php endif; ?>

	<?php popmake_the_popup_content( ); ?>

	<?php do_action('popmake_popup_after_inner'); ?>

</div>