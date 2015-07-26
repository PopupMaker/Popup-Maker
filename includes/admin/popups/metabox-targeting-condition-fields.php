<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders popup load settings fields
 * @since 1.0
 *
 * @param $post_id
 */

if ( ! function_exists( 'get_term_name' ) ) {
	function get_term_name( $term_id, $taxonomy ) {
		$term = get_term_by( 'id', absint( $term_id ), $taxonomy );

		return $term->name;
	}
}


add_action( 'popmake_popup_targeting_condition_meta_box_fields', 'popmake_popup_targeting_condition_meta_box_fields', 10 );
function popmake_popup_targeting_condition_meta_box_fields( $popup_id ) {
	$targeting_condition = popmake_get_popup_targeting_condition( $popup_id );
	/**
	 * Create nonce used for post type and taxonomy ajax searches. Copied from wp-admin/includes/nav-menu.php
	 */
	wp_nonce_field( 'add-menu_item', 'menu-settings-column-nonce' );

	/**
	 * Render Load on entire site toggle.
	 */ ?>
	<div id="targeting_condition-on_entire_site" class="targeting_condition form-table">
		<input type="checkbox"
		       id="popup_targeting_condition_on_entire_site"
		       name="popup_targeting_condition_on_entire_site"
		       value="true"
			<?php if ( ! empty( $targeting_condition['on_entire_site'] ) ) {
				echo 'checked="checked" ';
			} ?>
			/>
		<label for="popup_targeting_condition_on_entire_site"><?php _e( 'On Entire Site', 'popup-maker' ); ?></label>

		<div class="options">
			<?php do_action( "popmake_popup_targeting_condition_on_entire_site_options", $targeting_condition ); ?>
		</div>
	</div>
	<div id="targeting_condition-on_home" class="targeting_condition form-table">
		<input type="checkbox"
		       id="popup_targeting_condition_on_home"
		       name="popup_targeting_condition_on_home"
		       value="true"
			<?php if ( ! empty( $targeting_condition['on_home'] ) ) {
				echo 'checked="checked" ';
			} ?>
			/>
		<label for="popup_targeting_condition_on_home"><?php _e( 'On Home Page', 'popup-maker' ); ?></label>

		<div class="options">
			<?php do_action( "popmake_popup_targeting_condition_on_home_options", $targeting_condition ); ?>
		</div>
	</div>
	<div id="targeting_condition-exclude_on_home" class="targeting_condition form-table">
		<input type="checkbox"
		       id="popup_targeting_condition_exclude_on_home"
		       name="popup_targeting_condition_exclude_on_home"
		       value="true"
			<?php if ( ! empty( $targeting_condition['exclude_on_home'] ) ) {
				echo 'checked="checked" ';
			} ?>
			/>
		<label for="popup_targeting_condition_exclude_on_home"><?php _e( 'Exclude on Home Page', 'popup-maker' ); ?></label>

		<div class="options">
			<?php do_action( "popmake_popup_targeting_condition_exclude_on_home_options", $targeting_condition ); ?>
		</div>
	</div>
	<div id="targeting_condition-on_blog" class="targeting_condition form-table">
		<input type="checkbox"
		       id="popup_targeting_condition_on_blog"
		       name="popup_targeting_condition_on_blog"
		       value="true"
			<?php if ( ! empty( $targeting_condition['on_blog'] ) ) {
				echo 'checked="checked" ';
			} ?>
			/>
		<label for="popup_targeting_condition_on_blog"><?php _e( 'On Blog Index', 'popup-maker' ); ?></label>

		<div class="options">
			<?php do_action( "popmake_popup_targeting_condition_on_blog_options", $targeting_condition ); ?>
		</div>
	</div>
	<div id="targeting_condition-exclude_on_blog" class="targeting_condition form-table">
	<input type="checkbox"
	       id="popup_targeting_condition_exclude_on_blog"
	       name="popup_targeting_condition_exclude_on_blog"
	       value="true"
		<?php if ( ! empty( $targeting_condition['exclude_on_blog'] ) ) {
			echo 'checked="checked" ';
		} ?>
		/>
	<label for="popup_targeting_condition_exclude_on_blog"><?php _e( 'Exclude on Blog Index', 'popup-maker' ); ?></label>

	<div class="options">
		<?php do_action( "popmake_popup_targeting_condition_exclude_on_blog_options", $targeting_condition ); ?>
	</div>
	</div><?php

	do_action( 'popmake_before_post_type_targeting_conditions', $targeting_condition );

	$includes = popmake_get_popup_targeting_condition_includes( $popup_id );
	$excludes = popmake_get_popup_targeting_condition_excludes( $popup_id );

	foreach ( popmake_get_supported_types() as $pt ) {
		$is_post_type = get_post_type_object( $pt );
		$labels       = $is_post_type ? $is_post_type : get_taxonomy( $pt );
		if ( ! $labels ) {
			continue;
		}
		$plural = esc_attr( strtolower( $labels->labels->name ) );

		foreach ( array( 'include', 'exclude' ) as $include_exclude ) {
			$key     = ( $include_exclude != 'include' ? 'exclude_' : '' ) . "on_{$pt}s";
			$current = $include_exclude == 'include' ?
				( ! empty( $includes[ $pt ] ) ? $includes[ $pt ] : array() ) :
				( ! empty( $excludes[ $pt ] ) ? $excludes[ $pt ] : array() ); ?>
		<div id="targeting_condition-<?php echo $key; ?>" class="targeting_condition form-table">
			<input type="checkbox"
			       id="popup_targeting_condition_<?php echo $key; ?>"
			       name="popup_targeting_condition_<?php echo $key; ?>"
			       value="true"
				<?php if ( ! empty( $targeting_condition[ $key ] ) ) {
					echo 'checked="checked" ';
				} ?>
				/><?php
			$label = ( $include_exclude != 'include' ? 'Exclude ' : '' ) . 'On '; ?>
			<label for="popup_targeting_condition_<?php echo $key; ?>"><?php echo __( $label, 'popup-maker' ) . $labels->labels->name; ?></label>

			<div class="options">
				<p style="margin:0;"><?php
					$key = ( $include_exclude != 'include' ? 'exclude_' : '' ) . "on_specific_{$pt}s"; ?>
					<input type="checkbox" style="display:none" name="popup_targeting_condition_<?php echo $key; ?>" value="true" <?php if ( isset( $targeting_condition[ $key ] ) ) {
						echo 'checked';
					} ?>/>
					<label><?php
						$label = ( $include_exclude == 'include' ? 'Load' : 'Exclude' ) . ' on All ';
						echo __( $label, 'popup-maker' ) . $labels->labels->name; ?>
						<input type="radio"
						       name="<?php echo $key; ?>"
						       id="popup_targeting_condition_<?php echo $key; ?>"
						       value=""
							<?php if ( ! isset( $targeting_condition[ $key ] ) ) {
								echo 'checked';
							} ?>
							/>
					</label><br/>
					<label><?php
						$label = ( $include_exclude == 'include' ? 'Load' : 'Exclude' ) . ' on Specific ';
						echo __( $label, 'popup-maker' ) . $labels->labels->name; ?>
						<input type="radio"
						       name="<?php echo $key; ?>"
						       id="popup_targeting_condition_<?php echo $key; ?>"
						       value="true"
							<?php if ( isset( $targeting_condition[ $key ] ) ) {
								echo 'checked';
							} ?>
							/>
					</label>
				</p>

				<div id="<?php echo $key; ?>">
					<div class="nojs-tags hide-if-js">
							<textarea
								name="popup_targeting_condition_<?php echo $include_exclude == 'exclude' ? 'exclude_' : ''; ?>on_<?php echo $pt; ?>"
								rows="3" cols="20"
								id="popup_targeting_condition_<?php echo $include_exclude == 'exclude' ? 'exclude_' : ''; ?>on_<?php echo $pt; ?>"
								><?php esc_html_e( trim( implode( ',', $current ) ) ); ?></textarea>
					</div>
					<div class="hide-if-no-js"><?php
						if ( $is_post_type ) {
							popmake_post_type_item_metabox( $pt );
						} else {
							popmake_taxonomy_item_metabox( $pt );
						} ?>
						<div class="tagchecklist"><?php
							foreach ( $current as $post_id ) { ?>
								<span><a class="ntdelbutton" data-id="<?php echo $post_id; ?>">X</a>
								<?php echo $is_post_type ? get_the_title( $post_id ) : get_term_name( $post_id, $pt ); ?>
								</span><?php
							} ?>
						</div>
					</div>
					<hr/>
				</div>
			</div>
			</div><?php
		}
	} ?>
	<div id="targeting_condition-on_search" class="targeting_condition form-table">
		<input type="checkbox"
		       id="popup_targeting_condition_on_search"
		       name="popup_targeting_condition_on_search"
		       value="true"
			<?php if ( ! empty( $targeting_condition['on_search'] ) ) {
				echo 'checked="checked" ';
			} ?>
			/>
		<label for="popup_targeting_condition_on_search"><?php _e( 'On Search Pages', 'popup-maker' ); ?></label>

		<div class="options">
			<?php do_action( "popmake_popup_targeting_condition_on_search_options", $targeting_condition ); ?>
		</div>
	</div>
	<div id="targeting_condition-exclude_on_search" class="targeting_condition form-table">
		<input type="checkbox"
		       id="popup_targeting_condition_exclude_on_search"
		       name="popup_targeting_condition_exclude_on_search"
		       value="true"
			<?php if ( ! empty( $targeting_condition['exclude_on_search'] ) ) {
				echo 'checked="checked" ';
			} ?>
			/>
		<label for="popup_targeting_condition_exclude_on_search"><?php _e( 'Exclude on Search Pages', 'popup-maker' ); ?></label>

		<div class="options">
			<?php do_action( "popmake_popup_targeting_condition_exclude_on_search_options", $targeting_condition ); ?>
		</div>
	</div>
	<div id="targeting_condition-on_404" class="targeting_condition form-table">
		<input type="checkbox"
		       id="popup_targeting_condition_on_404"
		       name="popup_targeting_condition_on_404"
		       value="true"
			<?php if ( ! empty( $targeting_condition['on_404'] ) ) {
				echo 'checked="checked" ';
			} ?>
			/>
		<label for="popup_targeting_condition_on_404"><?php _e( 'On 404 Pages', 'popup-maker' ); ?></label>

		<div class="options">
			<?php do_action( "popmake_popup_targeting_condition_on_404_options", $targeting_condition ); ?>
		</div>
	</div>
	<div id="targeting_condition-exclude_on_404" class="targeting_condition form-table">
	<input type="checkbox"
	       id="popup_targeting_condition_exclude_on_404"
	       name="popup_targeting_condition_exclude_on_404"
	       value="true"
		<?php if ( ! empty( $targeting_condition['exclude_on_404'] ) ) {
			echo 'checked="checked" ';
		} ?>
		/>
	<label for="popup_targeting_condition_exclude_on_404"><?php _e( 'Exclude on 404 Pages', 'popup-maker' ); ?></label>

	<div class="options">
		<?php do_action( "popmake_popup_targeting_condition_exclude_on_404_options", $targeting_condition ); ?>
	</div>
	</div><?php
}