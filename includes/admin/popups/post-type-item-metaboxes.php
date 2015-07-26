<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Displays a metabox for a post type menu item.
 *
 * @since 1.0.0
 *
 * @param string $object Not used.
 * @param string $post_type The post type object.
 */
function popmake_post_type_item_metabox( $post_type_name ) {
	if ( ! function_exists( 'wp_nav_menu_item_post_type_meta_box' ) ) {
		include ABSPATH . 'wp-admin/includes/nav-menu.php';
	}
	global $_nav_menu_placeholder, $nav_menu_selected_id;

	$post_type = get_post_type_object( $post_type_name );


	// Paginate browsing for large numbers of post objects.
	$per_page = 50;
	$pagenum  = isset( $_REQUEST[ $post_type_name . '-tab' ] ) && isset( $_REQUEST['paged'] ) ? absint( $_REQUEST['paged'] ) : 1;
	$offset   = 0 < $pagenum ? $per_page * ( $pagenum - 1 ) : 0;

	$args = array(
		'offset'                 => $offset,
		'order'                  => 'ASC',
		'orderby'                => 'title',
		'posts_per_page'         => $per_page,
		'post_type'              => $post_type_name,
		'suppress_filters'       => true,
		'update_post_term_cache' => false,
		'update_post_meta_cache' => false
	);

	if ( isset( $post_type->_default_query ) ) {
		$args = array_merge( $args, (array) $post_type->_default_query );
	}

	// @todo transient caching of these results with proper invalidation on updating of a post of this type
	$get_posts = new WP_Query;
	$posts     = $get_posts->query( $args );
	if ( ! $get_posts->post_count ) {
		echo '<p>' . __( 'No items.' ) . '</p>';

		return;
	}

	$num_pages = $get_posts->max_num_pages;

	$page_links = paginate_links( array(
		'base'      => add_query_arg(
			array(
				$post_type_name . '-tab' => 'all',
				'paged'                  => '%#%',
				'item-type'              => 'post_type',
				'item-object'            => $post_type_name,
			)
		),
		'format'    => '',
		'prev_text' => __( '&laquo;' ),
		'next_text' => __( '&raquo;' ),
		'total'     => $num_pages,
		'current'   => $pagenum
	) );

	$db_fields = false;
	if ( is_post_type_hierarchical( $post_type_name ) ) {
		$db_fields = array( 'parent' => 'post_parent', 'id' => 'ID' );
	}

	$walker = new Walker_Nav_Menu_Checklist( $db_fields );

	$current_tab = 'most-recent';
	if ( isset( $_REQUEST[ $post_type_name . '-tab' ] ) && in_array( $_REQUEST[ $post_type_name . '-tab' ], array(
			'all',
			'search'
		) )
	) {
		$current_tab = $_REQUEST[ $post_type_name . '-tab' ];
	}

	if ( ! empty( $_REQUEST[ 'quick-search-posttype-' . $post_type_name ] ) ) {
		$current_tab = 'search';
	}

	$removed_args = array(
		'action',
		'customlink-tab',
		'edit-menu-item',
		'menu-item',
		'page-tab',
		'_wpnonce',
	);

	?>
	<div id="posttype-<?php echo $post_type_name; ?>" class="posttypediv">
		<ul id="posttype-<?php echo $post_type_name; ?>-tabs" class="posttype-tabs category-tabs add-menu-item-tabs">
			<li <?php echo( 'most-recent' == $current_tab ? ' class="tabs"' : '' ); ?>>
				<a class="nav-tab-link" data-type="tabs-panel-posttype-<?php echo esc_attr( $post_type_name ); ?>-most-recent" href="<?php if ( $nav_menu_selected_id ) {
					echo esc_url( add_query_arg( $post_type_name . '-tab', 'most-recent', remove_query_arg( $removed_args ) ) );
				} ?>#tabs-panel-posttype-<?php echo $post_type_name; ?>-most-recent">
					<?php _e( 'Most Recent' ); ?>
				</a>
			</li>
			<li <?php echo( 'all' == $current_tab ? ' class="tabs"' : '' ); ?>>
				<a class="nav-tab-link" data-type="<?php echo esc_attr( $post_type_name ); ?>-all" href="<?php if ( $nav_menu_selected_id ) {
					echo esc_url( add_query_arg( $post_type_name . '-tab', 'all', remove_query_arg( $removed_args ) ) );
				} ?>#<?php echo $post_type_name; ?>-all">
					<?php _e( 'View All' ); ?>
				</a>
			</li>
			<li <?php echo( 'search' == $current_tab ? ' class="tabs"' : '' ); ?>>
				<a class="nav-tab-link" data-type="tabs-panel-posttype-<?php echo esc_attr( $post_type_name ); ?>-search" href="<?php if ( $nav_menu_selected_id ) {
					echo esc_url( add_query_arg( $post_type_name . '-tab', 'search', remove_query_arg( $removed_args ) ) );
				} ?>#tabs-panel-posttype-<?php echo $post_type_name; ?>-search">
					<?php _e( 'Search' ); ?>
				</a>
			</li>
		</ul>
		<!-- .posttype-tabs -->

		<div id="tabs-panel-posttype-<?php echo $post_type_name; ?>-most-recent" class="tabs-panel <?php
		echo( 'most-recent' == $current_tab ? 'tabs-panel-active' : 'tabs-panel-inactive' );
		?>">
			<ul id="<?php echo $post_type_name; ?>checklist-most-recent" class="categorychecklist form-no-clear">
				<?php
				$recent_args    = array_merge( $args, array(
					'orderby'        => 'post_date',
					'order'          => 'DESC',
					'posts_per_page' => 15
				) );
				$most_recent    = $get_posts->query( $recent_args );
				$args['walker'] = $walker;
				echo walk_nav_menu_tree( array_map( 'wp_setup_nav_menu_item', $most_recent ), 0, (object) $args );
				?>
			</ul>
		</div>
		<!-- /.tabs-panel -->

		<div class="tabs-panel <?php
		echo( 'search' == $current_tab ? 'tabs-panel-active' : 'tabs-panel-inactive' );
		?>" id="tabs-panel-posttype-<?php echo $post_type_name; ?>-search">
			<?php
			if ( isset( $_REQUEST[ 'quick-search-posttype-' . $post_type_name ] ) ) {
				$searched       = esc_attr( $_REQUEST[ 'quick-search-posttype-' . $post_type_name ] );
				$search_results = get_posts( array(
					's'         => $searched,
					'post_type' => $post_type_name,
					'fields'    => 'all',
					'order'     => 'DESC',
				) );
			} else {
				$searched       = '';
				$search_results = array();
			}
			?>
			<p class="quick-search-wrap">
				<input type="search" class="quick-search input-with-default-title" title="<?php esc_attr_e( 'Search' ); ?>" value="<?php echo $searched; ?>" name="quick-search-posttype-<?php echo $post_type_name; ?>"/>
				<span class="spinner"></span>
				<?php submit_button( __( 'Search' ), 'button-small quick-search-submit button-secondary hide-if-js', 'submit', false, array( 'id' => 'submit-quick-search-posttype-' . $post_type_name ) ); ?>
			</p>

			<ul id="<?php echo $post_type_name; ?>-search-checklist" data-wp-lists="list:<?php echo $post_type_name ?>" class="categorychecklist form-no-clear">
				<?php if ( ! empty( $search_results ) && ! is_wp_error( $search_results ) ) : ?>
					<?php
					$args['walker'] = $walker;
					echo walk_nav_menu_tree( array_map( 'wp_setup_nav_menu_item', $search_results ), 0, (object) $args );
					?>
				<?php elseif ( is_wp_error( $search_results ) ) : ?>
					<li><?php echo $search_results->get_error_message(); ?></li>
				<?php elseif ( ! empty( $searched ) ) : ?>
					<li><?php _e( 'No results found.' ); ?></li>
				<?php endif; ?>
			</ul>
		</div>
		<!-- /.tabs-panel -->

		<div id="<?php echo $post_type_name; ?>-all" class="tabs-panel tabs-panel-view-all <?php
		echo( 'all' == $current_tab ? 'tabs-panel-active' : 'tabs-panel-inactive' );
		?>">
			<?php if ( ! empty( $page_links ) ) : ?>
				<div class="add-menu-item-pagelinks">
					<?php echo $page_links; ?>
				</div>
			<?php endif; ?>
			<ul id="<?php echo $post_type_name; ?>checklist" data-wp-lists="list:<?php echo $post_type_name ?>" class="categorychecklist form-no-clear">
				<?php
				$args['walker'] = $walker;

				/*
				 * If we're dealing with pages, let's put a checkbox for the front
				 * page at the top of the list.
				 */
				if ( 'page' == $post_type_name ) {
					$front_page = 'page' == get_option( 'show_on_front' ) ? (int) get_option( 'page_on_front' ) : 0;
					if ( ! empty( $front_page ) ) {
						$front_page_obj                = get_post( $front_page );
						$front_page_obj->front_or_home = true;
						array_unshift( $posts, $front_page_obj );
					} else {
						$_nav_menu_placeholder = ( 0 > $_nav_menu_placeholder ) ? intval( $_nav_menu_placeholder ) - 1 : - 1;
						array_unshift( $posts, (object) array(
							'front_or_home' => true,
							'ID'            => 0,
							'object_id'     => $_nav_menu_placeholder,
							'post_content'  => '',
							'post_excerpt'  => '',
							'post_parent'   => '',
							'post_title'    => _x( 'Home', 'nav menu home label' ),
							'post_type'     => 'nav_menu_item',
							'type'          => 'custom',
							'url'           => home_url( '/' ),
						) );
					}
				}

				/**
				 * Filter the posts displayed in the 'View All' tab of the current
				 * post type's menu items meta box.
				 *
				 * The dynamic portion of the hook name, $post_type_name,
				 * refers to the slug of the current post type.
				 *
				 * @since 3.2.0
				 *
				 * @see WP_Query::query()
				 *
				 * @param array $posts The posts for the current post type.
				 * @param array $args An array of WP_Query arguments.
				 * @param object $post_type The current post type object for this menu item meta box.
				 */
				$posts          = apply_filters( "nav_menu_items_{$post_type_name}", $posts, $args, $post_type );
				$checkbox_items = walk_nav_menu_tree( array_map( 'wp_setup_nav_menu_item', $posts ), 0, (object) $args );

				if ( 'all' == $current_tab && ! empty( $_REQUEST['selectall'] ) ) {
					$checkbox_items = preg_replace( '/(type=(.)checkbox(\2))/', '$1 checked=$2checked$2', $checkbox_items );

				}

				echo $checkbox_items;
				?>
			</ul>
			<?php if ( ! empty( $page_links ) ) : ?>
				<div class="add-menu-item-pagelinks">
					<?php echo $page_links; ?>
				</div>
			<?php endif; ?>
		</div>
		<!-- /.tabs-panel -->

		<p class="button-controls">
			<span class="list-controls">
				<a href="<?php
				echo esc_url( add_query_arg(
					array(
						$post_type_name . '-tab' => 'all',
						'selectall'              => 1,
					),
					remove_query_arg( $removed_args )
				) );
				?>#posttype-<?php echo $post_type_name; ?>" class="select-all"><?php _e( 'Select All' ); ?></a>
			</span>

			<span class="add-to-list">
				<button type="button" <?php wp_nav_menu_disabled_check( $nav_menu_selected_id ); ?> class="button-secondary submit-add-to-menu right" id="<?php echo esc_attr( 'submit-posttype-' . $post_type_name ); ?>"><?php esc_attr_e( 'Add Selected' ); ?></button>
				<span class="spinner"></span>
			</span>
		</p>

	</div><!-- /.posttypediv -->
	<?php
}

/**
 * Displays a metabox for a taxonomy menu item.
 *
 * @since 1.0.0
 *
 * @param string $taxonomy The taxonomy object.
 */
function popmake_taxonomy_item_metabox( $taxonomy_name ) {
	if ( ! function_exists( 'wp_nav_menu_item_post_type_meta_box' ) ) {
		include ABSPATH . 'wp-admin/includes/nav-menu.php';
	}
	global $nav_menu_selected_id;

	$taxonomy = get_taxonomy( $taxonomy_name );

	// Paginate browsing for large numbers of objects.
	$per_page = 50;
	$pagenum  = isset( $_REQUEST[ $taxonomy_name . '-tab' ] ) && isset( $_REQUEST['paged'] ) ? absint( $_REQUEST['paged'] ) : 1;
	$offset   = 0 < $pagenum ? $per_page * ( $pagenum - 1 ) : 0;

	$args = array(
		'child_of'     => 0,
		'exclude'      => '',
		'hide_empty'   => false,
		'hierarchical' => 1,
		'include'      => '',
		'number'       => $per_page,
		'offset'       => $offset,
		'order'        => 'ASC',
		'orderby'      => 'name',
		'pad_counts'   => false,
	);

	$terms = get_terms( $taxonomy_name, $args );

	if ( ! $terms || is_wp_error( $terms ) ) {
		echo '<p>' . __( 'No items.' ) . '</p>';

		return;
	}

	$num_pages = ceil( wp_count_terms( $taxonomy_name, array_merge( $args, array(
			'number' => '',
			'offset' => ''
		) ) ) / $per_page );

	$page_links = paginate_links( array(
		'base'      => add_query_arg(
			array(
				$taxonomy_name . '-tab' => 'all',
				'paged'                 => '%#%',
				'item-type'             => 'taxonomy',
				'item-object'           => $taxonomy_name,
			)
		),
		'format'    => '',
		'prev_text' => __( '&laquo;' ),
		'next_text' => __( '&raquo;' ),
		'total'     => $num_pages,
		'current'   => $pagenum
	) );

	$db_fields = false;
	if ( is_taxonomy_hierarchical( $taxonomy_name ) ) {
		$db_fields = array( 'parent' => 'parent', 'id' => 'term_id' );
	}

	$walker = new Walker_Nav_Menu_Checklist( $db_fields );

	$current_tab = 'most-used';
	if ( isset( $_REQUEST[ $taxonomy_name . '-tab' ] ) && in_array( $_REQUEST[ $taxonomy_name . '-tab' ], array(
			'all',
			'most-used',
			'search'
		) )
	) {
		$current_tab = $_REQUEST[ $taxonomy_name . '-tab' ];
	}

	if ( ! empty( $_REQUEST[ 'quick-search-taxonomy-' . $taxonomy_name ] ) ) {
		$current_tab = 'search';
	}

	$removed_args = array(
		'action',
		'customlink-tab',
		'edit-menu-item',
		'menu-item',
		'page-tab',
		'_wpnonce',
	);

	?>
	<div id="taxonomy-<?php echo $taxonomy_name; ?>" class="taxonomydiv">
		<ul id="taxonomy-<?php echo $taxonomy_name; ?>-tabs" class="taxonomy-tabs add-menu-item-tabs">
			<li <?php echo( 'most-used' == $current_tab ? ' class="tabs"' : '' ); ?>>
				<a class="nav-tab-link" data-type="tabs-panel-<?php echo esc_attr( $taxonomy_name ); ?>-pop" href="<?php if ( $nav_menu_selected_id ) {
					echo esc_url( add_query_arg( $taxonomy_name . '-tab', 'most-used', remove_query_arg( $removed_args ) ) );
				} ?>#tabs-panel-<?php echo $taxonomy_name; ?>-pop">
					<?php _e( 'Most Used' ); ?>
				</a>
			</li>
			<li <?php echo( 'all' == $current_tab ? ' class="tabs"' : '' ); ?>>
				<a class="nav-tab-link" data-type="tabs-panel-<?php echo esc_attr( $taxonomy_name ); ?>-all" href="<?php if ( $nav_menu_selected_id ) {
					echo esc_url( add_query_arg( $taxonomy_name . '-tab', 'all', remove_query_arg( $removed_args ) ) );
				} ?>#tabs-panel-<?php echo $taxonomy_name; ?>-all">
					<?php _e( 'View All' ); ?>
				</a>
			</li>
			<li <?php echo( 'search' == $current_tab ? ' class="tabs"' : '' ); ?>>
				<a class="nav-tab-link" data-type="tabs-panel-search-taxonomy-<?php echo esc_attr( $taxonomy_name ); ?>" href="<?php if ( $nav_menu_selected_id ) {
					echo esc_url( add_query_arg( $taxonomy_name . '-tab', 'search', remove_query_arg( $removed_args ) ) );
				} ?>#tabs-panel-search-taxonomy-<?php echo $taxonomy_name; ?>">
					<?php _e( 'Search' ); ?>
				</a>
			</li>
		</ul>
		<!-- .taxonomy-tabs -->

		<div id="tabs-panel-<?php echo $taxonomy_name; ?>-pop" class="tabs-panel <?php
		echo( 'most-used' == $current_tab ? 'tabs-panel-active' : 'tabs-panel-inactive' );
		?>">
			<ul id="<?php echo $taxonomy_name; ?>checklist-pop" class="categorychecklist form-no-clear">
				<?php
				$popular_terms  = get_terms( $taxonomy_name, array(
					'orderby'      => 'count',
					'order'        => 'DESC',
					'number'       => 10,
					'hierarchical' => false
				) );
				$args['walker'] = $walker;
				echo walk_nav_menu_tree( array_map( 'wp_setup_nav_menu_item', $popular_terms ), 0, (object) $args );
				?>
			</ul>
		</div>
		<!-- /.tabs-panel -->

		<div id="tabs-panel-<?php echo $taxonomy_name; ?>-all" class="tabs-panel tabs-panel-view-all <?php
		echo( 'all' == $current_tab ? 'tabs-panel-active' : 'tabs-panel-inactive' );
		?>">
			<?php if ( ! empty( $page_links ) ) : ?>
				<div class="add-menu-item-pagelinks">
					<?php echo $page_links; ?>
				</div>
			<?php endif; ?>
			<ul id="<?php echo $taxonomy_name; ?>checklist" data-wp-lists="list:<?php echo $taxonomy_name ?>" class="categorychecklist form-no-clear">
				<?php
				$args['walker'] = $walker;
				echo walk_nav_menu_tree( array_map( 'wp_setup_nav_menu_item', $terms ), 0, (object) $args );
				?>
			</ul>
			<?php if ( ! empty( $page_links ) ) : ?>
				<div class="add-menu-item-pagelinks">
					<?php echo $page_links; ?>
				</div>
			<?php endif; ?>
		</div>
		<!-- /.tabs-panel -->

		<div class="tabs-panel <?php
		echo( 'search' == $current_tab ? 'tabs-panel-active' : 'tabs-panel-inactive' );
		?>" id="tabs-panel-search-taxonomy-<?php echo $taxonomy_name; ?>">
			<?php
			if ( isset( $_REQUEST[ 'quick-search-taxonomy-' . $taxonomy_name ] ) ) {
				$searched       = esc_attr( $_REQUEST[ 'quick-search-taxonomy-' . $taxonomy_name ] );
				$search_results = get_terms( $taxonomy_name, array(
					'name__like'   => $searched,
					'fields'       => 'all',
					'orderby'      => 'count',
					'order'        => 'DESC',
					'hierarchical' => false
				) );
			} else {
				$searched       = '';
				$search_results = array();
			}
			?>
			<p class="quick-search-wrap">
				<input type="search" class="quick-search input-with-default-title" title="<?php esc_attr_e( 'Search' ); ?>" value="<?php echo $searched; ?>" name="quick-search-taxonomy-<?php echo $taxonomy_name; ?>"/>
				<span class="spinner"></span>
				<?php submit_button( __( 'Search' ), 'button-small quick-search-submit button-secondary hide-if-js', 'submit', false, array( 'id' => 'submit-quick-search-taxonomy-' . $taxonomy_name ) ); ?>
			</p>

			<ul id="<?php echo $taxonomy_name; ?>-search-checklist" data-wp-lists="list:<?php echo $taxonomy_name ?>" class="categorychecklist form-no-clear">
				<?php if ( ! empty( $search_results ) && ! is_wp_error( $search_results ) ) : ?>
					<?php
					$args['walker'] = $walker;
					echo walk_nav_menu_tree( array_map( 'wp_setup_nav_menu_item', $search_results ), 0, (object) $args );
					?>
				<?php elseif ( is_wp_error( $search_results ) ) : ?>
					<li><?php echo $search_results->get_error_message(); ?></li>
				<?php elseif ( ! empty( $searched ) ) : ?>
					<li><?php _e( 'No results found.' ); ?></li>
				<?php endif; ?>
			</ul>
		</div>
		<!-- /.tabs-panel -->

		<p class="button-controls">
			<span class="list-controls">
				<a href="<?php
				echo esc_url( add_query_arg(
					array(
						$taxonomy_name . '-tab' => 'all',
						'selectall'             => 1,
					),
					remove_query_arg( $removed_args )
				) );
				?>#taxonomy-<?php echo $taxonomy_name; ?>" class="select-all"><?php _e( 'Select All' ); ?></a>
			</span>

			<span class="add-to-menu">
				<input type="submit"<?php wp_nav_menu_disabled_check( $nav_menu_selected_id ); ?> class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e( 'Add to Menu' ); ?>" name="add-taxonomy-menu-item" id="<?php echo esc_attr( 'submit-taxonomy-' . $taxonomy_name ); ?>"/>
				<span class="spinner"></span>
			</span>
		</p>

	</div><!-- /.taxonomydiv -->
	<?php
}