<?php

/**
 * Laravel Public Directory Proxy
 *
 * This script allows accessing the application without /public/ in the URL.
 * It proxies requests to /public/index.php while preserving proper REQUEST_URI
 * for both domain-root installs and subfolder installs.
 */

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$basePath = $scriptDir === '/' ? '' : rtrim($scriptDir, '/');

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH) ?: '/';

if ($basePath !== '' && str_starts_with($path, $basePath)) {
    $relativePath = substr($path, strlen($basePath));
} else {
    $relativePath = $path;
}

$relativePath = '/' . ltrim((string) $relativePath, '/');

$query = parse_url($requestUri, PHP_URL_QUERY);
$newUri = $basePath . '/public' . $relativePath;
if ($query) {
    $newUri .= '?' . $query;
}

$_SERVER['REQUEST_URI'] = $newUri;
$_SERVER['SCRIPT_NAME'] = $basePath . '/public/index.php';
$_SERVER['PHP_SELF'] = $basePath . '/public/index.php';

require __DIR__ . '/public/index.php';
