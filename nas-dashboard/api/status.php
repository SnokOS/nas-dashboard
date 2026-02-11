<?php
/**
 * Server Status API
 * NAS Dashboard
 */

header('Content-Type: application/json');

// Check if server is responsive
$status = [
    'status' => 'متصل',
    'timestamp' => time(),
    'uptime' => function_exists('exec') ? exec('uptime -p') : 'غير متاح',
    'server_time' => date('Y-m-d H:i:s'),
    'php_version' => phpversion(),
    'hostname' => gethostname()
];

echo json_encode($status);
?>
