<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
  exit;
}

if (defined('WPB_VC_VERSION')) {
  //
  // Let's self activate the vc editor on our post type with add_filters
  //
  add_action('admin_init', 'Popmake_Enable_Visual_Composer');
  function Popmake_Enable_Visual_Composer() {
    global $pagenow;
    $screen = Popmake_GetCurrentPostType(); // Run our function to determine the correct post type
    // If we are on our post type
    if ($screen == 'popup') {
      if ($pagenow == 'post-new.php' || $pagenow == 'post.php') {
        // Let's activate the VC Editor
        add_filter('vc_role_access_with_post_types_get_state', '__return_true');
        add_filter('vc_role_access_with_backend_editor_get_state', '__return_true');
        add_filter('vc_role_access_with_frontend_editor_get_state', '__return_false');
        add_filter('vc_check_post_type_validation', '__return_true');
      }
    }
  }
  //
  // Let's determine the post type
  //
  // Check the post type since we have one
  function Popmake_GetCurrentPostType() {
    global $post, $typenow, $current_screen;
    if ($post && $post->post_type) {
      return $post->post_type;
    }
    // Check the global $typenow
    else if ($typenow) {
      return $typenow;
    }
    // Check the global $current_screen Object
    else if ($current_screen && $current_screen->post_type) {
      return $current_screen->post_type;
    }
    // Check the Post Type QueryString
    else if (isset($_REQUEST['post_type'])) {
      return sanitize_key($_REQUEST['post_type']);
    }
    // Try to get via get_post(); Attempt A
    else if (empty($typenow) && !empty($_GET['post'])) {
      $post = get_post($_GET['post']);
      $typenow = $post->post_type;
      return $typenow;
    }
    // Try to get via get_post(); Attempt B
    else if (empty($typenow) && !empty($_POST['post_ID'])) {
      $post = get_post($_POST['post_ID']);
      $typenow = $post->post_type;
      return $typenow;
    }
    // Try to get via get_current_screen()
    else if (function_exists('get_current_screen')) {
      $current = get_current_screen();
      if (isset($current) && ($current != false) && ($current->post_type)) {
        return $current->post_type;
      }
      else {
        return null;
      }
    }
    // We do not know the post type, return null
    return null;
  }
}
