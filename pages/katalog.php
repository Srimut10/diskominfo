<?php
require_once '../includes/header.php';

$tema_filter = isset($_GET['tema']) ? (int)$_GET['tema'] : 0;
$search      = trim($_GET['q'] ?? '');
$has_tema    = $conn->query("SHOW COLUMNS FROM modul LIKE 'tema_id'")->num_rows > 0;
$tema_list   = $conn->query("SELECT id, judul FROM tema_pelatihan WHERE status='open' ORDER BY judul")->fetch_all(MYSQLI_ASSOC);

$where  = ["m.status = 'published'"];
$params = []; $types = '';
if ($search) { $where[] = "m.judul LIKE ?"; $params[] = "%$search%"; $types .= 's'; }
if ($tema_filter > 0 && $has_tema) {
    $any = $conn->query("SELECT COUNT(*) as c FROM modul WHERE tema_id=$tema_filter AND status='published'")->fetch_assoc()['c'];
    if ($any > 0) { $where[] = "m.tema_id = ?"; $params[] = $tema_filter; $types .= 'i'; }
}
$join = $has_tema ? "LEFT JOIN tema_pelatihan tp ON m.tema_id = tp.id" : "";
$sel  = $has_tema ? ", tp.id as tema_id, tp.judul as tema_judul" : ", NULL as tema_id, NULL as tema_judul";
$sql  = "SELECT m.* $sel FROM modul m $join WHERE " . implode(' AND ', $where) . " ORDER BY m.created_at DESC";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$moduls    = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$total_all = $conn->query("SELECT COUNT(*) as c FROM modul WHERE status='published'")->fetch_assoc()['c'];
?>
<div class="main-content">

<!-- KATALOG HERO MINI -->
<section class="katalog-hero">
    <div class="katalog-hero-inner">
        <h1 class="animate-on-scroll">Katalog Modul Pelatihan</h1>
        <p class="animate-on-scroll">Temukan dan ikuti pelatihan yang sesuai dengan kebutuhan pengembangan kompetensi Anda</p>
        <form method="GET" class="katalog-search-form animate-on-scroll">
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari modul pelatihan...">
            <button type="submit">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                Cari
            </button>
        </form>
    </div>
</section>

<div class="katalog-body">
    <!-- SIDEBAR -->
    <aside class="katalog-sidebar">
        <div class="filter-header" onclick="toggleFilter()">
            <h3>Filter</h3>
            <span id="filter-icon" class="filter-toggle-icon">&#8963;</span>
        </div>
        <div class="filter-body" id="filter-body">
            <ul class="filter-list">
                <li>
                    <a href="katalog.php<?= $search?'?q='.urlencode($search):'' ?>" class="<?= $tema_filter==0?'active':'' ?>">
                        Semua Modul <span class="filter-count"><?= $total_all ?></span>
                    </a>
                </li>
            </ul>
            <?php if (!empty($tema_list)): ?>
            <div class="filter-section-label">Tema Pelatihan</div>
            <ul class="filter-list">
                <?php foreach ($tema_list as $t): ?>
                <li>
                    <a href="katalog.php?tema=<?= $t['id'] ?><?= $search?'&q='.urlencode($search):'' ?>" class="<?= $tema_filter==$t['id']?'active':'' ?>">
                        <?= htmlspecialchars($t['judul']) ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </aside>

    <!-- KONTEN -->
    <div class="katalog-content">
        <div class="katalog-topbar">
            <div class="katalog-info">
                Menampilkan <strong><?= count($moduls) ?></strong> dari <strong><?= $total_all ?></strong> modul
                <?php if ($tema_filter > 0): $tn=''; foreach($tema_list as $t){if($t['id']==$tema_filter){$tn=$t['judul'];break;}} ?>
                — <span style="color:#3f51b5">Tema: <?= htmlspecialchars($tn) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($moduls)): ?>
        <div class="katalog-empty">
            <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#c5cae9" stroke-width="1.5"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
            <p>Tidak ada modul ditemukan.</p>
            <?php if ($search || $tema_filter): ?>
            <a href="katalog.php" class="btn-primary" style="margin-top:12px;display:inline-flex">Reset Filter</a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="katalog-grid">
            <?php foreach ($moduls as $m): ?>
            <a href="detail.php?id=<?= $m['id'] ?>" class="katalog-card animate-on-scroll">
                <div class="katalog-thumb">
                    <?php if ($m['thumbnail']): ?>
                        <img src="../<?= htmlspecialchars($m['thumbnail']) ?>" alt="<?= htmlspecialchars($m['judul']) ?>">
                    <?php else: ?>
                        <div class="katalog-thumb-placeholder">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#9fa8da" stroke-width="1.5"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($m['file_ppt'])): ?>
                    <span class="katalog-badge-materi">Materi Tersedia</span>
                    <?php endif; ?>
                </div>
                <div class="katalog-card-body">
                    <?php if (!empty($m['tema_judul'])): ?>
                    <span class="katalog-tema-tag"><?= htmlspecialchars($m['tema_judul']) ?></span>
                    <?php endif; ?>
                    <h4 class="katalog-card-judul"><?= htmlspecialchars($m['judul']) ?></h4>
                    <div class="katalog-card-meta">
                        <span>
                            <svg class="icon icon-sm" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <?= date('d/m/Y', strtotime($m['tanggal'])) ?>
                        </span>
                        <?php if ($m['instruktur']): ?>
                        <span>
                            <svg class="icon icon-sm" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <?= htmlspecialchars($m['instruktur']) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <p class="katalog-card-desc"><?= htmlspecialchars($m['deskripsi']) ?></p>
                    <div class="katalog-card-footer">
                        <span class="katalog-card-cta">Lihat Detail &rarr;</span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleFilter() {
    const body = document.getElementById('filter-body');
    const icon = document.getElementById('filter-icon');
    const isOpen = body.style.display !== 'none';
    body.style.display = isOpen ? 'none' : 'block';
    icon.textContent = isOpen ? '⌄' : '⌃';
}
if (window.innerWidth <= 768) {
    document.getElementById('filter-body').style.display = 'none';
    document.getElementById('filter-icon').textContent = '⌄';
}
const obs = new IntersectionObserver(e => {
    e.forEach(en => { if(en.isIntersecting){en.target.classList.add('visible');obs.unobserve(en.target);} });
},{threshold:0.1});
document.querySelectorAll('.animate-on-scroll').forEach(el=>obs.observe(el));
</script>

</div>
<?php require_once '../includes/footer.php'; ?>
