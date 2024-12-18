<?php
session_start();
require_once '../config/database.php';
require_once '../includes/notifications.php';

if(!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$success = mark_all_notifications_as_read($_SESSION['user_id']);

echo json_encode(['success' => $success]);
