<?php
require_once 'config/db.php';

$queries = [
    "ALTER TABLE modul ADD COLUMN IF NOT EXISTS file_ppt VARCHAR(255) DEFAULT NULL AFTER file_materi",
    "UPDATE modul SET views = 0", // reset views ke nilai asli
    "ALTER TABLE modul ADD COLUMN IF NOT EXISTS tema_id INT DEFAULT NULL AFTER kategori_id",
    "ALTER TABLE modul ADD COLUMN IF NOT EXISTS sertifikat_template VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE pendaftaran ADD COLUMN IF NOT EXISTS nik VARCHAR(20) DEFAULT NULL AFTER no_hp",
    "ALTER TABLE pendaftaran ADD COLUMN IF NOT EXISTS alamat TEXT DEFAULT NULL AFTER nik",
    "ALTER TABLE pendaftaran ADD COLUMN IF NOT EXISTS npwp VARCHAR(25) DEFAULT NULL AFTER alamat",
    "ALTER TABLE pendaftaran ADD COLUMN IF NOT EXISTS no_rekening VARCHAR(30) DEFAULT NULL AFTER npwp",
    "ALTER TABLE pendaftaran ADD COLUMN IF NOT EXISTS asal_kecamatan VARCHAR(100) DEFAULT NULL AFTER no_rekening",
    "ALTER TABLE tema_pelatihan ADD COLUMN IF NOT EXISTS tanggal_selesai DATE DEFAULT NULL AFTER tanggal",
    "ALTER TABLE pendaftaran ADD COLUMN IF NOT EXISTS qr_code VARCHAR(255) DEFAULT NULL AFTER alamat",
    "ALTER TABLE pendaftaran ADD COLUMN IF NOT EXISTS alasan_batal TEXT DEFAULT NULL AFTER qr_code",
    "ALTER TABLE pendaftaran ADD COLUMN IF NOT EXISTS cancelled_at DATETIME DEFAULT NULL AFTER alasan_batal",
    "ALTER TABLE pendaftaran ADD COLUMN IF NOT EXISTS hadir TINYINT DEFAULT 0 AFTER cancelled_at",
    "ALTER TABLE pendaftaran ADD COLUMN IF NOT EXISTS hadir_at DATETIME DEFAULT NULL AFTER hadir",
    "CREATE TABLE IF NOT EXISTS notifikasi_admin (
        id INT AUTO_INCREMENT PRIMARY KEY,
        judul VARCHAR(200) NOT NULL,
        pesan TEXT,
        tipe ENUM('info','warning','danger') DEFAULT 'info',
        is_read TINYINT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Tabel quiz soal
    "CREATE TABLE IF NOT EXISTS quiz_soal (
        id INT AUTO_INCREMENT PRIMARY KEY,
        modul_id INT NOT NULL,
        pertanyaan TEXT NOT NULL,
        opsi_a VARCHAR(255), opsi_b VARCHAR(255), opsi_c VARCHAR(255), opsi_d VARCHAR(255),
        jawaban_benar ENUM('a','b','c','d') NOT NULL,
        urutan INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Tabel hasil quiz user
    "CREATE TABLE IF NOT EXISTS quiz_hasil (
        id INT AUTO_INCREMENT PRIMARY KEY,
        modul_id INT NOT NULL,
        user_id INT NOT NULL,
        skor INT DEFAULT 0,
        lulus TINYINT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_quiz (modul_id, user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Tabel tracking baca materi
    "CREATE TABLE IF NOT EXISTS materi_baca (
        id INT AUTO_INCREMENT PRIMARY KEY,
        modul_id INT NOT NULL,
        user_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_baca (modul_id, user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Tabel sertifikat
    "CREATE TABLE IF NOT EXISTS sertifikat (
        id INT AUTO_INCREMENT PRIMARY KEY,
        modul_id INT NOT NULL,
        user_id INT NOT NULL,
        nama_peserta VARCHAR(100) NOT NULL,
        kode_sertifikat VARCHAR(20) NOT NULL UNIQUE,
        file_path VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_sertifikat (modul_id, user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // password_reset
    "CREATE TABLE IF NOT EXISTS password_reset (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        used TINYINT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "UPDATE modul SET thumbnail = REPLACE(thumbnail, '../uploads/', 'uploads/') WHERE thumbnail LIKE '../%'",
    "UPDATE modul SET file_ppt = REPLACE(file_ppt, '../uploads/', 'uploads/') WHERE file_ppt LIKE '../%'",
];

$ok = 0; $fail = 0;
foreach ($queries as $q) {
    if ($conn->query($q)) { $ok++; } else { echo "Error: " . $conn->error . "<br>"; $fail++; }
}

$count = $conn->query("SELECT COUNT(*) as c FROM tema_pelatihan")->fetch_assoc()['c'];
if ($count == 0) {
    $conn->query("INSERT INTO tema_pelatihan (judul,deskripsi,instruktur,lokasi,tanggal,kuota,status) VALUES
        ('Keamanan Siber untuk ASN','Pelatihan keamanan siber dasar bagi ASN.','Ir. Budi Santoso, M.T.','Aula Diskominfo Lt.2','2026-05-10',30,'open'),
        ('Transformasi Digital Pemerintahan','Konsep dan implementasi transformasi digital.','Dr. Sari Dewi','Ruang Rapat Utama','2026-05-20',25,'open'),
        ('Pengelolaan Media Sosial Instansi','Strategi pengelolaan media sosial resmi instansi.','Andi Pratama, S.Kom.','Lab Komputer Lt.3','2026-06-05',20,'open'),
        ('Penggunaan Aplikasi Perkantoran','Pelatihan Microsoft Office dan Google Workspace.','Rini Wulandari, M.Kom.','Lab Komputer Lt.3','2026-06-15',35,'open'),
        ('Manajemen Data dan Arsip Digital','Teknik pengelolaan dan pengarsipan dokumen digital.','Drs. Hendra Kusuma','Aula Diskominfo Lt.2','2026-07-01',30,'open')
    ");
    echo "Seed tema berhasil.<br>";
}

echo "<br><strong>Migrasi selesai: $ok berhasil, $fail gagal.</strong><br>";
echo "<a href='index.php'>Kembali ke Beranda</a>";
?>
