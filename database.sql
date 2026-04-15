-- Database: diskominfo_pelatihan
CREATE DATABASE IF NOT EXISTS diskominfo_pelatihan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE diskominfo_pelatihan;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE modul (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(200) NOT NULL,
    deskripsi TEXT,
    kategori_id INT,
    instruktur VARCHAR(100),
    lokasi VARCHAR(150),
    tanggal DATE,
    views INT DEFAULT 0,
    status ENUM('published','draft') DEFAULT 'draft',
    thumbnail VARCHAR(255),
    file_materi VARCHAR(255),
    file_ppt VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE SET NULL
);

CREATE TABLE tema_pelatihan (
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
);

CREATE TABLE pendaftaran (
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
);

-- Seed data
INSERT INTO users (nama_lengkap, email, password, role) VALUES
('Administrator', 'admin@diskominfo.go.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('User Biasa', 'user@diskominfo.go.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');
-- password default: password

INSERT INTO kategori (nama, deskripsi) VALUES
('Internal', 'Pelatihan internal untuk pegawai'),
('Kegiatan', 'Kegiatan dan event pelatihan'),
('External', 'Pelatihan dari pihak eksternal');

INSERT INTO modul (judul, deskripsi, kategori_id, instruktur, lokasi, tanggal, views, status) VALUES
('Pemanfaatan Data Center dan ClouDisk', 'Pelatihan mengenai pemanfaatan data center dan cloudisk untuk mendukung operasional.', 1, 'Dr. Inkens', 'Aula Diskominfo Lt.2', '2026-04-15', 30, 'published'),
('Pemanfaatan Data Center dan ClouDisk', 'Pelatihan mengenai pemanfaatan data center dan cloudisk untuk mendukung operasional.', 2, 'Dr. Inkens', 'Aula Diskominfo Lt.2', '2026-03-18', 30, 'published'),
('Pemanfaatan Data Center dan ClouDisk', 'Pelatihan mengenai pemanfaatan data center dan cloudisk untuk mendukung operasional.', 1, 'Dr. Inkens', 'Aula Diskominfo Lt.2', '2026-01-15', 30, 'published'),
('Pemanfaatan Data Center dan ClouDisk', 'Pelatihan mengenai pemanfaatan data center dan cloudisk untuk mendukung operasional.', 1, 'Dr. Inkens', 'Aula Diskominfo Lt.2', '2026-01-15', 30, 'published');

INSERT INTO tema_pelatihan (judul, deskripsi, instruktur, lokasi, tanggal, kuota, status) VALUES
('Keamanan Siber untuk ASN', 'Pelatihan keamanan siber dasar bagi Aparatur Sipil Negara, mencakup phishing, password management, dan keamanan jaringan.', 'Ir. Budi Santoso, M.T.', 'Aula Diskominfo Lt.2', '2026-05-10', 30, 'open'),
('Transformasi Digital Pemerintahan', 'Memahami konsep dan implementasi transformasi digital di lingkungan pemerintahan daerah.', 'Dr. Sari Dewi', 'Ruang Rapat Utama', '2026-05-20', 25, 'open'),
('Pengelolaan Media Sosial Instansi', 'Strategi dan teknik pengelolaan media sosial resmi instansi pemerintah secara profesional.', 'Andi Pratama, S.Kom.', 'Lab Komputer Lt.3', '2026-06-05', 20, 'open'),
('Penggunaan Aplikasi Perkantoran', 'Pelatihan intensif penggunaan Microsoft Office dan Google Workspace untuk produktivitas kerja.', 'Rini Wulandari, M.Kom.', 'Lab Komputer Lt.3', '2026-06-15', 35, 'open'),
('Manajemen Data dan Arsip Digital', 'Teknik pengelolaan, penyimpanan, dan pengarsipan dokumen digital sesuai regulasi.', 'Drs. Hendra Kusuma', 'Aula Diskominfo Lt.2', '2026-07-01', 30, 'open');
