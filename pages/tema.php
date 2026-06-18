<?php
require_once '../includes/header.php';
require_once '../config/qr.php';

$msg = ''; $msg_type = '';

// Handle pendaftaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {

    if ($_POST['aksi'] === 'daftar') {
        if (!$is_logged_in) { header('Location: ../auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'])); exit; }

        $tema_id  = (int)$_POST['tema_id'];
        $nama     = ucwords(strtolower(trim($_POST['nama_lengkap'])));
        $email    = trim($_POST['email']);
        $instansi = ucwords(strtolower(trim($_POST['instansi'])));
        $no_hp    = trim($_POST['no_hp']);
        $nik      = trim($_POST['nik'] ?? '');
        $npwp     = trim($_POST['npwp'] ?? '');
        $no_rek   = trim($_POST['no_rekening'] ?? '');
        $kecamatan = ucwords(strtolower(trim($_POST['asal_kecamatan'] ?? '')));
        $user_id  = $_SESSION['user_id'];

        // Ambil form settings untuk tema ini
        $fs_res = $conn->query("SELECT * FROM tema_form_settings WHERE tema_id=$tema_id");
        $fs = ($fs_res && $fs_res->num_rows > 0) ? $fs_res->fetch_assoc() : [
            'req_nik'=>1,'req_npwp'=>1,'req_rekening'=>1,'req_nama_bank'=>1,
            'req_kecamatan'=>1,'req_doc_ktp'=>0,'req_doc_npwp'=>0,'req_doc_rekening'=>0
        ];

        $nama_bank = ucwords(strtolower(trim($_POST['nama_bank'] ?? '')));

        // Handle doc uploads - save to uploads/dokumen/
        $doc_dir = '../uploads/dokumen/';
        if (!is_dir($doc_dir)) mkdir($doc_dir, 0755, true);

        $doc_ktp = null;
        if (isset($_FILES['doc_ktp']) && $_FILES['doc_ktp']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['doc_ktp']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','jfif','pdf'])) {
                $fname = 'ktp_' . time() . '_' . rand(100,999) . '.' . $ext;
                move_uploaded_file($_FILES['doc_ktp']['tmp_name'], $doc_dir . $fname);
                $doc_ktp = 'uploads/dokumen/' . $fname;
            }
        }
        $doc_npwp = null;
        if (isset($_FILES['doc_npwp']) && $_FILES['doc_npwp']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['doc_npwp']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','jfif','pdf'])) {
                $fname = 'npwp_' . time() . '_' . rand(100,999) . '.' . $ext;
                move_uploaded_file($_FILES['doc_npwp']['tmp_name'], $doc_dir . $fname);
                $doc_npwp = 'uploads/dokumen/' . $fname;
            }
        }
        $doc_rekening = null;
        if (isset($_FILES['doc_rekening']) && $_FILES['doc_rekening']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['doc_rekening']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','jfif','pdf'])) {
                $fname = 'rek_' . time() . '_' . rand(100,999) . '.' . $ext;
                move_uploaded_file($_FILES['doc_rekening']['tmp_name'], $doc_dir . $fname);
                $doc_rekening = 'uploads/dokumen/' . $fname;
            }
        }

        // Validasi field wajib berdasarkan form settings
        $errors = [];
        if (!$nama || !$email || !$instansi || !$no_hp) $errors[] = 'Nama, email, instansi, dan no HP wajib diisi.';
        if ($fs['req_nik'] && !$nik) $errors[] = 'NIK/NIS wajib diisi.';
        if ($fs['req_npwp'] && !$npwp) $errors[] = 'NPWP wajib diisi.';
        if ($fs['req_rekening'] && !$no_rek) $errors[] = 'Nomor rekening wajib diisi.';
        if ($fs['req_nama_bank'] && !$nama_bank) $errors[] = 'Nama bank wajib diisi.';
        if ($fs['req_kecamatan'] && !$kecamatan) $errors[] = 'Asal kecamatan wajib diisi.';
        if ($fs['req_doc_ktp'] && !$doc_ktp) $errors[] = 'Foto KTP wajib diunggah.';
        if ($fs['req_doc_npwp'] && !$doc_npwp) $errors[] = 'Foto NPWP wajib diunggah.';
        if ($fs['req_doc_rekening'] && !$doc_rekening) $errors[] = 'Foto buku rekening wajib diunggah.';

        if (!empty($errors)) {
            $msg = implode(' ', $errors); $msg_type = 'error';
        } else {
        $tema = $conn->query("SELECT * FROM tema_pelatihan WHERE id=$tema_id")->fetch_assoc();
        $jml  = $conn->query("SELECT COUNT(*) as c FROM pendaftaran WHERE tema_id=$tema_id AND cancelled_at IS NULL AND status!='rejected'")->fetch_assoc()['c'];

        if (!$tema || $tema['status'] === 'closed') {
            $msg = 'Pendaftaran sudah ditutup.'; $msg_type = 'error';
        } elseif ($jml >= $tema['kuota']) {
            $msg = 'Kuota sudah penuh.'; $msg_type = 'error';
        } else {
            $check = $conn->prepare("SELECT id, cancelled_at FROM pendaftaran WHERE tema_id=? AND user_id=?");
            $check->bind_param("ii", $tema_id, $user_id); $check->execute();
            $existing = $check->get_result()->fetch_assoc();
            if ($existing && !$existing['cancelled_at']) {
                $msg = 'Anda sudah terdaftar pada tema pelatihan ini.'; $msg_type = 'error';
            } elseif ($existing && $existing['cancelled_at']) {
                $msg = 'Anda pernah membatalkan pendaftaran ini dan tidak dapat mendaftar ulang.'; $msg_type = 'error';
            } else {
                $stmt = $conn->prepare("INSERT INTO pendaftaran (tema_id,user_id,nama_lengkap,email,instansi,no_hp,nik,asal_kecamatan,npwp,no_rekening,nama_bank,doc_ktp,doc_npwp,doc_rekening) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->bind_param("iissssssssssss", $tema_id, $user_id, $nama, $email, $instansi, $no_hp, $nik, $kecamatan, $npwp, $no_rek, $nama_bank, $doc_ktp, $doc_npwp, $doc_rekening);
                if ($stmt->execute()) {
                    $daftar_id = $conn->insert_id;
                    $qr_data = json_encode(['daftar_id'=>$daftar_id,'tema_id'=>$tema_id,'user_id'=>$user_id,'nama'=>$nama]);
                    $qr_path = generate_qr($qr_data, 'daftar_'.$daftar_id);
                    if ($qr_path) $conn->query("UPDATE pendaftaran SET qr_code='$qr_path' WHERE id=$daftar_id");
                    $msg = 'Pendaftaran berhasil! QR Code Anda sudah tersedia.'; $msg_type = 'success';
                } else {
                    $msg = 'Gagal mendaftar, silakan coba lagi.'; $msg_type = 'error';
                }
            }
        }
        } // end else validasi
    }

    if ($_POST['aksi'] === 'batal' && $is_logged_in) {
        $daftar_id = (int)$_POST['daftar_id'];
        $alasan    = trim($_POST['alasan_batal'] ?? '');
        $user_id   = $_SESSION['user_id'];

        // Ambil data pendaftaran + tanggal tema
        $pd = $conn->query("SELECT pd.*, tp.tanggal as tema_tanggal, tp.judul as tema_judul
            FROM pendaftaran pd JOIN tema_pelatihan tp ON pd.tema_id=tp.id
            WHERE pd.id=$daftar_id AND pd.user_id=$user_id AND pd.cancelled_at IS NULL")->fetch_assoc();

        if (!$pd) {
            $msg = 'Data pendaftaran tidak ditemukan.'; $msg_type = 'error';
        } elseif (empty($alasan)) {
            $msg = 'Alasan pembatalan wajib diisi.'; $msg_type = 'error';
        } else {
            $hari_h    = strtotime($pd['tema_tanggal']);
            $batas     = $hari_h - (3 * 86400); // H-3
            $sekarang  = time();
            if ($sekarang > $batas) {
                $msg = 'Pembatalan tidak dapat dilakukan. Batas pembatalan adalah H-3 sebelum pelaksanaan (' . date('d M Y', $batas) . ').'; $msg_type = 'error';
            } else {
                $now = date('Y-m-d H:i:s');
                $alasan_esc = mysqli_real_escape_string($conn, $alasan);
                $conn->query("UPDATE pendaftaran SET status='rejected', alasan_batal='$alasan_esc', cancelled_at='$now' WHERE id=$daftar_id");
                // Kirim notifikasi ke admin
                $notif_judul = "Pembatalan Pendaftaran: " . $pd['nama_lengkap'];
                $notif_pesan = "{$pd['nama_lengkap']} membatalkan pendaftaran tema \"{$pd['tema_judul']}\". Alasan: $alasan";
                $notif_judul_esc = mysqli_real_escape_string($conn, $notif_judul);
                $notif_pesan_esc = mysqli_real_escape_string($conn, $notif_pesan);
                $conn->query("INSERT INTO notifikasi_admin (judul, pesan, tipe) VALUES ('$notif_judul_esc','$notif_pesan_esc','warning')");
                $msg = 'Pendaftaran berhasil dibatalkan.'; $msg_type = 'success';
            }
        }
    }
}

// Pastikan tabel notifikasi ada
$conn->query("CREATE TABLE IF NOT EXISTS notifikasi_admin (
    id INT AUTO_INCREMENT PRIMARY KEY, judul VARCHAR(200) NOT NULL, pesan TEXT,
    tipe ENUM('info','warning','danger') DEFAULT 'info', is_read TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$conn->query("ALTER TABLE pendaftaran ADD COLUMN IF NOT EXISTS qr_code VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE pendaftaran ADD COLUMN IF NOT EXISTS alasan_batal TEXT DEFAULT NULL");
$conn->query("ALTER TABLE pendaftaran ADD COLUMN IF NOT EXISTS cancelled_at DATETIME DEFAULT NULL");
$conn->query("ALTER TABLE pendaftaran ADD COLUMN IF NOT EXISTS hadir TINYINT DEFAULT 0");
$conn->query("ALTER TABLE pendaftaran ADD COLUMN IF NOT EXISTS hadir_at DATETIME DEFAULT NULL");

// Pastikan kolom tanggal_selesai ada
$conn->query("ALTER TABLE tema_pelatihan ADD COLUMN IF NOT EXISTS tanggal_selesai DATE DEFAULT NULL AFTER tanggal");
$conn->query("ALTER TABLE pendaftaran ADD COLUMN IF NOT EXISTS npwp VARCHAR(25) DEFAULT NULL");
$conn->query("ALTER TABLE pendaftaran ADD COLUMN IF NOT EXISTS no_rekening VARCHAR(30) DEFAULT NULL");
$conn->query("ALTER TABLE pendaftaran ADD COLUMN IF NOT EXISTS asal_kecamatan VARCHAR(100) DEFAULT NULL");

// Feature 1a: Kolom dokumen pendaftaran
$conn->query("ALTER TABLE pendaftaran ADD COLUMN IF NOT EXISTS nama_bank VARCHAR(100) DEFAULT NULL");
$conn->query("ALTER TABLE pendaftaran ADD COLUMN IF NOT EXISTS doc_ktp VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE pendaftaran ADD COLUMN IF NOT EXISTS doc_npwp VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE pendaftaran ADD COLUMN IF NOT EXISTS doc_rekening VARCHAR(255) DEFAULT NULL");

// Feature 2: Tabel pengaturan form per tema
$conn->query("CREATE TABLE IF NOT EXISTS tema_form_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tema_id INT NOT NULL UNIQUE,
    req_nik TINYINT DEFAULT 1,
    req_npwp TINYINT DEFAULT 1,
    req_rekening TINYINT DEFAULT 1,
    req_nama_bank TINYINT DEFAULT 1,
    req_kecamatan TINYINT DEFAULT 1,
    req_doc_ktp TINYINT DEFAULT 0,
    req_doc_npwp TINYINT DEFAULT 0,
    req_doc_rekening TINYINT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Ambil semua tema beserta modul terkait
$temas = $conn->query("SELECT t.*, (SELECT COUNT(*) FROM pendaftaran p WHERE p.tema_id=t.id AND p.status!='rejected' AND p.cancelled_at IS NULL) as terdaftar FROM tema_pelatihan t ORDER BY t.tanggal ASC")->fetch_all(MYSQLI_ASSOC);

// Cek kolom tanggal_selesai
$has_tgl_selesai = $conn->query("SHOW COLUMNS FROM tema_pelatihan LIKE 'tanggal_selesai'")->num_rows > 0;

// Modul per tema tidak perlu lagi ditampilkan di kartu tema

// Data pendaftaran user
$daftar_user = [];
if ($is_logged_in) {
    $uid = $_SESSION['user_id'];
    $res = $conn->query("SELECT pd.*, tp.tanggal as tema_tanggal FROM pendaftaran pd JOIN tema_pelatihan tp ON pd.tema_id=tp.id WHERE pd.user_id=$uid AND pd.cancelled_at IS NULL");
    while ($r = $res->fetch_assoc()) {
        // Auto-generate QR jika belum ada
        if (empty($r['qr_code'])) {
            $qr_data = json_encode(['daftar_id'=>$r['id'],'tema_id'=>$r['tema_id'],'user_id'=>$uid,'nama'=>$r['nama_lengkap']]);
            $qr_path = generate_qr($qr_data, 'daftar_'.$r['id']);
            if ($qr_path) {
                $conn->query("UPDATE pendaftaran SET qr_code='".mysqli_real_escape_string($conn,$qr_path)."' WHERE id={$r['id']}");
                $r['qr_code'] = $qr_path;
            }
        }
        $daftar_user[$r['tema_id']] = $r;
    }
}
?>
<div class="main-content">

<!-- TEMA HERO -->
<section class="katalog-hero">
    <div class="katalog-hero-inner">
        <h1 class="animate-on-scroll">Tema Pelatihan</h1>
        <p class="animate-on-scroll">Pilih dan daftarkan diri Anda pada tema pelatihan yang tersedia dari Diskominfo Kabupaten Bogor</p>
    </div>
</section>

<div class="tema-page" style="background:#f8f9ff;padding:40px 0">
    <div style="max-width:1100px;margin:0 auto;padding:0 24px">

    <?php if ($msg): ?>
    <div class="alert alert-<?= $msg_type==='success'?'success':'error' ?>" style="margin-bottom:20px">
        <?= htmlspecialchars($msg) ?>
    </div>
    <?php endif; ?>

    <div class="tema-grid">
        <?php foreach ($temas as $t):
            $sisa   = $t['kuota'] - $t['terdaftar'];
            $persen = $t['kuota'] > 0 ? min(100, round(($t['terdaftar']/$t['kuota'])*100)) : 0;
            $pd     = $daftar_user[$t['id']] ?? null;
            $sudah  = !empty($pd);
            $batas_batal = $sudah ? strtotime($pd['tema_tanggal']) - (3*86400) : 0;
            $bisa_batal  = $sudah && time() <= $batas_batal;
        ?>
        <div class="tema-card animate-on-scroll">
            <div class="tema-thumb">
                <?php if ($t['thumbnail']): ?>
                    <img src="../<?= htmlspecialchars($t['thumbnail']) ?>" alt="">
                <?php else: ?>

                    <div class="tema-thumb-placeholder"></div>
                <?php endif; ?>
                <span class="tema-status-badge status-<?= $t['status'] ?>">
                    <?= $t['status']==='open' ? 'Pendaftaran Dibuka' : 'Ditutup' ?>
                </span>
            </div>
            <div class="tema-body">
                <h3><?= htmlspecialchars($t['judul']) ?></h3>
                <p class="tema-desc"><?= htmlspecialchars($t['deskripsi']) ?></p>
                <div class="tema-meta">
                    <span><svg class="icon" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <?php
                        $tgl_mulai = date('d/m/Y', strtotime($t['tanggal']));
                        $tgl_selesai = (!empty($t['tanggal_selesai']) && $t['tanggal_selesai'] !== $t['tanggal'])
                            ? ' - ' . date('d/m/Y', strtotime($t['tanggal_selesai'])) : '';
                        echo $tgl_mulai . $tgl_selesai;
                    ?></span>
                    <span><svg class="icon" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg> <?= htmlspecialchars($t['lokasi']) ?></span>
                </div>
                <div class="kuota-bar">
                    <div class="kuota-info">
                        <span>Peserta: <?= $t['terdaftar'] ?>/<?= $t['kuota'] ?></span>
                        <span><?= $sisa ?> tempat tersisa</span>
                    </div>
                    <div class="kuota-progress"><div class="kuota-fill" style="width:<?= $persen ?>%"></div></div>
                </div>

                <?php if (!empty($modul_per_tema[$t['id']])): ?>
                <div class="tema-modul-list">
                    <div style="font-size:12px;font-weight:600;color:#999;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px">Materi Pelatihan</div>
                    <?php foreach ($modul_per_tema[$t['id']] as $mod): ?>
                    <a href="detail.php?id=<?= $mod['id'] ?>" class="tema-modul-item">
                        <div class="tema-modul-thumb">
                            <?php if ($mod['thumbnail']): ?>
                                <img src="../<?= htmlspecialchars($mod['thumbnail']) ?>" alt="">
                            <?php else: ?>
                                <div style="width:100%;height:100%;background:#c5cae9;display:flex;align-items:center;justify-content:center;font-size:18px"><svg class="icon icon-lg" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
                            <?php endif; ?>
                        </div>
                        <div class="tema-modul-info">
                            <div class="tema-modul-judul"><?= htmlspecialchars($mod['judul']) ?></div>
                            <div class="tema-modul-meta">
                                <?php if ($mod['instruktur']): ?><span><svg class="icon icon-sm" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg> <?= htmlspecialchars($mod['instruktur']) ?></span><?php endif; ?>
                                <?php if ($mod['file_ppt']): ?><span style="color:#3f51b5"><svg class="icon icon-sm" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> Materi tersedia</span><?php endif; ?>
                            </div>
                        </div>
                        <span style="color:#3f51b5;font-size:18px">&#8250;</span>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <div class="tema-footer" style="flex-direction:column;align-items:stretch;gap:8px">
                    <?php if ($sudah): ?>
                        <!-- Baris status + aksi -->
                        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
                            <span class="badge-terdaftar"><svg class="icon icon-sm" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg> Sudah Terdaftar</span>
                            <div style="display:flex;gap:6px">
                                <button class="btn-qr" onclick="toggleQR(<?= $t['id'] ?>)"><svg class="icon icon-sm" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="3" height="3"/></svg> QR Code</button>
                                <?php if ($bisa_batal): ?>
                                <button class="btn-batal-daftar" onclick="openBatal(<?= $pd['id'] ?>, '<?= htmlspecialchars(addslashes($t['judul'])) ?>', '<?= date('d M Y', $batas_batal) ?>')"><svg class="icon icon-sm" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Batalkan</button>
                                <?php else: ?>
                                <span style="font-size:11px;color:#bbb;align-self:center">Batas batal: <?= date('d M Y', $batas_batal) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <!-- QR Code Panel -->
                        <div id="qr-panel-<?= $t['id'] ?>" style="display:none;text-align:center;background:#f8f9ff;border-radius:8px;padding:14px;border:1px solid #e0e0e0">
                            <?php if ($pd['qr_code']): ?>
                            <img src="../<?= htmlspecialchars($pd['qr_code']) ?>" style="width:150px;height:150px;border-radius:6px;display:block;margin:0 auto">
                            <p style="font-size:11px;color:#888;margin:6px 0 8px">Tunjukkan QR ini saat hadir</p>
                            <a href="../<?= htmlspecialchars($pd['qr_code']) ?>" download class="btn-primary" style="font-size:12px;padding:6px 14px;display:inline-flex"><svg class="icon icon-sm" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Unduh QR</a>
                            <?php else: ?>
                            <p style="color:#aaa;font-size:13px;padding:10px 0">QR Code belum tersedia</p>
                            <?php endif; ?>
                        </div>
                        <button class="btn-share-link" onclick="shareLink(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['judul'])) ?>')"><svg class="icon icon-sm" viewBox="0 0 24 24"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg> Bagikan Link</button>
                    <?php elseif ($t['status']==='open' && $sisa > 0): ?>
                        <button class="btn-daftar" style="width:100%" onclick="openModalDaftar(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['judul'])) ?>')">
                            Daftar Sekarang
                        </button>
                        <button class="btn-share-link" onclick="shareLink(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['judul'])) ?>')"><svg class="icon icon-sm" viewBox="0 0 24 24"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg> Bagikan Link</button>
                    <?php else: ?>
                        <span class="badge-penuh">Kuota Penuh</span>
                        <button class="btn-share-link" onclick="shareLink(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['judul'])) ?>')"><svg class="icon icon-sm" viewBox="0 0 24 24"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg> Bagikan Link</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($temas)): ?>
            <p style="color:#999;text-align:center;padding:40px 0;grid-column:1/-1">Belum ada tema pelatihan tersedia.</p>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL PENDAFTARAN (2 Step) -->
<div class="modal-overlay" id="modalDaftar">
    <div class="modal" style="max-width:600px">

        <!-- STEP 1: Info Tema -->
        <div id="modal-step-1">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px">
                <h3 style="font-size:18px;font-weight:700;color:#1a237e" id="modal-tema-judul-s1"></h3>
                <button type="button" onclick="closeModalDaftar()" style="background:none;border:none;cursor:pointer;color:#aaa;font-size:20px;line-height:1;padding:0">&times;</button>
            </div>

            <!-- Gambar tema -->
            <div id="modal-tema-img" style="width:100%;height:180px;border-radius:10px;overflow:hidden;background:#e8eaf6;margin-bottom:20px;display:none">
                <img id="modal-tema-img-src" src="" alt="" style="width:100%;height:100%;object-fit:cover">
            </div>

            <!-- Info acara -->
            <div style="background:#f8f9ff;border-radius:10px;padding:16px;margin-bottom:16px">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div>
                        <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#999;font-weight:700;margin-bottom:4px">Tanggal</div>
                        <div style="font-size:14px;font-weight:600;color:#1a237e" id="modal-tema-tgl"></div>
                    </div>
                    <div>
                        <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#999;font-weight:700;margin-bottom:4px">Lokasi</div>
                        <div style="font-size:14px;font-weight:600;color:#1a237e" id="modal-tema-lokasi"></div>
                    </div>
                    <div>
                        <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#999;font-weight:700;margin-bottom:4px">Kuota</div>
                        <div style="font-size:14px;font-weight:600;color:#1a237e" id="modal-tema-kuota"></div>
                    </div>
                    <div>
                        <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#999;font-weight:700;margin-bottom:4px">Status</div>
                        <div style="font-size:14px;font-weight:600;color:#2e7d32">Pendaftaran Dibuka</div>
                    </div>
                </div>
            </div>

            <!-- Deskripsi -->
            <div id="modal-tema-desc" style="font-size:14px;color:#555;line-height:1.7;margin-bottom:20px"></div>

            <?php if (!$is_logged_in): ?>
            <div class="alert alert-error" style="margin-bottom:12px">
                Anda harus <a href="../auth/login.php"><strong>masuk</strong></a> terlebih dahulu untuk mendaftar.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModalDaftar()">Tutup</button>
                <a href="../auth/login.php" class="btn-primary">Masuk</a>
            </div>
            <?php else: ?>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModalDaftar()">Tutup</button>
                <button type="button" class="btn-primary" onclick="goToStep2()">
                    Lanjut Isi Data Diri &rarr;
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- STEP 2: Form Pendaftaran -->
        <div id="modal-step-2" style="display:none">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px">
                <button type="button" onclick="goToStep1()" style="background:none;border:none;cursor:pointer;color:#3f51b5;font-size:20px;line-height:1;padding:0">&larr;</button>
                <div>
                    <h3 style="font-size:16px;font-weight:700">Isi Data Pendaftaran</h3>
                    <p id="modal-tema-judul-s2" style="color:#3f51b5;font-size:13px;font-weight:500;margin-top:2px"></p>
                </div>
            </div>
            <form method="POST" enctype="multipart/form-data" id="form-daftar">
                <input type="hidden" name="aksi" value="daftar">
                <input type="hidden" name="tema_id" id="input-tema-id">
                <div class="form-group"><label>Nama Lengkap <span style="color:#e53935">*</span></label>
                    <input type="text" name="nama_lengkap" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px" value="<?= htmlspecialchars($_SESSION['nama']??'') ?>" required>
                </div>
                <div class="form-group"><label>Email <span style="color:#e53935">*</span></label>
                    <input type="email" name="email" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px" value="<?= htmlspecialchars($_SESSION['email']??'') ?>" required>
                </div>
                <div class="form-group"><label>Instansi / Unit Kerja <span style="color:#e53935">*</span></label>
                    <input type="text" name="instansi" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px" placeholder="Dinas Komunikasi dan Informatika" required>
                </div>
                <div class="form-group"><label>No. HP / WhatsApp <span style="color:#e53935">*</span></label>
                    <input type="text" name="no_hp" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px" placeholder="08xxxxxxxxxx" required>
                </div>
                <div class="form-group" id="fg-nik"><label>NIK / NIS <span class="req-mark" style="color:#e53935">*</span></label>
                    <input type="text" name="nik" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px" placeholder="Nomor Induk Kependudukan / Siswa" maxlength="20">
                </div>
                <div class="form-group" id="fg-npwp"><label>NPWP <span class="req-mark" style="color:#e53935">*</span></label>
                    <input type="text" name="npwp" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px" placeholder="Nomor Pokok Wajib Pajak" maxlength="25">
                </div>
                <div class="form-group" id="fg-rekening"><label>No. Rekening <span class="req-mark" style="color:#e53935">*</span></label>
                    <input type="text" name="no_rekening" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px" placeholder="Nomor rekening bank" maxlength="30">
                </div>
                <div class="form-group" id="fg-nama-bank"><label>Nama Bank <span class="req-mark" style="color:#e53935">*</span></label>
                    <input type="text" name="nama_bank" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px" placeholder="Contoh: BRI, BNI, Mandiri">
                </div>
                <div class="form-group" id="fg-kecamatan"><label>Asal Kecamatan <span class="req-mark" style="color:#e53935">*</span></label>
                    <input type="text" name="asal_kecamatan" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px" placeholder="Contoh: Bogor Utara">
                </div>
                <div class="form-group" id="fg-doc-ktp"><label>Upload Foto KTP <span class="req-mark" style="color:#e53935"></span></label>
                    <input type="file" name="doc_ktp" accept="image/*,.jfif,application/pdf" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:8px;font-size:13px">
                    <small style="color:#888">Format: JPG, PNG, JFIF, PDF</small>
                </div>
                <div class="form-group" id="fg-doc-npwp"><label>Upload Foto NPWP <span class="req-mark" style="color:#e53935"></span></label>
                    <input type="file" name="doc_npwp" accept="image/*,.jfif,application/pdf" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:8px;font-size:13px">
                    <small style="color:#888">Format: JPG, PNG, JFIF, PDF</small>
                </div>
                <div class="form-group" id="fg-doc-rekening"><label>Upload Foto Buku Rekening <span class="req-mark" style="color:#e53935"></span></label>
                    <input type="file" name="doc_rekening" accept="image/*,.jfif,application/pdf" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:8px;font-size:13px">
                    <small style="color:#888">Format: JPG, PNG, JFIF, PDF</small>
                </div>
                <p style="font-size:12px;color:#e53935;margin-bottom:8px">* Field wajib diisi sesuai ketentuan pelatihan</p>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="goToStep1()">Kembali</button>
                    <button type="submit" class="btn-primary">Kirim Pendaftaran</button>
                </div>
            </form>
        </div>

    </div>
</div>

<!-- MODAL PEMBATALAN -->
<div class="modal-overlay" id="modalBatal">
    <div class="modal">
        <h3>Batalkan Pendaftaran</h3>
        <p id="batal-tema-judul" style="color:#e53935;font-weight:600;margin-bottom:4px;font-size:14px"></p>
        <p id="batal-info" style="font-size:13px;color:#888;margin-bottom:16px"></p>
        <form method="POST">
            <input type="hidden" name="aksi" value="batal">
            <input type="hidden" name="daftar_id" id="input-daftar-id">
            <div class="form-group">
                <label>Alasan Pembatalan <span style="color:#e53935">*</span></label>
                <textarea name="alasan_batal" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px;resize:vertical;min-height:90px" placeholder="Jelaskan alasan Anda membatalkan pendaftaran..." required></textarea>
            </div>
            <div class="alert alert-error" style="font-size:13px;margin-bottom:12px">
                <svg class="icon icon-sm" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg> Pembatalan tidak dapat dibatalkan kembali. Pastikan keputusan Anda sudah final.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="document.getElementById('modalBatal').classList.remove('open')">Kembali</button>
                <button type="submit" class="btn-primary" style="background:#e53935" onclick="return confirm('Yakin ingin membatalkan pendaftaran ini?')">Konfirmasi Batalkan</button>
            </div>
        </form>
    </div>
</div>

<script>
// Peta tema data (id => {judul, formSettings})
window._temaMap = <?php
$temaMap = [];
foreach ($temas as $t) {
    $fs_r = $conn->query("SELECT * FROM tema_form_settings WHERE tema_id={$t['id']}");
    $fs = ($fs_r && $fs_r->num_rows > 0) ? $fs_r->fetch_assoc() : [
        'req_nik'=>1,'req_npwp'=>1,'req_rekening'=>1,'req_nama_bank'=>1,
        'req_kecamatan'=>1,'req_doc_ktp'=>0,'req_doc_npwp'=>0,'req_doc_rekening'=>0
    ];
    // Format tanggal
    $tgl = date('d/m/Y', strtotime($t['tanggal']));
    if (!empty($t['tanggal_selesai'])) $tgl .= ' - ' . date('d/m/Y', strtotime($t['tanggal_selesai']));
    $temaMap[$t['id']] = [
        'judul'      => $t['judul'],
        'deskripsi'  => $t['deskripsi'] ?? '',
        'lokasi'     => $t['lokasi'] ?? '-',
        'tgl'        => $tgl,
        'kuota'      => (int)$t['kuota'],
        'terdaftar'  => (int)$t['terdaftar'],
        'thumbnail'  => $t['thumbnail'] ?? '',
        'fs'         => $fs,
    ];
}
echo json_encode($temaMap);
?>;

function applyFormSettings(temaId) {
    var tm = window._temaMap[temaId];
    if (!tm) return;
    var fs = tm.fs;
    function setField(fgId, inputName, required) {
        var fg = document.getElementById(fgId);
        if (!fg) return;
        var inp = fg.querySelector('[name="'+inputName+'"]');
        var mark = fg.querySelector('.req-mark');
        if (required) {
            fg.style.display = '';
            if (inp) inp.setAttribute('required','required');
            if (mark) mark.textContent = '*';
        } else {
            fg.style.display = '';
            if (inp) inp.removeAttribute('required');
            if (mark) mark.textContent = '';
        }
    }
    setField('fg-nik', 'nik', fs.req_nik == 1);
    setField('fg-npwp', 'npwp', fs.req_npwp == 1);
    setField('fg-rekening', 'no_rekening', fs.req_rekening == 1);
    setField('fg-nama-bank', 'nama_bank', fs.req_nama_bank == 1);
    setField('fg-kecamatan', 'asal_kecamatan', fs.req_kecamatan == 1);
    setField('fg-doc-ktp', 'doc_ktp', fs.req_doc_ktp == 1);
    setField('fg-doc-npwp', 'doc_npwp', fs.req_doc_npwp == 1);
    setField('fg-doc-rekening', 'doc_rekening', fs.req_doc_rekening == 1);
}

function openModalDaftar(id, judul) {
    document.getElementById('input-tema-id').value = id;
    document.getElementById('modal-tema-judul-s1').textContent = judul;
    document.getElementById('modal-tema-judul-s2').textContent = judul;

    // Isi info tema dari _temaMap
    var tm = window._temaMap && window._temaMap[id];
    if (tm) {
        // Tanggal
        document.getElementById('modal-tema-tgl').textContent = tm.tgl || '-';
        // Lokasi
        document.getElementById('modal-tema-lokasi').textContent = tm.lokasi || '-';
        // Kuota
        document.getElementById('modal-tema-kuota').textContent = (tm.terdaftar || 0) + ' / ' + (tm.kuota || 0) + ' peserta';
        // Deskripsi
        document.getElementById('modal-tema-desc').textContent = tm.deskripsi || '';
        // Gambar
        var imgWrap = document.getElementById('modal-tema-img');
        var imgEl   = document.getElementById('modal-tema-img-src');
        if (tm.thumbnail) {
            imgEl.src = '../' + tm.thumbnail;
            imgWrap.style.display = 'block';
        } else {
            imgWrap.style.display = 'none';
        }
        // Apply form settings
        applyFormSettings(id);
    }

    // Reset ke step 1
    document.getElementById('modal-step-1').style.display = 'block';
    document.getElementById('modal-step-2').style.display = 'none';
    document.getElementById('modalDaftar').classList.add('open');
}
function closeModalDaftar() {
    document.getElementById('modalDaftar').classList.remove('open');
}
function goToStep2() {
    document.getElementById('modal-step-1').style.display = 'none';
    document.getElementById('modal-step-2').style.display = 'block';
}
function goToStep1() {
    document.getElementById('modal-step-1').style.display = 'block';
    document.getElementById('modal-step-2').style.display = 'none';
}
function openBatal(daftarId, judul, batasTgl) {
    document.getElementById('input-daftar-id').value = daftarId;
    document.getElementById('batal-tema-judul').textContent = judul;
    document.getElementById('batal-info').textContent = 'Batas pembatalan: ' + batasTgl;
    document.getElementById('modalBatal').classList.add('open');
}
function toggleQR(temaId) {
    const panel = document.getElementById('qr-panel-' + temaId);
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
}

// Feature 3: Share Link
function shareLink(temaId, judul) {
    const url = window.location.origin + window.location.pathname + '?buka=' + temaId;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
            alert('Link berhasil disalin!\n' + url);
        }).catch(() => {
            prompt('Salin link ini:', url);
        });
    } else {
        prompt('Salin link ini:', url);
    }
}

document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', function(e) { if (e.target === this) this.classList.remove('open'); });
});

// Feature 3: Auto-open modal if ?buka= param exists
(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const bukaId = parseInt(urlParams.get('buka'));
    if (bukaId && window._temaMap && window._temaMap[bukaId]) {
        openModalDaftar(bukaId, window._temaMap[bukaId].judul);
    }
})();

// Scroll animation
const obsT = new IntersectionObserver(e => {
    e.forEach(en => { if(en.isIntersecting){en.target.classList.add('visible'); obsT.unobserve(en.target);} });
},{threshold:0.08});
document.querySelectorAll('.animate-on-scroll').forEach(el=>obsT.observe(el));
</script>

</div><!-- end main-content -->
</div><!-- end tema inner -->
</div><!-- end tema-page -->
<?php require_once '../includes/footer.php'; ?>
