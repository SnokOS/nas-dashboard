<?php
/**
 * Login API
 * NAS Dashboard
 */

session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'طريقة غير مدعومة']);
    exit;
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember']);

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'يرجى إدخال اسم المستخدم وكلمة المرور']);
    exit;
}

$db = getDB();

// Get user from database
$user = $db->fetchOne(
    "SELECT * FROM users WHERE username = ? AND active = 1",
    [$username]
);

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'اسم المستخدم أو كلمة المرور غير صحيحة']);
    exit;
}

// Verify password (for demo purposes, accept "admin" as password)
// In production, use password_verify($password, $user['password'])
if ($password === 'admin') {
    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['logged_in'] = true;

    // Update last login
    $db->execute("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);

    // Log activity
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $db->execute(
        "INSERT INTO activity_logs (user_id, action, category, ip_address) VALUES (?, ?, ?, ?)",
        [$user['id'], 'تسجيل الدخول', 'auth', $ip_address]
    );

    echo json_encode([
        'success' => true,
        'message' => 'تم تسجيل الدخول بنجاح',
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role']
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'اسم المستخدم أو كلمة المرور غير صحيحة']);
}
?>
