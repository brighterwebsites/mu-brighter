<?php
// Brighter Tools: Business Info & Shortcodes
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Brighter Websites Business Info Manager
 * Description: Adds a custom admin page to manage business details and output them via shortcodes.
 * Author: Brighter Websites
 */
//add_theme_support('custom-logo');

// Enqueue custom admin styles
function brighterweb_enqueue_admin_styles() {
    wp_enqueue_style('brighterweb-options-styles', plugins_url('css/admin-support.css', __FILE__));
}

//add_action('admin_enqueue_scripts', 'brighterweb_enqueue_admin_styles');

// Register custom admin menu page
function brighterweb_render_business_info_form() {
    echo '<form method="post" action="options.php" class="business_info_style">';
    settings_fields('brighterweb_business_info_group');
    do_settings_sections('brighterweb_business_info_page');
    submit_button('Save Business Information');
    echo '</form>';
}


// Render the business information admin page
function brighterweb_render_info_page() {
    ?>
    <div class="business_info_page_style">
        <h2 class="business-info-h">Business Information</h2>
        <form method="post" action="options.php" class="business_info_style">
            <?php
            settings_fields('brighterweb_business_info_group');
            do_settings_sections('brighterweb_business_info_page');
            submit_button('Save Business Information');
            ?>
        </form>
    </div>
    <?php
}

// Register business info settings and fields
function brighterweb_register_business_info_settings() {
    $fields = [
        'business_name', 'contact_name', 'abn', 'phone_number', 'email',
        'address', 'city', 'state', 'postcode', 'country', 'lat', 'long'
    ];

    foreach ($fields as $field) {
        register_setting('brighterweb_business_info_group', $field);
    }

    add_settings_section(
        'brighterweb_info_section',
        'Business Information',
        'brighterweb_info_section_callback',
        'brighterweb_business_info_page'
    );

    add_settings_section(
        'brighterweb_contact_section',
        'Business Contact Info',
        'brighterweb_contact_section_callback',
        'brighterweb_business_info_page'
    );

    // Info fields
    $info_fields = ['business_name', 'contact_name', 'abn'];
    foreach ($info_fields as $field) {
        add_settings_field($field, ucwords(str_replace('_', ' ', $field)), 'brighterweb_field_callback', 'brighterweb_business_info_page', 'brighterweb_info_section', ['id' => $field]);
    }

    // Contact fields
    $contact_fields = ['phone_number', 'email', 'address', 'city', 'state', 'country', 'postcode', 'lat', 'long'];
    foreach ($contact_fields as $field) {
        add_settings_field($field, ucwords(str_replace('_', ' ', $field)), 'brighterweb_field_callback', 'brighterweb_business_info_page', 'brighterweb_contact_section', ['id' => $field]);
    }
}
add_action('admin_init', 'brighterweb_register_business_info_settings');

// Section callbacks
function brighterweb_info_section_callback() {
    echo '<p class="business-info-p">Enter your general business details below.</p>';
    echo '<p class="business-info-p">These details are used to generate the information on your privacy policy and should match the DPA Administration Primary Contact in Google Analytics. Log into Analytics and visit <a href="https://marketingplatform.google.com/gdpr" target="_blank" rel="noopener noreferrer">https://marketingplatform.google.com/gdpr</a> To Update DPA Contacts too.</p>';
echo '<p class="business-info-p"> This information is also used by SEOPress to generate data for some <a href="https://developers.google.com/search/docs/appearance/structured-data/intro-structured-data" target="_blank" rel="noopener noreferrer">schema types</a>.</p>';

    
}

function brighterweb_contact_section_callback() {
//    echo '<p class="business-info-p">Enter your contact and location information below.</p>';
}

// Universal field callback renderer
function brighterweb_field_callback($args) {
    $value = get_option($args['id']);
    echo "<input type='text' name='{$args['id']}' value='" . esc_attr($value) . "' />";
}

// Shortcode to output business info
function brighterweb_business_info_shortcode($atts) {
    $valid_keys = [
        'business_name', 'contact_name', 'abn', 'phone_number', 'email', 'address',
        'city', 'state', 'postcode', 'country', 'lat', 'long'
    ];

    $atts = shortcode_atts(['setting' => ''], $atts);

    if (in_array($atts['setting'], $valid_keys)) {
        return esc_html(get_option($atts['setting']));
    } else {
        return 'Invalid or missing setting attribute.';
    }
}
add_shortcode('business_info', 'brighterweb_business_info_shortcode');




