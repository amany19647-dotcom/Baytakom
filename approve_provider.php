<?php
require_once 'config.php';
if (!canApproveProviders()) redirect('dashboard.php');

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

if (!$user_id || !in_array($action, array('approve', 'reject'))) {
    redirect('admin_providers.php');
}

$stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ? AND user_type = 'provider'");
$stmt->execute(array($user_id));
$user = $stmt->fetch();
if (!$user) redirect('admin_providers.php');

if ($action == 'approve') {
    $pdo->prepare("UPDATE users SET is_approved = 'approved', welcome_shown = 0 WHERE id = ?")->execute(array($user_id));
    sendProviderApprovalEmail($user['email'], $user['name']);
    $_SESSION['admin_message'] = 'تم قبول "' . $user['name'] . '" وإرسال إشعار بالبريد.';
    $_SESSION['admin_message_type'] = 'success';
} else {
    $pdo->prepare("UPDATE users SET is_approved = 'rejected' WHERE id = ?")->execute(array($user_id));
    sendProviderRejectionEmail($user['email'], $user['name']);
    $_SESSION['admin_message'] = 'تم رفض "' . $user['name'] . '" وإرسال إشعار بالبريد.';
    $_SESSION['admin_message_type'] = 'error';
}

redirect('admin_providers.php');
?>