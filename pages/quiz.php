<?php
require_once '../includes/header.php';
if (!$is_logged_in) {
    header('Location: ../auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'])); exit;
}

// Pastikan semua tabel yang dibutuhkan sudah ada
$conn->query("ALTER TABLE modul ADD COLUMN IF NOT EXISTS sertifikat_template VARCHAR(255) DEFAULT NULL");
$conn->query("CREATE TABLE IF NOT EXISTS quiz_soal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    modul_id INT NOT NULL,
    pertanyaan TEXT NOT NULL,
    opsi_a VARCHAR(255) NOT NULL,
    opsi_b VARCHAR(255) NOT NULL,
    opsi_c VARCHAR(255),
    opsi_d VARCHAR(255),
    jawaban_benar ENUM('a','b','c','d') NOT NULL,
    urutan INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$conn->query("CREATE TABLE IF NOT EXISTS quiz_hasil (
    id INT AUTO_INCREMENT PRIMARY KEY,
    modul_id INT NOT NULL,
    user_id INT NOT NULL,
    skor INT DEFAULT 0,
    lulus TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_hasil (modul_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$conn->query("CREATE TABLE IF NOT EXISTS materi_baca (
    id INT AUTO_INCREMENT PRIMARY KEY,
    modul_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_baca (modul_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$conn->query("CREATE TABLE IF NOT EXISTS sertifikat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    modul_id INT NOT NULL,
    user_id INT NOT NULL,
    nama_peserta VARCHAR(100),
    kode_sertifikat VARCHAR(20),
    file_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_sertif (modul_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$modul_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$modul_id) { header('Location: katalog.php'); exit; }

$modul = $conn->query("SELECT * FROM modul WHERE id=$modul_id AND status='published'")->fetch_assoc();
if (!$modul) { header('Location: katalog.php'); exit; }

$uid = $_SESSION['user_id'];

// Cek sudah baca materi
$sudah_baca = $conn->query("SELECT id FROM materi_baca WHERE modul_id=$modul_id AND user_id=$uid")->num_rows > 0;
// Cek sudah punya hasil quiz
$hasil = $conn->query("SELECT * FROM quiz_hasil WHERE modul_id=$modul_id AND user_id=$uid")->fetch_assoc();
// Cek sudah punya sertifikat
$sertifikat = $conn->query("SELECT * FROM sertifikat WHERE modul_id=$modul_id AND user_id=$uid")->fetch_assoc();

// Handle submit quiz
$msg = ''; $skor_hasil = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {

    if ($_POST['aksi'] === 'tandai_baca') {
        $conn->query("INSERT IGNORE INTO materi_baca (modul_id, user_id) VALUES ($modul_id, $uid)");
        $sudah_baca = true;
    }

    if ($_POST['aksi'] === 'submit_quiz' && $sudah_baca && !$hasil) {
        $soals = $conn->query("SELECT * FROM quiz_soal WHERE modul_id=$modul_id ORDER BY urutan")->fetch_all(MYSQLI_ASSOC);
        $total = count($soals); $benar = 0;
        foreach ($soals as $s) {
            $jawaban = $_POST['jawaban_' . $s['id']] ?? '';
            if ($jawaban === $s['jawaban_benar']) $benar++;
        }
        $skor = $total > 0 ? round(($benar / $total) * 100) : 0;
        $lulus = $skor >= 70 ? 1 : 0;
        $stmt = $conn->prepare("INSERT INTO quiz_hasil (modul_id, user_id, skor, lulus) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE skor=VALUES(skor), lulus=VALUES(lulus)");
        $stmt->bind_param("iiii", $modul_id, $uid, $skor, $lulus);
        $stmt->execute();
        $hasil = ['skor' => $skor, 'lulus' => $lulus];
        $skor_hasil = $skor;

        // Generate sertifikat jika lulus
        if ($lulus && !$sertifikat) {
            $nama_peserta = $_SESSION['nama'];
            $kode = strtoupper(substr(md5($uid . $modul_id . time()), 0, 10));
            $file_sert = null;

            if ($modul['sertifikat_template'] && file_exists('../' . $modul['sertifikat_template'])) {
                $file_sert = generate_sertifikat('../' . $modul['sertifikat_template'], $nama_peserta, $kode, $modul['judul']);
            }

            $stmt2 = $conn->prepare("INSERT IGNORE INTO sertifikat (modul_id, user_id, nama_peserta, kode_sertifikat, file_path) VALUES (?,?,?,?,?)");
            $stmt2->bind_param("iisss", $modul_id, $uid, $nama_peserta, $kode, $file_sert);
            $stmt2->execute();
            $sertifikat = $conn->query("SELECT * FROM sertifikat WHERE modul_id=$modul_id AND user_id=$uid")->fetch_assoc();
        }
    }
}

// Ambil soal
$soals = $conn->query("SELECT * FROM quiz_soal WHERE modul_id=$modul_id ORDER BY urutan")->fetch_all(MYSQLI_ASSOC);

function generate_sertifikat($template_path, $nama, $kode, $judul_modul) {
    $ext = strtolower(pathinfo($template_path, PATHINFO_EXTENSION));
    if ($ext === 'png') $img = imagecreatefrompng($template_path);
    elseif (in_array($ext, ['jpg','jpeg'])) $img = imagecreatefromjpeg($template_path);
    else return null;

    $w = imagesx($img); $h = imagesy($img);
    $hitam = imagecolorallocate($img, 30, 30, 30);
    $biru  = imagecolorallocate($img, 63, 81, 181);

    // Tulis nama di tengah (posisi ~55% dari atas)
    $font = __DIR__ . '/../assets/fonts/OpenSans-Bold.ttf';
    if (file_exists($font)) {
        $size_nama = max(28, min(48, (int)($w / 18)));
        $bbox = imagettfbbox($size_nama, 0, $font, $nama);
        $tw = $bbox[2] - $bbox[0];
        $x = ($w - $tw) / 2;
        $y = $h * 0.55;
        imagettftext($img, $size_nama, 0, (int)$x, (int)$y, $biru, $font, $nama);

        // Kode sertifikat
        $size_kode = 14;
        $bbox2 = imagettfbbox($size_kode, 0, $font, $kode);
        $tw2 = $bbox2[2] - $bbox2[0];
        imagettftext($img, $size_kode, 0, (int)(($w - $tw2)/2), (int)($h * 0.88), $hitam, $font, 'Kode: ' . $kode);
    } else {
        // Fallback tanpa font TTF
        $scale = max(1, (int)($w / 400));
        imagestring($img, 5, (int)($w/2 - strlen($nama)*4*$scale), (int)($h*0.53), $nama, $biru);
        imagestring($img, 3, (int)($w/2 - 60), (int)($h*0.87), 'Kode: '.$kode, $hitam);
    }

    $dir = '../uploads/sertifikat/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $filename = 'sertifikat_' . $kode . '.png';
    imagepng($img, $dir . $filename);
    imagedestroy($img);
    return 'uploads/sertifikat/' . $filename;
}
?>
<div class="main-content">
<div class="quiz-page">
    <a href="detail.php?id=<?= $modul_id ?>" class="btn-back">&larr; Kembali ke Modul</a>
    <h1><?= htmlspecialchars($modul['judul']) ?></h1>
    <p style="color:#888;margin-bottom:24px">Selesaikan persyaratan berikut untuk mendapatkan sertifikat</p>

    <!-- PROGRESS STEPS -->
    <div class="quiz-steps">
        <div class="quiz-step <?= $sudah_baca ? 'done' : 'active' ?>">
            <div class="step-num"><?= $sudah_baca ? '✓' : '1' ?></div>
            <div class="step-label">Baca Materi</div>
        </div>
        <div class="step-line"></div>
        <div class="quiz-step <?= $hasil ? ($hasil['lulus'] ? 'done' : 'failed') : ($sudah_baca ? 'active' : '') ?>">
            <div class="step-num"><?= $hasil ? ($hasil['lulus'] ? '✓' : '✗') : '2' ?></div>
            <div class="step-label">Quiz</div>
        </div>
        <div class="step-line"></div>
        <div class="quiz-step <?= $sertifikat ? 'done' : '' ?>">
            <div class="step-num"><?= $sertifikat ? '✓' : '3' ?></div>
            <div class="step-label">Sertifikat</div>
        </div>
    </div>

    <?php if ($sertifikat): ?>
    <!-- SERTIFIKAT TERSEDIA -->
    <div class="sertifikat-box">
        <div style="font-size:48px;margin-bottom:12px"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg></div>
        <h2>Selamat, <?= htmlspecialchars($sertifikat['nama_peserta']) ?>!</h2>
        <p>Anda telah berhasil menyelesaikan modul ini dan mendapatkan sertifikat.</p>
        <div class="sertifikat-kode">Kode Sertifikat: <strong><?= $sertifikat['kode_sertifikat'] ?></strong></div>
        <?php if ($sertifikat['file_path']): ?>
            <div style="margin-top:16px">
                <img src="../<?= htmlspecialchars($sertifikat['file_path']) ?>" style="max-width:100%;border-radius:8px;box-shadow:0 4px 20px rgba(0,0,0,0.15)">
            </div>
            <a href="../<?= htmlspecialchars($sertifikat['file_path']) ?>" download="Sertifikat_<?= htmlspecialchars($sertifikat['nama_peserta']) ?>.png" class="btn-primary" style="margin-top:16px;display:inline-flex">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Unduh Sertifikat
            </a>
        <?php else: ?>
            <p style="color:#888;margin-top:12px;font-size:13px">Template sertifikat belum diupload admin. Hubungi admin untuk mendapatkan sertifikat fisik.</p>
        <?php endif; ?>
    </div>

    <?php elseif ($hasil && !$hasil['lulus']): ?>
    <!-- GAGAL QUIZ -->
    <div class="quiz-result failed">
        <div style="font-size:40px"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></div>
        <h3>Skor Anda: <?= $hasil['skor'] ?>/100</h3>
        <p>Minimal skor lulus adalah 70. Silakan pelajari materi kembali dan coba lagi.</p>
        <form method="POST">
            <input type="hidden" name="aksi" value="reset_quiz">
            <a href="viewer.php?id=<?= $modul_id ?>" class="btn-primary" style="margin-right:8px">Baca Ulang Materi</a>
        </form>
    </div>

    <?php elseif ($hasil && $hasil['lulus'] && !$sertifikat): ?>
    <div class="alert alert-success">Quiz lulus! Sertifikat sedang diproses...</div>

    <?php elseif ($sudah_baca && !$hasil && !empty($soals)): ?>
    <!-- FORM QUIZ -->
    <?php if ($skor_hasil !== null): ?>
        <div class="alert alert-<?= $skor_hasil >= 70 ? 'success' : 'error' ?>">
            Skor Anda: <?= $skor_hasil ?>/100 — <?= $skor_hasil >= 70 ? 'Lulus!' : 'Belum lulus, minimal 70.' ?>
        </div>
    <?php endif; ?>
    <div class="quiz-form-wrap">
        <h3 style="margin-bottom:20px">Quiz — <?= count($soals) ?> Soal</h3>
        <form method="POST">
            <input type="hidden" name="aksi" value="submit_quiz">
            <?php foreach ($soals as $i => $s): ?>
            <div class="soal-item">
                <p class="soal-pertanyaan"><strong><?= $i+1 ?>.</strong> <?= htmlspecialchars($s['pertanyaan']) ?></p>
                <?php foreach (['a','b','c','d'] as $op):
                    $val = $s['opsi_'.$op];
                    if (!$val) continue;
                ?>
                <label class="soal-opsi">
                    <input type="radio" name="jawaban_<?= $s['id'] ?>" value="<?= $op ?>" required>
                    <span><?= strtoupper($op) ?>. <?= htmlspecialchars($val) ?></span>
                </label>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
            <button type="submit" class="btn-primary" style="margin-top:20px;width:100%;justify-content:center">
                Kirim Jawaban
            </button>
        </form>
    </div>

    <?php elseif ($sudah_baca && empty($soals)): ?>
    <div class="alert alert-error">Quiz belum tersedia untuk modul ini. Hubungi admin.</div>

    <?php else: ?>
    <!-- STEP 1: BACA MATERI -->
    <div class="baca-materi-box">
        <div style="font-size:40px;margin-bottom:12px"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg></div>
        <h3>Langkah 1: Baca Materi</h3>
        <p>Buka dan pelajari materi modul ini terlebih dahulu, kemudian tandai sebagai sudah dibaca.</p>
        <div style="display:flex;gap:12px;justify-content:center;margin-top:20px;flex-wrap:wrap">
            <?php if ($modul['file_ppt']): ?>
            <a href="viewer.php?id=<?= $modul_id ?>" target="_blank" class="btn-primary">Buka Materi</a>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="aksi" value="tandai_baca">
                <button type="submit" class="btn-daftar">Tandai Sudah Dibaca</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

</div>
</div>
<?php require_once '../includes/footer.php'; ?>
