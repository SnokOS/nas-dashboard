# Docker Network Configuration Guide
# تكوين شبكة Docker للحصول على IP حقيقي من الراوتر

## طريقة 1: استخدام Macvlan Network

### الخطوة 1: معرفة اسم واجهة الشبكة
```bash
ip link show
# أو
ip addr show
```

### الخطوة 2: إنشاء شبكة Macvlan
```bash
# استبدل القيم التالية بما يناسب شبكتك:
# - 192.168.1.0/24: نطاق الشبكة
# - 192.168.1.1: عنوان البوابة (الراوتر)
# - eth0: اسم واجهة الشبكة

docker network create -d macvlan \
  --subnet=192.168.1.0/24 \
  --gateway=192.168.1.1 \
  -o parent=eth0 \
  macvlan-network
```

### الخطوة 3: إنشاء Container باستخدام Macvlan
```bash
docker run -d \
  --name=my-container \
  --network=macvlan-network \
  --ip=192.168.1.100 \
  ubuntu:latest
```

### التحقق من IP Address
```bash
docker inspect my-container | grep IPAddress
```

## طريقة 2: استخدام Bridge Network مع Port Mapping

### إنشاء شبكة Bridge مخصصة
```bash
docker network create \
  --driver=bridge \
  --subnet=172.18.0.0/16 \
  --gateway=172.18.0.1 \
  my-bridge-network
```

### إنشاء Container مع تحديد IP
```bash
docker run -d \
  --name=my-container \
  --network=my-bridge-network \
  --ip=172.18.0.10 \
  -p 8080:80 \
  nginx:latest
```

## طريقة 3: Host Network Mode

### إنشاء Container باستخدام Host Network
```bash
docker run -d \
  --name=my-container \
  --network=host \
  ubuntu:latest
```

**ملاحظة:** في هذا الوضع، سيستخدم الـ Container نفس IP الخاص بالـ Host.

## إعداد Docker Daemon للعمل مع Macvlan

### تحرير ملف daemon.json
```bash
sudo nano /etc/docker/daemon.json
```

### إضافة التكوين التالي:
```json
{
  "bip": "172.17.0.1/16",
  "default-address-pools": [
    {
      "base": "172.18.0.0/16",
      "size": 24
    }
  ],
  "dns": ["8.8.8.8", "8.8.4.4"]
}
```

### إعادة تشغيل Docker
```bash
sudo systemctl restart docker
```

## أمثلة عملية

### مثال 1: Ubuntu Container مع IP ثابت
```bash
docker network create -d macvlan \
  --subnet=192.168.1.0/24 \
  --gateway=192.168.1.1 \
  -o parent=eth0 \
  nas-network

docker run -d \
  --name=ubuntu-server \
  --network=nas-network \
  --ip=192.168.1.150 \
  --restart=always \
  ubuntu:22.04
```

### مثال 2: Web Server مع IP حقيقي
```bash
docker run -d \
  --name=nginx-server \
  --network=nas-network \
  --ip=192.168.1.151 \
  --restart=always \
  nginx:latest
```

### مثال 3: MySQL Database مع IP حقيقي
```bash
docker run -d \
  --name=mysql-server \
  --network=nas-network \
  --ip=192.168.1.152 \
  --restart=always \
  -e MYSQL_ROOT_PASSWORD=mypassword \
  mysql:8.0
```

## استكشاف الأخطاء

### المشكلة: لا يمكن الوصول إلى Container من الشبكة
**الحل:**
```bash
# تحقق من تكوين الشبكة
docker network inspect macvlan-network

# تحقق من IP Container
docker inspect my-container | grep IPAddress

# اختبار الاتصال
ping 192.168.1.150
```

### المشكلة: تعارض IP Addresses
**الحل:**
```bash
# حذف الشبكة وإعادة إنشائها
docker network rm macvlan-network
docker network create -d macvlan --subnet=192.168.1.0/24 --gateway=192.168.1.1 -o parent=eth0 macvlan-network
```

### المشكلة: Container لا يحصل على IP
**الحل:**
```bash
# تحقق من تكوين DHCP على الراوتر
# تأكد من أن نطاق IP المستخدم لا يتعارض مع DHCP

# أو قم بتحديد IP يدوياً
docker run -d --network=macvlan-network --ip=192.168.1.200 ...
```

## نصائح مهمة

1. **تجنب تعارض IP:**
   - احجز نطاق IP خاص بـ Docker على الراوتر
   - مثال: 192.168.1.150-192.168.1.200

2. **استخدام IP ثابتة:**
   - حدد IP لكل Container بدلاً من DHCP

3. **الأمان:**
   - استخدم Firewall لحماية Containers
   - قم بتكوين Network Policies

4. **الأداء:**
   - Macvlan يوفر أداء أفضل من Bridge
   - Host Network الأسرع لكن الأقل أماناً

## استخدام NAS Dashboard API

يمكنك استخدام API لإنشاء Containers مع Macvlan:

```javascript
// إنشاء Container مع Macvlan
const response = await fetch('/api/docker.php?action=create', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    image_id: 1,
    name: 'my-ubuntu',
    cpu_cores: 2,
    ram_mb: 2048,
    network_mode: 'macvlan-network',
    ip_address: '192.168.1.150'
  })
});
```

## المراجع

- [Docker Networking Documentation](https://docs.docker.com/network/)
- [Macvlan Network Driver](https://docs.docker.com/network/macvlan/)
- [Docker Network Commands](https://docs.docker.com/engine/reference/commandline/network/)
