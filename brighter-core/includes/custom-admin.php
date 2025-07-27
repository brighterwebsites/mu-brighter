<?php
// Brighter Tools: Admin UI Enhancements
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
