<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

class UserManagementAPI {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Check if user has admin privileges
    private function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
    
    // Get all users
    public function getUsers() {
        if (!$this->isAdmin()) {
            return ['error' => 'Unauthorized - Admin access required'];
        }
        
        return $this->db->fetchAll(
            "SELECT id, username, full_name, email, role, created_at, last_login, is_active 
             FROM users 
             ORDER BY created_at DESC"
        );
    }
    
    // Get single user
    public function getUser($userId) {
        if (!$this->isAdmin() && $_SESSION['user_id'] != $userId) {
            return ['error' => 'Unauthorized'];
        }
        
        return $this->db->fetchOne(
            "SELECT id, username, full_name, email, role, permissions, created_at, last_login, is_active 
             FROM users 
             WHERE id = ?",
            [$userId]
        );
    }
    
    // Create new user
    public function createUser($data) {
        if (!$this->isAdmin()) {
            return ['success' => false, 'error' => 'Unauthorized - Admin access required'];
        }
        
        try {
            // Check if username already exists
            $existing = $this->db->fetchOne(
                "SELECT id FROM users WHERE username = ?",
                [$data['username']]
            );
            
            if ($existing) {
                return ['success' => false, 'error' => 'Username already exists'];
            }
            
            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
            
            // Prepare permissions
            $permissions = $data['permissions'] ?? [];
            if ($data['role'] === 'admin') {
                $permissions = ['all' => true];
            }
            
            // Insert user
            $this->db->execute(
                "INSERT INTO users (username, password, full_name, email, role, permissions, is_active) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [
                    $data['username'],
                    $hashedPassword,
                    $data['full_name'] ?? '',
                    $data['email'] ?? '',
                    $data['role'] ?? 'user',
                    json_encode($permissions),
                    $data['is_active'] ?? 1
                ]
            );
            
            return [
                'success' => true,
                'user_id' => $this->db->lastInsertId(),
                'message' => 'User created successfully'
            ];
            
        } catch(Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // Update user
    public function updateUser($userId, $data) {
        if (!$this->isAdmin() && $_SESSION['user_id'] != $userId) {
            return ['success' => false, 'error' => 'Unauthorized'];
        }
        
        try {
            $updates = [];
            $values = [];
            
            // Build update query dynamically
            if (isset($data['full_name'])) {
                $updates[] = "full_name = ?";
                $values[] = $data['full_name'];
            }
            
            if (isset($data['email'])) {
                $updates[] = "email = ?";
                $values[] = $data['email'];
            }
            
            if (isset($data['password']) && !empty($data['password'])) {
                $updates[] = "password = ?";
                $values[] = password_hash($data['password'], PASSWORD_BCRYPT);
            }
            
            // Only admin can change these
            if ($this->isAdmin()) {
                if (isset($data['role'])) {
                    $updates[] = "role = ?";
                    $values[] = $data['role'];
                }
                
                if (isset($data['permissions'])) {
                    $updates[] = "permissions = ?";
                    $values[] = json_encode($data['permissions']);
                }
                
                if (isset($data['is_active'])) {
                    $updates[] = "is_active = ?";
                    $values[] = $data['is_active'];
                }
            }
            
            if (empty($updates)) {
                return ['success' => false, 'error' => 'No data to update'];
            }
            
            $values[] = $userId;
            
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
            $this->db->execute($sql, $values);
            
            return ['success' => true, 'message' => 'User updated successfully'];
            
        } catch(Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // Delete user
    public function deleteUser($userId) {
        if (!$this->isAdmin()) {
            return ['success' => false, 'error' => 'Unauthorized - Admin access required'];
        }
        
        if ($userId == $_SESSION['user_id']) {
            return ['success' => false, 'error' => 'Cannot delete your own account'];
        }
        
        try {
            $this->db->execute("DELETE FROM users WHERE id = ?", [$userId]);
            return ['success' => true, 'message' => 'User deleted successfully'];
        } catch(Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // Change user status (active/inactive)
    public function toggleUserStatus($userId) {
        if (!$this->isAdmin()) {
            return ['success' => false, 'error' => 'Unauthorized - Admin access required'];
        }
        
        if ($userId == $_SESSION['user_id']) {
            return ['success' => false, 'error' => 'Cannot deactivate your own account'];
        }
        
        try {
            $this->db->execute(
                "UPDATE users SET is_active = NOT is_active WHERE id = ?",
                [$userId]
            );
            
            return ['success' => true, 'message' => 'User status updated'];
        } catch(Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // Get user activity logs
    public function getUserActivityLogs($userId = null, $limit = 100) {
        if (!$this->isAdmin() && $userId != $_SESSION['user_id']) {
            return ['error' => 'Unauthorized'];
        }
        
        if ($userId) {
            return $this->db->fetchAll(
                "SELECT a.*, u.username 
                 FROM activity_logs a 
                 JOIN users u ON a.user_id = u.id 
                 WHERE a.user_id = ? 
                 ORDER BY a.created_at DESC 
                 LIMIT ?",
                [$userId, $limit]
            );
        } else {
            return $this->db->fetchAll(
                "SELECT a.*, u.username 
                 FROM activity_logs a 
                 JOIN users u ON a.user_id = u.id 
                 ORDER BY a.created_at DESC 
                 LIMIT ?",
                [$limit]
            );
        }
    }
    
    // Get user statistics
    public function getUserStats() {
        if (!$this->isAdmin()) {
            return ['error' => 'Unauthorized - Admin access required'];
        }
        
        $stats = [
            'total_users' => 0,
            'active_users' => 0,
            'admin_users' => 0,
            'recent_logins' => 0
        ];
        
        // Total users
        $result = $this->db->fetchOne("SELECT COUNT(*) as count FROM users");
        $stats['total_users'] = $result['count'];
        
        // Active users
        $result = $this->db->fetchOne("SELECT COUNT(*) as count FROM users WHERE is_active = 1");
        $stats['active_users'] = $result['count'];
        
        // Admin users
        $result = $this->db->fetchOne("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
        $stats['admin_users'] = $result['count'];
        
        // Recent logins (last 24 hours)
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );
        $stats['recent_logins'] = $result['count'];
        
        return $stats;
    }
}

// Handle requests
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized - Please login']);
    exit;
}

$userAPI = new UserManagementAPI();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET') {
    switch($action) {
        case 'list':
            echo json_encode($userAPI->getUsers());
            break;
        case 'get':
            $userId = $_GET['id'] ?? $_SESSION['user_id'];
            echo json_encode($userAPI->getUser($userId));
            break;
        case 'logs':
            $userId = $_GET['user_id'] ?? null;
            $limit = $_GET['limit'] ?? 100;
            echo json_encode($userAPI->getUserActivityLogs($userId, $limit));
            break;
        case 'stats':
            echo json_encode($userAPI->getUserStats());
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch($action) {
        case 'create':
            echo json_encode($userAPI->createUser($data));
            break;
        case 'update':
            $userId = $data['user_id'] ?? $_SESSION['user_id'];
            echo json_encode($userAPI->updateUser($userId, $data));
            break;
        case 'delete':
            echo json_encode($userAPI->deleteUser($data['user_id']));
            break;
        case 'toggle':
            echo json_encode($userAPI->toggleUserStatus($data['user_id']));
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}
?>
