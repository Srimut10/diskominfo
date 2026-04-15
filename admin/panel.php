<?php
require_once '../includes/header.php';
if (!$is_admin) { header('Location: ../index.php'); exit; }

// Helper upload file
function handle_upload($field, $allowed, $dir_abs, $dir_web) {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return null;
    if (!is_dir($dir_abs)) mkdir($dir_abs, 0755, true);
    $fname = uniqid() . '_' . time() . '.' . $ext;
    if (move_uploaded_file($_FILES[$field]['tmp_name'], $dir_abs . $fname)) return $dir_web . $fname;
    return null;
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah_modul') {
        $judul = trim($_POST['judul']); $deskripsi = trim($_POST['deskripsi']);
        $kat_id = (int)$_POST['kategori_id']; $instruktur = trim($_POST['instruktur']);
        $lokasi = trim($_POST['lokasi']); $tanggal = $_POST['tanggal']; $status = $_POST['status'];
        $thumbnail = handle_upload('thumbnail', ['jpg','jpeg','png','webp'], '../uploads/thumbnails/', 'uploads/thumbnails/');
        $file_ppt  = handle_upload('file_ppt', ['pdf','ppt','pptx'], '../uploads/ppt/', 'uploads/ppt/');
        $stmt = $conn->prepare("INSERT INTO modul (judul,deskripsi,kategori_id,instruktur,lokasi,tanggal,status,thumbnail,file_ppt) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssissssss", $judul,$deskripsi,$kat_id,$instruktur,$lokasi,$tanggal,$status,$thumbnail,$file_ppt);
        $stmt->execute();
        header('Location: panel.php?tab=modul&msg=added'); exit;
    }

    if ($aksi === 'hapus_modul') {
        $id = (int)$_POST['id'];
        $row = $conn->query("SELECT thumbnail, file_ppt FROM modul WHERE id=$id")->fetch_assoc();
        if ($row['thumbnail'] && file_exists('../' . $row['thumbnail'])) @unlink('../' . $row['thumbnail']);
        if ($row['file_ppt'] && file_exists('../' . $row['file_ppt'])) @unlink('../' . $row['file_ppt']);
        $conn->query("DELETE FROM modul WHERE id=$id");
        header('Location: panel.php?tab=modul&msg=deleted'); exit;
    }

    if ($aksi === 'edit_modul') {
        $id = (int)$_POST['id'];
        $judul = trim($_POST['judul']); $deskripsi = trim($_POST['deskripsi']);
        $kat_id = (int)$_POST['kategori_id']; $instruktur = trim($_POST['instruktur']);
        $lokasi = trim($_POST['lokasi']); $tanggal = $_POST['tanggal']; $status = $_POST['status'];
        $row = $conn->query("SELECT thumbnail, file_ppt FROM modul WHERE id=$id")->fetch_assoc();
        $thumbnail = handle_upload('thumbnail', ['jpg','jpeg','png','webp'], '../uploads/thumbnails/', 'uploads/thumbnails/') ?? $row['thumbnail'];
        $file_ppt  = handle_upload('file_ppt', ['pdf','ppt','pptx'], '../uploads/ppt/', 'uploads/ppt/') ?? $row['file_ppt'];
        $stmt = $conn->prepare("UPDATE modul SET judul=?,deskripsi=?,kategori_id=?,instruktur=?,lokasi=?,tanggal=?,status=?,thumbnail=?,file_ppt=? WHERE id=?");
        $stmt->bind_param("ssissssssi", $judul,$deskripsi,$kat_id,$instruktur,$lokasi,$tanggal,$status,$thumbnail,$file_ppt,$id);
        $stmt->execute();
        header('Location: panel.php?tab=modul&msg=updated'); exit;
    }

    if ($aksi === 'tambah_kategori') {
        $nama = trim($_POST['nama']); $desk = trim($_POST['deskripsi']);
        $stmt = $conn->prepare("INSERT INTO kategori (nama,deskripsi) VALUES (?,?)");
        $stmt->bind_param("ss", $nama, $desk); $stmt->execute();
        header('Location: panel.php?tab=kategori&msg=added'); exit;
    }

    if ($aksi === 'hapus_kategori') {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM kategori WHERE id=$id");
        header('Location: panel.php?tab=kategori&msg=deleted'); exit;
    }

    if ($aksi === 'tambah_tema') {
        $judul = trim($_POST['judul']); $deskripsi = trim($_POST['deskripsi']);
        $instruktur = trim($_POST['instruktur']); $lokasi = trim($_POST['lokasi']);
        $tanggal = $_POST['tanggal']; $kuota = (int)$_POST['kuota']; $status = $_POST['status'];
        $thumbnail = handle_upload('thumbnail', ['jpg','jpeg','png','webp'], '../uploads/thumbnails/', 'uploads/thumbnails/');
        $stmt = $conn->prepare("INSERT INTO tema_pelatihan (judul,deskripsi,instruktur,lokasi,tanggal,kuota,status,thumbnail) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param("sssssiss", $judul,$deskripsi,$instruktur,$lokasi,$tanggal,$kuota,$status,$thumbnail);
        $stmt->execute();
        header('Location: panel.php?tab=tema&msg=added'); exit;
    }

    if ($aksi === 'hapus_tema') {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM tema_pelatihan WHERE id=$id");
        header('Location: panel.php?tab=tema&msg=deleted'); exit;
    }

    if ($aksi === 'edit_tema') {
        $id = (int)$_POST['id'];
        $judul = trim($_POST['judul']); $deskripsi = trim($_POST['deskripsi']);
        $instruktur = trim($_POST['instruktur']); $lokasi = trim($_POST['lokasi']);
        $tanggal = $_POST['tanggal']; $kuota = (int)$_POST['kuota']; $status = $_POST['status'];
        $row = $conn->query("SELECT thumbnail FROM tema_pelatihan WHERE id=$id")->fetch_assoc();
        $thumbnail = handle_upload('thumbnail', ['jpg','jpeg','png','webp'], '../uploads/thumbnails/', 'uploads/thumbnails/') ?? $row['thumbnail'];
        $stmt = $conn->prepare("UPDATE tema_pelatihan SET judul=?,deskripsi=?,instruktur=?,lokasi=?,tanggal=?,kuota=?,status=?,thumbnail=? WHERE id=?");
        $stmt->bind_param("sssssissi", $judul,$deskripsi,$instruktur,$lokasi,$tanggal,$kuota,$status,$thumbnail,$id);
        $stmt->execute();
        header('Location: panel.php?tab=tema&msg=updated'); exit;
    }

    if ($aksi === 'update_pendaftaran') {
        $id = (int)$_POST['id']; $status = $_POST['status'];
        $stmt = $conn->prepare("UPDATE pendaftaran SET status=? WHERE id=?");
        $stmt->bind_param("si", $status, $id); $stmt->execute();
        header('Location: panel.php?tab=pendaftaran&msg=updated'); exit;
    }
}

$active_tab = $_GET['tab'] ?? 'modul';
$msg = $_GET['msg'] ?? '';

$moduls = $conn->query("SELECT m.*, k.nama as kategori_nama FROM modul m LEFT JOIN kategori k ON m.kategori_id=k.id ORDER BY m.created_at DESC")->fetch_all(MYSQLI_ASSOC);
$kategori_list = $conn->query("SELECT * FROM kategori ORDER BY nama")->fetch_all(MYSQLI_ASSOC);
$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$temas = $conn->query("SELECT t.*, (SELECT COUNT(*) FROM pendaftaran p WHERE p.tema_id=t.id) as total_daftar FROM tema_pelatihan t ORDER BY t.tanggal DESC")->fetch_all(MYSQLI_ASSOC);
$pendaftarans = $conn->query("SELECT pd.*, tp.judul as tema_judul FROM pendaftaran pd JOIN tema_pelatihan tp ON pd.tema_id=tp.id ORDER BY pd.created_at DESC")->fetch_all(MYSQLI_ASSOC);

?>
<div class="main-content">
<div class="admin-page">
    <div class="admin-header">
        <div><h1>Panel Admin</h1><p>Kelola modul pelatihan dan kategori</p></div>
        <?php if ($active_tab === 'modul'): ?>
        <button class="btn-primary" onclick="document.getElementById('modalTambahModul').classList.add('open')">+ Modul Baru</button>
        <?php elseif ($active_tab === 'tema'): ?>
        <button class="btn-primary" onclick="document.getElementById('modalTambahTema').classList.add('open')">+ Tema Baru</button>
        <?php endif; ?>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-success" style="margin-bottom:16px">
        <?= ['added'=>'Data berhasil ditambahkan.','deleted'=>'Data berhasil dihapus.','updated'=>'Data berhasil diperbarui.'][$msg] ?? '' ?>
    </div>
    <?php endif; ?>

    <div class="tabs">
        <button class="tab-btn <?= $active_tab=='modul'?'active':'' ?>" onclick="switchTab('modul')">Modul Pelatihan</button>
        <button class="tab-btn <?= $active_tab=='tema'?'active':'' ?>" onclick="switchTab('tema')">Tema Pelatihan</button>
        <button class="tab-btn <?= $active_tab=='pendaftaran'?'active':'' ?>" onclick="switchTab('pendaftaran')">Pendaftaran</button>
        <button class="tab-btn <?= $active_tab=='kategori'?'active':'' ?>" onclick="switchTab('kategori')">Kategori</button>
        <button class="tab-btn <?= $active_tab=='pengguna'?'active':'' ?>" onclick="switchTab('pengguna')">Pengguna</button>
    </div>

    <!-- TAB MODUL -->
    <div id="tab-modul" class="tab-content <?= $active_tab=='modul'?'active':'' ?>">
        <div class="data-table">
            <table>
                <thead><tr><th>Judul</th><th>Kategori</th><th>Tanggal</th><th>PPT</th><th>Foto</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php foreach ($moduls as $m): ?>
                    <tr>
                        <td><?= htmlspecialchars($m['judul']) ?></td>
                        <td><span class="badge badge-internal"><?= htmlspecialchars($m['kategori_nama'] ?? '-') ?></span></td>
                        <td><?= date('d M Y', strtotime($m['tanggal'])) ?></td>
                        <td><?= $m['file_ppt'] ? '<span class="badge-file">&#128196; PPT</span>' : '<span style="color:#ccc">-</span>' ?></td>
                        <td><?= $m['thumbnail'] ? '<span class="badge-file">&#128247; Foto</span>' : '<span style="color:#ccc">-</span>' ?></td>
                        <td><span class="status-badge status-<?= $m['status'] ?>"><?= $m['status'] ?></span></td>
                        <td>
                            <div class="action-btns">
                                <a href="../pages/detail.php?id=<?= $m['id'] ?>" class="btn-icon view" title="Lihat">&#128065;</a>
                                <button class="btn-icon edit" title="Edit" onclick="openEdit(<?= htmlspecialchars(json_encode($m)) ?>)">&#9998;</button>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Hapus modul ini?')">
                                    <input type="hidden" name="aksi" value="hapus_modul">
                                    <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                    <button type="submit" class="btn-icon delete">&#128465;</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($moduls)): ?><tr><td colspan="7" style="text-align:center;color:#999;padding:32px">Belum ada modul.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB TEMA PELATIHAN -->
    <div id="tab-tema" class="tab-content <?= $active_tab=='tema'?'active':'' ?>">
        <div class="data-table">
            <table>
                <thead><tr><th>Judul</th><th>Tanggal</th><th>Kuota</th><th>Terdaftar</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php foreach ($temas as $t): ?>
                    <tr>
                        <td><?= htmlspecialchars($t['judul']) ?></td>
                        <td><?= date('d M Y', strtotime($t['tanggal'])) ?></td>
                        <td><?= $t['kuota'] ?></td>
                        <td><?= $t['total_daftar'] ?></td>
                        <td><span class="status-badge <?= $t['status']==='open'?'status-published':'status-draft' ?>"><?= $t['status'] ?></span></td>
                        <td>
                            <div class="action-btns">
                                <button class="btn-icon edit" title="Edit" onclick="openEditTema(<?= htmlspecialchars(json_encode($t)) ?>)">&#9998;</button>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Hapus tema ini?')">
                                    <input type="hidden" name="aksi" value="hapus_tema">
                                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                    <button type="submit" class="btn-icon delete">&#128465;</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($temas)): ?><tr><td colspan="6" style="text-align:center;color:#999;padding:32px">Belum ada tema.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB PENDAFTARAN -->
    <div id="tab-pendaftaran" class="tab-content <?= $active_tab=='pendaftaran'?'active':'' ?>">
        <div class="data-table">
            <table>
                <thead><tr><th>Nama</th><th>Email</th><th>Tema</th><th>Instansi</th><th>No. HP</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php foreach ($pendaftarans as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['nama_lengkap']) ?></td>
                        <td><?= htmlspecialchars($p['email']) ?></td>
                        <td><?= htmlspecialchars($p['tema_judul']) ?></td>
                        <td><?= htmlspecialchars($p['instansi'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($p['no_hp'] ?? '-') ?></td>
                        <td>
                            <span class="status-badge status-<?= $p['status']==='approved'?'published':($p['status']==='rejected'?'draft':'pending') ?>">
                                <?= $p['status'] ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="aksi" value="update_pendaftaran">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <select name="status" onchange="this.form.submit()" class="select-status">
                                    <option value="pending" <?= $p['status']==='pending'?'selected':'' ?>>Pending</option>
                                    <option value="approved" <?= $p['status']==='approved'?'selected':'' ?>>Approved</option>
                                    <option value="rejected" <?= $p['status']==='rejected'?'selected':'' ?>>Rejected</option>
                                </select>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($pendaftarans)): ?><tr><td colspan="7" style="text-align:center;color:#999;padding:32px">Belum ada pendaftaran.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB KATEGORI -->
    <div id="tab-kategori" class="tab-content <?= $active_tab=='kategori'?'active':'' ?>">
        <div class="kategori-layout">
            <div class="data-table">
                <table>
                    <thead><tr><th>Nama</th><th>Deskripsi</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php foreach ($kategori_list as $k): ?>
                        <tr>
                            <td><?= htmlspecialchars($k['nama']) ?></td>
                            <td><?= htmlspecialchars($k['deskripsi']) ?></td>
                            <td>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Hapus kategori ini?')">
                                    <input type="hidden" name="aksi" value="hapus_kategori">
                                    <input type="hidden" name="id" value="<?= $k['id'] ?>">
                                    <button type="submit" class="btn-icon delete">&#128465;</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($kategori_list)): ?><tr><td colspan="3" style="text-align:center;color:#999;padding:32px">Belum ada kategori.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-form">
                <h3>Tambah Kategori</h3>
                <form method="POST">
                    <input type="hidden" name="aksi" value="tambah_kategori">
                    <div class="form-group"><label>Nama Kategori</label>
                        <input type="text" name="nama" class="form-control" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px" required>
                    </div>
                    <div class="form-group"><label>Deskripsi</label>
                        <textarea name="deskripsi" class="form-control"></textarea>
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%;justify-content:center">+ Tambah</button>
                </form>
            </div>
        </div>
    </div>

    <!-- TAB PENGGUNA -->
    <div id="tab-pengguna" class="tab-content <?= $active_tab=='pengguna'?'active':'' ?>">
        <div class="data-table pengguna-table">
            <table>
                <thead><tr><th>Nama</th><th>Email</th><th>Role</th><th>Bergabung</th></tr></thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['nama_lengkap']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><span class="role-badge role-<?= $u['role'] ?>"><?= $u['role'] ?></span></td>
                        <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL TAMBAH MODUL -->
<div class="modal-overlay" id="modalTambahModul">
    <div class="modal">
        <h3>Tambah Modul Baru</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="aksi" value="tambah_modul">
            <div class="form-group"><label>Judul</label>
                <input type="text" name="judul" class="form-control" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px" required>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Kategori</label>
                    <select name="kategori_id" class="form-control">
                        <?php foreach ($kategori_list as $k): ?>
                        <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Status</label>
                    <select name="status" class="form-control">
                        <option value="published">Published</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Instruktur</label>
                    <input type="text" name="instruktur" class="form-control" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px">
                </div>
                <div class="form-group"><label>Tanggal</label>
                    <input type="date" name="tanggal" class="form-control" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px">
                </div>
            </div>
            <div class="form-group"><label>Lokasi</label>
                <input type="text" name="lokasi" class="form-control" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px">
            </div>
            <div class="form-group"><label>Deskripsi</label>
                <textarea name="deskripsi" class="form-control"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Upload Foto / Thumbnail</label>
                    <input type="file" name="thumbnail" accept="image/*" class="form-control" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:8px;font-size:13px">
                    <small style="color:#888">JPG, PNG, WEBP</small>
                </div>
                <div class="form-group">
                    <label>Upload File Materi (PDF)</label>
                    <input type="file" name="file_ppt" accept=".pdf,.ppt,.pptx" class="form-control" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:8px;font-size:13px">
                    <small style="color:#888">PDF direkomendasikan, PPT/PPTX (maks 20MB)</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="document.getElementById('modalTambahModul').classList.remove('open')">Batal</button>
                <button type="submit" class="btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT MODUL -->
<div class="modal-overlay" id="modalEditModul">
    <div class="modal">
        <h3>Edit Modul</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="aksi" value="edit_modul">
            <input type="hidden" name="id" id="edit_id">
            <div class="form-group"><label>Judul</label>
                <input type="text" name="judul" id="edit_judul" class="form-control" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px" required>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Kategori</label>
                    <select name="kategori_id" id="edit_kategori" class="form-control">
                        <?php foreach ($kategori_list as $k): ?>
                        <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Status</label>
                    <select name="status" id="edit_status" class="form-control">
                        <option value="published">Published</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Instruktur</label>
                    <input type="text" name="instruktur" id="edit_instruktur" class="form-control" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px">
                </div>
                <div class="form-group"><label>Tanggal</label>
                    <input type="date" name="tanggal" id="edit_tanggal" class="form-control" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px">
                </div>
            </div>
            <div class="form-group"><label>Lokasi</label>
                <input type="text" name="lokasi" id="edit_lokasi" class="form-control" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px">
            </div>
            <div class="form-group"><label>Deskripsi</label>
                <textarea name="deskripsi" id="edit_deskripsi" class="form-control"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Ganti Foto / Thumbnail</label>
                    <div id="edit_thumb_preview" style="margin-bottom:6px"></div>
                    <input type="file" name="thumbnail" accept="image/*" class="form-control" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:8px;font-size:13px">
                    <small style="color:#888">Kosongkan jika tidak ingin mengganti</small>
                </div>
                <div class="form-group">
                    <label>Ganti File Materi (PDF)</label>
                    <div id="edit_ppt_info" style="margin-bottom:6px;font-size:12px;color:#3f51b5"></div>
                    <input type="file" name="file_ppt" accept=".pdf,.ppt,.pptx" class="form-control" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:8px;font-size:13px">
                    <small style="color:#888">Kosongkan jika tidak ingin mengganti</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="document.getElementById('modalEditModul').classList.remove('open')">Batal</button>
                <button type="submit" class="btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL TAMBAH TEMA -->
<div class="modal-overlay" id="modalTambahTema">
    <div class="modal">
        <h3>Tambah Tema Pelatihan</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="aksi" value="tambah_tema">
            <div class="form-group"><label>Judul Tema</label>
                <input type="text" name="judul" class="form-control" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px" required>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Instruktur</label>
                    <input type="text" name="instruktur" class="form-control" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px">
                </div>
                <div class="form-group"><label>Tanggal</label>
                    <input type="date" name="tanggal" class="form-control" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Lokasi</label>
                    <input type="text" name="lokasi" class="form-control" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px">
                </div>
                <div class="form-group"><label>Kuota Peserta</label>
                    <input type="number" name="kuota" min="1" value="30" class="form-control" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Status</label>
                    <select name="status" class="form-control">
                        <option value="open">Open</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <div class="form-group"><label>Foto Tema</label>
                    <input type="file" name="thumbnail" accept="image/*" class="form-control" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:8px;font-size:13px">
                </div>
            </div>
            <div class="form-group"><label>Deskripsi</label>
                <textarea name="deskripsi" class="form-control"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="document.getElementById('modalTambahTema').classList.remove('open')">Batal</button>
                <button type="submit" class="btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT TEMA -->
<div class="modal-overlay" id="modalEditTema">
    <div class="modal">
        <h3>Edit Tema Pelatihan</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="aksi" value="edit_tema">
            <input type="hidden" name="id" id="etema_id">
            <div class="form-group"><label>Judul Tema</label>
                <input type="text" name="judul" id="etema_judul" class="form-control" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px" required>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Instruktur</label>
                    <input type="text" name="instruktur" id="etema_instruktur" class="form-control" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px">
                </div>
                <div class="form-group"><label>Tanggal</label>
                    <input type="date" name="tanggal" id="etema_tanggal" class="form-control" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Lokasi</label>
                    <input type="text" name="lokasi" id="etema_lokasi" class="form-control" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px">
                </div>
                <div class="form-group"><label>Kuota Peserta</label>
                    <input type="number" name="kuota" id="etema_kuota" min="1" class="form-control" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Status</label>
                    <select name="status" id="etema_status" class="form-control">
                        <option value="open">Open</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <div class="form-group"><label>Ganti Foto</label>
                    <div id="etema_thumb_preview" style="margin-bottom:6px"></div>
                    <input type="file" name="thumbnail" accept="image/*" class="form-control" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:8px;font-size:13px">
                    <small style="color:#888">Kosongkan jika tidak ingin mengganti</small>
                </div>
            </div>
            <div class="form-group"><label>Deskripsi</label>
                <textarea name="deskripsi" id="etema_deskripsi" class="form-control"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="document.getElementById('modalEditTema').classList.remove('open')">Batal</button>
                <button type="submit" class="btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    event.target.classList.add('active');
    history.replaceState(null, '', '?tab=' + tab);
}
function openEdit(data) {
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_judul').value = data.judul;
    document.getElementById('edit_instruktur').value = data.instruktur || '';
    document.getElementById('edit_lokasi').value = data.lokasi || '';
    document.getElementById('edit_tanggal').value = data.tanggal || '';
    document.getElementById('edit_deskripsi').value = data.deskripsi || '';
    document.getElementById('edit_kategori').value = data.kategori_id || '';
    document.getElementById('edit_status').value = data.status || 'draft';
    const thumbEl = document.getElementById('edit_thumb_preview');
    thumbEl.innerHTML = data.thumbnail ? '<img src="../' + data.thumbnail + '" style="height:50px;border-radius:4px">' : '';
    const pptEl = document.getElementById('edit_ppt_info');
    pptEl.textContent = data.file_ppt ? '&#128196; File PPT sudah ada' : '';
    document.getElementById('modalEditModul').classList.add('open');
}
function openEditTema(data) {
    document.getElementById('etema_id').value = data.id;
    document.getElementById('etema_judul').value = data.judul;
    document.getElementById('etema_instruktur').value = data.instruktur || '';
    document.getElementById('etema_lokasi').value = data.lokasi || '';
    document.getElementById('etema_tanggal').value = data.tanggal || '';
    document.getElementById('etema_kuota').value = data.kuota || 30;
    document.getElementById('etema_deskripsi').value = data.deskripsi || '';
    document.getElementById('etema_status').value = data.status || 'open';
    const prev = document.getElementById('etema_thumb_preview');
    prev.innerHTML = data.thumbnail ? '<img src="../' + data.thumbnail + '" style="height:50px;border-radius:4px">' : '';
    document.getElementById('modalEditTema').classList.add('open');
}

    o.addEventListener('click', function(e) { if (e.target === this) this.classList.remove('open'); });
});
</script>

<?php require_once '../includes/footer.php'; ?>
