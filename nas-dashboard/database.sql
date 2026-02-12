-- NAS Dashboard - Enhanced Database
CREATE DATABASE IF NOT EXISTS nas_dashboard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nas_dashboard;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    full_name VARCHAR(150),
    profile_picture TEXT,
    role ENUM('admin', 'user', 'guest') DEFAULT 'user',
    permissions JSON,
    oauth_provider ENUM('local', 'google', 'github') DEFAULT 'local',
    oauth_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    active BOOLEAN DEFAULT TRUE,
    language VARCHAR(10) DEFAULT 'ar',
    INDEX idx_email (email),
    INDEX idx_oauth (oauth_provider, oauth_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Applications Table (متجر التطبيقات)
CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(255),
    category VARCHAR(50),
    github_url TEXT,
    docker_image VARCHAR(255),
    version VARCHAR(50),
    installed BOOLEAN DEFAULT FALSE,
    status ENUM('running', 'stopped', 'installing', 'error') DEFAULT 'stopped',
    port INT,
    web_ui_url VARCHAR(255),
    config JSON,
    install_script TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_installed (installed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Virtual Machines Table
CREATE TABLE IF NOT EXISTS virtual_machines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    os_type ENUM('linux', 'windows') NOT NULL,
    distribution VARCHAR(50),
    version VARCHAR(50),
    docker_image VARCHAR(255),
    container_id VARCHAR(100),
    ip_address VARCHAR(45),
    mac_address VARCHAR(17),
    cpu_cores INT DEFAULT 1,
    ram_mb INT DEFAULT 1024,
    disk_gb INT DEFAULT 20,
    status ENUM('running', 'stopped', 'paused', 'error') DEFAULT 'stopped',
    autostart BOOLEAN DEFAULT FALSE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Shared Folders Table
CREATE TABLE IF NOT EXISTS shared_folders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    path VARCHAR(500) NOT NULL,
    description TEXT,
    public BOOLEAN DEFAULT FALSE,
    password VARCHAR(255),
    max_size_gb INT DEFAULT 0,
    used_size_gb DECIMAL(10,2) DEFAULT 0,
    protocols JSON,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_public (public)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Folder Permissions Table
CREATE TABLE IF NOT EXISTS folder_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    folder_id INT NOT NULL,
    user_id INT NOT NULL,
    can_read BOOLEAN DEFAULT TRUE,
    can_write BOOLEAN DEFAULT FALSE,
    can_delete BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (folder_id) REFERENCES shared_folders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_permission (folder_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- IP Cameras Table
CREATE TABLE IF NOT EXISTS cameras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    port INT DEFAULT 554,
    protocol ENUM('rtsp', 'http', 'onvif') DEFAULT 'rtsp',
    username VARCHAR(100),
    password VARCHAR(255),
    stream_url TEXT,
    recording BOOLEAN DEFAULT FALSE,
    recording_path VARCHAR(500),
    status ENUM('online', 'offline', 'error') DEFAULT 'offline',
    location VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_check TIMESTAMP NULL,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Settings Table
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(50) NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    data_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_setting (category, setting_key),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Network Settings Table
CREATE TABLE IF NOT EXISTS network_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    protocol VARCHAR(20) NOT NULL,
    port INT NOT NULL,
    enabled BOOLEAN DEFAULT TRUE,
    description TEXT,
    UNIQUE KEY unique_protocol (protocol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Docker Images Table
CREATE TABLE IF NOT EXISTS docker_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image_name VARCHAR(200) NOT NULL,
    tag VARCHAR(50) DEFAULT 'latest',
    os_type ENUM('linux', 'windows'),
    distribution VARCHAR(50),
    size_mb INT,
    downloaded BOOLEAN DEFAULT FALSE,
    download_date TIMESTAMP NULL,
    UNIQUE KEY unique_image (image_name, tag)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Activity Logs Table
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    category VARCHAR(50),
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_created_at (created_at),
    INDEX idx_user_action (user_id, action),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- System Stats History Table (للرسوم البيانية)
CREATE TABLE IF NOT EXISTS system_stats_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cpu_usage DECIMAL(5,2),
    ram_usage DECIMAL(5,2),
    swap_usage DECIMAL(5,2),
    disk_usage DECIMAL(5,2),
    network_in_mb DECIMAL(10,2),
    network_out_mb DECIMAL(10,2),
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_recorded_at (recorded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user (password: admin)
INSERT INTO users (username, password, email, full_name, role, permissions, oauth_provider) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@nas.local', 'Administrator', 'admin', 
'{"dashboard": true, "applications": true, "vm": true, "folders": true, "settings": true, "cameras": true}', 'local');

-- Insert pre-installed applications
INSERT INTO applications (name, description, icon, category, docker_image, version, installed, status, port, web_ui_url, github_url) VALUES
('Jellyfin', 'خادم وسائط مفتوح المصدر لإدارة ومشاهدة الأفلام والمسلسلات', 'https://raw.githubusercontent.com/jellyfin/jellyfin-ux/master/branding/SVG/icon-transparent.svg', 'media', 'jellyfin/jellyfin:latest', '10.8.13', TRUE, 'running', 8096, 'http://localhost:8096', 'https://github.com/jellyfin/jellyfin'),
('Immich', 'إدارة الصور والفيديوهات بديل Google Photos', 'https://immich.app/img/immich-logo.svg', 'photos', 'ghcr.io/immich-app/immich-server:release', 'v1.95', TRUE, 'running', 2283, 'http://localhost:2283', 'https://github.com/immich-app/immich'),
('Nextcloud', 'سحابة خاصة كاملة لمشاركة الملفات والتعاون', 'https://raw.githubusercontent.com/nextcloud/server/master/core/img/logo/logo.svg', 'storage', 'nextcloud:latest', '28.0', TRUE, 'running', 8080, 'http://localhost:8080', 'https://github.com/nextcloud/server'),
('Pi-hole', 'حاجب الإعلانات على مستوى الشبكة', 'https://raw.githubusercontent.com/pi-hole/graphics/master/Vortex/Vortex_Vertical_wordmark.svg', 'network', 'pihole/pihole:latest', '2024.01', TRUE, 'running', 8081, 'http://localhost:8081/admin', 'https://github.com/pi-hole/pi-hole'),
('Portainer', 'إدارة Docker بواجهة رسومية', 'https://www.portainer.io/hubfs/portainer-logo-black.svg', 'management', 'portainer/portainer-ce:latest', '2.19', FALSE, 'stopped', 9000, 'http://localhost:9000', 'https://github.com/portainer/portainer'),
('Home Assistant', 'منصة أتمتة المنزل الذكي', 'https://brands.home-assistant.io/homeassistant/icon.png', 'automation', 'homeassistant/home-assistant:latest', '2024.1', FALSE, 'stopped', 8123, 'http://localhost:8123', 'https://github.com/home-assistant/core'),
('Plex', 'خادم وسائط متقدم', 'https://www.plex.tv/wp-content/themes/plex/assets/img/plex-logo.svg', 'media', 'plexinc/pms-docker:latest', '1.40', FALSE, 'stopped', 32400, 'http://localhost:32400', 'https://www.plex.tv'),
('Transmission', 'عميل تورنت خفيف', 'https://transmissionbt.com/images/favicon.png', 'downloads', 'linuxserver/transmission:latest', '4.0', FALSE, 'stopped', 9091, 'http://localhost:9091', 'https://github.com/transmission/transmission'),
('Sonarr', 'إدارة المسلسلات التلفزيونية', 'https://raw.githubusercontent.com/Sonarr/Sonarr/develop/Logo/128.png', 'media', 'linuxserver/sonarr:latest', '4.0', FALSE, 'stopped', 8989, 'http://localhost:8989', 'https://github.com/Sonarr/Sonarr'),
('Radarr', 'إدارة الأفلام', 'https://raw.githubusercontent.com/Radarr/Radarr/develop/Logo/128.png', 'media', 'linuxserver/radarr:latest', '5.2', FALSE, 'stopped', 7878, 'http://localhost:7878', 'https://github.com/Radarr/Radarr');

-- Network settings
INSERT INTO network_settings (protocol, port, enabled, description) VALUES
('SSH', 22, TRUE, 'Secure Shell Protocol'),
('FTP', 21, TRUE, 'File Transfer Protocol'),
('SFTP', 22, TRUE, 'SSH File Transfer Protocol'),
('HTTP', 80, TRUE, 'Hypertext Transfer Protocol'),
('HTTPS', 443, TRUE, 'HTTP Secure'),
('SMB', 445, TRUE, 'Server Message Block'),
('NFS', 2049, FALSE, 'Network File System'),
('RTSP', 554, TRUE, 'Real Time Streaming Protocol'),
('WebDAV', 8080, FALSE, 'Web Distributed Authoring and Versioning'),
('MYSQL', 3306, TRUE, 'MySQL Database'),
('REDIS', 6379, FALSE, 'Redis Cache'),
('DOCKER', 2375, TRUE, 'Docker API');

-- System settings
INSERT INTO settings (category, setting_key, setting_value, data_type, description) VALUES
('system', 'hostname', 'NAS-Server', 'string', 'اسم السيرفر'),
('system', 'timezone', 'Asia/Riyadh', 'string', 'المنطقة الزمنية'),
('system', 'language', 'ar', 'string', 'اللغة الافتراضية'),
('oauth', 'google_enabled', 'false', 'boolean', 'تفعيل تسجيل الدخول بـ Google'),
('oauth', 'google_client_id', '', 'string', 'Google Client ID'),
('oauth', 'google_client_secret', '', 'string', 'Google Client Secret'),
('oauth', 'google_redirect_uri', '', 'string', 'Google Redirect URI'),
('network', 'dhcp_enabled', 'true', 'boolean', 'تفعيل DHCP'),
('storage', 'max_upload_size', '2048', 'number', 'الحد الأقصى لرفع الملفات (MB)'),
('security', 'session_timeout', '3600', 'number', 'مدة الجلسة (ثانية)'),
('docker', 'auto_update', 'false', 'boolean', 'التحديث التلقائي للصور');

-- Docker images for VMs
INSERT INTO docker_images (image_name, tag, os_type, distribution, size_mb) VALUES
('ubuntu', 'latest', 'linux', 'Ubuntu', 77),
('ubuntu', '24.04', 'linux', 'Ubuntu', 77),
('debian', 'latest', 'linux', 'Debian', 124),
('fedora', 'latest', 'linux', 'Fedora', 194),
('archlinux', 'latest', 'linux', 'Arch Linux', 400),
('alpine', 'latest', 'linux', 'Alpine', 7);
