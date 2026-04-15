<?php
require_once '../includes/header.php';

// Handle pendaftaran
$msg = ''; $msg_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'daftar') {
    if (!$is_logged_in) {
        header('Location: ../auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    $tema_id = (int)$_POST['tema_id'];
    $nama = trim($_POST['nama_lengkap']);
    $email = trim($_POST['email']);
    $instansi = trim($_POST['instansi']);
    $no_hp = trim($_POST['no_hp']);
    $user_id = $_SESSION['user_id'];

    // Cek kuota
    $tema = $conn->query("SELECT * FROM tema_pelatihan WHERE id = $tema_id")->fetch_assoc();
    $jml = $conn->query("SELECT COUNT(*) as c FROM pendaftaran WHERE tema_id = $tema_id AND status != 'rejected'")->fetch_assoc()['c'];

    if (!$tema || $tema['status'] === 'closed') {
        $msg = 'Pendaftaran sudah ditutup.'; $msg_type = 'error';
    } elseif ($jml >= $tema['kuota']) {
        $msg = 'Kuota sudah penuh.'; $msg_type = 'error';
    } else {
        $check = $conn->prepare("SELECT id FROM pendaftaran WHERE tema_id=? AND user_id=?");
        $check->bind_param("ii", $tema_id, $user_id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $msg = 'Anda sudah mendaftar pada tema pelatihan ini.'; $msg_type = 'error';
        } else {
            $stmt = $conn->prepare("INSERT INTO pendaftaran (tema_id, user_id, nama_lengkap, email, instansi, no_hp) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param("iissss", $tema_id, $user_id, $nama, $email, $instansi, $no_hp);
            if ($stmt->execute()) {
                $msg = 'Pendaftaran berhasil! Kami akan menghubungi Anda segera.'; $msg_type = 'success';
            } else {
                $msg = 'Gagal mendaftar, silakan coba lagi.'; $msg_type = 'error';
            }
        }
    }
}

// Ambil semua tema
$temas = $conn->query("SELECT t.*, (SELECT COUNT(*) FROM pendaftaran p WHERE p.tema_id = t.id AND p.status != 'rejected') as terdaftar FROM tema_pelatihan t ORDER BY t.tanggal ASC")->fetch_all(MYSQLI_ASSOC);

// Cek tema yang sudah didaftar user
$sudah_daftar = [];
if ($is_logged_in) {
    $uid = $_SESSION['user_id'];
    $res = $conn->query("SELECT tema_id FROM pendaftaran WHERE user_id = $uid");
    while ($r = $res->fetch_assoc()) $sudah_daftar[] = $r['tema_id'];
}

$open_tema = isset($_GET['daftar']) ? (int)$_GET['daftar'] : 0;
?>
<div class="main-content">
<div class="tema-page">
    <div class="tema-header">
        <h1>Tema Pelatihan</h1>
        <p>Pilih dan daftarkan diri Anda pada tema pelatihan yang tersedia</p>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?= $msg_type === 'success' ? 'success' : 'error' ?>" style="max-width:900px;margin:0 auto 20px">
        <?= htmlspecialchars($msg) ?>
    </div>
    <?php endif; ?>

    <div class="tema-grid">
        <?php foreach ($temas as $t):
            $sisa = $t['kuota'] - $t['terdaftar'];
            $persen = $t['kuota'] > 0 ? min(100, round(($t['terdaftar'] / $t['kuota']) * 100)) : 0;
            $sudah = in_array($t['id'], $sudah_daftar);
        ?>
        <div class="tema-card">
            <div class="tema-thumb">
                <?php if ($t['thumbnail']): ?>
                    <img src="../<?= htmlspecialchars($t['thumbnail']) ?>" alt="">
                <?php else: ?>
                    <div class="tema-thumb-placeholder">&#127979;</div>
                <?php endif; ?>
                <span class="tema-status-badge status-<?= $t['status'] ?>">
                    <?= $t['status'] === 'open' ? 'Pendaftaran Dibuka' : 'Ditutup' ?>
                </span>
            </div>
            <div class="tema-body">
                <h3><?= htmlspecialchars($t['judul']) ?></h3>
                <p class="tema-desc"><?= htmlspecialchars($t['deskripsi']) ?></p>
                <div class="tema-meta">
                    <span>&#128197; <?= date('d M Y', strtotime($t['tanggal'])) ?></span>
                    <span>&#128100; <?= htmlspecialchars($t['instruktur']) ?></span>
                    <span>&#128205; <?= htmlspecialchars($t['lokasi']) ?></span>
                </div>
                <div class="kuota-bar">
                    <div class="kuota-info">
                        <span>Peserta: <?= $t['terdaftar'] ?>/<?= $t['kuota'] ?></span>
                        <span><?= $sisa ?> tempat tersisa</span>
                    </div>
                    <div class="kuota-progress">
                        <div class="kuota-fill" style="width:<?= $persen ?>%"></div>
                    </div>
                </div>
                <div class="tema-footer">
                    <?php if ($sudah): ?>
                        <span class="badge-terdaftar">&#10003; Sudah Terdaftar</span>
                    <?php elseif ($t['status'] === 'open' && $sisa > 0): ?>
                        <button class="btn-daftar" onclick="openModalDaftar(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['judul'])) ?>')">
                            Daftar Sekarang
                        </button>
                    <?php else: ?>
                        <span class="badge-penuh">Kuota Penuh</span>
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

<!-- MODAL PENDAFTARAN -->
<div class="modal-overlay" id="modalDaftar">
    <div class="modal">
        <h3>Daftar Pelatihan</h3>
        <p id="modal-tema-judul" style="color:#3f51b5;font-weight:600;margin-bottom:20px;font-size:14px"></p>
        <?php if (!$is_logged_in): ?>
            <div class="alert alert-error">Anda harus <a href="../auth/login.php">masuk</a> terlebih dahulu untuk mendaftar.</div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="document.getElementById('modalDaftar').classList.remove('open')">Tutup</button>
                <a href="../auth/login.php" class="btn-primary">Masuk</a>
            </div>
        <?php else: ?>
        <form method="POST">
            <input type="hidden" name="aksi" value="daftar">
            <input type="hidden" name="tema_id" id="input-tema-id">
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama_lengkap" class="form-control" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px" value="<?= htmlspecialchars($_SESSION['nama'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px" value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Instansi / Unit Kerja</label>
                <input type="text" name="instansi" class="form-control" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px" placeholder="Contoh: Dinas Komunikasi dan Informatika">
            </div>
            <div class="form-group">
                <label>No. HP / WhatsApp</label>
                <input type="text" name="no_hp" class="form-control" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px" placeholder="08xxxxxxxxxx">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="document.getElementById('modalDaftar').classList.remove('open')">Batal</button>
                <button type="submit" class="btn-primary">Kirim Pendaftaran</button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
function openModalDaftar(id, judul) {
    document.getElementById('input-tema-id').value = id;
    document.getElementById('modal-tema-judul').textContent = judul;
    document.getElementById('modalDaftar').classList.add('open');
}
document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', function(e) { if (e.target === this) this.classList.remove('open'); });
});
</script>

</div>
<?php require_once '../includes/footer.php'; ?>
