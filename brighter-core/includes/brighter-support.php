<?php
// Brighter Tools: Support Page & Login Styling
if ( ! defined( 'ABSPATH' ) ) exit;


// Brighter Support Admin Page with Tabs and Dynamic Manual Links

add_action('admin_menu', 'brighter_support_add_menu');
function brighter_support_add_menu() {
    add_menu_page(
        'Support Hub',
        'Support',
        'read',
        'brighter_support',
        'brighter_support_render_page',
        'dashicons-sos',
        3
    );
}



function brighter_enqueue_admin_support_styles($hook) {
    if (strpos($hook, 'brighter_support') !== false) {
        wp_enqueue_style('brighter-admin-support', plugin_dir_url(__FILE__) . '../css/admin-support.css');
    }
}
add_action('admin_enqueue_scripts', 'brighter_enqueue_admin_support_styles');


function brighter_support_render_page() {
    $current_user = wp_get_current_user();
    $email = $current_user->user_email;
    $admin_emails = ['team@brighterwebsites.com.au', 'support@brighterwebsites.com.au'];
    $active_tab = $_GET['tab'] ?? 'support';

    echo '<div class="wrap">';
    echo '<h1>Welcome to Your Website Support Hub</h1>';
    echo '<nav class="nav-tab-wrapper">';
    echo '<a href="?page=brighter_support&tab=support" class="nav-tab ' . ($active_tab == 'support' ? 'nav-tab-active' : '') . '">Support Info</a>';
    if (in_array($email, $admin_emails)) {
        echo '<a href="?page=brighter_support&tab=manuals" class="nav-tab ' . ($active_tab == 'manuals' ? 'nav-tab-active' : '') . '">Manual Links</a>';
    }
    
    echo '<a href="?page=brighter_support&tab=business_info" class="nav-tab ' . ($active_tab == 'business_info' ? 'nav-tab-active' : '') . '">Business Info</a>';

    echo '</nav>';

    if ($active_tab === 'manuals' && in_array($email, $admin_emails)) {
    echo '<form method="post" action="options.php">';
    settings_fields('brighter_support_settings');
    do_settings_sections('brighter_support_page');
    
  
    submit_button('Save Manual Links');
    echo '</form>';
} elseif ($active_tab === 'business_info' && current_user_can('manage_options')) {
    brighterweb_render_business_info_form();
} else {
    brighter_support_output_main();
}


    echo '</div>';
}

function brighter_support_output_main() {
    $site_url = site_url();
    $manual_full_link = esc_url(get_option('manual_full_link', '#'));
    $manual_quick_link = esc_url(get_option('manual_quick_link', '#'));
    $website_ranking_link = esc_url(get_option('website_ranking_link', '#'));
    $map_ranking_link = esc_url(get_option('map_ranking_link', '#'));


    echo '<div class="support-page">';
    
    echo '<div class="support-desc">';
        echo '<p>We’ve created this page to help you confidently manage and maintain your website. Below are quick-access links and tips to get you started.</p>';
    echo '</div>';
    
    echo '<div class="support-container support-manual">';
        echo '<h2>ℹ️ Website Owners Manual</h2>';
        echo '<ul>';
         // Website Owners Manual
        if ($manual_full_link && $manual_full_link !== '#') {
            echo '<li><strong>Full Manual:</strong> <a href="' . $manual_full_link . '" target="_blank">View Full Manual</a></li>';
        } else {
            echo '<li><strong>Full Manual:</strong> Coming Soon / Request <a href="mailto:support@brighterwebsites.com.au">support@brighterwebsites.com.au</a></li>';
            
        }
        if ($manual_quick_link && $manual_quick_link !== '#') {
            echo '<li><strong>Quick Guide:</strong> <a href="' . $manual_quick_link . '" target="_blank">View Quick Guide</a></li>';
        } else {
            echo '<li><strong>Quick Guide:</strong> Coming Soon / Request <a href="mailto:support@brighterwebsites.com.au">support@brighterwebsites.com.au</a></li>';
            
        }
        echo '</ul>';
    echo '</div>';
     $logo_url = plugin_dir_url(__FILE__) . '../assets/brighter-logo.png';

    echo '<div class="support-container support-brand">';
        
             echo '<img class="support-img" src="' . esc_url($logo_url) . '" alt="Support">';
            
            echo '<div class="support-help">';
                echo '<h2>🧰 Need Help?</h2>';
                echo '<p>We’re here to help with technical issues, updates, or questions.</p>';
            echo '</div>';
        
            echo '<div class="support-tips">';
                echo '<h3>💡 Quick Tips</h3>';
                echo '<li><a href="https://brighterwebsites.com.au/topics/website-help/" target="_blank">How to manage your website</a></li>';
            echo '</div>';
            
            echo '<div class="support-tips">';
                echo '<h3>💡 Support </h3>';
                echo '<ul>';
                    echo '<li><a href="https://brighterwebsites.com.au/support/" target="_blank">Submit a Support Request</a></li>';
                    echo '<li>Email us directly at <a href="mailto:support@brighterwebsites.com.au">support@brighterwebsites.com.au</a></li>';
                echo '</ul>';
            echo '</div>';
        
    echo '</div>';
        

    
   
    echo '<div class="support-container support-tools">';
        echo '<h2>📣 Marketing Tools</h2>';
        echo '<p><small>If these tools have been set up for you, Log in with your website Google Account, or as specified in your <strong>website owner manual</strong>.</small></p>';
        echo '<ul>';
        echo '<li><strong>Email Campaigns:</strong> <a href="http://dashboard.mailerlite.com" target="_blank">MailerLite Dashboard</a></li>';
        echo '<li><strong>SMS Marketing:</strong> <a href="https://www.smsglobal.com/" target="_blank">SMSGlobal</a></li>';
        echo '<li><strong>Social Media Management:</strong> <a href="https://www.postly.ai/" target="_blank">Postly</a></li>';
        echo '<li><strong>Content copywriter:</strong> <a href="https://neuronwriter.com/" target="_blank">neuronwriter</a></li>';

        echo '</ul>';
    echo '</div>';
    
    echo '<div class="support-container support-search">';
        echo '<h2>📈 Performance & Search</h2>';
        echo '<p>Log in with your website Google Account.</p>';
        echo '<ul>';
        echo '<li><strong>Google Search Console:</strong> <a href="https://search.google.com/search-console" target="_blank">Manage Search Performance</a></li>';
        echo '<li><strong>Website Visitors:</strong> <a href="https://analytics.google.com" target="_blank">Google Analytics</a></li>';
        echo '<li><strong>Check Speed:</strong> <a href="https://pagespeed.web.dev" target="_blank">PageSpeed Insights</a></li>';
        echo '</ul>';
        
        // Ranks Pro - Website Ranking
        if ($website_ranking_link && $website_ranking_link !== '#') {
            echo '<li><strong>SEO Website Ranking Report:</strong> <a href="' . $website_ranking_link . '" target="_blank">Open Tool</a></li>';
        } else {
            echo '<li><small><strong>SEO Website Ranking Report:</strong> Request <a href="mailto:support@brighterwebsites.com.au">support@brighterwebsites.com.au</a></small></li>';
        }
        // Ranks Pro - Map Ranking
        if ($map_ranking_link && $map_ranking_link !== '#') {
            echo '<li><strong>SEO Map Ranking Report:</strong> <a href="' . $map_ranking_link . '" target="_blank">Open Tool</a></li>';
        } else {
            echo '<li><strong><small>SEO Map Ranking Report:</strong> Request <a href="mailto:support@brighterwebsites.com.au">support@brighterwebsites.com.au</a></small></li>';
        }
    echo '</div>';
    
        echo '<div class="support-container support-health">';
        echo '<h2>🔒 Website Health</h2>';
        echo '<p>Log in as admin to access website health.</p>';
        
        echo '<ul>';
        echo '<li><strong>Check Website Health:</strong> <a href="' . esc_url( admin_url( 'site-health.php' ) ) . '" target="_blank">Site Health Tool</a></li>';

        echo '<li><strong>Check Website Health:</strong> <a href="' . $site_url . '/wp-admin/site-health.php" target="_blank">Site Health Tool</a></li>';
        echo '</ul>';
    echo '</div>';
         
     
    echo '<div class="support-container support-backup">';     
        echo '<h2>🔒💾 Backup & Restore</h2>';
        echo '<p>Your website is automatically backed up monthly. If something goes wrong, contact us to restore a previous version.</p>';
        echo '<p>Log in as admin to access Backups.</p>';

        echo '<ul>';
        echo '<li><strong>Backups:</strong> <a href="' . $site_url . '/wp-admin/admin.php?page=WPvivid" target="_blank">Go to Backups</a></li>';
        echo '</ul>';
    echo '</div>';
}

add_action('admin_init', function() {
    // Register all settings
    register_setting('brighter_support_settings', 'manual_full_link');
    register_setting('brighter_support_settings', 'manual_quick_link');
    register_setting('brighter_support_settings', 'website_ranking_link');
    register_setting('brighter_support_settings', 'map_ranking_link');
    register_setting('brighter_support_settings', 'brighter_login_logo');
    register_setting('brighter_support_settings', 'theme_colour');
    register_setting('brighter_support_settings', 'image_max_dimension');
    register_setting('brighter_support_settings', 'enable_image_resize');
    register_setting('brighter_support_settings', 'enable_extra_image_sizes');

    add_settings_field('enable_image_resize', 'Enable Image Resizing?', function() {
        $enabled = get_option('enable_image_resize', 'yes');
        echo '<label><input type="checkbox" name="enable_image_resize" value="yes" ' . checked('yes', $enabled, false) . '> Resize uploaded images</label>';
        echo '<p class="description">If unchecked, original images will be stored without resizing.</p>';
    }, 'brighter_support_page', 'manual_links_section');

 add_settings_field('image_max_dimension', 'Max Upload Dimension (px)', function() {
        echo '<input type="number" name="image_max_dimension" value="' . esc_attr(get_option('image_max_dimension', 2480)) . '" class="small-text" min="500" step="10">';
        echo '<p class="description">Maximum dimension for uploaded images (longest side).</p>';
    }, 'brighter_support_page', 'manual_links_section');

    add_settings_field('enable_extra_image_sizes', 'Enable Additional Image Sizes?', function() {
        $checked = checked(1, get_option('enable_extra_image_sizes'), false);
        echo "<input type='checkbox' name='enable_extra_image_sizes' value='1' $checked />";
        echo '<label> Enable medium_large, 1536, and 2048 sizes (e.g. for retina/hero)</label>';
        echo '<p class="description">leave unchecked to create 150x150 thumbnail, 300x and 1024x thumbnails plus original image.</p>';
    }, 'brighter_support_page', 'manual_links_section');

    

   



    add_settings_field('theme_colour', 'Theme Colour (Hex Code)', function() {
        echo '<input type="text" name="theme_colour" value="' . esc_attr(get_option('theme_colour')) . '" class="regular-text" placeholder="#193b2d">';
    }, 'brighter_support_page', 'manual_links_section');


    add_settings_section('manual_links_section', 'Edit Manual Links', '__return_false', 'brighter_support_page');

    // Full Manual
    add_settings_field('manual_full_link', 'Full Manual URL', function() {
        echo '<input type="url" name="manual_full_link" value="' . esc_url(get_option('manual_full_link')) . '" class="regular-text">';
    }, 'brighter_support_page', 'manual_links_section');

    // Quick Guide
    add_settings_field('manual_quick_link', 'Quick Guide URL', function() {
        echo '<input type="url" name="manual_quick_link" value="' . esc_url(get_option('manual_quick_link')) . '" class="regular-text">';
    }, 'brighter_support_page', 'manual_links_section');

    // Website Ranking Link
    add_settings_field('website_ranking_link', 'Ranks Pro Website Ranking Link', function() {
        echo '<input type="url" name="website_ranking_link" value="' . esc_url(get_option('website_ranking_link')) . '" class="regular-text">';
    }, 'brighter_support_page', 'manual_links_section');

    // Map Ranking Link
    add_settings_field('map_ranking_link', 'Ranks Pro Map Ranking Link', function() {
        echo '<input type="url" name="map_ranking_link" value="' . esc_url(get_option('map_ranking_link')) . '" class="regular-text">';
    }, 'brighter_support_page', 'manual_links_section');
    
    add_settings_field('brighter_login_logo', 'Login Page Logo URL', function() {
    echo '<input type="url" name="brighter_login_logo" value="' . esc_url(get_option('brighter_login_logo')) . '" class="regular-text">';
    echo '<p class="description">Paste the URL of the image you want to show on the login page.</p>';
}, 'brighter_support_page', 'manual_links_section');
});

