<?php
require_once 'notifications.php';

// Okunmamış bildirimleri al
$notifications = get_unread_notifications($_SESSION['user_id'] ?? 0);
$notification_count = count($notifications);
?>

<!-- Bildirim Butonu -->
<div class="dropdown">
    <button class="btn btn-link position-relative" type="button" data-bs-toggle="dropdown">
        <i class="bi bi-bell fs-5"></i>
        <?php if($notification_count > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                <?php echo $notification_count; ?>
            </span>
        <?php endif; ?>
    </button>
    
    <div class="dropdown-menu dropdown-menu-end p-0" style="width: 320px; max-height: 480px; overflow-y: auto;">
        <div class="p-2 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Bildirimler</h6>
            <?php if($notification_count > 0): ?>
                <button class="btn btn-sm btn-link text-decoration-none" onclick="markAllAsRead()">
                    Tümünü Okundu İşaretle
                </button>
            <?php endif; ?>
        </div>
        
        <?php if(empty($notifications)): ?>
            <div class="p-3 text-center text-muted">
                <i class="bi bi-bell-slash mb-2 fs-4"></i>
                <p class="mb-0">Yeni bildiriminiz yok</p>
            </div>
        <?php else: ?>
            <?php foreach($notifications as $notification): ?>
                <div class="notification-item p-2 border-bottom" data-id="<?php echo $notification['id']; ?>">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <?php
                            $icon_class = 'info';
                            switch($notification['type']) {
                                case 'success':
                                    $icon_class = 'check-circle text-success';
                                    break;
                                case 'warning':
                                    $icon_class = 'exclamation-triangle text-warning';
                                    break;
                                case 'danger':
                                    $icon_class = 'exclamation-circle text-danger';
                                    break;
                                default:
                                    $icon_class = 'info-circle text-info';
                            }
                            ?>
                            <i class="bi bi-<?php echo $icon_class; ?> fs-5"></i>
                        </div>
                        <div class="flex-grow-1 ms-2">
                            <?php if($notification['link']): ?>
                                <a href="<?php echo htmlspecialchars($notification['link']); ?>" 
                                   class="text-decoration-none text-dark"
                                   onclick="markAsRead(<?php echo $notification['id']; ?>)">
                            <?php endif; ?>
                            
                            <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                            <p class="mb-1 small"><?php echo htmlspecialchars($notification['message']); ?></p>
                            
                            <?php if($notification['link']): ?>
                                </a>
                            <?php endif; ?>
                            
                            <small class="text-muted">
                                <?php echo time_elapsed_string($notification['created_at']); ?>
                            </small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Bildirim JavaScript -->
<script>
function markAsRead(notificationId) {
    fetch('ajax/mark-notification-read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notification_id=' + notificationId
    });
}

function markAllAsRead() {
    fetch('ajax/mark-all-notifications-read.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            location.reload();
        }
    });
}

// Her 30 saniyede bir yeni bildirim kontrolü
setInterval(() => {
    fetch('ajax/check-notifications.php')
    .then(response => response.json())
    .then(data => {
        if(data.count > 0) {
            location.reload();
        }
    });
}, 30000);
</script>
