<?php
/**
 * Virtual Machines API
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
        // Get all VMs and available Docker images
        $vms = $db->fetchAll("SELECT * FROM virtual_machines ORDER BY created_at DESC");
        $images = $db->fetchAll("SELECT * FROM docker_images ORDER BY os_type, distribution, tag");
        
        // Group images by OS type and distribution
        $grouped_images = [];
        foreach ($images as $image) {
            $os = $image['os_type'];
            $dist = $image['distribution'];
            
            if (!isset($grouped_images[$os])) {
                $grouped_images[$os] = [];
            }
            if (!isset($grouped_images[$os][$dist])) {
                $grouped_images[$os][$dist] = [];
            }
            $grouped_images[$os][$dist][] = $image;
        }
        
        echo json_encode([
            'success' => true,
            'vms' => $vms,
            'images' => $images,
            'grouped_images' => $grouped_images
        ]);
        break;

    case 'POST':
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'create':
                // Create new VM
                $name = $_POST['name'] ?? 'VM-' . time();
                $os_type = $_POST['os_type'] ?? 'linux';
                $distribution = $_POST['distribution'] ?? 'Ubuntu';
                $version = $_POST['version'] ?? 'latest';
                $docker_image = $_POST['docker_image'] ?? 'ubuntu:latest';
                $cpu_cores = $_POST['cpu_cores'] ?? 1;
                $ram_mb = $_POST['ram_mb'] ?? 1024;
                $disk_gb = $_POST['disk_gb'] ?? 20;

                // Generate MAC address
                $mac_address = sprintf(
                    '02:%02x:%02x:%02x:%02x:%02x',
                    mt_rand(0, 255),
                    mt_rand(0, 255),
                    mt_rand(0, 255),
                    mt_rand(0, 255),
                    mt_rand(0, 255)
                );

                $db->execute(
                    "INSERT INTO virtual_machines (name, os_type, distribution, version, docker_image, mac_address, cpu_cores, ram_mb, disk_gb, created_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$name, $os_type, $distribution, $version, $docker_image, $mac_address, $cpu_cores, $ram_mb, $disk_gb, $_SESSION['user_id'], 'stopped']
                );

                $vm_id = $db->lastInsertId();

                // Here would be actual Docker container creation
                // docker run -d --name $name --cpus=$cpu_cores --memory="${ram_mb}m" $docker_image

                // Log activity
                $db->execute(
                    "INSERT INTO activity_logs (user_id, action, category, details) VALUES (?, ?, ?, ?)",
                    [$_SESSION['user_id'], 'إنشاء جهاز وهمي', 'vm', "VM: {$name} - OS: {$distribution} {$version}"]
                );

                echo json_encode([
                    'success' => true,
                    'message' => 'تم إنشاء الجهاز الوهمي بنجاح',
                    'vm_id' => $vm_id
                ]);
                break;

            case 'toggle':
                // Toggle VM status
                $vm_id = $_POST['vm_id'] ?? 0;
                $vm = $db->fetchOne("SELECT * FROM virtual_machines WHERE id = ?", [$vm_id]);

                if ($vm) {
                    $new_status = $vm['status'] === 'running' ? 'stopped' : 'running';
                    
                    // Assign IP if starting
                    $ip_address = $vm['ip_address'];
                    if ($new_status === 'running' && empty($ip_address)) {
                        $ip_address = '192.168.1.' . (100 + $vm_id);
                        $db->execute("UPDATE virtual_machines SET ip_address = ? WHERE id = ?", [$ip_address, $vm_id]);
                    }

                    $db->execute("UPDATE virtual_machines SET status = ? WHERE id = ?", [$new_status, $vm_id]);

                    // Here would be actual Docker container start/stop
                    // docker start/stop $container_name

                    echo json_encode([
                        'success' => true,
                        'message' => 'تم تحديث حالة الجهاز الوهمي',
                        'status' => $new_status,
                        'ip_address' => $ip_address
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'الجهاز الوهمي غير موجود']);
                }
                break;

            case 'delete':
                // Delete VM
                $vm_id = $_POST['vm_id'] ?? 0;
                $vm = $db->fetchOne("SELECT * FROM virtual_machines WHERE id = ?", [$vm_id]);

                if ($vm) {
                    $db->execute("DELETE FROM virtual_machines WHERE id = ?", [$vm_id]);

                    // Here would be actual Docker container removal
                    // docker rm -f $container_name

                    // Log activity
                    $db->execute(
                        "INSERT INTO activity_logs (user_id, action, category, details) VALUES (?, ?, ?, ?)",
                        [$_SESSION['user_id'], 'حذف جهاز وهمي', 'vm', "VM: {$vm['name']}"]
                    );

                    echo json_encode(['success' => true, 'message' => 'تم حذف الجهاز الوهمي']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'الجهاز الوهمي غير موجود']);
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
