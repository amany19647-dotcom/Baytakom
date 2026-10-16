<?php
require_once 'config.php';
if (!isLoggedIn() || !isProvider()) {
    http_response_code(403);
    exit;
}

$stmt = $pdo->prepare("UPDATE users SET welcome_shown = 1 WHERE id = ?");
$stmt->execute(array($_SESSION['user_id']));

echo json_encode(array('success' => true));
?>