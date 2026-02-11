<?php
/**
 * Shared Folders API
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
        // Get all shared folders
        $folders = $db->fetchAll("SELECT * FROM shared_folders ORDER BY created_at DESC");
        
        echo json_encode([
            'success' => true,
            'folders' => $folders
        ]);
        break;

    case 'POST':
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'create':
                // Create new shared folder
                $name = $_POST['name'] ?? '';
                $path = $_POST['path'] ?? '/mnt/shares/' . time();
                $description = $_POST['description'] ?? '';
                $public = isset($_POST['public']) ? 1 : 0;
                $password = $_POST['password'] ?? '';
                $max_size_gb = $_POST['max_size_gb'] ?? 0;

                if (empty($name)) {
                    echo json_encode(['success' => false, 'message' => 'اسم المجلد مطلوب']);
                    exit;
                }

                // Create folder on filesystem
                $full_path = $_SERVER['DOCUMENT_ROOT'] . $path;
                if (!file_exists($full_path)) {
                    mkdir($full_path, 0755, true);
                }

                $db->execute(
                    "INSERT INTO shared_folders (name, path, description, public, password, max_size_gb, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$name, $path, $description, $public, $password, $max_size_gb, $_SESSION['user_id']]
                );

                $folder_id = $db->lastInsertId();

                // Log activity
                $db->execute(
                    "INSERT INTO activity_logs (user_id, action, category, details) VALUES (?, ?, ?, ?)",
                    [$_SESSION['user_id'], 'إنشاء مجلد مشترك', 'folders', "Folder: {$name}"]
                );

                echo json_encode([
                    'success' => true,
                    'message' => 'تم إنشاء المجلد المشترك بنجاح',
                    'folder_id' => $folder_id
                ]);
                break;

            case 'delete':
                // Delete folder
                $folder_id = $_POST['folder_id'] ?? 0;
                $folder = $db->fetchOne("SELECT * FROM shared_folders WHERE id = ?", [$folder_id]);

                if ($folder) {
                    // Check permissions
                    if ($folder['created_by'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'admin') {
                        echo json_encode(['success' => false, 'message' => 'ليس لديك صلاحية لحذف هذا المجلد']);
                        exit;
                    }

                    // Delete from database
                    $db->execute("DELETE FROM shared_folders WHERE id = ?", [$folder_id]);
                    $db->execute("DELETE FROM folder_permissions WHERE folder_id = ?", [$folder_id]);

                    // Log activity
                    $db->execute(
                        "INSERT INTO activity_logs (user_id, action, category, details) VALUES (?, ?, ?, ?)",
                        [$_SESSION['user_id'], 'حذف مجلد مشترك', 'folders', "Folder: {$folder['name']}"]
                    );

                    echo json_encode(['success' => true, 'message' => 'تم حذف المجلد']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'المجلد غير موجود']);
                }
                break;

            case 'share':
                // Share folder with user
                $folder_id = $_POST['folder_id'] ?? 0;
                $user_id = $_POST['user_id'] ?? 0;
                $can_read = isset($_POST['can_read']) ? 1 : 0;
                $can_write = isset($_POST['can_write']) ? 1 : 0;
                $can_delete = isset($_POST['can_delete']) ? 1 : 0;

                $db->execute(
                    "INSERT INTO folder_permissions (folder_id, user_id, can_read, can_write, can_delete) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE can_read = ?, can_write = ?, can_delete = ?",
                    [$folder_id, $user_id, $can_read, $can_write, $can_delete, $can_read, $can_write, $can_delete]
                );

                echo json_encode(['success' => true, 'message' => 'تم تحديث صلاحيات المجلد']);
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'إجراء غير معروف']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'طريقة غير مدعومة']);
}
?>
