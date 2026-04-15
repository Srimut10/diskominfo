<?php
session_start();
if (isset($_SESSION['user_id'])) { header('Location: ../index.php'); exit; }
require_once '../config/db.php';

$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($nama && $email && $password) {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'Email sudah terdaftar.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (nama_lengkap, email, password, role) VALUES (?, ?, ?, 'user')");
            $stmt->bind_param("sss", $nama, $email, $hash);
            if ($stmt->execute()) {
                $success = 'Akun berhasil dibuat. <a href="login.php">Masuk sekarang</a>';
            } else {
                $error = 'Gagal membuat akun.';
            }
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
</head>
<body>
<div class="auth-page">
    <div class="auth-box">
        <div class="auth-logo">
            <img src="https://picsum.photos/seed/diskominfo/44/44" alt="Logo">
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
                <input type="password" name="password" placeholder="Buat password" required>
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
