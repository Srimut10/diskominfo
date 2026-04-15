<?php
require_once '../includes/header.php';

// Filter kategori
$kat_id = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;
$search = trim($_GET['q'] ?? '');

// Hitung per kategori
$kat_result = $conn->query("SELECT k.*, COUNT(m.id) as total FROM kategori k LEFT JOIN modul m ON m.kategori_id = k.id AND m.status='published' GROUP BY k.id");
$kategori_list = $kat_result->fetch_all(MYSQLI_ASSOC);

// Query modul
$where = ["m.status = 'published'"];
$params = []; $types = '';
if ($kat_id > 0) { $where[] = "m.kategori_id = ?"; $params[] = $kat_id; $types .= 'i'; }
if ($search) { $where[] = "m.judul LIKE ?"; $params[] = "%$search%"; $types .= 's'; }

$sql = "SELECT m.*, k.nama as kategori_nama FROM modul m LEFT JOIN kategori k ON m.kategori_id = k.id WHERE " . implode(' AND ', $where) . " ORDER BY m.created_at DESC";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$moduls = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

function badge_class($kat) {
    $map = ['Internal' => 'badge-internal', 'Kegiatan' => 'badge-kegiatan', 'External' => 'badge-external'];
    return $map[$kat] ?? 'badge-internal';
}

// Hitung total semua
$total_all = $conn->query("SELECT COUNT(*) as c FROM modul WHERE status='published'")->fetch_assoc()['c'];
?>
<div class="main-content">
<div class="katalog-layout">
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <h3>&#9776; Kategori</h3>
        <form method="GET">
            <input type="text" name="q" class="sidebar-search" placeholder="Cari modul..." value="<?= htmlspecialchars($search) ?>">
            <ul class="filter-list">
                <li>
                    <a href="katalog.php<?= $search ? '?q='.urlencode($search) : '' ?>" class="<?= $kat_id == 0 ? 'active' : '' ?>">
                        Semua <span class="filter-count"><?= $total_all ?></span>
                    </a>
                </li>
                <?php foreach ($kategori_list as $k): ?>
                <li>
                    <a href="katalog.php?kategori=<?= $k['id'] ?><?= $search ? '&q='.urlencode($search) : '' ?>" class="<?= $kat_id == $k['id'] ? 'active' : '' ?>">
                        <?= htmlspecialchars($k['nama']) ?> <span class="filter-count"><?= $k['total'] ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </form>
    </aside>

    <!-- KONTEN -->
    <div class="katalog-content">
        <h2>Katalog Modul Pelatihan</h2>
        <p>Temukan dan ikuti pelatihan yang tersedia</p>
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
                    <span class="badge <?= badge_class($m['kategori_nama']) ?>"><?= htmlspecialchars($m['kategori_nama'] ?? '') ?></span>
                    <a href="detail.php?id=<?= $m['id'] ?>" class="card-title"><?= htmlspecialchars($m['judul']) ?></a>
                    <div class="card-meta">
                        <span>&#128197; <?= date('d M Y', strtotime($m['tanggal'])) ?></span>
                        <span>&#128100; <?= htmlspecialchars($m['instruktur']) ?></span>
                        <span>&#128205; <?= htmlspecialchars($m['lokasi']) ?></span>
                    </div>
                    <p class="card-desc"><?= htmlspecialchars($m['deskripsi']) ?></p>
                    <div class="card-footer">
                        <span class="card-views">&#128065; <?= $m['views'] ?></span>
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
<?php require_once '../includes/footer.php'; ?>
