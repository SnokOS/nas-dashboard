<?php
/**
 * User Info API
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

// Get current user info
$user = $db->fetchOne("SELECT id, username, email, role, permissions, created_at, last_login FROM users WHERE id = ?", [$_SESSION['user_id']]);

if ($user) {
    echo json_encode([
        'success' => true,
        'user' => $user
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'المستخدم غير موجود']);
}
?>
