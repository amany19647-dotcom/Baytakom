<?php
require_once 'config.php';
if (!canManageSettings()) redirect('dashboard.php');

// جلب محفظة المنصة
$stmt = $pdo->query("SELECT * FROM wallets WHERE type = 'platform' LIMIT 1");
$platform_wallet = $stmt->fetch();

if (!$platform_wallet) {
    die('محفظة المنصة غير موجودة');
}

$wallet_id = $platform_wallet['id'];

// جلب نسبة العمولة
$stmt = $pdo->query("SELECT commission_rate FROM commission_settings LIMIT 1");
$comm = $stmt->fetch();
$commission_rate = $comm ? floatval($comm['commission_rate']) : 10.00;

// ============ الإحصائيات ============
$stmt = $pdo->query("SELECT IFNULL(SUM(amount), 0) FROM transactions WHERE type = 'deposit'");
$total_deposits = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT IFNULL(SUM(amount), 0) FROM transactions WHERE type = 'commission'");
$total_commissions = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'completed'");
$completed_bookings = $stmt->fetchColumn();

// ============ الفلترة ============
$filter_type = isset($_GET['type']) ? $_GET['type'] : 'all';
$allowed_types = array('all', 'deposit', 'commission', 'transfer', 'withdrawal');
if (!in_array($filter_type, $allowed_types)) $filter_type = 'all';

$sql = "SELECT t.*, b.id as bk_id, u.name as client_name
        FROM transactions t 
        LEFT JOIN bookings b ON t.booking_id = b.id 
        LEFT JOIN users u ON b.user_id = u.id 
        WHERE t.wallet_id = ?";
$params = array($wallet_id);

if ($filter_type !== 'all') {
    $sql .= " AND t.type = ?";
    $params[] = $filter_type;
}

$sql .= " ORDER BY t.created_at DESC LIMIT 200";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>محفظة المنصة - بيتكم</title>
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

        .balance-card {
            background: linear-gradient(135deg, rgba(11, 143, 122, 0.2), rgba(18, 168, 143, 0.08));
            border: 1px solid rgba(11, 143, 122, 0.3);
            border-radius: 24px;
            padding: 35px;
            margin-bottom: 25px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .balance-card::before {
            content: '';
            position: absolute;
            top: -50%; right: -20%;
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(11, 143, 122, 0.15), transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        .balance-card .balance-icon {
            font-size: 50px;
            color: var(--primary-teal);
            margin-bottom: 10px;
            opacity: 0.8;
            position: relative;
        }
        .balance-card .balance-label {
            color: var(--text-gray);
            font-size: 15px;
            margin-bottom: 8px;
            position: relative;
        }
        .balance-card .balance-value {
            font-size: 44px;
            font-weight: 800;
            color: var(--primary-teal);
            letter-spacing: 1px;
            position: relative;
            text-shadow: 0 0 30px rgba(18, 168, 143, 0.3);
        }
        .balance-card .balance-value small {
            font-size: 20px;
            color: var(--text-gray);
            font-weight: 500;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 18px;
            margin-bottom: 30px;
        }
        .stat-box {
            background: var(--bg-glass);
            backdrop-filter: blur(14px);
            border: 1px solid var(--border-glass);
            border-radius: 18px;
            padding: 22px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s;
        }
        .stat-box:hover { transform: translateY(-3px); border-color: rgba(11, 143, 122, 0.3); }
        .stat-box .stat-icon {
            width: 55px; height: 55px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }
        .stat-box .stat-icon.deposit { background: rgba(52, 168, 83, 0.15); color: #34a853; }
        .stat-box .stat-icon.commission { background: rgba(251, 188, 4, 0.15); color: #fbbc04; }
        .stat-box .stat-icon.count { background: rgba(139, 92, 246, 0.15); color: #8b5cf6; }
        .stat-box .stat-info { flex: 1; }
        .stat-box .stat-info .stat-value {
            font-size: 22px;
            font-weight: 800;
            color: var(--text-white);
        }
        .stat-box .stat-info .stat-value small { font-size: 13px; color: var(--text-gray); font-weight: 500; }
        .stat-box .stat-info .stat-label {
            font-size: 13px;
            color: var(--text-gray);
            margin-top: 2px;
        }

        .filter-bar {
            background: var(--bg-glass);
            border: 1px solid var(--border-glass);
            border-radius: 16px;
            padding: 12px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .filter-bar a {
            padding: 10px 22px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-gray);
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .filter-bar a:hover { background: rgba(11, 143, 122, 0.08); color: var(--text-white); }
        .filter-bar a.active {
            background: var(--gradient-main);
            color: #fff;
            box-shadow: 0 4px 15px var(--shadow-green);
        }

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

        .tx-type-badge {
            padding: 5px 14px; border-radius: 30px;
            font-size: 12px; font-weight: 700; display: inline-flex;
            align-items: center; gap: 5px;
        }
        .tx-deposit { background: rgba(52, 168, 83, 0.15); color: #34a853; }
        .tx-commission { background: rgba(251, 188, 4, 0.15); color: #fbbc04; }
        .tx-transfer { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
        .tx-withdrawal { background: rgba(234, 67, 53, 0.15); color: #ea4335; }

        .amount-cell { font-weight: 700; font-size: 14px; }
        .amount-positive { color: #34a853; }
        .amount-negative { color: #ea4335; }

        .empty-state { text-align: center; padding: 50px 20px; color: var(--text-gray); }
        .empty-state i { font-size: 60px; color: var(--primary-teal); opacity: 0.3; margin-bottom: 15px; display: block; }
        .empty-state h3 { color: var(--text-white); margin-bottom: 8px; }

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
            table th, table td { padding: 8px 10px; font-size: 11px; }
            .balance-card .balance-value { font-size: 32px; }
            .balance-card { padding: 25px; }
            .page-header h1 { font-size: 28px; }
        }
        @media (max-width: 480px) {
            .side-menu { width: 280px; right: -320px; }
            .header-center { display: none; }
            .filter-bar { flex-direction: column; }
            .filter-bar a { justify-content: center; }
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
            <h1 data-aos="fade-up"><i class="fas fa-wallet"></i> محفظة المنصة</h1>
            <p data-aos="fade-up" data-aos-delay="100">إدارة الرصيد والكشوفات المالية للمنصة</p>
        </div>
    </div>

    <section>
        <div class="container">

            <!-- بطاقة الرصيد -->
            <div class="balance-card" data-aos="fade-up">
                <div class="balance-icon"><i class="fas fa-wallet"></i></div>
                <div class="balance-label">الرصيد الحالي في محفظة المنصة</div>
                <div class="balance-value">
                    <?php echo number_format($platform_wallet['balance'], 2); ?>
                    <small>ج.س</small>
                </div>
            </div>

            <!-- الإحصائيات -->
            <div class="stats-grid" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-box">
                    <div class="stat-icon deposit"><i class="fas fa-arrow-down"></i></div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($total_deposits, 2); ?> <small>ج.س</small></div>
                        <div class="stat-label">إجمالي المستلم</div>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="stat-icon commission"><i class="fas fa-percent"></i></div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($total_commissions, 2); ?> <small>ج.س</small></div>
                        <div class="stat-label">إجمالي العمولات (<?php echo $commission_rate; ?>%)</div>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="stat-icon count"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $completed_bookings; ?></div>
                        <div class="stat-label">حجوزات مكتملة</div>
                    </div>
                </div>
            </div>

            <!-- الفلترة -->
            <div class="filter-bar" data-aos="fade-up">
                <a href="?type=all" class="<?php echo $filter_type == 'all' ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i> الكل
                </a>
                <a href="?type=deposit" class="<?php echo $filter_type == 'deposit' ? 'active' : ''; ?>">
                    <i class="fas fa-arrow-down"></i> إيداعات
                </a>
                <a href="?type=commission" class="<?php echo $filter_type == 'commission' ? 'active' : ''; ?>">
                    <i class="fas fa-percent"></i> عمولات
                </a>
                <a href="?type=transfer" class="<?php echo $filter_type == 'transfer' ? 'active' : ''; ?>">
                    <i class="fas fa-share"></i> تحويلات
                </a>
            </div>

            <!-- الجدول -->
            <?php if (count($transactions) == 0): ?>
                <div class="empty-state" data-aos="fade-up">
                    <i class="fas fa-receipt"></i>
                    <h3>لا توجد حركات مالية</h3>
                    <p>لم يتم تسجيل أي عمليات حتى الآن</p>
                </div>
            <?php else: ?>
                <div class="table-responsive" data-aos="fade-up">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>النوع</th>
                                <th>المبلغ</th>
                                <th>الرصيد بعد</th>
                                <th>الحجز</th>
                                <th>الوصف</th>
                                <th>التاريخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $tx): 
                                $type_labels = array(
                                    'deposit' => 'إيداع',
                                    'commission' => 'عمولة',
                                    'transfer' => 'تحويل',
                                    'withdrawal' => 'سحب',
                                    'refund' => 'استرجاع'
                                );
                                $type_icons = array(
                                    'deposit' => 'fa-arrow-down',
                                    'commission' => 'fa-percent',
                                    'transfer' => 'fa-share',
                                    'withdrawal' => 'fa-arrow-up',
                                    'refund' => 'fa-undo'
                                );
                                $is_positive = in_array($tx['type'], array('deposit', 'commission'));
                            ?>
                                <tr>
                                    <td><?php echo $tx['id']; ?></td>
                                    <td>
                                        <span class="tx-type-badge tx-<?php echo $tx['type']; ?>">
                                            <i class="fas <?php echo isset($type_icons[$tx['type']]) ? $type_icons[$tx['type']] : 'fa-circle'; ?>"></i>
                                            <?php echo isset($type_labels[$tx['type']]) ? $type_labels[$tx['type']] : $tx['type']; ?>
                                        </span>
                                    </td>
                                    <td class="amount-cell <?php echo $is_positive ? 'amount-positive' : 'amount-negative'; ?>">
                                        <?php echo ($is_positive ? '+' : '-') . number_format($tx['amount'], 2); ?> ج.س
                                    </td>
                                    <td class="amount-cell" style="color: var(--text-white);">
                                        <?php echo number_format($tx['balance_after'], 2); ?> ج.س
                                    </td>
                                    <td>
                                        <?php if ($tx['booking_id']): ?>
                                            <a href="admin_bookings.php" style="color:var(--primary-teal); font-weight:700;">
                                                #<?php echo $tx['booking_id']; ?>
                                            </a>
                                        <?php else: ?>
                                            <span style="color:var(--text-gray);">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:right; max-width:250px;">
                                        <?php echo htmlspecialchars($tx['description']); ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($tx['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

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