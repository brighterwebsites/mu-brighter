<?php
// wp-content/mu-plugins/brighter-core/includes/api.php

if (!defined('ABSPATH')) exit;

// Register our custom endpoint
add_action('rest_api_init', function () {
    register_rest_route('brighter-core/v1', '/posts', [
        'methods'  => 'GET',
        'callback' => 'brc_get_blog_posts',
        'permission_callback' => 'brc_api_require_token', // Optional: make public by replacing with '__return_true'
    ]);
});

// Endpoint callback
function brc_get_blog_posts($req) {
    $args = [
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => 20, // Adjust as needed
    ];

    $posts = get_posts($args);

    $data = array_map(function ($post) {
        return [
            'id'      => $post->ID,
            'title'   => get_the_title($post),
            'excerpt' => get_the_excerpt($post),
            'content' => wp_strip_all_tags($post->post_content),
            'slug'    => $post->post_name,
            'tags'    => wp_get_post_tags($post->ID, ['fields' => 'names']),
            'url'     => get_permalink($post),
        ];
    }, $posts);

    return new WP_REST_Response($data, 200);
}

