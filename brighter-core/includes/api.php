<?php
// wp-content/mu-plugins/brighter-core/includes/api.php

if ( ! defined('ABSPATH') ) exit;

add_action('rest_api_init', function() {
    $namespace = 'brighter-core/v1';

    // Health
    register_rest_route($namespace, '/status', [
        'methods' => 'GET',
        'callback' => function() {
            return new WP_REST_Response([
                'ok'     => true,
                'plugin' => 'brighter-core',
                'time'   => current_time('mysql'),
            ], 200);
        },
        'permission_callback' => '__return_true', // public
    ]);

    // Settings (read)
    register_rest_route($namespace, '/settings', [
        'methods' => 'GET',
        'callback' => function() {
            $settings = get_option('brc_settings', [ 
                'notify_email' => get_option('admin_email') 
            ]);
            return new WP_REST_Response($settings, 200);
        },
        'permission_callback' => 'brc_api_require_token',
    ]);

    // Settings (write)
    register_rest_route($namespace, '/settings', [
        'methods' => 'POST',
        'callback' => function(WP_REST_Request $req) {
            $body     = $req->get_json_params();
            $settings = wp_parse_args(
                (array)$body, 
                (array)get_option('brc_settings', [])
            );
            update_option('brc_settings', $settings);
            return new WP_REST_Response([
                'updated'  => true, 
                'settings' => $settings
            ], 200);
        },
        'permission_callback' => 'brc_api_require_token',
    ]);

    // Files (read-only, limited to MU plugin directory)
    register_rest_route($namespace, '/files', [
        'methods' => 'GET',
        'callback' => function(WP_REST_Request $req) {
            $rel    = $req->get_param('path') ?: '';
            $base   = wp_normalize_path(WPMU_PLUGIN_DIR . '/brighter-core/');
            $target = wp_normalize_path($base . $rel);

            // Prevent directory traversal
            if (strpos($target, $base) !== 0 || !file_exists($target)) {
                return new WP_REST_Response([ 'error' => 'Invalid path' ], 400);
            }

            if (is_dir($target)) {
                return new WP_REST_Response([
                    'path'  => $rel,
                    'items' => array_values(array_diff(scandir($target), ['.', '..'])),
                ], 200);
            } else {
                return new WP_REST_Response([
                    'path'    => $rel,
                    'content' => file_get_contents($target),
                ], 200);
            }
        },
        'permission_callback' => 'brc_api_require_token',
    ]);
}); // <-- fixed closure


/**
 * Token authentication helper
 */
function brc_api_require_token(WP_REST_Request $req) {
    $provided = $req->get_header('X-Brighter-Token');
    $stored   = get_option('brc_token');

    // Check if token was provided
    if (!is_string($provided) || empty($stored)) {
        return new WP_REST_Response(['error' => 'Unauthorized'], 403);
    }

    // Validate the token
    if (hash_equals($stored, $provided)) {
        return true;
    }

    return new WP_Error('brc_forbidden', 'Invalid or missing API token', [ 'status' => 403 ]);
}

/**
 * Run a single Site Health test
 */
add_action('rest_api_init', function () {
    register_rest_route('brighter-core/v1', '/site-health/(?P<test>[a-zA-Z0-9\\-]+)', [
        'methods'  => 'GET',
        'callback' => 'brc_run_site_health_test',
        'args'     => [
            'test' => [
                'required' => true,
            ],
        ],
        'permission_callback' => 'brc_api_require_token',
    ]);

    // NEW: List all available site health tests
    register_rest_route('brighter-core/v1', '/site-health-tests', [
        'methods'  => 'GET',
        'callback' => function() {
            require_once ABSPATH . 'wp-admin/includes/class-wp-site-health.php';
            $health = new WP_Site_Health();
            $tests  = $health->get_tests()['direct'];
            return array_keys($tests);
        },
        'permission_callback' => 'brc_api_require_token',
    ]);
});

function brc_run_site_health_test($request) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-site-health.php';

    $slug   = sanitize_text_field($request['test']);
    $health = new WP_Site_Health();
    $tests  = $health->get_tests()['direct'];

    if (!isset($tests[$slug])) {
        return new WP_Error('not_found', 'Site health test not found', [ 'status' => 404 ]);
    }

    return call_user_func($tests[$slug]);
}

