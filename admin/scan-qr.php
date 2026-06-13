<?php
require_once '../includes/header.php';
if (!$is_admin) { header('Location: ../index.php'); exit; }

$result = null; $error = '';

// Handle verifikasi QR via POST (dari scan atau input manual)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qr_data'])) {
    $raw = trim($_POST['qr_data']);
    $data = json_decode($raw, true);
    if (!$data || empty($data['daftar_id'])) {
        $error = 'QR Code tidak valid.';
    } else {
        $did = (int)$data['daftar_id'];
        $pd  = $conn->query("SELECT pd.*, tp.judul as tema_judul, tp.tanggal as tema_tanggal
            FROM pendaftaran pd JOIN tema_pelatihan tp ON pd.tema_id=tp.id
            WHERE pd.id=$did AND pd.cancelled_at IS NULL")->fetch_assoc();
        if (!$pd) {
            $error = 'Data pendaftaran tidak ditemukan atau sudah dibatalkan.';
        } else {
            if ($pd['hadir']) {
                $result = ['status' => 'sudah', 'data' => $pd];
            } else {
                $now = date('Y-m-d H:i:s');
                $conn->query("UPDATE pendaftaran SET hadir=1, hadir_at='$now' WHERE id=$did");
                $pd['hadir'] = 1; $pd['hadir_at'] = $now;
                $result = ['status' => 'berhasil', 'data' => $pd];
            }
        }
    }
}

// Daftar hadir hari ini
$hadir_hari_ini = $conn->query("SELECT pd.*, tp.judul as tema_judul FROM pendaftaran pd
    JOIN tema_pelatihan tp ON pd.tema_id=tp.id
    WHERE pd.hadir=1 AND DATE(pd.hadir_at)=CURDATE()
    ORDER BY pd.hadir_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<div class="main-content">
<div class="admin-page">
    <div class="admin-header">
        <div><h1>Scan QR Kehadiran</h1><p>Scan QR Code peserta untuk mencatat kehadiran</p></div>
        <a href="panel.php" class="btn-primary" style="background:#666">&larr; Kembali</a>
    </div>

    <div class="scan-layout">
        <!-- PANEL SCAN -->
        <div class="scan-panel">
            <div class="scan-box" id="scan-box">
                <div id="reader"></div>
                <div class="scan-overlay-text" id="scan-overlay-text">Klik tombol di bawah untuk mulai scan</div>
            </div>
            <p id="scan-error-msg" style="display:none;color:#e53935;font-size:13px;margin-top:8px;padding:10px;background:#ffebee;border-radius:8px"></p>
            <button id="btn-scan" class="btn-primary" style="width:100%;justify-content:center;margin-top:12px" onclick="startScan()">
                <svg class="icon" viewBox="0 0 24 24"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg> Mulai Scan Kamera
            </button>
            <form method="POST" id="form-manual">
                <input type="hidden" name="qr_data" id="qr_data_input">
            </form>

            <!-- HASIL SCAN -->
            <?php if ($error): ?>
            <div class="scan-result error">
                <div style="font-size:32px"><svg class="icon icon-lg" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></div>
                <h3>QR Tidak Valid</h3>
                <p><?= htmlspecialchars($error) ?></p>
            </div>
            <?php endif; ?>
            <?php if ($result): ?>
            <div class="scan-result <?= $result['status']==='berhasil'?'success':'warning' ?>">
                <?php $pd = $result['data']; ?>
                <?php if ($result['status']==='berhasil'): ?>
                <div style="font-size:40px"><svg class="icon icon-lg" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></div>
                <h3>Kehadiran Tercatat!</h3>
                <?php else: ?>
                <div style="font-size:40px"><svg class="icon icon-lg" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div>
                <h3>Sudah Tercatat Sebelumnya</h3>
                <?php endif; ?>
                <div class="scan-info-box">
                    <div><span>Nama</span><strong><?= htmlspecialchars($pd['nama_lengkap']) ?></strong></div>
                    <div><span>Tema</span><strong><?= htmlspecialchars($pd['tema_judul']) ?></strong></div>
                    <div><span>Instansi</span><strong><?= htmlspecialchars($pd['instansi']??'-') ?></strong></div>
                    <div><span>Waktu Hadir</span><strong><?= $pd['hadir_at'] ? date('d M Y H:i', strtotime($pd['hadir_at'])) : '-' ?></strong></div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- DAFTAR HADIR HARI INI -->
        <div>
            <div class="card-form">
                <h3>Hadir Hari Ini (<?= count($hadir_hari_ini) ?> orang)</h3>
                <?php if (empty($hadir_hari_ini)): ?>
                <p style="color:#aaa;text-align:center;padding:20px 0;font-size:13px">Belum ada kehadiran hari ini</p>
                <?php else: ?>
                <div style="max-height:500px;overflow-y:auto">
                    <?php foreach ($hadir_hari_ini as $h): ?>
                    <div style="padding:10px 0;border-bottom:1px solid #f0f0f0">
                        <div style="font-weight:600;font-size:14px"><?= htmlspecialchars($h['nama_lengkap']) ?></div>
                        <div style="font-size:12px;color:#888"><?= htmlspecialchars($h['tema_judul']) ?></div>
                        <div style="font-size:11px;color:#aaa"><?= date('H:i', strtotime($h['hadir_at'])) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Library QR Scanner -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
let scanner = null;
let isScanning = false;

function startScan() {
    const btn = document.getElementById('btn-scan');
    const readerEl = document.getElementById('reader');
    const overlayText = document.getElementById('scan-overlay-text');

    if (isScanning) {
        // Stop scan
        if (scanner) {
            scanner.stop().then(() => {
                scanner.clear();
                scanner = null;
                isScanning = false;
                btn.textContent = 'Mulai Scan Kamera';
                btn.style.background = '';
                readerEl.innerHTML = '';
                overlayText.style.display = 'block';
            }).catch(() => {});
        }
        return;
    }

    // Cek apakah browser support
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        showError('Browser tidak mendukung akses kamera. Gunakan Chrome/Firefox terbaru.');
        return;
    }

    overlayText.style.display = 'none';
    btn.textContent = 'Stop Scan';
    btn.style.background = '#e53935';
    isScanning = true;

    scanner = new Html5Qrcode("reader");
    scanner.start(
        { facingMode: "environment" },
        { fps: 15, qrbox: { width: 260, height: 260 }, aspectRatio: 1.0 },
        (decodedText) => {
            // Berhasil scan
            isScanning = false;
            btn.textContent = 'Mulai Scan Kamera';
            btn.style.background = '';
            overlayText.style.display = 'block';
            scanner.stop().then(() => {
                scanner.clear();
                scanner = null;
                submitQR(decodedText);
            }).catch(() => {
                submitQR(decodedText);
            });
        },
        (errorMsg) => {
            // Frame tidak terbaca — normal, abaikan
        }
    ).catch(err => {
        isScanning = false;
        btn.textContent = 'Mulai Scan Kamera';
        btn.style.background = '';
        overlayText.style.display = 'block';
        let msg = 'Kamera tidak dapat diakses.';
        if (err.toString().includes('NotAllowedError')) {
            msg = 'Izin kamera ditolak. Klik ikon kunci di address bar dan izinkan akses kamera.';
        } else if (err.toString().includes('NotFoundError')) {
            msg = 'Kamera tidak ditemukan di perangkat ini.';
        }
        showError(msg);
    });
}

function submitQR(data) {
    document.getElementById('qr_data_input').value = data;
    document.getElementById('form-manual').submit();
}

function showError(msg) {
    const el = document.getElementById('scan-error-msg');
    el.textContent = msg;
    el.style.display = 'block';
}

// Beep sound saat berhasil scan
function playBeep() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain); gain.connect(ctx.destination);
        osc.frequency.value = 880;
        gain.gain.setValueAtTime(0.3, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + 0.3);
    } catch(e) {}
}
</script>

<?php require_once '../includes/footer.php'; ?>
