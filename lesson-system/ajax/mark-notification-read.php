<?php
session_start();
require_once '../config/database.php';
require_once '../includes/notifications.php';

if(!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$notification_id = $_POST['notification_id'] ?? 0;

if($notification_id) {
    mark_notification_as_read($notification_id, $_SESSION['user_id']);
}

echo json_encode(['success' => true]);
