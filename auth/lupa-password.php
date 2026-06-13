<?php
session_start();
require_once '../config/db.php';

// Cek apakah kolom user_id ada, kalau tidak drop dan buat ulang
$col = $conn->query("SHOW COLUMNS FROM password_reset LIKE 'user_id'");
if ($col === false || $col->num_rows === 0) {
    $conn->query("DROP TABLE IF EXISTS password_reset");
}
// Buat tabel password_reset jika belum ada
$conn->query("CREATE TABLE IF NOT EXISTS password_reset (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$step  = $_GET['step'] ?? 'request';
$token = $_GET['token'] ?? '';
$error = ''; $success = '';

// STEP 2: Reset password via token
if ($step === 'reset' && $token) {
    $stmt = $conn->prepare("SELECT * FROM password_reset WHERE token=? AND expires_at > NOW() AND used=0");
    $stmt->bind_param("s", $token); $stmt->execute();
    $reset = $stmt->get_result()->fetch_assoc();
    if (!$reset) {
        $error = 'Link reset tidak valid atau sudah kadaluarsa.';
        $step  = 'invalid';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $baru  = $_POST['password_baru'] ?? '';
        $ulang = $_POST['password_ulang'] ?? '';
        if (strlen($baru) < 6) {
            $error = 'Password minimal 6 karakter.';
        } elseif ($baru !== $ulang) {
            $error = 'Konfirmasi password tidak cocok.';
        } else {
            $hash = password_hash($baru, PASSWORD_DEFAULT);
            $upd  = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $upd->bind_param("si", $hash, $reset['user_id']); $upd->execute();
            $conn->query("UPDATE password_reset SET used=1 WHERE token='".mysqli_real_escape_string($conn,$token)."'");
            $success = 'Password berhasil direset. <a href="login.php">Masuk sekarang</a>';
            $step = 'done';
        }
    }
}

// STEP 1: Request reset
if ($step === 'request' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!$email) {
        $error = 'Masukkan email Anda.';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
        $stmt->bind_param("s", $email); $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        if (!$user) {
            $error = 'Email tidak ditemukan.';
        } else {
            $token_baru = bin2hex(random_bytes(32));
            $expires    = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $conn->query("DELETE FROM password_reset WHERE user_id={$user['id']}");
            $ins = $conn->prepare("INSERT INTO password_reset (user_id, token, expires_at) VALUES (?,?,?)");
            $ins->bind_param("iss", $user['id'], $token_baru, $expires); $ins->execute();

            $reset_url = (isset($_SERVER['HTTPS'])?'https':'http') . '://' . $_SERVER['HTTP_HOST']
                       . dirname($_SERVER['PHP_SELF']) . '/lupa-password.php?step=reset&token=' . $token_baru;
            $success = 'Link reset berhasil dibuat. Salin link di bawah ini:<br>
                <div style="background:#f0f4ff;border:1px solid #c5cae9;padding:12px;border-radius:8px;word-break:break-all;margin-top:10px;font-size:13px">
                <a href="'.htmlspecialchars($reset_url).'">'.htmlspecialchars($reset_url).'</a></div>
                <small style="color:#888;display:block;margin-top:6px">Link berlaku 1 jam.</small>';
            $step = 'sent';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Diskominfo</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="auth-page">
    <div class="auth-box">
        <div class="auth-logo">
            <img src="../logo diskom.png" alt="Logo" class="logo-img" style="height:48px;width:auto;border-radius:0">
            <div class="auth-logo-text">
                <div class="title">DISKOMINFO</div>
                <div class="sub">BOGOR</div>
            </div>
        </div>

        <?php if ($step === 'request' || $step === 'sent'): ?>
            <h2>Lupa Password</h2>
            <p class="subtitle">Masukkan email akun Anda untuk mendapatkan link reset</p>
            <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
            <?php if ($step !== 'sent'): ?>
            <form method="POST">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="email@diskominfo.go.id" required>
                </div>
                <button type="submit" class="btn-submit">Kirim Link Reset</button>
            </form>
            <?php endif; ?>

        <?php elseif ($step === 'reset'): ?>
            <h2>Reset Password</h2>
            <p class="subtitle">Masukkan password baru Anda</p>
            <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label>Password Baru</label>
                    <div class="input-password">
                        <input type="password" name="password_baru" id="pw_baru" placeholder="Minimal 6 karakter" required>
                        <button type="button" class="toggle-pw" onclick="togglePw('pw_baru',this)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                    </div>
                </div>
                <div class="form-group">
                    <label>Konfirmasi Password Baru</label>
                    <div class="input-password">
                        <input type="password" name="password_ulang" id="pw_ulang" placeholder="Ulangi password baru" required>
                        <button type="button" class="toggle-pw" onclick="togglePw('pw_ulang',this)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                    </div>
                </div>
                <button type="submit" class="btn-submit">Reset Password</button>
            </form>

        <?php elseif ($step === 'done'): ?>
            <h2>Password Direset</h2>
            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

        <?php elseif ($step === 'invalid'): ?>
            <h2>Link Tidak Valid</h2>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="auth-link" style="margin-top:16px">
            <a href="login.php">&larr; Kembali ke halaman masuk</a>
        </div>
    </div>
</div>
<script>
function togglePw(id, btn) {
    const inp = document.getElementById(id);
    const eyeSvg = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
    const eyeOffSvg = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
    if (inp.type === 'password') { inp.type = 'text'; btn.innerHTML = eyeOffSvg; }
    else { inp.type = 'password'; btn.innerHTML = eyeSvg; }
}
</script>
</body>
</html>
