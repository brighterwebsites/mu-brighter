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

    $max_size = intval(get_option('image_max_dimension', 2480));


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

add_action('update_option_image_max_dimension', function($old, $new) {
    error_log("Updated max image dimension to: $new px");
}, 10, 2);


add_action('admin_init', function () {
    $image_sizes = [
        'thumbnail'    => [150, 150, true],
        'medium'       => [300, 300, false],
        'custom_768w'  => [768, 0, false],
        'custom_1200w'  => [1200, 0, false],
        '1536x1536'    => [1536, 1536, false],
        '2048x2048'    => [2048, 2048, false],
    ];

// Create checkboxes for enabling/disabling sizes
    foreach ($image_sizes as $size => $dims) {
        add_settings_field("enable_size_$size", "Enable " . ucfirst($size), function () use ($size) {
            $enabled = get_option("enable_size_$size", 1);
            echo '<input type="checkbox" name="enable_size_' . $size . '" value="1" ' . checked(1, $enabled, false) . '> ' . ucfirst($size);
        }, 'brighter_optimisation_page', 'image_optimisation_section');
    }
});

// Dynamically control which sizes are generated
add_filter('intermediate_image_sizes_advanced', function($sizes) {
    foreach ($sizes as $size => $value) {
        if (!get_option("enable_size_$size", false)) {
            unset($sizes[$size]);
        }
    }
    return $sizes;
});

// Register custom image sizes
add_action('init', function() {
    // Always register sizes, but remove disabled ones in the filter above
    add_image_size('custom_768w', 768, 0, false);
    add_image_size('custom_1200w', 1200, 0, false);
    add_image_size('1536x1536', 1536, 1536, false);
    add_image_size('2048x2048', 2048, 2048, false);

    // Remove the default 1024px "large" size
    remove_image_size('large');
});

// Show custom sizes in Media dropdown
add_filter('image_size_names_choose', function($sizes) {
    if (get_option('enable_size_custom_768w')) {
        $sizes['custom_768w'] = 'Medium Large (768w)';
    }
    if (get_option('enable_size_custom_1200w')) {
        $sizes['custom_1200w'] = 'Large (1200w)';
    }
    return $sizes;
});

// Prevent WordPress from generating disabled default sizes
add_action('init', function () {
    $map = [
        'medium_large' => 'medium_large_size',
        '1536x1536'    => '1536x1536_size',
        '2048x2048'    => '2048x2048_size',
    ];
    foreach ($map as $size => $opt_prefix) {
        if (!get_option("enable_size_$size", 0)) {
            update_option("{$opt_prefix}_w", 0);
            update_option("{$opt_prefix}_h", 0);
        }
    }
});


add_filter('intermediate_image_sizes_advanced', function($sizes) {
    // Only keep sizes that are explicitly enabled
    $allowed_sizes = [];
    foreach (['thumbnail', 'medium', 'custom_768w', 'custom_1200w'] as $size) {
        if (get_option("enable_size_$size", 1)) {
            $allowed_sizes[] = $size;
        }
    }
    return array_intersect_key($sizes, array_flip($allowed_sizes));
}, 10, 1);

add_action('init', function () {
    $map = [
        'medium_large' => 'medium_large_size',
        '1536x1536'    => '1536x1536_size',
        '2048x2048'    => '2048x2048_size',
    ];
    foreach ($map as $size => $opt_prefix) {
        if (!get_option("enable_size_$size", 0)) {
            update_option("{$opt_prefix}_w", 0);
            update_option("{$opt_prefix}_h", 0);
        }
    }
});
add_action('after_setup_theme', function() {
    // Remove any custom sizes added by the theme
    foreach (get_intermediate_image_sizes() as $size) {
        if (!in_array($size, ['thumbnail', 'medium', 'custom_768w', 'custom_1200w'])) {
            remove_image_size($size);
        }
    }
});

if (isset($_GET['regen']) && $_GET['regen'] === 'done') {
    echo '<div class="notice notice-success is-dismissible"><p>All image sizes have been regenerated successfully!</p></div>';
}


// Disable comments on media attachments
add_filter('comments_open', function($open, $post_id) {
    $post = get_post($post_id);
    if ($post && $post->post_type === 'attachment') {
        return false;
    }
    return $open;
}, 10, 2);
