<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';

$current_page = basename($_SERVER['PHP_SELF']);
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platform Pelatihan Digital - Diskominfo</title>
    <?php
    $depth = substr_count(str_replace('\\','/',$_SERVER['PHP_SELF']), '/') - 2;
    $base = str_repeat('../', max(0, $depth));
    ?>
    <link rel="stylesheet" href="<?= $base ?>assets/css/style.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-container">
        <a href="<?= $base ?>index.php" class="nav-logo">
            <img src="<?= $base ?>Lambang Kabupaten Bogor - 2025.png" alt="Logo Diskominfo" class="logo-img">
            <div class="logo-text">
                <span class="logo-title">DISKOMINFO</span>
                <span class="logo-sub">BOGOR</span>
            </div>
        </a>
        <ul class="nav-links">
            <li><a href="<?= $base ?>index.php" class="<?= ($current_page == 'index.php') ? 'active' : '' ?>">Beranda</a></li>
            <li><a href="<?= $base ?>pages/katalog.php" class="<?= ($current_page == 'katalog.php') ? 'active' : '' ?>">Katalog</a></li>
            <li><a href="<?= $base ?>pages/tema.php" class="<?= ($current_page == 'tema.php') ? 'active' : '' ?>">Tema Pelatihan</a></li>
            <?php if ($is_admin): ?>
            <li><a href="<?= $base ?>admin/panel.php" class="<?= (strpos($_SERVER['PHP_SELF'], 'admin') !== false) ? 'active' : '' ?>">Admin</a></li>
            <?php endif; ?>
        </ul>
        <div class="nav-auth">
            <?php if ($is_logged_in): ?>
                <span class="user-email"><?= htmlspecialchars($_SESSION['email']) ?></span>
                <a href="<?= $base ?>auth/logout.php" class="btn-logout">Keluar</a>
            <?php else: ?>
                <a href="<?= $base ?>auth/login.php" class="btn-login">Masuk</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
