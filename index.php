<?php
require_once 'includes/header.php';

// Auto-create tables
$conn->query("CREATE TABLE IF NOT EXISTS hero_settings (id INT AUTO_INCREMENT PRIMARY KEY, hero_image VARCHAR(255) DEFAULT NULL, hero_judul VARCHAR(200) DEFAULT 'Platform Pelatihan Digital Diskominfo Bogor', hero_subjudul VARCHAR(300) DEFAULT 'Akses katalog modul pelatihan, daftar kegiatan, dan materi pelatihan secara mudah dan terpusat.', updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$conn->query("CREATE TABLE IF NOT EXISTS konten_beranda (id INT AUTO_INCREMENT PRIMARY KEY, judul VARCHAR(200) NOT NULL, deskripsi TEXT, gambar VARCHAR(255), tipe ENUM('pemberitaan','agenda','pengumuman') DEFAULT 'pemberitaan', tanggal DATE, status ENUM('published','draft') DEFAULT 'published', urutan INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$res = $conn->query("SELECT * FROM hero_settings LIMIT 1");
if ($res->num_rows === 0) $conn->query("INSERT INTO hero_settings (hero_judul, hero_subjudul) VALUES ('Platform Pelatihan Digital Diskominfo Bogor','Akses katalog modul pelatihan, daftar kegiatan, dan materi pelatihan secara mudah dan terpusat.')");
$hero = $conn->query("SELECT * FROM hero_settings LIMIT 1")->fetch_assoc();
$kontens = $conn->query("SELECT * FROM konten_beranda WHERE status='published' ORDER BY urutan ASC, created_at DESC")->fetch_all(MYSQLI_ASSOC);

// Stats
$stat_modul   = $conn->query("SELECT COUNT(*) as c FROM modul WHERE status='published'")->fetch_assoc()['c'];
$stat_tema    = $conn->query("SELECT COUNT(*) as c FROM tema_pelatihan WHERE status='open'")->fetch_assoc()['c'];

// Modul terbaru
$moduls = $conn->query("SELECT m.* FROM modul m WHERE m.status='published' ORDER BY m.created_at DESC LIMIT 6")->fetch_all(MYSQLI_ASSOC);
?>
<div class="main-content">

<!-- ===== HERO ===== -->
<section class="hero-fullscreen" <?php if ($hero['hero_image']): ?>style="background-image:url('<?= htmlspecialchars($hero['hero_image']) ?>')"<?php endif; ?>>
    <div class="hero-overlay"></div>
    <!-- Dekorasi pattern jika tidak ada foto -->
    <?php if (!$hero['hero_image']): ?>
    <div class="hero-pattern">
        <svg class="hero-circle hero-circle-1" viewBox="0 0 200 200"><circle cx="100" cy="100" r="100" fill="rgba(255,255,255,0.04)"/></svg>
        <svg class="hero-circle hero-circle-2" viewBox="0 0 300 300"><circle cx="150" cy="150" r="150" fill="rgba(255,255,255,0.04)"/></svg>
        <svg class="hero-circle hero-circle-3" viewBox="0 0 150 150"><circle cx="75" cy="75" r="75" fill="rgba(255,255,255,0.06)"/></svg>
    </div>
    <?php endif; ?>
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

<!-- ===== MODUL POPULER ===== -->
<?php if (!empty($moduls)): ?>
<section class="populer-section">
    <div class="section-container">
        <div class="section-label-top animate-on-scroll">Katalog</div>
        <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:8px">
            <h2 class="section-title animate-on-scroll" style="margin-bottom:0">Modul Pelatihan Terbaru</h2>
            <a href="pages/katalog.php" class="link-semua animate-on-scroll">Lihat Semua &rarr;</a>
        </div>
        <p class="section-subtitle animate-on-scroll" style="margin-bottom:32px">Temukan modul pelatihan yang sesuai dengan kebutuhan pengembangan kompetensi Anda.</p>
        <div class="populer-grid">
            <?php foreach ($moduls as $m): ?>
            <a href="pages/detail.php?id=<?= $m['id'] ?>" class="populer-card animate-on-scroll">
                <div class="populer-thumb">
                    <?php if ($m['thumbnail']): ?>
                        <img src="<?= htmlspecialchars($m['thumbnail']) ?>" alt="<?= htmlspecialchars($m['judul']) ?>">
                    <?php else: ?>
                        <div class="populer-thumb-placeholder">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#9fa8da" stroke-width="1.5"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                        </div>
                    <?php endif; ?>
                    <div class="populer-overlay">
                        <span class="populer-cta">Lihat Detail</span>
                    </div>
                </div>
                <div class="populer-body">
                    <h4 class="populer-judul"><?= htmlspecialchars($m['judul']) ?></h4>
                    <div class="populer-meta">
                        <span><?= date('d/m/Y', strtotime($m['tanggal'])) ?></span>
                        <?php if ($m['lokasi']): ?><span><?= htmlspecialchars($m['lokasi']) ?></span><?php endif; ?>
                    </div>
                    <p class="populer-desc"><?= htmlspecialchars($m['deskripsi']) ?></p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ===== PEMBERITAAN & AGENDA ===== -->
<?php if (!empty($kontens)): ?>
<section class="berita-section">
    <div class="section-container">
        <div class="section-label-top animate-on-scroll">Informasi</div>
        <h2 class="section-title animate-on-scroll">Pemberitaan &amp; Agenda</h2>
        <div class="berita-grid">
            <?php foreach ($kontens as $k): ?>
            <div class="berita-card animate-on-scroll">
                <?php if ($k['gambar']): ?>
                <div class="berita-img"><img src="<?= htmlspecialchars($k['gambar']) ?>" alt="<?= htmlspecialchars($k['judul']) ?>"></div>
                <?php else: ?>
                <div class="berita-img berita-img-placeholder">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#9fa8da" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                </div>
                <?php endif; ?>
                <div class="berita-body">
                    <span class="berita-tipe berita-tipe-<?= $k['tipe'] ?>"><?= ucfirst($k['tipe']) ?></span>
                    <?php if ($k['tanggal']): ?><div class="berita-tgl"><?= date('d F Y', strtotime($k['tanggal'])) ?></div><?php endif; ?>
                    <h3 class="berita-judul"><?= htmlspecialchars($k['judul']) ?></h3>
                    <?php if ($k['deskripsi']): ?><p class="berita-desc"><?= htmlspecialchars($k['deskripsi']) ?></p><?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php elseif ($is_admin): ?>
<section class="berita-section">
    <div class="section-container" style="text-align:center;padding:40px">
        <p style="color:#aaa">Belum ada pemberitaan. <a href="admin/panel.php?tab=beranda" style="color:#3f51b5">+ Tambah Konten</a></p>
    </div>
</section>
<?php endif; ?>

</div>

<script>
// Animated counter
function animateCounter(el) {
    const target = parseInt(el.dataset.target) || 0;
    const duration = 1800;
    const step = Math.ceil(target / (duration / 16));
    let current = 0;
    const timer = setInterval(() => {
        current = Math.min(current + step, target);
        el.textContent = current.toLocaleString('id-ID');
        if (current >= target) clearInterval(timer);
    }, 16);
}

// Scroll animations
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            // Trigger counter if it's a stat number
            const counters = entry.target.querySelectorAll('.stat-counter-num[data-target]');
            counters.forEach(animateCounter);
            // If the element itself is a counter parent
            if (entry.target.classList.contains('stat-counter-item')) {
                const num = entry.target.querySelector('.stat-counter-num');
                if (num && !num.dataset.animated) {
                    num.dataset.animated = '1';
                    animateCounter(num);
                }
            }
            observer.unobserve(entry.target);
        }
    });
}, { threshold: 0.15 });

document.querySelectorAll('.animate-on-scroll').forEach(el => observer.observe(el));
</script>

<?php require_once 'includes/footer.php'; ?>
