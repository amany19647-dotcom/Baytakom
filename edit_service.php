<?php
require_once 'config.php';
if (!isProvider() || !isProviderApproved()) redirect('index.php');

$user_id = $_SESSION['user_id'];
$sid = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$sid) redirect('my_services.php');

$stmt = $pdo->prepare("SELECT * FROM services WHERE id = ? AND provider_id = ?");
$stmt->execute(array($sid, $user_id));
$service = $stmt->fetch();
if (!$service) redirect('my_services.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category = trim($_POST['category']);
    $allow_quantity = isset($_POST['allow_quantity']) ? 1 : 0;
    $max_quantity = ($allow_quantity && !empty($_POST['max_quantity'])) ? intval($_POST['max_quantity']) : null;

    if (empty($name) || empty($description) || $price <= 0) {
        $error = 'يرجى ملء جميع الحقول المطلوبة';
    } elseif ($allow_quantity && $max_quantity && $max_quantity < 1) {
        $error = 'الحد الأقصى للكمية يجب أن يكون 1 على الأقل';
    } else {
        $image = $service['image'];
        if (isset($_FILES['service_image']) && $_FILES['service_image']['error'] == UPLOAD_ERR_OK) {
            if ($image) deleteImage($image);
            $uploaded = uploadImage($_FILES['service_image'], 'services');
            if ($uploaded) $image = $uploaded;
            else $error = 'فشل رفع الصورة';
        }

        if (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
            if ($image) { deleteImage($image); $image = null; }
        }

        if (empty($error)) {
            $stmt = $pdo->prepare("UPDATE services SET name = ?, description = ?, price = ?, category = ?, image = ?, allow_quantity = ?, max_quantity = ? WHERE id = ? AND provider_id = ?");
            if ($stmt->execute(array($name, $description, $price, $category, $image, $allow_quantity, $max_quantity, $sid, $user_id))) {
                $success = 'تم تحديث الخدمة بنجاح';
                $service['name'] = $name;
                $service['description'] = $description;
                $service['price'] = $price;
                $service['category'] = $category;
                $service['image'] = $image;
                $service['allow_quantity'] = $allow_quantity;
                $service['max_quantity'] = $max_quantity;
            } else {
                $error = 'حدث خطأ أثناء التحديث';
            }
        }
    }
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل الخدمة - بيتكم</title>
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
        .edit-container {
            max-width: 800px; margin: 0 auto;
            background: var(--bg-glass);
            border: 1px solid var(--border-glass);
            border-radius: 30px;
            padding: 40px;
        }
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 8px;
        }
        .input-wrapper { position: relative; }
        .input-wrapper i {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.3);
            font-size: 18px;
            pointer-events: none;
        }
        .input-wrapper input,
        .input-wrapper select,
        .input-wrapper textarea {
            width: 100%;
            padding: 15px 50px 15px 20px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-white);
            font-size: 16px;
            transition: 0.3s;
            outline: none;
            font-family: 'Tajawal', sans-serif;
        }
        .input-wrapper input:focus,
        .input-wrapper select:focus,
        .input-wrapper textarea:focus {
            border-color: var(--primary-green);
            background: rgba(255, 255, 255, 0.08);
        }
        .input-wrapper select option { background: #0D1F1B; color: var(--text-white); }
        .input-wrapper textarea { min-height: 100px; resize: vertical; padding-top: 15px; }
        .input-wrapper textarea + i { top: 18px; transform: none; }
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }
        .file-input-wrapper input[type=file] {
            position: absolute;
            left: 0; top: 0;
            opacity: 0;
            width: 100%; height: 100%;
            cursor: pointer;
        }
        .file-input-wrapper .file-label {
            display: block;
            padding: 15px 50px 15px 20px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.6);
            font-size: 16px;
            transition: 0.3s;
            cursor: pointer;
            position: relative;
        }
        .file-input-wrapper .file-label i {
            position: absolute;
            right: 16px; top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.3);
        }
        .current-image {
            margin-top: 8px;
            font-size: 13px;
            color: var(--text-gray);
        }
        .current-image img {
            max-width: 150px;
            max-height: 100px;
            border-radius: 8px;
            border: 1px solid var(--border-glass);
            display: block;
            margin-bottom: 5px;
        }
        .remove-option {
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .remove-option input[type="checkbox"] {
            width: 18px; height: 18px;
            accent-color: var(--primary-green);
            cursor: pointer;
        }
        .remove-option label {
            color: var(--text-gray);
            font-size: 14px;
            cursor: pointer;
        }
        .msg-box {
            padding: 14px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .msg-box.error { background: rgba(234, 67, 53, 0.15); border: 1px solid rgba(234, 67, 53, 0.3); color: #ea4335; }
        .msg-box.success { background: rgba(52, 168, 83, 0.15); border: 1px solid rgba(52, 168, 83, 0.3); color: #34a853; }
        .btn-register {
            width: 100%;
            padding: 16px;
            background: var(--gradient-main);
            border: none;
            border-radius: 14px;
            color: white;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 25px var(--shadow-green);
            font-family: 'Tajawal', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        /* ===== Quantity Toggle Styles ===== */
        .quantity-toggle-box {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-glass);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .toggle-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }
        .toggle-info { flex: 1; }
        .toggle-info h4 {
            color: var(--text-white);
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .toggle-info h4 i { color: var(--primary-teal); margin-left: 8px; }
        .toggle-info p {
            color: var(--text-gray);
            font-size: 13px;
            margin: 0;
        }
        .toggle-switch {
            position: relative;
            width: 60px;
            height: 32px;
            flex-shrink: 0;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 32px;
            transition: 0.4s;
        }
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 24px;
            width: 24px;
            left: 4px;
            bottom: 4px;
            background: white;
            border-radius: 50%;
            transition: 0.4s;
        }
        .toggle-switch input:checked + .toggle-slider { background: var(--gradient-main); }
        .toggle-switch input:checked + .toggle-slider:before { transform: translateX(28px); }
        .max-quantity-box {
            display: none;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--border-glass);
        }
        .max-quantity-box.show {
            display: block;
            animation: slideDown 0.3s ease;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
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
            .edit-container { padding: 25px; }
            .grid-2 { grid-template-columns: 1fr; }
            .page-header h1 { font-size: 28px; }
        }
        @media (max-width: 480px) {
            .side-menu { width: 280px; right: -320px; }
            .edit-container { padding: 20px 15px; border-radius: 20px; }
            .footer-grid .contact-section { flex-direction: column; gap: 15px; }
            .header-center { display: none; }
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
                    <li><a href="provider_dashboard.php"><i class="fas fa-chart-pie"></i> لوحة مقدم الخدمة</a></li>
                    <li><a href="add_service.php"><i class="fas fa-plus-circle"></i> إضافة خدمة</a></li>
                    <li><a href="my_services.php" class="active"><i class="fas fa-briefcase"></i> خدماتي</a></li>
                    <li><a href="provider_orders.php"><i class="fas fa-clipboard-list"></i> طلباتي</a></li>
                    <li><a href="provider_wallet.php"><i class="fas fa-wallet"></i> محفظتي</a></li>
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
            <h1 data-aos="fade-up"><i class="fas fa-edit"></i> تعديل الخدمة</h1>
            <p data-aos="fade-up" data-aos-delay="100">تحديث بيانات الخدمة</p>
        </div>
    </div>

    <section>
        <div class="container">
            <div class="edit-container" data-aos="fade-up">
                <?php if (!empty($error)): ?>
                    <div class="msg-box error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
                <?php endif; ?>
                <?php if (!empty($success)): ?>
                    <div class="msg-box success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>اسم الخدمة</label>
                        <div class="input-wrapper">
                            <input type="text" name="name" value="<?php echo htmlspecialchars($service['name']); ?>" required>
                            <i class="fas fa-tag"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>الوصف</label>
                        <div class="input-wrapper">
                            <textarea name="description" required><?php echo htmlspecialchars($service['description']); ?></textarea>
                            <i class="fas fa-pen"></i>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>السعر (ج.س)</label>
                            <div class="input-wrapper">
                                <input type="number" name="price" step="0.01" value="<?php echo $service['price']; ?>" required>
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>الفئة</label>
                            <div class="input-wrapper">
                                <select name="category">
                                    <?php
                                    $cats = array('تنظيف','كهرباء','سباكة','تكييف','دهان','نجارة','نقل أثاث','طبيخ','حدائق','صيانة عامة','أخرى');
                                    foreach ($cats as $c) {
                                        $sel = ($service['category'] == $c) ? 'selected' : '';
                                        echo '<option value="' . $c . '" ' . $sel . '>' . $c . '</option>';
                                    }
                                    ?>
                                </select>
                                <i class="fas fa-list"></i>
                            </div>
                        </div>
                    </div>

                    <!-- ===== خيارات الكمية ===== -->
                    <div class="quantity-toggle-box">
                        <div class="toggle-row">
                            <div class="toggle-info">
                                <h4><i class="fas fa-calculator"></i> السماح بطلب كمية متعددة</h4>
                                <p>إذا كانت خدمتك تقبل التعدد (مثل تنظيف غرف متعددة)، فعّل هذا الخيار</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="allow_quantity" id="allow_quantity" value="1"
                                    <?php echo ($service['allow_quantity'] == 1) ? 'checked' : ''; ?> onchange="toggleMaxQuantity()">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>

                        <div class="max-quantity-box <?php echo ($service['allow_quantity'] == 1) ? 'show' : ''; ?>" id="maxQuantityBox">
                            <div class="form-group" style="margin-bottom:0;">
                                <label>الحد الأقصى للكمية (اختياري)</label>
                                <div class="input-wrapper">
                                    <input type="number" name="max_quantity" id="max_quantity" min="1"
                                        value="<?php echo ($service['max_quantity']) ? $service['max_quantity'] : ''; ?>"
                                        placeholder="اتركه فارغاً للسماح بأي كمية">
                                    <i class="fas fa-hashtag"></i>
                                </div>
                                <small style="color:var(--text-gray); font-size:12px; margin-top:6px; display:block;">إذا تركته فارغاً، يمكن للعميل طلب أي كمية</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>صورة الخدمة</label>
                        <div class="file-input-wrapper">
                            <span class="file-label"><i class="fas fa-image"></i> اختر صورة جديدة</span>
                            <input type="file" name="service_image" accept="image/*">
                        </div>
                        <?php if ($service['image']): ?>
                            <div class="current-image">
                                <img src="<?php echo htmlspecialchars($service['image']); ?>">
                                الصورة الحالية
                            </div>
                        <?php else: ?>
                            <div class="current-image">لا توجد صورة حالياً</div>
                        <?php endif; ?>
                        <div class="remove-option">
                            <input type="checkbox" name="remove_image" id="remove_image" value="1">
                            <label for="remove_image">إزالة الصورة الحالية</label>
                        </div>
                        <small style="color:var(--text-gray); font-size:12px;">الحد الأقصى: 2 ميجابايت</small>
                    </div>

                    <button type="submit" class="btn-register"><i class="fas fa-save"></i> تحديث الخدمة</button>
                </form>
            </div>
        </div>
    </section>

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

    function toggleMaxQuantity() {
        var checkbox = document.getElementById('allow_quantity');
        var box = document.getElementById('maxQuantityBox');
        if (checkbox.checked) box.classList.add('show');
        else { box.classList.remove('show'); document.getElementById('max_quantity').value = ''; }
    }
    </script>

</body>
</html>