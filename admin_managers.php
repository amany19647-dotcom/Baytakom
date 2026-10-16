<?php
require_once 'config.php';
if (!isSuperAdmin()) redirect('dashboard.php');

if (isset($_GET['action']) && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action == 'delete') {
        if ($user_id != $_SESSION['user_id']) {
            $pdo->prepare("DELETE FROM users WHERE id = ? AND user_type = 'admin'")->execute(array($user_id));
            $_SESSION['admin_message'] = 'تم حذف المدير';
            $_SESSION['admin_message_type'] = 'info';
        } else {
            $_SESSION['admin_message'] = 'لا يمكنك حذف حسابك';
            $_SESSION['admin_message_type'] = 'error';
        }
    } elseif ($action == 'ban') {
        if ($user_id != $_SESSION['user_id']) {
            $pdo->prepare("UPDATE users SET is_banned = 1 WHERE id = ? AND user_type = 'admin'")->execute(array($user_id));
            $_SESSION['admin_message'] = 'تم حظر المدير';
            $_SESSION['admin_message_type'] = 'error';
        }
    } elseif ($action == 'unban') {
        $pdo->prepare("UPDATE users SET is_banned = 0 WHERE id = ? AND user_type = 'admin'")->execute(array($user_id));
        $_SESSION['admin_message'] = 'تم إلغاء الحظر';
        $_SESSION['admin_message_type'] = 'success';
    }
    redirect('admin_managers.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $admin_role = $_POST['admin_role'];

    if (empty($name) || empty($username) || empty($email) || empty($password)) {
        $error = 'يرجى ملء جميع الحقول المطلوبة';
    } elseif (!in_array($admin_role, array('super_admin', 'providers', 'clients', 'reviews'))) {
        $error = 'دور غير صالح';
    } elseif (strlen($password) < 6) {
        $error = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute(array($email, $username));
        if ($stmt->fetch()) {
            $error = 'البريد أو اسم المستخدم موجود بالفعل';
        } else {
            $hashed = md5($password);
            $stmt = $pdo->prepare("INSERT INTO users (name, username, email, phone, region, age, service_type, password, user_type, admin_role, is_approved, city) VALUES (?, ?, ?, ?, 'ربك', 30, 'إدارة', ?, 'admin', ?, 'approved', 'rabak')");
            if ($stmt->execute(array($name, $username, $email, $phone, $hashed, $admin_role))) {
                $success = 'تم إضافة المدير بنجاح';
            } else {
                $error = 'حدث خطأ أثناء الإضافة';
            }
        }
    }
}

$stmt = $pdo->query("SELECT * FROM users WHERE user_type = 'admin' ORDER BY created_at DESC");
$managers = $stmt->fetchAll();

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المدراء - بيتكم</title>
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
        .add-manager-box {
            background: var(--bg-glass);
            border: 1px solid var(--border-glass);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
        }
        .add-manager-box h3 {
            font-size: 20px; color: var(--text-white);
            margin-bottom: 20px;
        }
        .add-manager-box h3 i { color: var(--primary-teal); margin-left: 8px; }
        .grid-2 {
            display: grid; grid-template-columns: 1fr 1fr; gap: 15px;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block; font-size: 14px; font-weight: 600;
            color: rgba(255, 255, 255, 0.8); margin-bottom: 8px;
        }
        .form-group .input-wrapper { position: relative; }
        .form-group .input-wrapper i {
            position: absolute; right: 16px; top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.3); font-size: 18px;
            pointer-events: none;
        }
        .form-group input,
        .form-group select {
            width: 100%; padding: 15px 50px 15px 20px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-white); font-size: 16px;
            transition: 0.3s; outline: none;
            font-family: 'Tajawal', sans-serif;
        }
        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--primary-green);
            background: rgba(255, 255, 255, 0.08);
        }
        .form-group input::placeholder { color: rgba(255, 255, 255, 0.3); }
        .form-group select option { background: #0D1F1B; color: var(--text-white); }
        .btn-register {
            width: 100%; padding: 14px;
            background: var(--gradient-main);
            border: none; border-radius: 14px;
            color: white; font-size: 16px; font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 25px var(--shadow-green);
            font-family: 'Tajawal', sans-serif;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            margin-top: 10px;
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
        .badge-role {
            padding: 4px 14px; border-radius: 30px;
            font-size: 11px; font-weight: 700; display: inline-block;
            background: rgba(11, 143, 122, 0.15); color: var(--primary-teal);
        }
        .badge-status {
            padding: 4px 14px; border-radius: 30px;
            font-size: 11px; font-weight: 700; display: inline-block;
        }
        .badge-banned { background: rgba(234, 67, 53, 0.15); color: #ea4335; }
        .badge-active { background: rgba(52, 168, 83, 0.15); color: #34a853; }
        .btn-sm {
            padding: 5px 14px; border-radius: 30px;
            font-weight: 600; border: none;
            cursor: pointer; display: inline-block;
            font-size: 11px; font-family: 'Tajawal', sans-serif;
        }
        .btn-sm.danger { background: rgba(234, 67, 53, 0.15); color: #ea4335; }
        .btn-sm.success { background: rgba(52, 168, 83, 0.15); color: #34a853; }
        .msg-box {
            padding: 14px 20px; border-radius: 12px;
            margin-bottom: 20px; font-weight: 600;
            display: flex; align-items: center; gap: 12px;
        }
        .msg-box.success { background: rgba(52, 168, 83, 0.15); border: 1px solid rgba(52, 168, 83, 0.2); color: #34a853; }
        .msg-box.error { background: rgba(234, 67, 53, 0.15); border: 1px solid rgba(234, 67, 53, 0.2); color: #ea4335; }
        .msg-box.info { background: rgba(59, 130, 246, 0.12); border: 1px solid rgba(59, 130, 246, 0.15); color: #3b82f6; }
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
            .grid-2 { grid-template-columns: 1fr; }
            table th, table td { padding: 8px 10px; font-size: 11px; }
            .page-header h1 { font-size: 28px; }
        }
        @media (max-width: 480px) {
            .side-menu { width: 280px; right: -320px; }
            .header-center { display: none; }
            .add-manager-box { padding: 20px; }
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
            <h1 data-aos="fade-up"><i class="fas fa-user-tie"></i> إدارة المدراء</h1>
            <p data-aos="fade-up" data-aos-delay="100">إضافة وحذف وتعديل صلاحيات المدراء</p>
        </div>
    </div>

    <section>
        <div class="container">
            <?php if (isset($_SESSION['admin_message'])): ?>
                <div class="msg-box <?php echo $_SESSION['admin_message_type']; ?>">
                    <i class="fas fa-<?php echo $_SESSION['admin_message_type'] == 'success' ? 'check-circle' : ($_SESSION['admin_message_type'] == 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
                    <?php echo $_SESSION['admin_message']; unset($_SESSION['admin_message']); unset($_SESSION['admin_message_type']); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="msg-box error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
                <div class="msg-box success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <?php endif; ?>

            <div class="add-manager-box" data-aos="fade-up">
                <h3><i class="fas fa-plus-circle"></i> إضافة مدير جديد</h3>

                <form method="POST">
                    <div class="grid-2">
                        <div class="form-group">
                            <label>الاسم الكامل</label>
                            <div class="input-wrapper">
                                <input type="text" name="name" placeholder="الاسم" required>
                                <i class="fas fa-user"></i>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>اسم المستخدم</label>
                            <div class="input-wrapper">
                                <input type="text" name="username" placeholder="username" required>
                                <i class="fas fa-at"></i>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>البريد الإلكتروني</label>
                            <div class="input-wrapper">
                                <input type="email" name="email" placeholder="example@email.com" required>
                                <i class="fas fa-envelope"></i>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>رقم الهاتف</label>
                            <div class="input-wrapper">
                                <input type="tel" name="phone" placeholder="09xxxxxxxx">
                                <i class="fas fa-phone"></i>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>كلمة المرور</label>
                            <div class="input-wrapper">
                                <input type="password" name="password" placeholder="••••••••" required minlength="6">
                                <i class="fas fa-lock"></i>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>الدور</label>
                            <div class="input-wrapper">
                                <select name="admin_role" required>
                                    <option value="">اختر الدور</option>
                                    <option value="super_admin">مدير عام (كل الصلاحيات)</option>
                                    <option value="providers">مدير مقدمي الخدمات</option>
                                    <option value="clients">مدير العملاء</option>
                                    <option value="reviews">مدير التقييمات</option>
                                </select>
                                <i class="fas fa-user-tag"></i>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn-register"><i class="fas fa-user-plus"></i> إضافة المدير</button>
                </form>
            </div>

            <h3 style="font-size:20px; color:var(--text-white); margin-bottom:15px;" data-aos="fade-up">
                <i class="fas fa-users-cog" style="color:var(--primary-teal);"></i> المدراء الحاليون (<?php echo count($managers); ?>)
            </h3>

            <div class="table-responsive" data-aos="fade-up">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الاسم</th>
                            <th>البريد</th>
                            <th>الدور</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($managers as $m): ?>
                            <tr>
                                <td><?php echo $m['id']; ?></td>
                                <td><?php echo htmlspecialchars($m['name']); ?></td>
                                <td><?php echo htmlspecialchars($m['email']); ?></td>
                                <td>
                                    <span class="badge-role">
                                        <?php echo htmlspecialchars(getAdminRoleLabel(isset($m['admin_role']) ? $m['admin_role'] : 'main')); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge-status <?php echo $m['is_banned'] == 1 ? 'badge-banned' : 'badge-active'; ?>">
                                        <?php echo $m['is_banned'] == 1 ? 'محظور' : 'نشط'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display:flex; gap:3px; flex-wrap:wrap; justify-content:center;">
                                        <?php if ($m['id'] != $_SESSION['user_id']): ?>
                                            <?php if ($m['is_banned'] == 1): ?>
                                                <a href="?action=unban&id=<?php echo $m['id']; ?>" class="btn-sm success" onclick="return confirm('إلغاء الحظر؟')">إلغاء الحظر</a>
                                            <?php else: ?>
                                                <a href="?action=ban&id=<?php echo $m['id']; ?>" class="btn-sm danger" onclick="return confirm('حظر هذا المدير؟')">حظر</a>
                                            <?php endif; ?>
                                            <a href="?action=delete&id=<?php echo $m['id']; ?>" class="btn-sm danger" onclick="return confirm('حذف هذا المدير نهائياً؟')">حذف</a>
                                        <?php else: ?>
                                            <span style="color:var(--text-gray); font-size:12px;">(أنت)</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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