<?php
require_once 'config.php';

$provider_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$provider_id) redirect('providers.php');

$stmt = $pdo->prepare("
    SELECT u.*, p.specialty, p.experience_years, p.rating, p.about, p.verified,
           p.working_hours, p.city as provider_city
    FROM users u
    LEFT JOIN providers p ON u.id = p.user_id
    WHERE u.id = ? AND u.user_type = 'provider' AND u.is_approved = 'approved' AND u.is_banned = 0
");
$stmt->execute(array($provider_id));
$provider = $stmt->fetch();
if (!$provider) redirect('providers.php');

$stmt = $pdo->prepare("SELECT * FROM services WHERE provider_id = ? AND status = 'active'");
$stmt->execute(array($provider_id));
$services = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT r.*, u.name as user_name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.provider_id = ? AND r.review_type = 'provider' ORDER BY r.created_at DESC LIMIT 5");
$stmt->execute(array($provider_id));
$reviews = $stmt->fetchAll();

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($provider['name']); ?> - بيتكم</title>
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
        .page-header p { color: var(--text-gray); font-size: 16px; }
        section { padding: 70px 0; }
        .provider-profile {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 40px;
            align-items: start;
        }
        .sidebar {
            background: var(--bg-glass);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border-glass);
            border-radius: 24px;
            padding: 30px;
            text-align: center;
        }
        .sidebar .avatar {
            width: 120px; height: 120px;
            border-radius: 50%;
            margin: 0 auto 15px;
            overflow: hidden;
            border: 4px solid var(--primary-green);
            background: rgba(11, 143, 122, 0.1);
            display: flex; align-items: center; justify-content: center;
            font-size: 60px;
            color: var(--primary-teal);
        }
        .sidebar .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .sidebar h3 { font-size: 22px; font-weight: 800; color: var(--text-white); }
        .sidebar .specialty { color: var(--text-gray); }
        .sidebar .experience { color: var(--text-gray); font-size: 14px; margin-top: 4px; }
        .sidebar .rating { margin: 10px 0; color: #FBBF24; }
        .sidebar .rating span { color: var(--text-gray); font-size: 14px; }
        .sidebar .verified-badge {
            display: inline-block;
            background: #10b981;
            color: #fff;
            padding: 4px 14px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 700;
            margin-top: 10px;
        }
        .sidebar .city-info { color: var(--text-gray); font-size: 14px; margin-top: 6px; }
        .sidebar .city-info i { color: var(--primary-teal); }
        .sidebar .working-hours {
            margin-top: 15px; padding: 12px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            border: 1px solid var(--border-light);
        }
        .sidebar .working-hours .label { color: var(--text-gray); font-size: 12px; }
        .sidebar .working-hours .value { color: var(--text-white); font-size: 14px; }
        .main-content .section-box {
            background: var(--bg-glass);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border-glass);
            border-radius: 24px;
            padding: 25px;
            margin-bottom: 25px;
        }
        .main-content .section-box h4 { color: var(--text-white); margin-bottom: 10px; }
        .main-content .section-box h4 i { color: var(--primary-teal); margin-left: 8px; }
        .main-content .section-box p { color: var(--text-gray); }
        .main-content .services-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .main-content .services-grid .service-item {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-glass);
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            transition: 0.3s;
        }
        .main-content .services-grid .service-item:hover { border-color: var(--primary-green); }
        .main-content .services-grid .service-item h5 { color: var(--text-white); font-size: 15px; margin-bottom: 5px; }
        .main-content .services-grid .service-item .price { color: var(--primary-teal); font-weight: 700; }
        .main-content .services-grid .service-item .qty-badge {
            display: inline-block;
            background: rgba(11, 143, 122, 0.12);
            color: var(--primary-teal);
            font-size: 10px;
            padding: 2px 10px;
            border-radius: 20px;
            margin-top: 5px;
            font-weight: 700;
        }
        .main-content .services-grid .service-item .btn-sm {
            background: var(--gradient-main);
            color: #fff; padding: 6px 18px;
            border-radius: 30px;
            font-weight: 600;
            display: inline-block;
            margin-top: 8px;
            font-size: 12px;
            font-family: 'Tajawal', sans-serif;
        }
        .main-content .reviews-list .review-item {
            border-bottom: 1px solid var(--border-light);
            padding: 12px 0;
        }
        .main-content .reviews-list .review-item:last-child { border-bottom: none; }
        .main-content .reviews-list .review-item .review-header {
            display: flex; justify-content: space-between;
            align-items: center;
        }
        .main-content .reviews-list .review-item .user { color: var(--text-white); font-weight: 600; }
        .main-content .reviews-list .review-item .stars { color: #FBBF24; }
        .main-content .reviews-list .review-item .comment { color: var(--text-gray); font-size: 14px; margin-top: 4px; }
        .main-content .reviews-list .review-item .date { color: var(--text-gray); font-size: 11px; margin-top: 4px; }
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
        @media (max-width: 992px) {
            .provider-profile { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .header-center span { display: none; }
            .side-menu { width: 300px; right: -340px; }
            .main-content .services-grid { grid-template-columns: 1fr; }
            .page-header h1 { font-size: 28px; }
        }
        @media (max-width: 480px) {
            .sidebar { padding: 20px; }
            .sidebar .avatar { width: 90px; height: 90px; }
            .main-content .section-box { padding: 18px; }
            .side-menu { width: 280px; right: -320px; }
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
            <li><a href="providers.php" class="active"><i class="fas fa-users"></i> مقدمو الخدمات</a></li>
            <li><a href="about.php"><i class="fas fa-circle-info"></i> من نحن</a></li>
            <li><a href="contact.php"><i class="fas fa-envelope"></i> تواصل معنا</a></li>
            <?php if (isLoggedIn()): ?>
                <?php if (isAdmin()): ?>
                    <li><a href="dashboard.php"><i class="fas fa-gauge-high"></i> لوحة التحكم</a></li>
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
                <li><a href="login.php"><i class="fas fa-right-to-bracket"></i> تسجيل الدخول</a></li>
                <li><a href="register.php"><i class="fas fa-user-plus"></i> إنشاء حساب</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="page-header">
        <div class="container">
            <h1 data-aos="fade-up"><?php echo htmlspecialchars($provider['name']); ?></h1>
            <p data-aos="fade-up" data-aos-delay="100"><?php echo htmlspecialchars(isset($provider['specialty']) ? $provider['specialty'] : ''); ?></p>
        </div>
    </div>

    <section>
        <div class="container">
            <div class="provider-profile" data-aos="fade-up">
                <div class="sidebar">
                    <div class="avatar">
                        <?php if ($provider['profile_image'] && file_exists($provider['profile_image'])): ?>
                            <img src="<?php echo htmlspecialchars($provider['profile_image']); ?>">
                        <?php else: ?>
                            <i class="fas fa-user-circle"></i>
                        <?php endif; ?>
                    </div>
                    <h3><?php echo htmlspecialchars($provider['name']); ?></h3>
                    <div class="specialty"><?php echo htmlspecialchars(isset($provider['specialty']) ? $provider['specialty'] : ''); ?></div>
                    <div class="experience"><i class="fas fa-briefcase"></i> <?php echo isset($provider['experience_years']) ? $provider['experience_years'] : 0; ?> سنوات خبرة</div>
                    <div class="rating">
                        <?php $rating = isset($provider['rating']) ? round($provider['rating']) : 0; for ($i = 0; $i < 5; $i++) { echo $i < $rating ? '★' : '☆'; } ?>
                        <span>(<?php echo isset($provider['rating']) ? $provider['rating'] : '0.0'; ?>)</span>
                    </div>
                    <?php if (!empty($provider['provider_city'])): ?>
                        <div class="city-info">
                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars(getCityName($provider['provider_city'])); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($provider['verified']): ?>
                        <span class="verified-badge"><i class="fas fa-check-circle"></i> موثوق</span>
                    <?php endif; ?>
                    <?php if ($provider['working_hours']): ?>
                        <div class="working-hours">
                            <div class="label">ساعات العمل</div>
                            <div class="value"><?php echo htmlspecialchars($provider['working_hours']); ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="main-content">
                    <div class="section-box">
                        <h4><i class="fas fa-user"></i> عن مقدم الخدمة</h4>
                        <p><?php echo !empty($provider['about']) ? htmlspecialchars($provider['about']) : 'لا توجد معلومات إضافية'; ?></p>
                    </div>

                    <div class="section-box">
                        <h4><i class="fas fa-briefcase"></i> الخدمات المقدمة</h4>
                        <?php if (count($services) == 0): ?>
                            <p>لا توجد خدمات متاحة حالياً</p>
                        <?php else: ?>
                            <div class="services-grid">
                                <?php foreach ($services as $s): ?>
                                    <div class="service-item">
                                        <h5><?php echo htmlspecialchars($s['name']); ?></h5>
                                        <div class="price"><?php echo number_format($s['price'], 2); ?> ج.س</div>
                                        <?php if (!empty($s['allow_quantity'])): ?>
                                            <span class="qty-badge"><i class="fas fa-calculator"></i> يقبل التعدد<?php echo $s['max_quantity'] ? ' (حتى ' . $s['max_quantity'] . ')' : ''; ?></span>
                                        <?php endif; ?>
                                        <br>
                                        <?php if (isLoggedIn()): ?>
                                            <a href="book_service.php?id=<?php echo $s['id']; ?>" class="btn-sm">اطلب الآن</a>
                                        <?php else: ?>
                                            <a href="login.php?redirect=book_service.php?id=<?php echo $s['id']; ?>" class="btn-sm">اطلب الآن</a>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="section-box">
                        <h4><i class="fas fa-star"></i> التقييمات</h4>
                        <?php if (count($reviews) == 0): ?>
                            <p>لا توجد تقييمات بعد</p>
                        <?php else: ?>
                            <div class="reviews-list">
                                <?php foreach ($reviews as $r): ?>
                                    <div class="review-item">
                                        <div class="review-header">
                                            <span class="user"><?php echo htmlspecialchars($r['user_name']); ?></span>
                                            <span class="stars"><?php echo str_repeat('★', $r['rating']) . str_repeat('☆', 5 - $r['rating']); ?></span>
                                        </div>
                                        <div class="comment"><?php echo htmlspecialchars($r['comment']); ?></div>
                                        <div class="date"><?php echo date('d/m/Y', strtotime($r['created_at'])); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
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
    </script>

</body>
</html>