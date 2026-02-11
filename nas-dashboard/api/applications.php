<?php
/**
 * Applications API
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
        // Get all applications
        $applications = $db->fetchAll("SELECT * FROM applications ORDER BY installed DESC, name ASC");
        
        echo json_encode([
            'success' => true,
            'applications' => $applications
        ]);
        break;

    case 'POST':
        // Toggle application status
        $app_id = $_POST['app_id'] ?? 0;
        $action = $_POST['action'] ?? '';

        if ($action === 'toggle') {
            $app = $db->fetchOne("SELECT * FROM applications WHERE id = ?", [$app_id]);
            
            if ($app) {
                $new_status = $app['status'] === 'running' ? 'stopped' : 'running';
                $db->execute("UPDATE applications SET status = ? WHERE id = ?", [$new_status, $app_id]);
                
                // Log activity
                $db->execute(
                    "INSERT INTO activity_logs (user_id, action, category, details) VALUES (?, ?, ?, ?)",
                    [$_SESSION['user_id'], 'تبديل حالة التطبيق', 'applications', "Application: {$app['name']} - Status: {$new_status}"]
                );
                
                echo json_encode([
                    'success' => true,
                    'message' => 'تم تحديث حالة التطبيق',
                    'status' => $new_status
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'التطبيق غير موجود']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'إجراء غير معروف']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'طريقة غير مدعومة']);
}
?>
