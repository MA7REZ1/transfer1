document.addEventListener('DOMContentLoaded', function() {
    // العناصر الأساسية
    const sidebar = document.querySelector('.sidebar');
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    let overlay = document.querySelector('.sidebar-overlay');
    const mainContent = document.querySelector('.main-content');
    const isRTL = document.dir === 'rtl' || document.documentElement.getAttribute('dir') === 'rtl';
    
    // إنشاء الأوفرلاي إذا لم يكن موجوداً
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
    }
    
    // التأكد من إخفاء السايدبار في البداية على الموبايل
    function initializeSidebar() {
        if (window.innerWidth <= 992) {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
            document.body.style.overflow = '';
        }
    }

    // تنفيذ عند تحميل الصفحة
    initializeSidebar();

    // تفعيل زر القائمة في الموبايل
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            toggleSidebar();
        });
    }

    // دالة تبديل السايدبار
    function toggleSidebar() {
        sidebar.classList.toggle('show');
        overlay.classList.toggle('show');
        document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
    }

    // إغلاق عند النقر على الأوفرلاي
    overlay.addEventListener('click', function() {
        if (window.innerWidth <= 992) {
            toggleSidebar();
        }
    });

    // إغلاق عند الضغط على ESC في الموبايل
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && window.innerWidth <= 992 && sidebar.classList.contains('show')) {
            toggleSidebar();
        }
    });
    
    // إغلاق السايدبار عند تغيير حجم النافذة للشاشات الكبيرة
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 992 && sidebar.classList.contains('show')) {
            toggleSidebar();
        }
    });

    // إغلاق السايدبار عند النقر على الروابط في الموبايل فقط
    if (sidebar) {
        const links = sidebar.querySelectorAll('a');
        links.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 992) {
                    setTimeout(toggleSidebar, 150);
                }
            });
        });
    }
    
    // معالجة تغيير حجم النافذة
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            initializeSidebar();
        }, 250);
    });
}); 