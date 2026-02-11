<?php
/**
 * Cameras API
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
        // Get all cameras
        $cameras = $db->fetchAll("SELECT * FROM cameras ORDER BY created_at DESC");
        
        echo json_encode([
            'success' => true,
            'cameras' => $cameras
        ]);
        break;

    case 'POST':
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'create':
                // Add new camera
                $name = $_POST['name'] ?? '';
                $ip_address = $_POST['ip_address'] ?? '';
                $port = $_POST['port'] ?? 554;
                $protocol = $_POST['protocol'] ?? 'rtsp';
                $username = $_POST['username'] ?? '';
                $password = $_POST['password'] ?? '';
                $location = $_POST['location'] ?? '';
                $stream_url = $_POST['stream_url'] ?? '';

                if (empty($name) || empty($ip_address)) {
                    echo json_encode(['success' => false, 'message' => 'الاسم وعنوان IP مطلوبان']);
                    exit;
                }

                // Build stream URL if not provided
                if (empty($stream_url)) {
                    $stream_url = "{$protocol}://{$username}:{$password}@{$ip_address}:{$port}/stream";
                }

                $db->execute(
                    "INSERT INTO cameras (name, ip_address, port, protocol, username, password, stream_url, location, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$name, $ip_address, $port, $protocol, $username, $password, $stream_url, $location, 'offline']
                );

                $camera_id = $db->lastInsertId();

                // Log activity
                $db->execute(
                    "INSERT INTO activity_logs (user_id, action, category, details) VALUES (?, ?, ?, ?)",
                    [$_SESSION['user_id'], 'إضافة كاميرا', 'cameras', "Camera: {$name} - IP: {$ip_address}"]
                );

                echo json_encode([
                    'success' => true,
                    'message' => 'تمت إضافة الكاميرا بنجاح',
                    'camera_id' => $camera_id
                ]);
                break;

            case 'toggle_recording':
                // Toggle recording status
                $camera_id = $_POST['camera_id'] ?? 0;
                $camera = $db->fetchOne("SELECT * FROM cameras WHERE id = ?", [$camera_id]);

                if ($camera) {
                    $new_recording = $camera['recording'] ? 0 : 1;
                    $db->execute("UPDATE cameras SET recording = ? WHERE id = ?", [$new_recording, $camera_id]);

                    // Here would be actual recording start/stop logic
                    // ffmpeg -i $stream_url -c copy output.mp4

                    echo json_encode([
                        'success' => true,
                        'message' => 'تم تحديث حالة التسجيل',
                        'recording' => $new_recording
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'الكاميرا غير موجودة']);
                }
                break;

            case 'check_status':
                // Check camera online status
                $camera_id = $_POST['camera_id'] ?? 0;
                $camera = $db->fetchOne("SELECT * FROM cameras WHERE id = ?", [$camera_id]);

                if ($camera) {
                    // Simple ping check
                    $online = @fsockopen($camera['ip_address'], $camera['port'], $errno, $errstr, 2);
                    $status = $online ? 'online' : 'offline';
                    if ($online) fclose($online);

                    $db->execute("UPDATE cameras SET status = ? WHERE id = ?", [$status, $camera_id]);

                    echo json_encode([
                        'success' => true,
                        'status' => $status
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'الكاميرا غير موجودة']);
                }
                break;

            case 'delete':
                // Delete camera
                $camera_id = $_POST['camera_id'] ?? 0;
                $camera = $db->fetchOne("SELECT * FROM cameras WHERE id = ?", [$camera_id]);

                if ($camera) {
                    $db->execute("DELETE FROM cameras WHERE id = ?", [$camera_id]);

                    // Log activity
                    $db->execute(
                        "INSERT INTO activity_logs (user_id, action, category, details) VALUES (?, ?, ?, ?)",
                        [$_SESSION['user_id'], 'حذف كاميرا', 'cameras', "Camera: {$camera['name']}"]
                    );

                    echo json_encode(['success' => true, 'message' => 'تم حذف الكاميرا']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'الكاميرا غير موجودة']);
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
