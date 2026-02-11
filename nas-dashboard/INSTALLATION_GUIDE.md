# دليل التثبيت التفصيلي - NAS Dashboard
## خطوة بخطوة

---

## المحتويات
1. [المتطلبات](#المتطلبات)
2. [التثبيت السريع](#التثبيت-السريع)
3. [التثبيت المفصل](#التثبيت-المفصل)
4. [الإعداد الأولي](#الإعداد-الأولي)
5. [استكشاف الأخطاء](#استكشاف-الأخطاء)

---

## المتطلبات

### نظام التشغيل
- Ubuntu 20.04 / 22.04 / 24.04
- Debian 11 / 12
- CentOS 8 / 9
- Fedora 35+
- أي توزيعة Linux أخرى

### المواصفات
- **المعالج:** 2 Core CPU (الموصى به: 4+ Cores)
- **الذاكرة:** 2 GB RAM (الموصى به: 8+ GB)
- **التخزين:** 20 GB (الموصى به: 500+ GB)
- **الشبكة:** اتصال بالإنترنت

---

## التثبيت السريع

### الخطوة 1: تحميل المشروع
```bash
# فك ضغط الملف
tar -xzf nas-dashboard.tar.gz

# الانتقال إلى المجلد
cd nas-dashboard
```

### الخطوة 2: تشغيل التثبيت
```bash
# منح صلاحيات التنفيذ
chmod +x install.sh

# تشغيل التثبيت (يحتاج sudo)
sudo ./install.sh
```

### الخطوة 3: الوصول إلى النظام
```
افتح المتصفح واذهب إلى:
http://YOUR-SERVER-IP/nas-dashboard

بيانات الدخول:
اسم المستخدم: admin
كلمة المرور: admin
```

**✅ انتهى! يمكنك الآن البدء باستخدام النظام**

---

## التثبيت المفصل

إذا كنت تريد التحكم الكامل في عملية التثبيت:

### 1. تحديث النظام

#### Ubuntu/Debian:
```bash
sudo apt-get update
sudo apt-get upgrade -y
```

#### CentOS/RHEL/Fedora:
```bash
sudo yum update -y
# أو
sudo dnf update -y
```

### 2. تثبيت Apache

#### Ubuntu/Debian:
```bash
sudo apt-get install apache2 -y
sudo systemctl enable apache2
sudo systemctl start apache2
```

#### CentOS/RHEL/Fedora:
```bash
sudo yum install httpd -y
sudo systemctl enable httpd
sudo systemctl start httpd
```

### 3. تثبيت PHP

#### Ubuntu/Debian:
```bash
sudo apt-get install php php-mysql php-cli php-curl php-json php-mbstring php-xml libapache2-mod-php -y
```

#### CentOS/RHEL/Fedora:
```bash
sudo yum install php php-mysqlnd php-cli php-curl php-json php-mbstring php-xml -y
```

### 4. تثبيت MariaDB/MySQL

#### Ubuntu/Debian:
```bash
sudo apt-get install mariadb-server mariadb-client -y
sudo systemctl enable mariadb
sudo systemctl start mariadb
```

#### CentOS/RHEL/Fedora:
```bash
sudo yum install mariadb-server mariadb -y
sudo systemctl enable mariadb
sudo systemctl start mariadb
```

#### تأمين قاعدة البيانات:
```bash
sudo mysql_secure_installation
```

### 5. تثبيت Docker

```bash
# تثبيت Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# تشغيل Docker
sudo systemctl enable docker
sudo systemctl start docker

# إضافة المستخدم الحالي لمجموعة docker (اختياري)
sudo usermod -aG docker $USER
```

### 6. تثبيت الخدمات الإضافية

#### Ubuntu/Debian:
```bash
sudo apt-get install samba nfs-kernel-server vsftpd openssh-server -y
```

#### CentOS/RHEL/Fedora:
```bash
sudo yum install samba nfs-utils vsftpd openssh-server -y
```

### 7. إعداد قاعدة البيانات

```bash
# الدخول إلى MySQL
sudo mysql -u root

# في موجه MySQL:
CREATE DATABASE nas_dashboard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'nas_user'@'localhost' IDENTIFIED BY 'YOUR_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON nas_dashboard.* TO 'nas_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# استيراد قاعدة البيانات
sudo mysql -u root nas_dashboard < database.sql
```

### 8. نسخ الملفات

```bash
# إنشاء المجلد
sudo mkdir -p /var/www/html/nas-dashboard

# نسخ الملفات
sudo cp -r * /var/www/html/nas-dashboard/

# تعيين الصلاحيات
sudo chown -R www-data:www-data /var/www/html/nas-dashboard
# أو في CentOS/RHEL:
# sudo chown -R apache:apache /var/www/html/nas-dashboard

sudo chmod -R 755 /var/www/html/nas-dashboard

# إنشاء مجلد التحميلات
sudo mkdir -p /var/www/html/nas-dashboard/uploads
sudo chmod 777 /var/www/html/nas-dashboard/uploads
```

### 9. تعديل ملف config/database.php

```bash
sudo nano /var/www/html/nas-dashboard/config/database.php
```

غير السطور التالية:
```php
define('DB_USER', 'nas_user');
define('DB_PASS', 'YOUR_STRONG_PASSWORD');
```

### 10. إعداد Apache Virtual Host (اختياري)

#### Ubuntu/Debian:
```bash
sudo nano /etc/apache2/sites-available/nas-dashboard.conf
```

#### CentOS/RHEL:
```bash
sudo nano /etc/httpd/conf.d/nas-dashboard.conf
```

أضف المحتوى التالي:
```apache
<VirtualHost *:80>
    ServerName nas.yourdomain.com
    DocumentRoot /var/www/html/nas-dashboard
    
    <Directory /var/www/html/nas-dashboard>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/nas-dashboard-error.log
    CustomLog ${APACHE_LOG_DIR}/nas-dashboard-access.log combined
</VirtualHost>
```

#### Ubuntu/Debian - تفعيل الموقع:
```bash
sudo a2ensite nas-dashboard
sudo a2enmod rewrite
sudo systemctl reload apache2
```

#### CentOS/RHEL:
```bash
sudo systemctl reload httpd
```

### 11. إعداد الجدار الناري

#### UFW (Ubuntu/Debian):
```bash
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 22/tcp
sudo ufw allow 21/tcp
sudo ufw allow 445/tcp
sudo ufw reload
```

#### Firewalld (CentOS/RHEL/Fedora):
```bash
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --permanent --add-service=ssh
sudo firewall-cmd --permanent --add-service=ftp
sudo firewall-cmd --permanent --add-service=samba
sudo firewall-cmd --reload
```

---

## الإعداد الأولي

### 1. تسجيل الدخول الأول
```
URL: http://YOUR-SERVER-IP/nas-dashboard
اسم المستخدم: admin
كلمة المرور: admin
```

### 2. تغيير كلمة المرور
1. اذهب إلى **الإعدادات**
2. اختر **المستخدمون**
3. اختر المستخدم admin
4. غير كلمة المرور

### 3. إعداد الشبكة
1. اذهب إلى **الإعدادات** > **الشبكة**
2. فعّل البروتوكولات المطلوبة
3. تأكد من تطابق المنافذ مع إعدادات الجدار الناري

### 4. إنشاء مستخدمين جدد
1. اذهب إلى **الإعدادات** > **المستخدمون**
2. اضغط **إضافة مستخدم**
3. املأ البيانات المطلوبة
4. حدد الصلاحيات

---

## استكشاف الأخطاء

### مشكلة: لا يمكن الوصول إلى الصفحة

**الحل:**
```bash
# تحقق من حالة Apache
sudo systemctl status apache2
# أو
sudo systemctl status httpd

# إعادة تشغيل Apache
sudo systemctl restart apache2
# أو
sudo systemctl restart httpd

# تحقق من السجلات
sudo tail -f /var/log/apache2/error.log
# أو
sudo tail -f /var/log/httpd/error_log
```

### مشكلة: خطأ في الاتصال بقاعدة البيانات

**الحل:**
```bash
# تحقق من حالة MariaDB
sudo systemctl status mariadb

# إعادة تشغيل MariaDB
sudo systemctl restart mariadb

# اختبار الاتصال
mysql -u nas_user -p nas_dashboard
```

### مشكلة: صلاحيات الملفات

**الحل:**
```bash
# إصلاح الصلاحيات
sudo chown -R www-data:www-data /var/www/html/nas-dashboard
sudo chmod -R 755 /var/www/html/nas-dashboard
sudo chmod -R 777 /var/www/html/nas-dashboard/uploads
```

### مشكلة: Docker لا يعمل

**الحل:**
```bash
# تحقق من حالة Docker
sudo systemctl status docker

# إعادة تشغيل Docker
sudo systemctl restart docker

# اختبار Docker
sudo docker run hello-world
```

### مشكلة: المنافذ مغلقة

**الحل:**
```bash
# تحقق من المنافذ المفتوحة
sudo netstat -tulpn | grep LISTEN

# فتح المنافذ في UFW
sudo ufw allow PORT_NUMBER/tcp

# فتح المنافذ في Firewalld
sudo firewall-cmd --permanent --add-port=PORT_NUMBER/tcp
sudo firewall-cmd --reload
```

---

## نصائح إضافية

### 1. النسخ الاحتياطي
```bash
# نسخ احتياطي لقاعدة البيانات
mysqldump -u root nas_dashboard > backup_$(date +%Y%m%d).sql

# نسخ احتياطي للملفات
tar -czf nas-backup_$(date +%Y%m%d).tar.gz /var/www/html/nas-dashboard
```

### 2. التحديثات
```bash
# تحديث النظام بانتظام
sudo apt-get update && sudo apt-get upgrade -y
# أو
sudo yum update -y
```

### 3. المراقبة
```bash
# مراقبة موارد النظام
htop

# مراقبة استخدام القرص
df -h

# مراقبة الذاكرة
free -h
```

---

## الدعم

للحصول على المساعدة:
1. راجع ملف README.md
2. تحقق من السجلات في `/var/log/`
3. تواصل مع الدعم الفني

---

**تمت بنجاح! 🎉**

نتمنى لك تجربة ممتعة مع NAS Dashboard!
