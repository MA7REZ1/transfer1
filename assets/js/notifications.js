document.addEventListener('DOMContentLoaded', function() {
    const notificationIcon = document.querySelector('.notification-icon');
    const notificationsDropdown = document.querySelector('.notifications-dropdown');
    
    if (notificationIcon && notificationsDropdown) {
        // Toggle notifications dropdown
        notificationIcon.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Toggle dropdown visibility
            notificationsDropdown.classList.toggle('show');
            
            // Add ringing animation to bell icon
            const bellIcon = this.querySelector('i');
            bellIcon.classList.add('ringing');
            setTimeout(() => bellIcon.classList.remove('ringing'), 1000);
            
            // Load notifications if dropdown is shown
            if (notificationsDropdown.classList.contains('show')) {
                loadNotifications();
            }
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!notificationsDropdown.contains(e.target) && !notificationIcon.contains(e.target)) {
                notificationsDropdown.classList.remove('show');
            }
        });
    }
    
    // Function to load notifications
    function loadNotifications() {
        fetch('../admin/get_notifications.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const container = document.getElementById('notificationsContainer');
                    if (container) {
                        if (data.notifications && data.notifications.length > 0) {
                            const notificationsHtml = data.notifications.map(notification => `
                                <div class="notification-item ${notification.is_read ? '' : 'unread'}" data-id="${notification.id}">
                                    <div class="notification-content">
                                        <p>${notification.message}</p>
                                        <div class="notification-time">
                                            <i class="far fa-clock"></i>
                                            ${notification.time_ago}
                                        </div>
                                    </div>
                                </div>
                            `).join('');
                            container.innerHTML = notificationsHtml;
                        } else {
                            container.innerHTML = `
                                <div class="text-center py-4">
                                    <i class="far fa-bell-slash fa-2x text-muted mb-2"></i>
                                    <p class="text-muted">لا توجد إشعارات جديدة</p>
                                </div>
                            `;
                        }
                    }

                    // Update unread notifications count
                    updateNotificationCount(data.unread_count);
                }
            })
            .catch(error => {
                console.error('Error loading notifications:', error);
            });
    }
    
    // Function to update notification count
    function updateNotificationCount(count) {
        const countElement = document.getElementById('notificationCount');
        if (count > 0) {
            if (countElement) {
                countElement.textContent = count;
            } else {
                const badge = document.createElement('span');
                badge.id = 'notificationCount';
                badge.className = 'notification-badge';
                badge.textContent = count;
                notificationIcon?.appendChild(badge);
            }
        } else if (countElement) {
            countElement.remove();
        }
    }
    
    // Handle notification clicks
    document.addEventListener('click', function(e) {
        const notificationItem = e.target.closest('.notification-item');
        if (notificationItem) {
            const notificationId = notificationItem.dataset.id;
            if (notificationId && notificationItem.classList.contains('unread')) {
                markNotificationAsRead(notificationId);
            }
        }
    });

    // Function to mark notification as read
    function markNotificationAsRead(notificationId) {
        fetch('ajax/mark_notification_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                notification_id: notificationId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const notificationItem = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
                if (notificationItem) {
                    notificationItem.classList.remove('unread');
                }
                
                // Update notification count
                if (typeof data.unread_count !== 'undefined') {
                    updateNotificationCount(data.unread_count);
                }
            }
        })
        .catch(error => console.error('Error marking notification as read:', error));
    }

    // Check for new notifications periodically
    if (notificationIcon && notificationsDropdown) {
        setInterval(loadNotifications, 30000); // Check every 30 seconds
    }
});
