# NAS Dashboard v2.0 - Enhanced Edition 🚀

## نظام إدارة NAS احترافي ومتطور

![Version](https://img.shields.io/badge/version-2.0.0-blue.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)
![PHP](https://img.shields.io/badge/PHP-7.4+-purple.svg)

---

## 🎉 التحديثات الجديدة في الإصدار 2.0

### ✨ إصلاح مشكلة قاعدة البيانات
- ✅ **إصلاح كامل لمشكلة الاتصال بقاعدة البيانات**
- ✅ إنشاء قاعدة البيانات تلقائياً إذا لم تكن موجودة
- ✅ رسائل خطأ واضحة ومفصلة
- ✅ معالجة أفضل للأخطاء
- ✅ دعم متغيرات البيئة (Environment Variables)

### 🔐 تسجيل الدخول بحساب Google
- ✅ **OAuth 2.0 Integration كامل**
- ✅ تسجيل دخول بضغطة واحدة
- ✅ جلب صورة المستخدم تلقائياً من Google
- ✅ جلب الاسم الكامل والبريد الإلكتروني
- ✅ واجهة تسجيل دخول محسّنة

### 📱 متجر التطبيقات (App Store)
- ✅ **متجر تطبيقات مدمج**
- ✅ مكتبة تطبيقات Casa OS الكاملة
- ✅ تثبيت بضغطة زر واحدة
- ✅ تطبيقات مثبتة مسبقاً:
  - **Jellyfin** - خادم وسائط
  - **Immich** - إدارة الصور (بديل Google Photos)
  - **Nextcloud** - سحابة خاصة
  - **Pi-hole** - حاجب إعلانات
- ✅ +50 تطبيق إضافي متاح

### 📊 Dashboard محسّن مع رسوم بيانية
- ✅ **رسوم بيانية حية ومتحركة**
- ✅ عرض استخدام CPU مع رسم بياني
- ✅ عرض استخدام RAM مع رسم بياني
- ✅ عرض SWAP Usage
- ✅ عرض ZRAM (إن وجد)
- ✅ قراص استخدام Disk مع تفاصيل
- ✅ سرعة الشبكة (Upload/Download)
- ✅ رسوم بيانية بتقنية Chart.js
- ✅ تحديث كل 5 ثوان

### 🎨 تصميم أكثر احترافية
- ✅ **تصميم Material Design 3.0**
- ✅ تأثيرات Glassmorphism
- ✅ انتقالات وتأثيرات سلسة
- ✅ أيقونات متحركة
- ✅ بطاقات بتأثيرات 3D
- ✅ ألوان متدرجة عصرية
- ✅ ظلال ديناميكية

---

## 📦 ما الجديد؟

### 1. متجر التطبيقات

```javascript
// تطبيقات مثبتة مسبقاً:
✓ Jellyfin (Port 8096)
✓ Immich (Port 2283)
✓ Nextcloud (Port 8080)
✓ Pi-hole (Port 8081)

// تطبيقات متاحة للتثبيت:
• Portainer - إدارة Docker
• Home Assistant - المنزل الذكي
• Plex - خادم وسائط
• Transmission - تورنت
• Sonarr - إدارة المسلسلات
• Radarr - إدارة الأفلام
• + 40+ تطبيق آخر
```

### 2. Google OAuth Integration

#### الإعداد:
1. اذهب إلى [Google Cloud Console](https://console.cloud.google.com/)
2. أنشئ مشروع جديد
3. فعّل Google+ API
4. أنشئ OAuth 2.0 credentials
5. أضف redirect URI: `http://your-server/nas-dashboard/api/oauth_callback.php`
6. احفظ Client ID و Client Secret
7. في Dashboard: الإعدادات > OAuth > أدخل البيانات

#### المميزات:
- تسجيل دخول آمن
- صورة المستخدم من Google
- الاسم الكامل تلقائياً
- لا حاجة لتذكر كلمة مرور

### 3. Dashboard المطوّر

#### معلومات النظام:
```
┌─────────────────────────────────┐
│ CPU Usage: 45%  [████████░░]   │
│ RAM: 6.2GB/16GB [████████░░]   │
│ SWAP: 1.2GB/8GB  [███░░░░░░]  │
│ Disk: 450GB/1TB  [█████░░░░░]  │
│ ↓ 125 MB/s  ↑ 45 MB/s          │
└─────────────────────────────────┘
```

#### رسوم بيانية:
- Line Charts للتاريخ
- Gauge Charts للاستخدام الحالي
- Doughnut Charts للتوزيع
- Real-time updates

---

## 🚀 البدء السريع

### التثبيت:

```bash
# 1. فك الضغط
tar -xzf nas-dashboard-v2.0.tar.gz
cd nas-dashboard

# 2. تشغيل التثبيت
chmod +x install.sh
sudo ./install.sh

# 3. افتح المتصفح
http://YOUR-SERVER-IP/nas-dashboard
```

### بيانات الدخول الافتراضية:
```
اسم المستخدم: admin
كلمة المرور: admin
```

⚠️ **هام:** غيّر كلمة المرور فوراً!

---

## 📊 لقطات الشاشة

### صفحة تسجيل الدخول المحسّنة
- تصميم Glassmorphism
- جزيئات متحركة في الخلفية
- زر Google Sign-In
- تأثيرات Ripple

### Dashboard
- 8 بطاقات إحصائيات
- 4 رسوم بيانية حية
- معلومات النظام الكاملة
- تحديث تلقائي

### متجر التطبيقات
- grid layout عصري
- أيقونات التطبيقات
- معلومات كل تطبيق
- زر تثبيت/إلغاء تثبيت
- حالة التطبيق

---

## 🔧 المتطلبات

### الحد الأدنى:
- Ubuntu 20.04+ / Debian 11+
- 2 Core CPU
- 4 GB RAM
- 20 GB Storage
- PHP 7.4+
- MySQL/MariaDB 10.5+
- Docker 20.10+

### الموصى به:
- 4+ Core CPU
- 16+ GB RAM
- 500+ GB Storage
- SSD Storage

---

## 📖 الدليل الكامل

### إعداد Google OAuth:

1. **Google Cloud Console:**
```
1. اذهب إلى console.cloud.google.com
2. New Project → "NAS Dashboard"
3. APIs & Services → Enable APIs
4. Enable: Google+ API
5. Credentials → Create Credentials → OAuth 2.0
6. Application type: Web application
7. Authorized redirect URIs:
   http://your-server/nas-dashboard/api/oauth_callback.php
8. Create → Copy Client ID & Secret
```

2. **في NAS Dashboard:**
```
الإعدادات → OAuth Settings
✓ تفعيل Google Sign-In
Client ID: [paste]
Client Secret: [paste]
Redirect URI: [auto-filled]
حفظ
```

3. **اختبار:**
```
- اذهب إلى صفحة تسجيل الدخول
- اضغط "تسجيل الدخول بحساب Google"
- اختر الحساب
- تم!
```

### تثبيت التطبيقات:

```bash
# من Dashboard:
Applications → App Store → اختر التطبيق → Install

# يدوياً (Docker):
docker run -d \
  --name jellyfin \
  -p 8096:8096 \
  -v /path/to/config:/config \
  -v /path/to/media:/media \
  jellyfin/jellyfin:latest
```

---

## 🎨 التخصيص

### الألوان:
```css
/* css/custom.css */
:root {
    --primary-color: #667eea;
    --secondary-color: #764ba2;
    --accent-color: #f093fb;
    --background: #f5f7fa;
    --surface: #ffffff;
    --text-primary: #2c3e50;
    --text-secondary: #7f8c8d;
}
```

### اللغة:
```php
// config/settings.php
define('DEFAULT_LANGUAGE', 'ar'); // ar, en, fr
```

---

## 🔒 الأمان

### التحسينات الأمنية:
- ✅ CSRF Protection
- ✅ XSS Prevention
- ✅ SQL Injection Protection
- ✅ Rate Limiting
- ✅ Session Security
- ✅ Password Hashing (bcrypt)
- ✅ OAuth 2.0
- ✅ HTTPS Support

---

## 📱 API Documentation

### Endpoints:

```
POST   /api/login.php
POST   /api/logout.php
GET    /api/stats.php
GET    /api/applications.php
POST   /api/applications.php (install/uninstall)
GET    /api/vm.php
POST   /api/vm.php (create/delete/toggle)
GET    /api/oauth_status.php
GET    /api/oauth_redirect.php
GET    /api/oauth_callback.php
```

---

## 🐛 استكشاف الأخطاء

### مشكلة: فشل الاتصال بقاعدة البيانات

**الحل:**
```bash
# تحقق من MariaDB
sudo systemctl status mariadb

# إعادة تشغيل
sudo systemctl restart mariadb

# اختبر الاتصال
mysql -u root -p
```

### مشكلة: Google OAuth لا يعمل

**الحل:**
```
1. تحقق من Redirect URI
2. تأكد من تفعيل Google+ API
3. راجع Client ID & Secret
4. تأكد من domain مطابق
```

---

## 📈 خارطة الطريق

### قريباً:
- [ ] Dark Mode كامل
- [ ] Multi-language (English, French)
- [ ] Mobile Apps (iOS/Android)
- [ ] Advanced Notifications
- [ ] Telegram Bot Integration
- [ ] Backup to Cloud (Google Drive, OneDrive)
- [ ] 2FA Authentication
- [ ] Advanced User Roles
- [ ] API Rate Limiting Dashboard
- [ ] Kubernetes Support

---

## 👥 المساهمة

نرحب بجميع المساهمات!

```bash
# Fork the repo
git clone https://github.com/yourusername/nas-dashboard
cd nas-dashboard
git checkout -b feature/amazing-feature
# Make changes
git commit -m "Add amazing feature"
git push origin feature/amazing-feature
# Create Pull Request
```

---

## 📝 الترخيص

MIT License - مفتوح المصدر ومجاني للجميع!

---

## 🙏 شكر خاص

- Casa OS Team
- Jellyfin Project
- Immich Team
- Nextcloud
- Pi-hole Project
- جميع المساهمين

---

## 📧 الدعم

- 📧 Email: support@nas-dashboard.com
- 💬 Discord: [Join Server]
- 🐛 Issues: [GitHub Issues]
- 📖 Docs: [Documentation]

---

## ⭐ إذا أعجبك المشروع

```
⭐ ضع نجمة على GitHub
📢 شارك مع الأصدقاء
💡 اقترح ميزات
🐛 أبلغ عن المشاكل
```

---

**🎉 استمتع باستخدام NAS Dashboard v2.0!**

*Built with ❤️ for the NAS community*

**الإصدار:** 2.0.0  
**التاريخ:** فبراير 2026  
**الحالة:** مستقر ✅
