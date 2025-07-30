<?php
/**
 * Image Optimisation Enhancements
 * Brighter Websites – Unified Image Size Management
 */

if (!defined('ABSPATH')) exit;

/**
 * Resize uploaded images to max dimension (if enabled in settings).
 */
add_filter('wp_handle_upload', 'brighter_resize_uploaded_images');
function brighter_resize_uploaded_images($upload) {
    $file_path = $upload['file'];
    $file_type = $upload['type'];

    if (!preg_match('/^image\/(jpe?g|png|gif)$/', $file_type)) return $upload;
    if (get_option('enable_image_resize', 'yes') !== 'yes') return $upload;

    list($width, $height) = getimagesize($file_path);
    $max_size = intval(get_option('image_max_dimension', 2480));

    if ($width <= $max_size && $height <= $max_size) return $upload;

    $aspect_ratio = $width / $height;
    $new_width = ($width >= $height) ? $max_size : intval($max_size * $aspect_ratio);
    $new_height = ($width >= $height) ? intval($max_size / $aspect_ratio) : $max_size;

    switch ($file_type) {
        case 'image/jpeg': $src = imagecreatefromjpeg($file_path); break;
        case 'image/png':  $src = imagecreatefrompng($file_path); break;
        case 'image/gif':  $src = imagecreatefromgif($file_path); break;
        default: return $upload;
    }

    $dst = imagecreatetruecolor($new_width, $new_height);
    if (in_array($file_type, ['image/png', 'image/gif'])) {
        imagecolortransparent($dst, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
    }

    imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

    switch ($file_type) {
        case 'image/jpeg': imagejpeg($dst, $file_path, 90); break;
        case 'image/png':  imagepng($dst, $file_path); break;
        case 'image/gif':  imagegif($dst, $file_path); break;
    }

    imagedestroy($src);
    imagedestroy($dst);

    return $upload;
}

/**
 * Force modern image size settings.
 * - Large = 1200px (uncropped)
 * - Medium = 300px (uncropped)
 * - Add medium_large, 1536px, and 2048px
 */
/**
 * Force modern image size settings early.
 */
add_action('init', function () {
    // Sync core WP sizes
    if ((int) get_option('large_size_w') !== 1200) {
        update_option('thumbnail_size_w', 150);
        update_option('thumbnail_size_h', 150);
        update_option('medium_size_w', 300);
        update_option('medium_size_h', 0);
        update_option('large_size_w', 1200);
        update_option('large_size_h', 0);
    }

    // Register custom sizes
    add_image_size('medium_large', 768, 0);
    add_image_size('1536x1536', 1536, 0);
    add_image_size('2048x2048', 2048, 0);
    add_image_size('custom_768w', 768, 0); // Ensure custom_768w is included
});

/**
 * Disable WordPress' big image scaling (no "_scaled" files).
 */
add_filter('big_image_size_threshold', '__return_false');

/**
 * Disable comments on media attachments.
 */
add_filter('comments_open', function($open, $post_id) {
    $post = get_post($post_id);
    return ($post && $post->post_type === 'attachment') ? false : $open;
}, 10, 2);


add_action('admin_post_brighter_regenerate_images', function () {
    if (!current_user_can('manage_options') || !wp_verify_nonce($_GET['_wpnonce'], 'brighter_regenerate_images')) {
        wp_die('Access denied');
    }

    $attachments = get_posts([
        'post_type'      => 'attachment',
        'post_mime_type' => 'image',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ]);

    foreach ($attachments as $attachment_id) {
        $fullsize_path = get_attached_file($attachment_id);
        if (file_exists($fullsize_path)) {
            wp_update_attachment_metadata(
                $attachment_id,
                wp_generate_attachment_metadata($attachment_id, $fullsize_path)
            );
        }
    }

    wp_redirect(admin_url('options-general.php?page=brighter_optimisation_page&regen=done'));
    exit;
});

add_action('admin_post_brighter_regenerate_images', function () {
    if (!current_user_can('manage_options') || !isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'brighter_regenerate_images')) {
        wp_die('Access denied');
    }

    $attachments = get_posts([
        'post_type'      => 'attachment',
        'post_mime_type' => 'image',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ]);

    foreach ($attachments as $attachment_id) {
        $file = get_attached_file($attachment_id);
        if ($file && file_exists($file)) {
            wp_update_attachment_metadata(
                $attachment_id,
                wp_generate_attachment_metadata($attachment_id, $file)
            );
        }
    }

    wp_redirect(admin_url('options-general.php?page=brighter_support&tab=optimisation&regen=done'));
    exit;
});

