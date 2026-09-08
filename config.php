<?php
// Prevent multiple inclusions
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__FILE__));
    define('PUBLIC_PATH', ROOT_PATH . '/PUBLIC');
    define('VIEWS_PATH', ROOT_PATH . '/VIEWS');
    define('MODELS_PATH', ROOT_PATH . '/MODELS');
    define('CONTROLLERS_PATH', ROOT_PATH . '/CONTROLLAR');
    define('DATABASE_PATH', ROOT_PATH . '/DATABASE');

    // Dynamic Base URL detection for local development and Railway deployment
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    
    // Determine base URL relative to root
    $baseUrl = getenv('APP_URL') ?: ($protocol . "://" . $host . '/');
    if (!str_ends_with($baseUrl, '/')) {
        $baseUrl .= '/';
    }
    
    define('BASE_URL', $baseUrl);

    // Asset paths
    define('CSS_PATH', BASE_URL . 'PUBLIC/CSS/');
    define('JS_PATH', BASE_URL . 'PUBLIC/JS/');
    define('IMAGES_PATH', BASE_URL . 'PUBLIC/pic/');

    // Environment mode
    define('APP_ENV', getenv('APP_ENV') ?: 'production');
    
    if (APP_ENV === 'development') {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
    } else {
        ini_set('display_errors', 0);
        ini_set('display_startup_errors', 0);
        error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    }
}

// Helper functions
if (!function_exists('asset')) {
    function asset($path) {
        return BASE_URL . 'PUBLIC/' . ltrim($path, '/');
    }
}

if (!function_exists('view')) {
    function view($path) {
        return BASE_URL . 'VIEWS/' . ltrim($path, '/');
    }
}

if (!function_exists('model')) {
    function model($path) {
        return BASE_URL . 'MODELS/' . ltrim($path, '/');
    }
}

if (!function_exists('controller')) {
    function controller($path) {
        return BASE_URL . 'CONTROLLAR/' . ltrim($path, '/');
    }
}
?>