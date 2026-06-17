<?php
require_once '../includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header('Location: katalog.php'); exit; }

// Handle pendaftaran dari halaman detail
$msg = ''; $msg_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'daftar') {
    if (!$is_logged_in) {
        header('Location: ../auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
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

    $tema = $conn->query("SELECT * FROM tema_pelatihan WHERE id=$tema_id")->fetch_assoc();
    $jml  = $conn->query("SELECT COUNT(*) as c FROM pendaftaran WHERE tema_id=$tema_id AND cancelled_at IS NULL AND status!='rejected'")->fetch_assoc()['c'];

    if (!$tema || $tema['status'] === 'closed') {
        $msg = 'Pendaftaran sudah ditutup.'; $msg_type = 'error';
    } elseif ($jml >= $tema['kuota']) {
        $msg = 'Kuota sudah penuh.'; $msg_type = 'error';
    } else {
        // Cek double daftar — termasuk yang sudah dibatalkan tidak boleh daftar ulang
        $check = $conn->prepare("SELECT id, cancelled_at FROM pendaftaran WHERE tema_id=? AND user_id=?");
        $check->bind_param("ii", $tema_id, $user_id); $check->execute();
        $existing = $check->get_result()->fetch_assoc();
        if ($existing && !$existing['cancelled_at']) {
            $msg = 'Anda sudah terdaftar pada tema pelatihan ini.'; $msg_type = 'error';
        } elseif ($existing && $existing['cancelled_at']) {
            $msg = 'Anda pernah membatalkan pendaftaran ini dan tidak dapat mendaftar ulang.'; $msg_type = 'error';
        } else {
            $stmt = $conn->prepare("INSERT INTO pendaftaran (tema_id,user_id,nama_lengkap,email,instansi,no_hp,nik,asal_kecamatan,npwp,no_rekening) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("iissssssss", $tema_id, $user_id, $nama, $email, $instansi, $no_hp, $nik, $kecamatan, $npwp, $no_rek);
            if ($stmt->execute()) {
                $daftar_id = $conn->insert_id;
                require_once '../config/qr.php';
                $qr_data = json_encode(['daftar_id'=>$daftar_id,'tema_id'=>$tema_id,'user_id'=>$user_id,'nama'=>$nama]);
                $qr_path = generate_qr($qr_data, 'daftar_'.$daftar_id);
                if ($qr_path) $conn->query("UPDATE pendaftaran SET qr_code='$qr_path' WHERE id=$daftar_id");
                $msg = 'Pendaftaran berhasil! QR Code Anda sudah tersedia.'; $msg_type = 'success';
            } else {
                $msg = 'Gagal mendaftar, silakan coba lagi.'; $msg_type = 'error';
            }
        }
    }
}

// Tambah kolom tanggal_selesai jika belum ada
$conn->query("ALTER TABLE tema_pelatihan ADD COLUMN IF NOT EXISTS tanggal_selesai DATE DEFAULT NULL AFTER tanggal");
$conn->query("ALTER TABLE pendaftaran ADD COLUMN IF NOT EXISTS npwp VARCHAR(25) DEFAULT NULL");
$conn->query("ALTER TABLE pendaftaran ADD COLUMN IF NOT EXISTS no_rekening VARCHAR(30) DEFAULT NULL");
$conn->query("ALTER TABLE pendaftaran ADD COLUMN IF NOT EXISTS asal_kecamatan VARCHAR(100) DEFAULT NULL");

// Cek apakah kolom tema_id sudah ada
$col_check = $conn->query("SHOW COLUMNS FROM modul LIKE 'tema_id'");
$has_tema_col = $col_check->num_rows > 0;

if ($has_tema_col) {
    $stmt = $conn->prepare("
        SELECT m.*, k.nama as kategori_nama,
               tp.id as tema_id, tp.judul as tema_judul,
               tp.lokasi as tema_lokasi, tp.tanggal as tema_tanggal,
               tp.tanggal_selesai as tema_tanggal_selesai,
               tp.kuota as tema_kuota, tp.status as tema_status, tp.deskripsi as tema_deskripsi,
               (SELECT COUNT(*) FROM pendaftaran p WHERE p.tema_id=tp.id AND p.status!='rejected') as tema_terdaftar
        FROM modul m
        LEFT JOIN kategori k ON m.kategori_id = k.id
        LEFT JOIN tema_pelatihan tp ON m.tema_id = tp.id
        WHERE m.id = ? AND m.status = 'published'
    ");
} else {
    $stmt = $conn->prepare("
        SELECT m.*, k.nama as kategori_nama
        FROM modul m
        LEFT JOIN kategori k ON m.kategori_id = k.id
        WHERE m.id = ? AND m.status = 'published'
    ");
}
$stmt->bind_param("i", $id);
$stmt->execute();
$m = $stmt->get_result()->fetch_assoc();
if (!$m) { header('Location: katalog.php'); exit; }

$conn->query("UPDATE modul SET views = views + 1 WHERE id = $id");

// Cek apakah user sudah daftar tema ini
$sudah_daftar = false;
if ($is_logged_in && !empty($m['tema_id'])) {
    $uid = $_SESSION['user_id'];
    $tid = (int)$m['tema_id'];
    $sudah_daftar = $conn->query("SELECT id FROM pendaftaran WHERE tema_id=$tid AND user_id=$uid")->num_rows > 0;
}

function badge_class($kat) {
    $map = ['Internal'=>'badge-internal','Kegiatan'=>'badge-kegiatan','External'=>'badge-external'];
    return $map[$kat] ?? 'badge-internal';
}
?>
<div class="main-content">
<div class="detail-page">
    <a href="katalog.php" class="btn-back">&larr; Kembali ke Katalog</a>

    <?php if ($msg): ?>
    <div class="alert alert-<?= $msg_type === 'success' ? 'success' : 'error' ?>" style="margin-bottom:16px">
        <?= htmlspecialchars($msg) ?>
    </div>
    <?php endif; ?>

    <div class="detail-thumb">
        <?php if ($m['thumbnail']): ?>
            <img src="../<?= htmlspecialchars($m['thumbnail']) ?>" alt="<?= htmlspecialchars($m['judul']) ?>">
        <?php else: ?>
            <div style="width:100%;height:100%;background:linear-gradient(135deg,#1a237e 0%,#3f51b5 60%,#7986cb 100%);display:flex;align-items:center;justify-content:center">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.4)" stroke-width="1.5"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
            </div>
        <?php endif; ?>
    </div>

    <span class="badge <?= badge_class($m['kategori_nama']) ?>"><?= htmlspecialchars($m['kategori_nama'] ?? '') ?></span>
    <h1 style="font-size:26px;font-weight:700;margin:12px 0 16px"><?= htmlspecialchars($m['judul']) ?></h1>
    <div class="detail-meta">
        <span><svg class="icon" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> <?= $m['views'] ?> dilihat</span>
    </div>

    <!-- INFO ACARA -->
    <div class="acara-card">
        <div class="acara-item">
            <div class="acara-icon"><svg class="icon icon-lg" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
            <div class="acara-detail">
                <span class="acara-label">Tanggal Acara</span>
                <span class="acara-value"><?= date('l, d F Y', strtotime($m['tanggal'])) ?></span>
            </div>
        </div>
        <?php if ($m['instruktur']): ?>
        <div class="acara-item">
            <div class="acara-icon"><svg class="icon icon-lg" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
            <div class="acara-detail">
                <span class="acara-label">Narasumber / Instruktur</span>
                <span class="acara-value"><?= htmlspecialchars($m['instruktur']) ?></span>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($m['lokasi']): ?>
        <div class="acara-item">
            <div class="acara-icon"><svg class="icon icon-lg" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></div>
            <div class="acara-detail">
                <span class="acara-label">Lokasi / Alamat Acara</span>
                <span class="acara-value"><?= htmlspecialchars($m['lokasi']) ?></span>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="detail-desc">
        <?= nl2br(htmlspecialchars($m['deskripsi'])) ?>
    </div>

    <?php if ($m['file_ppt']): ?>
    <div class="materi-box">
        <div class="materi-icon-wrap">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div class="materi-info">
            <strong>Materi Tersedia</strong>
            <p>Buka dan pelajari materi pelatihan ini secara online.</p>
        </div>
        <?php if ($is_logged_in): ?>
            <a href="viewer.php?id=<?= $m['id'] ?>" class="btn-primary">Buka Materi</a>
        <?php else: ?>
            <a href="../auth/login.php?redirect=<?= urlencode('pages/viewer.php?id='.$m['id']) ?>" class="btn-login-required">Login untuk Membuka</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($m['tema_id'])): ?>
    <?php
        $sisa   = $m['tema_kuota'] - $m['tema_terdaftar'];
        $persen = $m['tema_kuota'] > 0 ? min(100, round(($m['tema_terdaftar'] / $m['tema_kuota']) * 100)) : 0;
        // Ambil data pendaftaran user untuk tema ini
        $pd_detail = null;
        $bisa_batal_detail = false;
        if ($sudah_daftar) {
            $tid = (int)$m['tema_id'];
            $uid = $_SESSION['user_id'];
            $pd_detail = $conn->query("SELECT * FROM pendaftaran WHERE tema_id=$tid AND user_id=$uid AND cancelled_at IS NULL LIMIT 1")->fetch_assoc();
            if ($pd_detail) {
                // Auto-generate QR jika belum ada
                if (empty($pd_detail['qr_code'])) {
                    require_once '../config/qr.php';
                    $qr_data = json_encode(['daftar_id'=>$pd_detail['id'],'tema_id'=>$tid,'user_id'=>$uid,'nama'=>$pd_detail['nama_lengkap']]);
                    $qr_path = generate_qr($qr_data, 'daftar_'.$pd_detail['id']);
                    if ($qr_path) {
                        $conn->query("UPDATE pendaftaran SET qr_code='".mysqli_real_escape_string($conn,$qr_path)."' WHERE id={$pd_detail['id']}");
                        $pd_detail['qr_code'] = $qr_path;
                    }
                }
                $batas = strtotime($m['tema_tanggal']) - (3 * 86400);
                $bisa_batal_detail = time() <= $batas;
            }
        }
    ?>
    <div class="tema-box">
        <div class="tema-box-header">
            <div>
                <strong>Tema Pelatihan Terkait</strong>
                <p><?= htmlspecialchars($m['tema_judul']) ?></p>
            </div>
            <span class="tema-status-badge status-<?= $m['tema_status'] ?>">
                <?= $m['tema_status'] === 'open' ? 'Pendaftaran Dibuka' : 'Ditutup' ?>
            </span>
        </div>
        <div class="tema-box-meta">
            <span><?php
                $tgl_mulai = date('d/m/Y', strtotime($m['tema_tanggal']));
                $tgl_selesai = !empty($m['tema_tanggal_selesai']) ? ' - '.date('d/m/Y', strtotime($m['tema_tanggal_selesai'])) : '';
                echo $tgl_mulai . $tgl_selesai;
            ?></span>
            <span><?= htmlspecialchars($m['tema_lokasi']) ?></span>
        </div>
        <?php if ($m['tema_deskripsi']): ?>
        <p style="font-size:13px;color:#666;margin:10px 0"><?= htmlspecialchars($m['tema_deskripsi']) ?></p>
        <?php endif; ?>
        <div class="kuota-bar" style="margin:12px 0">
            <div class="kuota-info">
                <span>Peserta terdaftar: <?= $m['tema_terdaftar'] ?>/<?= $m['tema_kuota'] ?></span>
                <span><?= $sisa ?> tempat tersisa</span>
            </div>
            <div class="kuota-progress"><div class="kuota-fill" style="width:<?= $persen ?>%"></div></div>
        </div>
        <div class="tema-box-footer" style="flex-direction:column;align-items:stretch;gap:10px">
            <?php if ($sudah_daftar && $pd_detail): ?>
                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                    <span class="badge-terdaftar">Sudah Terdaftar</span>
                    <button class="btn-qr" onclick="toggleQRDetail()">QR Code</button>
                    <?php if ($bisa_batal_detail): ?>
                    <button class="btn-batal-daftar" onclick="document.getElementById('modalBatalDetail').classList.add('open')">Batalkan</button>
                    <?php else: ?>
                    <span style="font-size:11px;color:#aaa">Batas batal: <?= date('d M Y', strtotime($m['tema_tanggal']) - 3*86400) ?></span>
                    <?php endif; ?>
                </div>
                <div id="qr-detail-panel" style="display:none;text-align:center;background:#f8f9ff;border-radius:8px;padding:14px;border:1px solid #e0e0e0">
                    <?php if ($pd_detail['qr_code']): ?>
                    <img src="../<?= htmlspecialchars($pd_detail['qr_code']) ?>" style="width:150px;height:150px;border-radius:6px;display:block;margin:0 auto">
                    <p style="font-size:12px;color:#888;margin:6px 0 8px">Tunjukkan QR ini saat hadir</p>
                    <a href="../<?= htmlspecialchars($pd_detail['qr_code']) ?>" download class="btn-primary" style="font-size:12px;padding:6px 14px;display:inline-flex">Unduh QR</a>
                    <?php else: ?>
                    <p style="color:#aaa;font-size:13px">QR Code belum tersedia</p>
                    <?php endif; ?>
                </div>
            <?php elseif ($m['tema_status'] === 'open' && $sisa > 0): ?>
                <a href="tema.php" class="btn-primary" style="text-decoration:none;text-align:center">Lihat Tema Pelatihan</a>
            <?php else: ?>
                <span class="badge-penuh">Kuota Penuh / Ditutup</span>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($sudah_daftar && $pd_detail && $bisa_batal_detail): ?>
    <!-- MODAL BATALKAN dari detail -->
    <div class="modal-overlay" id="modalBatalDetail">
        <div class="modal">
            <h3>Batalkan Pendaftaran</h3>
            <p style="color:#e53935;font-weight:600;margin-bottom:4px;font-size:14px"><?= htmlspecialchars($m['tema_judul']) ?></p>
            <form method="POST" action="../pages/tema.php">
                <input type="hidden" name="aksi" value="batal">
                <input type="hidden" name="daftar_id" value="<?= $pd_detail['id'] ?>">
                <div class="form-group" style="margin-top:16px">
                    <label>Alasan Pembatalan <span style="color:#e53935">*</span></label>
                    <textarea name="alasan_batal" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px;resize:vertical;min-height:90px" placeholder="Jelaskan alasan pembatalan..." required></textarea>
                </div>
                <div class="alert alert-error" style="font-size:13px;margin-bottom:12px">Pembatalan tidak dapat dibatalkan kembali.</div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="document.getElementById('modalBatalDetail').classList.remove('open')">Kembali</button>
                    <button type="submit" class="btn-primary" style="background:#e53935" onclick="return confirm('Yakin ingin membatalkan?')">Konfirmasi</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- MODAL PENDAFTARAN -->
    <?php if ($is_logged_in && !$sudah_daftar): ?>
    <div class="modal-overlay" id="modalDaftar">
        <div class="modal">
            <h3>Daftar Pelatihan</h3>
            <p style="color:#3f51b5;font-weight:600;margin-bottom:20px;font-size:14px"><?= htmlspecialchars($m['tema_judul']) ?></p>
            <form method="POST">
                <input type="hidden" name="aksi" value="daftar">
                <input type="hidden" name="tema_id" value="<?= $m['tema_id'] ?>">
                <div class="form-group"><label>Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px" value="<?= htmlspecialchars($_SESSION['nama'] ?? '') ?>" required>
                </div>
                <div class="form-group"><label>Email</label>
                    <input type="email" name="email" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px" value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>" required>
                </div>
                <div class="form-group"><label>Instansi / Unit Kerja</label>
                    <input type="text" name="instansi" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px" placeholder="Contoh: Dinas Komunikasi dan Informatika">
                </div>
                <div class="form-group"><label>No. HP / WhatsApp</label>
                    <input type="text" name="no_hp" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px" placeholder="08xxxxxxxxxx">
                </div>
                <div class="form-group"><label>NIK / NIS</label>
                    <input type="text" name="nik" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px" placeholder="Nomor Induk Kependudukan / Siswa" maxlength="20">
                </div>
                <div class="form-group"><label>Alamat</label>
                    <textarea name="alamat" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px;resize:vertical;min-height:70px" placeholder="Alamat lengkap"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="document.getElementById('modalDaftar').classList.remove('open')">Batal</button>
                    <button type="submit" class="btn-primary">Kirim Pendaftaran</button>
                </div>
            </form>
        </div>
    </div>
    <script>
    document.querySelectorAll('.modal-overlay').forEach(o => {
        o.addEventListener('click', function(e) { if (e.target === this) this.classList.remove('open'); });
    });
    function toggleQRDetail() {
        const p = document.getElementById('qr-detail-panel');
        p.style.display = p.style.display === 'none' ? 'block' : 'none';
    }
    </script>
    <?php endif; ?>
    <?php endif; ?>

</div>
<?php require_once '../includes/footer.php'; ?>
