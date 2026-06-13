<?php
require_once 'includes/header.php';

// Auto-create tables
$conn->query("CREATE TABLE IF NOT EXISTS hero_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hero_image VARCHAR(255) DEFAULT NULL,
    hero_judul VARCHAR(200) DEFAULT 'Platform Pelatihan Digital Diskominfo Bogor',
    hero_subjudul VARCHAR(300) DEFAULT 'Akses katalog modul pelatihan, daftar kegiatan, dan materi pelatihan secara mudah dan terpusat.',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS konten_beranda (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(200) NOT NULL,
    deskripsi TEXT,
    gambar VARCHAR(255),
    tipe ENUM('pemberitaan','agenda','pengumuman') DEFAULT 'pemberitaan',
    tanggal DATE,
    status ENUM('published','draft') DEFAULT 'published',
    urutan INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$res = $conn->query("SELECT * FROM hero_settings LIMIT 1");
if ($res->num_rows === 0) {
    $conn->query("INSERT INTO hero_settings (hero_judul, hero_subjudul) VALUES ('Platform Pelatihan Digital Diskominfo Bogor','Akses katalog modul pelatihan, daftar kegiatan, dan materi pelatihan secara mudah dan terpusat.')");
}
$hero = $conn->query("SELECT * FROM hero_settings LIMIT 1")->fetch_assoc();

// Ambil konten beranda yang published, urut by urutan ASC, created_at DESC
$kontens = $conn->query("SELECT * FROM konten_beranda WHERE status='published' ORDER BY urutan ASC, created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<div class="main-content">

<!-- HERO FULLSCREEN -->
<section class="hero-fullscreen" <?php if ($hero['hero_image']): ?>style="background-image:url('<?= htmlspecialchars($hero['hero_image']) ?>')"<?php endif; ?>>
    <div class="hero-overlay"></div>
    <div class="hero-fullscreen-content">
        <div class="hero-fullscreen-label">Platform Pelatihan Digital</div>
        <h1 class="hero-fullscreen-title"><?= htmlspecialchars($hero['hero_judul']) ?></h1>
        <p class="hero-fullscreen-sub"><?= htmlspecialchars($hero['hero_subjudul']) ?></p>
        <div class="hero-fullscreen-btns">
            <a href="pages/katalog.php" class="btn-hero-outline">Lihat Katalog</a>
            <a href="pages/tema.php" class="btn-hero-solid">Daftar Pelatihan</a>
        </div>
    </div>
    <div class="hero-scroll-indicator">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
    </div>
</section>

<!-- KONTEN BERANDA: PEMBERITAAN & AGENDA -->
<div class="section">
    <div class="section-header">
        <h2>Pemberitaan &amp; Agenda</h2>
    </div>
    <?php if (!empty($kontens)): ?>
    <div class="konten-grid">
        <?php foreach ($kontens as $k): ?>
        <div class="konten-card">
            <?php if ($k['gambar']): ?>
            <div class="konten-img">
                <img src="<?= htmlspecialchars($k['gambar']) ?>" alt="<?= htmlspecialchars($k['judul']) ?>">
                <span class="konten-tipe konten-tipe-<?= $k['tipe'] ?>"><?= ucfirst($k['tipe']) ?></span>
            </div>
            <?php else: ?>
            <div class="konten-img konten-img-placeholder">
                <span class="konten-tipe konten-tipe-<?= $k['tipe'] ?>"><?= ucfirst($k['tipe']) ?></span>
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#9fa8da" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            </div>
            <?php endif; ?>
            <div class="konten-body">
                <?php if ($k['tanggal']): ?>
                <div class="konten-tanggal"><?= date('d F Y', strtotime($k['tanggal'])) ?></div>
                <?php endif; ?>
                <h3 class="konten-judul"><?= htmlspecialchars($k['judul']) ?></h3>
                <?php if ($k['deskripsi']): ?>
                <p class="konten-desc"><?= htmlspecialchars($k['deskripsi']) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="text-align:center;padding:60px 20px;color:#aaa">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#c5cae9" stroke-width="1.5" style="margin-bottom:12px"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
        <p style="font-size:15px">Belum ada pemberitaan atau agenda.</p>
        <?php if ($is_admin): ?>
        <a href="admin/panel.php?tab=beranda" style="color:#3f51b5;font-size:14px;margin-top:8px;display:inline-block">+ Tambah Konten di Panel Admin</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

</div>
<?php require_once 'includes/footer.php'; ?>
