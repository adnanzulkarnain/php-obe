-- ============================================================
-- PHP-OBE DUMMY DATA SQL
-- ============================================================
-- Berisi data dummy lengkap untuk sistem OBE
-- Dapat langsung di-import ke database MySQL/MariaDB
--
-- CARA IMPORT:
-- mysql -u username -p database_name < dummy-data.sql
-- atau via PHPMyAdmin / MySQL Workbench
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. MASTER DATA - Roles & Jenis Penilaian
-- ============================================================

-- Roles
INSERT IGNORE INTO roles (role_name, description) VALUES
('admin', 'System Administrator'),
('kaprodi', 'Ketua Program Studi'),
('dosen', 'Dosen Pengampu'),
('mahasiswa', 'Mahasiswa');

-- Jenis Penilaian
INSERT IGNORE INTO jenis_penilaian (nama_jenis, deskripsi) VALUES
('Quiz', 'Kuis singkat mingguan'),
('Tugas', 'Tugas individu atau kelompok'),
('Praktikum', 'Praktikum atau lab'),
('UTS', 'Ujian Tengah Semester'),
('UAS', 'Ujian Akhir Semester'),
('Project', 'Project akhir');

-- ============================================================
-- 2. FAKULTAS & PROGRAM STUDI
-- ============================================================

-- Fakultas
INSERT IGNORE INTO fakultas (id_fakultas, nama) VALUES
('FTI', 'Fakultas Teknologi Industri'),
('FEB', 'Fakultas Ekonomi dan Bisnis'),
('FT', 'Fakultas Teknik');

-- Program Studi
INSERT IGNORE INTO prodi (id_prodi, id_fakultas, nama, jenjang, akreditasi, tahun_berdiri) VALUES
('TIF', 'FTI', 'Teknik Informatika', 'S1', 'A', 2010),
('SI', 'FTI', 'Sistem Informasi', 'S1', 'B', 2012),
('MAN', 'FEB', 'Manajemen', 'S1', 'A', 2008);

-- ============================================================
-- 3. DOSEN
-- ============================================================

INSERT IGNORE INTO dosen (id_dosen, nidn, nama, email, phone, id_prodi, status) VALUES
('DSN001', '0123456701', 'Dr. Ahmad Santoso, M.Kom', 'ahmad.santoso@univ.ac.id', '081234567001', 'TIF', 'aktif'),
('DSN002', '0123456702', 'Prof. Budi Raharjo, Ph.D', 'budi.raharjo@univ.ac.id', '081234567002', 'TIF', 'aktif'),
('DSN003', '0123456703', 'Dr. Citra Dewi, M.T', 'citra.dewi@univ.ac.id', '081234567003', 'TIF', 'aktif'),
('DSN004', '0123456704', 'Drs. Doni Setiawan, M.Kom', 'doni.setiawan@univ.ac.id', '081234567004', 'TIF', 'aktif'),
('DSN005', '0123456705', 'Eka Putri, S.Kom, M.Sc', 'eka.putri@univ.ac.id', '081234567005', 'TIF', 'aktif'),
('DSN006', '0123456706', 'Dr. Fajar Nugroho, M.Kom', 'fajar.nugroho@univ.ac.id', '081234567006', 'SI', 'aktif');

-- ============================================================
-- 4. KURIKULUM
-- ============================================================

INSERT IGNORE INTO kurikulum (
    id_prodi, kode_kurikulum, nama_kurikulum, tahun_berlaku, tahun_berakhir,
    deskripsi, status, is_primary, nomor_sk, tanggal_sk, created_by, approved_by, approved_at
) VALUES
('TIF', 'K2024', 'Kurikulum OBE 2024', 2024, NULL, 'OBE-based curriculum aligned with MBKM', 'aktif', 1, 'SK/TIF/001/2024', '2024-01-15', 'DSN001', 'DSN002', '2024-01-15'),
('TIF', 'K2020', 'Kurikulum 2020', 2020, 2024, 'Previous curriculum version', 'non-aktif', 0, 'SK/TIF/001/2020', '2020-01-10', 'DSN002', 'DSN002', '2020-01-10'),
('SI', 'K2024', 'Kurikulum SI 2024', 2024, NULL, 'Information Systems OBE curriculum', 'aktif', 1, 'SK/SI/001/2024', '2024-01-20', 'DSN006', 'DSN006', '2024-01-20');

-- ============================================================
-- 5. CPL (Capaian Pembelajaran Lulusan)
-- ============================================================
-- Menggunakan subquery untuk mendapatkan id_kurikulum

INSERT IGNORE INTO cpl (id_kurikulum, kode_cpl, deskripsi, kategori, urutan, is_active)
SELECT id_kurikulum, 'CPL-S1', 'Menunjukkan sikap bertanggung jawab atas pekerjaan di bidang keahliannya secara mandiri', 'sikap', 1, 1
FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';

INSERT IGNORE INTO cpl (id_kurikulum, kode_cpl, deskripsi, kategori, urutan, is_active)
SELECT id_kurikulum, 'CPL-S2', 'Menginternalisasi nilai, norma, dan etika akademik', 'sikap', 2, 1
FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';

INSERT IGNORE INTO cpl (id_kurikulum, kode_cpl, deskripsi, kategori, urutan, is_active)
SELECT id_kurikulum, 'CPL-P1', 'Menguasai konsep teoretis bidang pengetahuan komputer secara umum dan konsep teoretis bagian khusus dalam bidang pengetahuan tersebut secara mendalam', 'pengetahuan', 3, 1
FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';

INSERT IGNORE INTO cpl (id_kurikulum, kode_cpl, deskripsi, kategori, urutan, is_active)
SELECT id_kurikulum, 'CPL-P2', 'Menguasai pengetahuan tentang algoritma, pemrograman, struktur data, basis data, dan matematika diskrit', 'pengetahuan', 4, 1
FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';

INSERT IGNORE INTO cpl (id_kurikulum, kode_cpl, deskripsi, kategori, urutan, is_active)
SELECT id_kurikulum, 'CPL-KU1', 'Mampu menerapkan pemikiran logis, kritis, sistematis, dan inovatif dalam konteks pengembangan atau implementasi ilmu pengetahuan dan teknologi', 'keterampilan_umum', 5, 1
FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';

INSERT IGNORE INTO cpl (id_kurikulum, kode_cpl, deskripsi, kategori, urutan, is_active)
SELECT id_kurikulum, 'CPL-KU2', 'Mampu menunjukkan kinerja mandiri, bermutu, dan terukur', 'keterampilan_umum', 6, 1
FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';

INSERT IGNORE INTO cpl (id_kurikulum, kode_cpl, deskripsi, kategori, urutan, is_active)
SELECT id_kurikulum, 'CPL-KK1', 'Mampu merancang, mengimplementasi, dan mengevaluasi sistem berbasis komputer', 'keterampilan_khusus', 7, 1
FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';

INSERT IGNORE INTO cpl (id_kurikulum, kode_cpl, deskripsi, kategori, urutan, is_active)
SELECT id_kurikulum, 'CPL-KK2', 'Mampu menganalisis permasalahan komputasi kompleks dan menerapkan prinsip-prinsip komputasi untuk mengidentifikasi solusi', 'keterampilan_khusus', 8, 1
FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';

INSERT IGNORE INTO cpl (id_kurikulum, kode_cpl, deskripsi, kategori, urutan, is_active)
SELECT id_kurikulum, 'CPL-KK3', 'Mampu merancang solusi untuk masalah komputasi kompleks dan merancang serta mengevaluasi sistem berbasis komputer', 'keterampilan_khusus', 9, 1
FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';

-- ============================================================
-- 6. MATA KULIAH
-- ============================================================

INSERT IGNORE INTO matakuliah (kode_mk, id_kurikulum, nama_mk, nama_mk_eng, sks, semester, rumpun, jenis_mk, is_active)
SELECT 'TIF101', id_kurikulum, 'Algoritma dan Pemrograman', 'Algorithm and Programming', 4, 1, 'Pemrograman', 'wajib', 1
FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';

INSERT IGNORE INTO matakuliah (kode_mk, id_kurikulum, nama_mk, nama_mk_eng, sks, semester, rumpun, jenis_mk, is_active)
SELECT 'TIF102', id_kurikulum, 'Matematika Diskrit', 'Discrete Mathematics', 3, 1, 'Matematika', 'wajib', 1
FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';

INSERT IGNORE INTO matakuliah (kode_mk, id_kurikulum, nama_mk, nama_mk_eng, sks, semester, rumpun, jenis_mk, is_active)
SELECT 'TIF103', id_kurikulum, 'Pengantar Teknologi Informasi', 'Introduction to Information Technology', 3, 1, 'Dasar TI', 'wajib', 1
FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';

INSERT IGNORE INTO matakuliah (kode_mk, id_kurikulum, nama_mk, nama_mk_eng, sks, semester, rumpun, jenis_mk, is_active)
SELECT 'TIF201', id_kurikulum, 'Struktur Data', 'Data Structures', 4, 2, 'Pemrograman', 'wajib', 1
FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';

INSERT IGNORE INTO matakuliah (kode_mk, id_kurikulum, nama_mk, nama_mk_eng, sks, semester, rumpun, jenis_mk, is_active)
SELECT 'TIF202', id_kurikulum, 'Basis Data', 'Database', 4, 2, 'Sistem Informasi', 'wajib', 1
FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';

INSERT IGNORE INTO matakuliah (kode_mk, id_kurikulum, nama_mk, nama_mk_eng, sks, semester, rumpun, jenis_mk, is_active)
SELECT 'TIF203', id_kurikulum, 'Pemrograman Berorientasi Objek', 'Object-Oriented Programming', 4, 2, 'Pemrograman', 'wajib', 1
FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';

INSERT IGNORE INTO matakuliah (kode_mk, id_kurikulum, nama_mk, nama_mk_eng, sks, semester, rumpun, jenis_mk, is_active)
SELECT 'TIF301', id_kurikulum, 'Rekayasa Perangkat Lunak', 'Software Engineering', 4, 3, 'RPL', 'wajib', 1
FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';

INSERT IGNORE INTO matakuliah (kode_mk, id_kurikulum, nama_mk, nama_mk_eng, sks, semester, rumpun, jenis_mk, is_active)
SELECT 'TIF302', id_kurikulum, 'Desain dan Analisis Algoritma', 'Algorithm Design and Analysis', 3, 3, 'Algoritma', 'wajib', 1
FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';

INSERT IGNORE INTO matakuliah (kode_mk, id_kurikulum, nama_mk, nama_mk_eng, sks, semester, rumpun, jenis_mk, is_active)
SELECT 'TIF303', id_kurikulum, 'Pemrograman Web', 'Web Programming', 4, 3, 'Pemrograman', 'wajib', 1
FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';

INSERT IGNORE INTO matakuliah (kode_mk, id_kurikulum, nama_mk, nama_mk_eng, sks, semester, rumpun, jenis_mk, is_active)
SELECT 'TIF401', id_kurikulum, 'Kecerdasan Buatan', 'Artificial Intelligence', 3, 4, 'AI', 'pilihan', 1
FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';

-- ============================================================
-- 7. PRASYARAT MATA KULIAH
-- ============================================================

INSERT INTO prasyarat_mk (kode_mk, id_kurikulum, kode_mk_prasyarat, jenis_prasyarat)
SELECT 'TIF201', id_kurikulum, 'TIF101', 'wajib'
FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';

INSERT INTO prasyarat_mk (kode_mk, id_kurikulum, kode_mk_prasyarat, jenis_prasyarat)
SELECT 'TIF203', id_kurikulum, 'TIF101', 'wajib'
FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';

INSERT INTO prasyarat_mk (kode_mk, id_kurikulum, kode_mk_prasyarat, jenis_prasyarat)
SELECT 'TIF301', id_kurikulum, 'TIF201', 'wajib'
FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';

INSERT INTO prasyarat_mk (kode_mk, id_kurikulum, kode_mk_prasyarat, jenis_prasyarat)
SELECT 'TIF301', id_kurikulum, 'TIF202', 'wajib'
FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';

INSERT INTO prasyarat_mk (kode_mk, id_kurikulum, kode_mk_prasyarat, jenis_prasyarat)
SELECT 'TIF302', id_kurikulum, 'TIF201', 'wajib'
FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';

INSERT INTO prasyarat_mk (kode_mk, id_kurikulum, kode_mk_prasyarat, jenis_prasyarat)
SELECT 'TIF303', id_kurikulum, 'TIF203', 'wajib'
FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';

-- ============================================================
-- 8. RPS (Rencana Pembelajaran Semester)
-- ============================================================

INSERT INTO rps (kode_mk, id_kurikulum, semester_berlaku, tahun_ajaran, status, ketua_pengembang, tanggal_disusun, deskripsi_mk, deskripsi_singkat, created_by)
SELECT 'TIF301', id_kurikulum, 'Ganjil', '2024/2025', 'approved', 'DSN001', '2024-08-01',
       'Mata kuliah ini membahas prinsip, konsep, dan teknik rekayasa perangkat lunak modern',
       'Pembelajaran RPL dengan pendekatan agile dan waterfall', 'DSN001'
FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';

INSERT INTO rps (kode_mk, id_kurikulum, semester_berlaku, tahun_ajaran, status, ketua_pengembang, tanggal_disusun, deskripsi_mk, deskripsi_singkat, created_by)
SELECT 'TIF303', id_kurikulum, 'Ganjil', '2024/2025', 'approved', 'DSN003', '2024-08-01',
       'Mata kuliah ini membahas pengembangan aplikasi web modern menggunakan framework terkini',
       'Pembelajaran web programming dengan project-based learning', 'DSN003'
FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';

INSERT INTO rps (kode_mk, id_kurikulum, semester_berlaku, tahun_ajaran, status, ketua_pengembang, tanggal_disusun, deskripsi_mk, deskripsi_singkat, created_by)
SELECT 'TIF101', id_kurikulum, 'Ganjil', '2024/2025', 'approved', 'DSN002', '2024-08-01',
       'Mata kuliah dasar pemrograman dengan fokus pada algoritma dan logika',
       'Pembelajaran fundamental programming', 'DSN002'
FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';

-- ============================================================
-- 9. CPMK (Capaian Pembelajaran Mata Kuliah)
-- ============================================================

-- CPMK untuk RPL (TIF301)
INSERT INTO cpmk (id_rps, kode_cpmk, deskripsi, urutan)
SELECT id_rps, 'CPMK-1', 'Mahasiswa mampu memahami konsep dasar rekayasa perangkat lunak', 1
FROM rps WHERE kode_mk = 'TIF301' AND tahun_ajaran = '2024/2025';

INSERT INTO cpmk (id_rps, kode_cpmk, deskripsi, urutan)
SELECT id_rps, 'CPMK-2', 'Mahasiswa mampu menganalisis kebutuhan sistem', 2
FROM rps WHERE kode_mk = 'TIF301' AND tahun_ajaran = '2024/2025';

INSERT INTO cpmk (id_rps, kode_cpmk, deskripsi, urutan)
SELECT id_rps, 'CPMK-3', 'Mahasiswa mampu merancang arsitektur perangkat lunak', 3
FROM rps WHERE kode_mk = 'TIF301' AND tahun_ajaran = '2024/2025';

INSERT INTO cpmk (id_rps, kode_cpmk, deskripsi, urutan)
SELECT id_rps, 'CPMK-4', 'Mahasiswa mampu mengimplementasikan sistem dengan metodologi yang tepat', 4
FROM rps WHERE kode_mk = 'TIF301' AND tahun_ajaran = '2024/2025';

INSERT INTO cpmk (id_rps, kode_cpmk, deskripsi, urutan)
SELECT id_rps, 'CPMK-5', 'Mahasiswa mampu melakukan testing dan quality assurance', 5
FROM rps WHERE kode_mk = 'TIF301' AND tahun_ajaran = '2024/2025';

-- CPMK untuk Web Programming (TIF303)
INSERT INTO cpmk (id_rps, kode_cpmk, deskripsi, urutan)
SELECT id_rps, 'CPMK-1', 'Mahasiswa mampu memahami arsitektur aplikasi web', 1
FROM rps WHERE kode_mk = 'TIF303' AND tahun_ajaran = '2024/2025';

INSERT INTO cpmk (id_rps, kode_cpmk, deskripsi, urutan)
SELECT id_rps, 'CPMK-2', 'Mahasiswa mampu mengimplementasikan frontend dengan HTML, CSS, JavaScript', 2
FROM rps WHERE kode_mk = 'TIF303' AND tahun_ajaran = '2024/2025';

INSERT INTO cpmk (id_rps, kode_cpmk, deskripsi, urutan)
SELECT id_rps, 'CPMK-3', 'Mahasiswa mampu mengimplementasikan backend dengan PHP/Node.js', 3
FROM rps WHERE kode_mk = 'TIF303' AND tahun_ajaran = '2024/2025';

INSERT INTO cpmk (id_rps, kode_cpmk, deskripsi, urutan)
SELECT id_rps, 'CPMK-4', 'Mahasiswa mampu mengintegrasikan frontend dan backend', 4
FROM rps WHERE kode_mk = 'TIF303' AND tahun_ajaran = '2024/2025';

-- CPMK untuk Algoritma (TIF101)
INSERT INTO cpmk (id_rps, kode_cpmk, deskripsi, urutan)
SELECT id_rps, 'CPMK-1', 'Mahasiswa mampu memahami konsep dasar algoritma', 1
FROM rps WHERE kode_mk = 'TIF101' AND tahun_ajaran = '2024/2025';

INSERT INTO cpmk (id_rps, kode_cpmk, deskripsi, urutan)
SELECT id_rps, 'CPMK-2', 'Mahasiswa mampu merancang algoritma untuk menyelesaikan masalah', 2
FROM rps WHERE kode_mk = 'TIF101' AND tahun_ajaran = '2024/2025';

INSERT INTO cpmk (id_rps, kode_cpmk, deskripsi, urutan)
SELECT id_rps, 'CPMK-3', 'Mahasiswa mampu mengimplementasikan algoritma dalam bahasa pemrograman', 3
FROM rps WHERE kode_mk = 'TIF101' AND tahun_ajaran = '2024/2025';

-- ============================================================
-- 10. SUB-CPMK
-- ============================================================

INSERT INTO subcpmk (id_cpmk, kode_subcpmk, deskripsi, indikator, urutan)
SELECT c.id_cpmk, 'SubCPMK-1.1', 'Menjelaskan definisi dan ruang lingkup RPL', 'Mampu mendefinisikan RPL dengan benar', 1
FROM cpmk c
JOIN rps r ON c.id_rps = r.id_rps
WHERE r.kode_mk = 'TIF301' AND r.tahun_ajaran = '2024/2025' AND c.kode_cpmk = 'CPMK-1';

INSERT INTO subcpmk (id_cpmk, kode_subcpmk, deskripsi, indikator, urutan)
SELECT c.id_cpmk, 'SubCPMK-1.2', 'Mengidentifikasi tahapan dalam siklus hidup perangkat lunak', 'Mampu menyebutkan minimal 5 tahapan SDLC', 2
FROM cpmk c
JOIN rps r ON c.id_rps = r.id_rps
WHERE r.kode_mk = 'TIF301' AND r.tahun_ajaran = '2024/2025' AND c.kode_cpmk = 'CPMK-1';

INSERT INTO subcpmk (id_cpmk, kode_subcpmk, deskripsi, indikator, urutan)
SELECT c.id_cpmk, 'SubCPMK-1.3', 'Membandingkan berbagai model proses pengembangan', 'Mampu membandingkan waterfall, agile, dan spiral', 3
FROM cpmk c
JOIN rps r ON c.id_rps = r.id_rps
WHERE r.kode_mk = 'TIF301' AND r.tahun_ajaran = '2024/2025' AND c.kode_cpmk = 'CPMK-1';

-- ============================================================
-- 11. RELASI CPMK-CPL
-- ============================================================

INSERT IGNORE INTO relasi_cpmk_cpl (id_cpmk, id_cpl, bobot_kontribusi)
SELECT c.id_cpmk, cpl.id_cpl, 80.00
FROM cpmk c
JOIN rps r ON c.id_rps = r.id_rps
JOIN cpl ON cpl.id_kurikulum = r.id_kurikulum
WHERE r.kode_mk = 'TIF301' AND r.tahun_ajaran = '2024/2025' AND cpl.kode_cpl = 'CPL-KK1';

INSERT IGNORE INTO relasi_cpmk_cpl (id_cpmk, id_cpl, bobot_kontribusi)
SELECT c.id_cpmk, cpl.id_cpl, 60.00
FROM cpmk c
JOIN rps r ON c.id_rps = r.id_rps
JOIN cpl ON cpl.id_kurikulum = r.id_kurikulum
WHERE r.kode_mk = 'TIF301' AND r.tahun_ajaran = '2024/2025' AND cpl.kode_cpl = 'CPL-P1';

-- ============================================================
-- 12. KELAS
-- ============================================================

INSERT IGNORE INTO kelas (kode_mk, id_kurikulum, id_rps, nama_kelas, semester, tahun_ajaran, kapasitas, kuota_terisi, hari, jam_mulai, jam_selesai, ruangan, status)
SELECT r.kode_mk, r.id_kurikulum, r.id_rps, 'A', 'Ganjil', '2024/2025', 40, 35, 'Senin', '08:00:00', '10:30:00', 'R.301', 'open'
FROM rps r WHERE r.tahun_ajaran = '2024/2025' AND r.kode_mk = 'TIF301';

INSERT IGNORE INTO kelas (kode_mk, id_kurikulum, id_rps, nama_kelas, semester, tahun_ajaran, kapasitas, kuota_terisi, hari, jam_mulai, jam_selesai, ruangan, status)
SELECT r.kode_mk, r.id_kurikulum, r.id_rps, 'B', 'Ganjil', '2024/2025', 40, 32, 'Selasa', '13:00:00', '15:30:00', 'R.302', 'open'
FROM rps r WHERE r.tahun_ajaran = '2024/2025' AND r.kode_mk = 'TIF301';

INSERT IGNORE INTO kelas (kode_mk, id_kurikulum, id_rps, nama_kelas, semester, tahun_ajaran, kapasitas, kuota_terisi, hari, jam_mulai, jam_selesai, ruangan, status)
SELECT r.kode_mk, r.id_kurikulum, r.id_rps, 'A', 'Ganjil', '2024/2025', 40, 35, 'Senin', '08:00:00', '10:30:00', 'R.301', 'open'
FROM rps r WHERE r.tahun_ajaran = '2024/2025' AND r.kode_mk = 'TIF303';

INSERT IGNORE INTO kelas (kode_mk, id_kurikulum, id_rps, nama_kelas, semester, tahun_ajaran, kapasitas, kuota_terisi, hari, jam_mulai, jam_selesai, ruangan, status)
SELECT r.kode_mk, r.id_kurikulum, r.id_rps, 'B', 'Ganjil', '2024/2025', 40, 32, 'Selasa', '13:00:00', '15:30:00', 'R.302', 'open'
FROM rps r WHERE r.tahun_ajaran = '2024/2025' AND r.kode_mk = 'TIF303';

INSERT IGNORE INTO kelas (kode_mk, id_kurikulum, id_rps, nama_kelas, semester, tahun_ajaran, kapasitas, kuota_terisi, hari, jam_mulai, jam_selesai, ruangan, status)
SELECT r.kode_mk, r.id_kurikulum, r.id_rps, 'A', 'Ganjil', '2024/2025', 40, 35, 'Senin', '08:00:00', '10:30:00', 'R.301', 'open'
FROM rps r WHERE r.tahun_ajaran = '2024/2025' AND r.kode_mk = 'TIF101';

INSERT IGNORE INTO kelas (kode_mk, id_kurikulum, id_rps, nama_kelas, semester, tahun_ajaran, kapasitas, kuota_terisi, hari, jam_mulai, jam_selesai, ruangan, status)
SELECT r.kode_mk, r.id_kurikulum, r.id_rps, 'B', 'Ganjil', '2024/2025', 40, 32, 'Selasa', '13:00:00', '15:30:00', 'R.302', 'open'
FROM rps r WHERE r.tahun_ajaran = '2024/2025' AND r.kode_mk = 'TIF101';

-- ============================================================
-- 13. TUGAS MENGAJAR
-- ============================================================

INSERT IGNORE INTO tugas_mengajar (id_kelas, id_dosen, peran)
SELECT id_kelas, 'DSN001', 'koordinator' FROM kelas WHERE kode_mk = 'TIF301' AND nama_kelas = 'A';

INSERT IGNORE INTO tugas_mengajar (id_kelas, id_dosen, peran)
SELECT id_kelas, 'DSN002', 'pengampu' FROM kelas WHERE kode_mk = 'TIF301' AND nama_kelas = 'A';

INSERT IGNORE INTO tugas_mengajar (id_kelas, id_dosen, peran)
SELECT id_kelas, 'DSN003', 'koordinator' FROM kelas WHERE kode_mk = 'TIF303' AND nama_kelas = 'A';

INSERT IGNORE INTO tugas_mengajar (id_kelas, id_dosen, peran)
SELECT id_kelas, 'DSN004', 'pengampu' FROM kelas WHERE kode_mk = 'TIF303' AND nama_kelas = 'A';

INSERT IGNORE INTO tugas_mengajar (id_kelas, id_dosen, peran)
SELECT id_kelas, 'DSN002', 'koordinator' FROM kelas WHERE kode_mk = 'TIF101' AND nama_kelas = 'A';

INSERT IGNORE INTO tugas_mengajar (id_kelas, id_dosen, peran)
SELECT id_kelas, 'DSN005', 'pengampu' FROM kelas WHERE kode_mk = 'TIF101' AND nama_kelas = 'A';

-- ============================================================
-- 14. MAHASISWA
-- ============================================================

INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100001', 'Mahasiswa 1', 'mhs1@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif'
FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';

INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100002', 'Mahasiswa 2', 'mhs2@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif'
FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';

INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100003', 'Mahasiswa 3', 'mhs3@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif'
FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';

INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100004', 'Mahasiswa 4', 'mhs4@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif'
FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';

INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100005', 'Mahasiswa 5', 'mhs5@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif'
FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';

INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100006', 'Mahasiswa 6', 'mhs6@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif'
FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';

INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100007', 'Mahasiswa 7', 'mhs7@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif'
FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';

INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100008', 'Mahasiswa 8', 'mhs8@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif'
FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';

INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100009', 'Mahasiswa 9', 'mhs9@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif'
FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';

INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100010', 'Mahasiswa 10', 'mhs10@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif'
FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';

-- Insert mahasiswa 11-50
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100011', 'Mahasiswa 11', 'mhs11@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100012', 'Mahasiswa 12', 'mhs12@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100013', 'Mahasiswa 13', 'mhs13@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100014', 'Mahasiswa 14', 'mhs14@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100015', 'Mahasiswa 15', 'mhs15@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100016', 'Mahasiswa 16', 'mhs16@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100017', 'Mahasiswa 17', 'mhs17@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100018', 'Mahasiswa 18', 'mhs18@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100019', 'Mahasiswa 19', 'mhs19@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100020', 'Mahasiswa 20', 'mhs20@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100021', 'Mahasiswa 21', 'mhs21@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100022', 'Mahasiswa 22', 'mhs22@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100023', 'Mahasiswa 23', 'mhs23@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100024', 'Mahasiswa 24', 'mhs24@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100025', 'Mahasiswa 25', 'mhs25@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100026', 'Mahasiswa 26', 'mhs26@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100027', 'Mahasiswa 27', 'mhs27@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100028', 'Mahasiswa 28', 'mhs28@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100029', 'Mahasiswa 29', 'mhs29@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100030', 'Mahasiswa 30', 'mhs30@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100031', 'Mahasiswa 31', 'mhs31@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100032', 'Mahasiswa 32', 'mhs32@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100033', 'Mahasiswa 33', 'mhs33@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100034', 'Mahasiswa 34', 'mhs34@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100035', 'Mahasiswa 35', 'mhs35@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100036', 'Mahasiswa 36', 'mhs36@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100037', 'Mahasiswa 37', 'mhs37@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100038', 'Mahasiswa 38', 'mhs38@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100039', 'Mahasiswa 39', 'mhs39@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100040', 'Mahasiswa 40', 'mhs40@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100041', 'Mahasiswa 41', 'mhs41@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100042', 'Mahasiswa 42', 'mhs42@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100043', 'Mahasiswa 43', 'mhs43@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100044', 'Mahasiswa 44', 'mhs44@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100045', 'Mahasiswa 45', 'mhs45@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100046', 'Mahasiswa 46', 'mhs46@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100047', 'Mahasiswa 47', 'mhs47@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100048', 'Mahasiswa 48', 'mhs48@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100049', 'Mahasiswa 49', 'mhs49@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';
INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
SELECT '20240100050', 'Mahasiswa 50', 'mhs50@student.univ.ac.id', 'TIF', id_kurikulum, '2024', 'aktif' FROM kurikulum WHERE kode_kurikulum = 'K2024' AND id_prodi = 'TIF';

-- ============================================================
-- 15. ENROLLMENT (Pendaftaran Mahasiswa ke Kelas)
-- ============================================================

-- Enrollment untuk 35 mahasiswa pertama ke berbagai kelas
INSERT IGNORE INTO enrollment (nim, id_kelas, tanggal_daftar, status, nilai_akhir, nilai_huruf)
SELECT m.nim, k.id_kelas, '2024-08-15', 'aktif', 85.5, 'A'
FROM mahasiswa m, kelas k
WHERE m.nim IN ('20240100001', '20240100002', '20240100003', '20240100004', '20240100005')
  AND k.kode_mk = 'TIF301' AND k.nama_kelas = 'A';

INSERT IGNORE INTO enrollment (nim, id_kelas, tanggal_daftar, status, nilai_akhir, nilai_huruf)
SELECT m.nim, k.id_kelas, '2024-08-15', 'aktif', 78.0, 'B+'
FROM mahasiswa m, kelas k
WHERE m.nim IN ('20240100006', '20240100007', '20240100008', '20240100009', '20240100010')
  AND k.kode_mk = 'TIF301' AND k.nama_kelas = 'A';

INSERT IGNORE INTO enrollment (nim, id_kelas, tanggal_daftar, status, nilai_akhir, nilai_huruf)
SELECT m.nim, k.id_kelas, '2024-08-15', 'aktif', 82.0, 'A-'
FROM mahasiswa m, kelas k
WHERE m.nim IN ('20240100011', '20240100012', '20240100013', '20240100014', '20240100015')
  AND k.kode_mk = 'TIF303' AND k.nama_kelas = 'A';

INSERT IGNORE INTO enrollment (nim, id_kelas, tanggal_daftar, status, nilai_akhir, nilai_huruf)
SELECT m.nim, k.id_kelas, '2024-08-15', 'aktif', 88.5, 'A'
FROM mahasiswa m, kelas k
WHERE m.nim IN ('20240100016', '20240100017', '20240100018', '20240100019', '20240100020')
  AND k.kode_mk = 'TIF101' AND k.nama_kelas = 'A';

INSERT IGNORE INTO enrollment (nim, id_kelas, tanggal_daftar, status, nilai_akhir, nilai_huruf)
SELECT m.nim, k.id_kelas, '2024-08-15', 'aktif', 72.0, 'B'
FROM mahasiswa m, kelas k
WHERE m.nim IN ('20240100021', '20240100022', '20240100023', '20240100024', '20240100025')
  AND k.kode_mk = 'TIF101' AND k.nama_kelas = 'A';

-- ============================================================
-- 16. USERS (Akun Login)
-- ============================================================
-- Password hash untuk: admin123, kaprodi123, dosen123, mhs123

INSERT IGNORE INTO users (username, email, password_hash, user_type, ref_id, is_active) VALUES
('admin', 'admin@univ.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, 1),
('kaprodi_tif', 'kaprodi.tif@univ.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'kaprodi', 'DSN001', 1),
('dosen1', 'dosen1@univ.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'dosen', 'DSN002', 1),
('dosen2', 'dosen2@univ.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'dosen', 'DSN003', 1),
('20240100001', 'mhs1@student.univ.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mahasiswa', '20240100001', 1),
('20240100002', 'mhs2@student.univ.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mahasiswa', '20240100002', 1);

-- ============================================================
-- 17. TEMPLATE PENILAIAN
-- ============================================================

INSERT INTO template_penilaian (id_rps, id_cpmk, id_jenis, bobot)
SELECT r.id_rps, c.id_cpmk, 1, 10.00  -- Quiz 10%
FROM rps r
JOIN cpmk c ON c.id_rps = r.id_rps
WHERE r.kode_mk = 'TIF301' AND r.tahun_ajaran = '2024/2025' AND c.kode_cpmk = 'CPMK-1';

INSERT INTO template_penilaian (id_rps, id_cpmk, id_jenis, bobot)
SELECT r.id_rps, c.id_cpmk, 2, 20.00  -- Tugas 20%
FROM rps r
JOIN cpmk c ON c.id_rps = r.id_rps
WHERE r.kode_mk = 'TIF301' AND r.tahun_ajaran = '2024/2025' AND c.kode_cpmk = 'CPMK-1';

INSERT INTO template_penilaian (id_rps, id_cpmk, id_jenis, bobot)
SELECT r.id_rps, c.id_cpmk, 4, 30.00  -- UTS 30%
FROM rps r
JOIN cpmk c ON c.id_rps = r.id_rps
WHERE r.kode_mk = 'TIF301' AND r.tahun_ajaran = '2024/2025' AND c.kode_cpmk = 'CPMK-1';

INSERT INTO template_penilaian (id_rps, id_cpmk, id_jenis, bobot)
SELECT r.id_rps, c.id_cpmk, 5, 40.00  -- UAS 40%
FROM rps r
JOIN cpmk c ON c.id_rps = r.id_rps
WHERE r.kode_mk = 'TIF301' AND r.tahun_ajaran = '2024/2025' AND c.kode_cpmk = 'CPMK-1';

-- ============================================================
-- 18. KOMPONEN PENILAIAN
-- ============================================================

INSERT INTO komponen_penilaian (id_kelas, id_template, nama_komponen, deskripsi, tanggal_pelaksanaan, deadline, bobot_realisasi, nilai_maksimal)
SELECT k.id_kelas, tp.id_template, 'Quiz Minggu 1', 'Quiz tentang konsep dasar RPL', '2024-09-10', '2024-09-10', 10.00, 100.00
FROM kelas k
JOIN template_penilaian tp ON tp.id_rps = k.id_rps
WHERE k.kode_mk = 'TIF301' AND k.nama_kelas = 'A'
LIMIT 1;

-- ============================================================
-- 19. NILAI DETAIL
-- ============================================================

INSERT IGNORE INTO nilai_detail (id_enrollment, id_komponen, nilai_mentah, dinilai_oleh)
SELECT e.id_enrollment, kp.id_komponen, 85.0, 'DSN001'
FROM enrollment e
JOIN kelas k ON e.id_kelas = k.id_kelas
JOIN komponen_penilaian kp ON kp.id_kelas = k.id_kelas
WHERE k.kode_mk = 'TIF301' AND k.nama_kelas = 'A'
LIMIT 10;

-- ============================================================
-- 20. KETERCAPAIAN CPMK
-- ============================================================

INSERT IGNORE INTO ketercapaian_cpmk (id_enrollment, id_cpmk, nilai_cpmk, status_tercapai)
SELECT e.id_enrollment, c.id_cpmk, 82.5, 1
FROM enrollment e
JOIN kelas k ON e.id_kelas = k.id_kelas
JOIN cpmk c ON c.id_rps = k.id_rps
WHERE k.kode_mk = 'TIF301'
LIMIT 20;

-- ============================================================
-- 21. RENCANA MINGGUAN
-- ============================================================

INSERT IGNORE INTO rencana_mingguan (id_rps, minggu_ke, id_subcpmk, materi, metode, aktivitas, media_software, media_hardware, pengalaman_belajar, estimasi_waktu_menit)
SELECT r.id_rps, 1, s.id_subcpmk,
       '["Pengenalan RPL", "Konsep Dasar Software Engineering"]',
       '["Ceramah", "Diskusi", "Studi Kasus"]',
       '["Presentasi", "Quiz", "Diskusi Kelompok"]',
       'PowerPoint, Video Tutorial',
       'Proyektor, Laptop, Whiteboard',
       'Mahasiswa belajar melalui diskusi kelompok dan studi kasus nyata',
       150
FROM rps r
JOIN cpmk c ON c.id_rps = r.id_rps
JOIN subcpmk s ON s.id_cpmk = c.id_cpmk
WHERE r.kode_mk = 'TIF301' AND r.tahun_ajaran = '2024/2025'
LIMIT 1;

INSERT IGNORE INTO rencana_mingguan (id_rps, minggu_ke, id_subcpmk, materi, metode, aktivitas, media_software, media_hardware, pengalaman_belajar, estimasi_waktu_menit)
SELECT r.id_rps, 2, s.id_subcpmk,
       '["SDLC", "Software Development Life Cycle"]',
       '["Ceramah", "Praktikum", "Demonstrasi"]',
       '["Latihan", "Presentasi Kelompok"]',
       'PowerPoint, IDE',
       'Proyektor, Laptop',
       'Mahasiswa mempraktikkan tahapan SDLC dengan project kecil',
       150
FROM rps r
JOIN cpmk c ON c.id_rps = r.id_rps
JOIN subcpmk s ON s.id_cpmk = c.id_cpmk
WHERE r.kode_mk = 'TIF301' AND r.tahun_ajaran = '2024/2025'
LIMIT 1;

-- ============================================================
-- 22. PUSTAKA (Referensi)
-- ============================================================

INSERT INTO pustaka (id_rps, jenis, referensi, penulis, tahun, penerbit, isbn, url)
SELECT id_rps, 'utama', 'Software Engineering: A Practitioner\'s Approach', 'Roger S. Pressman', 2019, 'McGraw-Hill', '9780078022128', NULL
FROM rps WHERE kode_mk = 'TIF301' AND tahun_ajaran = '2024/2025' LIMIT 1;

INSERT INTO pustaka (id_rps, jenis, referensi, penulis, tahun, penerbit, isbn, url)
SELECT id_rps, 'utama', 'Clean Code: A Handbook of Agile Software Craftsmanship', 'Robert C. Martin', 2008, 'Prentice Hall', '9780132350884', NULL
FROM rps WHERE kode_mk = 'TIF301' AND tahun_ajaran = '2024/2025' LIMIT 1;

INSERT INTO pustaka (id_rps, jenis, referensi, penulis, tahun, penerbit, isbn, url)
SELECT id_rps, 'pendukung', 'Design Patterns: Elements of Reusable Object-Oriented Software', 'Gang of Four', 1994, 'Addison-Wesley', '9780201633610', NULL
FROM rps WHERE kode_mk = 'TIF301' AND tahun_ajaran = '2024/2025' LIMIT 1;

INSERT INTO pustaka (id_rps, jenis, referensi, penulis, tahun, penerbit, isbn, url)
SELECT id_rps, 'pendukung', 'The Mythical Man-Month', 'Frederick P. Brooks Jr.', 1995, 'Addison-Wesley', '9780201835953', NULL
FROM rps WHERE kode_mk = 'TIF301' AND tahun_ajaran = '2024/2025' LIMIT 1;

-- ============================================================
-- 23. AMBANG BATAS
-- ============================================================

INSERT IGNORE INTO ambang_batas (id_rps, batas_kelulusan_cpmk, batas_kelulusan_mk, persentase_mahasiswa_lulus)
SELECT id_rps, 40.01, 50.00, 75.00
FROM rps WHERE tahun_ajaran = '2024/2025';

-- ============================================================
-- 24. REALISASI PERTEMUAN (Berita Acara Kuliah)
-- ============================================================

INSERT INTO realisasi_pertemuan (id_kelas, id_minggu, tanggal_pelaksanaan, materi_disampaikan, metode_digunakan, kendala, catatan_dosen, status, verified_by, verified_at, komentar_kaprodi, created_by, created_at)
SELECT k.id_kelas, rm.id_minggu, '2024-09-02',
       'Pengenalan RPL dan Konsep Dasar',
       'Ceramah, Diskusi Kelompok',
       NULL,
       'Mahasiswa cukup aktif dalam diskusi. Materi tersampaikan dengan baik.',
       'verified',
       'DSN001',
       '2024-09-04 10:00:00',
       'Berita acara sudah sesuai dengan RPS. Metode pembelajaran sudah baik.',
       'DSN001',
       '2024-09-02 10:30:00'
FROM kelas k
JOIN rencana_mingguan rm ON rm.id_rps = k.id_rps
WHERE k.kode_mk = 'TIF301' AND k.nama_kelas = 'A' AND rm.minggu_ke = 1
LIMIT 1;

INSERT INTO realisasi_pertemuan (id_kelas, id_minggu, tanggal_pelaksanaan, materi_disampaikan, metode_digunakan, kendala, catatan_dosen, status, verified_by, verified_at, komentar_kaprodi, created_by, created_at)
SELECT k.id_kelas, rm.id_minggu, '2024-09-09',
       'SDLC dan Model Pengembangan Software',
       'Ceramah, Praktikum, Studi Kasus',
       'Proyektor sempat bermasalah di awal perkuliahan',
       'Materi tentang SDLC disampaikan dengan studi kasus. Quiz dilakukan di akhir sesi.',
       'submitted',
       NULL,
       NULL,
       NULL,
       'DSN001',
       '2024-09-09 10:30:00'
FROM kelas k
JOIN rencana_mingguan rm ON rm.id_rps = k.id_rps
WHERE k.kode_mk = 'TIF301' AND k.nama_kelas = 'A' AND rm.minggu_ke = 2
LIMIT 1;

-- ============================================================
-- 25. KEHADIRAN (Attendance)
-- ============================================================

INSERT INTO kehadiran (id_realisasi, nim, status, keterangan)
SELECT rp.id_realisasi, e.nim, 'hadir', NULL
FROM realisasi_pertemuan rp
JOIN enrollment e ON rp.id_kelas = e.id_kelas
WHERE e.status = 'aktif'
LIMIT 50;

INSERT INTO kehadiran (id_realisasi, nim, status, keterangan)
SELECT rp.id_realisasi, e.nim, 'izin', 'Izin keperluan keluarga'
FROM realisasi_pertemuan rp
JOIN enrollment e ON rp.id_kelas = e.id_kelas
WHERE e.status = 'aktif'
LIMIT 10;

INSERT INTO kehadiran (id_realisasi, nim, status, keterangan)
SELECT rp.id_realisasi, e.nim, 'sakit', 'Sakit demam'
FROM realisasi_pertemuan rp
JOIN enrollment e ON rp.id_kelas = e.id_kelas
WHERE e.status = 'aktif'
LIMIT 5;

-- ============================================================
-- SELESAI - Restore Foreign Key Checks
-- ============================================================

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- INFORMASI LOGIN
-- ============================================================
-- Username: admin       | Password: admin123
-- Username: kaprodi_tif | Password: kaprodi123
-- Username: dosen1      | Password: dosen123
-- Username: dosen2      | Password: dosen123
-- Username: 20240100001 | Password: mhs123
-- Username: 20240100002 | Password: mhs123
-- ============================================================
