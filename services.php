<?php
require_once 'config.php';

$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$city = isset($_GET['city']) ? trim($_GET['city']) : '';

$sql = "SELECT s.*, u.name as provider_name FROM services s JOIN users u ON s.provider_id = u.id WHERE s.status = 'active'";
$params = array();

if (!empty($category)) { $sql .= " AND s.category = ?"; $params[] = $category; }
if (!empty($search)) { $sql .= " AND (s.name LIKE ? OR s.description LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if (!empty($city)) { $sql .= " AND u.city = ?"; $params[] = $city; }

$sql .= " ORDER BY s.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$services = $stmt->fetchAll();

$stmt = $pdo->query("SELECT DISTINCT category FROM services WHERE status = 'active' ORDER BY category");
$categories = $stmt->fetchAll();

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بيتكم | الخدمات - ربك وكوستي</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        :root {
            --primary-green: #0B8F7A;
            --primary-teal: #12A88F;
            --primary-cyan: #19B394;
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
            transition: width 0.1s ease;
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

        .header-inner {
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: nowrap; gap: 12px;
        }

        .logo {
            display: flex; align-items: center; gap: 10px;
            font-size: 22px; font-weight: 800; flex-shrink: 0;
            order: 3;
        }
        .logo img {
            width: 42px; height: 42px; border-radius: 12px;
            object-fit: cover;
            border: 2px solid rgba(11, 143, 122, 0.3);
            background: #fff; padding: 3px;
        }
        .logo span:last-child {
            background: var(--gradient-main);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 22px;
        }

        .header-center {
            display: flex; align-items: center; gap: 8px;
            color: rgba(255, 255, 255, 0.5);
            font-size: 15px; font-weight: 500;
            order: 2;
        }
        .header-center i { color: var(--primary-teal); font-size: 16px; opacity: 0.6; }

        .hamburger {
            display: flex !important;
            flex-direction: column; gap: 4.5px;
            background: rgba(11, 143, 122, 0.08);
            border: 1px solid rgba(11, 143, 122, 0.15);
            border-radius: 10px; cursor: pointer;
            padding: 10px 12px;
            z-index: 1100;
            flex-shrink: 0;
            order: 1;
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
            margin: 10px 16px 12px;
            background: rgba(11, 143, 122, 0.08);
            border: 1px solid rgba(11, 143, 122, 0.15);
            border-radius: 14px;
        }
        .menu-user-section .user-avatar {
            width: 44px; height: 44px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid var(--primary-teal);
            display: flex; align-items: center; justify-content: center;
            background: rgba(11, 143, 122, 0.1);
            flex-shrink: 0;
        }
        .menu-user-section .user-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .menu-user-section .user-avatar i { font-size: 24px; color: var(--primary-teal); }
        .menu-user-section .user-info { flex: 1; overflow: hidden; }
        .menu-user-section .user-info .user-name {
            font-size: 15px; font-weight: 700; color: var(--text-white);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .menu-user-section .user-info .user-type {
            font-size: 12px; color: var(--text-gray); margin-top: 2px;
        }

        .menu-links { list-style: none; padding: 5px 16px; }
        .menu-links a {
            display: flex; align-items: center; gap: 16px;
            padding: 13px 18px; color: var(--text-gray);
            font-weight: 500; font-size: 16px;
            transition: all 0.3s;
            border-radius: 12px;
        }
        .menu-links a i { width: 24px; font-size: 18px; color: var(--text-gray); opacity: 0.5; text-align: center; }
        .menu-links a:hover { color: var(--text-white); background: rgba(11, 143, 122, 0.08); transform: translateX(-4px); }
        .menu-links a:hover i { opacity: 1; color: var(--primary-teal); }
        .menu-links a.active { color: var(--text-white); background: rgba(11, 143, 122, 0.12); border-right: 3px solid var(--primary-teal); }
        .menu-links a.active i { opacity: 1; color: var(--primary-teal); }

        .menu-login { color: var(--primary-teal) !important; font-weight: 700 !important; }
        .menu-login i { color: var(--primary-teal) !important; opacity: 1 !important; }
        .menu-register {
            background: var(--gradient-main) !important;
            color: white !important;
            border-radius: 12px;
            margin: 4px 0;
            font-weight: 700 !important;
            justify-content: center !important;
            box-shadow: 0 4px 25px var(--shadow-green);
        }
        .menu-register i { color: white !important; opacity: 1 !important; }
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

        .filter-box {
            background: var(--bg-glass);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border-glass);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex; gap: 15px;
            flex-wrap: wrap; align-items: center;
        }
        .filter-box form {
            display: flex; gap: 15px;
            flex-wrap: wrap; align-items: center; width: 100%;
        }
        .filter-select { flex: 1; min-width: 150px; }
        .filter-select select {
            width: 100%; padding: 12px 18px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border-glass);
            color: var(--text-white);
            font-family: inherit; font-size: 15px;
            outline: none; cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%239AAEAA' stroke-width='2' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: left 18px center;
        }
        .filter-select select option { background: #0D1F1B; color: var(--text-white); }
        .filter-search { flex: 2; min-width: 200px; position: relative; }
        .filter-search input {
            width: 100%; padding: 12px 18px 12px 45px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border-glass);
            color: var(--text-white);
            font-family: inherit; font-size: 15px;
            outline: none;
        }
        .filter-search i {
            position: absolute; left: 16px; top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.3); font-size: 16px;
        }
        .btn-primary {
            padding: 12px 30px; border-radius: 50px;
            font-weight: 700; border: none;
            cursor: pointer; font-size: 14px;
            background: var(--gradient-main);
            color: #fff;
            box-shadow: 0 4px 14px var(--shadow-green);
            font-family: 'Tajawal', sans-serif;
        }
        .btn-outline {
            padding: 12px 30px; border-radius: 50px;
            font-weight: 700; font-size: 14px;
            background: transparent;
            border: 2px solid var(--primary-green);
            color: var(--primary-green);
            text-decoration: none;
            display: inline-block;
            font-family: 'Tajawal', sans-serif;
        }

        section { padding: 70px 0; }
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 30px;
        }
        .card {
            background: var(--bg-glass);
            backdrop-filter: blur(14px);
            border: 1px solid var(--border-glass);
            border-radius: 24px;
            padding: 24px 18px;
            transition: all 0.5s;
            text-align: center;
            color: var(--text-white);
        }
        .card:hover {
            transform: translateY(-8px);
            border-color: rgba(11, 143, 122, 0.2);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
        }
        .card .image-wrapper {
            width: 100%; height: 150px;
            border-radius: 16px; overflow: hidden;
            margin-bottom: 14px;
            background: rgba(255, 255, 255, 0.03);
        }
        .card .image-wrapper img { width: 100%; height: 100%; object-fit: cover; transition: 0.6s; }
        .card:hover .image-wrapper img { transform: scale(1.06); }
        .card .image-wrapper .icon-placeholder {
            width: 100%; height: 100%;
            display: flex; align-items: center; justify-content: center;
            font-size: 50px; color: var(--primary-teal);
            background: rgba(11, 143, 122, 0.05);
        }
        .card h3 { font-size: 20px; margin-bottom: 6px; }
        .card p { color: var(--text-gray); font-size: 14px; margin-bottom: 16px; }
        .card .btn-sm {
            background: var(--gradient-main);
            color: #fff; padding: 8px 24px;
            border-radius: 30px;
            font-weight: 600; border: none;
            cursor: pointer;
            display: inline-block;
            box-shadow: 0 4px 15px var(--shadow-green);
            font-family: 'Tajawal', sans-serif;
            font-size: 13px;
        }

        .empty-state { text-align: center; padding: 60px 20px; color: var(--text-gray); }
        .empty-state i { font-size: 60px; color: var(--primary-teal); opacity: 0.3; margin-bottom: 20px; display: block; }
        .empty-state h3 { color: var(--text-white); margin-bottom: 10px; font-size: 24px; }
        .empty-state p { margin-bottom: 20px; }

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
        .footer-grid .contact-section .contact-item .value a { color: var(--text-white); }
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
            position: fixed; bottom: 30px; right: 30px;
            width: 50px; height: 50px; border-radius: 50%;
            background: var(--bg-glass);
            backdrop-filter: blur(16px);
            color: #fff;
            border: 1px solid var(--border-glass);
            font-size: 22px; cursor: pointer;
            opacity: 0; visibility: hidden; z-index: 999;
        }
        #backToTop.visible { opacity: 1; visibility: visible; }

        @media (max-width: 768px) {
            .header-center span { display: none; }
            .side-menu { width: 300px; right: -340px; }
            .filter-box form { flex-direction: column; }
            .filter-select, .filter-search { width: 100%; min-width: auto; }
            .services-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 20px; }
            .page-header h1 { font-size: 28px; }
        }
        @media (max-width: 480px) {
            .services-grid { grid-template-columns: 1fr; }
            .side-menu { width: 280px; right: -320px; }
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
            <li><a href="services.php" class="active"><i class="fas fa-tools"></i> الخدمات</a></li>
            <li><a href="providers.php"><i class="fas fa-users"></i> مقدمو الخدمات</a></li>
            <li><a href="about.php"><i class="fas fa-circle-info"></i> من نحن</a></li>
            <li><a href="contact.php"><i class="fas fa-envelope"></i> تواصل معنا</a></li>
            <?php if (isLoggedIn()): ?>
                <?php if (isAdmin()): ?>
                    <?php if (isSuperAdmin()): ?>
                        <li><a href="admin_managers.php"><i class="fas fa-user-tie"></i> إدارة المدراء</a></li>
                    <?php endif; ?>
                    <?php if (canManageProviders()): ?>
                        <li><a href="admin_providers.php"><i class="fas fa-users-cog"></i> مقدمي الخدمات</a></li>
                    <?php endif; ?>
                    <?php if (canManageClients()): ?>
                        <li><a href="admin_clients.php"><i class="fas fa-user-friends"></i> العملاء</a></li>
                    <?php endif; ?>
                    <?php if (canManageReviews()): ?>
                        <li><a href="admin_reviews.php"><i class="fas fa-star"></i> التقييمات</a></li>
                    <?php endif; ?>
                    <?php if (canManageBookings()): ?>
                        <li><a href="admin_bookings.php"><i class="fas fa-calendar-check"></i> الحجوزات</a></li>
                        <li><a href="admin_services.php"><i class="fas fa-tools"></i> الخدمات</a></li>
                        <li><a href="dashboard.php"><i class="fas fa-gauge-high"></i> لوحة التحكم</a></li>
                    <?php endif; ?>
                    <?php if (canManageSettings()): ?>
                        <li><a href="admin_settings.php"><i class="fas fa-cog"></i> الإعدادات</a></li>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if (isProvider() && isProviderApproved()): ?>
                    <li><a href="add_service.php"><i class="fas fa-plus-circle"></i> إضافة خدمة</a></li>
                    <li><a href="my_services.php"><i class="fas fa-briefcase"></i> خدماتي</a></li>
                    <li><a href="provider_orders.php"><i class="fas fa-clipboard-list"></i> طلباتي</a></li>
                <?php endif; ?>

                <?php if (isClient() || (isProvider() && !isProviderApproved())): ?>
                    <li><a href="my_bookings.php"><i class="fas fa-calendar-check"></i> حجوزاتي</a></li>
                <?php endif; ?>

                <li><a href="my_reviews.php"><i class="fas fa-star"></i> التقييمات</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> الملف الشخصي</a></li>
                <li><a href="logout.php" class="menu-logout"><i class="fas fa-right-from-bracket"></i> تسجيل خروج</a></li>
            <?php else: ?>
                <li><a href="login.php" class="menu-login"><i class="fas fa-right-to-bracket"></i> تسجيل الدخول</a></li>
                <li><a href="register.php" class="menu-register"><i class="fas fa-user-plus"></i> إنشاء حساب</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="page-header">
        <div class="container">
            <h1 data-aos="fade-up"><i class="fas fa-tools"></i> جميع الخدمات</h1>
            <p data-aos="fade-up" data-aos-delay="100">اختر الخدمة المناسبة لاحتياجاتك في ربك وكوستي</p>
        </div>
    </div>

    <section>
        <div class="container">
            <div class="filter-box" data-aos="fade-up">
                <form method="GET" action="">
                    <div class="filter-select">
                        <select name="category">
                            <option value="">جميع الفئات</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo ($category == $cat['category']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-select">
                        <select name="city">
                            <option value="">جميع المدن</option>
                            <option value="rabak" <?php echo ($city == 'rabak') ? 'selected' : ''; ?>>ربك</option>
                            <option value="kosti" <?php echo ($city == 'kosti') ? 'selected' : ''; ?>>كوستي</option>
                        </select>
                    </div>
                    <div class="filter-search">
                        <input type="text" name="search" placeholder="ابحث عن خدمة..." value="<?php echo htmlspecialchars($search); ?>">
                        <i class="fas fa-search"></i>
                    </div>
                    <button type="submit" class="btn-primary">بحث</button>
                    <?php if (!empty($category) || !empty($search) || !empty($city)): ?>
                        <a href="services.php" class="btn-outline">إعادة ضبط</a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if (count($services) == 0): ?>
                <div class="empty-state" data-aos="fade-up">
                    <i class="fas fa-tools"></i>
                    <h3>لا توجد خدمات مطابقة</h3>
                    <p>حاول تغيير معايير البحث</p>
                    <a href="services.php" class="btn-primary">عرض جميع الخدمات</a>
                </div>
            <?php else: ?>
                <div class="services-grid" data-aos="fade-up">
                    <?php foreach ($services as $service): ?>
                        <div class="card">
                            <div class="image-wrapper">
                                <?php if ($service['image'] && file_exists($service['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($service['image']); ?>" alt="<?php echo htmlspecialchars($service['name']); ?>">
                                <?php else: ?>
                                    <div class="icon-placeholder"><i class="fas <?php echo htmlspecialchars($service['icon']); ?>"></i></div>
                                <?php endif; ?>
                            </div>
                            <h3><?php echo htmlspecialchars($service['name']); ?></h3>
                            <p style="font-size:13px; margin-bottom:5px;">
                                <i class="fas fa-user" style="color:var(--primary-teal);"></i>
                                <?php echo htmlspecialchars($service['provider_name']); ?>
                            </p>
                            <p><?php echo htmlspecialchars($service['description']); ?></p>
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:10px;">
                                <span style="color:var(--primary-teal); font-weight:700; font-size:18px;">
                                    <?php echo number_format($service['price'], 2); ?> ج.س
                                </span>
                                <?php if (isLoggedIn()): ?>
                                    <a href="book_service.php?id=<?php echo $service['id']; ?>" class="btn-sm">اطلب الآن</a>
                                <?php else: ?>
                                    <a href="login.php?redirect=book_service.php?id=<?php echo $service['id']; ?>" class="btn-sm">اطلب الآن</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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
    </script>

</body>
</html>