<?php

/**
 * Image Optimisation Enhancements
 * Restrict image sizes and control auto-generated variants
 */
 
 
// Brighter Websites: Image Optimisation Settings
if ( ! defined( 'ABSPATH' ) ) exit;


add_filter('wp_handle_upload', 'brighter_resize_uploaded_images');
function brighter_resize_uploaded_images($upload) {
    $file_path = $upload['file'];
    $file_type = $upload['type'];

    if (!preg_match('/^image\/(jpe?g|png|gif)$/', $file_type)) return $upload;

    if (get_option('enable_image_resize', 'yes') !== 'yes') return $upload;

    list($width, $height) = getimagesize($file_path);
    $max_size = intval(get_option('image_max_resize', 2480));

    if ($width <= $max_size && $height <= $max_size) return $upload;

    $aspect_ratio = $width / $height;
    $new_width = $width >= $height ? $max_size : intval($max_size * $aspect_ratio);
    $new_height = $width >= $height ? intval($max_size / $aspect_ratio) : $max_size;

    switch ($file_type) {
        case 'image/jpeg': $src = imagecreatefromjpeg($file_path); break;
        case 'image/png': $src = imagecreatefrompng($file_path); break;
        case 'image/gif': $src = imagecreatefromgif($file_path); break;
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
        case 'image/png': imagepng($dst, $file_path); break;
        case 'image/gif': imagegif($dst, $file_path); break;
    }

    imagedestroy($src);
    imagedestroy($dst);

    return $upload;
}


/**
 * Only allow thumbnail (150x150), medium (300x300), and large (1024x1024).
 * Prevent all other default sizes and WooCommerce extras.
 */

// 1. Remove unwanted sizes
function custom_image_sizes_filter( $sizes ) {
    // Keep only the ones you want
    return [
        'thumbnail' => $sizes['thumbnail'],
        'medium' => $sizes['medium'],
        'large' => $sizes['large'],
    ];
}
// Disable large image scaling
add_filter('big_image_size_threshold', '__return_false');

// Completely remove default large sizes
add_filter('intermediate_image_sizes_advanced', function($sizes) {
    $enabled = get_option('enable_extra_image_sizes', false);
    if (!$enabled) {
        unset($sizes['medium_large']);
        unset($sizes['1536x1536']);
        unset($sizes['2048x2048']);
    }
    return $sizes;
});

// Also forcefully set their dimensions to 0
function remove_large_image_dimensions() {
    update_option('medium_large_size_w', 0);
    update_option('medium_large_size_h', 0);
    update_option('1536x1536_size_w', 0);
    update_option('1536x1536_size_h', 0);
    update_option('2048x2048_size_w', 0);
    update_option('2048x2048_size_h', 0);
}
add_action('init', 'remove_large_image_dimensions');



// Disable comments on media attachments
add_filter('comments_open', function($open, $post_id) {
    $post = get_post($post_id);
    if ($post && $post->post_type === 'attachment') {
        return false;
    }
    return $open;
}, 10, 2);
