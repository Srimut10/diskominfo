<?php
// Jalankan sekali untuk generate QR bagi pendaftaran yang belum punya
require_once 'config/db.php';
require_once 'config/qr.php';

$rows = $conn->query("SELECT * FROM pendaftaran WHERE (qr_code IS NULL OR qr_code='') AND cancelled_at IS NULL")->fetch_all(MYSQLI_ASSOC);
$ok = 0;
foreach ($rows as $r) {
    $qr_data = json_encode(['daftar_id'=>$r['id'],'tema_id'=>$r['tema_id'],'user_id'=>$r['user_id'],'nama'=>$r['nama_lengkap']]);
    $path = generate_qr($qr_data, 'daftar_'.$r['id']);
    if ($path) {
        $conn->query("UPDATE pendaftaran SET qr_code='$path' WHERE id={$r['id']}");
        echo "✓ QR dibuat untuk: {$r['nama_lengkap']}<br>";
        $ok++;
    } else {
        echo "✗ Gagal untuk: {$r['nama_lengkap']}<br>";
    }
}
echo "<br><strong>Selesai: $ok QR berhasil dibuat dari " . count($rows) . " data.</strong><br>";
echo "<a href='index.php'>Kembali</a> | <a href='admin/panel.php?tab=pendaftaran'>Lihat Pendaftaran</a>";
?>
