<?php
require_once 'includes/header.php';

// Ambil modul terbaru
$query = "SELECT m.*, k.nama as kategori_nama FROM modul m 
          LEFT JOIN kategori k ON m.kategori_id = k.id 
          WHERE m.status = 'published' 
          ORDER BY m.created_at DESC LIMIT 4";
$result = $conn->query($query);
$moduls = $result->fetch_all(MYSQLI_ASSOC);

function badge_class($kat) {
    $map = ['Internal' => 'badge-internal', 'Kegiatan' => 'badge-kegiatan', 'External' => 'badge-external'];
    return $map[$kat] ?? 'badge-internal';
}
?>
<div class="main-content">

<!-- HERO -->
<section class="hero">
    <div class="hero-container">
        <div class="hero-label">Platform Pelatihan Digital</div>
        <h1>Modul Pelatihan Berbasis Web</h1>
        <p>Akses katalog modul pelatihan, daftar kegiatan, dan unduh materi pelatihan secara mudah dan terpusat</p>
        <a href="pages/katalog.php" class="btn-hero">Lihat Katalog &rarr;</a>
    </div>
</section>

<!-- MODUL TERBARU -->
<div class="section">
    <div class="section-header">
        <h2>Modul Terbaru</h2>
        <a href="pages/katalog.php" class="link-semua">Lihat Semua &rarr;</a>
    </div>
    <div class="card-grid">
        <?php foreach ($moduls as $m): ?>
        <div class="card">
            <div class="card-thumb">
                <?php if ($m['thumbnail']): ?>
                    <img src="<?= htmlspecialchars($m['thumbnail']) ?>" alt="">
                <?php else: ?>
                    <img src="https://picsum.photos/seed/<?= $m['id'] ?>/400/200" alt="">
                <?php endif; ?>
            </div>
            <div class="card-body">
                <span class="badge <?= badge_class($m['kategori_nama']) ?>"><?= htmlspecialchars($m['kategori_nama'] ?? 'Internal') ?></span>
                <a href="pages/detail.php?id=<?= $m['id'] ?>" class="card-title"><?= htmlspecialchars($m['judul']) ?></a>
                <div class="card-meta">
                    <span>&#128197; <?= date('d M Y', strtotime($m['tanggal'])) ?></span>
                    <span>&#128100; <?= htmlspecialchars($m['instruktur']) ?></span>
                    <span>&#128205; <?= htmlspecialchars($m['lokasi']) ?></span>
                </div>
                <p class="card-desc"><?= htmlspecialchars($m['deskripsi']) ?></p>
                <div class="card-footer">
                    <span class="card-views">&#128065; <?= $m['views'] ?></span>
                    <a href="pages/detail.php?id=<?= $m['id'] ?>" class="link-detail">Selengkapnya</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($moduls)): ?>
            <p style="color:#999;grid-column:1/-1;text-align:center;padding:40px 0">Belum ada modul tersedia.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
