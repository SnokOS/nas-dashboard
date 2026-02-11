<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

class NetworkAPI {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Get all network settings
    public function getNetworkSettings() {
        return $this->db->fetchAll(
            "SELECT * FROM network_settings ORDER BY port"
        );
    }
    
    // Update network setting
    public function updateNetworkSetting($id, $data) {
        try {
            $this->db->execute(
                "UPDATE network_settings SET 
                 enabled = ?, 
                 port = ?, 
                 config = ?,
                 updated_at = NOW() 
                 WHERE id = ?",
                [
                    $data['enabled'] ?? 1,
                    $data['port'] ?? 0,
                    json_encode($data['config'] ?? []),
                    $id
                ]
            );
            
            // Apply firewall rules
            $this->applyFirewallRules($data['port'], $data['enabled']);
            
            return ['success' => true, 'message' => 'Network setting updated'];
        } catch(Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // Apply firewall rules
    private function applyFirewallRules($port, $enabled) {
        if ($enabled) {
            // Open port
            exec("sudo ufw allow $port/tcp 2>&1", $output, $return);
            exec("sudo firewall-cmd --permanent --add-port=$port/tcp 2>&1");
            exec("sudo firewall-cmd --reload 2>&1");
        } else {
            // Close port
            exec("sudo ufw delete allow $port/tcp 2>&1");
            exec("sudo firewall-cmd --permanent --remove-port=$port/tcp 2>&1");
            exec("sudo firewall-cmd --reload 2>&1");
        }
    }
    
    // Get system network interfaces
    public function getNetworkInterfaces() {
        $interfaces = [];
        
        // Get network interfaces
        exec("ip -br addr show", $output);
        foreach ($output as $line) {
            if (preg_match('/^(\S+)\s+(\S+)\s+(.+)$/', $line, $matches)) {
                $interfaces[] = [
                    'name' => $matches[1],
                    'state' => $matches[2],
                    'addresses' => $matches[3]
                ];
            }
        }
        
        return $interfaces;
    }
    
    // Get network statistics
    public function getNetworkStats() {
        $stats = [];
        
        // Get interface statistics
        if (file_exists('/proc/net/dev')) {
            $lines = file('/proc/net/dev');
            foreach ($lines as $line) {
                if (strpos($line, ':') !== false) {
                    list($interface, $data) = explode(':', $line);
                    $interface = trim($interface);
                    $values = preg_split('/\s+/', trim($data));
                    
                    if (count($values) >= 16) {
                        $stats[$interface] = [
                            'rx_bytes' => $values[0],
                            'rx_packets' => $values[1],
                            'tx_bytes' => $values[8],
                            'tx_packets' => $values[9]
                        ];
                    }
                }
            }
        }
        
        return $stats;
    }
    
    // Test port connectivity
    public function testPort($host, $port, $timeout = 5) {
        $connection = @fsockopen($host, $port, $errno, $errstr, $timeout);
        
        if ($connection) {
            fclose($connection);
            return ['success' => true, 'message' => 'Port is open'];
        } else {
            return ['success' => false, 'error' => "Port is closed or unreachable: $errstr"];
        }
    }
}

class SettingsAPI {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Get all system settings
    public function getSystemSettings($category = null) {
        if ($category) {
            return $this->db->fetchAll(
                "SELECT * FROM system_settings WHERE category = ? ORDER BY setting_key",
                [$category]
            );
        } else {
            return $this->db->fetchAll(
                "SELECT * FROM system_settings ORDER BY category, setting_key"
            );
        }
    }
    
    // Update system setting
    public function updateSystemSetting($key, $value, $userId) {
        try {
            $this->db->execute(
                "UPDATE system_settings SET 
                 setting_value = ?,
                 updated_by = ?,
                 updated_at = NOW()
                 WHERE setting_key = ?",
                [$value, $userId, $key]
            );
            
            return ['success' => true, 'message' => 'Setting updated'];
        } catch(Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // Get Docker network configuration
    public function getDockerNetworkConfig() {
        $configFile = '/etc/docker/daemon.json';
        
        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
            return $config;
        }
        
        return [];
    }
    
    // Update Docker network configuration
    public function updateDockerNetworkConfig($config) {
        try {
            $configFile = '/etc/docker/daemon.json';
            $currentConfig = $this->getDockerNetworkConfig();
            
            // Merge configurations
            $newConfig = array_merge($currentConfig, $config);
            
            // Write to file
            file_put_contents($configFile, json_encode($newConfig, JSON_PRETTY_PRINT));
            
            // Restart Docker
            exec("sudo systemctl restart docker 2>&1", $output, $return);
            
            if ($return === 0) {
                return ['success' => true, 'message' => 'Docker configuration updated'];
            } else {
                return ['success' => false, 'error' => 'Failed to restart Docker'];
            }
        } catch(Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

// Handle requests
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$type = $_GET['type'] ?? 'network';

if ($type === 'network') {
    $api = new NetworkAPI();
    
    if ($method === 'GET') {
        switch($action) {
            case 'list':
                echo json_encode($api->getNetworkSettings());
                break;
            case 'interfaces':
                echo json_encode($api->getNetworkInterfaces());
                break;
            case 'stats':
                echo json_encode($api->getNetworkStats());
                break;
            default:
                echo json_encode(['error' => 'Invalid action']);
        }
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        switch($action) {
            case 'update':
                echo json_encode($api->updateNetworkSetting($data['id'], $data));
                break;
            case 'test':
                echo json_encode($api->testPort($data['host'], $data['port'], $data['timeout'] ?? 5));
                break;
            default:
                echo json_encode(['error' => 'Invalid action']);
        }
    }
} elseif ($type === 'settings') {
    $api = new SettingsAPI();
    
    if ($method === 'GET') {
        switch($action) {
            case 'list':
                $category = $_GET['category'] ?? null;
                echo json_encode($api->getSystemSettings($category));
                break;
            case 'docker':
                echo json_encode($api->getDockerNetworkConfig());
                break;
            default:
                echo json_encode(['error' => 'Invalid action']);
        }
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        switch($action) {
            case 'update':
                echo json_encode($api->updateSystemSetting($data['key'], $data['value'], $_SESSION['user_id']));
                break;
            case 'docker':
                echo json_encode($api->updateDockerNetworkConfig($data['config']));
                break;
            default:
                echo json_encode(['error' => 'Invalid action']);
        }
    }
}
?>
