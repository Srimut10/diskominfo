<?php
require_once '../includes/header.php';
?>
<style>
/* ===== TENTANG KAMI — self-contained styles ===== */
.tkg-breadcrumb { background:#f8f9ff; border-bottom:1px solid #e8eaf6; padding:12px 0; }
.tkg-breadcrumb-inner { max-width:1200px; margin:0 auto; padding:0 24px; display:flex; align-items:center; gap:6px; font-size:13px; color:#888; }
.tkg-breadcrumb-inner a { color:#3f51b5; text-decoration:none; }
.tkg-breadcrumb-inner svg { width:14px; height:14px; stroke:#bbb; fill:none; stroke-width:2; flex-shrink:0; }

.tkg-hero { background:linear-gradient(135deg,#1a237e 0%,#283593 50%,#3f51b5 100%); padding:80px 24px; }
.tkg-hero-inner { max-width:1100px; margin:0 auto; display:grid; grid-template-columns:1fr 220px; gap:48px; align-items:center; }
.tkg-hero-label { font-size:12px; text-transform:uppercase; letter-spacing:2px; color:rgba(255,255,255,.7); font-weight:700; margin-bottom:12px; }
.tkg-hero h1 { font-size:36px; font-weight:800; line-height:1.2; color:#fff; margin-bottom:16px; }
.tkg-hero h1 span { color:rgba(255,255,255,.75); }
.tkg-hero p { font-size:16px; color:rgba(255,255,255,.85); line-height:1.7; max-width:520px; }
.tkg-hero-img { display:flex; align-items:center; justify-content:center; }
.tkg-hero-img-wrap { width:180px; height:180px; background:rgba(255,255,255,.12); border-radius:50%; display:flex; align-items:center; justify-content:center; padding:20px; }
.tkg-hero-img-wrap img { width:100%; height:100%; object-fit:contain; }
@media(max-width:768px){ .tkg-hero{ padding:48px 20px; } .tkg-hero-inner{ grid-template-columns:1fr; gap:0; } .tkg-hero-img{ display:none; } .tkg-hero h1{ font-size:26px; } }

.tkg-section { padding:64px 24px; }
.tkg-section.gray { background:#f8f9ff; }
.tkg-section.white { background:#fff; }
.tkg-container { max-width:1100px; margin:0 auto; }
.tkg-label { font-size:12px; text-transform:uppercase; letter-spacing:2px; color:#3f51b5; font-weight:700; margin-bottom:8px; }
.tkg-title { font-size:26px; font-weight:800; color:#1a237e; margin-bottom:16px; line-height:1.25; }
.tkg-subtitle { font-size:15px; color:#666; line-height:1.7; margin-bottom:40px; max-width:640px; }
.tkg-center { text-align:center; }
.tkg-center .tkg-label { display:block; }
.tkg-center .tkg-subtitle { margin:0 auto 40px; }

/* Visi Misi */
.tkg-visimisi-grid { display:grid; grid-template-columns:1fr 1fr; gap:24px; }
.tkg-visimisi-card { background:#fff; border-radius:16px; padding:32px 28px; box-shadow:0 2px 12px rgba(26,35,126,.07); border-top:4px solid #3f51b5; }
.tkg-icon-wrap { width:56px; height:56px; border-radius:14px; background:#e8eaf6; display:flex; align-items:center; justify-content:center; margin-bottom:20px; color:#3f51b5; flex-shrink:0; }
.tkg-icon-wrap svg { width:26px; height:26px; flex-shrink:0; }
.tkg-visimisi-card h3 { font-size:20px; font-weight:800; color:#1a237e; margin-bottom:14px; }
.tkg-visimisi-card p { font-size:14px; color:#555; line-height:1.75; }
.tkg-visimisi-card ul { padding-left:18px; display:flex; flex-direction:column; gap:10px; }
.tkg-visimisi-card ul li { font-size:14px; color:#555; line-height:1.6; }
@media(max-width:768px){ .tkg-visimisi-grid{ grid-template-columns:1fr; } }

/* Tujuan */
.tkg-tujuan-grid { display:grid; grid-template-columns:1fr 1fr; gap:48px; align-items:start; }
.tkg-tujuan-text p { font-size:14px; color:#555; line-height:1.8; margin-bottom:14px; }
.tkg-goal-list { display:flex; flex-direction:column; gap:16px; margin-top:8px; }
.tkg-goal-item { display:flex; gap:16px; align-items:flex-start; padding:16px; background:#f8f9ff; border-radius:10px; border-left:4px solid #3f51b5; }
.tkg-goal-num { font-size:22px; font-weight:900; color:#3f51b5; opacity:.6; min-width:36px; }
.tkg-goal-item > div:last-child { font-size:14px; color:#444; line-height:1.65; }
@media(max-width:768px){ .tkg-tujuan-grid{ grid-template-columns:1fr; gap:32px; } }

/* Fitur */
.tkg-fitur-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:24px; }
.tkg-fitur-card { background:#fff; border-radius:16px; padding:28px 24px; box-shadow:0 2px 12px rgba(26,35,126,.06); transition:transform .25s, box-shadow .25s; }
.tkg-fitur-card:hover { transform:translateY(-5px); box-shadow:0 10px 28px rgba(26,35,126,.12); }
.tkg-fitur-card h4 { font-size:15px; font-weight:700; color:#1a237e; margin:16px 0 10px; }
.tkg-fitur-card p { font-size:13px; color:#666; line-height:1.7; }
.tkg-fitur-icon { width:56px; height:56px; border-radius:14px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.tkg-fitur-icon svg { width:26px; height:26px; flex-shrink:0; }
.tkg-fi-blue   { background:#e8eaf6; color:#3f51b5; }
.tkg-fi-green  { background:#e8f5e9; color:#2e7d32; }
.tkg-fi-orange { background:#fff3e0; color:#e65100; }
.tkg-fi-teal   { background:#e0f2f1; color:#00695c; }
.tkg-fi-pink   { background:#fce4ec; color:#c2185b; }

/* Penyelenggara */
.tkg-penyelenggara { background:linear-gradient(135deg,#e8eaf6 0%,#f3e5f5 100%); border-radius:20px; padding:48px 40px; display:grid; grid-template-columns:180px 1fr; gap:48px; align-items:center; }
.tkg-penyelenggara img { width:160px; height:auto; object-fit:contain; display:block; margin:0 auto; }
.tkg-penyelenggara h2 { font-size:22px; font-weight:800; color:#1a237e; margin:12px 0 16px; line-height:1.3; }
.tkg-penyelenggara p { font-size:14px; color:#555; line-height:1.75; margin-bottom:20px; }
.tkg-kontak-list { display:flex; flex-direction:column; gap:10px; }
.tkg-kontak-item { display:flex; align-items:flex-start; gap:10px; font-size:13px; color:#444; }
.tkg-kontak-item svg { width:16px; height:16px; stroke:#3f51b5; fill:none; stroke-width:2; flex-shrink:0; margin-top:2px; }
@media(max-width:768px){ .tkg-penyelenggara{ grid-template-columns:1fr; text-align:center; padding:32px 20px; gap:24px; } .tkg-kontak-item{ justify-content:center; } }

/* CTA */
.tkg-cta { background:linear-gradient(135deg,#1a237e 0%,#3f51b5 100%); padding:72px 24px; text-align:center; }
.tkg-cta h2 { font-size:28px; font-weight:800; color:#fff; margin-bottom:12px; }
.tkg-cta p { font-size:15px; color:rgba(255,255,255,.85); margin-bottom:32px; line-height:1.7; }
.tkg-cta-btns { display:flex; gap:16px; justify-content:center; flex-wrap:wrap; }
.tkg-btn-outline { padding:13px 28px; border:2px solid #fff; color:#fff; border-radius:8px; text-decoration:none; font-size:15px; font-weight:600; transition:all .2s; }
.tkg-btn-outline:hover { background:#fff; color:#1a237e; }
.tkg-btn-solid { padding:13px 28px; background:#fff; color:#1a237e; border-radius:8px; text-decoration:none; font-size:15px; font-weight:700; border:2px solid #fff; transition:all .2s; }
.tkg-btn-solid:hover { background:transparent; color:#fff; }
</style>

<div class="main-content">

<!-- BREADCRUMB -->
<div class="tkg-breadcrumb">
    <div class="tkg-breadcrumb-inner">
        <a href="<?= $base ?>index.php">Beranda</a>
        <svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
        <span style="color:#333;font-weight:500">Tentang Kami</span>
    </div>
</div>

<!-- HERO -->
<section class="tkg-hero">
    <div class="tkg-hero-inner">
        <div class="tkg-hero-text">
            <div class="tkg-hero-label">Tentang Kami</div>
            <h1>Platform Pelatihan Digital<br><span>Diskominfo Kabupaten Bogor</span></h1>
            <p>Sistem pengelolaan pembelajaran dan pengembangan kompetensi aparatur Pemerintah Kabupaten Bogor yang terintegrasi serta berbasis teknologi informasi dan komunikasi.</p>
        </div>
        <div class="tkg-hero-img">
            <div class="tkg-hero-img-wrap">
                <img src="<?= $base ?>Lambang Kabupaten Bogor - 2025.png" alt="Lambang Kabupaten Bogor">
            </div>
        </div>
    </div>
</section>

<!-- VISI MISI -->
<section class="tkg-section gray">
    <div class="tkg-container">
        <div class="tkg-visimisi-grid">
            <div class="tkg-visimisi-card">
                <div class="tkg-icon-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/>
                        <line x1="21.17" y1="8" x2="12" y2="8"/><line x1="3.95" y1="6.06" x2="8.54" y2="14"/>
                        <line x1="10.88" y1="21.94" x2="15.46" y2="14"/>
                    </svg>
                </div>
                <h3>Visi</h3>
                <p>Menjadi platform pelatihan digital terdepan yang mendukung transformasi kompetensi aparatur Pemerintah Kabupaten Bogor menuju birokrasi yang profesional, inovatif, dan melayani.</p>
            </div>
            <div class="tkg-visimisi-card">
                <div class="tkg-icon-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 11 12 14 22 4"/>
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                    </svg>
                </div>
                <h3>Misi</h3>
                <ul>
                    <li>Menyediakan akses modul pelatihan yang mudah, terpusat, dan terstandarisasi.</li>
                    <li>Meningkatkan kompetensi ASN melalui pembelajaran berbasis teknologi.</li>
                    <li>Mendorong budaya belajar berkelanjutan di lingkungan Pemerintah Kabupaten Bogor.</li>
                    <li>Memastikan akuntabilitas pelaksanaan pelatihan melalui sistem absensi digital.</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- MAKSUD & TUJUAN -->
<section class="tkg-section white">
    <div class="tkg-container">
        <div class="tkg-tujuan-grid">
            <div class="tkg-tujuan-text">
                <div class="tkg-label">Latar Belakang</div>
                <h2 class="tkg-title">Maksud dan Tujuan</h2>
                <p>Maksud dibentuknya <strong>Platform Pelatihan Digital Diskominfo</strong> adalah untuk menerapkan sistem pengelolaan pembelajaran dan pengembangan kompetensi aparatur Pemerintah Kabupaten Bogor yang terintegrasi serta berbasis teknologi informasi dan komunikasi.</p>
                <p>Hal ini memungkinkan Pemerintah Kabupaten Bogor sebagai organisasi menjadi tempat pembelajaran (<em>learning organization</em>) yang berfokus pada pembelajaran strategis dan berdampak langsung terhadap peningkatan kinerja organisasi.</p>
            </div>
            <div>
                <div class="tkg-label">Tujuan</div>
                <h2 class="tkg-title">Tujuan dibentuknya platform ini adalah:</h2>
                <div class="tkg-goal-list">
                    <div class="tkg-goal-item">
                        <div class="tkg-goal-num">01</div>
                        <div>Meningkatkan kompetensi teknis dan manajerial ASN Kabupaten Bogor secara sistematis.</div>
                    </div>
                    <div class="tkg-goal-item">
                        <div class="tkg-goal-num">02</div>
                        <div>Memudahkan akses materi pelatihan kapan saja dan di mana saja secara digital.</div>
                    </div>
                    <div class="tkg-goal-item">
                        <div class="tkg-goal-num">03</div>
                        <div>Mengintegrasikan sistem pendaftaran, absensi, dan evaluasi pelatihan dalam satu platform.</div>
                    </div>
                    <div class="tkg-goal-item">
                        <div class="tkg-goal-num">04</div>
                        <div>Mendukung efisiensi pengelolaan kegiatan pelatihan bagi penyelenggara dan peserta.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FITUR UNGGULAN -->
<section class="tkg-section gray">
    <div class="tkg-container">
        <div class="tkg-center" style="margin-bottom:40px">
            <div class="tkg-label">Keunggulan</div>
            <h2 class="tkg-title" style="margin:8px auto 12px">Fitur Unggulan Platform</h2>
            <p class="tkg-subtitle">Dirancang untuk memudahkan proses pelatihan dari awal hingga akhir.</p>
        </div>
        <div class="tkg-fitur-grid">
            <div class="tkg-fitur-card">
                <div class="tkg-fitur-icon tkg-fi-blue">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                </div>
                <h4>Katalog Modul Digital</h4>
                <p>Kumpulan materi pelatihan dalam format PDF dan PPT yang dapat diakses oleh seluruh peserta terdaftar kapan saja.</p>
            </div>
            <div class="tkg-fitur-card">
                <div class="tkg-fitur-icon tkg-fi-green">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <h4>Pendaftaran Online</h4>
                <p>Sistem pendaftaran pelatihan yang mudah dan cepat. Peserta dapat mendaftar, memantau status, dan membatalkan pendaftaran secara mandiri.</p>
            </div>
            <div class="tkg-fitur-card">
                <div class="tkg-fitur-icon tkg-fi-orange">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="12" y1="8" x2="12" y2="16"/></svg>
                </div>
                <h4>Absensi QR Code</h4>
                <p>Absensi kehadiran peserta dilakukan dengan scan QR Code yang unik per peserta, menjamin keakuratan dan efisiensi pencatatan.</p>
            </div>
            <div class="tkg-fitur-card">
                <div class="tkg-fitur-icon tkg-fi-teal">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                </div>
                <h4>Laporan & Ekspor Data</h4>
                <p>Admin dapat memantau statistik peserta, mengunduh laporan absensi, dan mengelola seluruh data pelatihan secara terpusat.</p>
            </div>
            <div class="tkg-fitur-card">
                <div class="tkg-fitur-icon tkg-fi-pink">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <h4>Akses Aman & Terverifikasi</h4>
                <p>Sistem login dengan verifikasi akun memastikan hanya peserta terdaftar yang dapat mengakses materi dan mengikuti pelatihan.</p>
            </div>
        </div>
    </div>
</section>

<!-- PENYELENGGARA -->
<section class="tkg-section white">
    <div class="tkg-container">
        <div class="tkg-penyelenggara">
            <div style="display:flex;align-items:center;justify-content:center">
                <img src="<?= $base ?>logo diskom.png" alt="Logo Diskominfo Bogor" style="max-width:160px;height:auto;object-fit:contain">
            </div>
            <div>
                <div class="tkg-label">Penyelenggara</div>
                <h2>Dinas Komunikasi dan Informatika<br>Kabupaten Bogor</h2>
                <p>Platform ini dikelola oleh <strong>Dinas Komunikasi dan Informatika (Diskominfo) Kabupaten Bogor</strong> sebagai wujud komitmen dalam mendukung transformasi digital dan peningkatan kapasitas sumber daya manusia aparatur pemerintah daerah.</p>
                <div class="tkg-kontak-list">
                    <div class="tkg-kontak-item">
                        <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        <span>Jl. Tegar Beriman, Cibinong, Kabupaten Bogor, Jawa Barat</span>
                    </div>
                    <div class="tkg-kontak-item">
                        <svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        <span>diskominfo@bogorkab.go.id</span>
                    </div>
                    <div class="tkg-kontak-item">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                        <span>bogorkab.go.id</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="tkg-cta">
    <h2>Siap Memulai Pelatihan?</h2>
    <p>Bergabunglah dengan aparatur yang telah memanfaatkan platform ini untuk meningkatkan kompetensi mereka.</p>
    <div class="tkg-cta-btns">
        <a href="<?= $base ?>pages/katalog.php" class="tkg-btn-outline">Lihat Katalog Modul</a>
        <a href="<?= $base ?>pages/tema.php" class="tkg-btn-solid">Daftar Pelatihan</a>
    </div>
</section>

</div>

<?php require_once '../includes/footer.php'; ?>
