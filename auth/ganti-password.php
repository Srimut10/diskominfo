<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once '../config/db.php';

$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lama  = $_POST['password_lama'] ?? '';
    $baru  = $_POST['password_baru'] ?? '';
    $ulang = $_POST['password_ulang'] ?? '';

    if (!$lama || !$baru || !$ulang) {
        $error = 'Semua field wajib diisi.';
    } elseif (strlen($baru) < 6) {
        $error = 'Password baru minimal 6 karakter.';
    } elseif ($baru !== $ulang) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $uid  = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT password FROM users WHERE id=?");
        $stmt->bind_param("i", $uid); $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        if (!password_verify($lama, $user['password'])) {
            $error = 'Password lama tidak sesuai.';
        } else {
            $hash = password_hash($baru, PASSWORD_DEFAULT);
            $upd  = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $upd->bind_param("si", $hash, $uid); $upd->execute();
            $success = 'Password berhasil diubah.';
        }
    }
}
?>
<?php
require_once '../includes/header.php';
?>
<div class="main-content">
<div class="detail-page" style="max-width:480px">
    <a href="../index.php" class="btn-back">&larr; Kembali</a>
    <h1 style="font-size:22px;font-weight:700;margin:16px 0 6px">Ganti Password</h1>
    <p style="color:#888;font-size:14px;margin-bottom:24px">Ubah password akun Anda</p>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" style="background:#fff;padding:24px;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.07)">
        <div class="form-group">
            <label>Password Lama</label>
            <div class="input-password">
                <input type="password" id="pw_lama" name="password_lama" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px" required>
                <button type="button" class="toggle-pw" onclick="togglePw('pw_lama',this)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
            </div>
        </div>
        <div class="form-group">
            <label>Password Baru</label>
            <div class="input-password">
                <input type="password" id="pw_baru" name="password_baru" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px" required>
                <button type="button" class="toggle-pw" onclick="togglePw('pw_baru',this)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
            </div>
        </div>
        <div class="form-group">
            <label>Konfirmasi Password Baru</label>
            <div class="input-password">
                <input type="password" id="pw_ulang" name="password_ulang" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px" required>
                <button type="button" class="toggle-pw" onclick="togglePw('pw_ulang',this)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
            </div>
        </div>
        <button type="submit" class="btn-submit" style="width:100%;padding:12px;background:#3f51b5;color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer">
            Simpan Password Baru
        </button>
    </form>
</div>
<?php require_once '../includes/footer.php'; ?>
<script>
function togglePw(id, btn) {
    const inp = document.getElementById(id);
    const eyeSvg = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
    const eyeOffSvg = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
    if (inp.type === 'password') { inp.type = 'text'; btn.innerHTML = eyeOffSvg; }
    else { inp.type = 'password'; btn.innerHTML = eyeSvg; }
}
</script>
