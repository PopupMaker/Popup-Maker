<?php get_head(); ?>

	<?php popmake_get_template_part( 'popup' ); ?>

	<script>
		jQuery('#popmake-<?php the_popup_ID(); ?>').popmake('open');
	</script>


<?php get_footer(); ?>
