-- قاعدة بيانات NAS Dashboard
CREATE DATABASE IF NOT EXISTS nas_dashboard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nas_dashboard;

-- جدول المستخدمين
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin', 'user', 'guest') DEFAULT 'user',
    permissions JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    active BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- جدول الأجهزة الوهمية
CREATE TABLE IF NOT EXISTS virtual_machines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    os_type ENUM('linux', 'windows') NOT NULL,
    distribution VARCHAR(50),
    version VARCHAR(50),
    docker_image VARCHAR(255),
    ip_address VARCHAR(45),
    mac_address VARCHAR(17),
    cpu_cores INT DEFAULT 1,
    ram_mb INT DEFAULT 1024,
    disk_gb INT DEFAULT 20,
    status ENUM('running', 'stopped', 'paused') DEFAULT 'stopped',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- جدول المجلدات المشتركة
CREATE TABLE IF NOT EXISTS shared_folders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    path VARCHAR(500) NOT NULL,
    description TEXT,
    public BOOLEAN DEFAULT FALSE,
    password VARCHAR(255),
    max_size_gb INT DEFAULT 0,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- جدول صلاحيات المجلدات
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

-- جدول الكاميرات
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
    status ENUM('online', 'offline') DEFAULT 'offline',
    location VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- جدول الإعدادات
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(50) NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    data_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_setting (category, setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- جدول إعدادات الشبكة
CREATE TABLE IF NOT EXISTS network_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    protocol VARCHAR(20) NOT NULL,
    port INT NOT NULL,
    enabled BOOLEAN DEFAULT TRUE,
    description TEXT,
    UNIQUE KEY unique_protocol (protocol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- جدول Docker Images
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

-- جدول التطبيقات
CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(100),
    url VARCHAR(255),
    installed BOOLEAN DEFAULT FALSE,
    version VARCHAR(50),
    status ENUM('running', 'stopped') DEFAULT 'stopped'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- جدول السجلات
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
    INDEX idx_user_action (user_id, action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- إدراج المستخدم الافتراضي (admin/admin)
INSERT INTO users (username, password, email, role, permissions) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@nas.local', 'admin', 
'{"dashboard": true, "applications": true, "vm": true, "folders": true, "settings": true, "cameras": true}');

-- إعدادات الشبكة الافتراضية
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

-- إعدادات النظام الافتراضية
INSERT INTO settings (category, setting_key, setting_value, data_type, description) VALUES
('system', 'hostname', 'NAS-Server', 'string', 'اسم السيرفر'),
('system', 'timezone', 'Asia/Riyadh', 'string', 'المنطقة الزمنية'),
('system', 'language', 'ar', 'string', 'اللغة الافتراضية'),
('network', 'dhcp_enabled', 'true', 'boolean', 'تفعيل DHCP'),
('network', 'static_ip', '', 'string', 'عنوان IP ثابت'),
('network', 'subnet_mask', '255.255.255.0', 'string', 'Subnet Mask'),
('network', 'gateway', '', 'string', 'البوابة الافتراضية'),
('network', 'dns_primary', '8.8.8.8', 'string', 'DNS الأساسي'),
('network', 'dns_secondary', '8.8.4.4', 'string', 'DNS الثانوي'),
('storage', 'max_upload_size', '1024', 'number', 'الحد الأقصى لرفع الملفات (MB)'),
('storage', 'recycle_bin_enabled', 'true', 'boolean', 'تفعيل سلة المحذوفات'),
('storage', 'recycle_bin_days', '30', 'number', 'مدة الاحتفاظ في سلة المحذوفات'),
('security', 'session_timeout', '3600', 'number', 'مدة الجلسة (ثانية)'),
('security', 'max_login_attempts', '5', 'number', 'عدد محاولات تسجيل الدخول'),
('security', 'auto_logout', 'true', 'boolean', 'تسجيل الخروج التلقائي'),
('docker', 'auto_update', 'false', 'boolean', 'التحديث التلقائي للصور'),
('docker', 'registry', 'https://hub.docker.com', 'string', 'Docker Registry');

-- صور Docker للأنظمة المختلفة
INSERT INTO docker_images (image_name, tag, os_type, distribution, size_mb) VALUES
('ubuntu', 'latest', 'linux', 'Ubuntu', 77),
('ubuntu', '24.04', 'linux', 'Ubuntu', 77),
('ubuntu', '22.04', 'linux', 'Ubuntu', 77),
('ubuntu', '20.04', 'linux', 'Ubuntu', 72),
('debian', 'latest', 'linux', 'Debian', 124),
('debian', 'bookworm', 'linux', 'Debian', 124),
('debian', 'bullseye', 'linux', 'Debian', 124),
('fedora', 'latest', 'linux', 'Fedora', 194),
('fedora', '39', 'linux', 'Fedora', 194),
('archlinux', 'latest', 'linux', 'Arch Linux', 400),
('archlinux', 'base', 'linux', 'Arch Linux', 400),
('manjarolinux/base', 'latest', 'linux', 'Manjaro', 450),
('centos', 'latest', 'linux', 'CentOS', 231),
('centos', '7', 'linux', 'CentOS', 204),
('alpine', 'latest', 'linux', 'Alpine', 7),
('kalilinux/kali-rolling', 'latest', 'linux', 'Kali Linux', 126),
('opensuse/leap', 'latest', 'linux', 'openSUSE', 104),
('mcr.microsoft.com/windows', 'ltsc2022', 'windows', 'Windows Server 2022', 4500),
('mcr.microsoft.com/windows', 'ltsc2019', 'windows', 'Windows Server 2019', 4200),
('mcr.microsoft.com/windows/servercore', 'ltsc2022', 'windows', 'Windows Server Core 2022', 2800),
('mcr.microsoft.com/windows/nanoserver', 'ltsc2022', 'windows', 'Windows Nano Server', 300);

-- التطبيقات الافتراضية
INSERT INTO applications (name, description, icon, url, installed, status) VALUES
('File Manager', 'إدارة الملفات', 'folder', '/filemanager', TRUE, 'running'),
('Media Server', 'خادم الوسائط', 'play-circle', '/media', FALSE, 'stopped'),
('Download Manager', 'مدير التحميلات', 'download', '/downloads', FALSE, 'stopped'),
('Backup Manager', 'إدارة النسخ الاحتياطي', 'database', '/backup', TRUE, 'running'),
('Network Monitor', 'مراقبة الشبكة', 'activity', '/network', TRUE, 'running');
