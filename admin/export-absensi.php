<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { exit('Akses ditolak'); }

$tema_id = (int)($_GET['tema'] ?? 0);
if (!$tema_id) exit('Tema tidak valid');

$tema = $conn->query("SELECT * FROM tema_pelatihan WHERE id=$tema_id")->fetch_assoc();
if (!$tema) exit('Tema tidak ditemukan');

$conn->query("ALTER TABLE pendaftaran ADD COLUMN IF NOT EXISTS asal_kecamatan VARCHAR(100) DEFAULT NULL");
$conn->query("ALTER TABLE pendaftaran ADD COLUMN IF NOT EXISTS npwp VARCHAR(25) DEFAULT NULL");
$conn->query("ALTER TABLE pendaftaran ADD COLUMN IF NOT EXISTS no_rekening VARCHAR(30) DEFAULT NULL");
$conn->query("ALTER TABLE pendaftaran ADD COLUMN IF NOT EXISTS nama_bank VARCHAR(100) DEFAULT NULL");
$conn->query("ALTER TABLE pendaftaran ADD COLUMN IF NOT EXISTS doc_ktp VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE pendaftaran ADD COLUMN IF NOT EXISTS doc_npwp VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE pendaftaran ADD COLUMN IF NOT EXISTS doc_rekening VARCHAR(255) DEFAULT NULL");

$peserta = $conn->query("
    SELECT pd.nama_lengkap, pd.nik, pd.email, pd.no_hp, pd.instansi,
           pd.asal_kecamatan, pd.npwp, pd.no_rekening, pd.nama_bank,
           pd.doc_ktp, pd.doc_npwp, pd.doc_rekening,
           pd.hadir, pd.hadir_at, pd.created_at
    FROM pendaftaran pd
    WHERE pd.tema_id=$tema_id AND pd.cancelled_at IS NULL
    ORDER BY TRIM(LOWER(pd.nama_lengkap)) ASC
")->fetch_all(MYSQLI_ASSOC);

$tgl = date('d/m/Y', strtotime($tema['tanggal']));
if (!empty($tema['tanggal_selesai'])) $tgl .= ' - ' . date('d/m/Y', strtotime($tema['tanggal_selesai']));

$base_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$judul_file = 'Absensi_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $tema['judul']) . '_' . date('Ymd');

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $judul_file . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

echo "\xEF\xBB\xBF"; // BOM UTF-8
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:x="urn:schemas-microsoft-com:office:excel"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta charset="UTF-8">
<!--[if gte mso 9]>
<xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>
<x:Name>Absensi</x:Name>
<x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>
</x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml>
<![endif]-->
<style>
    body   { font-family: Calibri, Arial, sans-serif; font-size: 11pt; }
    table  { border-collapse: collapse; }
    .info-lbl { font-weight: bold; padding: 4px 8px 4px 0; font-size: 11pt; background: #fff; }
    .info-val { padding: 4px 8px; font-size: 11pt; background: #fff; }
    th {
        background-color: #1A237E;
        color: #FFFFFF;
        font-weight: bold;
        border: 1px solid #0D1A5C;
        padding: 7px 10px;
        font-size: 11pt;
        white-space: nowrap;
        mso-number-format: "\@";
    }
    td {
        border: 1px solid #BDBDBD;
        padding: 6px 10px;
        font-size: 10pt;
        vertical-align: middle;
        mso-number-format: "\@";
    }
    tr:nth-child(even) td { background-color: #F5F7FF; }
    .hadir   { background-color: #C8E6C9; color: #1B5E20; font-weight: bold; text-align: center; }
    .belum   { background-color: #FFCDD2; color: #B71C1C; text-align: center; }
    .num     { text-align: center; }
    .link-cell { color: #1565C0; text-decoration: underline; font-size: 10pt; }
</style>
</head>
<body>

<!-- Info Header -->
<table style="margin-bottom:12px;border:none">
    <tr><td class="info-lbl" style="border:none">Tema Pelatihan</td><td class="info-val" style="border:none"><?= htmlspecialchars($tema['judul']) ?></td></tr>
    <tr><td class="info-lbl" style="border:none">Tanggal</td><td class="info-val" style="border:none"><?= $tgl ?></td></tr>
    <tr><td class="info-lbl" style="border:none">Lokasi</td><td class="info-val" style="border:none"><?= htmlspecialchars($tema['lokasi'] ?? '-') ?></td></tr>
    <tr><td class="info-lbl" style="border:none">Dicetak</td><td class="info-val" style="border:none"><?= date('d/m/Y H:i') ?></td></tr>
</table>

<!-- Tabel Peserta -->
<table width="100%">
    <thead>
        <tr>
            <th style="width:3%">No</th>
            <th style="width:12%">Nama Lengkap</th>
            <th style="width:10%">NIK / NIS</th>
            <th style="width:12%">Email</th>
            <th style="width:12%">Instansi</th>
            <th style="width:8%">No. HP</th>
            <th style="width:8%">Kecamatan</th>
            <th style="width:9%">NPWP</th>
            <th style="width:9%">No. Rekening</th>
            <th style="width:6%">Nama Bank</th>
            <th style="width:4%">Foto KTP</th>
            <th style="width:4%">Foto NPWP</th>
            <th style="width:4%">Buku Rek</th>
            <th style="width:6%">Status</th>
            <th style="width:8%">Waktu Hadir</th>
            <th style="width:7%">Tgl Daftar</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($peserta)): ?>
        <tr><td colspan="16" style="text-align:center;color:#999">Belum ada peserta terdaftar.</td></tr>
        <?php endif; ?>
        <?php foreach ($peserta as $i => $p):
            $url_ktp  = $p['doc_ktp']      ? $base_url . '/' . ltrim($p['doc_ktp'], '/')      : '-';
            $url_npwp = $p['doc_npwp']     ? $base_url . '/' . ltrim($p['doc_npwp'], '/')     : '-';
            $url_rek  = $p['doc_rekening'] ? $base_url . '/' . ltrim($p['doc_rekening'], '/') : '-';
        ?>
        <tr>
            <td class="num"><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($p['nama_lengkap']) ?></td>
            <td class="num"><?= htmlspecialchars($p['nik'] ?? '-') ?></td>
            <td><?= htmlspecialchars($p['email']) ?></td>
            <td><?= htmlspecialchars($p['instansi'] ?? '-') ?></td>
            <td class="num"><?= htmlspecialchars($p['no_hp'] ?? '-') ?></td>
            <td><?= htmlspecialchars($p['asal_kecamatan'] ?? '-') ?></td>
            <td class="num"><?= htmlspecialchars($p['npwp'] ?? '-') ?></td>
            <td class="num"><?= htmlspecialchars($p['no_rekening'] ?? '-') ?></td>
            <td><?= htmlspecialchars($p['nama_bank'] ?? '-') ?></td>
            <td><?php if ($url_ktp !== '-'): ?><a href="<?= htmlspecialchars($url_ktp) ?>">Lihat KTP</a><?php else: ?>-<?php endif; ?></td>
            <td><?php if ($url_npwp !== '-'): ?><a href="<?= htmlspecialchars($url_npwp) ?>">Lihat NPWP</a><?php else: ?>-<?php endif; ?></td>
            <td><?php if ($url_rek !== '-'): ?><a href="<?= htmlspecialchars($url_rek) ?>">Lihat Rekening</a><?php else: ?>-<?php endif; ?></td>
            <td class="<?= $p['hadir'] ? 'hadir' : 'belum' ?>"><?= $p['hadir'] ? 'Hadir' : 'Tidak Hadir' ?></td>
            <td class="num"><?= $p['hadir_at'] ? date('d/m/Y H:i', strtotime($p['hadir_at'])) : '-' ?></td>
            <td class="num"><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
