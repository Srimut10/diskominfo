<?php
// Jalankan sekali untuk update database yang sudah ada
require_once 'config/db.php';

$queries = [
    "ALTER TABLE modul ADD COLUMN IF NOT EXISTS file_ppt VARCHAR(255) DEFAULT NULL AFTER file_materi",
    // Fix path lama yang tersimpan dengan prefix ../
    "UPDATE modul SET thumbnail = REPLACE(thumbnail, '../uploads/', 'uploads/') WHERE thumbnail LIKE '../%'",
    "UPDATE modul SET file_ppt = REPLACE(file_ppt, '../uploads/', 'uploads/') WHERE file_ppt LIKE '../%'",
    "CREATE TABLE IF NOT EXISTS tema_pelatihan (
        id INT AUTO_INCREMENT PRIMARY KEY,
        judul VARCHAR(200) NOT NULL,
        deskripsi TEXT,
        instruktur VARCHAR(100),
        lokasi VARCHAR(150),
        tanggal DATE,
        kuota INT DEFAULT 0,
        status ENUM('open','closed') DEFAULT 'open',
        thumbnail VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS pendaftaran (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tema_id INT NOT NULL,
        user_id INT NOT NULL,
        nama_lengkap VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        instansi VARCHAR(150),
        no_hp VARCHAR(20),
        status ENUM('pending','approved','rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (tema_id) REFERENCES tema_pelatihan(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_daftar (tema_id, user_id)
    )",
];

$ok = 0; $fail = 0;
foreach ($queries as $q) {
    if ($conn->query($q)) { $ok++; } else { echo "Error: " . $conn->error . "<br>"; $fail++; }
}

// Seed tema jika kosong
$count = $conn->query("SELECT COUNT(*) as c FROM tema_pelatihan")->fetch_assoc()['c'];
if ($count == 0) {
    $conn->query("INSERT INTO tema_pelatihan (judul,deskripsi,instruktur,lokasi,tanggal,kuota,status) VALUES
        ('Keamanan Siber untuk ASN','Pelatihan keamanan siber dasar bagi ASN.','Ir. Budi Santoso, M.T.','Aula Diskominfo Lt.2','2026-05-10',30,'open'),
        ('Transformasi Digital Pemerintahan','Konsep dan implementasi transformasi digital.','Dr. Sari Dewi','Ruang Rapat Utama','2026-05-20',25,'open'),
        ('Pengelolaan Media Sosial Instansi','Strategi pengelolaan media sosial resmi instansi.','Andi Pratama, S.Kom.','Lab Komputer Lt.3','2026-06-05',20,'open'),
        ('Penggunaan Aplikasi Perkantoran','Pelatihan Microsoft Office dan Google Workspace.','Rini Wulandari, M.Kom.','Lab Komputer Lt.3','2026-06-15',35,'open'),
        ('Manajemen Data dan Arsip Digital','Teknik pengelolaan dan pengarsipan dokumen digital.','Drs. Hendra Kusuma','Aula Diskominfo Lt.2','2026-07-01',30,'open')
    ");
    echo "Seed tema pelatihan berhasil.<br>";
}

echo "<br><strong>Migrasi selesai: $ok berhasil, $fail gagal.</strong><br>";
echo "<a href='index.php'>Kembali ke Beranda</a>";
?>
