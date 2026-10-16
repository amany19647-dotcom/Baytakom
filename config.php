<?php
// config.php - إعدادات قاعدة البيانات والجلسات والدوال المساعدة

define('DB_HOST', 'localhost');
define('DB_NAME', 'baitkom_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// ============================================
// المدن المتاحة (قابلة للتوسع مستقبلاً)
// ============================================
$AVAILABLE_CITIES = array(
    'rabak' => 'ربك',
    'kosti' => 'كوستي'
);

// ============================================
// الاتصال بقاعدة البيانات (PDO)
// ============================================
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("فشل الاتصال بقاعدة البيانات: " . $e->getMessage());
}

// ============================================
// بدء الجلسة
// ============================================
if (!isset($_SESSION)) {
    session_start();
}

// ============================================
// دوال التحقق من الصلاحيات
// ============================================
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['user_type'] == 'admin';
}

function isProvider() {
    return isLoggedIn() && $_SESSION['user_type'] == 'provider';
}

function isClient() {
    return isLoggedIn() && $_SESSION['user_type'] == 'receiver';
}

function isSuperAdmin() {
    return isAdmin() && isset($_SESSION['admin_role']) && $_SESSION['admin_role'] == 'super_admin';
}

function isMainAdmin() {
    return isAdmin() && isset($_SESSION['admin_role']) && in_array($_SESSION['admin_role'], array('super_admin', 'main'));
}

function isProvidersAdmin() {
    return isAdmin() && isset($_SESSION['admin_role']) && $_SESSION['admin_role'] == 'providers';
}

function isClientsAdmin() {
    return isAdmin() && isset($_SESSION['admin_role']) && $_SESSION['admin_role'] == 'clients';
}

function isReviewsAdmin() {
    return isAdmin() && isset($_SESSION['admin_role']) && $_SESSION['admin_role'] == 'reviews';
}

function canManageProviders() {
    return isMainAdmin() || isProvidersAdmin();
}

function canManageClients() {
    return isMainAdmin() || isClientsAdmin();
}

function canManageReviews() {
    return isMainAdmin() || isReviewsAdmin();
}

function canManageBookings() {
    return isMainAdmin();
}

function canManageServices() {
    return isMainAdmin();
}

function canManageAdmins() {
    return isSuperAdmin();
}

function canApproveProviders() {
    return isMainAdmin();
}

function canManageSettings() {
    return isMainAdmin();
}

// ===== حالة مقدم الخدمة =====
function isProviderApproved() {
    if (!isProvider()) return false;
    return isset($_SESSION['is_approved']) && $_SESSION['is_approved'] == 'approved';
}

function isProviderPending() {
    if (!isProvider()) return false;
    return isset($_SESSION['is_approved']) && $_SESSION['is_approved'] == 'pending';
}

function isProviderRejected() {
    if (!isProvider()) return false;
    return isset($_SESSION['is_approved']) && $_SESSION['is_approved'] == 'rejected';
}

// ===== دوال عامة =====
function getUserById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute(array($id));
    return $stmt->fetch();
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return getUserById($_SESSION['user_id']);
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function uploadImage($file, $subdir = 'general', $maxSize = 2097152) {
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > $maxSize) return false;

    $allowedTypes = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
    if (!in_array($file['type'], $allowedTypes)) return false;

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newName = time() . '_' . uniqid() . '.' . $ext;

    $uploadDir = 'uploads/' . $subdir . '/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $fullPath = $uploadDir . $newName;
    if (move_uploaded_file($file['tmp_name'], $fullPath)) return $fullPath;
    return false;
}

function deleteImage($path) {
    if ($path && file_exists($path)) return unlink($path);
    return false;
}

// ============================================
// دوال المدن
// ============================================
function getCityName($key) {
    global $AVAILABLE_CITIES;
    return isset($AVAILABLE_CITIES[$key]) ? $AVAILABLE_CITIES[$key] : $key;
}

function getCitiesList() {
    global $AVAILABLE_CITIES;
    return $AVAILABLE_CITIES;
}

function renderCityOptions($selected = '') {
    $cities = getCitiesList();
    $html = '';
    foreach ($cities as $key => $name) {
        $sel = ($selected == $key) ? ' selected' : '';
        $html .= '<option value="' . htmlspecialchars($key) . '"' . $sel . '>' . htmlspecialchars($name) . '</option>';
    }
    return $html;
}

// ============================================
// دوال إرسال البريد الإلكتروني
// ============================================
function sendEmail($to, $subject, $message, $from = 'info@baitkom.com') {
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=utf-8\r\n";
    $headers .= "From: بيتكم <$from>\r\n";
    $headers .= "Reply-To: $from\r\n";
    return mail($to, $subject, $message, $headers);
}

function sendProviderApprovalEmail($email, $name) {
    $subject = 'تم قبول طلب انضمامك - بيتكم';
    $message = '
    <div style="direction:rtl; font-family:Tajawal,sans-serif; max-width:600px; margin:0 auto; padding:20px; background:#0B1715; color:#F8FAF9; border-radius:10px;">
        <div style="text-align:center; padding:20px; border-bottom:1px solid rgba(255,255,255,0.05);">
            <h1 style="color:#12A88F; margin:0;">بيتكم</h1>
            <h2 style="color:#F8FAF9; margin:10px 0;">تم قبول طلب الانضمام</h2>
        </div>
        <div style="padding:20px;">
            <p>مرحباً <strong>' . $name . '</strong>،</p>
            <p style="color:#9AAEAA;">تم قبول طلب انضمامك كمقدم خدمة في منصة بيتكم.</p>
            <p style="color:#9AAEAA;">يمكنك الآن تسجيل الدخول وإضافة خدماتك.</p>
            <div style="text-align:center; margin:25px 0;">
                <a href="http://localhost/kh/login.php" style="display:inline-block; padding:12px 35px; background:#0B8F7A; color:#fff; border-radius:50px; text-decoration:none; font-weight:700;">تسجيل الدخول</a>
            </div>
        </div>
        <div style="text-align:center; padding:20px; color:#9AAEAA; font-size:12px;">
            © 2026 بيتكم
        </div>
    </div>
    ';
    return sendEmail($email, $subject, $message);
}

function sendProviderRejectionEmail($email, $name, $reason = '') {
    $subject = 'تحديث بشأن طلب انضمامك - بيتكم';
    $reason_text = $reason ? "<p style='color:#9AAEAA;'><strong>سبب الرفض:</strong> {$reason}</p>" : '';
    $message = '
    <div style="direction:rtl; font-family:Tajawal,sans-serif; max-width:600px; margin:0 auto; padding:20px; background:#0B1715; color:#F8FAF9; border-radius:10px;">
        <div style="text-align:center; padding:20px; border-bottom:1px solid rgba(255,255,255,0.05);">
            <h1 style="color:#12A88F; margin:0;">بيتكم</h1>
            <h2 style="color:#F8FAF9; margin:10px 0;">تحديث بشأن طلب الانضمام</h2>
        </div>
        <div style="padding:20px;">
            <p>مرحباً <strong>' . $name . '</strong>،</p>
            <p style="color:#9AAEAA;">نأسف لإعلامك بأنه تم رفض طلب انضمامك في الوقت الحالي.</p>
            ' . $reason_text . '
        </div>
        <div style="text-align:center; padding:20px; color:#9AAEAA; font-size:12px;">
            © 2026 بيتكم
        </div>
    </div>
    ';
    return sendEmail($email, $subject, $message);
}

function sendUserBanEmail($email, $name, $reason = '') {
    $subject = 'تم حظر حسابك - بيتكم';
    $reason_text = $reason ? "<p style='color:#9AAEAA;'><strong>سبب الحظر:</strong> {$reason}</p>" : '';
    $message = '
    <div style="direction:rtl; font-family:Tajawal,sans-serif; max-width:600px; margin:0 auto; padding:20px; background:#0B1715; color:#F8FAF9; border-radius:10px;">
        <div style="text-align:center; padding:20px; border-bottom:1px solid rgba(255,255,255,0.05);">
            <h1 style="color:#12A88F; margin:0;">بيتكم</h1>
            <h2 style="color:#F8FAF9; margin:10px 0;">تم حظر حسابك</h2>
        </div>
        <div style="padding:20px;">
            <p>مرحباً <strong>' . $name . '</strong>،</p>
            <p style="color:#9AAEAA;">تم حظر حسابك في منصة بيتكم.</p>
            ' . $reason_text . '
        </div>
        <div style="text-align:center; padding:20px; color:#9AAEAA; font-size:12px;">
            © 2026 بيتكم
        </div>
    </div>
    ';
    return sendEmail($email, $subject, $message);
}

function sendUserUnbanEmail($email, $name) {
    $subject = 'تم إلغاء حظر حسابك - بيتكم';
    $message = '
    <div style="direction:rtl; font-family:Tajawal,sans-serif; max-width:600px; margin:0 auto; padding:20px; background:#0B1715; color:#F8FAF9; border-radius:10px;">
        <div style="text-align:center; padding:20px; border-bottom:1px solid rgba(255,255,255,0.05);">
            <h1 style="color:#12A88F; margin:0;">بيتكم</h1>
            <h2 style="color:#F8FAF9; margin:10px 0;">تم إلغاء حظر حسابك</h2>
        </div>
        <div style="padding:20px;">
            <p>مرحباً <strong>' . $name . '</strong>،</p>
            <p style="color:#9AAEAA;">تم إلغاء حظر حسابك في منصة بيتكم.</p>
        </div>
        <div style="text-align:center; padding:20px; color:#9AAEAA; font-size:12px;">
            © 2026 بيتكم
        </div>
    </div>
    ';
    return sendEmail($email, $subject, $message);
}

// ============================================
// دوال التقييمات
// ============================================
function addReview($user_id, $provider_id, $booking_id, $rating, $comment, $review_type = 'service') {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO reviews (user_id, provider_id, booking_id, review_type, rating, comment) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->execute(array($user_id, $provider_id, $booking_id, $review_type, $rating, $comment))) {
        if ($provider_id && $review_type == 'provider') {
            $stmt2 = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE provider_id = ? AND review_type = 'provider'");
            $stmt2->execute(array($provider_id));
            $avg = $stmt2->fetch();
            $new_rating = round($avg['avg_rating'], 1);
            $pdo->prepare("UPDATE providers SET rating = ? WHERE user_id = ?")->execute(array($new_rating, $provider_id));
        }
        return true;
    }
    return false;
}

function hasReviewedBooking($booking_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM reviews WHERE booking_id = ?");
    $stmt->execute(array($booking_id));
    return $stmt->fetch() ? true : false;
}

// ============================================
// دوال مساعدة للعرض
// ============================================
function getSiteSetting($key, $default = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute(array($key));
        $row = $stmt->fetch();
        return $row ? $row['setting_value'] : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

function getReviewTypeLabel($type) {
    $labels = array(
        'service' => 'تقييم خدمة',
        'provider' => 'تقييم مقدم خدمة',
        'site' => 'تقييم الموقع'
    );
    return isset($labels[$type]) ? $labels[$type] : $type;
}

function getAdminRoleLabel($role) {
    $labels = array(
        'super_admin' => 'مدير عام',
        'main' => 'مدير رئيسي',
        'providers' => 'مدير مقدمي الخدمات',
        'clients' => 'مدير العملاء',
        'reviews' => 'مدير التقييمات'
    );
    return isset($labels[$role]) ? $labels[$role] : $role;
}

// ============================================
// دوال عرض الهيدر والقائمة الجانبية
// ============================================
function renderHeader($current_page = '', $header_subtitle = '') {
    $user = isLoggedIn() ? getCurrentUser() : null;
    if (empty($header_subtitle)) {
        $header_subtitle = 'ربك وكوستي - ولاية النيل الأبيض';
    }
    ?>
    <header id="mainHeader">
        <div class="container header-inner">
            <div class="logo">
                <img src="uploads/sh.png" alt="بيتكم">
                <span>بيتكم</span>
            </div>
            <div class="header-center">
                <i class="fas fa-map-marker-alt"></i>
                <span><?php echo htmlspecialchars($header_subtitle); ?></span>
            </div>
            
            <?php if ($user): ?>
                <div class="header-user" id="headerUser">
                    <button class="user-toggle" id="userToggle" aria-label="قائمة المستخدم">
                        <?php if ($user['profile_image'] && file_exists($user['profile_image'])): ?>
                            <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="صورة">
                        <?php else: ?>
                            <i class="fas fa-user-circle"></i>
                        <?php endif; ?>
                        <span class="user-name"><?php echo htmlspecialchars($user['name']); ?></span>
                        <i class="fas fa-chevron-down user-arrow"></i>
                    </button>
                    <div class="user-dropdown" id="userDropdown">
                        <a href="profile.php"><i class="fas fa-user"></i> الملف الشخصي</a>
                        <?php if (isClient() || (isProvider() && !isProviderApproved())): ?>
                            <a href="my_bookings.php"><i class="fas fa-calendar-check"></i> حجوزاتي</a>
                            <a href="my_reviews.php"><i class="fas fa-star"></i> تقييماتي</a>
                        <?php endif; ?>
                        <?php if (isProvider() && isProviderApproved()): ?>
                            <a href="provider_dashboard.php"><i class="fas fa-chart-pie"></i> لوحة مقدم الخدمة</a>
                            <a href="provider_orders.php"><i class="fas fa-clipboard-list"></i> طلباتي</a>
                            <a href="provider_wallet.php"><i class="fas fa-wallet"></i> محفظتي</a>
                        <?php endif; ?>
                        <?php if (isAdmin()): ?>
                            <a href="dashboard.php"><i class="fas fa-gauge-high"></i> لوحة التحكم</a>
                        <?php endif; ?>
                        <a href="logout.php" class="logout-item"><i class="fas fa-right-from-bracket"></i> تسجيل خروج</a>
                    </div>
                </div>
            <?php endif; ?>
            
            <button class="hamburger" id="hamburgerBtn" aria-label="القائمة">
                <span></span><span></span><span></span>
            </button>
        </div>
    </header>
    <?php
}

function renderSideMenu($current_page = '') {
    ?>
    <div class="menu-overlay" id="menuOverlay"></div>
    <nav class="side-menu" id="sideMenu">
        <div class="menu-header">
            <span class="close-btn" id="closeMenuBtn">✕</span>
            <div class="menu-logo">بيتكم</div>
        </div>
        <ul class="menu-links">
            <li><a href="home.php" class="<?php echo $current_page == 'home.php' ? 'active' : ''; ?>"><i class="fas fa-home"></i> الرئيسية</a></li>
            <li><a href="services.php" class="<?php echo $current_page == 'services.php' ? 'active' : ''; ?>"><i class="fas fa-tools"></i> الخدمات</a></li>
            <li><a href="providers.php" class="<?php echo $current_page == 'providers.php' ? 'active' : ''; ?>"><i class="fas fa-users"></i> مقدمو الخدمات</a></li>
            <li><a href="about.php" class="<?php echo $current_page == 'about.php' ? 'active' : ''; ?>"><i class="fas fa-circle-info"></i> من نحن</a></li>
            <li><a href="contact.php" class="<?php echo $current_page == 'contact.php' ? 'active' : ''; ?>"><i class="fas fa-envelope"></i> تواصل معنا</a></li>

            <?php if (isLoggedIn()): ?>
                <?php if (isAdmin()): ?>
                    <?php if (isSuperAdmin()): ?>
                        <li><a href="admin_managers.php" class="<?php echo $current_page == 'admin_managers.php' ? 'active' : ''; ?>"><i class="fas fa-user-tie"></i> إدارة المدراء</a></li>
                    <?php endif; ?>
                    <?php if (canManageProviders()): ?>
                        <li><a href="admin_providers.php" class="<?php echo $current_page == 'admin_providers.php' ? 'active' : ''; ?>"><i class="fas fa-users-cog"></i> مقدمي الخدمات</a></li>
                    <?php endif; ?>
                    <?php if (canManageClients()): ?>
                        <li><a href="admin_clients.php" class="<?php echo $current_page == 'admin_clients.php' ? 'active' : ''; ?>"><i class="fas fa-user-friends"></i> العملاء</a></li>
                    <?php endif; ?>
                    <?php if (canManageReviews()): ?>
                        <li><a href="admin_reviews.php" class="<?php echo $current_page == 'admin_reviews.php' ? 'active' : ''; ?>"><i class="fas fa-star"></i> التقييمات</a></li>
                    <?php endif; ?>
                    <?php if (canManageBookings()): ?>
                        <li><a href="admin_bookings.php" class="<?php echo $current_page == 'admin_bookings.php' ? 'active' : ''; ?>"><i class="fas fa-calendar-check"></i> الحجوزات</a></li>
                        <li><a href="admin_services.php" class="<?php echo $current_page == 'admin_services.php' ? 'active' : ''; ?>"><i class="fas fa-tools"></i> الخدمات</a></li>
                        <li><a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>"><i class="fas fa-gauge-high"></i> لوحة التحكم</a></li>
                    <?php endif; ?>
                    <?php if (canManageSettings()): ?>
                        <li><a href="admin_settings.php" class="<?php echo $current_page == 'admin_settings.php' ? 'active' : ''; ?>"><i class="fas fa-cog"></i> الإعدادات</a></li>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if (isProvider() && isProviderApproved()): ?>
                    <li><a href="provider_dashboard.php" class="<?php echo $current_page == 'provider_dashboard.php' ? 'active' : ''; ?>"><i class="fas fa-chart-pie"></i> لوحة مقدم الخدمة</a></li>
                    <li><a href="add_service.php" class="<?php echo $current_page == 'add_service.php' ? 'active' : ''; ?>"><i class="fas fa-plus-circle"></i> إضافة خدمة</a></li>
                    <li><a href="my_services.php" class="<?php echo $current_page == 'my_services.php' ? 'active' : ''; ?>"><i class="fas fa-briefcase"></i> خدماتي</a></li>
                    <li><a href="provider_orders.php" class="<?php echo $current_page == 'provider_orders.php' ? 'active' : ''; ?>"><i class="fas fa-clipboard-list"></i> طلباتي</a></li>
                    <li><a href="provider_wallet.php" class="<?php echo $current_page == 'provider_wallet.php' ? 'active' : ''; ?>"><i class="fas fa-wallet"></i> محفظتي</a></li>
                <?php endif; ?>

                <?php if (isClient() || (isProvider() && !isProviderApproved())): ?>
                    <li><a href="my_bookings.php" class="<?php echo $current_page == 'my_bookings.php' ? 'active' : ''; ?>"><i class="fas fa-calendar-check"></i> حجوزاتي</a></li>
                    <li><a href="my_reviews.php" class="<?php echo $current_page == 'my_reviews.php' ? 'active' : ''; ?>"><i class="fas fa-star"></i> تقييماتي</a></li>
                <?php endif; ?>

                <li><a href="profile.php" class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>"><i class="fas fa-user"></i> الملف الشخصي</a></li>
                <li><a href="logout.php" class="menu-logout"><i class="fas fa-right-from-bracket"></i> تسجيل خروج</a></li>
            <?php else: ?>
                <li><a href="login.php" class="menu-login <?php echo $current_page == 'login.php' ? 'active' : ''; ?>"><i class="fas fa-right-to-bracket"></i> تسجيل الدخول</a></li>
                <li><a href="register.php" class="menu-register <?php echo $current_page == 'register.php' ? 'active' : ''; ?>"><i class="fas fa-user-plus"></i> إنشاء حساب</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php
}

function renderFooter() {
    ?>
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
                    <a href="#" aria-label="واتساب"><i class="fab fa-whatsapp"></i></a>
                    <a href="#" aria-label="إنستقرام"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="تويتر"><i class="fab fa-twitter"></i></a>
                    <a href="#" aria-label="يوتيوب"><i class="fab fa-youtube"></i></a>
                </div>
                <div class="copyright">© 2026 بيتكم - ربك وكوستي. جميع الحقوق محفوظة</div>
            </div>
        </div>
    </footer>
    <?php
}

function renderCommonScripts() {
    ?>
    <button id="backToTop" onclick="window.scrollTo({top:0,behavior:'smooth'})"><i class="fas fa-arrow-up"></i></button>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
    AOS.init({ duration: 800, once: true, mirror: false, offset: 50, easing: 'ease-out-cubic' });

    var scrollProgress = document.getElementById('scrollProgress');
    if (scrollProgress) {
        window.addEventListener('scroll', function() {
            var scrollTop = window.scrollY;
            var docHeight = document.documentElement.scrollHeight - window.innerHeight;
            if (docHeight > 0) scrollProgress.style.width = ((scrollTop / docHeight) * 100) + '%';
        });
    }

    var header = document.getElementById('mainHeader');
    if (header) {
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
    }

    document.addEventListener('DOMContentLoaded', function() {
        var hamburger = document.getElementById('hamburgerBtn');
        var sideMenu = document.getElementById('sideMenu');
        var closeBtn = document.getElementById('closeMenuBtn');
        var overlay = document.getElementById('menuOverlay');

        function toggleMenu() {
            if (!hamburger || !sideMenu || !overlay) return;
            hamburger.classList.toggle('active');
            sideMenu.classList.toggle('open');
            overlay.classList.toggle('active');
            document.body.style.overflow = sideMenu.classList.contains('open') ? 'hidden' : '';
        }

        if (hamburger) hamburger.addEventListener('click', toggleMenu);
        if (closeBtn) closeBtn.addEventListener('click', toggleMenu);
        if (overlay) overlay.addEventListener('click', toggleMenu);

        document.querySelectorAll('.menu-links a').forEach(function(link) {
            link.addEventListener('click', function() {
                if (sideMenu && sideMenu.classList.contains('open')) toggleMenu();
            });
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sideMenu && sideMenu.classList.contains('open')) toggleMenu();
        });

        var userToggle = document.getElementById('userToggle');
        var userDropdown = document.getElementById('userDropdown');
        if (userToggle && userDropdown) {
            userToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                userDropdown.classList.toggle('show');
            });
            document.addEventListener('click', function(e) {
                if (!userDropdown.contains(e.target) && !userToggle.contains(e.target)) {
                    userDropdown.classList.remove('show');
                }
            });
        }
    });

    var backBtn = document.getElementById('backToTop');
    if (backBtn) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 400) backBtn.classList.add('visible'); else backBtn.classList.remove('visible');
        });
    }
    </script>
    <?php
}

function renderPageStart($title, $current_page, $header_subtitle = '') {
    ?>
    <!DOCTYPE html>
    <html lang="ar" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($title); ?> - بيتكم</title>
        <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
    <div id="scrollProgress"></div>
    <?php
    renderHeader($current_page, $header_subtitle);
    renderSideMenu($current_page);
}

function renderPageEnd() {
    renderFooter();
    renderCommonScripts();
    ?>
    </body>
    </html>
    <?php
}
?>