<?php
session_start();
if (isset($_SESSION['user_id'])) { header('Location: ../index.php'); exit; }
require_once '../config/db.php';
require_once '../config/captcha.php';

// Rate limiting: maks 5 percobaan login dalam 10 menit
if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;
if (!isset($_SESSION['login_lockout'])) $_SESSION['login_lockout'] = 0;

$is_locked = $_SESSION['login_lockout'] > time();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($is_locked) {
        $sisa = ceil(($_SESSION['login_lockout'] - time()) / 60);
        $error = "Terlalu banyak percobaan gagal. Coba lagi dalam $sisa menit.";
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $recap    = $_POST['g-recaptcha-response'] ?? '';

        if (!verify_recaptcha($recap)) {
            $error = 'Verifikasi reCAPTCHA gagal. Centang "I\'m not a robot" terlebih dahulu.';
        } elseif ($email && $password) {
            $stmt = $conn->prepare("SELECT id, nama_lengkap, email, password, role FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama']    = $user['nama_lengkap'];
                $_SESSION['email']   = $user['email'];
                $_SESSION['role']    = $user['role'];
                $_SESSION['login_attempts'] = 0; // reset
                header('Location: ../index.php'); exit;
            } else {
                $_SESSION['login_attempts']++;
                if ($_SESSION['login_attempts'] >= 5) {
                    $_SESSION['login_lockout'] = time() + 600; // kunci 10 menit
                    $_SESSION['login_attempts'] = 0;
                    $error = 'Akun dikunci 10 menit karena terlalu banyak percobaan gagal.';
                } else {
                    $sisa_coba = 5 - $_SESSION['login_attempts'];
                    $error = "Email atau password salah. Sisa percobaan: $sisa_coba kali.";
                }
            }
        } else {
            $error = 'Semua field wajib diisi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - Diskominfo</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
<div class="auth-page">
    <div class="auth-box">
        <div class="auth-logo">
            <img src="../Lambang Kabupaten Bogor - 2025.png" alt="Logo" class="logo-img" style="height:48px;width:auto;border-radius:0">
            <div class="auth-logo-text">
                <div class="title">DISKOMINFO</div>
                <div class="sub">BOGOR</div>
            </div>
        </div>
        <h2>Masuk</h2>
        <p class="subtitle">Masuk ke akun Anda untuk melanjutkan</p>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($is_locked): ?>
            <div class="alert alert-error">Akun sementara dikunci. Tunggu beberapa menit dan coba lagi.</div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="email@diskominfo.go.id" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="input-password">
                    <input type="password" id="pw_login" name="password" placeholder="Masukkan password" required>
                    <button type="button" class="toggle-pw" onclick="togglePw('pw_login',this)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                </div>
            </div>
            <div class="form-group">
                <div class="g-recaptcha" data-sitekey="<?= RECAPTCHA_SITE_KEY ?>"></div>
            </div>
            <button type="submit" class="btn-submit">Masuk</button>
        </form>
        <div class="auth-link">
            Belum punya akun? <a href="register.php">Daftar</a>
        </div>
        <div class="auth-link" style="margin-top:8px">
            <a href="lupa-password.php">Lupa password?</a>
        </div>
    </div>
</div>
</body>
</html>
<script>
function togglePw(id, btn) {
    const inp = document.getElementById(id);
    const eyeSvg = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
    const eyeOffSvg = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
    if (inp.type === 'password') { inp.type = 'text'; btn.innerHTML = eyeOffSvg; }
    else { inp.type = 'password'; btn.innerHTML = eyeSvg; }
}
</script>
