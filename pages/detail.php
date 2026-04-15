<?php
require_once '../includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header('Location: katalog.php'); exit; }

$stmt = $conn->prepare("SELECT m.*, k.nama as kategori_nama FROM modul m LEFT JOIN kategori k ON m.kategori_id = k.id WHERE m.id = ? AND m.status = 'published'");
$stmt->bind_param("i", $id);
$stmt->execute();
$m = $stmt->get_result()->fetch_assoc();
if (!$m) { header('Location: katalog.php'); exit; }

$conn->query("UPDATE modul SET views = views + 1 WHERE id = $id");

function badge_class($kat) {
    $map = ['Internal' => 'badge-internal', 'Kegiatan' => 'badge-kegiatan', 'External' => 'badge-external'];
    return $map[$kat] ?? 'badge-internal';
}
?>
<div class="main-content">
<div class="detail-page">
    <a href="katalog.php" class="btn-back">&larr; Kembali ke Katalog</a>
    <div class="detail-thumb">
        <?php if ($m['thumbnail']): ?>
            <img src="../<?= htmlspecialchars($m['thumbnail']) ?>" alt="<?= htmlspecialchars($m['judul']) ?>">
        <?php else: ?>
            <img src="https://picsum.photos/seed/<?= $m['id'] ?>/900/320" alt="">
        <?php endif; ?>
    </div>
    <span class="badge <?= badge_class($m['kategori_nama']) ?>"><?= htmlspecialchars($m['kategori_nama'] ?? '') ?></span>
    <h1 style="font-size:26px;font-weight:700;margin:12px 0 16px"><?= htmlspecialchars($m['judul']) ?></h1>
    <div class="detail-meta">
        <span>&#128197; <?= date('d M Y', strtotime($m['tanggal'])) ?></span>
        <span>&#128100; <?= htmlspecialchars($m['instruktur']) ?></span>
        <span>&#128205; <?= htmlspecialchars($m['lokasi']) ?></span>
        <span>&#128065; <?= $m['views'] ?> dilihat</span>
    </div>
    <div class="detail-desc">
        <?= nl2br(htmlspecialchars($m['deskripsi'])) ?>
    </div>

    <?php if ($m['file_ppt']): ?>
    <div class="materi-box">
        <div class="materi-icon">&#128196;</div>
        <div class="materi-info">
            <strong>Materi PowerPoint Tersedia</strong>
            <p>Buka dan pelajari materi presentasi pelatihan ini secara online.</p>
        </div>
        <?php if ($is_logged_in): ?>
            <a href="viewer.php?id=<?= $m['id'] ?>" class="btn-primary">&#128065; Buka Materi</a>
        <?php else: ?>
            <a href="../auth/login.php?redirect=<?= urlencode('pages/viewer.php?id='.$m['id']) ?>" class="btn-login-required">
                &#128274; Login untuk Membuka
            </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
<?php require_once '../includes/footer.php'; ?>
