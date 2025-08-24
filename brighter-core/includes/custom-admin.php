<?php
// Brighter Tools: Admin UI Enhancements
// Custom/Brighter Admin Bar Logo
// Hide & Custom Front end admin bar
// Registers Page Taxonomy 
// Ads Excerpts for pages


if ( ! defined( 'ABSPATH' ) ) exit;

function brighterwebsites_admin_logo() {
    // Build the logo URL from MU plugin path
    $logo_url = site_url('/wp-content/mu-plugins/brighter-core/assets/icon-white.png');
    ?>
    <style>
    #wpadminbar #wp-admin-bar-wp-logo > .ab-item .ab-icon:before {
        content: "" !important;
        background-image: url('<?php echo esc_url($logo_url); ?>') !important;
        background-size: contain !important;
        background-repeat: no-repeat !important;
        background-position: center center !important;
        width: 20px !important;
        height: 20px !important;
        display: inline-block !important;
    }

    #wpadminbar #wp-admin-bar-wp-logo > .ab-item .ab-icon {
        background: none !important;
    }
    </style>
    <?php
}
add_action('admin_head', 'brighterwebsites_admin_logo');
add_action('wp_head', 'brighterwebsites_admin_logo');

// Your PHP code goes here// Hide admin bar on frontend
add_filter('show_admin_bar', '__return_false');

// Enqueue custom CSS and output dual pill links
add_action('wp_footer', function() {
    if (current_user_can('edit_posts') && !is_admin()) {
          global $post;
        ?>
        <style>
            .gs-admin-bar-links {
                position: fixed;
                bottom: 20px;
                right: 20px;
                display: flex;
                gap: 10px;
                z-index: 9999;
            }
            .gs-admin-bar-links a {
                background-color: rgba(0, 0, 0, 0.8);
                color: #fff;
                padding: 10px 16px;
                border-radius: 999px;
                font-size: 14px;
                text-decoration: none;
                font-family: sans-serif;
                transition: background 0.3s ease;
            }
            .gs-admin-bar-links a:hover {
                background-color: #000;
            }
        </style>
        <div class="gs-admin-bar-links">
            <a href="https://brighterwebsites.com.au/support" target="_blank" rel="noopener">💬 Support</a>
            <a href="<?php echo admin_url('edit.php'); ?>">🛠 Dashboard</a>
            
              <a href="<?php echo get_edit_post_link($post->ID); ?>">✏️ Edit This Page</a>
        </div>
        <?php
    }
});



/**
 * Ensure our args win even if another plugin registers the same taxonomy first.
 * Runs before register_taxonomy is processed.
 */
add_filter('register_taxonomy_args', function ($args, $taxonomy){
  if ($taxonomy !== 'pagetype') return $args;

  // Admin-only taxonomy with REST for editors like Breakdance
  $args['public']              = false;
  $args['publicly_queryable']  = false;
  $args['rewrite']             = false;
  $args['show_ui']             = true;
  $args['show_in_menu']        = true;
  $args['show_in_nav_menus']   = false;
  $args['show_admin_column']   = true;
  $args['show_in_quick_edit']  = true;
  $args['show_tagcloud']       = false;
  $args['show_in_rest']        = true;     // for Breakdance/Gutenberg visibility
  $args['hierarchical']        = true;
  $args['default_term']        = array('name' => 'General');
  // Capabilities: only admins manage, editors can assign
  $args['capabilities'] = array(
    'manage_terms' => 'manage_options',    // Admins
    'edit_terms'   => 'manage_options',
    'delete_terms' => 'manage_options',
    'assign_terms' => 'edit_pages',        // Editors and above
  );

  // Labels and textdomain
  $args['labels'] = array(
    'name'          => esc_html__('Page Types', 'brighterwebsites'),
    'singular_name' => esc_html__('Page Type', 'brighterwebsites'),
    'menu_name'     => esc_html__('Page Types', 'brighterwebsites'),
    'all_items'     => esc_html__('All Page Types', 'brighterwebsites'),
    'edit_item'     => esc_html__('Edit Page Type', 'brighterwebsites'),
    'view_item'     => esc_html__('View Page Type', 'brighterwebsites'),
    'add_new_item'  => esc_html__('Add new Page Type', 'brighterwebsites'),
    'new_item_name' => esc_html__('New Page Type name', 'brighterwebsites'),
    'search_items'  => esc_html__('Search Page Types', 'brighterwebsites'),
    'not_found'     => esc_html__('No Page Types found', 'brighterwebsites'),
  );

  return $args;
}, 10, 2);

/**
 * Register the taxonomy on init with a later priority so it runs after CPT UI.
 */
add_action('init', function () {
  if (!taxonomy_exists('pagetype')) {
    register_taxonomy('pagetype', array('page'), array()); // args are overridden by the filter above
  } else {
    // Make sure Pages are attached even if someone else registered it
    register_taxonomy_for_object_type('pagetype', 'page');
  }
}, 40);

/**
 * Admin guards: everything below only runs in wp-admin.
 */
if (is_admin()) {

  // Pages list: filter dropdown
  add_action('restrict_manage_posts', function () {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (empty($screen) || $screen->post_type !== 'page' || !taxonomy_exists('pagetype')) return;

    $selected      = isset($_GET['pagetype']) ? sanitize_text_field($_GET['pagetype']) : '';
    $info_taxonomy = get_taxonomy('pagetype');

    wp_dropdown_categories(array(
      'show_option_all' => sprintf(esc_html__('Show all %s', 'brighterwebsites'), $info_taxonomy->label),
      'taxonomy'        => 'pagetype',
      'name'            => 'pagetype',
      'orderby'         => 'name',
      'selected'        => $selected,
      'show_count'      => true,
      'hide_empty'      => false,
      'hierarchical'    => true,
      'value_field'     => 'term_id',
    ));
  });

  // Convert term_id to slug for the list table query
  add_filter('parse_query', function ($query) {
    if (!is_admin() || !function_exists('get_current_screen')) return;
    $screen = get_current_screen();
    if (empty($screen) || $screen->base !== 'edit' || $screen->post_type !== 'page') return;

    if (isset($query->query_vars['pagetype']) && is_numeric($query->query_vars['pagetype']) && intval($query->query_vars['pagetype']) > 0) {
      $term = get_term_by('id', intval($query->query_vars['pagetype']), 'pagetype');
      if ($term && !is_wp_error($term)) {
        $query->query_vars['pagetype'] = $term->slug;
      }
    }
  });

  // Pages list: column display
  add_filter('manage_pages_columns', function ($cols) {
    $cols['pagetype'] = esc_html__('Page Type', 'brighterwebsites');
    return $cols;
  });
  add_action('manage_pages_custom_column', function ($col, $post_id) {
    if ($col !== 'pagetype') return;
    $terms = get_the_terms($post_id, 'pagetype');
    if (is_wp_error($terms) || empty($terms)) { echo '�'; return; }
    echo esc_html(join(', ', wp_list_pluck($terms, 'name')));
  }, 10, 2);

  // Make default term stick on new pages
  add_action('save_post_page', function ($post_id, $post, $update) {
    if ($update || wp_is_post_revision($post_id)) return;
    if (!has_term('', 'pagetype', $post_id)) {
      $term = get_term_by('name', 'General', 'pagetype');
      if ($term && !is_wp_error($term)) {
        wp_set_object_terms($post_id, array((int) $term->term_id), 'pagetype', false);
      }
    }
  }, 10, 3);
}

/**
 * Belt and braces. Block accidental front-end taxonomy archive.
 */
add_action('template_redirect', function () {
  if (is_tax('pagetype')) {
    wp_redirect(home_url(), 301);
    exit;
  }
});

// show_in_rest is true. That lets Breakdance conditions, queries, or template rules see and target pagetype terms. If helpful, you can also add classes to the <body> for easy CSS targeting:

add_filter('body_class', function($classes){
  if (is_page()) {
    $terms = get_the_terms(get_the_ID(), 'pagetype');
    if ($terms && !is_wp_error($terms)) {
      foreach ($terms as $t) $classes[] = 'pagetype-' . sanitize_html_class($t->slug);
    }
  }
  return $classes;
});


/**
 * Excerpts for pages
 */
add_action('init', function () {
  add_post_type_support('page', 'excerpt');
});
