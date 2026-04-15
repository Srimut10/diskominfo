<?php
session_start();
if (isset($_SESSION['user_id'])) { header('Location: ../index.php'); exit; }
require_once '../config/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = $conn->prepare("SELECT id, nama_lengkap, email, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama'] = $user['nama_lengkap'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            header('Location: ../index.php');
            exit;
        } else {
            $error = 'Email atau password salah.';
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
    <title>Masuk - Diskominfo</title>
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
        <h2>Masuk</h2>
        <p class="subtitle">Masuk ke akun Anda untuk melanjutkan</p>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="email@diskominfo.go.id" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Masukkan password" required>
            </div>
            <button type="submit" class="btn-submit">Masuk</button>
        </form>
        <div class="auth-link">
            Belum punya akun? <a href="register.php">Daftar</a>
        </div>
    </div>
</div>
</body>
</html>
