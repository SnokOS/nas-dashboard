<?php
/**
 * Check Authentication API
 * NAS Dashboard
 */

session_start();
header('Content-Type: application/json');

$authenticated = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

if ($authenticated) {
    echo json_encode([
        'authenticated' => true,
        'user' => [
            'id' => $_SESSION['user_id'] ?? 0,
            'username' => $_SESSION['username'] ?? '',
            'role' => $_SESSION['role'] ?? 'user'
        ]
    ]);
} else {
    echo json_encode(['authenticated' => false]);
}
?>
