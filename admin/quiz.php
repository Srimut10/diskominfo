<?php
require_once '../includes/header.php';
if (!$is_admin) { header('Location: ../index.php'); exit; }

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

$modul_id = isset($_GET['modul']) ? (int)$_GET['modul'] : 0;
$msg = $_GET['msg'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah_soal') {
        $mid = (int)$_POST['modul_id'];
        $pert = trim($_POST['pertanyaan']);
        $a = trim($_POST['opsi_a']); $b = trim($_POST['opsi_b']);
        $c = trim($_POST['opsi_c']); $d = trim($_POST['opsi_d']);
        $jwb = $_POST['jawaban_benar'];
        $urt = (int)$_POST['urutan'];
        $stmt = $conn->prepare("INSERT INTO quiz_soal (modul_id,pertanyaan,opsi_a,opsi_b,opsi_c,opsi_d,jawaban_benar,urutan) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param("issssssi", $mid,$pert,$a,$b,$c,$d,$jwb,$urt);
        $stmt->execute();
        header("Location: quiz.php?modul=$mid&msg=added"); exit;
    }

    if ($aksi === 'edit_soal') {
        $id  = (int)$_POST['id'];
        $mid = (int)$_POST['modul_id'];
        $pert = trim($_POST['pertanyaan']);
        $a = trim($_POST['opsi_a']); $b = trim($_POST['opsi_b']);
        $c = trim($_POST['opsi_c']); $d = trim($_POST['opsi_d']);
        $jwb = $_POST['jawaban_benar'];
        $urt = (int)$_POST['urutan'];
        $stmt = $conn->prepare("UPDATE quiz_soal SET pertanyaan=?,opsi_a=?,opsi_b=?,opsi_c=?,opsi_d=?,jawaban_benar=?,urutan=? WHERE id=?");
        $stmt->bind_param("ssssssii", $pert,$a,$b,$c,$d,$jwb,$urt,$id);
        $stmt->execute();
        header("Location: quiz.php?modul=$mid&msg=updated"); exit;
    }

    if ($aksi === 'hapus_soal') {
        $id  = (int)$_POST['id'];
        $mid = (int)$_POST['modul_id'];
        $conn->query("DELETE FROM quiz_soal WHERE id=$id");
        header("Location: quiz.php?modul=$mid&msg=deleted"); exit;
    }

    if ($aksi === 'upload_template') {
        $mid = (int)$_POST['modul_id'];
        if (isset($_FILES['template']) && $_FILES['template']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['template']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png','jpg','jpeg'])) {
                $dir = '../uploads/templates/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $fname = 'template_' . $mid . '_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['template']['tmp_name'], $dir . $fname);
                $path = 'uploads/templates/' . $fname;
                $stmt = $conn->prepare("UPDATE modul SET sertifikat_template=? WHERE id=?");
                $stmt->bind_param("si", $path, $mid); $stmt->execute();
            }
        }
        header("Location: quiz.php?modul=$mid&msg=uploaded"); exit;
    }
}

$moduls = $conn->query("SELECT id, judul, sertifikat_template FROM modul WHERE status='published' ORDER BY judul")->fetch_all(MYSQLI_ASSOC);
$soals = [];
$modul_aktif = null;
if ($modul_id) {
    $soals = $conn->query("SELECT * FROM quiz_soal WHERE modul_id=$modul_id ORDER BY urutan")->fetch_all(MYSQLI_ASSOC);
    foreach ($moduls as $m) { if ($m['id'] == $modul_id) { $modul_aktif = $m; break; } }
}
$msg_map = ['added'=>'Soal berhasil ditambahkan.','updated'=>'Soal berhasil diperbarui.','deleted'=>'Soal berhasil dihapus.','uploaded'=>'Template berhasil diupload.'];
?>
<div class="main-content">
<div class="admin-page">
    <div class="admin-header">
        <div><h1>Kelola Quiz & Sertifikat</h1><p>Buat soal quiz dan upload template sertifikat per modul</p></div>
        <a href="panel.php" class="btn-primary" style="background:#666">&larr; Kembali ke Panel</a>
    </div>

    <?php if ($msg && isset($msg_map[$msg])): ?>
    <div class="alert alert-success" style="margin-bottom:16px"><?= $msg_map[$msg] ?></div>
    <?php endif; ?>

    <div class="kategori-layout">
        <!-- DAFTAR MODUL -->
        <div class="data-table" style="height:fit-content">
            <table>
                <thead><tr><th>Modul</th><th>Soal</th><th>Template</th></tr></thead>
                <tbody>
                <?php foreach ($moduls as $m):
                    $jml = $conn->query("SELECT COUNT(*) as c FROM quiz_soal WHERE modul_id={$m['id']}")->fetch_assoc()['c'];
                ?>
                <tr class="<?= $m['id']==$modul_id?'row-active':'' ?>">
                    <td><a href="quiz.php?modul=<?= $m['id'] ?>" style="color:#3f51b5;text-decoration:none"><?= htmlspecialchars($m['judul']) ?></a></td>
                    <td><?= $jml ?> soal</td>
                    <td><?= $m['sertifikat_template'] ? '<span class="badge-file">Template</span>' : '<span style="color:#ccc">-</span>' ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- PANEL KANAN -->
        <?php if ($modul_aktif): ?>
        <div>
            <div class="card-form" style="margin-bottom:20px">
                <h3>Template Sertifikat</h3>
                <p style="font-size:13px;color:#888;margin-bottom:12px">Upload gambar PNG/JPG sebagai background sertifikat.</p>
                <?php if ($modul_aktif['sertifikat_template']): ?>
                <img src="../<?= htmlspecialchars($modul_aktif['sertifikat_template']) ?>" style="width:100%;border-radius:8px;margin-bottom:12px">
                <?php endif; ?>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="aksi" value="upload_template">
                    <input type="hidden" name="modul_id" value="<?= $modul_id ?>">
                    <input type="file" name="template" accept="image/png,image/jpeg" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:8px;font-size:13px;margin-bottom:10px">
                    <button type="submit" class="btn-primary" style="width:100%;justify-content:center">Upload Template</button>
                </form>
            </div>

            <div class="card-form">
                <h3>Tambah Soal Quiz</h3>
                <div style="background:#e8eaf6;border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:13px;color:#3f51b5">
                    Modul: <strong style="color:#1a237e"><?= htmlspecialchars($modul_aktif['judul']) ?></strong>
                </div>
                <form method="POST">
                    <input type="hidden" name="aksi" value="tambah_soal">
                    <input type="hidden" name="modul_id" value="<?= $modul_id ?>">
                    <div class="form-group"><label>Pertanyaan</label>
                        <textarea name="pertanyaan" class="form-control" rows="3" required></textarea>
                    </div>
                    <?php foreach (['a','b','c','d'] as $op): ?>
                    <div class="form-group"><label>Opsi <?= strtoupper($op) ?></label>
                        <input type="text" name="opsi_<?= $op ?>" class="form-control" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px" <?= $op<='b'?'required':'' ?>>
                    </div>
                    <?php endforeach; ?>
                    <div class="form-group"><label>Jawaban Benar</label>
                        <select name="jawaban_benar" class="form-control">
                            <option value="a">A</option><option value="b">B</option>
                            <option value="c">C</option><option value="d">D</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Urutan <span style="font-weight:400;color:#888;font-size:12px">— nomor urut tampil ke peserta</span></label>
                        <input type="number" name="urutan" value="<?= count($soals)+1 ?>" class="form-control" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px">
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%;justify-content:center">+ Tambah Soal</button>
                </form>
            </div>
        </div>
        <?php else: ?>
        <div class="card-form" style="text-align:center;color:#aaa;padding:40px">
            <div style="font-size:40px"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
            <p style="margin-top:12px">Pilih modul di sebelah kiri</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- DAFTAR SOAL -->
    <?php if (!empty($soals)): ?>
    <div class="data-table" style="margin-top:24px">
        <div style="padding:16px;font-weight:600;font-size:15px;border-bottom:1px solid #eee">
            Daftar Soal — <?= htmlspecialchars($modul_aktif['judul']) ?>
            <span style="font-weight:400;font-size:13px;color:#888;margin-left:8px"><?= count($soals) ?> soal</span>
        </div>
        <table>
            <thead><tr><th>#</th><th>Pertanyaan</th><th>A</th><th>B</th><th>C</th><th>D</th><th>Jawaban</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($soals as $i => $s): ?>
            <tr>
                <td><?= $s['urutan'] ?></td>
                <td style="max-width:220px"><?= htmlspecialchars($s['pertanyaan']) ?></td>
                <td><?= htmlspecialchars($s['opsi_a']) ?></td>
                <td><?= htmlspecialchars($s['opsi_b']) ?></td>
                <td><?= htmlspecialchars($s['opsi_c'] ?? '-') ?></td>
                <td><?= htmlspecialchars($s['opsi_d'] ?? '-') ?></td>
                <td><span class="badge badge-internal"><?= strtoupper($s['jawaban_benar']) ?></span></td>
                <td>
                    <div class="action-btns">
        <button class="btn-icon view" title="Lihat" onclick="lihatSoal(<?= htmlspecialchars(json_encode($s)) ?>)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                        <button class="btn-icon edit" title="Edit" onclick="editSoal(<?= htmlspecialchars(json_encode($s)) ?>)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Hapus soal ini?')">
                            <input type="hidden" name="aksi" value="hapus_soal">
                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                            <input type="hidden" name="modul_id" value="<?= $modul_id ?>">
                            <button type="submit" class="btn-icon delete"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg></button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- MODAL LIHAT SOAL -->
<div class="modal-overlay" id="modalLihatSoal">
    <div class="modal">
        <h3>Detail Soal</h3>
        <div id="lihat-pertanyaan" style="font-size:15px;font-weight:600;margin-bottom:16px;line-height:1.6"></div>
        <div id="lihat-opsi" style="display:flex;flex-direction:column;gap:8px;margin-bottom:16px"></div>
        <div id="lihat-jawaban" style="font-size:13px;color:#2e7d32;font-weight:600"></div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="document.getElementById('modalLihatSoal').classList.remove('open')">Tutup</button>
        </div>
    </div>
</div>

<!-- MODAL EDIT SOAL -->
<div class="modal-overlay" id="modalEditSoal">
    <div class="modal">
        <h3>Edit Soal</h3>
        <form method="POST">
            <input type="hidden" name="aksi" value="edit_soal">
            <input type="hidden" name="id" id="es_id">
            <input type="hidden" name="modul_id" value="<?= $modul_id ?>">
            <div class="form-group"><label>Pertanyaan</label>
                <textarea name="pertanyaan" id="es_pertanyaan" class="form-control" rows="3" required></textarea>
            </div>
            <?php foreach (['a','b','c','d'] as $op): ?>
            <div class="form-group"><label>Opsi <?= strtoupper($op) ?></label>
                <input type="text" name="opsi_<?= $op ?>" id="es_opsi_<?= $op ?>" class="form-control" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px" <?= $op<='b'?'required':'' ?>>
            </div>
            <?php endforeach; ?>
            <div class="form-group"><label>Jawaban Benar</label>
                <select name="jawaban_benar" id="es_jawaban" class="form-control">
                    <option value="a">A</option><option value="b">B</option>
                    <option value="c">C</option><option value="d">D</option>
                </select>
            </div>
            <div class="form-group"><label>Urutan</label>
                <input type="number" name="urutan" id="es_urutan" class="form-control" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="document.getElementById('modalEditSoal').classList.remove('open')">Batal</button>
                <button type="submit" class="btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
function lihatSoal(s) {
    document.getElementById('lihat-pertanyaan').textContent = s.pertanyaan;
    const opsiEl = document.getElementById('lihat-opsi');
    opsiEl.innerHTML = '';
    const opsi = {a: s.opsi_a, b: s.opsi_b, c: s.opsi_c, d: s.opsi_d};
    for (const [k, v] of Object.entries(opsi)) {
        if (!v) continue;
        const isBenar = k === s.jawaban_benar;
        opsiEl.innerHTML += `<div style="padding:8px 14px;border-radius:8px;font-size:14px;background:${isBenar?'#e8f5e9':'#f5f5f5'};color:${isBenar?'#2e7d32':'#333'}">
            <strong>${k.toUpperCase()}.</strong> ${v} ${isBenar ? ' ✓' : ''}
        </div>`;
    }
    document.getElementById('lihat-jawaban').textContent = 'Jawaban benar: ' + s.jawaban_benar.toUpperCase();
    document.getElementById('modalLihatSoal').classList.add('open');
}
function editSoal(s) {
    document.getElementById('es_id').value = s.id;
    document.getElementById('es_pertanyaan').value = s.pertanyaan;
    document.getElementById('es_opsi_a').value = s.opsi_a || '';
    document.getElementById('es_opsi_b').value = s.opsi_b || '';
    document.getElementById('es_opsi_c').value = s.opsi_c || '';
    document.getElementById('es_opsi_d').value = s.opsi_d || '';
    document.getElementById('es_jawaban').value = s.jawaban_benar;
    document.getElementById('es_urutan').value = s.urutan;
    document.getElementById('modalEditSoal').classList.add('open');
}
document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', function(e) { if (e.target === this) this.classList.remove('open'); });
});
</script>
</div>
<?php require_once '../includes/footer.php'; ?>
