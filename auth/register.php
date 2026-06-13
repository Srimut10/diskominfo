<?php
session_start();
if (isset($_SESSION['user_id'])) { header('Location: ../index.php'); exit; }
require_once '../config/db.php';
require_once '../config/captcha.php';

$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $recap    = $_POST['g-recaptcha-response'] ?? '';

    if (!verify_recaptcha($recap)) {
        $error = 'Verifikasi reCAPTCHA gagal. Centang "I\'m not a robot" terlebih dahulu.';
    } elseif ($nama && $email && $password) {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email); $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'Email sudah terdaftar.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (nama_lengkap, email, password, role) VALUES (?, ?, ?, 'user')");
            $stmt->bind_param("sss", $nama, $email, $hash);
            $success = $stmt->execute() ? 'Akun berhasil dibuat. <a href="login.php">Masuk sekarang</a>' : '';
            if (!$success) $error = 'Gagal membuat akun.';
        }
    } else {
        $error = 'Semua field wajib diisi.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - Diskominfo</title>
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
        <h2>Daftar Akun</h2>
        <p class="subtitle">Buat akun baru untuk mengakses platform pelatihan</p>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama" placeholder="Nama Lengkap Anda" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="email@diskominfo.go.id" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="input-password">
                    <input type="password" id="pw_reg" name="password" placeholder="Buat password" required>
                    <button type="button" class="toggle-pw" onclick="togglePw('pw_reg',this)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                </div>
            </div>
            <div class="form-group">
                <div class="g-recaptcha" data-sitekey="<?= RECAPTCHA_SITE_KEY ?>"></div>
            </div>
            <button type="submit" class="btn-submit">Daftar</button>
        </form>
        <div class="auth-link">
            Sudah punya akun? <a href="login.php">Masuk</a>
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
