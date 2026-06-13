<?php
require_once '../includes/header.php';
if (!$is_admin) { header('Location: ../index.php'); exit; }
// Disable the default .main-content wrapper used elsewhere
$in_admin_panel = true;

// Helper upload file dengan pemrosesan kualitas gambar
require_once '../config/image.php';

function handle_upload($field, $allowed, $dir_abs, $dir_web) {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return null;
    if (!is_dir($dir_abs)) mkdir($dir_abs, 0755, true);
    $fname = uniqid() . '_' . time() . '.' . $ext;
    $dest  = $dir_abs . $fname;

    // Cek apakah file adalah gambar
    $img_exts = ['jpg','jpeg','png','webp','jfif','gif'];
    if (in_array($ext, $img_exts)) {
        // Proses dengan GD: simpan kualitas tinggi, resize jika > 1920px
        $ok = process_image($_FILES[$field]['tmp_name'], $dest, 1920, 1080, 92);
    } else {
        $ok = move_uploaded_file($_FILES[$field]['tmp_name'], $dest);
    }

    return $ok ? $dir_web . $fname : null;
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'save_form_settings') {
        $tid = (int)$_POST['tema_id'];
        $fields = ['req_nik','req_npwp','req_rekening','req_nama_bank','req_kecamatan','req_doc_ktp','req_doc_npwp','req_doc_rekening'];
        $vals = [];
        foreach ($fields as $f) $vals[$f] = isset($_POST[$f]) ? 1 : 0;
        $conn->query("INSERT INTO tema_form_settings (tema_id,req_nik,req_npwp,req_rekening,req_nama_bank,req_kecamatan,req_doc_ktp,req_doc_npwp,req_doc_rekening)
            VALUES ($tid,{$vals['req_nik']},{$vals['req_npwp']},{$vals['req_rekening']},{$vals['req_nama_bank']},{$vals['req_kecamatan']},{$vals['req_doc_ktp']},{$vals['req_doc_npwp']},{$vals['req_doc_rekening']})
            ON DUPLICATE KEY UPDATE req_nik={$vals['req_nik']},req_npwp={$vals['req_npwp']},req_rekening={$vals['req_rekening']},req_nama_bank={$vals['req_nama_bank']},req_kecamatan={$vals['req_kecamatan']},req_doc_ktp={$vals['req_doc_ktp']},req_doc_npwp={$vals['req_doc_npwp']},req_doc_rekening={$vals['req_doc_rekening']}");
        header('Location: panel.php?tab=tema&msg=updated'); exit;
    }

    if ($aksi === 'tambah_modul') {
        $judul = trim($_POST['judul']); $deskripsi = trim($_POST['deskripsi']);
        $kat_id = (int)$_POST['kategori_id']; $instruktur = trim($_POST['instruktur']);
        $lokasi = trim($_POST['lokasi']); $tanggal = $_POST['tanggal']; $status = $_POST['status'];
        $tema_id = !empty($_POST['tema_id']) ? (int)$_POST['tema_id'] : null;
        $thumbnail = handle_upload('thumbnail', ['jpg','jpeg','png','webp','jfif'], '../uploads/thumbnails/', 'uploads/thumbnails/');
        $file_ppt  = handle_upload('file_ppt', ['pdf','ppt','pptx'], '../uploads/ppt/', 'uploads/ppt/');
        $stmt = $conn->prepare("INSERT INTO modul (judul,deskripsi,kategori_id,tema_id,instruktur,lokasi,tanggal,status,thumbnail,file_ppt) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssiissssss", $judul,$deskripsi,$kat_id,$tema_id,$instruktur,$lokasi,$tanggal,$status,$thumbnail,$file_ppt);
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
        $tema_id = !empty($_POST['tema_id']) ? (int)$_POST['tema_id'] : null;
        $row = $conn->query("SELECT thumbnail, file_ppt FROM modul WHERE id=$id")->fetch_assoc();
        $thumbnail = handle_upload('thumbnail', ['jpg','jpeg','png','webp','jfif'], '../uploads/thumbnails/', 'uploads/thumbnails/') ?? $row['thumbnail'];
        $file_ppt  = handle_upload('file_ppt', ['pdf','ppt','pptx'], '../uploads/ppt/', 'uploads/ppt/') ?? $row['file_ppt'];
        $stmt = $conn->prepare("UPDATE modul SET judul=?,deskripsi=?,kategori_id=?,tema_id=?,instruktur=?,lokasi=?,tanggal=?,status=?,thumbnail=?,file_ppt=? WHERE id=?");
        $stmt->bind_param("ssiissssssi", $judul,$deskripsi,$kat_id,$tema_id,$instruktur,$lokasi,$tanggal,$status,$thumbnail,$file_ppt,$id);
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
        $lokasi = trim($_POST['lokasi']);
        $tanggal = $_POST['tanggal'];
        $tanggal_selesai = !empty($_POST['tanggal_selesai']) ? $_POST['tanggal_selesai'] : null;
        $kuota = (int)$_POST['kuota']; $status = $_POST['status'];
        $thumbnail = handle_upload('thumbnail', ['jpg','jpeg','png','webp','jfif'], '../uploads/thumbnails/', 'uploads/thumbnails/');
        $conn->query("ALTER TABLE tema_pelatihan ADD COLUMN IF NOT EXISTS tanggal_selesai DATE DEFAULT NULL AFTER tanggal");
        $stmt = $conn->prepare("INSERT INTO tema_pelatihan (judul,deskripsi,lokasi,tanggal,tanggal_selesai,kuota,status,thumbnail) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param("sssssiss", $judul,$deskripsi,$lokasi,$tanggal,$tanggal_selesai,$kuota,$status,$thumbnail);
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
        $lokasi = trim($_POST['lokasi']);
        $tanggal = $_POST['tanggal'];
        $tanggal_selesai = !empty($_POST['tanggal_selesai']) ? $_POST['tanggal_selesai'] : null;
        $kuota = (int)$_POST['kuota']; $status = $_POST['status'];
        $row = $conn->query("SELECT thumbnail FROM tema_pelatihan WHERE id=$id")->fetch_assoc();
        $thumbnail = handle_upload('thumbnail', ['jpg','jpeg','png','webp','jfif'], '../uploads/thumbnails/', 'uploads/thumbnails/') ?? $row['thumbnail'];
        $stmt = $conn->prepare("UPDATE tema_pelatihan SET judul=?,deskripsi=?,lokasi=?,tanggal=?,tanggal_selesai=?,kuota=?,status=?,thumbnail=? WHERE id=?");
        $stmt->bind_param("sssssissi", $judul,$deskripsi,$lokasi,$tanggal,$tanggal_selesai,$kuota,$status,$thumbnail,$id);
        $stmt->execute();
        header('Location: panel.php?tab=tema&msg=updated'); exit;
    }

    if ($aksi === 'update_pendaftaran') {
        $id = (int)$_POST['id']; $status = $_POST['status'];
        $stmt = $conn->prepare("UPDATE pendaftaran SET status=? WHERE id=?");
        $stmt->bind_param("si", $status, $id); $stmt->execute();
        header('Location: panel.php?tab=pendaftaran&msg=updated'); exit;
    }

    if ($aksi === 'update_hero') {
        $judul    = trim($_POST['hero_judul']);
        $subjudul = trim($_POST['hero_subjudul']);
        $conn->query("CREATE TABLE IF NOT EXISTS hero_settings (id INT AUTO_INCREMENT PRIMARY KEY, hero_image VARCHAR(255), hero_judul VARCHAR(200), hero_subjudul VARCHAR(300), updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB");
        $img = handle_upload('hero_image', ['jpg','jpeg','png','webp','jfif'], '../uploads/hero/', 'uploads/hero/');
        $res_h = $conn->query("SELECT id, hero_image FROM hero_settings LIMIT 1");
        $existing_hero = $res_h->fetch_assoc();
        if ($existing_hero) {
            if ($img) {
                if ($existing_hero['hero_image'] && file_exists('../'.$existing_hero['hero_image'])) @unlink('../'.$existing_hero['hero_image']);
                $conn->query("UPDATE hero_settings SET hero_judul='".mysqli_real_escape_string($conn,$judul)."', hero_subjudul='".mysqli_real_escape_string($conn,$subjudul)."', hero_image='".mysqli_real_escape_string($conn,$img)."' WHERE id={$existing_hero['id']}");
            } else {
                $conn->query("UPDATE hero_settings SET hero_judul='".mysqli_real_escape_string($conn,$judul)."', hero_subjudul='".mysqli_real_escape_string($conn,$subjudul)."' WHERE id={$existing_hero['id']}");
            }
        } else {
            $conn->query("INSERT INTO hero_settings (hero_judul,hero_subjudul,hero_image) VALUES ('".mysqli_real_escape_string($conn,$judul)."','".mysqli_real_escape_string($conn,$subjudul)."','".($img?mysqli_real_escape_string($conn,$img):'')."')");
        }
        header('Location: panel.php?tab=beranda&msg=updated'); exit;
    }

    if ($aksi === 'tambah_konten') {
        $judul = trim($_POST['judul']);
        $desk  = trim($_POST['deskripsi']);
        $tipe  = $_POST['tipe'];
        $tgl   = $_POST['tanggal'];
        $urut  = (int)$_POST['urutan'];
        $stat  = $_POST['status'];
        $gambar = handle_upload('gambar', ['jpg','jpeg','png','webp','jfif'], '../uploads/konten/', 'uploads/konten/');
        $stmt = $conn->prepare("INSERT INTO konten_beranda (judul,deskripsi,tipe,tanggal,urutan,status,gambar) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssiis", $judul,$desk,$tipe,$tgl,$urut,$stat,$gambar);
        $stmt->execute();
        header('Location: panel.php?tab=beranda&msg=added'); exit;
    }

    if ($aksi === 'edit_konten') {
        $id    = (int)$_POST['id'];
        $judul = trim($_POST['judul']);
        $desk  = trim($_POST['deskripsi']);
        $tipe  = $_POST['tipe'];
        $tgl   = $_POST['tanggal'];
        $urut  = (int)$_POST['urutan'];
        $stat  = $_POST['status'];
        $row   = $conn->query("SELECT gambar FROM konten_beranda WHERE id=$id")->fetch_assoc();
        $gambar = handle_upload('gambar', ['jpg','jpeg','png','webp','jfif'], '../uploads/konten/', 'uploads/konten/') ?? $row['gambar'];
        $stmt = $conn->prepare("UPDATE konten_beranda SET judul=?,deskripsi=?,tipe=?,tanggal=?,urutan=?,status=?,gambar=? WHERE id=?");
        $stmt->bind_param("ssssissi", $judul,$desk,$tipe,$tgl,$urut,$stat,$gambar,$id);
        $stmt->execute();
        header('Location: panel.php?tab=beranda&msg=updated'); exit;
    }

    if ($aksi === 'hapus_konten') {
        $id = (int)$_POST['id'];
        $row = $conn->query("SELECT gambar FROM konten_beranda WHERE id=$id")->fetch_assoc();
        if ($row && $row['gambar'] && file_exists('../'.$row['gambar'])) @unlink('../'.$row['gambar']);
        $conn->query("DELETE FROM konten_beranda WHERE id=$id");
        header('Location: panel.php?tab=beranda&msg=deleted'); exit;
    }

    if ($aksi === 'tambah_user') {
        $nama  = trim($_POST['nama_lengkap']);
        $email = trim($_POST['email']);
        $pass  = $_POST['password'];
        $role  = $_POST['role'];
        if ($nama && $email && $pass) {
            $check = $conn->prepare("SELECT id FROM users WHERE email=?");
            $check->bind_param("s", $email); $check->execute();
            if ($check->get_result()->num_rows > 0) {
                header('Location: panel.php?tab=pengguna&msg=email_exists'); exit;
            }
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (nama_lengkap,email,password,role) VALUES (?,?,?,?)");
            $stmt->bind_param("ssss", $nama, $email, $hash, $role);
            $stmt->execute();
        }
        header('Location: panel.php?tab=pengguna&msg=added'); exit;
    }

    if ($aksi === 'hapus_user') {
        $id = (int)$_POST['id'];
        if ($id !== $_SESSION['user_id']) { // jangan hapus diri sendiri
            $conn->query("DELETE FROM users WHERE id=$id");
        }
        header('Location: panel.php?tab=pengguna&msg=deleted'); exit;
    }
}

$active_tab = $_GET['tab'] ?? 'modul';
$msg = $_GET['msg'] ?? '';

// Handle hapus foto hero
if (isset($_GET['hapus_hero']) && $_GET['hapus_hero'] == '1' && $is_admin) {
    $h = $conn->query("SELECT hero_image FROM hero_settings LIMIT 1")->fetch_assoc();
    if ($h && $h['hero_image'] && file_exists('../'.$h['hero_image'])) {
        @unlink('../'.$h['hero_image']);
    }
    $conn->query("UPDATE hero_settings SET hero_image=NULL");
    header('Location: panel.php?tab=beranda&msg=updated'); exit;
}

// Notifikasi belum dibaca
$conn->query("CREATE TABLE IF NOT EXISTS notifikasi_admin (
    id INT AUTO_INCREMENT PRIMARY KEY, judul VARCHAR(200) NOT NULL, pesan TEXT,
    tipe ENUM('info','warning','danger') DEFAULT 'info', is_read TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
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
    req_doc_rekening TINYINT DEFAULT 0,
    FOREIGN KEY (tema_id) REFERENCES tema_pelatihan(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$notif_unread = $conn->query("SELECT COUNT(*) as c FROM notifikasi_admin WHERE is_read=0")->fetch_assoc()['c'];
$notifs = $conn->query("SELECT * FROM notifikasi_admin ORDER BY created_at DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);

// Tandai semua notif dibaca jika buka tab notifikasi
if ($active_tab === 'notifikasi') {
    $conn->query("UPDATE notifikasi_admin SET is_read=1 WHERE is_read=0");
    $notif_unread = 0;
}

$moduls = $conn->query("SELECT m.*, k.nama as kategori_nama FROM modul m LEFT JOIN kategori k ON m.kategori_id=k.id ORDER BY m.created_at DESC")->fetch_all(MYSQLI_ASSOC);
$kategori_list = $conn->query("SELECT * FROM kategori ORDER BY nama")->fetch_all(MYSQLI_ASSOC);
$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$temas = $conn->query("SELECT t.*, (SELECT COUNT(*) FROM pendaftaran p WHERE p.tema_id=t.id) as total_daftar FROM tema_pelatihan t ORDER BY t.tanggal DESC")->fetch_all(MYSQLI_ASSOC);
$pendaftarans = $conn->query("SELECT pd.*, tp.judul as tema_judul, tp.id as tema_id FROM pendaftaran pd JOIN tema_pelatihan tp ON pd.tema_id=tp.id ORDER BY tp.judul ASC, TRIM(LOWER(pd.nama_lengkap)) ASC")->fetch_all(MYSQLI_ASSOC);

// Ambil form settings per tema
$form_settings_all = [];
$fs_all = $conn->query("SELECT * FROM tema_form_settings");
if ($fs_all) {
    while ($fs_row = $fs_all->fetch_assoc()) {
        $form_settings_all[$fs_row['tema_id']] = $fs_row;
    }
}

?>
<style>
/* Extra admin-panel-specific tweaks */
body { background: #f0f2f8; }
.navbar { position: sticky; top: 0; z-index: 100; }
.admin-wrapper { display: flex; min-height: calc(100vh - 72px); }
</style>

<!-- Stat counts query -->
<?php
$total_modul      = count($moduls);
$total_tema       = count($temas);
$total_pendaftar  = count($pendaftarans);
$total_users      = count($users);
?>

<div class="admin-wrapper">

<!-- ===== SIDEBAR ===== -->
<aside class="admin-sidebar">
    <div class="admin-sidebar-brand">
        <span>Panel Kontrol</span>
        <h2>Admin Dashboard</h2>
    </div>
    <ul class="admin-nav">
        <li class="admin-nav-label">Konten</li>
        <li>
            <a href="?tab=modul" class="<?= $active_tab=='modul'?'active':'' ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                Modul Pelatihan
            </a>
        </li>
        <li>
            <a href="?tab=tema" class="<?= $active_tab=='tema'?'active':'' ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                Tema Pelatihan
            </a>
        </li>
        <li>
            <a href="?tab=beranda" class="<?= $active_tab=='beranda'?'active':'' ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                Kelola Beranda
            </a>
        </li>
        <div class="admin-nav-divider"></div>
        <li class="admin-nav-label">Peserta</li>
        <li>
            <a href="?tab=pendaftaran" class="<?= $active_tab=='pendaftaran'?'active':'' ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Pendaftaran
            </a>
        </li>
        <li>
            <a href="absensi.php" >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                Absensi
            </a>
        </li>
        <li>
            <a href="scan-qr.php">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h3v3h-3zM17 17h3v3h-3zM14 20h3"/></svg>
                Scan QR
            </a>
        </li>
        <div class="admin-nav-divider"></div>
        <li class="admin-nav-label">Sistem</li>
        <li>
            <a href="quiz.php">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                Kelola Quiz
            </a>
        </li>
        <li>
            <a href="?tab=pengguna" class="<?= $active_tab=='pengguna'?'active':'' ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Pengguna
            </a>
        </li>
    </ul>
    <div class="admin-sidebar-footer">
        <a href="../index.php">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
            Lihat Situs
        </a>
        <a href="../auth/logout.php">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Keluar
        </a>
    </div>
</aside>

<!-- ===== MAIN CONTENT ===== -->
<main class="admin-main">

    <!-- Top bar -->
    <div class="admin-topbar">
        <div class="admin-topbar-left">
            <?php
            $tab_titles = [
                'modul'       => ['Modul Pelatihan', 'Kelola semua modul dan materi'],
                'tema'        => ['Tema Pelatihan', 'Kelola tema dan sesi pelatihan'],
                'pendaftaran' => ['Pendaftaran', 'Data peserta yang mendaftar'],
                'beranda'     => ['Kelola Beranda', 'Hero, pemberitaan & agenda'],
                'pengguna'    => ['Pengguna', 'Kelola akun pengguna sistem'],
            ];
            $t = $tab_titles[$active_tab] ?? ['Panel Admin', 'Kelola platform pelatihan'];
            ?>
            <h1><?= $t[0] ?></h1>
            <p><?= $t[1] ?></p>
        </div>
        <div class="admin-topbar-right">
            <?php if ($active_tab === 'modul'): ?>
                <a href="quiz.php" class="btn-primary" style="background:#fff;color:#3f51b5;border:2px solid #3f51b5;box-shadow:none;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    Kelola Quiz
                </a>
                <button class="btn-primary" onclick="document.getElementById('modalTambahModul').classList.add('open')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Modul Baru
                </button>
            <?php elseif ($active_tab === 'tema'): ?>
                <button class="btn-primary" onclick="document.getElementById('modalTambahTema').classList.add('open')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Tema Baru
                </button>
            <?php elseif ($active_tab === 'pendaftaran'): ?>
                <a href="scan-qr.php" class="btn-primary" style="background:#2e7d32;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    Scan QR
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Alert -->
    <?php if ($msg): ?>
    <div class="alert alert-success" style="margin-bottom:20px">
        <?= ['added'=>'✓ Data berhasil ditambahkan.','deleted'=>'✓ Data berhasil dihapus.','updated'=>'✓ Data berhasil diperbarui.'][$msg] ?? '' ?>
    </div>
    <?php endif; ?>

    <!-- Stat Cards (shown only on overview tabs) -->
    <?php if (in_array($active_tab, ['modul','tema','pendaftaran'])): ?>
    <div class="stat-cards">
        <div class="stat-card">
            <div class="stat-icon blue">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
            </div>
            <div>
                <div class="stat-num"><?= $total_modul ?></div>
                <div class="stat-label">Total Modul</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
            </div>
            <div>
                <div class="stat-num"><?= $total_tema ?></div>
                <div class="stat-label">Tema Pelatihan</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon teal">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <div>
                <div class="stat-num"><?= $total_users ?></div>
                <div class="stat-label">Pengguna</div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Panel Card -->
    <div class="panel-card">
        <!-- Tab Navigation -->
        <div class="panel-card-header">
            <nav class="admin-tabs">
                <a href="?tab=modul" class="admin-tab-btn <?= $active_tab=='modul'?'active':'' ?>">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/></svg>
                    Modul
                    <span class="admin-tab-badge"><?= $total_modul ?></span>
                </a>
                <a href="?tab=tema" class="admin-tab-btn <?= $active_tab=='tema'?'active':'' ?>">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                    Tema
                    <span class="admin-tab-badge"><?= $total_tema ?></span>
                </a>
                <a href="?tab=pendaftaran" class="admin-tab-btn <?= $active_tab=='pendaftaran'?'active':'' ?>">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    Pendaftaran
                    <span class="admin-tab-badge"><?= $total_pendaftar ?></span>
                </a>
                <a href="?tab=beranda" class="admin-tab-btn <?= $active_tab=='beranda'?'active':'' ?>">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                    Beranda
                </a>
                <a href="?tab=pengguna" class="admin-tab-btn <?= $active_tab=='pengguna'?'active':'' ?>">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Pengguna
                </a>
            </nav>
        </div>
        <div class="panel-card-body">

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
                        <td><?= $m['file_ppt'] ? '<span class="badge-file">PPT</span>' : '<span style="color:#ccc">-</span>' ?></td>
                        <td><?= $m['thumbnail'] ? '<span class="badge-file">Foto</span>' : '<span style="color:#ccc">-</span>' ?></td>
                        <td><span class="status-badge status-<?= $m['status'] ?>"><?= $m['status'] ?></span></td>
                        <td>
                            <div class="action-btns">
                                <a href="../pages/detail.php?id=<?= $m['id'] ?>" class="btn-icon view" title="Lihat"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></a>
                                <button class="btn-icon edit" title="Edit" onclick="openEdit(<?= htmlspecialchars(json_encode($m)) ?>)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Hapus modul ini?')">
                                    <input type="hidden" name="aksi" value="hapus_modul">
                                    <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                    <button type="submit" class="btn-icon delete"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg></button>
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
                        <td><?php
                            $tm = date('d/m/Y', strtotime($t['tanggal']));
                            $ts = !empty($t['tanggal_selesai']) ? ' - '.date('d/m/Y', strtotime($t['tanggal_selesai'])) : '';
                            echo $tm.$ts;
                        ?></td>
                        <td><?= $t['kuota'] ?></td>
                        <td><?= $t['total_daftar'] ?></td>
                        <td><span class="status-badge <?= $t['status']==='open'?'status-published':'status-draft' ?>"><?= $t['status'] ?></span></td>
                        <td>
                            <div class="action-btns">
                                <button class="btn-icon edit btn-edit-tema" title="Edit" data-tema='<?= htmlspecialchars(json_encode($t), ENT_QUOTES) ?>'><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>
                                <button class="btn-icon view btn-form-settings" title="Pengaturan Form" data-tema-id="<?= $t['id'] ?>" data-tema-judul="<?= htmlspecialchars($t['judul'], ENT_QUOTES) ?>"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg></button>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Hapus tema ini?')">
                                    <input type="hidden" name="aksi" value="hapus_tema">
                                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                    <button type="submit" class="btn-icon delete"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg></button>
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
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
            <div style="font-size:14px;color:#666">Total: <strong><?= count($pendaftarans) ?></strong> pendaftaran</div>
            <a href="scan-qr.php" class="btn-primary" style="background:#2e7d32">Scan QR Kehadiran</a>
        </div>
        <?php
        // Kelompokkan pendaftaran per tema
        $grouped = [];
        foreach ($pendaftarans as $p) {
            $grouped[$p['tema_judul']][] = $p;
        }
        if (empty($grouped)): ?>
        <div style="text-align:center;color:#999;padding:40px">Belum ada pendaftaran.</div>
        <?php else: ?>
        <?php foreach ($grouped as $tema_nama => $peserta_tema): ?>
        <div style="margin-bottom:32px">
            <div class="section-table-header">
                <h3>
                    <?= htmlspecialchars($tema_nama) ?>
                    <span style="font-size:12px;font-weight:400;color:#888;margin-left:8px">(<?= count($peserta_tema) ?> peserta)</span>
                </h3>
                <?php
                $tid_exp = $peserta_tema[0]['tema_id'] ?? 0;
                if ($tid_exp): ?>
                <div style="display:flex;gap:8px">
                    <a href="export-absensi.php?tema=<?= $tid_exp ?>" class="btn-primary" style="background:#2e7d32;font-size:12px;padding:6px 14px;">Download Excel</a>
                    <a href="absensi.php?tema=<?= $tid_exp ?>" class="btn-primary" style="background:#1565c0;font-size:12px;padding:6px 14px;">Absensi</a>
                </div>
                <?php endif; ?>
            </div>
            <div class="data-table" style="overflow-x:auto">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>NIK/NIS</th>
                            <th>Instansi</th>
                            <th>No. HP</th>
                            <th>Kecamatan</th>
                            <th>NPWP</th>
                            <th>No. Rekening</th>
                            <th>Hadir</th>
                            <th>QR</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($peserta_tema as $i => $p): ?>
                        <tr>
                            <td style="text-align:center;color:#888;font-size:13px"><?= $i+1 ?></td>
                            <td>
                                <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($p['nama_lengkap']) ?></div>
                                <div style="font-size:11px;color:#888"><?= htmlspecialchars($p['email']) ?></div>
                            </td>
                            <td style="font-size:13px"><?= htmlspecialchars($p['nik'] ?? '-') ?></td>
                            <td style="font-size:13px"><?= htmlspecialchars($p['instansi'] ?? '-') ?></td>
                            <td style="font-size:13px"><?= htmlspecialchars($p['no_hp'] ?? '-') ?></td>
                            <td style="font-size:13px"><?= htmlspecialchars($p['asal_kecamatan'] ?? '-') ?></td>
                            <td style="font-size:13px"><?= htmlspecialchars($p['npwp'] ?? '-') ?></td>
                            <td style="font-size:13px"><?= htmlspecialchars($p['no_rekening'] ?? '-') ?></td>
                            <td style="text-align:center">
                                <?php if (!empty($p['hadir']) && $p['hadir']): ?>
                                    <span class="status-badge status-published" title="<?= $p['hadir_at'] ? date('d/m/Y H:i', strtotime($p['hadir_at'])) : '' ?>">Hadir</span>
                                <?php else: ?>
                                    <span style="color:#ccc;font-size:12px">-</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center">
                                <?php if (!empty($p['cancelled_at'])): ?>
                                    <span class="status-badge status-draft" title="<?= htmlspecialchars($p['alasan_batal'] ?? '') ?>">Batal</span>
                                <?php elseif (!empty($p['qr_code'])): ?>
                                    <a href="../<?= htmlspecialchars($p['qr_code']) ?>" target="_blank" class="btn-icon view" title="Lihat QR">QR</a>
                                <?php else: ?>
                                    <span style="color:#ccc;font-size:12px">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- TAB BERANDA -->
    <div id="tab-beranda" class="tab-content <?= $active_tab=='beranda'?'active':'' ?>">
        <?php
        $conn->query("CREATE TABLE IF NOT EXISTS hero_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            hero_image VARCHAR(255) DEFAULT NULL,
            hero_judul VARCHAR(200) DEFAULT 'Platform Pelatihan Digital Diskominfo Bogor',
            hero_subjudul VARCHAR(300) DEFAULT 'Akses katalog modul pelatihan secara mudah dan terpusat.',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $res_h = $conn->query("SELECT * FROM hero_settings LIMIT 1");
        if ($res_h->num_rows === 0) $conn->query("INSERT INTO hero_settings (hero_judul) VALUES ('Platform Pelatihan Digital Diskominfo Bogor')");
        $hero = $conn->query("SELECT * FROM hero_settings LIMIT 1")->fetch_assoc();
        ?>
        <div class="kategori-layout">
            <!-- Preview -->
            <div>
                <div style="border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);margin-bottom:16px;position:relative;height:260px;background:linear-gradient(135deg,#1a237e,#3f51b5);background-size:cover;background-position:center;<?= $hero['hero_image'] ? 'background-image:url(\'../'.$hero['hero_image'].'\')' : '' ?>">
                    <div style="position:absolute;inset:0;background:rgba(0,0,0,0.5);display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:20px">
                        <div style="font-size:11px;color:rgba(255,255,255,.7);text-transform:uppercase;letter-spacing:2px;margin-bottom:8px">Platform Pelatihan Digital</div>
                        <div style="font-size:20px;font-weight:800;color:#fff;margin-bottom:8px"><?= htmlspecialchars($hero['hero_judul']) ?></div>
                        <div style="font-size:13px;color:rgba(255,255,255,.8)"><?= htmlspecialchars($hero['hero_subjudul']) ?></div>
                    </div>
                </div>
                <p style="font-size:12px;color:#888;text-align:center">Preview tampilan hero beranda</p>
            </div>
            <!-- Form Edit -->
            <div class="card-form">
                <h3>Kelola Hero Beranda</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="aksi" value="update_hero">
                    <div class="form-group">
                        <label>Judul Hero</label>
                        <input type="text" name="hero_judul" class="form-control" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px" value="<?= htmlspecialchars($hero['hero_judul']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Sub Judul / Deskripsi</label>
                        <textarea name="hero_subjudul" class="form-control" style="min-height:80px"><?= htmlspecialchars($hero['hero_subjudul']) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Foto Background Hero</label>
                        <?php if ($hero['hero_image']): ?>
                        <div style="margin-bottom:8px;display:flex;align-items:center;gap:12px">
                            <img src="../<?= htmlspecialchars($hero['hero_image']) ?>" style="height:60px;border-radius:6px;object-fit:cover">
                            <div>
                                <small style="display:block;color:#888;margin-bottom:6px">Gambar saat ini</small>
                                <a href="?tab=beranda&hapus_hero=1" class="btn-primary" style="background:#e53935;font-size:12px;padding:5px 12px;" onclick="return confirm('Hapus foto background hero?')">Hapus Foto</a>
                            </div>
                        </div>
                        <?php endif; ?>
                        <input type="file" name="hero_image" accept="image/*,.jfif" class="form-control" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:8px;font-size:13px">
                        <small style="color:#888">JPG, PNG, WEBP — Kosongkan jika tidak ingin mengganti. Disarankan ukuran minimal 1920x1080px.</small>
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%;justify-content:center">Simpan Perubahan</button>
                </form>
            </div>
        </div>

        <hr style="margin:28px 0;border:none;border-top:1px solid #eee">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
            <h3 style="font-size:16px;font-weight:700">Konten Pemberitaan &amp; Agenda</h3>
            <button class="btn-primary" onclick="document.getElementById('modalTambahKonten').classList.add('open')">+ Tambah Konten</button>
        </div>
        <?php
        $conn->query("CREATE TABLE IF NOT EXISTS konten_beranda (id INT AUTO_INCREMENT PRIMARY KEY, judul VARCHAR(200) NOT NULL, deskripsi TEXT, gambar VARCHAR(255), tipe ENUM('pemberitaan','agenda','pengumuman') DEFAULT 'pemberitaan', tanggal DATE, status ENUM('published','draft') DEFAULT 'published', urutan INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $konten_list = $conn->query("SELECT * FROM konten_beranda ORDER BY urutan ASC, created_at DESC")->fetch_all(MYSQLI_ASSOC);
        ?>
        <div class="data-table">
            <table>
                <thead><tr><th>Gambar</th><th>Judul</th><th>Tipe</th><th>Tanggal</th><th>Urutan</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($konten_list as $k): ?>
                <tr>
                    <td><?= $k['gambar'] ? '<img src="../'.$k['gambar'].'" style="height:48px;width:72px;object-fit:cover;border-radius:4px">' : '<span style="color:#ccc">-</span>' ?></td>
                    <td style="font-weight:600;font-size:13px"><?= htmlspecialchars($k['judul']) ?></td>
                    <td><span class="badge badge-internal"><?= $k['tipe'] ?></span></td>
                    <td style="font-size:13px"><?= $k['tanggal'] ? date('d/m/Y', strtotime($k['tanggal'])) : '-' ?></td>
                    <td style="text-align:center"><?= $k['urutan'] ?></td>
                    <td><span class="status-badge status-<?= $k['status']==='published'?'published':'draft' ?>"><?= $k['status'] ?></span></td>
                    <td>
                        <div class="action-btns">
                            <button class="btn-icon edit" onclick="openEditKonten(<?= htmlspecialchars(json_encode($k)) ?>)">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </button>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Hapus konten ini?')">
                                <input type="hidden" name="aksi" value="hapus_konten">
                                <input type="hidden" name="id" value="<?= $k['id'] ?>">
                                <button type="submit" class="btn-icon delete">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($konten_list)): ?><tr><td colspan="7" style="text-align:center;color:#999;padding:24px">Belum ada konten. Klik "+ Tambah Konten" untuk mulai.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div><!-- /tab-beranda -->

<!-- MODAL TAMBAH KONTEN -->
<div class="modal-overlay" id="modalTambahKonten">
    <div class="modal">
        <h3>Tambah Konten</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="aksi" value="tambah_konten">
            <div class="form-group"><label>Judul</label>
                <input type="text" name="judul" class="form-control" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px" required>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Tipe</label>
                    <select name="tipe" class="form-control">
                        <option value="pemberitaan">Pemberitaan</option>
                        <option value="agenda">Agenda</option>
                        <option value="pengumuman">Pengumuman</option>
                    </select>
                </div>
                <div class="form-group"><label>Tanggal</label>
                    <input type="date" name="tanggal" class="form-control" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px">
                </div>
            </div>
            <div class="form-group"><label>Deskripsi</label>
                <textarea name="deskripsi" class="form-control" style="min-height:80px"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Gambar</label>
                    <input type="file" name="gambar" accept="image/*,.jfif" class="form-control" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:8px;font-size:13px">
                </div>
                <div class="form-group"><label>Urutan Tampil</label>
                    <input type="number" name="urutan" value="0" min="0" class="form-control" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px">
                </div>
            </div>
            <div class="form-group"><label>Status</label>
                <select name="status" class="form-control">
                    <option value="published">Published</option>
                    <option value="draft">Draft</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="document.getElementById('modalTambahKonten').classList.remove('open')">Batal</button>
                <button type="submit" class="btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT KONTEN -->
<div class="modal-overlay" id="modalEditKonten">
    <div class="modal">
        <h3>Edit Konten</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="aksi" value="edit_konten">
            <input type="hidden" name="id" id="ek_id">
            <div class="form-group"><label>Judul</label>
                <input type="text" name="judul" id="ek_judul" class="form-control" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px" required>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Tipe</label>
                    <select name="tipe" id="ek_tipe" class="form-control">
                        <option value="pemberitaan">Pemberitaan</option>
                        <option value="agenda">Agenda</option>
                        <option value="pengumuman">Pengumuman</option>
                    </select>
                </div>
                <div class="form-group"><label>Tanggal</label>
                    <input type="date" name="tanggal" id="ek_tanggal" class="form-control" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px">
                </div>
            </div>
            <div class="form-group"><label>Deskripsi</label>
                <textarea name="deskripsi" id="ek_deskripsi" class="form-control" style="min-height:80px"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Ganti Gambar</label>
                    <div id="ek_img_preview" style="margin-bottom:6px"></div>
                    <input type="file" name="gambar" accept="image/*,.jfif" class="form-control" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:8px;font-size:13px">
                    <small style="color:#888">Kosongkan jika tidak diganti</small>
                </div>
                <div class="form-group"><label>Urutan Tampil</label>
                    <input type="number" name="urutan" id="ek_urutan" min="0" class="form-control" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px">
                </div>
            </div>
            <div class="form-group"><label>Status</label>
                <select name="status" id="ek_status" class="form-control">
                    <option value="published">Published</option>
                    <option value="draft">Draft</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="document.getElementById('modalEditKonten').classList.remove('open')">Batal</button>
                <button type="submit" class="btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

    <!-- TAB PENGGUNA -->
    <div id="tab-pengguna" class="tab-content <?= $active_tab=='pengguna'?'active':'' ?>">
        <?php
        $msg_map = ['added'=>'Pengguna berhasil ditambahkan.','deleted'=>'Pengguna berhasil dihapus.','email_exists'=>'Email sudah terdaftar.'];
        if ($active_tab=='pengguna' && $msg && isset($msg_map[$msg])):
        ?>
        <div class="alert alert-<?= $msg==='email_exists'?'error':'success' ?>" style="margin-bottom:16px"><?= $msg_map[$msg] ?></div>
        <?php endif; ?>
        <div class="kategori-layout">
            <div class="data-table pengguna-table">
                <table>
                    <thead><tr><th>Nama</th><th>Email</th><th>Role</th><th>Bergabung</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['nama_lengkap']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><span class="role-badge role-<?= $u['role'] ?>"><?= $u['role'] ?></span></td>
                            <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                            <td>
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Hapus pengguna ini?')">
                                    <input type="hidden" name="aksi" value="hapus_user">
                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn-icon delete"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg></button>
                                </form>
                                <?php else: ?>
                                <span style="font-size:11px;color:#aaa">Anda</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-form">
                <h3>Tambah Pengguna</h3>
                <form method="POST">
                    <input type="hidden" name="aksi" value="tambah_user">
                    <div class="form-group"><label>Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" class="form-control" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px" required>
                    </div>
                    <div class="form-group"><label>Email</label>
                        <input type="email" name="email" class="form-control" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px" required>
                    </div>
                    <div class="form-group"><label>Password</label>
                        <input type="password" name="password" class="form-control" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px" required>
                    </div>
                    <div class="form-group"><label>Role</label>
                        <select name="role" class="form-control">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%;justify-content:center">+ Tambah</button>
                </form>
            </div>
        </div>
    </div>
</div><!-- /tab-pengguna -->
    </div><!-- /panel-card-body -->
</div><!-- /panel-card -->
</main><!-- /admin-main -->
</div><!-- /admin-wrapper -->

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
            <div class="form-group"><label>Tema Pelatihan <small style="color:#999">(opsional)</small></label>
                <select name="tema_id" class="form-control">
                    <option value="">-- Tidak dikaitkan --</option>
                    <?php foreach ($temas as $t): ?>
                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['judul']) ?> (<?= date('d M Y', strtotime($t['tanggal'])) ?>)</option>
                    <?php endforeach; ?>
                </select>
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
                    <small style="color:#888">JPG, PNG, WEBP, JFIF</small>
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
            <div class="form-group"><label>Tema Pelatihan <small style="color:#999">(opsional)</small></label>
                <select name="tema_id" id="edit_tema_id" class="form-control">
                    <option value="">-- Tidak dikaitkan --</option>
                    <?php foreach ($temas as $t): ?>
                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['judul']) ?> (<?= date('d M Y', strtotime($t['tanggal'])) ?>)</option>
                    <?php endforeach; ?>
                </select>
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
                <div class="form-group"><label>Tanggal Mulai</label>
                    <input type="date" name="tanggal" class="form-control" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px">
                </div>
                <div class="form-group"><label>Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" class="form-control" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px">
                    <small style="color:#888">Kosongkan jika 1 hari</small>
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
                <div class="form-group"><label>Tanggal Mulai</label>
                    <input type="date" name="tanggal" id="etema_tanggal" class="form-control" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px">
                </div>
                <div class="form-group"><label>Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" id="etema_tanggal_selesai" class="form-control" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px">
                    <small style="color:#888">Kosongkan jika 1 hari</small>
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
function openEdit(data) {
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_judul').value = data.judul;
    document.getElementById('edit_instruktur').value = data.instruktur || '';
    document.getElementById('edit_lokasi').value = data.lokasi || '';
    document.getElementById('edit_tanggal').value = data.tanggal || '';
    document.getElementById('edit_deskripsi').value = data.deskripsi || '';
    document.getElementById('edit_kategori').value = data.kategori_id || '';
    document.getElementById('edit_status').value = data.status || 'draft';
    document.getElementById('edit_tema_id').value = data.tema_id || '';
    const thumbEl = document.getElementById('edit_thumb_preview');
    thumbEl.innerHTML = data.thumbnail ? '<img src="../' + data.thumbnail + '" style="height:50px;border-radius:4px">' : '';
    const pptEl = document.getElementById('edit_ppt_info');
    pptEl.textContent = data.file_ppt ? 'File PPT sudah ada' : '';
    document.getElementById('modalEditModul').classList.add('open');
}
function openEditTema(data) {
    document.getElementById('etema_id').value = data.id;
    document.getElementById('etema_judul').value = data.judul;
    document.getElementById('etema_lokasi').value = data.lokasi || '';
    document.getElementById('etema_tanggal').value = data.tanggal || '';
    document.getElementById('etema_tanggal_selesai').value = data.tanggal_selesai || '';
    document.getElementById('etema_kuota').value = data.kuota || 30;
    document.getElementById('etema_deskripsi').value = data.deskripsi || '';
    document.getElementById('etema_status').value = data.status || 'open';
    const prev = document.getElementById('etema_thumb_preview');
    prev.innerHTML = data.thumbnail ? '<img src="../' + data.thumbnail + '" style="height:50px;border-radius:4px">' : '';
    document.getElementById('modalEditTema').classList.add('open');
}

function openEditKonten(data) {
    document.getElementById('ek_id').value = data.id;
    document.getElementById('ek_judul').value = data.judul;
    document.getElementById('ek_tipe').value = data.tipe || 'pemberitaan';
    document.getElementById('ek_tanggal').value = data.tanggal || '';
    document.getElementById('ek_deskripsi').value = data.deskripsi || '';
    document.getElementById('ek_urutan').value = data.urutan || 0;
    document.getElementById('ek_status').value = data.status || 'published';
    const prev = document.getElementById('ek_img_preview');
    prev.innerHTML = data.gambar ? '<img src="../' + data.gambar + '" style="height:50px;border-radius:4px;object-fit:cover">' : '';
    document.getElementById('modalEditKonten').classList.add('open');
}

document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', function(e) { if (e.target === this) this.classList.remove('open'); });
});
document.querySelectorAll('.btn-edit-tema').forEach(btn => {
    btn.addEventListener('click', function() {
        openEditTema(JSON.parse(this.dataset.tema));
    });
});
document.querySelectorAll('.btn-form-settings').forEach(btn => {
    btn.addEventListener('click', function() {
        openFormSettings(parseInt(this.dataset.temaId), this.dataset.temaJudul);
    });
});
</script>

<!-- MODAL PENGATURAN FORM TEMA -->
<div class="modal-overlay" id="modalFormSettings">
    <div class="modal">
        <h3>Pengaturan Form Pendaftaran</h3>
        <p id="fs-tema-judul" style="color:#3f51b5;font-weight:600;margin-bottom:16px;font-size:14px"></p>
        <form method="POST">
            <input type="hidden" name="aksi" value="save_form_settings">
            <input type="hidden" name="tema_id" id="fs-tema-id">
            <p style="font-size:13px;color:#666;margin-bottom:14px">Centang field yang <strong>wajib</strong> diisi peserta saat mendaftar:</p>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:20px">
                <label style="display:flex;align-items:center;gap:8px;font-size:14px;cursor:pointer;padding:10px;background:#f8f9ff;border-radius:8px;border:1px solid #e0e7ff">
                    <input type="checkbox" name="req_nik" id="fs_req_nik" value="1"> NIK / NIS
                </label>
                <label style="display:flex;align-items:center;gap:8px;font-size:14px;cursor:pointer;padding:10px;background:#f8f9ff;border-radius:8px;border:1px solid #e0e7ff">
                    <input type="checkbox" name="req_npwp" id="fs_req_npwp" value="1"> NPWP
                </label>
                <label style="display:flex;align-items:center;gap:8px;font-size:14px;cursor:pointer;padding:10px;background:#f8f9ff;border-radius:8px;border:1px solid #e0e7ff">
                    <input type="checkbox" name="req_rekening" id="fs_req_rekening" value="1"> No. Rekening
                </label>
                <label style="display:flex;align-items:center;gap:8px;font-size:14px;cursor:pointer;padding:10px;background:#f8f9ff;border-radius:8px;border:1px solid #e0e7ff">
                    <input type="checkbox" name="req_nama_bank" id="fs_req_nama_bank" value="1"> Nama Bank
                </label>
                <label style="display:flex;align-items:center;gap:8px;font-size:14px;cursor:pointer;padding:10px;background:#f8f9ff;border-radius:8px;border:1px solid #e0e7ff">
                    <input type="checkbox" name="req_kecamatan" id="fs_req_kecamatan" value="1"> Asal Kecamatan
                </label>
                <label style="display:flex;align-items:center;gap:8px;font-size:14px;cursor:pointer;padding:10px;background:#f8f9ff;border-radius:8px;border:1px solid #e0e7ff">
                    <input type="checkbox" name="req_doc_ktp" id="fs_req_doc_ktp" value="1"> Upload KTP
                </label>
                <label style="display:flex;align-items:center;gap:8px;font-size:14px;cursor:pointer;padding:10px;background:#f8f9ff;border-radius:8px;border:1px solid #e0e7ff">
                    <input type="checkbox" name="req_doc_npwp" id="fs_req_doc_npwp" value="1"> Upload NPWP
                </label>
                <label style="display:flex;align-items:center;gap:8px;font-size:14px;cursor:pointer;padding:10px;background:#f8f9ff;border-radius:8px;border:1px solid #e0e7ff">
                    <input type="checkbox" name="req_doc_rekening" id="fs_req_doc_rekening" value="1"> Upload Buku Rekening
                </label>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="document.getElementById('modalFormSettings').classList.remove('open')">Batal</button>
                <button type="submit" class="btn-primary">Simpan Pengaturan</button>
            </div>
        </form>
    </div>
</div>

<script>
var _formSettingsAll = <?php echo json_encode($form_settings_all); ?>;
var _defaultFs = {req_nik:1,req_npwp:1,req_rekening:1,req_nama_bank:1,req_kecamatan:1,req_doc_ktp:0,req_doc_npwp:0,req_doc_rekening:0};

function openFormSettings(temaId, temaJudul) {
    document.getElementById('fs-tema-id').value = temaId;
    document.getElementById('fs-tema-judul').textContent = temaJudul;
    var fs = _formSettingsAll[temaId] || _defaultFs;
    document.getElementById('fs_req_nik').checked = fs.req_nik == 1;
    document.getElementById('fs_req_npwp').checked = fs.req_npwp == 1;
    document.getElementById('fs_req_rekening').checked = fs.req_rekening == 1;
    document.getElementById('fs_req_nama_bank').checked = fs.req_nama_bank == 1;
    document.getElementById('fs_req_kecamatan').checked = fs.req_kecamatan == 1;
    document.getElementById('fs_req_doc_ktp').checked = fs.req_doc_ktp == 1;
    document.getElementById('fs_req_doc_npwp').checked = fs.req_doc_npwp == 1;
    document.getElementById('fs_req_doc_rekening').checked = fs.req_doc_rekening == 1;
    document.getElementById('modalFormSettings').classList.add('open');
}
</script>

<div><!-- absorbed by footer's closing </div> -->
<?php require_once '../includes/footer.php'; ?>
