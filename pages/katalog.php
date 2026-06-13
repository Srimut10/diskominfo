<?php
require_once '../includes/header.php';

$tema_filter = isset($_GET['tema']) ? (int)$_GET['tema'] : 0;
$search      = trim($_GET['q'] ?? '');

$has_tema = $conn->query("SHOW COLUMNS FROM modul LIKE 'tema_id'")->num_rows > 0;

// Tema list untuk filter
$tema_list = $conn->query("SELECT id, judul FROM tema_pelatihan WHERE status='open' ORDER BY judul")->fetch_all(MYSQLI_ASSOC);

// Query modul
$where  = ["m.status = 'published'"];
$params = []; $types = '';
if ($search) { $where[] = "m.judul LIKE ?"; $params[] = "%$search%"; $types .= 's'; }
if ($tema_filter > 0 && $has_tema) {
    $any = $conn->query("SELECT COUNT(*) as c FROM modul WHERE tema_id=$tema_filter AND status='published'")->fetch_assoc()['c'];
    if ($any > 0) { $where[] = "m.tema_id = ?"; $params[] = $tema_filter; $types .= 'i'; }
}

$join_tema = $has_tema ? "LEFT JOIN tema_pelatihan tp ON m.tema_id = tp.id" : "";
$sel_tema  = $has_tema ? ", tp.id as tema_id, tp.judul as tema_judul, tp.status as tema_status" : ", NULL as tema_id, NULL as tema_judul, NULL as tema_status";

$sql = "SELECT m.* $sel_tema FROM modul m $join_tema
        WHERE " . implode(' AND ', $where) . " ORDER BY m.created_at DESC";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$moduls = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total_all = $conn->query("SELECT COUNT(*) as c FROM modul WHERE status='published'")->fetch_assoc()['c'];
?>
<div class="main-content">
<div class="katalog-layout">
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="filter-header" onclick="toggleFilter()">
            <h3>Filter</h3>
            <span class="filter-toggle-icon" id="filter-icon">&#8963;</span>
        </div>
        <div class="filter-body" id="filter-body">
        <form method="GET">
            <input type="text" name="q" class="sidebar-search" placeholder="Cari modul..." value="<?= htmlspecialchars($search) ?>">

            <ul class="filter-list" style="margin-bottom:16px">
                <li>
                    <a href="katalog.php<?= $search?'?q='.urlencode($search):'' ?>" class="<?= $tema_filter==0?'active':'' ?>">
                        Semua Modul <span class="filter-count"><?= $total_all ?></span>
                    </a>
                </li>
            </ul>

            <?php if (!empty($tema_list)): ?>
            <div style="font-size:12px;font-weight:600;color:#999;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px">Tema Pelatihan</div>
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
        </form>
        </div>
    </aside>

    <!-- KONTEN -->
    <div class="katalog-content">
        <h2>Katalog Modul Pelatihan</h2>
        <p>Temukan dan ikuti pelatihan yang tersedia
            <?php if ($tema_filter > 0):
                $tn = '';
                foreach ($tema_list as $t) { if ($t['id']==$tema_filter) { $tn=$t['judul']; break; } }
            ?>
            &mdash; <span style="color:#3f51b5">Tema: <?= htmlspecialchars($tn) ?></span>
            <?php endif; ?>
        </p>
        <div class="card-grid">
            <?php foreach ($moduls as $m): ?>
            <div class="card">
                <div class="card-thumb">
                    <?php if ($m['thumbnail']): ?>
                        <img src="../<?= htmlspecialchars($m['thumbnail']) ?>" alt="">
                    <?php else: ?>
                        <img src="https://picsum.photos/seed/<?= $m['id'] ?>/400/200" alt="">
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (!empty($m['tema_judul'])): ?>
                    <a href="katalog.php?tema=<?= $m['tema_id'] ?>" class="badge badge-internal" style="text-decoration:none;display:inline-block;margin-bottom:8px">
                        <?= htmlspecialchars($m['tema_judul']) ?>
                    </a>
                    <?php endif; ?>
                    <a href="detail.php?id=<?= $m['id'] ?>" class="card-title"><?= htmlspecialchars($m['judul']) ?></a>
                    <div class="card-meta">
                        <span><?= date('d/m/Y', strtotime($m['tanggal'])) ?></span>
                        <span><?= htmlspecialchars($m['instruktur'] ?? '') ?></span>
                        <span><?= htmlspecialchars($m['lokasi'] ?? '') ?></span>
                    </div>
                    <p class="card-desc"><?= htmlspecialchars($m['deskripsi']) ?></p>
                    <div class="card-footer">
                        <a href="detail.php?id=<?= $m['id'] ?>" class="link-detail">Selengkapnya</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($moduls)): ?>
                <p style="color:#999;grid-column:1/-1;text-align:center;padding:40px 0">Tidak ada modul ditemukan.</p>
            <?php endif; ?>
        </div>
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
</script>
<?php require_once '../includes/footer.php'; ?>
