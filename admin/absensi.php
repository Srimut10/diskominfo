<?php
require_once '../includes/header.php';
if (!$is_admin) { header('Location: ../index.php'); exit; }

// Pastikan kolom ada
$conn->query("ALTER TABLE pendaftaran ADD COLUMN IF NOT EXISTS nama_bank VARCHAR(100) DEFAULT NULL");
$conn->query("ALTER TABLE pendaftaran ADD COLUMN IF NOT EXISTS doc_ktp VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE pendaftaran ADD COLUMN IF NOT EXISTS doc_npwp VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE pendaftaran ADD COLUMN IF NOT EXISTS doc_rekening VARCHAR(255) DEFAULT NULL");

$tema_id = isset($_GET['tema']) ? (int)$_GET['tema'] : 0;
$temas   = $conn->query("SELECT * FROM tema_pelatihan ORDER BY tanggal DESC")->fetch_all(MYSQLI_ASSOC);

$peserta = [];
$tema_aktif = null;
if ($tema_id) {
    $tema_aktif = $conn->query("SELECT * FROM tema_pelatihan WHERE id=$tema_id")->fetch_assoc();
    $peserta = $conn->query("
        SELECT pd.*, u.email as user_email
        FROM pendaftaran pd
        LEFT JOIN users u ON pd.user_id = u.id
        WHERE pd.tema_id = $tema_id AND pd.cancelled_at IS NULL
        ORDER BY TRIM(LOWER(pd.nama_lengkap)) ASC
    ")->fetch_all(MYSQLI_ASSOC);
}

$total = count($peserta);
$hadir = count(array_filter($peserta, fn($p) => $p['hadir']));
$belum = $total - $hadir;
?>
<div class="main-content">
<div class="admin-page">
    <div class="admin-header">
        <div>
            <h1>Laporan Absensi</h1>
            <p>Data kehadiran peserta per tema pelatihan</p>
        </div>
        <div style="display:flex;gap:8px">
            <?php if ($tema_id): ?>
            <a href="export-absensi.php?tema=<?= $tema_id ?>" class="btn-primary" style="background:#2e7d32">Download Excel</a>
            <?php endif; ?>
            <a href="panel.php" class="btn-primary" style="background:#666">Kembali</a>
        </div>
    </div>

    <!-- Pilih Tema -->
    <div class="card-form" style="margin-bottom:24px">
        <form method="GET" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
            <div style="flex:1;min-width:200px">
                <label style="font-size:13px;font-weight:600;color:#555;display:block;margin-bottom:6px">Pilih Tema Pelatihan</label>
                <select name="tema" class="form-control" style="width:100%">
                    <option value="">-- Pilih Tema --</option>
                    <?php foreach ($temas as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= $tema_id==$t['id']?'selected':'' ?>>
                        <?= htmlspecialchars($t['judul']) ?> (<?= date('d/m/Y', strtotime($t['tanggal'])) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-primary">Tampilkan</button>
        </form>
    </div>

    <?php if ($tema_aktif): ?>
    <!-- Ringkasan -->
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px">
        <div style="background:#fff;border-radius:10px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.07);text-align:center">
            <div style="font-size:28px;font-weight:700;color:#3f51b5"><?= $total ?></div>
            <div style="font-size:13px;color:#888;margin-top:4px">Total Terdaftar</div>
        </div>
        <div style="background:#fff;border-radius:10px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.07);text-align:center">
            <div style="font-size:28px;font-weight:700;color:#2e7d32"><?= $hadir ?></div>
            <div style="font-size:13px;color:#888;margin-top:4px">Hadir</div>
        </div>
        <div style="background:#fff;border-radius:10px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.07);text-align:center">
            <div style="font-size:28px;font-weight:700;color:#e53935"><?= $belum ?></div>
            <div style="font-size:13px;color:#888;margin-top:4px">Tidak Hadir</div>
        </div>
    </div>

    <!-- Tabel Absensi -->
    <div class="data-table" style="overflow-x:auto">
        <div style="padding:16px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #f0f0f0">
            <strong style="font-size:15px"><?= htmlspecialchars($tema_aktif['judul']) ?></strong>
            <span style="font-size:13px;color:#888">
                <?= date('d/m/Y', strtotime($tema_aktif['tanggal'])) ?>
                <?php if (!empty($tema_aktif['tanggal_selesai'])): ?> - <?= date('d/m/Y', strtotime($tema_aktif['tanggal_selesai'])) ?><?php endif; ?>
                &bull; <?= htmlspecialchars($tema_aktif['lokasi']) ?>
            </span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Lengkap</th>
                    <th>NIK/NIS</th>
                    <th>Instansi</th>
                    <th>No. HP</th>
                    <th>Kecamatan</th>
                    <th>NPWP</th>
                    <th>No. Rekening</th>
                    <th>Nama Bank</th>
                    <th>Foto KTP</th>
                    <th>Foto NPWP</th>
                    <th>Buku Rekening</th>
                    <th>Status</th>
                    <th>Waktu Hadir</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($peserta as $i => $p): ?>
                <tr>
                    <td style="text-align:center;color:#888"><?= $i+1 ?></td>
                    <td>
                        <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($p['nama_lengkap']) ?></div>
                        <div style="font-size:11px;color:#aaa"><?= htmlspecialchars($p['user_email'] ?? '') ?></div>
                    </td>
                    <td style="font-size:13px"><?= htmlspecialchars($p['nik'] ?? '-') ?></td>
                    <td style="font-size:13px"><?= htmlspecialchars($p['instansi'] ?? '-') ?></td>
                    <td style="font-size:13px"><?= htmlspecialchars($p['no_hp'] ?? '-') ?></td>
                    <td style="font-size:13px"><?= htmlspecialchars($p['asal_kecamatan'] ?? '-') ?></td>
                    <td style="font-size:13px"><?= htmlspecialchars($p['npwp'] ?? '-') ?></td>
                    <td style="font-size:13px"><?= htmlspecialchars($p['no_rekening'] ?? '-') ?></td>
                    <td style="font-size:13px"><?= htmlspecialchars($p['nama_bank'] ?? '-') ?></td>
                    <td style="text-align:center">
                        <?php if (!empty($p['doc_ktp'])): ?>
                        <a href="../<?= htmlspecialchars($p['doc_ktp']) ?>" target="_blank" class="btn-primary" style="font-size:11px;padding:4px 10px;background:#1565c0">Lihat</a>
                        <?php else: ?><span style="color:#ccc;font-size:12px">-</span><?php endif; ?>
                    </td>
                    <td style="text-align:center">
                        <?php if (!empty($p['doc_npwp'])): ?>
                        <a href="../<?= htmlspecialchars($p['doc_npwp']) ?>" target="_blank" class="btn-primary" style="font-size:11px;padding:4px 10px;background:#1565c0">Lihat</a>
                        <?php else: ?><span style="color:#ccc;font-size:12px">-</span><?php endif; ?>
                    </td>
                    <td style="text-align:center">
                        <?php if (!empty($p['doc_rekening'])): ?>
                        <a href="../<?= htmlspecialchars($p['doc_rekening']) ?>" target="_blank" class="btn-primary" style="font-size:11px;padding:4px 10px;background:#1565c0">Lihat</a>
                        <?php else: ?><span style="color:#ccc;font-size:12px">-</span><?php endif; ?>
                    </td>
                    <td>
                        <?php if ($p['hadir']): ?>
                            <span class="status-badge status-published">Hadir</span>
                        <?php else: ?>
                            <span class="status-badge status-pending">Belum Hadir</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:13px;color:#666;white-space:nowrap">
                        <?= $p['hadir_at'] ? date('d/m/Y H:i', strtotime($p['hadir_at'])) : '-' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($peserta)): ?>
                <tr><td colspan="14" style="text-align:center;color:#999;padding:32px">Belum ada peserta terdaftar.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div style="text-align:center;padding:60px;color:#aaa">
        <p style="font-size:15px">Pilih tema pelatihan untuk melihat laporan absensi</p>
    </div>
    <?php endif; ?>
</div>
</div>
<?php require_once '../includes/footer.php'; ?>
