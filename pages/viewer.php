<?php
require_once '../includes/header.php';

// Wajib login
if (!$is_logged_in) {
    header('Location: ../auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header('Location: katalog.php'); exit; }

$stmt = $conn->prepare("SELECT m.*, k.nama as kategori_nama FROM modul m LEFT JOIN kategori k ON m.kategori_id = k.id WHERE m.id = ? AND m.status = 'published'");
$stmt->bind_param("i", $id);
$stmt->execute();
$m = $stmt->get_result()->fetch_assoc();
if (!$m || !$m['file_ppt']) { header('Location: detail.php?id=' . $id); exit; }

// Path file dari root
$file_path = '../' . $m['file_ppt'];
$ext = strtolower(pathinfo($m['file_ppt'], PATHINFO_EXTENSION));
?>
<div class="main-content">
<div class="viewer-page">
    <div class="viewer-header">
        <a href="detail.php?id=<?= $id ?>" class="btn-back">&larr; Kembali ke Detail</a>
        <h2><?= htmlspecialchars($m['judul']) ?></h2>
        <span class="viewer-info">Materi hanya dapat dilihat, tidak dapat diunduh</span>
    </div>
    <div class="ppt-viewer-wrap">
        <?php if ($ext === 'pdf'): ?>
            <iframe
                src="../<?= htmlspecialchars($m['file_ppt']) ?>#toolbar=0&navpanes=0&scrollbar=1&view=FitH"
                class="ppt-iframe"
                frameborder="0"
                oncontextmenu="return false;"
            ></iframe>
        <?php else: ?>
            <div class="viewer-unsupported">
                <div style="font-size:48px;margin-bottom:16px"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
                <p>Format file <strong><?= strtoupper($ext) ?></strong> tidak dapat ditampilkan langsung di browser.</p>
                <p style="margin-top:8px;color:#888;font-size:13px">Minta admin untuk mengupload ulang materi dalam format <strong>PDF</strong>.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
</div>
<?php require_once '../includes/footer.php'; ?>
