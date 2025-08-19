<?php
// Brighter Tools: Support Page & Login Styling
if ( ! defined( 'ABSPATH' ) ) exit;

// Design Credit Hook  
add_action( 'wp_footer', function () {
    // Dynamically get site info
    $site_name = get_bloginfo( 'name' );
    $site_url  = home_url();

    // Hidden attribution comment
    echo "\n<!-- Website built by Brighter Websites - https://brighterwebsites.com.au -->\n";

    // Publisher meta tag
    echo "\n<meta name=\"publisher\" content=\"Brighter Websites\">\n";

    // Schema JSON-LD
    echo '
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "' . esc_js( $site_name ) . '",
  "url": "' . esc_url( $site_url ) . '",
  "publisher": {
    "@type": "Organization",
    "name": "Brighter Websites",
    "url": "https://brighterwebsites.com.au"
  }
}
</script>
';
}, 99 );

function brighter_credit_shortcode() {
    // Hide only on blog post single views
    if ( is_single() && get_post_type() === 'post' ) {
        return '';
    }

    // Get site name and convert to slug
    $site_name   = get_bloginfo( 'name' );
    $utm_source  = sanitize_title( $site_name );

    // Build tracked URL
    $url = 'https://brighterwebsites.com.au/?utm_source=' . $utm_source . '&utm_medium=footer&utm_campaign=site-credit';

    // Return the credit
    return 'Proudly Built by <a href="' . esc_url( $url ) . '" target="_blank" rel="noopener"><strong>BRIGHTER WEBSITES</strong></a>';
}
add_shortcode( 'brighter_credit', 'brighter_credit_shortcode' );



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


// Render CSS
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
    
    echo '<a href="?page=brighter_support&tab=optimisation" class="nav-tab ' . ($active_tab == 'optimisation' ? 'nav-tab-active' : '') . '">Optimisation</a>';
    echo '</nav>';
    echo '<a href="?page=brighter_support&tab=tweaks" class="nav-tab ' . ($active_tab == 'tweaks' ? 'nav-tab-active' : '') . '">Brighter Tweaks</a>';


    if ($active_tab === 'manuals' && in_array($email, $admin_emails)) {
     echo '<div class="support-page">';
    echo '<form method="post" action="options.php">';
    settings_fields('brighter_support_settings');
    do_settings_sections('brighter_support_page');
    submit_button('Save Manual Links');
    echo '</div>';
    echo '</form>';
    
        } elseif ($active_tab === 'business_info' && current_user_can('manage_options')) {
            brighterweb_render_business_info_form();
        } elseif ($active_tab === 'optimisation' && current_user_can('manage_options')) {
            echo '<div class="support-page">';
            echo '<form method="post" action="options.php">';
            settings_fields('brighter_optimisation_settings');
          
            do_settings_sections('brighter_optimisation_page');
            submit_button('Save Optimisation Settings');
           
            echo '</form>';
            echo '</div>';
        }
		/* ADD THIS */
		} elseif ($active_tab === 'tweaks' && current_user_can('manage_options')) {
			Brighter_Tweaks::render_page();
			echo '<div class="support-page">';
			echo '<form method="post" action="options.php">';
			settings_fields('brighter_tweaks_settings');
			do_settings_sections('brighter_tweaks_page');
			submit_button('Save Tweaks');
			echo '</form>';
			echo '</div>';
        
        else {
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
	$logo_url = plugin_dir_url(__FILE__) . '../assets/brighter-logo.png';
	$site_url = get_site_url();


    echo '<div class="support-page">';
    
    echo '<div class="support-desc">';
        echo '<p>Weve created this page to help you confidently manage and maintain your website. Below are quick-access links and tips to get you started.</p>';
    echo '</div>';
    
    echo '<div class="support-container support-manual">';
        echo '<h2>ℹ️ Website Owners Manual</h2>';
        
         // Website Owners Manual
        if ($manual_full_link && $manual_full_link !== '#') {
         
            echo '<div class="bright-manual"><a href="' . $manual_full_link . '" target="_blank">Website Manual</a></div>';
            
        } else {
            echo '<div class="bright-manual">Full Manual:</strong> Coming Soon</div>';
            
        }
        if ($manual_quick_link && $manual_quick_link !== '#') {
          echo '<div class="bright-manual"><a href="' . $manual_quick_link . '" target="_blank">Quick Guide</a></div>';
        } else {
            echo '<div class="bright-manual"><strong>Quick Guide:</strong> Coming Soon</div>';
            
        }
       
    echo '</div>';


echo '<div class="support-container support-brand">';


        echo '<h2>📝 Manage Your Content</h2>';
       
        echo '<table class="compare-table">';
            echo '<thead><tr><th><a href="' . $site_url . '/wp-admin/edit.php" target="_blank">Posts</a></th><th><a href="' . $site_url . '/wp-admin/edit.php?post_type=page" target="_blank">Pages</a></th></tr></thead>';
            echo '<tbody>';
                echo '<tr><td>Appear in blog feed</td><td>Stand-alone content (like About or Contact)</td></tr>';
                echo '<tr><td>Organised by date, category, tags</td><td>Organised hierarchically (parent/child)</td></tr>';
                echo '<tr><td>Ideal for regular updates</td><td>Best for timeless content</td></tr>';
            echo '</tbody>';
        echo '</table>';



echo '</div>';
 
    
    
    echo '<div class="support-container support-brand">';
        
             
            
            echo '<div class="support-help">';
                echo '<div class="support-help-inner">';
                    echo '<img class="support-img" src="' . esc_url($logo_url) . '" alt="Support">';
                    echo '<h2>🧰 Need Help?</h2>';
                echo '</div>';
                echo '<p>We’re here to help with technical issues, updates, or questions. Email us directly at <a href="mailto:support@brighterwebsites.com.au">support@brighterwebsites.com.au</a</p>';
               
                echo '<div class="bright-button"><a href="https://brighterwebsites.com.au/kb/" target="_blank">Website Knowledge Base</a></div>';
            echo '</div>';
        
            echo '<div class="support-tips">';
               
   echo '<div class="support-container support-search">';
        echo '<h2>📈 Performance & Search</h2>';
      echo '<p>If we set these up as part of your website package, you’ll find your account details in your Website Manual.</p>';

        echo '<ul>';
        echo '<li><strong>Google Search Console:</strong> <a href="https://search.google.com/search-console" target="_blank">Manage Search Performance</a></li>';
         echo '<li><strong>AHREFS SEO Health:</strong> <a href="https://app.ahrefs.com/site-audit" target="_blank">Site Audit</a></li>';
        echo '<li><strong>Website Visitors:</strong> <a href="https://app.ahrefs.com/site-audit" target="_blank">Google Analytics</a></li>';
        echo '<li><strong>Check Speed:</strong> <a href="https://pagespeed.web.dev" target="_blank">PageSpeed Insights</a></li>';
        echo '</ul>';
        
        // Ranks Pro - Website Ranking
        if ($website_ranking_link && $website_ranking_link !== '#') {
            echo '<li><strong>SEO Website Ranking Report:</strong> <a href="' . $website_ranking_link . '" target="_blank">Open Tool</a></li>';
        } else {
            echo '<li><strong>SEO Website Ranking Report:</strong> Request <a href="mailto:support@brighterwebsites.com.au">support@brighterwebsites.com.au</a></li>';
        }
        // Ranks Pro - Map Ranking
        if ($map_ranking_link && $map_ranking_link !== '#') {
            echo '<li><strong>SEO Map Ranking Report:</strong> <a href="' . $map_ranking_link . '" target="_blank">Open Tool</a></li>';
        } else {
            echo '<li><strong>SEO Map Ranking Report:</strong> Request <a href="mailto:support@brighterwebsites.com.au">support@brighterwebsites.com.au</a></li>';
        }
    echo '</div>';
 
   
   
    echo '<div class="support-container support-tools">';
       
       echo '<p><strong>📣 Recommended Tools</strong> – If these tools have been set up for you, you’ll find the login details in your <strong>Website Owner Manual</strong>.</p>';

        echo '<ul>';
        echo '<li><strong>Email Campaigns:</strong> <a href="https://www.mailerlite.com/invite/e74a69700df56/" target="_blank">MailerLite</a></li>';
        echo '<li><strong>SMS Marketing:</strong> <a href="https://www.smsglobal.com/" target="_blank">SMSGlobal</a></li>';
        echo '<li><strong>Social Media Management:</strong> <a href="https://www.postly.ai/" target="_blank">Postly</a></li>';
        echo '<li><strong>Content copywriter:</strong> <a href="https://app.neuronwriter.com/ar/98d2833da3de4ac1cc524b8864cf1241/" target="_blank">Neuronwriter</a></li>';

        echo '</ul>';
    echo '</div>';
    
  
    
        echo '<div class="support-container support-health">';
  
        echo '<p>🔒 <strong>Admin Tools</strong> - Log in as admin to access website health.</p>';
        
        echo '<ul>';
        echo '<li><strong>Check Website Health:</strong> <a href="' . $site_url . '/wp-admin/site-health.php" target="_blank">Site Health Tool</a></li>';
       echo '<li><strong>Backups:</strong> <a href="' . $site_url . '/wp-admin/admin.php?page=WPvivid" target="_blank">Go to Backups</a></li>';
        echo '</ul>';
        echo '<p>*Your website is automatically backed up monthly. If something goes wrong, contact us to restore a previous version.</p>';
    echo '</div>';
         
     
   
}


add_action('admin_init', function() {

    // 🔧 Register all settings
    register_setting('brighter_support_settings', 'manual_full_link');
    register_setting('brighter_support_settings', 'manual_quick_link');
    register_setting('brighter_support_settings', 'website_ranking_link');
    register_setting('brighter_support_settings', 'map_ranking_link');
    register_setting('brighter_support_settings', 'brighter_login_logo');
    

    // 📘 Section: Manual & Visual Settings
    add_settings_section('manual_links_section', 'Edit Manual Links', '__return_false', 'brighter_support_page');

 
    // 📚 Full Manual URL
    add_settings_field('manual_full_link', 'Full Manual URL', function() {
        echo '<input type="url" name="manual_full_link" value="' . esc_url(get_option('manual_full_link')) . '" class="regular-text">';
    }, 'brighter_support_page', 'manual_links_section');

    // ⚡ Quick Guide URL
    add_settings_field('manual_quick_link', 'Quick Guide URL', function() {
        echo '<input type="url" name="manual_quick_link" value="' . esc_url(get_option('manual_quick_link')) . '" class="regular-text">';
    }, 'brighter_support_page', 'manual_links_section');

    // 🌐 Website Ranking Link
    add_settings_field('website_ranking_link', 'Ranks Pro Website Ranking Link', function() {
        echo '<input type="url" name="website_ranking_link" value="' . esc_url(get_option('website_ranking_link')) . '" class="regular-text">';
    }, 'brighter_support_page', 'manual_links_section');

    // 📍 Map Ranking Link
    add_settings_field('map_ranking_link', 'Ranks Pro Map Ranking Link', function() {
        echo '<input type="url" name="map_ranking_link" value="' . esc_url(get_option('map_ranking_link')) . '" class="regular-text">';
    }, 'brighter_support_page', 'manual_links_section');

    // 🔒 Login Page Logo
    add_settings_field('brighter_login_logo', 'Login Page Logo URL', function() {
        echo '<input type="url" name="brighter_login_logo" value="' . esc_url(get_option('brighter_login_logo')) . '" class="regular-text">';
        echo '<p class="description">Paste the URL of the image you want to show on the login page.</p>';
    }, 'brighter_support_page', 'manual_links_section');
});


