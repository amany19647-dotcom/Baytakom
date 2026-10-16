<?php
require_once 'config.php';
if (isLoggedIn()) redirect('home.php');

$error = '';
$message = '';
$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$email = '';

if (!empty($token)) {
    $stmt = $pdo->prepare("SELECT email, expires_at FROM password_resets WHERE token = ?");
    $stmt->execute(array($token));
    $reset = $stmt->fetch();

    if (!$reset) {
        $error = 'الرابط غير صالح أو منتهي الصلاحية';
    } elseif (strtotime($reset['expires_at']) < time()) {
        $error = 'انتهت صلاحية رابط الاستعادة';
    } else {
        $email = $reset['email'];
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($token)) {
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if (empty($password) || strlen($password) < 6) {
        $error = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
    } elseif ($password !== $confirm) {
        $error = 'كلمة المرور غير متطابقة';
    } else {
        $stmt = $pdo->prepare("SELECT email, expires_at FROM password_resets WHERE token = ?");
        $stmt->execute(array($token));
        $reset = $stmt->fetch();

        if (!$reset || strtotime($reset['expires_at']) < time()) {
            $error = 'الرابط غير صالح أو منتهي الصلاحية';
        } else {
            $hashed = md5($password);
            $pdo->prepare("UPDATE users SET password = ? WHERE email = ?")->execute(array($hashed, $reset['email']));
            $pdo->prepare("DELETE FROM password_resets WHERE token = ?")->execute(array($token));
            $message = 'تم تغيير كلمة المرور بنجاح، يمكنك الآن <a href="login.php">تسجيل الدخول</a>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعادة تعيين كلمة المرور - بيتكم</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-green: #0B8F7A;
            --primary-teal: #12A88F;
            --bg-dark: #0B1715;
            --text-white: #F8FAF9;
            --text-gray: #9AAEAA;
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
            align-items: center;
            padding: 20px;
            direction: rtl;
        }
        a { text-decoration: none; color: inherit; }
        .reset-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-glass);
            border-radius: 30px;
            padding: 45px 40px;
            max-width: 440px;
            width: 100%;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.6);
        }
        .reset-container .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .reset-container .header .logo-icon {
            font-size: 50px;
            color: var(--primary-teal);
            display: block;
            margin-bottom: 10px;
        }
        .reset-container .header h1 {
            font-size: 28px;
            font-weight: 800;
            color: var(--text-white);
            margin-bottom: 6px;
        }
        .reset-container .header p {
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
        .input-wrapper input {
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
        .input-wrapper input:focus {
            border-color: var(--primary-green);
            background: rgba(255, 255, 255, 0.08);
        }
        .input-wrapper input::placeholder { color: rgba(255, 255, 255, 0.3); }
        .btn-login {
            width: 100%;
            padding: 16px;
            background: var(--gradient-main);
            border: none;
            border-radius: 14px;
            color: white;
            font-size: 17px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 25px var(--shadow-green);
            font-family: 'Tajawal', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        .btn-login:hover { transform: scale(1.02); }
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
        .error-message a { color: var(--primary-teal); font-weight: 700; }
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
        .success-message a { color: var(--primary-teal); font-weight: 700; }
        .back-link {
            text-align: center;
            margin-top: 20px;
            color: rgba(255, 255, 255, 0.4);
            font-size: 14px;
        }
        .back-link a { color: var(--primary-teal); font-weight: 600; }
        @media (max-width: 480px) {
            .reset-container { padding: 30px 20px; border-radius: 20px; }
            .reset-container .header h1 { font-size: 22px; }
        }
    </style>
</head>
<body>

<div class="reset-container">
    <div class="header">
        <span class="logo-icon"><i class="fas fa-lock-open"></i></span>
        <h1>إعادة تعيين كلمة المرور</h1>
        <p>أدخل كلمة المرور الجديدة</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>
    <?php if (!empty($message)): ?>
        <div class="success-message"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
    <?php endif; ?>

    <?php if (empty($error) && empty($message) && !empty($token)): ?>
        <div class="info-note">
            <i class="fas fa-info-circle"></i>
            إعادة تعيين للبريد: <strong><?php echo htmlspecialchars($email); ?></strong>
        </div>
        <form method="POST">
            <div class="form-group">
                <label>كلمة المرور الجديدة</label>
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
            <button type="submit" class="btn-login"><i class="fas fa-save"></i> تغيير كلمة المرور</button>
        </form>
    <?php endif; ?>

    <?php if (empty($token)): ?>
        <div class="error-message"><i class="fas fa-exclamation-circle"></i> لم يتم العثور على رابط صالح. <a href="forgot_password.php">طلب رابط جديد</a></div>
    <?php endif; ?>

    <div class="back-link"><a href="login.php"><i class="fas fa-arrow-right"></i> العودة إلى تسجيل الدخول</a></div>
</div>

</body>
</html>