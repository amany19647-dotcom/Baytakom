<?php
require_once 'config.php';
if (isLoggedIn()) redirect('home.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $region = trim($_POST['region']);
    $age = intval($_POST['age']);
    $service_type = isset($_POST['service_type']) ? trim($_POST['service_type']) : '';
    $user_type = $_POST['user_type'];
    $working_hours = isset($_POST['working_hours']) ? trim($_POST['working_hours']) : '';
    $working_area = isset($_POST['working_area']) ? trim($_POST['working_area']) : '';
    $experience_years = isset($_POST['experience_years']) ? intval($_POST['experience_years']) : 0;
    $city = isset($_POST['city']) ? trim($_POST['city']) : '';
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    $terms = isset($_POST['terms']) ? $_POST['terms'] : '';

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'يرجى إدخال بريد إلكتروني صحيح';
    }

    if (empty($error)) {
        $domain = substr(strrchr($email, "@"), 1);
        $blocked_domains = array('tempmail.com', 'guerrillamail.com', 'mailinator.com', '10minutemail.com', 'throwaway.com', 'temp-mail.org');
        if (in_array($domain, $blocked_domains)) {
            $error = 'يرجى استخدام بريد إلكتروني حقيقي';
        }
    }

    if (empty($error) && empty($terms)) {
        $error = 'يجب الموافقة على الشروط والأحكام';
    } elseif (empty($error) && $password !== $confirm) {
        $error = 'كلمة المرور غير متطابقة';
    } elseif (empty($error) && strlen($password) < 6) {
        $error = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
    } elseif (empty($error) && empty($city)) {
        $error = 'يرجى تحديد المدينة';
    } else if (empty($error)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute(array($email, $username));
        if ($stmt->fetch()) {
            $error = 'البريد الإلكتروني أو اسم المستخدم موجود بالفعل';
        } else {
            $hashed = md5($password);
            $is_approved = ($user_type == 'provider') ? 'pending' : 'approved';

            $profile_image = null;
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == UPLOAD_ERR_OK) {
                $uploaded = uploadImage($_FILES['profile_image'], 'users');
                if ($uploaded) $profile_image = $uploaded;
            }

            $national_id_image = null;
            $certificate_image = null;

            if ($user_type == 'provider') {
                if (isset($_FILES['national_id_image']) && $_FILES['national_id_image']['error'] == UPLOAD_ERR_OK) {
                    $uploaded = uploadImage($_FILES['national_id_image'], 'ids');
                    if ($uploaded) {
                        $national_id_image = $uploaded;
                    } else {
                        $error = 'فشل رفع صورة الرقم الوطني';
                    }
                } else {
                    $error = 'يرجى رفع صورة الرقم الوطني';
                }

                if (empty($error) && isset($_FILES['certificate_image']) && $_FILES['certificate_image']['error'] == UPLOAD_ERR_OK) {
                    $uploaded = uploadImage($_FILES['certificate_image'], 'certificates');
                    if ($uploaded) $certificate_image = $uploaded;
                }
            }

            if (empty($error)) {
                $stmt = $pdo->prepare("INSERT INTO users (name, username, email, phone, region, age, city, service_type, password, user_type, is_approved, profile_image, national_id_image, certificate_image, working_area) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                if ($stmt->execute(array($name, $username, $email, $phone, $region, $age, $city, $service_type, $hashed, $user_type, $is_approved, $profile_image, $national_id_image, $certificate_image, $working_area))) {
                    $user_id = $pdo->lastInsertId();

                    if ($user_type == 'provider') {
                        $stmt2 = $pdo->prepare("INSERT INTO providers (user_id, specialty, experience_years, working_hours, city) VALUES (?, ?, ?, ?, ?)");
                        $stmt2->execute(array($user_id, $service_type, $experience_years, $working_hours, $city));
                        $success = 'تم إنشاء حسابك بنجاح، سيتم مراجعة طلبك من قبل الإدارة. سيتم إعلامك عبر البريد عند قبول الطلب.';
                    } else {
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['name'] = $name;
                        $_SESSION['username'] = $username;
                        $_SESSION['user_type'] = $user_type;
                        $_SESSION['is_approved'] = $is_approved;
                        redirect('home.php');
                    }
                } else {
                    $error = 'حدث خطأ أثناء التسجيل';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء حساب - بيتكم</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-green: #0B8F7A;
            --primary-teal: #12A88F;
            --primary-cyan: #19B394;
            --bg-dark: #0B1715;
            --text-white: #F8FAF9;
            --text-gray: #9AAEAA;
            --border-light: rgba(255, 255, 255, 0.07);
            --border-glass: rgba(255, 255, 255, 0.10);
            --shadow-green: rgba(11, 143, 122, 0.25);
            --gradient-main: linear-gradient(135deg, #0B8F7A, #12A88F, #19B394);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Tajawal', sans-serif;
            background: var(--bg-dark);
            color: var(--text-white);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 40px 20px;
            direction: rtl;
        }
        a { text-decoration: none; color: inherit; }
        .register-wrapper { width: 100%; max-width: 620px; }
        .register-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-glass);
            border-radius: 30px;
            padding: 45px 40px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.6);
        }
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .register-header img {
            max-width: 150px;
            height: auto;
            border-radius: 12px;
            margin-bottom: 15px;
        }
        .register-header h1 {
            font-size: 30px;
            font-weight: 800;
            color: var(--text-white);
            margin-bottom: 8px;
        }
        .register-header p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 15px;
        }
        .info-note {
            background: rgba(11, 143, 122, 0.08);
            border-right: 3px solid var(--primary-green);
            padding: 12px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            color: var(--text-gray);
        }
        .info-note i { color: var(--primary-green); margin-left: 8px; }
        .error-message {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(234, 67, 53, 0.15);
            border: 1px solid rgba(234, 67, 53, 0.3);
            color: #ea4335;
        }
        .success-message {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(52, 168, 83, 0.15);
            border: 1px solid rgba(52, 168, 83, 0.3);
            color: #34a853;
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
        .input-wrapper select {
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
        .input-wrapper select:focus {
            border-color: var(--primary-green);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 4px rgba(11, 143, 122, 0.15);
        }
        .input-wrapper input::placeholder { color: rgba(255, 255, 255, 0.3); }
        .input-wrapper select option { background: #0D1F1B; color: var(--text-white); }
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
        .file-input-wrapper .file-label:hover { border-color: var(--primary-green); }
        .helper-text {
            color: var(--text-gray);
            font-size: 12px;
            margin-top: 6px;
            display: block;
        }
        .provider-fields { display: none; }
        .provider-fields.show {
            display: block;
            animation: slideDown 0.4s ease;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .terms-container {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-glass);
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 16px;
            max-height: 200px;
            overflow-y: auto;
        }
        .terms-container::-webkit-scrollbar { width: 4px; }
        .terms-container::-webkit-scrollbar-thumb {
            background: var(--gradient-main);
            border-radius: 10px;
        }
        .terms-container h4 {
            color: var(--text-white);
            margin-bottom: 10px;
            font-size: 16px;
        }
        .terms-container ul { padding-right: 20px; }
        .terms-container li {
            color: var(--text-gray);
            font-size: 14px;
            line-height: 1.7;
            margin-bottom: 4px;
        }
        .terms-check {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 20px;
        }
        .terms-check input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-top: 3px;
            accent-color: var(--primary-green);
            cursor: pointer;
            flex-shrink: 0;
        }
        .terms-check label {
            color: var(--text-gray);
            font-size: 14px;
            cursor: pointer;
        }
        .terms-check label a {
            color: var(--primary-teal);
            font-weight: 600;
        }
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
            transition: 0.3s;
            box-shadow: 0 4px 25px var(--shadow-green);
            font-family: 'Tajawal', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        .btn-register:hover {
            transform: scale(1.02);
            box-shadow: 0 10px 40px var(--shadow-green);
        }
        .register-link {
            text-align: center;
            margin-top: 20px;
            color: rgba(255, 255, 255, 0.5);
            font-size: 15px;
        }
        .register-link a {
            color: var(--primary-teal);
            font-weight: 700;
            transition: 0.3s;
        }
        .register-link a:hover {
            color: var(--primary-cyan);
            text-decoration: underline;
        }
        .back-home {
            display: inline-block;
            margin-top: 20px;
            color: rgba(255, 255, 255, 0.4);
            font-size: 14px;
            transition: 0.3s;
        }
        .back-home:hover { color: var(--text-white); }
        @media (max-width: 480px) {
            .register-container { padding: 25px 18px; border-radius: 20px; }
            .register-header h1 { font-size: 22px; }
            .register-header img { max-width: 120px; }
            .input-wrapper input,
            .input-wrapper select {
                padding: 12px 45px 12px 16px;
                font-size: 14px;
            }
            .btn-register { font-size: 16px; padding: 14px; }
            .terms-container { max-height: 150px; }
        }
    </style>
</head>
<body>

<div class="register-wrapper">
    <div class="register-container">
        <div class="register-header">
            <img src="uploads/sh.png" alt="بيتكم">
            <h1>إنشاء حساب جديد</h1>
            <p>اختر نوع الحساب المناسب لك</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="success-message"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <div style="text-align:center; margin-top:20px;">
                <a href="login.php" class="btn-register" style="display:inline-flex; padding:12px 40px; width:auto;">تسجيل الدخول</a>
                <a href="index.php" class="back-home" style="margin-right:15px;">الرئيسية</a>
            </div>
        <?php endif; ?>

        <?php if (empty($success)): ?>
        <div class="info-note">
            <i class="fas fa-info-circle"></i>
            <strong>ملاحظة:</strong> سيتم مراجعة طلب مقدمي الخدمات من قبل الإدارة قبل تفعيل الحساب.
        </div>

        <form method="POST" enctype="multipart/form-data" id="registerForm">
            <div class="form-group">
                <label>الاسم الكامل</label>
                <div class="input-wrapper">
                    <input type="text" name="name" placeholder="أحمد محمد" required>
                    <i class="fas fa-user"></i>
                </div>
            </div>

            <div class="form-group">
                <label>اسم المستخدم</label>
                <div class="input-wrapper">
                    <input type="text" name="username" placeholder="ahmed_mohamed" required>
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
                    <input type="tel" name="phone" placeholder="0912345678" required>
                    <i class="fas fa-phone"></i>
                </div>
            </div>

            <div class="form-group">
                <label>المنطقة</label>
                <div class="input-wrapper">
                    <input type="text" name="region" placeholder="مثال: حي النصر" required>
                    <i class="fas fa-map-marker-alt"></i>
                </div>
            </div>

            <div class="form-group">
                <label>العمر</label>
                <div class="input-wrapper">
                    <input type="number" name="age" min="18" placeholder="25" required>
                    <i class="fas fa-calendar-alt"></i>
                </div>
            </div>

            <div class="form-group">
                <label>المدينة</label>
                <div class="input-wrapper">
                    <select name="city" required>
                        <option value="">اختر المدينة</option>
                        <option value="rabak">ربك</option>
                        <option value="kosti">كوستي</option>
                    </select>
                    <i class="fas fa-city"></i>
                </div>
            </div>

            <div class="form-group">
                <label>نوع الحساب</label>
                <div class="input-wrapper">
                    <select name="user_type" id="user_type" required>
                        <option value="receiver">عميل (متلقي خدمة)</option>
                        <option value="provider">مقدم خدمة</option>
                    </select>
                    <i class="fas fa-user-tag"></i>
                </div>
            </div>

            <div class="form-group">
                <label>الصورة الشخصية (اختياري)</label>
                <div class="file-input-wrapper">
                    <span class="file-label"><i class="fas fa-image"></i> اختر صورة</span>
                    <input type="file" name="profile_image" accept="image/*">
                </div>
                <small class="helper-text">الحد الأقصى للحجم: 2 ميجابايت</small>
            </div>

            <div class="provider-fields" id="providerFields">
                <div class="form-group">
                    <label>نوع الخدمة المقدمة</label>
                    <div class="input-wrapper">
                        <select name="service_type">
                            <option value="">اختر نوع الخدمة</option>
                            <option value="تنظيف">تنظيف</option>
                            <option value="كهرباء">كهرباء</option>
                            <option value="سباكة">سباكة</option>
                            <option value="تكييف">تكييف</option>
                            <option value="دهان">دهان</option>
                            <option value="نجارة">نجارة</option>
                            <option value="نقل أثاث">نقل أثاث</option>
                            <option value="طبيخ">طبيخ</option>
                            <option value="حدائق">حدائق</option>
                            <option value="صيانة عامة">صيانة عامة</option>
                            <option value="أخرى">أخرى</option>
                        </select>
                        <i class="fas fa-briefcase"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>صورة الرقم الوطني</label>
                    <div class="file-input-wrapper">
                        <span class="file-label"><i class="fas fa-id-card"></i> اختر صورة الرقم الوطني</span>
                        <input type="file" name="national_id_image" accept="image/*" id="nationalIdInput">
                    </div>
                    <small class="helper-text">مطلوب - الحد الأقصى: 2 ميجابايت</small>
                </div>

                <div class="form-group">
                    <label>شهادة الخبرة (اختياري)</label>
                    <div class="file-input-wrapper">
                        <span class="file-label"><i class="fas fa-certificate"></i> اختر شهادة الخبرة</span>
                        <input type="file" name="certificate_image" accept="image/*">
                    </div>
                </div>

                <div class="form-group">
                    <label>سنوات الخبرة</label>
                    <div class="input-wrapper">
                        <input type="number" name="experience_years" min="0" placeholder="0" value="0">
                        <i class="fas fa-briefcase"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>نطاق الأحياء التي تغطيها</label>
                    <div class="input-wrapper">
                        <input type="text" name="working_area" placeholder="مثال: حي النصر، حي السلام">
                        <i class="fas fa-map-pin"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>ساعات العمل (اختياري)</label>
                    <div class="input-wrapper">
                        <input type="text" name="working_hours" placeholder="مثال: السبت-الخميس 8ص-6م">
                        <i class="fas fa-clock"></i>
                    </div>
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
                <label>تأكيد كلمة المرور</label>
                <div class="input-wrapper">
                    <input type="password" name="confirm_password" placeholder="••••••••" required>
                    <i class="fas fa-lock"></i>
                </div>
            </div>

            <div class="terms-container">
                <h4>شروط وأحكام المنصة</h4>
                <div id="termsContent"></div>
            </div>

            <div class="terms-check">
                <input type="checkbox" name="terms" id="terms" value="1" required>
                <label for="terms">أوافق على <a href="#">شروط وأحكام</a> استخدام المنصة</label>
            </div>

            <button type="submit" class="btn-register"><i class="fas fa-user-plus"></i> إنشاء حساب</button>
        </form>
        <?php endif; ?>

        <div class="register-link">لديك حساب بالفعل؟ <a href="login.php">تسجيل الدخول</a></div>
        <div style="text-align:center;"><a href="index.php" class="back-home"><i class="fas fa-arrow-right"></i> العودة للرئيسية</a></div>
    </div>
</div>

<script>
    document.getElementById('user_type').addEventListener('change', function() {
        var field = document.getElementById('providerFields');
        var termsContent = document.getElementById('termsContent');
        var nationalIdInput = document.getElementById('nationalIdInput');
        var serviceTypeSelect = document.querySelector('select[name="service_type"]');

        if (this.value === 'provider') {
            field.classList.add('show');
            serviceTypeSelect.setAttribute('required', 'required');
            nationalIdInput.setAttribute('required', 'required');
            termsContent.innerHTML = getProviderTerms();
        } else {
            field.classList.remove('show');
            serviceTypeSelect.removeAttribute('required');
            nationalIdInput.removeAttribute('required');
            termsContent.innerHTML = getClientTerms();
        }
    });

    function getClientTerms() {
        return '<ul>' +
            '<li>يلتزم العميل بتقديم بيانات صحيحة وحقيقية عند التسجيل.</li>' +
            '<li>يتحمل العميل مسؤولية المحافظة على بيانات الدخول الخاصة بحسابه.</li>' +
            '<li>يلتزم العميل بتقديم معلومات صحيحة عن الخدمة المطلوبة.</li>' +
            '<li>يلتزم العميل باحترام مقدم الخدمة وعدم الإساءة.</li>' +
            '<li>يمنع استخدام المنصة لطلب خدمات غير قانونية.</li>' +
            '<li>يجب على العميل الالتزام بالمواعيد التي يقوم بحجزها.</li>' +
            '<li>لا يجوز للعميل استخدام المنصة للتحايل على النظام.</li>' +
            '<li>يجب على العميل عدم مشاركة بيانات الدخول مع الآخرين.</li>' +
            '<li>يحق للمنصة تعليق الحساب في حال وجود مخالفة.</li>' +
            '<li>يجب أن تكون التقييمات حقيقية ومحترمة.</li>' +
            '<li>استخدام المنصة يعني الموافقة على الشروط والأحكام.</li>' +
            '</ul>';
    }

    function getProviderTerms() {
        return '<ul>' +
            '<li>يلتزم مقدم الخدمة بتقديم معلومات صحيحة وكاملة.</li>' +
            '<li>يلتزم مقدم الخدمة برفع صورة صحيحة للرقم الوطني.</li>' +
            '<li>يجب أن تكون المهنة والتخصص حقيقية.</li>' +
            '<li>تحتفظ المنصة بحق مراجعة البيانات قبل اعتماد الحساب.</li>' +
            '<li>يجب على مقدم الخدمة الالتزام بالمواعيد.</li>' +
            '<li>يجب تنفيذ الخدمة بجودة ومهنية مناسبة.</li>' +
            '<li>يجب التعامل مع العملاء باحترام.</li>' +
            '<li>يمنع استخدام بيانات العملاء لأي غرض خارج نطاق الخدمة.</li>' +
            '<li>يتحمل مقدم الخدمة مسؤولية صحة الأسعار.</li>' +
            '<li>يمنع التلاعب بالتقييمات أو إنشاء تقييمات وهمية.</li>' +
            '<li>يحق للمنصة رفض أو إيقاف الحساب عند وجود مخالفة.</li>' +
            '<li>استخدام المنصة يعني الموافقة على الشروط والأحكام.</li>' +
            '</ul>';
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('termsContent').innerHTML = getClientTerms();
    });
</script>

</body>
</html>