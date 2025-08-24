<?php
// wp-content/mu-plugins/brighter-core/includes/brighter-settings.php

if ( ! defined('ABSPATH') ) exit;

/**
 * Enqueue styles (optional, if you want separate styling for settings)
 */

add_action('admin_enqueue_scripts', function($hook) {

    // Only load our CSS on Brighter Support + AI Tracker pages
    $allowed_pages = [
        'toplevel_page_brighter_support',   // MU plugin support page
           ];

    if (!in_array($hook, $allowed_pages)) {
        return; // ? stop loading CSS everywhere else
    }

    wp_enqueue_style(
        'brighter-admin',
        plugin_dir_url(__FILE__) . 'css/admin-support.css',
        [],
        '1.0.0'
    );
});



/**
 * Register settings for Manual Links + API Token
 */
add_action('admin_init', function() {

    // 🔧 Register options (all in one group now)
    register_setting('brighter_support_settings', 'manual_full_link');
    register_setting('brighter_support_settings', 'manual_quick_link');
    register_setting('brighter_support_settings', 'website_ranking_link');
    register_setting('brighter_support_settings', 'map_ranking_link');
    register_setting('brighter_support_settings', 'brighter_login_logo');
    register_setting('brighter_support_settings', 'brc_token'); // 👈 token saved here

    // 📘 Section: Manual & API Settings
    add_settings_section(
        'manual_links_section',
        'Edit Manual Links & API Token',
        '__return_false',
        'brighter_support_page'
    );

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

    // 🔑 API Token (with Generate button)
    add_settings_field('brc_token', 'Brighter API Token', function() {
        $val = esc_attr(get_option('brc_token'));
        $new_token = wp_generate_password(32, false, false); // 32-char random, letters/numbers only

        echo '<input type="text" class="regular-text" id="brc_token" name="brc_token" value="' . $val . '" />';
        echo '<p class="description">Used in REST API requests as <code>X-Brighter-Token</code>.</p>';
        echo '<p><button type="button" class="button" onclick="document.getElementById(\'brc_token\').value=\'' . $new_token . '\'">Generate New Token</button></p>';
    }, 'brighter_support_page', 'manual_links_section');
});
