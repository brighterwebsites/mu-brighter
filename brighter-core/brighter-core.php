<?php
// Brighter Core MU Plugin Loader

// Define plugin constants
define('BRIGHTER_CORE_VERSION', '1.0.0');
define('BRIGHTER_CORE_PATH', plugin_dir_path(__FILE__));

// Module toggles – set to true/false to enable/disable
$brighter_modules = [
    'activity-log'    => true,
    'business-info'   => true,
    'custom-admin'    => true,
    'brighter-support'=> true,
    'login-styling'=> true,
    'privacy-policy-style'=> true,
    'image-optimisation' => true,
    'php-limits' => true,
    'helpers'         => true, // Include helpers if needed
    // 'performance'   => false, // Remove or create the file
];

// Include modules conditionally
foreach ($brighter_modules as $module => $enabled) {
    if ($enabled) {
        $path = BRIGHTER_CORE_PATH . 'includes/' . $module . '.php';
        if (file_exists($path)) {
            require_once $path;
        } else {
            error_log("Brighter Core: Module file not found: $path");
        }
    }
}
