<?php
require_once 'config.php';
if (!canManageProviders()) redirect('dashboard.php');

$provider_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$provider_id) redirect('admin_providers.php');

$stmt = $pdo->prepare("
    SELECT u.*, p.specialty, p.experience_years, p.rating, p.about,
           p.verified, p.working_hours, p.city as provider_city
    FROM users u
    LEFT JOIN providers p ON u.id = p.user_id
    WHERE u.id = ? AND u.user_type = 'provider'
");
$stmt->execute(array($provider_id));
$provider = $stmt->fetch();
if (!$provider) redirect('admin_providers.php');

$stmt = $pdo->prepare("SELECT * FROM services WHERE provider_id = ? AND status = 'active'");
$stmt->execute(array($provider_id));
$services = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total FROM reviews WHERE provider_id = ? AND review_type = 'provider'");
$stmt->execute(array($provider_id));
$rating_data = $stmt->fetch();
$avg_rating = round(isset($rating_data['avg_rating']) ? $rating_data['avg_rating'] : 0, 1);
$total_reviews = isset($rating_data['total']) ? $rating_data['total'] : 0;

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفاصيل مقدم الخدمة - بيتكم</title>
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
        .msg-box {
            padding: 14px 20px; border-radius: 12px;
            margin-bottom: 20px; font-weight: 600;
            display: flex; align-items: center; gap: 12px;
        }
        .msg-box.success { background: rgba(52, 168, 83, 0.15); border: 1px solid rgba(52, 168, 83, 0.2); color: #34a853; }
        .msg-box.error { background: rgba(234, 67, 53, 0.15); border: 1px solid rgba(234, 67, 53, 0.2); color: #ea4335; }
        .msg-box.info { background: rgba(59, 130, 246, 0.12); border: 1px solid rgba(59, 130, 246, 0.15); color: #3b82f6; }
        .provider-header {
            display: flex; align-items: center; gap: 25px;
            flex-wrap: wrap;
            background: var(--bg-glass);
            border: 1px solid var(--border-glass);
            border-radius: 16px;
            padding: 25px 30px; margin-bottom: 25px;
        }
        .provider-header .avatar {
            width: 80px; height: 80px; border-radius: 50%;
            overflow: hidden;
            border: 3px solid var(--primary-teal);
            flex-shrink: 0;
            background: rgba(11, 143, 122, 0.06);
            display: flex; align-items: center; justify-content: center;
        }
        .provider-header .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .provider-header .avatar i { font-size: 40px; color: var(--primary-teal); opacity: 0.5; }
        .provider-header .info h2 { font-size: 24px; font-weight: 800; color: var(--text-white); }
        .provider-header .info .specialty { color: var(--text-gray); font-size: 15px; }
        .provider-header .info .rating { color: #FBBF24; font-size: 16px; margin-top: 4px; }
        .provider-header .badges { display: flex; gap: 8px; flex-wrap: wrap; margin-right: auto; }
        .badge-status {
            padding: 4px 14px; border-radius: 30px;
            font-size: 12px; font-weight: 700; display: inline-block;
        }
        .badge-approved { background: rgba(52, 168, 83, 0.15); color: #34a853; }
        .badge-pending { background: rgba(251, 188, 4, 0.15); color: #fbbc04; }
        .badge-rejected { background: rgba(234, 67, 53, 0.15); color: #ea4335; }
        .badge-verified { background: rgba(52, 168, 83, 0.15); color: #34a853; }
        .badge-unverified { background: rgba(234, 67, 53, 0.15); color: #ea4335; }
        .badge-banned { background: rgba(234, 67, 53, 0.15); color: #ea4335; }
        .badge-active { background: rgba(52, 168, 83, 0.15); color: #34a853; }
        .actions-box {
            background: var(--bg-glass);
            border: 1px solid var(--border-glass);
            border-radius: 16px;
            padding: 20px 25px; margin-bottom: 25px;
        }
        .actions-box h3 {
            font-size: 16px; font-weight: 700;
            color: var(--text-white); margin-bottom: 12px;
        }
        .actions-box h3 i { color: var(--primary-teal); margin-left: 8px; }
        .actions-box .actions-group { display: flex; gap: 10px; flex-wrap: wrap; }
        .btn-sm {
            padding: 8px 18px; border-radius: 30px;
            font-weight: 600; border: none;
            cursor: pointer; display: inline-block;
            font-size: 13px; font-family: 'Tajawal', sans-serif;
        }
        .btn-sm.danger { background: rgba(234, 67, 53, 0.15); color: #ea4335; }
        .btn-sm.success { background: rgba(52, 168, 83, 0.15); color: #34a853; }
        .btn-sm.warning { background: rgba(251, 188, 4, 0.15); color: #fbbc04; }
        .btn-sm.primary { background: rgba(11, 143, 122, 0.15); color: var(--primary-teal); }
        .details-grid {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 15px;
            background: var(--bg-glass);
            border: 1px solid var(--border-glass);
            border-radius: 16px;
            padding: 25px; margin-bottom: 25px;
        }
        .details-grid .detail-item {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-glass);
            border-radius: 12px;
            padding: 14px 18px;
        }
        .details-grid .detail-item.full { grid-column: 1 / -1; }
        .details-grid .detail-item .label {
            font-size: 12px; color: var(--text-gray);
            font-weight: 500; display: block; margin-bottom: 3px;
        }
        .details-grid .detail-item .label i { color: var(--primary-teal); margin-left: 5px; }
        .details-grid .detail-item .value {
            font-size: 16px; font-weight: 600; color: var(--text-white);
        }
        .image-preview img {
            max-width: 250px; max-height: 180px;
            border-radius: 8px;
            border: 1px solid var(--border-glass);
            object-fit: cover;
        }
        .no-image { color: var(--text-gray); font-size: 13px; }
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
            .provider-header { flex-direction: column; text-align: center; }
            .provider-header .badges { margin-right: 0; justify-content: center; }
            .details-grid { grid-template-columns: 1fr; }
            .page-header h1 { font-size: 28px; }
        }
        @media (max-width: 480px) {
            .side-menu { width: 280px; right: -320px; }
            .header-center { display: none; }
            .provider-header { padding: 20px; }
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
            <?php if (isAdmin()): ?>
                <li><a href="dashboard.php" class="active"><i class="fas fa-gauge-high"></i> لوحة التحكم</a></li>
            <?php endif; ?>
            <li><a href="my_reviews.php"><i class="fas fa-star"></i> التقييمات</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> الملف الشخصي</a></li>
            <li><a href="logout.php" class="menu-logout"><i class="fas fa-right-from-bracket"></i> تسجيل خروج</a></li>
        </ul>
    </nav>

    <div class="page-header">
        <div class="container">
            <h1 data-aos="fade-up"><i class="fas fa-user-cog"></i> تفاصيل مقدم الخدمة</h1>
            <p data-aos="fade-up" data-aos-delay="100">مراجعة كاملة لبيانات ومستندات مقدم الخدمة</p>
        </div>
    </div>

    <section>
        <div class="container">

            <?php if ($provider['is_approved'] == 'pending'): ?>
                <div class="msg-box info" data-aos="fade-up">
                    <i class="fas fa-clock"></i> هذا الحساب قيد المراجعة، راجع البيانات ثم اضغط قبول أو رفض.
                </div>
            <?php elseif ($provider['is_approved'] == 'approved'): ?>
                <div class="msg-box success" data-aos="fade-up">
                    <i class="fas fa-check-circle"></i> هذا الحساب مفعل.
                </div>
            <?php elseif ($provider['is_approved'] == 'rejected'): ?>
                <div class="msg-box error" data-aos="fade-up">
                    <i class="fas fa-times-circle"></i> هذا الحساب مرفوض.
                </div>
            <?php endif; ?>

            <div class="provider-header" data-aos="fade-up">
                <div class="avatar">
                    <?php if ($provider['profile_image'] && file_exists($provider['profile_image'])): ?>
                        <img src="<?php echo htmlspecialchars($provider['profile_image']); ?>">
                    <?php else: ?>
                        <i class="fas fa-user-circle"></i>
                    <?php endif; ?>
                </div>
                <div class="info">
                    <h2><?php echo htmlspecialchars($provider['name']); ?></h2>
                    <div class="specialty"><i class="fas fa-briefcase"></i> <?php echo htmlspecialchars(isset($provider['specialty']) ? $provider['specialty'] : '-'); ?></div>
                    <div class="rating">
                        <?php echo str_repeat('★', round($avg_rating)) . str_repeat('☆', 5 - round($avg_rating)); ?>
                        <span style="color:var(--text-gray); font-size:14px;"> (<?php echo $avg_rating; ?> / 5 - <?php echo $total_reviews; ?> تقييم)</span>
                    </div>
                </div>
                <div class="badges">
                    <span class="badge-status badge-<?php echo $provider['is_approved']; ?>">
                        <?php
                            $labels = array('approved'=>'مفعل', 'pending'=>'قيد المراجعة', 'rejected'=>'مرفوض');
                            echo isset($labels[$provider['is_approved']]) ? $labels[$provider['is_approved']] : $provider['is_approved'];
                        ?>
                    </span>
                    <span class="badge-status <?php echo isset($provider['verified']) && $provider['verified'] == 1 ? 'badge-verified' : 'badge-unverified'; ?>">
                        <?php echo isset($provider['verified']) && $provider['verified'] == 1 ? 'موثوق' : 'غير موثوق'; ?>
                    </span>
                    <span class="badge-status <?php echo $provider['is_banned'] == 1 ? 'badge-banned' : 'badge-active'; ?>">
                        <?php echo $provider['is_banned'] == 1 ? 'محظور' : 'نشط'; ?>
                    </span>
                </div>
            </div>

            <div class="actions-box" data-aos="fade-up">
                <h3><i class="fas fa-cog"></i> الإجراءات المتاحة</h3>
                <div class="actions-group">
                    <?php if ($provider['is_approved'] == 'pending' && canApproveProviders()): ?>
                        <a href="admin_providers.php?action=approve&id=<?php echo $provider['id']; ?>" class="btn-sm success" onclick="return confirm('قبول هذا مقدم الخدمة؟')">
                            <i class="fas fa-check"></i> قبول
                        </a>
                        <a href="admin_providers.php?action=reject&id=<?php echo $provider['id']; ?>" class="btn-sm danger" onclick="return confirm('رفض هذا مقدم الخدمة؟')">
                            <i class="fas fa-times"></i> رفض
                        </a>
                    <?php endif; ?>

                    <?php if ($provider['is_approved'] == 'approved'): ?>
                        <?php if (isset($provider['verified']) && $provider['verified'] == 1): ?>
                            <a href="admin_providers.php?action=unverify&id=<?php echo $provider['id']; ?>" class="btn-sm warning">إلغاء التوثيق</a>
                        <?php else: ?>
                            <a href="admin_providers.php?action=verify&id=<?php echo $provider['id']; ?>" class="btn-sm success">توثيق</a>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($provider['is_banned'] == 1): ?>
                        <a href="admin_providers.php?action=unban&id=<?php echo $provider['id']; ?>" class="btn-sm success">إلغاء الحظر</a>
                    <?php else: ?>
                        <a href="admin_providers.php?action=ban&id=<?php echo $provider['id']; ?>" class="btn-sm danger">حظر</a>
                    <?php endif; ?>

                    <?php if (canApproveProviders()): ?>
                        <a href="admin_providers.php?action=delete&id=<?php echo $provider['id']; ?>" class="btn-sm danger" onclick="return confirm('حذف هذا المقدم بالكامل؟')">حذف</a>
                    <?php endif; ?>

                    <a href="admin_providers.php" class="btn-sm primary"><i class="fas fa-arrow-right"></i> العودة</a>
                </div>
            </div>

            <div class="details-grid" data-aos="fade-up">
                <div class="detail-item">
                    <span class="label"><i class="fas fa-user"></i> الاسم الكامل</span>
                    <span class="value"><?php echo htmlspecialchars($provider['name']); ?></span>
                </div>
                <div class="detail-item">
                    <span class="label"><i class="fas fa-envelope"></i> البريد الإلكتروني</span>
                    <span class="value"><?php echo htmlspecialchars($provider['email']); ?></span>
                </div>
                <div class="detail-item">
                    <span class="label"><i class="fas fa-phone"></i> رقم الهاتف</span>
                    <span class="value"><?php echo htmlspecialchars($provider['phone']); ?></span>
                </div>
                <div class="detail-item">
                    <span class="label"><i class="fas fa-city"></i> المدينة</span>
                    <span class="value"><?php echo htmlspecialchars(getCityName(isset($provider['provider_city']) ? $provider['provider_city'] : '')); ?></span>
                </div>
                <div class="detail-item">
                    <span class="label"><i class="fas fa-map-marker-alt"></i> المنطقة</span>
                    <span class="value"><?php echo htmlspecialchars($provider['region']); ?></span>
                </div>
                <div class="detail-item">
                    <span class="label"><i class="fas fa-calendar-alt"></i> العمر</span>
                    <span class="value"><?php echo $provider['age']; ?> سنة</span>
                </div>
                <div class="detail-item">
                    <span class="label"><i class="fas fa-briefcase"></i> التخصص</span>
                    <span class="value"><?php echo htmlspecialchars(isset($provider['specialty']) ? $provider['specialty'] : '-'); ?></span>
                </div>
                <div class="detail-item">
                    <span class="label"><i class="fas fa-clock"></i> سنوات الخبرة</span>
                    <span class="value"><?php echo isset($provider['experience_years']) ? $provider['experience_years'] : 0; ?> سنة</span>
                </div>
                <div class="detail-item">
                    <span class="label"><i class="fas fa-map-pin"></i> نطاق الأحياء</span>
                    <span class="value"><?php echo !empty($provider['working_area']) ? htmlspecialchars($provider['working_area']) : 'غير محدد'; ?></span>
                </div>
                <div class="detail-item">
                    <span class="label"><i class="fas fa-clock"></i> ساعات العمل</span>
                    <span class="value"><?php echo !empty($provider['working_hours']) ? htmlspecialchars($provider['working_hours']) : 'غير محدد'; ?></span>
                </div>

                <div class="detail-item full">
                    <span class="label"><i class="fas fa-id-card"></i> صورة الرقم الوطني (للمدير فقط)</span>
                    <div class="image-preview">
                        <?php if (!empty($provider['national_id_image']) && file_exists($provider['national_id_image'])): ?>
                            <img src="<?php echo htmlspecialchars($provider['national_id_image']); ?>" alt="الرقم الوطني">
                        <?php else: ?>
                            <span class="no-image"><i class="fas fa-times-circle"></i> لم يتم رفع صورة الرقم الوطني</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="detail-item full">
                    <span class="label"><i class="fas fa-certificate"></i> شهادة الخبرة</span>
                    <div class="image-preview">
                        <?php if (!empty($provider['certificate_image']) && file_exists($provider['certificate_image'])): ?>
                            <img src="<?php echo htmlspecialchars($provider['certificate_image']); ?>" alt="شهادة الخبرة">
                        <?php else: ?>
                            <span class="no-image"><i class="fas fa-times-circle"></i> لم يتم رفع شهادة الخبرة</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="detail-item full">
                    <span class="label"><i class="fas fa-tools"></i> الخدمات المقدمة</span>
                    <div class="value">
                        <?php if (count($services) > 0): ?>
                            <?php foreach ($services as $s): ?>
                                <span style="display:inline-block; background:rgba(11,143,122,0.08); color:var(--primary-teal); padding:4px 14px; border-radius:20px; font-size:13px; margin:3px 0;">
                                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($s['name']); ?>
                                    (<?php echo number_format($s['price'], 2); ?> ج.س)
                                </span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span style="color:var(--text-gray); font-size:14px;">لا توجد خدمات مضافة</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-grid">
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