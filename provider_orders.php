<?php
require_once 'config.php';
if (!isProvider() || !isProviderApproved()) redirect('index.php');

$provider_id = $_SESSION['user_id'];

if (isset($_GET['action']) && isset($_GET['booking_id'])) {
    $booking_id = intval($_GET['booking_id']);
    $status = $_GET['action'];
    $allowed = array('pending', 'assigned', 'in_progress', 'cancelled');

    if ($status === 'completed') {
        $_SESSION['message'] = 'لا يمكن تغيير الحالة إلى مكتملة مباشرة، يجب أن يقر العميل بإكمال الخدمة أولاً.';
        $_SESSION['message_type'] = 'warning';
        redirect('provider_orders.php');
    } elseif (in_array($status, $allowed)) {
        $stmt = $pdo->prepare("SELECT b.id, b.payment_status FROM bookings b JOIN services s ON b.service_id = s.id WHERE b.id = ? AND s.provider_id = ?");
        $stmt->execute(array($booking_id, $provider_id));
        $row = $stmt->fetch();

        if ($row) {
            if ($status === 'in_progress' && $row['payment_status'] != 'paid') {
                $_SESSION['message'] = 'لا يمكن بدء التنفيذ قبل أن يقوم العميل بالدفع.';
                $_SESSION['message_type'] = 'warning';
                redirect('provider_orders.php');
            }
            $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?")->execute(array($status, $booking_id));
            $_SESSION['message'] = 'تم تحديث حالة الطلب';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'لا يمكنك تعديل هذا الطلب';
            $_SESSION['message_type'] = 'error';
        }
        redirect('provider_orders.php');
    }
}

$stmt = $pdo->prepare("
    SELECT b.*, u.name as client_name, u.phone as client_phone, s.name as service_name, s.price as unit_price
    FROM bookings b
    JOIN services s ON b.service_id = s.id
    JOIN users u ON b.user_id = u.id
    WHERE s.provider_id = ?
    ORDER BY b.created_at DESC
");
$stmt->execute(array($provider_id));
$bookings = $stmt->fetchAll();

$total = count($bookings);
$pending = $assigned = $in_progress = $completed = 0;
foreach ($bookings as $b) {
    if ($b['status'] == 'pending') $pending++;
    elseif ($b['status'] == 'assigned') $assigned++;
    elseif ($b['status'] == 'in_progress') $in_progress++;
    elseif ($b['status'] == 'completed') $completed++;
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طلباتي - بيتكم</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        :root {
            --primary-green: #0B8F7A;
            --primary-teal: #12A88F;
            --bg-dark: #0B1715;
            --bg-glass: rgba(255, 255, 255, 0.06);
            --text-white: #F8FAF9;
            --text-gray: #9AAEAA;
            --border-light: rgba(255, 255, 255, 0.07);
            --border-glass: rgba(255, 255, 255, 0.10);
            --shadow-green: rgba(11, 143, 122, 0.25);
            --gradient-main: linear-gradient(135deg, #0B8F7A, #12A88F, #19B394);
            --gradient-dark: linear-gradient(180deg, #0B1715, #0D1F1B);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Tajawal', sans-serif;
            background: var(--bg-dark);
            color: var(--text-white);
            line-height: 1.6;
            overflow-x: hidden;
        }
        a { text-decoration: none; color: inherit; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        #scrollProgress {
            position: fixed; top: 0; left: 0; width: 0%; height: 3px;
            background: var(--gradient-main); z-index: 9999;
        }
        header {
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
            padding: 14px 0;
            background: rgba(16, 34, 31, 0.85);
            backdrop-filter: blur(14px) saturate(1.2);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.5s cubic-bezier(0.22, 1, 0.36, 1);
        }
        header.hidden { transform: translateY(-100%); }
        header.scrolled {
            padding: 10px 0;
            background: rgba(16, 34, 31, 0.92);
            backdrop-filter: blur(20px) saturate(1.3);
            box-shadow: 0 4px 40px rgba(0, 0, 0, 0.3);
        }
        .header-inner { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .logo { display: flex; align-items: center; gap: 10px; font-size: 22px; font-weight: 800; flex-shrink: 0; order: 3; }
        .logo img { width: 42px; height: 42px; border-radius: 12px; object-fit: cover; border: 2px solid rgba(11, 143, 122, 0.3); background: #fff; padding: 3px; }
        .logo span:last-child {
            background: var(--gradient-main);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 22px;
        }
        .header-center { display: flex; align-items: center; gap: 8px; color: rgba(255, 255, 255, 0.5); font-size: 15px; font-weight: 500; order: 2; }
        .header-center i { color: var(--primary-teal); font-size: 16px; opacity: 0.6; }
        .hamburger {
            display: flex !important;
            flex-direction: column; gap: 4.5px;
            background: rgba(11, 143, 122, 0.08);
            border: 1px solid rgba(11, 143, 122, 0.15);
            border-radius: 10px; cursor: pointer;
            padding: 10px 12px; z-index: 1100;
            flex-shrink: 0; order: 1;
        }
        .hamburger span {
            display: block; width: 24px; height: 2.5px;
            background: var(--text-white); border-radius: 4px;
            transition: all 0.35s;
            transform-origin: center;
        }
        .hamburger.active span:nth-child(1) { transform: rotate(45deg) translate(5px, 5px); background: var(--primary-teal); }
        .hamburger.active span:nth-child(2) { opacity: 0; transform: scaleX(0); }
        .hamburger.active span:nth-child(3) { transform: rotate(-45deg) translate(5px, -5px); background: var(--primary-teal); }
        .side-menu {
            position: fixed; top: 0; right: -420px;
            width: 380px; max-width: 90vw; height: 100vh;
            background: rgba(16, 34, 31, 0.95);
            backdrop-filter: blur(28px) saturate(1.3);
            border-left: 1px solid rgba(255, 255, 255, 0.06);
            padding: 24px 0 30px;
            transition: right 0.5s cubic-bezier(0.22, 1, 0.36, 1);
            z-index: 2000; overflow-y: auto;
            box-shadow: -20px 0 80px rgba(0, 0, 0, 0.6);
        }
        .side-menu.open { right: 0; }
        .side-menu::-webkit-scrollbar { width: 3px; }
        .side-menu::-webkit-scrollbar-thumb { background: var(--gradient-main); border-radius: 10px; }
        .menu-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.55);
            backdrop-filter: blur(6px);
            z-index: 1999; opacity: 0; visibility: hidden;
            transition: all 0.5s ease;
        }
        .menu-overlay.active { opacity: 1; visibility: visible; }
        .menu-header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 0 24px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        .menu-logo {
            font-size: 22px; font-weight: 800;
            background: var(--gradient-main);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .close-btn {
            font-size: 24px; color: var(--text-gray);
            cursor: pointer; padding: 6px 12px; border-radius: 8px;
            background: rgba(255, 255, 255, 0.03);
        }
        .close-btn:hover { color: var(--text-white); background: rgba(255, 255, 255, 0.06); transform: rotate(90deg); }
        .menu-user-section {
            display: flex; align-items: center; gap: 12px;
            padding: 14px 18px;
            margin: 12px 16px 6px;
            background: rgba(11, 143, 122, 0.08);
            border: 1px solid rgba(11, 143, 122, 0.15);
            border-radius: 14px;
        }
        .menu-user-section .user-avatar {
            width: 44px; height: 44px;
            border-radius: 50%; overflow: hidden;
            border: 2px solid var(--primary-teal);
            display: flex; align-items: center; justify-content: center;
            background: rgba(11, 143, 122, 0.1);
            flex-shrink: 0;
        }
        .menu-user-section .user-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .menu-user-section .user-avatar i { font-size: 24px; color: var(--primary-teal); }
        .menu-user-section .user-info { flex: 1; overflow: hidden; }
        .menu-user-section .user-info .user-name {
            font-size: 15px; font-weight: 700;
            color: var(--text-white);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .menu-user-section .user-info .user-type { font-size: 12px; color: var(--text-gray); margin-top: 2px; }
        .menu-links { list-style: none; padding: 5px 16px; }
        .menu-links a {
            display: flex; align-items: center; gap: 16px;
            padding: 13px 18px; color: var(--text-gray);
            font-weight: 500; font-size: 16px;
            border-radius: 12px;
        }
        .menu-links a i { width: 24px; font-size: 18px; color: var(--text-gray); opacity: 0.5; text-align: center; }
        .menu-links a:hover { color: var(--text-white); background: rgba(11, 143, 122, 0.08); transform: translateX(-4px); }
        .menu-links a:hover i { opacity: 1; color: var(--primary-teal); }
        .menu-links a.active { color: var(--text-white); background: rgba(11, 143, 122, 0.12); border-right: 3px solid var(--primary-teal); }
        .menu-links a.active i { opacity: 1; color: var(--primary-teal); }
        .menu-logout { color: #EF4444 !important; font-weight: 700 !important; }
        .menu-logout i { color: #EF4444 !important; opacity: 0.8 !important; }
        .page-header {
            padding: 140px 0 30px;
            text-align: center;
            background: var(--gradient-dark);
            border-bottom: 1px solid var(--border-light);
        }
        .page-header h1 { font-size: 36px; font-weight: 800; color: var(--text-white); }
        .page-header h1 i { color: var(--primary-teal); margin-left: 12px; }
        .page-header p { color: var(--text-gray); font-size: 16px; }
        section { padding: 70px 0; }
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px; margin: 20px 0;
        }
        .stat-card {
            background: var(--bg-glass);
            border: 1px solid var(--border-glass);
            border-radius: 16px;
            padding: 20px; text-align: center;
        }
        .stat-card .number { font-size: 28px; font-weight: 800; color: var(--primary-teal); }
        .stat-card .label { color: var(--text-gray); font-size: 13px; margin-top: 4px; }
        .table-responsive { overflow-x: auto; margin: 20px 0; }
        table {
            width: 100%; border-collapse: collapse;
            background: var(--bg-glass);
            border-radius: 16px; overflow: hidden;
            border: 1px solid var(--border-glass);
        }
        table thead { background: rgba(255, 255, 255, 0.04); border-bottom: 2px solid var(--border-glass); }
        table th { padding: 14px 16px; text-align: center; font-weight: 700; color: var(--text-white); font-size: 13px; }
        table td { padding: 14px 16px; text-align: center; color: var(--text-gray); font-size: 13px; border-bottom: 1px solid rgba(255, 255, 255, 0.04); }
        table tbody tr:hover { background: rgba(255, 255, 255, 0.03); }
        .badge-status, .qty-badge {
            padding: 4px 14px; border-radius: 30px;
            font-size: 12px; font-weight: 700; display: inline-block;
        }
        .qty-badge { background: rgba(11, 143, 122, 0.15); color: var(--primary-teal); }
        .badge-pending { background: rgba(251, 188, 4, 0.15); color: #fbbc04; }
        .badge-assigned { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
        .badge-in_progress { background: rgba(139, 92, 246, 0.15); color: #8b5cf6; }
        .badge-completed { background: rgba(52, 168, 83, 0.15); color: #34a853; }
        .badge-cancelled { background: rgba(234, 67, 53, 0.15); color: #ea4335; }
        .total-price-cell { color: var(--primary-teal); font-weight: 700; }
        .btn-sm {
            padding: 6px 18px; border-radius: 30px;
            font-weight: 600; border: none;
            cursor: pointer; display: inline-block;
            font-size: 13px; font-family: 'Tajawal', sans-serif;
        }
        .btn-sm.danger { background: rgba(234, 67, 53, 0.15); color: #ea4335; }
        .btn-sm.success { background: rgba(52, 168, 83, 0.15); color: #34a853; }
        .btn-sm.primary { background: rgba(11, 143, 122, 0.15); color: var(--primary-teal); }
        .btn-sm.info { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
        .btn-primary {
            display: inline-block; padding: 12px 30px;
            border-radius: 50px;
            background: var(--gradient-main);
            color: #fff; font-weight: 700;
            box-shadow: 0 4px 14px var(--shadow-green);
            font-family: 'Tajawal', sans-serif;
        }
        .empty-state { text-align: center; padding: 60px 20px; color: var(--text-gray); }
        .empty-state i { font-size: 60px; color: var(--primary-teal); opacity: 0.3; margin-bottom: 20px; display: block; }
        .empty-state h3 { color: var(--text-white); margin-bottom: 10px; font-size: 24px; }
        .empty-state p { margin-bottom: 20px; }
        .msg-box {
            padding: 14px 20px; border-radius: 12px;
            margin-bottom: 20px; font-weight: 600;
            display: flex; align-items: center; gap: 12px;
        }
        .msg-box.success { background: rgba(52, 168, 83, 0.15); border: 1px solid rgba(52, 168, 83, 0.2); color: #34a853; }
        .msg-box.error { background: rgba(234, 67, 53, 0.15); border: 1px solid rgba(234, 67, 53, 0.2); color: #ea4335; }
        .msg-box.warning { background: rgba(251, 188, 4, 0.15); border: 1px solid rgba(251, 188, 4, 0.2); color: #fbbc04; }

        .details-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(8px);
            z-index: 3000;
            display: none;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .details-modal-overlay.active { display: flex; }
        .details-modal {
            background: rgba(16, 34, 31, 0.98);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-glass);
            border-radius: 24px;
            padding: 35px 30px;
            max-width: 650px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalFade 0.35s ease;
        }
        @keyframes modalFade {
            0% { opacity: 0; transform: scale(0.92) translateY(20px); }
            100% { opacity: 1; transform: scale(1) translateY(0); }
        }
        .details-modal::-webkit-scrollbar { width: 4px; }
        .details-modal::-webkit-scrollbar-thumb { background: var(--gradient-main); border-radius: 10px; }
        .details-modal .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
            padding-bottom: 18px;
            border-bottom: 1px solid var(--border-glass);
        }
        .details-modal .modal-header h3 {
            font-size: 22px;
            font-weight: 800;
            color: var(--text-white);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .details-modal .modal-header h3 i { color: var(--primary-teal); }
        .details-modal .modal-header .close-modal {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border-glass);
            color: var(--text-gray);
            width: 36px; height: 36px;
            border-radius: 50%;
            font-size: 18px;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.3s;
        }
        .details-modal .modal-header .close-modal:hover {
            background: rgba(234, 67, 53, 0.15);
            color: #ea4335;
            border-color: rgba(234, 67, 53, 0.3);
            transform: rotate(90deg);
        }
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }
        .details-grid .detail-item {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-glass);
            border-radius: 14px;
            padding: 14px 18px;
        }
        .details-grid .detail-item.full { grid-column: 1 / -1; }
        .details-grid .detail-item .label {
            font-size: 12px;
            color: var(--text-gray);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .details-grid .detail-item .label i { color: var(--primary-teal); font-size: 13px; }
        .details-grid .detail-item .value {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-white);
            word-break: break-word;
        }
        .details-grid .detail-item.highlight {
            background: linear-gradient(135deg, rgba(11, 143, 122, 0.12), rgba(18, 168, 143, 0.05));
            border-color: rgba(11, 143, 122, 0.3);
        }
        .details-grid .detail-item.highlight .value {
            color: var(--primary-teal);
            font-size: 20px;
            font-weight: 800;
        }
        .details-grid .detail-item.payment-paid {
            background: rgba(52, 168, 83, 0.1);
            border-color: rgba(52, 168, 83, 0.25);
        }
        .details-grid .detail-item.payment-unpaid {
            background: rgba(251, 188, 4, 0.1);
            border-color: rgba(251, 188, 4, 0.25);
        }
        .details-grid .detail-item.payment-paid .value { color: #34a853; }
        .details-grid .detail-item.payment-unpaid .value { color: #fbbc04; }

        .modal-actions {
            margin-top: 22px;
            padding-top: 20px;
            border-top: 1px solid var(--border-glass);
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .modal-actions .btn-sm { padding: 10px 24px; font-size: 14px; }
        .modal-actions .btn-close-modal {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-gray);
            border: 1px solid var(--border-glass);
        }
        .modal-actions .btn-close-modal:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-white);
        }

        footer {
            background: rgba(11, 23, 21, 0.95);
            color: var(--text-gray);
            padding: 40px 0 20px;
            border-top: 1px solid var(--border-light);
        }
        .footer-grid {
            display: flex; justify-content: center;
            align-items: center; flex-direction: column; gap: 20px;
        }
        .footer-grid .contact-section {
            display: flex; flex-wrap: wrap;
            justify-content: center; gap: 40px; text-align: center;
        }
        .footer-grid .contact-section .contact-item {
            display: flex; flex-direction: column;
            align-items: center; gap: 8px;
            padding: 10px 20px; border-radius: 12px;
            background: rgba(255, 255, 255, 0.02);
        }
        .footer-grid .contact-section .contact-item i { font-size: 28px; color: var(--primary-teal); }
        .footer-grid .contact-section .contact-item .label { font-size: 13px; color: var(--text-gray); opacity: 0.6; }
        .footer-grid .contact-section .contact-item .value { font-size: 16px; font-weight: 600; color: var(--text-white); }
        .social-icons { display: flex; gap: 15px; margin-top: 15px; justify-content: center; }
        .social-icons a {
            display: flex; align-items: center; justify-content: center;
            width: 44px; height: 44px; border-radius: 50%;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-light);
            color: var(--text-gray);
            transition: all 0.3s; font-size: 18px;
        }
        .social-icons a:hover { background: var(--gradient-main); color: #fff; transform: translateY(-4px); border-color: transparent; }
        .copyright {
            text-align: center;
            border-top: 1px solid var(--border-light);
            padding-top: 25px; margin-top: 20px;
            font-size: 14px; opacity: 0.5; width: 100%;
        }
        #backToTop {
            position: fixed;
            bottom: 30px; right: 30px;
            width: 50px; height: 50px;
            border-radius: 50%;
            background: var(--bg-glass);
            backdrop-filter: blur(16px);
            color: #fff;
            border: 1px solid var(--border-glass);
            font-size: 22px;
            cursor: pointer;
            opacity: 0; visibility: hidden;
            z-index: 999;
        }
        #backToTop.visible { opacity: 1; visibility: visible; }
        @media (max-width: 768px) {
            .header-center span { display: none; }
            .side-menu { width: 300px; right: -340px; }
            table th, table td { padding: 8px 10px; font-size: 11px; }
            .stats-row { grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); }
            .page-header h1 { font-size: 28px; }
            .details-grid { grid-template-columns: 1fr; }
            .details-modal { padding: 25px 20px; }
        }
        @media (max-width: 480px) {
            .side-menu { width: 280px; right: -320px; }
            .footer-grid .contact-section { flex-direction: column; gap: 15px; }
            .header-center { display: none; }
            .modal-actions { flex-direction: column; }
            .modal-actions .btn-sm { width: 100%; text-align: center; }
        }
        [data-aos] { opacity: 0; }
        [data-aos].aos-animate { opacity: 1; }
    </style>
</head>
<body>

    <div id="scrollProgress"></div>

    <header id="mainHeader">
        <div class="container header-inner">
            <div class="logo">
                <img src="uploads/sh.png" alt="بيتكم">
                <span>بيتكم</span>
            </div>
            <div class="header-center">
                <i class="fas fa-map-marker-alt"></i>
                <span>ربك وكوستي - ولاية النيل الأبيض</span>
            </div>
            <button class="hamburger" id="hamburgerBtn"><span></span><span></span><span></span></button>
        </div>
    </header>

    <div class="menu-overlay" id="menuOverlay"></div>
    <nav class="side-menu" id="sideMenu">
        <div class="menu-header">
            <span class="close-btn" id="closeMenuBtn">✕</span>
            <div class="menu-logo">بيتكم</div>
        </div>

        <?php if (isLoggedIn()): 
            $current_user = getCurrentUser();
        ?>
            <div class="menu-user-section">
                <div class="user-avatar">
                    <?php if ($current_user['profile_image'] && file_exists($current_user['profile_image'])): ?>
                        <img src="<?php echo htmlspecialchars($current_user['profile_image']); ?>" alt="صورة">
                    <?php else: ?>
                        <i class="fas fa-user-circle"></i>
                    <?php endif; ?>
                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($current_user['name']); ?></div>
                    <div class="user-type">
                        <?php
                            if (isAdmin()) echo 'مدير';
                            elseif (isProvider()) echo 'مقدم خدمة';
                            else echo 'عميل';
                        ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <ul class="menu-links">
            <li><a href="home.php"><i class="fas fa-home"></i> الرئيسية</a></li>
            <li><a href="services.php"><i class="fas fa-tools"></i> الخدمات</a></li>
            <li><a href="providers.php"><i class="fas fa-users"></i> مقدمو الخدمات</a></li>
            <li><a href="about.php"><i class="fas fa-circle-info"></i> من نحن</a></li>
            <li><a href="contact.php"><i class="fas fa-envelope"></i> تواصل معنا</a></li>
            <?php if (isLoggedIn()): ?>
                <?php if (isAdmin()): ?>
                    <li><a href="dashboard.php"><i class="fas fa-gauge-high"></i> لوحة التحكم</a></li>
                <?php endif; ?>
                <?php if (isProvider() && isProviderApproved()): ?>
                    <li><a href="add_service.php"><i class="fas fa-plus-circle"></i> إضافة خدمة</a></li>
                    <li><a href="my_services.php"><i class="fas fa-briefcase"></i> خدماتي</a></li>
                    <li><a href="provider_orders.php" class="active"><i class="fas fa-clipboard-list"></i> طلباتي</a></li>
                <?php endif; ?>
                <?php if (isClient() || (isProvider() && !isProviderApproved())): ?>
                    <li><a href="my_bookings.php"><i class="fas fa-calendar-check"></i> حجوزاتي</a></li>
                <?php endif; ?>
                <li><a href="my_reviews.php"><i class="fas fa-star"></i> التقييمات</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> الملف الشخصي</a></li>
                <li><a href="logout.php" class="menu-logout"><i class="fas fa-right-from-bracket"></i> تسجيل خروج</a></li>
            <?php else: ?>
                <li><a href="login.php"><i class="fas fa-right-to-bracket"></i> تسجيل الدخول</a></li>
                <li><a href="register.php"><i class="fas fa-user-plus"></i> إنشاء حساب</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="page-header">
        <div class="container">
            <h1 data-aos="fade-up"><i class="fas fa-clipboard-list"></i> طلباتي</h1>
            <p data-aos="fade-up" data-aos-delay="100">جميع الحجوزات التي قام بها العملاء لخدماتك</p>
        </div>
    </div>

    <section>
        <div class="container">
            <?php if (isset($_SESSION['message'])): ?>
                <div class="msg-box <?php echo $_SESSION['message_type']; ?>">
                    <i class="fas fa-<?php echo $_SESSION['message_type'] == 'success' ? 'check-circle' : ($_SESSION['message_type'] == 'warning' ? 'exclamation-triangle' : 'exclamation-circle'); ?>"></i>
                    <?php echo $_SESSION['message']; unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
                </div>
            <?php endif; ?>

            <div class="stats-row" data-aos="fade-up">
                <div class="stat-card"><div class="number"><?php echo $total; ?></div><div class="label">الإجمالي</div></div>
                <div class="stat-card"><div class="number" style="color:#fbbc04;"><?php echo $pending; ?></div><div class="label">قيد الانتظار</div></div>
                <div class="stat-card"><div class="number" style="color:#3b82f6;"><?php echo $assigned; ?></div><div class="label">مقبولة</div></div>
                <div class="stat-card"><div class="number" style="color:#8b5cf6;"><?php echo $in_progress; ?></div><div class="label">قيد التنفيذ</div></div>
                <div class="stat-card"><div class="number" style="color:#34a853;"><?php echo $completed; ?></div><div class="label">مكتملة</div></div>
            </div>

            <?php if (count($bookings) == 0): ?>
                <div class="empty-state" data-aos="fade-up">
                    <i class="fas fa-inbox"></i>
                    <h3>لا توجد طلبات حالياً</h3>
                    <p>سيظهر هنا أي حجوزات يقوم بها العملاء لخدماتك</p>
                    <a href="add_service.php" class="btn-primary"><i class="fas fa-plus"></i> إضافة خدمة جديدة</a>
                </div>
            <?php else: ?>
                <div class="table-responsive" data-aos="fade-up">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>العميل</th>
                                <th>الخدمة</th>
                                <th>التاريخ</th>
                                <th>الكمية</th>
                                <th>سعر الوحدة</th>
                                <th>الإجمالي</th>
                                <th>الحالة</th>
                                <th>الإجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $b): 
                                $booking_total = $b['total_price'] ? $b['total_price'] : ($b['unit_price'] * $b['quantity']);
                                $status_labels = array('pending'=>'معلق', 'assigned'=>'مقبول', 'in_progress'=>'قيد التنفيذ', 'completed'=>'مكتمل', 'cancelled'=>'ملغي');
                                $status_text = isset($status_labels[$b['status']]) ? $status_labels[$b['status']] : $b['status'];
                                $payment_text = $b['payment_status'] == 'paid' ? 'مدفوع' : 'غير مدفوع';

                                // ✅ تجهيز بيانات المودال
                                $modal_data = array(
                                    "id" => $b["id"],
                                    "client_name" => $b["client_name"],
                                    "client_phone" => $b["client_phone"],
                                    "service_name" => $b["service_name"],
                                    "booking_date" => date("d/m/Y", strtotime($b["booking_date"])),
                                    "booking_time" => $b["booking_time"] ? date("h:i A", strtotime($b["booking_time"])) : "-",
                                    "quantity" => $b["quantity"],
                                    "unit_price" => number_format($b["unit_price"], 2),
                                    "total_price" => number_format($booking_total, 2),
                                    "address" => $b["address"],
                                    "city" => $b["city"],
                                    "notes" => isset($b["notes"]) ? $b["notes"] : "",
                                    "payment_status" => $b["payment_status"],
                                    "payment_text" => $payment_text,
                                    "status" => $b["status"],
                                    "status_text" => $status_text,
                                    "completion_comment" => isset($b["completion_comment"]) ? $b["completion_comment"] : "",
                                    "created_at" => date("d/m/Y H:i", strtotime($b["created_at"]))
                                );
                                // ✅ إزالة JSON_UNESCAPED_UNICODE ليتوافق مع جميع نسخ PHP
                                $modal_json = htmlspecialchars(json_encode($modal_data), ENT_QUOTES, 'UTF-8');
                            ?>
                                <tr>
                                    <td><?php echo $b['id']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($b['client_name']); ?>
                                        <span style="display:block; color:#94a3b8; font-size:11px;"><?php echo htmlspecialchars($b['client_phone']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($b['service_name']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($b['booking_date'])); ?></td>
                                    <td><span class="qty-badge"><?php echo $b['quantity']; ?></span></td>
                                    <td><?php echo number_format($b['unit_price'], 2); ?> ج.س</td>
                                    <td class="total-price-cell"><?php echo number_format($booking_total, 2); ?> ج.س</td>
                                    <td>
                                        <span class="badge-status badge-<?php echo $b['status']; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display:flex; gap:3px; flex-wrap:wrap; justify-content:center;">
                                            <button type="button" class="btn-sm info details-btn" data-booking="<?php echo $modal_json; ?>">
                                                <i class="fas fa-eye"></i> تفاصيل
                                            </button>

                                            <?php if ($b['status'] == 'pending'): ?>
                                                <a href="?action=assigned&booking_id=<?php echo $b['id']; ?>" class="btn-sm success" onclick="return confirm('قبول هذا الطلب؟')">قبول</a>
                                                <a href="?action=cancelled&booking_id=<?php echo $b['id']; ?>" class="btn-sm danger" onclick="return confirm('رفض هذا الطلب؟')">رفض</a>
                                            <?php endif; ?>

                                            <?php if ($b['status'] == 'assigned'): ?>
                                                <?php if ($b['payment_status'] == 'paid'): ?>
                                                    <a href="?action=in_progress&booking_id=<?php echo $b['id']; ?>" class="btn-sm primary">بدء التنفيذ</a>
                                                <?php else: ?>
                                                    <span style="color:#fbbc04; font-size:11px;"><i class="fas fa-hourglass-half"></i> بانتظار دفع العميل</span>
                                                <?php endif; ?>
                                            <?php endif; ?>

                                            <?php if ($b['status'] == 'in_progress'): ?>
                                                <span style="color:#fbbc04; font-size:11px;">بانتظار تأكيد العميل</span>
                                            <?php endif; ?>
                                            <?php if ($b['status'] == 'completed'): ?>
                                                <span style="color:#34a853; font-size:12px;"><i class="fas fa-check-circle"></i> مكتمل</span>
                                            <?php endif; ?>
                                            <?php if ($b['status'] == 'cancelled'): ?>
                                                <span style="color:#ea4335; font-size:12px;"><i class="fas fa-times-circle"></i> ملغي</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <div class="details-modal-overlay" id="detailsModal">
        <div class="details-modal">
            <div class="modal-header">
                <h3><i class="fas fa-file-invoice"></i> تفاصيل الطلب</h3>
                <button type="button" class="close-modal" onclick="closeDetailsModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="details-grid" id="detailsContent"></div>

            <div class="modal-actions">
                <button type="button" class="btn-sm btn-close-modal" onclick="closeDetailsModal()">
                    <i class="fas fa-times"></i> إغلاق
                </button>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="contact-section">
                    <div class="contact-item">
                        <i class="fas fa-phone-alt"></i>
                        <span class="label">الهاتف</span>
                        <span class="value"><a href="tel:0912602109">0912602109</a></span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <span class="label">البريد الإلكتروني</span>
                        <span class="value"><a href="mailto:baitkom@gmail.com">baitkom@gmail.com</a></span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span class="label">الموقع</span>
                        <span class="value">ربك وكوستي - ولاية النيل الأبيض</span>
                    </div>
                </div>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-whatsapp"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
                <div class="copyright">© 2026 بيتكم - ربك وكوستي. جميع الحقوق محفوظة</div>
            </div>
        </div>
    </footer>

    <button id="backToTop" onclick="window.scrollTo({top:0,behavior:'smooth'})"><i class="fas fa-arrow-up"></i></button>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
    AOS.init({ duration: 800, once: true, mirror: false, offset: 50 });
    var scrollProgress = document.getElementById('scrollProgress');
    window.addEventListener('scroll', function() {
        var st = window.scrollY;
        var dh = document.documentElement.scrollHeight - window.innerHeight;
        if (dh > 0) scrollProgress.style.width = ((st / dh) * 100) + '%';
    });
    var header = document.getElementById('mainHeader');
    var lastScrollY = 0, ticking = false;
    window.addEventListener('scroll', function() {
        if (!ticking) {
            window.requestAnimationFrame(function() {
                var cy = window.scrollY;
                if (cy > 50) header.classList.add('scrolled'); else header.classList.remove('scrolled');
                if (cy > lastScrollY && cy > 100) header.classList.add('hidden'); else header.classList.remove('hidden');
                lastScrollY = cy;
                ticking = false;
            });
            ticking = true;
        }
    });
    document.addEventListener('DOMContentLoaded', function() {
        var hamburger = document.getElementById('hamburgerBtn');
        var sideMenu = document.getElementById('sideMenu');
        var closeBtn = document.getElementById('closeMenuBtn');
        var overlay = document.getElementById('menuOverlay');
        function toggleMenu() {
            hamburger.classList.toggle('active');
            sideMenu.classList.toggle('open');
            overlay.classList.toggle('active');
            document.body.style.overflow = sideMenu.classList.contains('open') ? 'hidden' : '';
        }
        hamburger.addEventListener('click', toggleMenu);
        closeBtn.addEventListener('click', toggleMenu);
        overlay.addEventListener('click', toggleMenu);
        document.querySelectorAll('.menu-links a').forEach(function(link) {
            link.addEventListener('click', function() {
                if (sideMenu.classList.contains('open')) toggleMenu();
            });
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sideMenu.classList.contains('open')) toggleMenu();
        });
    });
    var backBtn = document.getElementById('backToTop');
    window.addEventListener('scroll', function() {
        if (window.scrollY > 400) backBtn.classList.add('visible'); else backBtn.classList.remove('visible');
    });

    function showBookingDetails(data) {
        var modal = document.getElementById('detailsModal');
        var content = document.getElementById('detailsContent');

        var paymentClass = data.payment_status === 'paid' ? 'payment-paid' : 'payment-unpaid';
        var paymentIcon = data.payment_status === 'paid' ? 'fa-check-circle' : 'fa-clock';

        function esc(str) {
            if (str === null || str === undefined) return '';
            var div = document.createElement('div');
            div.textContent = String(str);
            return div.innerHTML;
        }

        var html = '';
        html += '<div class="detail-item">';
        html += '  <div class="label"><i class="fas fa-hashtag"></i> رقم الحجز</div>';
        html += '  <div class="value">#' + esc(data.id) + '</div>';
        html += '</div>';

        html += '<div class="detail-item">';
        html += '  <div class="label"><i class="fas fa-calendar-plus"></i> تاريخ الطلب</div>';
        html += '  <div class="value">' + esc(data.created_at) + '</div>';
        html += '</div>';

        html += '<div class="detail-item">';
        html += '  <div class="label"><i class="fas fa-user"></i> اسم العميل</div>';
        html += '  <div class="value">' + esc(data.client_name) + '</div>';
        html += '</div>';

        html += '<div class="detail-item">';
        html += '  <div class="label"><i class="fas fa-phone"></i> رقم الهاتف</div>';
        html += '  <div class="value"><a href="tel:' + esc(data.client_phone) + '" style="color:var(--primary-teal);">' + esc(data.client_phone) + '</a></div>';
        html += '</div>';

        html += '<div class="detail-item full">';
        html += '  <div class="label"><i class="fas fa-tools"></i> الخدمة المطلوبة</div>';
        html += '  <div class="value">' + esc(data.service_name) + '</div>';
        html += '</div>';

        html += '<div class="detail-item">';
        html += '  <div class="label"><i class="fas fa-calendar-day"></i> تاريخ التنفيذ</div>';
        html += '  <div class="value">' + esc(data.booking_date) + '</div>';
        html += '</div>';

        html += '<div class="detail-item">';
        html += '  <div class="label"><i class="fas fa-clock"></i> وقت التنفيذ</div>';
        html += '  <div class="value">' + esc(data.booking_time) + '</div>';
        html += '</div>';

        html += '<div class="detail-item full">';
        html += '  <div class="label"><i class="fas fa-map-marker-alt"></i> العنوان</div>';
        html += '  <div class="value">' + esc(data.address) + ' - ' + esc(data.city) + '</div>';
        html += '</div>';

        if (data.notes && String(data.notes).trim() !== '') {
            html += '<div class="detail-item full">';
            html += '  <div class="label"><i class="fas fa-pen"></i> ملاحظات العميل</div>';
            html += '  <div class="value">' + esc(data.notes) + '</div>';
            html += '</div>';
        }

        html += '<div class="detail-item">';
        html += '  <div class="label"><i class="fas fa-calculator"></i> الكمية</div>';
        html += '  <div class="value">' + esc(data.quantity) + '</div>';
        html += '</div>';

        html += '<div class="detail-item">';
        html += '  <div class="label"><i class="fas fa-money-bill-wave"></i> سعر الوحدة</div>';
        html += '  <div class="value">' + esc(data.unit_price) + ' ج.س</div>';
        html += '</div>';

        html += '<div class="detail-item highlight full">';
        html += '  <div class="label"><i class="fas fa-receipt"></i> الإجمالي الكلي</div>';
        html += '  <div class="value">' + esc(data.total_price) + ' ج.س</div>';
        html += '</div>';

        html += '<div class="detail-item ' + paymentClass + '">';
        html += '  <div class="label"><i class="fas ' + paymentIcon + '"></i> حالة الدفع</div>';
        html += '  <div class="value">' + esc(data.payment_text) + '</div>';
        html += '</div>';

        html += '<div class="detail-item">';
        html += '  <div class="label"><i class="fas fa-info-circle"></i> حالة الطلب</div>';
        html += '  <div class="value">' + esc(data.status_text) + '</div>';
        html += '</div>';

        if (data.completion_comment && String(data.completion_comment).trim() !== '') {
            html += '<div class="detail-item full">';
            html += '  <div class="label"><i class="fas fa-comment"></i> تعليق الإكمال</div>';
            html += '  <div class="value">' + esc(data.completion_comment) + '</div>';
            html += '</div>';
        }

        content.innerHTML = html;
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeDetailsModal() {
        document.getElementById('detailsModal').classList.remove('active');
        document.body.style.overflow = '';
    }

    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.details-btn');
        if (btn) {
            try {
                var data = JSON.parse(btn.dataset.booking);
                showBookingDetails(data);
            } catch (err) {
                console.error('خطأ في قراءة بيانات الطلب:', err);
                alert('حدث خطأ في عرض التفاصيل');
            }
        }
    });

    document.getElementById('detailsModal').addEventListener('click', function(e) {
        if (e.target === this) closeDetailsModal();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeDetailsModal();
    });
    </script>

</body>
</html>