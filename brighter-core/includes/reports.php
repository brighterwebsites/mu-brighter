<?php
// wp-content/mu-plugins/brighter-core/includes/reports.php

if ( ! defined('ABSPATH') ) exit;

/**
 * REST API: Reports & Analytics
 */
add_action('rest_api_init', function() {
    $namespace = 'brighter-core/v1';

    // Site Health (basic)
    register_rest_route($namespace, '/site-health', [
        'methods' => 'GET',
        'callback' => function() {
            if ( ! class_exists('WP_Site_Health') ) {
                require_once ABSPATH . 'wp-admin/includes/class-wp-site-health.php';
            }

            $health = new WP_Site_Health();

            // Grab a few core tests
            $tests = [
                'php_version'   => $health->get_test_php_version(),
                'https_status'  => $health->get_test_https_status(),
                'rest_avail'    => $health->get_test_rest_availability(),
            ];

            return new WP_REST_Response([
                'ok' => true,
                'tests' => $tests,
            ], 200);
        },
        'permission_callback' => 'brc_api_require_token',
    ]);

    // (Future) Add analytics reports here
});
