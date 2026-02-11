<?php
/**
 * System Stats API
 * NAS Dashboard
 */

session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

// Check authentication
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح']);
    exit;
}

$db = getDB();

// Get real system information
$stats = [];

// Storage stats
$storage_info = disk_free_space('/');
$storage_total = disk_total_space('/');
$storage_used = $storage_total - $storage_info;
$storage_used_gb = round($storage_used / (1024 * 1024 * 1024), 2);
$storage_total_gb = round($storage_total / (1024 * 1024 * 1024), 2);

$stats['storage'] = [
    'used' => $storage_used_gb . ' GB',
    'total' => $storage_total_gb . ' GB',
    'percent' => round(($storage_used / $storage_total) * 100, 2)
];

// VM stats
$vm_count = $db->fetchOne("SELECT COUNT(*) as count FROM virtual_machines WHERE status = 'running'");
$stats['vm'] = [
    'running' => $vm_count['count'] ?? 0,
    'total' => $db->fetchOne("SELECT COUNT(*) as count FROM virtual_machines")['count'] ?? 0
];

// Users stats
$users_count = $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE active = 1");
$stats['users'] = [
    'active' => $users_count['count'] ?? 0,
    'total' => $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'] ?? 0
];

// Network stats
$stats['network'] = [
    'status' => 'متصل',
    'uptime' => exec('uptime -p'),
    'hostname' => gethostname()
];

// CPU and Memory (Linux only)
if (PHP_OS_FAMILY === 'Linux') {
    $load = sys_getloadavg();
    $stats['cpu'] = [
        'load_1' => round($load[0], 2),
        'load_5' => round($load[1], 2),
        'load_15' => round($load[2], 2)
    ];
    
    $mem_free = shell_exec('free | grep Mem | awk \'{print $4/$2 * 100.0}\'');
    $stats['memory'] = [
        'free_percent' => round((float)$mem_free, 2)
    ];
}

echo json_encode([
    'success' => true,
    'stats' => $stats,
    'timestamp' => time()
]);
?>
