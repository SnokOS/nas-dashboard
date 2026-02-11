#!/bin/bash

#########################################
# NAS Dashboard Installation Script
# تثبيت لوحة تحكم NAS Dashboard
#########################################

echo "======================================"
echo "  NAS Dashboard - Installation"
echo "  تثبيت لوحة تحكم NAS Dashboard"
echo "======================================"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if script is run as root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}يرجى تشغيل السكريبت كمستخدم root${NC}"
    echo "استخدم: sudo ./install.sh"
    exit 1
fi

echo -e "${GREEN}[1/10]${NC} التحقق من النظام..."

# Detect OS
if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS=$NAME
    VER=$VERSION_ID
else
    echo -e "${RED}لا يمكن تحديد نظام التشغيل${NC}"
    exit 1
fi

echo "نظام التشغيل: $OS $VER"

# Update system
echo -e "${GREEN}[2/10]${NC} تحديث النظام..."
if command -v apt-get &> /dev/null; then
    apt-get update -qq
    PKG_MANAGER="apt-get"
elif command -v yum &> /dev/null; then
    yum update -y -q
    PKG_MANAGER="yum"
else
    echo -e "${RED}مدير الحزم غير مدعوم${NC}"
    exit 1
fi

# Install Apache/Nginx
echo -e "${GREEN}[3/10]${NC} تثبيت خادم الويب..."
if ! command -v apache2 &> /dev/null && ! command -v nginx &> /dev/null; then
    if [ "$PKG_MANAGER" = "apt-get" ]; then
        apt-get install -y apache2 > /dev/null 2>&1
        systemctl enable apache2
        systemctl start apache2
    else
        yum install -y httpd > /dev/null 2>&1
        systemctl enable httpd
        systemctl start httpd
    fi
    echo -e "${GREEN}✓${NC} تم تثبيت Apache"
else
    echo -e "${GREEN}✓${NC} خادم الويب مثبت مسبقاً"
fi

# Install PHP
echo -e "${GREEN}[4/10]${NC} تثبيت PHP..."
if ! command -v php &> /dev/null; then
    if [ "$PKG_MANAGER" = "apt-get" ]; then
        apt-get install -y php php-mysql php-cli php-curl php-json php-mbstring php-xml > /dev/null 2>&1
    else
        yum install -y php php-mysqlnd php-cli php-curl php-json php-mbstring php-xml > /dev/null 2>&1
    fi
    echo -e "${GREEN}✓${NC} تم تثبيت PHP"
else
    PHP_VERSION=$(php -v | head -n 1 | cut -d " " -f 2 | cut -d "." -f 1,2)
    echo -e "${GREEN}✓${NC} PHP $PHP_VERSION مثبت مسبقاً"
fi

# Install MySQL/MariaDB
echo -e "${GREEN}[5/10]${NC} تثبيت قاعدة البيانات..."
if ! command -v mysql &> /dev/null; then
    if [ "$PKG_MANAGER" = "apt-get" ]; then
        apt-get install -y mariadb-server mariadb-client > /dev/null 2>&1
    else
        yum install -y mariadb-server mariadb > /dev/null 2>&1
    fi
    systemctl enable mariadb
    systemctl start mariadb
    echo -e "${GREEN}✓${NC} تم تثبيت MariaDB"
else
    echo -e "${GREEN}✓${NC} قاعدة البيانات مثبتة مسبقاً"
fi

# Install Docker
echo -e "${GREEN}[6/10]${NC} تثبيت Docker..."
if ! command -v docker &> /dev/null; then
    curl -fsSL https://get.docker.com -o get-docker.sh > /dev/null 2>&1
    sh get-docker.sh > /dev/null 2>&1
    systemctl enable docker
    systemctl start docker
    rm get-docker.sh
    echo -e "${GREEN}✓${NC} تم تثبيت Docker"
else
    DOCKER_VERSION=$(docker --version | cut -d " " -f 3 | cut -d "," -f 1)
    echo -e "${GREEN}✓${NC} Docker $DOCKER_VERSION مثبت مسبقاً"
fi

# Install additional tools
echo -e "${GREEN}[7/10]${NC} تثبيت الأدوات الإضافية..."
if [ "$PKG_MANAGER" = "apt-get" ]; then
    apt-get install -y samba nfs-kernel-server vsftpd openssh-server > /dev/null 2>&1
else
    yum install -y samba nfs-utils vsftpd openssh-server > /dev/null 2>&1
fi
echo -e "${GREEN}✓${NC} تم تثبيت الأدوات الإضافية"

# Setup database
echo -e "${GREEN}[8/10]${NC} إعداد قاعدة البيانات..."
mysql -u root << EOF > /dev/null 2>&1
CREATE DATABASE IF NOT EXISTS nas_dashboard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'nas_user'@'localhost' IDENTIFIED BY 'nas_password';
GRANT ALL PRIVILEGES ON nas_dashboard.* TO 'nas_user'@'localhost';
FLUSH PRIVILEGES;
EOF

# Import database
if [ -f "database.sql" ]; then
    mysql -u root nas_dashboard < database.sql > /dev/null 2>&1
    echo -e "${GREEN}✓${NC} تم استيراد قاعدة البيانات"
else
    echo -e "${YELLOW}⚠${NC} ملف قاعدة البيانات غير موجود"
fi

# Copy files to web root
echo -e "${GREEN}[9/10]${NC} نسخ الملفات..."
WEB_ROOT="/var/www/html/nas-dashboard"
mkdir -p $WEB_ROOT

# Copy all files
cp -r * $WEB_ROOT/ 2>/dev/null

# Set permissions
chown -R www-data:www-data $WEB_ROOT 2>/dev/null || chown -R apache:apache $WEB_ROOT 2>/dev/null
chmod -R 755 $WEB_ROOT
chmod -R 777 $WEB_ROOT/uploads 2>/dev/null || mkdir -p $WEB_ROOT/uploads && chmod 777 $WEB_ROOT/uploads

echo -e "${GREEN}✓${NC} تم نسخ الملفات"

# Configure Apache/Nginx
echo -e "${GREEN}[10/10]${NC} إعداد خادم الويب..."

# Create Apache virtual host
if command -v apache2 &> /dev/null || command -v httpd &> /dev/null; then
    VHOST_FILE="/etc/apache2/sites-available/nas-dashboard.conf"
    [ -d "/etc/httpd/conf.d" ] && VHOST_FILE="/etc/httpd/conf.d/nas-dashboard.conf"
    
    cat > $VHOST_FILE << 'EOFVHOST'
<VirtualHost *:80>
    ServerName nas-dashboard.local
    DocumentRoot /var/www/html/nas-dashboard
    
    <Directory /var/www/html/nas-dashboard>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/nas-dashboard-error.log
    CustomLog ${APACHE_LOG_DIR}/nas-dashboard-access.log combined
</VirtualHost>
EOFVHOST

    # Enable site
    if command -v a2ensite &> /dev/null; then
        a2ensite nas-dashboard > /dev/null 2>&1
        a2enmod rewrite > /dev/null 2>&1
        systemctl reload apache2
    else
        systemctl reload httpd
    fi
fi

echo -e "${GREEN}✓${NC} تم إعداد خادم الويب"

# Get server IP
SERVER_IP=$(hostname -I | awk '{print $1}')

# Create shares directory
mkdir -p /mnt/shares
chmod 755 /mnt/shares

# Configure firewall (if exists)
if command -v ufw &> /dev/null; then
    ufw allow 80/tcp > /dev/null 2>&1
    ufw allow 443/tcp > /dev/null 2>&1
    ufw allow 22/tcp > /dev/null 2>&1
    ufw allow 21/tcp > /dev/null 2>&1
    ufw allow 445/tcp > /dev/null 2>&1
fi

if command -v firewall-cmd &> /dev/null; then
    firewall-cmd --permanent --add-service=http > /dev/null 2>&1
    firewall-cmd --permanent --add-service=https > /dev/null 2>&1
    firewall-cmd --permanent --add-service=ssh > /dev/null 2>&1
    firewall-cmd --permanent --add-service=ftp > /dev/null 2>&1
    firewall-cmd --permanent --add-service=samba > /dev/null 2>&1
    firewall-cmd --reload > /dev/null 2>&1
fi

# Print success message
echo ""
echo "======================================"
echo -e "${GREEN}التثبيت اكتمل بنجاح!${NC}"
echo "======================================"
echo ""
echo -e "يمكنك الوصول إلى لوحة التحكم من خلال:"
echo -e "${YELLOW}http://$SERVER_IP/nas-dashboard${NC}"
echo -e "${YELLOW}http://nas-dashboard.local${NC} (بعد إضافة DNS المحلي)"
echo ""
echo "بيانات تسجيل الدخول الافتراضية:"
echo -e "اسم المستخدم: ${GREEN}admin${NC}"
echo -e "كلمة المرور: ${GREEN}admin${NC}"
echo ""
echo -e "${YELLOW}⚠ تحذير: يرجى تغيير كلمة المرور الافتراضية فوراً!${NC}"
echo ""
echo "الخدمات المثبتة:"
echo "  - Apache/Nginx (خادم الويب)"
echo "  - PHP (محرك PHP)"
echo "  - MariaDB (قاعدة البيانات)"
echo "  - Docker (الحاويات)"
echo "  - Samba (مشاركة الملفات)"
echo "  - FTP (نقل الملفات)"
echo "  - SSH (الوصول الآمن)"
echo ""
echo "للمزيد من المعلومات، راجع ملف README.md"
echo "======================================"
