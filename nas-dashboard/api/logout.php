<?php
/**
 * Logout API
 * NAS Dashboard
 */

session_start();
header('Content-Type: application/json');

// Destroy session
session_destroy();
session_unset();

echo json_encode(['success' => true, 'message' => 'تم تسجيل الخروج بنجاح']);
?>
