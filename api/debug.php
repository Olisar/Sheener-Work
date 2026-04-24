<?php
/* File: sheener/api/debug.php */

/**
 * Debug endpoint to test configuration
 */
header('Content-Type: application/json');

$debug = [
    'php_version' => PHP_VERSION,
    'curl_available' => function_exists('curl_init'),
    'config_file_exists' => file_exists(__DIR__ . '/config.php'),
    'config_loaded' => false,
    'api_key_set' => false,
    'api_key_length' => 0
];

if ($debug['config_file_exists']) {
    try {
        $config = require __DIR__ . '/config.php';
        $debug['config_loaded'] = is_array($config);
        if ($debug['config_loaded']) {
            $debug['api_key_set'] = isset($config['gemini_api_key']) && !empty($config['gemini_api_key']);
            if ($debug['api_key_set']) {
                $debug['api_key_length'] = strlen($config['gemini_api_key']);
                $debug['api_key_preview'] = substr($config['gemini_api_key'], 0, 10) . '...';
            }
        }
    } catch (Exception $e) {
        $debug['config_error'] = $e->getMessage();
    }
}

echo json_encode($debug, JSON_PRETTY_PRINT);

