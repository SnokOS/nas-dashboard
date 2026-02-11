// NAS Dashboard - Main JavaScript File

// Global variables
let currentSection = 'dashboard';
let userData = null;
let systemData = {};

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    initializeDashboard();
    loadUserData();
    loadSystemStats();
    setupMenuNavigation();
    startAutoRefresh();
});

// Initialize dashboard
function initializeDashboard() {
    console.log('Dashboard initialized');
    checkAuthentication();
}

// Check if user is authenticated
function checkAuthentication() {
    fetch('api/check_auth.php')
        .then(response => response.json())
        .then(data => {
            if (!data.authenticated) {
                window.location.href = 'login.html';
            } else {
                userData = data.user;
                updateUserInfo();
            }
        })
        .catch(error => {
            console.error('Authentication check failed:', error);
        });
}

// Load user data
function loadUserData() {
    fetch('api/user.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                userData = data.user;
                updateUserInfo();
            }
        })
        .catch(error => console.error('Error loading user data:', error));
}

// Update user info in header
function updateUserInfo() {
    if (userData) {
        const userName = document.querySelector('.user-name');
        const userAvatar = document.querySelector('.user-avatar');
        
        if (userName) {
            userName.textContent = userData.username || 'Admin';
        }
        if (userAvatar) {
            userAvatar.textContent = (userData.username || 'A').charAt(0).toUpperCase();
        }
    }
}

// Load system statistics
function loadSystemStats() {
    fetch('api/stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                systemData = data.stats;
                updateDashboardStats();
            }
        })
        .catch(error => console.error('Error loading stats:', error));
}

// Update dashboard statistics
function updateDashboardStats() {
    const stats = systemData;
    
    // Update storage
    const storageUsed = document.getElementById('storage-used');
    if (storageUsed && stats.storage) {
        storageUsed.textContent = stats.storage.used || '0 GB';
    }
    
    // Update VM count
    const vmCount = document.getElementById('vm-count');
    if (vmCount && stats.vm) {
        vmCount.textContent = stats.vm.running || '0';
    }
    
    // Update users count
    const usersCount = document.getElementById('users-count');
    if (usersCount && stats.users) {
        usersCount.textContent = stats.users.active || '0';
    }
    
    // Update network status
    const networkStatus = document.getElementById('network-status');
    if (networkStatus && stats.network) {
        networkStatus.textContent = stats.network.status || 'متصل';
    }
}

// Setup menu navigation
function setupMenuNavigation() {
    const menuItems = document.querySelectorAll('.menu-item');
    
    menuItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all items
            menuItems.forEach(i => i.classList.remove('active'));
            
            // Add active class to clicked item
            this.classList.add('active');
            
            // Get section name
            const section = this.getAttribute('data-section');
            
            // Switch content
            switchSection(section);
        });
    });
}

// Switch between sections
function switchSection(section) {
    currentSection = section;
    
    // Hide all sections
    const sections = document.querySelectorAll('.content-section');
    sections.forEach(s => s.classList.remove('active'));
    
    // Show selected section
    const activeSection = document.getElementById(`section-${section}`);
    if (activeSection) {
        activeSection.classList.add('active');
    }
    
    // Update page title and breadcrumb
    updatePageHeader(section);
    
    // Load section content
    loadSectionContent(section);
}

// Update page header
function updatePageHeader(section) {
    const titles = {
        'dashboard': 'Dashboard',
        'applications': 'التطبيقات',
        'vm': 'الأجهزة الوهمية',
        'folders': 'المجلدات المشتركة',
        'cameras': 'كاميرات المراقبة',
        'settings': 'الإعدادات'
    };
    
    const pageTitle = document.getElementById('pageTitle');
    const breadcrumb = document.getElementById('breadcrumb');
    
    if (pageTitle) {
        pageTitle.textContent = titles[section] || section;
    }
    
    if (breadcrumb) {
        breadcrumb.textContent = `الرئيسية > ${titles[section] || section}`;
    }
}

// Load section content
function loadSectionContent(section) {
    const contentDiv = document.getElementById(`${section}-content`);
    
    if (!contentDiv) return;
    
    // Show loading
    contentDiv.innerHTML = '<div class="loading"><div class="spinner"></div><p>جاري التحميل...</p></div>';
    
    // Load content based on section
    switch(section) {
        case 'applications':
            loadApplications(contentDiv);
            break;
        case 'vm':
            loadVirtualMachines(contentDiv);
            break;
        case 'folders':
            loadSharedFolders(contentDiv);
            break;
        case 'cameras':
            loadCameras(contentDiv);
            break;
        case 'settings':
            loadSettings(contentDiv);
            break;
    }
}

// Load applications
function loadApplications(container) {
    fetch('api/applications.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderApplications(container, data.applications);
            } else {
                container.innerHTML = '<p>خطأ في تحميل التطبيقات</p>';
            }
        })
        .catch(error => {
            console.error('Error loading applications:', error);
            container.innerHTML = '<p>خطأ في الاتصال</p>';
        });
}

// Render applications
function renderApplications(container, apps) {
    let html = '<div class="apps-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">';
    
    apps.forEach(app => {
        html += `
            <div class="app-card" style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); text-align: center;">
                <div style="font-size: 48px; margin-bottom: 15px;">${getAppIcon(app.icon)}</div>
                <h3 style="color: #2c3e50; margin-bottom: 10px;">${app.name}</h3>
                <p style="color: #7f8c8d; font-size: 14px; margin-bottom: 15px;">${app.description}</p>
                <div style="display: flex; gap: 10px; justify-content: center;">
                    <button onclick="toggleApp(${app.id}, '${app.status}')" style="padding: 8px 15px; background: ${app.status === 'running' ? '#e74c3c' : '#27ae60'}; color: white; border: none; border-radius: 6px; cursor: pointer;">
                        ${app.status === 'running' ? 'إيقاف' : 'تشغيل'}
                    </button>
                    <button onclick="openApp('${app.url}')" style="padding: 8px 15px; background: #667eea; color: white; border: none; border-radius: 6px; cursor: pointer;">
                        فتح
                    </button>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

// Get app icon
function getAppIcon(icon) {
    const icons = {
        'folder': '📁',
        'play-circle': '▶️',
        'download': '⬇️',
        'database': '💾',
        'activity': '📊'
    };
    return icons[icon] || '📦';
}

// Load virtual machines
function loadVirtualMachines(container) {
    fetch('api/vm.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderVirtualMachines(container, data.vms, data.images);
            } else {
                container.innerHTML = '<p>خطأ في تحميل الأجهزة الوهمية</p>';
            }
        })
        .catch(error => {
            console.error('Error loading VMs:', error);
            container.innerHTML = '<p>خطأ في الاتصال</p>';
        });
}

// Render virtual machines
function renderVirtualMachines(container, vms, images) {
    let html = `
        <div style="margin-bottom: 20px;">
            <button onclick="showCreateVM()" style="padding: 12px 24px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px;">
                <i class="fas fa-plus"></i> إنشاء جهاز وهمي جديد
            </button>
        </div>
        <div id="create-vm-form" style="display: none; background: #f8f9fa; padding: 25px; border-radius: 12px; margin-bottom: 20px;">
            <!-- VM Creation Form will be added here -->
        </div>
        <div class="vm-list">
    `;
    
    if (vms.length === 0) {
        html += '<p style="text-align: center; color: #7f8c8d; padding: 40px;">لا توجد أجهزة وهمية حتى الآن</p>';
    } else {
        vms.forEach(vm => {
            html += `
                <div class="vm-card" style="background: white; padding: 20px; border-radius: 12px; margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h3 style="color: #2c3e50; margin-bottom: 5px;">${vm.name}</h3>
                            <p style="color: #7f8c8d; font-size: 14px;">${vm.distribution} ${vm.version}</p>
                            <p style="color: #7f8c8d; font-size: 13px; margin-top: 5px;">IP: ${vm.ip_address || 'لم يتم التعيين'}</p>
                        </div>
                        <div style="text-align: left;">
                            <span style="display: inline-block; padding: 6px 12px; background: ${vm.status === 'running' ? '#d4edda' : '#f8d7da'}; color: ${vm.status === 'running' ? '#155724' : '#721c24'}; border-radius: 20px; font-size: 13px; margin-bottom: 10px;">
                                ${vm.status === 'running' ? 'قيد التشغيل' : 'متوقف'}
                            </span>
                            <div style="display: flex; gap: 8px;">
                                <button onclick="toggleVM(${vm.id}, '${vm.status}')" style="padding: 8px 15px; background: ${vm.status === 'running' ? '#e74c3c' : '#27ae60'}; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 13px;">
                                    ${vm.status === 'running' ? 'إيقاف' : 'تشغيل'}
                                </button>
                                <button onclick="deleteVM(${vm.id})" style="padding: 8px 15px; background: #c0392b; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 13px;">
                                    حذف
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
    }
    
    html += '</div>';
    container.innerHTML = html;
}

// Load shared folders
function loadSharedFolders(container) {
    fetch('api/folders.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderSharedFolders(container, data.folders);
            } else {
                container.innerHTML = '<p>خطأ في تحميل المجلدات</p>';
            }
        })
        .catch(error => {
            console.error('Error loading folders:', error);
            container.innerHTML = '<p>خطأ في الاتصال</p>';
        });
}

// Render shared folders
function renderSharedFolders(container, folders) {
    let html = `
        <div style="margin-bottom: 20px;">
            <button onclick="showCreateFolder()" style="padding: 12px 24px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px;">
                <i class="fas fa-plus"></i> إضافة مجلد مشترك
            </button>
        </div>
        <div class="folders-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
    `;
    
    if (folders.length === 0) {
        html += '<p style="text-align: center; color: #7f8c8d; padding: 40px;">لا توجد مجلدات مشتركة</p>';
    } else {
        folders.forEach(folder => {
            html += `
                <div class="folder-card" style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                    <div style="font-size: 48px; margin-bottom: 15px; text-align: center;">📁</div>
                    <h3 style="color: #2c3e50; margin-bottom: 10px; text-align: center;">${folder.name}</h3>
                    <p style="color: #7f8c8d; font-size: 13px; margin-bottom: 10px;">${folder.path}</p>
                    <p style="color: #95a5a6; font-size: 12px; margin-bottom: 15px;">${folder.description || 'لا يوجد وصف'}</p>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="padding: 4px 10px; background: ${folder.public ? '#d4edda' : '#f8d7da'}; color: ${folder.public ? '#155724' : '#721c24'}; border-radius: 15px; font-size: 12px;">
                            ${folder.public ? 'عام' : 'خاص'}
                        </span>
                        <div style="display: flex; gap: 5px;">
                            <button onclick="openFolder(${folder.id})" style="padding: 6px 12px; background: #667eea; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 12px;">فتح</button>
                            <button onclick="shareFolder(${folder.id})" style="padding: 6px 12px; background: #3498db; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 12px;">مشاركة</button>
                            <button onclick="deleteFolder(${folder.id})" style="padding: 6px 12px; background: #e74c3c; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 12px;">حذف</button>
                        </div>
                    </div>
                </div>
            `;
        });
    }
    
    html += '</div>';
    container.innerHTML = html;
}

// Load cameras
function loadCameras(container) {
    fetch('api/cameras.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderCameras(container, data.cameras);
            } else {
                container.innerHTML = '<p>خطأ في تحميل الكاميرات</p>';
            }
        })
        .catch(error => {
            console.error('Error loading cameras:', error);
            container.innerHTML = '<p>خطأ في الاتصال</p>';
        });
}

// Render cameras
function renderCameras(container, cameras) {
    let html = `
        <div style="margin-bottom: 20px;">
            <button onclick="showAddCamera()" style="padding: 12px 24px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px;">
                <i class="fas fa-plus"></i> إضافة كاميرا
            </button>
        </div>
        <div class="cameras-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px;">
    `;
    
    if (cameras.length === 0) {
        html += '<p style="text-align: center; color: #7f8c8d; padding: 40px;">لا توجد كاميرات مراقبة</p>';
    } else {
        cameras.forEach(camera => {
            html += `
                <div class="camera-card" style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                    <div style="background: #ecf0f1; height: 200px; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-bottom: 15px; font-size: 64px;">
                        📹
                    </div>
                    <h3 style="color: #2c3e50; margin-bottom: 8px;">${camera.name}</h3>
                    <p style="color: #7f8c8d; font-size: 13px; margin-bottom: 5px;">📍 ${camera.location || 'غير محدد'}</p>
                    <p style="color: #7f8c8d; font-size: 13px; margin-bottom: 10px;">🌐 ${camera.ip_address}:${camera.port}</p>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <span style="padding: 4px 10px; background: ${camera.status === 'online' ? '#d4edda' : '#f8d7da'}; color: ${camera.status === 'online' ? '#155724' : '#721c24'}; border-radius: 15px; font-size: 12px;">
                            ${camera.status === 'online' ? 'متصلة' : 'غير متصلة'}
                        </span>
                        <span style="padding: 4px 10px; background: ${camera.recording ? '#d4edda' : '#f8d7da'}; color: ${camera.recording ? '#155724' : '#721c24'}; border-radius: 15px; font-size: 12px;">
                            ${camera.recording ? '🔴 تسجيل' : 'غير مسجلة'}
                        </span>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button onclick="viewCamera(${camera.id})" style="flex: 1; padding: 10px; background: #667eea; color: white; border: none; border-radius: 6px; cursor: pointer;">عرض</button>
                        <button onclick="toggleRecording(${camera.id})" style="flex: 1; padding: 10px; background: #e74c3c; color: white; border: none; border-radius: 6px; cursor: pointer;">
                            ${camera.recording ? 'إيقاف التسجيل' : 'بدء التسجيل'}
                        </button>
                    </div>
                </div>
            `;
        });
    }
    
    html += '</div>';
    container.innerHTML = html;
}

// Load settings
function loadSettings(container) {
    fetch('api/settings.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderSettings(container, data.settings, data.network);
            } else {
                container.innerHTML = '<p>خطأ في تحميل الإعدادات</p>';
            }
        })
        .catch(error => {
            console.error('Error loading settings:', error);
            container.innerHTML = '<p>خطأ في الاتصال</p>';
        });
}

// Render settings
function renderSettings(container, settings, network) {
    let html = `
        <div class="settings-tabs" style="margin-bottom: 25px; border-bottom: 2px solid #ecf0f1;">
            <button class="settings-tab active" onclick="switchSettingsTab('general')" style="padding: 12px 24px; background: none; border: none; border-bottom: 3px solid #667eea; color: #667eea; cursor: pointer; font-weight: 600;">
                إعدادات عامة
            </button>
            <button class="settings-tab" onclick="switchSettingsTab('network')" style="padding: 12px 24px; background: none; border: none; border-bottom: 3px solid transparent; color: #7f8c8d; cursor: pointer; font-weight: 600;">
                إعدادات الشبكة
            </button>
            <button class="settings-tab" onclick="switchSettingsTab('users')" style="padding: 12px 24px; background: none; border: none; border-bottom: 3px solid transparent; color: #7f8c8d; cursor: pointer; font-weight: 600;">
                المستخدمون
            </button>
        </div>
        
        <div id="settings-content-area"></div>
    `;
    
    container.innerHTML = html;
    
    // Load general settings by default
    showGeneralSettings(settings);
}

// Show general settings
function showGeneralSettings(settings) {
    const contentArea = document.getElementById('settings-content-area');
    
    let html = `
        <div class="settings-section">
            <h3 style="color: #2c3e50; margin-bottom: 20px;">إعدادات النظام</h3>
            <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
    `;
    
    // System settings
    settings.system.forEach(setting => {
        html += `
            <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #ecf0f1;">
                <label style="display: block; color: #2c3e50; font-weight: 600; margin-bottom: 8px;">${setting.description}</label>
                <input type="${setting.data_type === 'number' ? 'number' : 'text'}" value="${setting.setting_value}" 
                    style="width: 100%; padding: 10px 15px; border: 2px solid #e0e0e0; border-radius: 8px;" 
                    onchange="updateSetting('${setting.category}', '${setting.setting_key}', this.value)">
            </div>
        `;
    });
    
    html += '</div></div>';
    contentArea.innerHTML = html;
}

// Auto-refresh system stats
function startAutoRefresh() {
    setInterval(() => {
        if (currentSection === 'dashboard') {
            loadSystemStats();
        }
    }, 30000); // Refresh every 30 seconds
}

// Toggle sidebar (mobile)
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('active');
}

// Logout
function logout() {
    if (confirm('هل أنت متأكد من تسجيل الخروج؟')) {
        fetch('api/logout.php', { method: 'POST' })
            .then(() => {
                window.location.href = 'login.html';
            })
            .catch(error => {
                console.error('Logout error:', error);
                window.location.href = 'login.html';
            });
    }
}

// Placeholder functions (to be implemented)
function toggleApp(id, status) { alert('تبديل حالة التطبيق: ' + id); }
function openApp(url) { window.open(url, '_blank'); }
function showCreateVM() { alert('إنشاء جهاز وهمي جديد'); }
function toggleVM(id, status) { alert('تبديل حالة VM: ' + id); }
function deleteVM(id) { if(confirm('حذف الجهاز الوهمي؟')) alert('تم الحذف'); }
function showCreateFolder() { alert('إضافة مجلد جديد'); }
function openFolder(id) { alert('فتح المجلد: ' + id); }
function shareFolder(id) { alert('مشاركة المجلد: ' + id); }
function deleteFolder(id) { if(confirm('حذف المجلد؟')) alert('تم الحذف'); }
function showAddCamera() { alert('إضافة كاميرا جديدة'); }
function viewCamera(id) { alert('عرض الكاميرا: ' + id); }
function toggleRecording(id) { alert('تبديل التسجيل للكاميرا: ' + id); }
function switchSettingsTab(tab) { alert('تبديل إلى: ' + tab); }
function updateSetting(category, key, value) { console.log('Update:', category, key, value); }
