<?php
// Brighter Core MU Plugin Loader


// Define plugin constants
define('BRIGHTER_CORE_VERSION', '2.0.0');
define('BRIGHTER_CORE_PATH', plugin_dir_path(__FILE__));

// Module toggles – set to true/false to enable/disable
$brighter_modules = [
 	'brighter-support'=> true,
    	'brighter-support-image-settings'=> true,
	'brighter-tweaks'=> true,
	'custom-admin'    => true,
 	'custom-wpemail'    => true,
	'helpers'         => true,  
	'image-optimisation' => true,
	'login-styling'=> true,
	'php-limits' => true,
	'privacy-policy-style'=> true,
	'tracking'   => true,
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

