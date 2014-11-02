<?php
/**
 * Renders popup load settings fields
 * @since 1.0
 * @param $post_id
 */

if(!function_exists('get_term_name')) {
	function get_term_name( $term_id , $taxonomy ) {
		$term = get_term_by( 'id', absint( $term_id ), $taxonomy );
		return $term->name;
	}
}


add_action('popmake_popup_loading_condition_meta_box_fields', 'popmake_popup_loading_condition_meta_box_fields', 10);
function popmake_popup_loading_condition_meta_box_fields( $popup_id ) {
	$loading_condition = popmake_get_popup_loading_condition( $popup_id );
	/**
	 * Create nonce used for post type and taxonomy ajax searches. Copied from wp-admin/includes/nav-menu.php
	 */
	wp_nonce_field( 'add-menu_item', 'menu-settings-column-nonce' );

	/**
	 * Render Load on entire site toggle.
	 */?>
	<div id="loading_condition-on_entire_site" class="loading_condition form-table">
		<input type="checkbox"
			id="popup_loading_condition_on_entire_site"
			name="popup_loading_condition_on_entire_site"
			value="true"
			<?php if(!empty($loading_condition['on_entire_site'])) echo 'checked="checked" '; ?>
		/>
		<label for="popup_loading_condition_on_entire_site"><?php _e( 'On Entire Site', 'popup-maker' ); ?></label>
		<div class="options">
			<?php do_action("popmake_popup_loading_condition_on_entire_site_options", $loading_condition); ?>
		</div>
	</div>
	<div id="loading_condition-on_home" class="loading_condition form-table">
		<input type="checkbox"
			id="popup_loading_condition_on_home"
			name="popup_loading_condition_on_home"
			value="true"
			<?php if(!empty($loading_condition['on_home'])) echo 'checked="checked" '; ?>
		/>
		<label for="popup_loading_condition_on_home"><?php _e( 'On Home Page', 'popup-maker' ); ?></label>
		<div class="options">
			<?php do_action("popmake_popup_loading_condition_on_home_options", $loading_condition); ?>
		</div>
	</div>
	<div id="loading_condition-exclude_on_home" class="loading_condition form-table">
			<input type="checkbox"
			id="popup_loading_condition_exclude_on_home"
			name="popup_loading_condition_exclude_on_home"
			value="true"
			<?php if(!empty($loading_condition['exclude_on_home'])) echo 'checked="checked" '; ?>
		/>
		<label for="popup_loading_condition_exclude_on_home"><?php _e( 'Exclude on Home Page', 'popup-maker' ); ?></label>
		<div class="options">
			<?php do_action("popmake_popup_loading_condition_exclude_on_home_options", $loading_condition); ?>
		</div>
	</div><?php

	$includes = popmake_get_popup_loading_condition_includes( $popup_id );
	$excludes = popmake_get_popup_loading_condition_excludes( $popup_id );

	foreach( popmake_get_supported_types() as $name ) {
		$is_post_type = get_post_type_object( $name );
		$labels = $is_post_type ? $is_post_type : get_taxonomy( $name );
		$plural = esc_attr( strtolower( $labels->labels->name ) );

		foreach(array('include', 'exclude') as $include_exclude) {
			$key = ($include_exclude != 'include' ? 'exclude_' : '') . "on_{$plural}";
			$current = $include_exclude == 'include' ?
				(!empty($includes[$name]) ? $includes[$name] : array()) :
				(!empty($excludes[$name]) ? $excludes[$name] : array()); ?>
			<div id="loading_condition-<?php echo $key; ?>" class="loading_condition form-table">
				<input type="checkbox"
					id="popup_loading_condition_<?php echo $key; ?>"
					name="popup_loading_condition_<?php echo $key; ?>"
					value="true"
					<?php if(!empty($loading_condition[$key])) echo 'checked="checked" '; ?>
				/><?php
				$label = ($include_exclude != 'include' ? 'Exclude ' : '') . 'On ';?>
				<label for="popup_loading_condition_<?php echo $key; ?>"><?php echo __( $label, 'popup-maker' ) . $labels->labels->name; ?></label>
				<div class="options">
					<p style="margin:0;">
						<label><?php
							$key = ($include_exclude != 'include' ? 'exclude_' : '') . "on_specific_{$plural}";
							$label = ($include_exclude == 'include' ? 'Load' : 'Exclude') . ' on All ';
							echo __( $label, 'popup-maker' ) . $labels->labels->name; ?>
							<input type="radio"
								name="popup_loading_condition_<?php echo $key; ?>"
								value=""
								<?php if(!isset($loading_condition[$key])) echo 'checked'; ?>
							/>
						</label><br/>
						<label><?php
							$label = ($include_exclude == 'include' ? 'Load' : 'Exclude') . ' on Specific ';
							echo __( $label, 'popup-maker' ) . $labels->labels->name; ?>
							<input type="radio"
								name="popup_loading_condition_<?php echo $key; ?>"
								value="true"
								<?php if(isset($loading_condition[$key])) echo 'checked'; ?>
							/>
						</label>
					</p>
					<div id="<?php echo $key; ?>">
						<div class="nojs-tags hide-if-js">
							<textarea
								name="popup_loading_condition_<?php echo $include_exclude == 'exclude' ? 'exclude_' : '';?>on_<?php echo $name; ?>"
								rows="3" cols="20"
								id="popup_loading_condition_<?php echo $include_exclude == 'exclude' ? 'exclude_' : '';?>on_<?php echo $name; ?>"
							><?php esc_html_e( trim( implode(',', $current) ) );?></textarea>
						</div>
						<div class="hide-if-no-js"><?php
							if($is_post_type) popmake_post_type_item_metabox( $name );
							else popmake_taxonomy_item_metabox( $name );?>						
							<div class="tagchecklist"><?php
								foreach($current as $post_id) { ?>
									<span><a class="ntdelbutton" data-id="<?php echo $post_id;?>">X</a>
										<?php echo $is_post_type ? get_the_title( $post_id ) : get_term_name( $post_id, $name );?>
									</span><?php
								}?>
							</div>
						</div>
						<hr/>
					</div>
				</div>
			</div><?php
		}
	}
}