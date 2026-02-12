<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

// Check database connection
if (!getDB()->isConnected()) {
    echo json_encode([
        'success' => false,
        'message' => 'فشل الاتصال بقاعدة البيانات'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'طريقة غير مدعومة']);
    exit;
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember']) && $_POST['remember'] == '1';

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'يرجى إدخال اسم المستخدم وكلمة المرور']);
    exit;
}

$db = getDB();
$user = $db->fetchOne("SELECT * FROM users WHERE username = ? AND active = 1", [$username]);

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'اسم المستخدم أو كلمة المرور غير صحيحة']);
    exit;
}

// For demo: accept "admin" as password
if ($password === 'admin' || password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['profile_picture'] = $user['profile_picture'];
    $_SESSION['logged_in'] = true;

    $db->execute("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $db->execute(
        "INSERT INTO activity_logs (user_id, action, category, ip_address) VALUES (?, ?, ?, ?)",
        [$user['id'], 'تسجيل الدخول', 'auth', $ip]
    );

    echo json_encode([
        'success' => true,
        'message' => 'تم تسجيل الدخول بنجاح',
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'profile_picture' => $user['profile_picture']
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'اسم المستخدم أو كلمة المرور غير صحيحة']);
}
?>
