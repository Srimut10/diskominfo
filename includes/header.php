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
    // Cara paling reliable: gunakan path absolut dari server
    // Ini bekerja di localhost maupun hosting tanpa perlu hitung depth
    $project_root_real = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    $doc_root_real     = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
    
    // Path project relatif dari document root
    $project_web_path = trim(str_replace($doc_root_real, '', $project_root_real), '/');
    
    // Base URL absolut ke project root
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'];
    $base_url = $protocol . '://' . $host . ($project_web_path ? '/' . $project_web_path . '/' : '/');
    
    // Relative base (untuk link antar halaman)
    $script_dir_real = str_replace('\\', '/', realpath(dirname($_SERVER['SCRIPT_FILENAME'])));
    $rel = ltrim(str_replace($project_root_real, '', $script_dir_real), '/');
    $depth = $rel ? count(array_filter(explode('/', $rel))) : 0;
    $base = str_repeat('../', $depth);
    ?>
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/style.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-container">
        <a href="<?= $base ?>index.php" class="nav-logo">
            <img src="<?= $base ?>Lambang Kabupaten Bogor - 2025.png" alt="Logo Diskominfo" class="logo-img">
            <div class="logo-text">
                <span class="logo-title">DISKOMINFO</span>
                <span class="logo-sub">KABUPATEN BOGOR</span>
            </div>
        </a>
        <ul class="nav-links">
            <li><a href="<?= $base ?>index.php" class="<?= $current_page=='index.php'?'active':'' ?>">Beranda</a></li>
            <li><a href="<?= $base ?>pages/katalog.php" class="<?= $current_page=='katalog.php'?'active':'' ?>">Katalog</a></li>
            <li><a href="<?= $base ?>pages/tema.php" class="<?= $current_page=='tema.php'?'active':'' ?>">Tema Pelatihan</a></li>
            <?php if ($is_admin): ?>
            <li><a href="<?= $base ?>admin/panel.php" class="<?= strpos($_SERVER['PHP_SELF'],'admin')!==false?'active':'' ?>">Admin</a></li>
            <?php endif; ?>
        </ul>
        <div class="nav-auth">
            <?php if ($is_logged_in): ?>
                <span class="user-email"><?= htmlspecialchars($_SESSION['email']) ?></span>
                <a href="<?= $base ?>auth/ganti-password.php" class="btn-ganti-pass" title="Ganti Password">Ganti Sandi</a>
                <a href="<?= $base ?>auth/logout.php" class="btn-logout">Keluar</a>
            <?php else: ?>
                <a href="<?= $base ?>auth/login.php" class="btn-login">Masuk</a>
            <?php endif; ?>
        </div>
        <!-- Hamburger -->
        <button class="nav-hamburger" id="hamburger" onclick="toggleMobileMenu()" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
    </div>
</nav>

<!-- Mobile Menu -->
<div class="mobile-menu-overlay" id="mobile-overlay" onclick="toggleMobileMenu()"></div>
<div class="mobile-menu" id="mobile-menu">
    <ul>
        <li><a href="<?= $base ?>index.php" class="<?= $current_page=='index.php'?'active':'' ?>">Beranda</a></li>
        <li><a href="<?= $base ?>pages/katalog.php" class="<?= $current_page=='katalog.php'?'active':'' ?>">Katalog</a></li>
        <li><a href="<?= $base ?>pages/tema.php" class="<?= $current_page=='tema.php'?'active':'' ?>">Tema Pelatihan</a></li>
        <?php if ($is_admin): ?>
        <li><a href="<?= $base ?>admin/panel.php">Admin</a></li>
        <?php endif; ?>
    </ul>
    <div class="mobile-auth">
        <?php if ($is_logged_in): ?>
            <span class="user-email"><?= htmlspecialchars($_SESSION['email']) ?></span>
            <a href="<?= $base ?>auth/ganti-password.php" class="btn-ganti-pass"><svg class="icon" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> Ganti Password</a>
            <a href="<?= $base ?>auth/logout.php" class="btn-logout">Keluar</a>
        <?php else: ?>
            <a href="<?= $base ?>auth/login.php" class="btn-login">Masuk</a>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleMobileMenu() {
    document.getElementById('mobile-menu').classList.toggle('open');
    document.getElementById('mobile-overlay').classList.toggle('open');
    document.getElementById('hamburger').classList.toggle('open');
}
</script>
