<?php
/**
 * Hubizz - Root Index Redirect
 * This file redirects all traffic to the public folder
 * Place this in /public_html root if .htaccess doesn't work
 */

// Check if we're already in the public folder
if (basename(__DIR__) === 'public') {
    // We're in public folder, load Laravel
    require __DIR__.'/index.php';
    exit;
}

// Redirect to public folder
$publicPath = __DIR__ . '/public';

// Check if public folder exists
if (!is_dir($publicPath)) {
    die('Error: Public folder not found. Please ensure Laravel is properly deployed.');
}

// Get the request URI
$uri = $_SERVER['REQUEST_URI'];

// Remove query string
$uri = strtok($uri, '?');

// Build the full path to the file in public
$filePath = $publicPath . $uri;

// If it's a real file in public, serve it
if (is_file($filePath)) {
    // Set appropriate content type
    $extension = pathinfo($filePath, PATHINFO_EXTENSION);
    $mimeTypes = [
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'json' => 'application/json',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'svg'  => 'image/svg+xml',
        'ico'  => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2'=> 'font/woff2',
        'ttf'  => 'font/ttf',
        'eot'  => 'application/vnd.ms-fontobject',
    ];

    if (isset($mimeTypes[$extension])) {
        header('Content-Type: ' . $mimeTypes[$extension]);
    }

    readfile($filePath);
    exit;
}

// Change to public directory
chdir($publicPath);

// Set up environment for Laravel
$_SERVER['SCRIPT_FILENAME'] = $publicPath . '/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';

// Load Laravel
require $publicPath . '/index.php';
