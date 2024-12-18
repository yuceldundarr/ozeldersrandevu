<?php
session_start();
require_once '../config/database.php';
require_once '../includes/notifications.php';

if(!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$notifications = get_unread_notifications($_SESSION['user_id']);
$count = count($notifications);

echo json_encode(['count' => $count]);
