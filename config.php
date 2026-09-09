<?php
// Base URL configuration
define('BASE_URL', '/MVC/');

// Directory paths
define('ROOT_PATH', dirname(__FILE__));
define('PUBLIC_PATH', ROOT_PATH . '/PUBLIC');
define('VIEWS_PATH', ROOT_PATH . '/VIEWS');
define('MODELS_PATH', ROOT_PATH . '/MODELS');
define('CONTROLLERS_PATH', ROOT_PATH . '/CONTROLLAR');
define('DATABASE_PATH', ROOT_PATH . '/DATABASE');

// Asset paths
define('CSS_PATH', BASE_URL . 'PUBLIC/CSS/');
define('JS_PATH', BASE_URL . 'PUBLIC/JS/');
define('IMAGES_PATH', BASE_URL . 'PUBLIC/pic/');

// Helper function to get asset URLs
function asset($path) {
    return BASE_URL . 'PUBLIC/' . $path;
}

// Helper function to get view URLs
function view($path) {
    return BASE_URL . 'VIEWS/' . $path;
}

// Helper function to get model URLs
function model($path) {
    return BASE_URL . 'MODELS/' . $path;
}

// Helper function to get controller URLs
function controller($path) {
    return BASE_URL . 'CONTROLLAR/' . $path;
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'root');
define('DB_PASS', '');
?> 