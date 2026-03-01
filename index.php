<?php

/**
 * Laravel Public Directory Proxy
 *
 * This script allows accessing the application without /public/ in the URL.
 * It intercepts requests to /LMS-Misdinar/... and proxies them to /public/index.php
 * while fixing the REQUEST_URI so Symfony/Laravel correctly resolves routes.
 *
 * Example:
 *   /LMS-Misdinar/login  →  internally handled as /LMS-Misdinar/public/login
 */

// Determine the base path for this project
$basePath = dirname($_SERVER['SCRIPT_NAME']); // e.g., /LMS-Misdinar

// Get the requested path relative to the project root
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Extract the part after the base path
$relativePath = substr($path, strlen($basePath)); // e.g., /login
if ($relativePath === false) {
    $relativePath = '/';
}

// Fix REQUEST_URI to include /public so Symfony can compute baseUrl correctly
$query = parse_url($requestUri, PHP_URL_QUERY);
$newUri = $basePath . '/public' . $relativePath;
if ($query) {
    $newUri .= '?' . $query;
}
$_SERVER['REQUEST_URI'] = $newUri;
$_SERVER['SCRIPT_NAME'] = $basePath . '/public/index.php';
$_SERVER['PHP_SELF'] = $basePath . '/public/index.php';

// Load Laravel's front controller
require __DIR__ . '/public/index.php';
