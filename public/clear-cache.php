<?php
/**
 * Cache Clear Script
 * DELETE THIS FILE AFTER USE!
 *
 * Access: https://api.alemedu.com/clear-cache.php?key=YOUR_SECRET_KEY
 */

// Security key - change this to something secret
$secretKey = 'clear-cache-2024-secret';

// Check the key
if (!isset($_GET['key']) || $_GET['key'] !== $secretKey) {
    http_response_code(403);
    die('Forbidden');
}

// Change to Laravel root directory
chdir(dirname(__DIR__));

// Clear config cache
$output = [];
$output[] = "=== Clearing Config Cache ===";
exec('php artisan config:clear 2>&1', $configClear);
$output = array_merge($output, $configClear);

$output[] = "\n=== Rebuilding Config Cache ===";
exec('php artisan config:cache 2>&1', $configCache);
$output = array_merge($output, $configCache);

$output[] = "\n=== Clearing Application Cache ===";
exec('php artisan cache:clear 2>&1', $cacheClear);
$output = array_merge($output, $cacheClear);

$output[] = "\n=== Clearing Route Cache ===";
exec('php artisan route:clear 2>&1', $routeClear);
$output = array_merge($output, $routeClear);

$output[] = "\n=== Clearing View Cache ===";
exec('php artisan view:clear 2>&1', $viewClear);
$output = array_merge($output, $viewClear);

// Output results
header('Content-Type: text/plain; charset=utf-8');
echo "Laravel Cache Clear Results\n";
echo "===========================\n\n";
echo implode("\n", $output);
echo "\n\n⚠️ DELETE THIS FILE NOW FOR SECURITY!";
