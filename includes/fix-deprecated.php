<?php
add_filter('popmake_shortcode_popup_atts', 'popmake_shortcode_popup_attribute_deprecated_options');
function popmake_shortcode_popup_attribute_deprecated_options($atts)
{
	if(!empty($atts['theme']))
	{
		 $atts['theme_id'] = $atts['theme'];
		 unset($atts['theme']);
	}
	if(!empty($atts['duration']))
	{
		 $atts['animationSpeed'] = $atts['duration'];
		 unset($atts['duration']);
	}
	if(!empty($atts['overlayEscClose']))
	{
		$atts['escClose'] = $atts['overlayEscClose'];
		unset($atts['overlayEscClose']);
	}
	if(!empty($atts['direction']))
	{
		switch($atts['direction'])
		{
			case 'topleft': $atts['origin'] = 'left top'; break;
			case 'topright': $atts['origin'] = 'right top'; break;
			case 'bottomleft': $atts['origin'] = 'left bottom'; break;
			case 'bottomright': $atts['origin'] = 'right bottom'; break;
			default: $atts['origin'] = $atts['direction']; break;
		}
		unset($atts['direction']);
	}
	return $atts;
}
