<?php
/**
 * Settings API
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
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get all settings
        $all_settings = $db->fetchAll("SELECT * FROM settings ORDER BY category, setting_key");
        $network = $db->fetchAll("SELECT * FROM network_settings ORDER BY protocol");

        // Group settings by category
        $grouped_settings = [];
        foreach ($all_settings as $setting) {
            $category = $setting['category'];
            if (!isset($grouped_settings[$category])) {
                $grouped_settings[$category] = [];
            }
            $grouped_settings[$category][] = $setting;
        }
        
        echo json_encode([
            'success' => true,
            'settings' => $grouped_settings,
            'network' => $network,
            'all_settings' => $all_settings
        ]);
        break;

    case 'POST':
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'update':
                // Update setting
                $category = $_POST['category'] ?? '';
                $setting_key = $_POST['setting_key'] ?? '';
                $setting_value = $_POST['setting_value'] ?? '';

                if (empty($category) || empty($setting_key)) {
                    echo json_encode(['success' => false, 'message' => 'معلومات الإعداد غير مكتملة']);
                    exit;
                }

                $db->execute(
                    "UPDATE settings SET setting_value = ? WHERE category = ? AND setting_key = ?",
                    [$setting_value, $category, $setting_key]
                );

                // Log activity
                $db->execute(
                    "INSERT INTO activity_logs (user_id, action, category, details) VALUES (?, ?, ?, ?)",
                    [$_SESSION['user_id'], 'تحديث إعداد', 'settings', "Setting: {$category}.{$setting_key} = {$setting_value}"]
                );

                echo json_encode(['success' => true, 'message' => 'تم تحديث الإعداد']);
                break;

            case 'update_network':
                // Update network protocol
                $protocol = $_POST['protocol'] ?? '';
                $enabled = isset($_POST['enabled']) ? 1 : 0;
                $port = $_POST['port'] ?? 0;

                if (empty($protocol)) {
                    echo json_encode(['success' => false, 'message' => 'البروتوكول مطلوب']);
                    exit;
                }

                $db->execute(
                    "UPDATE network_settings SET enabled = ?, port = ? WHERE protocol = ?",
                    [$enabled, $port, $protocol]
                );

                echo json_encode(['success' => true, 'message' => 'تم تحديث إعدادات الشبكة']);
                break;

            case 'add_user':
                // Add new user
                $username = $_POST['username'] ?? '';
                $password = $_POST['password'] ?? '';
                $email = $_POST['email'] ?? '';
                $role = $_POST['role'] ?? 'user';
                $permissions = $_POST['permissions'] ?? '{}';

                if (empty($username) || empty($password)) {
                    echo json_encode(['success' => false, 'message' => 'اسم المستخدم وكلمة المرور مطلوبان']);
                    exit;
                }

                // Check if username exists
                $existing = $db->fetchOne("SELECT id FROM users WHERE username = ?", [$username]);
                if ($existing) {
                    echo json_encode(['success' => false, 'message' => 'اسم المستخدم موجود بالفعل']);
                    exit;
                }

                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $db->execute(
                    "INSERT INTO users (username, password, email, role, permissions) VALUES (?, ?, ?, ?, ?)",
                    [$username, $hashed_password, $email, $role, $permissions]
                );

                $user_id = $db->lastInsertId();

                // Log activity
                $db->execute(
                    "INSERT INTO activity_logs (user_id, action, category, details) VALUES (?, ?, ?, ?)",
                    [$_SESSION['user_id'], 'إضافة مستخدم', 'users', "User: {$username} - Role: {$role}"]
                );

                echo json_encode([
                    'success' => true,
                    'message' => 'تمت إضافة المستخدم بنجاح',
                    'user_id' => $user_id
                ]);
                break;

            case 'update_user':
                // Update user
                $user_id = $_POST['user_id'] ?? 0;
                $role = $_POST['role'] ?? '';
                $permissions = $_POST['permissions'] ?? '';
                $active = isset($_POST['active']) ? 1 : 0;

                if ($user_id == 0) {
                    echo json_encode(['success' => false, 'message' => 'معرف المستخدم مطلوب']);
                    exit;
                }

                $updates = [];
                $params = [];

                if (!empty($role)) {
                    $updates[] = "role = ?";
                    $params[] = $role;
                }
                if (!empty($permissions)) {
                    $updates[] = "permissions = ?";
                    $params[] = $permissions;
                }
                $updates[] = "active = ?";
                $params[] = $active;

                $params[] = $user_id;

                $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
                $db->execute($sql, $params);

                echo json_encode(['success' => true, 'message' => 'تم تحديث المستخدم']);
                break;

            case 'delete_user':
                // Delete user
                $user_id = $_POST['user_id'] ?? 0;

                if ($user_id == $_SESSION['user_id']) {
                    echo json_encode(['success' => false, 'message' => 'لا يمكنك حذف حسابك الخاص']);
                    exit;
                }

                $user = $db->fetchOne("SELECT username FROM users WHERE id = ?", [$user_id]);
                if ($user) {
                    $db->execute("DELETE FROM users WHERE id = ?", [$user_id]);

                    // Log activity
                    $db->execute(
                        "INSERT INTO activity_logs (user_id, action, category, details) VALUES (?, ?, ?, ?)",
                        [$_SESSION['user_id'], 'حذف مستخدم', 'users', "User: {$user['username']}"]
                    );

                    echo json_encode(['success' => true, 'message' => 'تم حذف المستخدم']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'المستخدم غير موجود']);
                }
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'إجراء غير معروف']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'طريقة غير مدعومة']);
}
?>
