document.addEventListener('DOMContentLoaded', function() {
    const notificationIcon = document.querySelector('.notification-icon');
    const notificationsDropdown = document.querySelector('.notifications-dropdown');
    const notificationsContainer = document.getElementById('notificationsContainer');
    let isDropdownOpen = false;

    // Toggle notifications dropdown
    if (notificationIcon) {
        notificationIcon.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (!isDropdownOpen) {
                loadNotifications();
                notificationsDropdown.classList.add('show');
                isDropdownOpen = true;
            } else {
                notificationsDropdown.classList.remove('show');
                isDropdownOpen = false;
            }
        });
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (isDropdownOpen && !notificationsDropdown.contains(e.target)) {
            notificationsDropdown.classList.remove('show');
            isDropdownOpen = false;
        }
    });

    // Load notifications
    function loadNotifications() {
        fetch('get_notifications.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateNotificationsUI(data.notifications);
                    updateNotificationCount(data.unread_count);
                }
            })
            .catch(error => console.error('Error loading notifications:', error));
    }

    // Update notifications UI
    function updateNotificationsUI(notifications) {
        if (!notificationsContainer) return;

        if (notifications.length === 0) {
            const lang = document.documentElement.getAttribute('lang');
            notificationsContainer.innerHTML = `
                <div class="notification-item">
                    <div class="notification-content">
                        <p>${lang === 'ar' ? 'لا توجد إشعارات' : 'No notifications'}</p>
                    </div>
                </div>`;
            return;
        }

        notificationsContainer.innerHTML = notifications.map(notification => {
            return `
                <div class="notification-item ${notification.is_read ? '' : 'unread'}" 
                     data-id="${notification.id}" 
                     onclick="markNotificationAsRead(${notification.id})">
                    <div class="notification-content">
                        <div class="flex-grow-1">
                            <p>${notification.message}</p>
                            <div class="notification-time">
                                <i class="far fa-clock"></i>
                                ${notification.time_ago}
                            </div>
                        </div>
                    </div>
                </div>`;
        }).join('');
    }

    // Update notification count
    function updateNotificationCount(count) {
        const countElement = document.getElementById('notificationCount');
        if (countElement) {
            if (count > 0) {
                countElement.textContent = count;
                countElement.style.display = 'block';
            } else {
                countElement.style.display = 'none';
            }
        }
    }

    // Mark notification as read
    window.markNotificationAsRead = function(notificationId) {
        fetch('update_notification.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `notification_id=${notificationId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const notificationElement = document.querySelector(`[data-id="${notificationId}"]`);
                if (notificationElement) {
                    notificationElement.classList.remove('unread');
                }
                updateNotificationCount(data.unread_count);
            }
        })
        .catch(error => console.error('Error marking notification as read:', error));
    };

    // Auto refresh notifications every 30 seconds
    setInterval(function() {
        if (isDropdownOpen) {
            loadNotifications();
        }
    }, 30000);
});
