<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<link rel="profile" href="http://gmpg.org/xfn/11" />
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />
	<?php

	// Hack
	$popup_id            = absint( $_GET['p'] );
	pum()->current_popup = pum_get_popup( $popup_id );

	PUM_Site_Assets::enqueue_popup_assets( $popup_id );

	wp_head(); ?>
</head>
<body <?php body_class(); ?> itemscope="itemscope" itemtype="http://schema.org/WebPage">
<?php do_action( 'body_open' ); ?>
<!--<div class="page">-->

<?php if ( have_posts() ) : ?>

	<?php while ( have_posts() ) : the_post(); ?>
		<?php include Popup_Maker::$DIR . 'templates/popup.php'; ?>
	<?php endwhile; ?>

<?php endif; ?>

<!--</div>-->
<?php wp_footer(); ?>
</body>
</html>