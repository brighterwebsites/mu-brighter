<?php
// Brighter Websites: Increase PHP Limits via MU Plugin
if ( ! defined( 'ABSPATH' ) ) exit;

// Memory Limits
if ( ! defined( 'WP_MEMORY_LIMIT' ) ) {
    define( 'WP_MEMORY_LIMIT', '256M' );
}
if ( ! defined( 'WP_MAX_MEMORY_LIMIT' ) ) {
    define( 'WP_MAX_MEMORY_LIMIT', '512M' );
}

// Timeouts
if ( ! ini_get( 'max_execution_time' ) || ini_get( 'max_execution_time' ) < 300 ) {
    ini_set( 'max_execution_time', '300' );
}
if ( ! ini_get( 'max_input_time' ) || ini_get( 'max_input_time' ) < 300 ) {
    ini_set( 'max_input_time', '300' );
}

// Upload/Post Limits
if ( ! ini_get( 'post_max_size' ) || (int) ini_get( 'post_max_size' ) < 64 ) {
    ini_set( 'post_max_size', '64M' );
}
if ( ! ini_get( 'upload_max_filesize' ) || (int) ini_get( 'upload_max_filesize' ) < 64 ) {
    ini_set( 'upload_max_filesize', '64M' );
}
if ( ! ini_get( 'max_input_vars' ) || (int) ini_get( 'max_input_vars' ) < 3000 ) {
    ini_set( 'max_input_vars', '3000' );
}

// Optional: GZIP Compression
if ( ! ini_get( 'zlib.output_compression' ) ) {
    ini_set( 'zlib.output_compression', 'On' );
}
