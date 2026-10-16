<?php
require_once 'config.php';
if (isLoggedIn()) redirect('home.php');

$error = '';
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $redirect = isset($_POST['redirect']) ? $_POST['redirect'] : '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
    $stmt->execute(array($email, $email));
    $user = $stmt->fetch();

    if ($user && md5($password) == $user['password']) {
        if (isset($user['is_banned']) && $user['is_banned'] == 1) {
            $error = 'تم حظر حسابك من قبل الإدارة';
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['is_approved'] = $user['is_approved'];
            $_SESSION['admin_role'] = isset($user['admin_role']) ? $user['admin_role'] : null;

            if (!empty($redirect)) {
                redirect($redirect);
            } elseif ($user['user_type'] == 'admin') {
                redirect('dashboard.php');
            } else {
                redirect('home.php');
            }
        }
    } else {
        $error = 'البريد الإلكتروني أو كلمة المرور غير صحيحة';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - بيتكم</title>
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
        .login-wrapper { width: 100%; max-width: 460px; }
        .login-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-glass);
            border-radius: 30px;
            padding: 45px 40px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.6);
        }
        .login-header {
            text-align: center;
            margin-bottom: 35px;
        }
        .login-header img {
            max-width: 150px;
            height: auto;
            border-radius: 12px;
            margin-bottom: 15px;
        }
        .login-header h1 {
            font-size: 30px;
            font-weight: 800;
            color: var(--text-white);
            margin-bottom: 8px;
        }
        .login-header p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 15px;
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
            box-shadow: 0 0 0 4px rgba(11, 143, 122, 0.15);
        }
        .input-wrapper input::placeholder { color: rgba(255, 255, 255, 0.3); }
        .btn-login {
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
        .btn-login:hover {
            transform: scale(1.02);
            box-shadow: 0 10px 40px var(--shadow-green);
        }
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
        .forgot-link {
            text-align: center;
            margin-top: 15px;
            color: rgba(255, 255, 255, 0.4);
            font-size: 14px;
        }
        .forgot-link a { color: var(--text-gray); transition: 0.3s; }
        .forgot-link a:hover { color: var(--primary-teal); }
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: rgba(255, 255, 255, 0.5);
            font-size: 15px;
        }
        .login-link a {
            color: var(--primary-teal);
            font-weight: 700;
            transition: 0.3s;
        }
        .login-link a:hover {
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
            .login-container { padding: 30px 20px; border-radius: 20px; }
            .login-header h1 { font-size: 22px; }
            .login-header img { max-width: 120px; }
            .input-wrapper input { padding: 12px 45px 12px 16px; font-size: 14px; }
            .btn-login { font-size: 16px; padding: 14px; }
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-container">
        <div class="login-header">
            <img src="uploads/sh.png" alt="بيتكم">
            <h1>مرحباً بعودتك</h1>
            <p>سجل الدخول للوصول إلى حسابك</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <?php if (!empty($redirect)): ?>
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
            <?php endif; ?>

            <div class="form-group">
                <label>البريد الإلكتروني أو اسم المستخدم</label>
                <div class="input-wrapper">
                    <input type="text" name="email" placeholder="example@email.com" required>
                    <i class="fas fa-envelope"></i>
                </div>
            </div>

            <div class="form-group">
                <label>كلمة المرور</label>
                <div class="input-wrapper">
                    <input type="password" name="password" placeholder="••••••••" required>
                    <i class="fas fa-lock"></i>
                </div>
            </div>

            <button type="submit" class="btn-login"><i class="fas fa-sign-in-alt"></i> تسجيل الدخول</button>
        </form>

        <div class="forgot-link"><a href="forgot_password.php"><i class="fas fa-key"></i> نسيت كلمة المرور؟</a></div>
        <div class="login-link">ليس لديك حساب؟ <a href="register.php">إنشاء حساب جديد</a></div>
        <div style="text-align:center;"><a href="index.php" class="back-home"><i class="fas fa-arrow-right"></i> العودة للرئيسية</a></div>
    </div>
</div>

</body>
</html>