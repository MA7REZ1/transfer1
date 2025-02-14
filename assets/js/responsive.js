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
            sidebar?.classList.remove('show');
            overlay?.classList.remove('show');
            document.body.style.overflow = '';
            mainContent.style.marginLeft = '0';
            if (isRTL) {
                mainContent.style.marginRight = '0';
            }
        } else {
            mainContent.style.marginLeft = isRTL ? '0' : '280px';
            mainContent.style.marginRight = isRTL ? '280px' : '0';
            sidebar?.classList.remove('show');
            overlay?.classList.remove('show');
        }
    }

    // تنفيذ عند تحميل الصفحة
    initializeSidebar();

    // تفعيل زر القائمة في الموبايل
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebar();
        });
    }

    // دالة تبديل السايدبار
    function toggleSidebar() {
        if (!sidebar) return;
        
        const isShowing = !sidebar.classList.contains('show');
        
        sidebar.classList.toggle('show');
        overlay.classList.toggle('show');
        
        // منع التمرير عند فتح السايدبار
        document.body.style.overflow = isShowing ? 'hidden' : '';
        
        // تحديث هوامش المحتوى الرئيسي
        if (window.innerWidth <= 992) {
            mainContent.style.marginLeft = '0';
            mainContent.style.marginRight = '0';
        }
    }

    // إغلاق عند النقر على الأوفرلاي
    overlay.addEventListener('click', function(e) {
        e.preventDefault();
        if (window.innerWidth <= 992 && sidebar?.classList.contains('show')) {
            toggleSidebar();
        }
    });

    // إغلاق عند النقر خارج السايدبار
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 992 && 
            sidebar?.classList.contains('show') && 
            !sidebar.contains(e.target) && 
            !mobileMenuToggle.contains(e.target)) {
            toggleSidebar();
        }
    });

    // إغلاق عند الضغط على ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar?.classList.contains('show')) {
            toggleSidebar();
        }
    });
    
    // معالجة تغيير حجم النافذة
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            initializeSidebar();
        }, 250);
    });

    // إغلاق السايدبار عند النقر على الروابط في الموبايل
    if (sidebar) {
        const links = sidebar.querySelectorAll('a');
        links.forEach(link => {
            link.addEventListener('click', function(e) {
                if (window.innerWidth <= 992) {
                    // تأخير قصير قبل إغلاق السايدبار للسماح بالانتقال
                    setTimeout(() => {
                        toggleSidebar();
                    }, 150);
                }
            });
        });
    }
}); 