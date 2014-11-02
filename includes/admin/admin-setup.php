<?php
function popmake_plugin_action_links($links, $file) {
	if($file == plugin_basename(POPMAKE)) {
		$settings_page_url = admin_url('edit.php?post_type=popup&page=settings');
		$plugin_action_links = apply_filters('popmake_action_links', array(
			'settings' => '<a href="'. $settings_page_url .'">'.__( 'Settings', 'popup-maker' ).'</a>',
			'extensions' => '<a href="https://wppopupmaker.com/extensions?utm_source=em-free&utm_medium=plugins+page&utm_campaign=extensions" target="_blank">'.__('Addons', 'popup-maker' ).'</a>',
		));
		foreach($plugin_action_links  as $link) {
			array_unshift( $links, $link );
		}
	}
	return $links;
}
add_filter('plugin_action_links', 'popmake_plugin_action_links', 10, 2);


function popmake_admin_footer() {
	if(popmake_is_admin_page())
		do_action('popmake_admin_footer');
}
add_action('admin_print_footer_scripts', 'popmake_admin_footer', 1000);


function popmake_admin_footer_social_scripts() {?>
	<div id="fb-root"></div>
	<script>
	jQuery(document).ready(function(){
		(function(d, s, id) {
			var js, fjs = d.getElementsByTagName(s)[0];
			if (d.getElementById(id)) return;
			js = d.createElement(s); js.id = id;
			js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId=191746824208314";
			fjs.parentNode.insertBefore(js, fjs);
		}(document, 'script', 'facebook-jssdk'));
		(function() {
			var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
			po.src = 'https://apis.google.com/js/plusone.js';
			var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
		})();
		!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');
	});
	</script><?php
}
//add_action('popmake_admin_footer', 'popmake_admin_footer_social_scripts');