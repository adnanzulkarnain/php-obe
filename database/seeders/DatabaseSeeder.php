<?php

namespace Database\Seeders;

use App\Config\Database;
use PDO;

/**
 * Database Seeder
 * Seeds database with comprehensive sample data for OBE system
 */
class DatabaseSeeder
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * Run all seeders
     */
    public function run(): void
    {
        echo "=== Starting Database Seeding ===\n\n";

        try {
            // Start transaction
            $this->pdo->beginTransaction();

            // Seed in order of dependencies
            $this->seedRoles();
            $this->seedJenisPenilaian();
            $this->seedFakultas();
            $this->seedProdi();
            $this->seedDosen();
            $this->seedKurikulum();
            $this->seedCPL();
            $this->seedMatakuliah();
            $this->seedPrasyaratMK();
            $this->seedRPS();
            $this->seedCPMK();
            $this->seedSubCPMK();
            $this->seedRelasiCPMKCPL();
            $this->seedKelas();
            $this->seedTugasMengajar();
            $this->seedMahasiswa();
            $this->seedEnrollment();
            $this->seedUsers();
            $this->seedTemplatePenilaian();
            $this->seedKomponenPenilaian();
            $this->seedNilaiDetail();
            $this->seedKetercapaianCPMK();
            $this->seedRencanaMingguan();
            $this->seedPustaka();
            $this->seedAmbangBatas();
            $this->seedRealisasiPertemuan();
            $this->seedKehadiran();

            // Commit transaction
            $this->pdo->commit();

            echo "\n=== Database Seeded Successfully! ===\n";
            $this->printSummary();
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            echo "\n❌ Error seeding database: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    /**
     * Seed roles
     */
    private function seedRoles(): void
    {
        echo "📝 Seeding roles...\n";

        $roles = [
            ['admin', 'System Administrator'],
            ['kaprodi', 'Ketua Program Studi'],
            ['dosen', 'Dosen Pengampu'],
            ['mahasiswa', 'Mahasiswa']
        ];

        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO roles (role_name, description)
            VALUES (?, ?)
        ");

        foreach ($roles as $role) {
            $stmt->execute($role);
        }
    }

    /**
     * Seed jenis penilaian
     */
    private function seedJenisPenilaian(): void
    {
        echo "📝 Seeding jenis penilaian...\n";

        $jenis = [
            ['Quiz', 'Kuis singkat mingguan'],
            ['Tugas', 'Tugas individu atau kelompok'],
            ['Praktikum', 'Praktikum atau lab'],
            ['UTS', 'Ujian Tengah Semester'],
            ['UAS', 'Ujian Akhir Semester'],
            ['Project', 'Project akhir']
        ];

        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO jenis_penilaian (nama_jenis, deskripsi)
            VALUES (?, ?)
        ");

        foreach ($jenis as $j) {
            $stmt->execute($j);
        }
    }

    /**
     * Seed fakultas
     */
    private function seedFakultas(): void
    {
        echo "🏛️  Seeding fakultas...\n";

        $fakultas = [
            ['FTI', 'Fakultas Teknologi Industri'],
            ['FEB', 'Fakultas Ekonomi dan Bisnis'],
            ['FT', 'Fakultas Teknik']
        ];

        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO fakultas (id_fakultas, nama)
            VALUES (?, ?)
        ");

        foreach ($fakultas as $f) {
            $stmt->execute($f);
        }
    }

    /**
     * Seed prodi
     */
    private function seedProdi(): void
    {
        echo "🎓 Seeding prodi...\n";

        $prodi = [
            ['TIF', 'FTI', 'Teknik Informatika', 'S1', 'A', 2010],
            ['SI', 'FTI', 'Sistem Informasi', 'S1', 'B', 2012],
            ['MAN', 'FEB', 'Manajemen', 'S1', 'A', 2008]
        ];

        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO prodi (id_prodi, id_fakultas, nama, jenjang, akreditasi, tahun_berdiri)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        foreach ($prodi as $p) {
            $stmt->execute($p);
        }
    }

    /**
     * Seed dosen
     */
    private function seedDosen(): void
    {
        echo "👨‍🏫 Seeding dosen...\n";

        $dosen = [
            ['DSN001', '0123456701', 'Dr. Ahmad Santoso, M.Kom', 'ahmad.santoso@univ.ac.id', '081234567001', 'TIF', 'aktif'],
            ['DSN002', '0123456702', 'Prof. Budi Raharjo, Ph.D', 'budi.raharjo@univ.ac.id', '081234567002', 'TIF', 'aktif'],
            ['DSN003', '0123456703', 'Dr. Citra Dewi, M.T', 'citra.dewi@univ.ac.id', '081234567003', 'TIF', 'aktif'],
            ['DSN004', '0123456704', 'Drs. Doni Setiawan, M.Kom', 'doni.setiawan@univ.ac.id', '081234567004', 'TIF', 'aktif'],
            ['DSN005', '0123456705', 'Eka Putri, S.Kom, M.Sc', 'eka.putri@univ.ac.id', '081234567005', 'TIF', 'aktif'],
            ['DSN006', '0123456706', 'Dr. Fajar Nugroho, M.Kom', 'fajar.nugroho@univ.ac.id', '081234567006', 'SI', 'aktif']
        ];

        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO dosen (id_dosen, nidn, nama, email, phone, id_prodi, status)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($dosen as $d) {
            $stmt->execute($d);
        }
    }

    /**
     * Seed kurikulum
     */
    private function seedKurikulum(): void
    {
        echo "📚 Seeding kurikulum...\n";

        $kurikulum = [
            ['TIF', 'K2024', 'Kurikulum OBE 2024', 2024, null, 'OBE-based curriculum aligned with MBKM', 'aktif', true, 'SK/TIF/001/2024', '2024-01-15', 'DSN001', 'DSN002', '2024-01-15'],
            ['TIF', 'K2020', 'Kurikulum 2020', 2020, 2024, 'Previous curriculum version', 'non-aktif', false, 'SK/TIF/001/2020', '2020-01-10', 'DSN002', 'DSN002', '2020-01-10'],
            ['SI', 'K2024', 'Kurikulum SI 2024', 2024, null, 'Information Systems OBE curriculum', 'aktif', true, 'SK/SI/001/2024', '2024-01-20', 'DSN006', 'DSN006', '2024-01-20']
        ];

        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO kurikulum (id_prodi, kode_kurikulum, nama_kurikulum, tahun_berlaku, tahun_berakhir,
                                   deskripsi, status, is_primary, nomor_sk, tanggal_sk, created_by, approved_by, approved_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($kurikulum as $k) {
            $stmt->execute($k);
        }
    }

    /**
     * Seed CPL
     */
    private function seedCPL(): void
    {
        echo "🎯 Seeding CPL (Capaian Pembelajaran Lulusan)...\n";

        // Get kurikulum K2024 TIF
        $stmt = $this->pdo->prepare("SELECT id_kurikulum FROM kurikulum WHERE kode_kurikulum = ? AND id_prodi = ?");
        $stmt->execute(['K2024', 'TIF']);
        $idKurikulum = $stmt->fetchColumn();

        $cpl = [
            [$idKurikulum, 'CPL-S1', 'Menunjukkan sikap bertanggung jawab atas pekerjaan di bidang keahliannya secara mandiri', 'sikap', 1, true],
            [$idKurikulum, 'CPL-S2', 'Menginternalisasi nilai, norma, dan etika akademik', 'sikap', 2, true],
            [$idKurikulum, 'CPL-P1', 'Menguasai konsep teoretis bidang pengetahuan komputer secara umum dan konsep teoretis bagian khusus dalam bidang pengetahuan tersebut secara mendalam', 'pengetahuan', 3, true],
            [$idKurikulum, 'CPL-P2', 'Menguasai pengetahuan tentang algoritma, pemrograman, struktur data, basis data, dan matematika diskrit', 'pengetahuan', 4, true],
            [$idKurikulum, 'CPL-KU1', 'Mampu menerapkan pemikiran logis, kritis, sistematis, dan inovatif dalam konteks pengembangan atau implementasi ilmu pengetahuan dan teknologi', 'keterampilan_umum', 5, true],
            [$idKurikulum, 'CPL-KU2', 'Mampu menunjukkan kinerja mandiri, bermutu, dan terukur', 'keterampilan_umum', 6, true],
            [$idKurikulum, 'CPL-KK1', 'Mampu merancang, mengimplementasi, dan mengevaluasi sistem berbasis komputer', 'keterampilan_khusus', 7, true],
            [$idKurikulum, 'CPL-KK2', 'Mampu menganalisis permasalahan komputasi kompleks dan menerapkan prinsip-prinsip komputasi untuk mengidentifikasi solusi', 'keterampilan_khusus', 8, true],
            [$idKurikulum, 'CPL-KK3', 'Mampu merancang solusi untuk masalah komputasi kompleks dan merancang serta mengevaluasi sistem berbasis komputer', 'keterampilan_khusus', 9, true]
        ];

        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO cpl (id_kurikulum, kode_cpl, deskripsi, kategori, urutan, is_active)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        foreach ($cpl as $c) {
            $stmt->execute($c);
        }
    }

    /**
     * Seed mata kuliah
     */
    private function seedMatakuliah(): void
    {
        echo "📖 Seeding mata kuliah...\n";

        // Get kurikulum K2024 TIF
        $stmt = $this->pdo->prepare("SELECT id_kurikulum FROM kurikulum WHERE kode_kurikulum = ? AND id_prodi = ?");
        $stmt->execute(['K2024', 'TIF']);
        $idKurikulum = $stmt->fetchColumn();

        $matakuliah = [
            ['TIF101', $idKurikulum, 'Algoritma dan Pemrograman', 'Algorithm and Programming', 4, 1, 'Pemrograman', 'wajib', true],
            ['TIF102', $idKurikulum, 'Matematika Diskrit', 'Discrete Mathematics', 3, 1, 'Matematika', 'wajib', true],
            ['TIF103', $idKurikulum, 'Pengantar Teknologi Informasi', 'Introduction to Information Technology', 3, 1, 'Dasar TI', 'wajib', true],
            ['TIF201', $idKurikulum, 'Struktur Data', 'Data Structures', 4, 2, 'Pemrograman', 'wajib', true],
            ['TIF202', $idKurikulum, 'Basis Data', 'Database', 4, 2, 'Sistem Informasi', 'wajib', true],
            ['TIF203', $idKurikulum, 'Pemrograman Berorientasi Objek', 'Object-Oriented Programming', 4, 2, 'Pemrograman', 'wajib', true],
            ['TIF301', $idKurikulum, 'Rekayasa Perangkat Lunak', 'Software Engineering', 4, 3, 'RPL', 'wajib', true],
            ['TIF302', $idKurikulum, 'Desain dan Analisis Algoritma', 'Algorithm Design and Analysis', 3, 3, 'Algoritma', 'wajib', true],
            ['TIF303', $idKurikulum, 'Pemrograman Web', 'Web Programming', 4, 3, 'Pemrograman', 'wajib', true],
            ['TIF401', $idKurikulum, 'Kecerdasan Buatan', 'Artificial Intelligence', 3, 4, 'AI', 'pilihan', true]
        ];

        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO matakuliah (kode_mk, id_kurikulum, nama_mk, nama_mk_eng, sks, semester, rumpun, jenis_mk, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($matakuliah as $mk) {
            $stmt->execute($mk);
        }
    }

    /**
     * Seed prasyarat mata kuliah
     */
    private function seedPrasyaratMK(): void
    {
        echo "🔗 Seeding prasyarat mata kuliah...\n";

        // Get kurikulum K2024 TIF
        $stmt = $this->pdo->prepare("SELECT id_kurikulum FROM kurikulum WHERE kode_kurikulum = ? AND id_prodi = ?");
        $stmt->execute(['K2024', 'TIF']);
        $idKurikulum = $stmt->fetchColumn();

        $prasyarat = [
            ['TIF201', $idKurikulum, 'TIF101', 'wajib'], // Struktur Data memerlukan Algoritma
            ['TIF203', $idKurikulum, 'TIF101', 'wajib'], // PBO memerlukan Algoritma
            ['TIF301', $idKurikulum, 'TIF201', 'wajib'], // RPL memerlukan Struktur Data
            ['TIF301', $idKurikulum, 'TIF202', 'wajib'], // RPL memerlukan Basis Data
            ['TIF302', $idKurikulum, 'TIF201', 'wajib'], // Desain Algoritma memerlukan Struktur Data
            ['TIF303', $idKurikulum, 'TIF203', 'wajib']  // Web Programming memerlukan PBO
        ];

        $stmt = $this->pdo->prepare("
            INSERT INTO prasyarat_mk (kode_mk, id_kurikulum, kode_mk_prasyarat, jenis_prasyarat)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($prasyarat as $p) {
            $stmt->execute($p);
        }
    }

    /**
     * Seed RPS
     */
    private function seedRPS(): void
    {
        echo "📋 Seeding RPS (Rencana Pembelajaran Semester)...\n";

        // Get kurikulum K2024 TIF
        $stmt = $this->pdo->prepare("SELECT id_kurikulum FROM kurikulum WHERE kode_kurikulum = ? AND id_prodi = ?");
        $stmt->execute(['K2024', 'TIF']);
        $idKurikulum = $stmt->fetchColumn();

        $rps = [
            ['TIF301', $idKurikulum, 'Ganjil', '2024/2025', 'approved', 'DSN001', '2024-08-01',
             'Mata kuliah ini membahas prinsip, konsep, dan teknik rekayasa perangkat lunak modern',
             'Pembelajaran RPL dengan pendekatan agile dan waterfall', 'DSN001'],
            ['TIF303', $idKurikulum, 'Ganjil', '2024/2025', 'approved', 'DSN003', '2024-08-01',
             'Mata kuliah ini membahas pengembangan aplikasi web modern menggunakan framework terkini',
             'Pembelajaran web programming dengan project-based learning', 'DSN003'],
            ['TIF101', $idKurikulum, 'Ganjil', '2024/2025', 'approved', 'DSN002', '2024-08-01',
             'Mata kuliah dasar pemrograman dengan fokus pada algoritma dan logika',
             'Pembelajaran fundamental programming', 'DSN002']
        ];

        $stmt = $this->pdo->prepare("
            INSERT INTO rps (kode_mk, id_kurikulum, semester_berlaku, tahun_ajaran, status,
                             ketua_pengembang, tanggal_disusun, deskripsi_mk, deskripsi_singkat, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($rps as $r) {
            $stmt->execute($r);
        }
    }

    /**
     * Seed CPMK
     */
    private function seedCPMK(): void
    {
        echo "🎯 Seeding CPMK (Capaian Pembelajaran Mata Kuliah)...\n";

        // Get RPS for TIF301
        $stmt = $this->pdo->prepare("
            SELECT id_rps FROM rps
            WHERE kode_mk = ? AND tahun_ajaran = ?
        ");
        $stmt->execute(['TIF301', '2024/2025']);
        $idRpsTIF301 = $stmt->fetchColumn();

        $stmt->execute(['TIF303', '2024/2025']);
        $idRpsTIF303 = $stmt->fetchColumn();

        $stmt->execute(['TIF101', '2024/2025']);
        $idRpsTIF101 = $stmt->fetchColumn();

        $cpmk = [
            // CPMK for RPL (TIF301)
            [$idRpsTIF301, 'CPMK-1', 'Mahasiswa mampu memahami konsep dasar rekayasa perangkat lunak', 1],
            [$idRpsTIF301, 'CPMK-2', 'Mahasiswa mampu menganalisis kebutuhan sistem', 2],
            [$idRpsTIF301, 'CPMK-3', 'Mahasiswa mampu merancang arsitektur perangkat lunak', 3],
            [$idRpsTIF301, 'CPMK-4', 'Mahasiswa mampu mengimplementasikan sistem dengan metodologi yang tepat', 4],
            [$idRpsTIF301, 'CPMK-5', 'Mahasiswa mampu melakukan testing dan quality assurance', 5],

            // CPMK for Web Programming (TIF303)
            [$idRpsTIF303, 'CPMK-1', 'Mahasiswa mampu memahami arsitektur aplikasi web', 1],
            [$idRpsTIF303, 'CPMK-2', 'Mahasiswa mampu mengimplementasikan frontend dengan HTML, CSS, JavaScript', 2],
            [$idRpsTIF303, 'CPMK-3', 'Mahasiswa mampu mengimplementasikan backend dengan PHP/Node.js', 3],
            [$idRpsTIF303, 'CPMK-4', 'Mahasiswa mampu mengintegrasikan frontend dan backend', 4],

            // CPMK for Algoritma (TIF101)
            [$idRpsTIF101, 'CPMK-1', 'Mahasiswa mampu memahami konsep dasar algoritma', 1],
            [$idRpsTIF101, 'CPMK-2', 'Mahasiswa mampu merancang algoritma untuk menyelesaikan masalah', 2],
            [$idRpsTIF101, 'CPMK-3', 'Mahasiswa mampu mengimplementasikan algoritma dalam bahasa pemrograman', 3]
        ];

        $stmt = $this->pdo->prepare("
            INSERT INTO cpmk (id_rps, kode_cpmk, deskripsi, urutan)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($cpmk as $c) {
            $stmt->execute($c);
        }
    }

    /**
     * Seed SubCPMK
     */
    private function seedSubCPMK(): void
    {
        echo "📌 Seeding SubCPMK...\n";

        // Get CPMK
        $stmt = $this->pdo->prepare("
            SELECT c.id_cpmk, c.kode_cpmk, r.kode_mk
            FROM cpmk c
            JOIN rps r ON c.id_rps = r.id_rps
            WHERE r.kode_mk = ? AND r.tahun_ajaran = ?
            ORDER BY c.urutan
            LIMIT 1
        ");

        $stmt->execute(['TIF301', '2024/2025']);
        $cpmk1 = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cpmk1) {
            $subcpmk = [
                [$cpmk1['id_cpmk'], 'SubCPMK-1.1', 'Menjelaskan definisi dan ruang lingkup RPL', 'Mampu mendefinisikan RPL dengan benar', 1],
                [$cpmk1['id_cpmk'], 'SubCPMK-1.2', 'Mengidentifikasi tahapan dalam siklus hidup perangkat lunak', 'Mampu menyebutkan minimal 5 tahapan SDLC', 2],
                [$cpmk1['id_cpmk'], 'SubCPMK-1.3', 'Membandingkan berbagai model proses pengembangan', 'Mampu membandingkan waterfall, agile, dan spiral', 3]
            ];

            $stmt = $this->pdo->prepare("
                INSERT INTO subcpmk (id_cpmk, kode_subcpmk, deskripsi, indikator, urutan)
                VALUES (?, ?, ?, ?, ?)
            ");

            foreach ($subcpmk as $s) {
                $stmt->execute($s);
            }
        }
    }

    /**
     * Seed relasi CPMK-CPL
     */
    private function seedRelasiCPMKCPL(): void
    {
        echo "🔗 Seeding relasi CPMK-CPL...\n";

        // Get CPMK for TIF301
        $stmt = $this->pdo->query("
            SELECT c.id_cpmk, r.id_kurikulum
            FROM cpmk c
            JOIN rps r ON c.id_rps = r.id_rps
            WHERE r.kode_mk = 'TIF301' AND r.tahun_ajaran = '2024/2025'
        ");
        $cpmkData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($cpmkData)) {
            $idKurikulum = $cpmkData[0]['id_kurikulum'];

            // Get CPL for the same curriculum
            $stmt = $this->pdo->prepare("
                SELECT id_cpl FROM cpl
                WHERE id_kurikulum = ?
                ORDER BY urutan
                LIMIT 5
            ");
            $stmt->execute([$idKurikulum]);
            $cplIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($cplIds)) {
                $relasi = [];
                foreach ($cpmkData as $index => $cpmk) {
                    // Map each CPMK to 2-3 CPLs with different weights
                    $relasi[] = [$cpmk['id_cpmk'], $cplIds[$index % count($cplIds)], 80.00];
                    $relasi[] = [$cpmk['id_cpmk'], $cplIds[($index + 1) % count($cplIds)], 60.00];
                }

                $stmt = $this->pdo->prepare("
                    INSERT IGNORE INTO relasi_cpmk_cpl (id_cpmk, id_cpl, bobot_kontribusi)
                    VALUES (?, ?, ?)
                ");

                foreach ($relasi as $r) {
                    $stmt->execute($r);
                }
            }
        }
    }

    /**
     * Seed kelas
     */
    private function seedKelas(): void
    {
        echo "🏫 Seeding kelas...\n";

        // Get RPS IDs
        $stmt = $this->pdo->prepare("
            SELECT id_rps, kode_mk, id_kurikulum
            FROM rps
            WHERE tahun_ajaran = '2024/2025'
        ");
        $stmt->execute();
        $rpsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $kelas = [];
        foreach ($rpsData as $rps) {
            $kelas[] = [
                $rps['kode_mk'], $rps['id_kurikulum'], $rps['id_rps'], 'A',
                'Ganjil', '2024/2025', 40, 35, 'Senin', '08:00:00', '10:30:00', 'R.301', 'open'
            ];
            $kelas[] = [
                $rps['kode_mk'], $rps['id_kurikulum'], $rps['id_rps'], 'B',
                'Ganjil', '2024/2025', 40, 32, 'Selasa', '13:00:00', '15:30:00', 'R.302', 'open'
            ];
        }

        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO kelas (kode_mk, id_kurikulum, id_rps, nama_kelas, semester, tahun_ajaran,
                               kapasitas, kuota_terisi, hari, jam_mulai, jam_selesai, ruangan, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($kelas as $k) {
            $stmt->execute($k);
        }
    }

    /**
     * Seed tugas mengajar
     */
    private function seedTugasMengajar(): void
    {
        echo "👨‍🏫 Seeding tugas mengajar...\n";

        // Get kelas IDs
        $stmt = $this->pdo->query("SELECT id_kelas FROM kelas LIMIT 10");
        $kelasIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $tugasMengajar = [];
        $dosenIds = ['DSN001', 'DSN002', 'DSN003', 'DSN004', 'DSN005'];

        foreach ($kelasIds as $index => $idKelas) {
            $tugasMengajar[] = [$idKelas, $dosenIds[$index % count($dosenIds)], 'koordinator'];
            $tugasMengajar[] = [$idKelas, $dosenIds[($index + 1) % count($dosenIds)], 'pengampu'];
        }

        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO tugas_mengajar (id_kelas, id_dosen, peran)
            VALUES (?, ?, ?)
        ");

        foreach ($tugasMengajar as $t) {
            $stmt->execute($t);
        }
    }

    /**
     * Seed mahasiswa
     */
    private function seedMahasiswa(): void
    {
        echo "👨‍🎓 Seeding mahasiswa...\n";

        // Get kurikulum K2024 TIF
        $stmt = $this->pdo->prepare("SELECT id_kurikulum FROM kurikulum WHERE kode_kurikulum = ? AND id_prodi = ?");
        $stmt->execute(['K2024', 'TIF']);
        $idKurikulum = $stmt->fetchColumn();

        $mahasiswa = [];
        for ($i = 1; $i <= 50; $i++) {
            $nim = '202401' . str_pad($i, 4, '0', STR_PAD_LEFT);
            $nama = 'Mahasiswa ' . $i;
            $email = 'mhs' . $i . '@student.univ.ac.id';
            $angkatan = '2024';
            $status = 'aktif';

            $mahasiswa[] = [$nim, $nama, $email, 'TIF', $idKurikulum, $angkatan, $status];
        }

        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan, status)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($mahasiswa as $m) {
            $stmt->execute($m);
        }
    }

    /**
     * Seed enrollment
     */
    private function seedEnrollment(): void
    {
        echo "📝 Seeding enrollment...\n";

        // Get mahasiswa
        $stmt = $this->pdo->query("SELECT nim FROM mahasiswa LIMIT 35");
        $nimList = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Get kelas
        $stmt = $this->pdo->query("SELECT id_kelas FROM kelas LIMIT 6");
        $kelasIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $enrollment = [];
        foreach ($nimList as $nim) {
            // Enroll each student to 3-4 classes
            $numClasses = rand(3, 4);
            $selectedClasses = array_rand(array_flip($kelasIds), min($numClasses, count($kelasIds)));
            if (!is_array($selectedClasses)) {
                $selectedClasses = [$selectedClasses];
            }

            foreach ($selectedClasses as $idKelas) {
                $nilaiAkhir = rand(60, 95);
                $nilaiHuruf = $this->convertToGrade($nilaiAkhir);
                $enrollment[] = [$nim, $idKelas, '2024-08-15', 'aktif', $nilaiAkhir, $nilaiHuruf];
            }
        }

        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO enrollment (nim, id_kelas, tanggal_daftar, status, nilai_akhir, nilai_huruf)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        foreach ($enrollment as $e) {
            $stmt->execute($e);
        }
    }

    /**
     * Seed users
     */
    private function seedUsers(): void
    {
        echo "👤 Seeding users...\n";

        $users = [
            ['admin', 'admin@univ.ac.id', password_hash('admin123', PASSWORD_BCRYPT), 'admin', null, true],
            ['kaprodi_tif', 'kaprodi.tif@univ.ac.id', password_hash('kaprodi123', PASSWORD_BCRYPT), 'kaprodi', 'DSN001', true],
            ['dosen1', 'dosen1@univ.ac.id', password_hash('dosen123', PASSWORD_BCRYPT), 'dosen', 'DSN002', true],
            ['dosen2', 'dosen2@univ.ac.id', password_hash('dosen123', PASSWORD_BCRYPT), 'dosen', 'DSN003', true],
            ['202401001', 'mhs1@student.univ.ac.id', password_hash('mhs123', PASSWORD_BCRYPT), 'mahasiswa', '20240100001', true],
            ['202401002', 'mhs2@student.univ.ac.id', password_hash('mhs123', PASSWORD_BCRYPT), 'mahasiswa', '20240100002', true]
        ];

        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO users (username, email, password_hash, user_type, ref_id, is_active)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        foreach ($users as $u) {
            $stmt->execute($u);
        }
    }

    /**
     * Seed template penilaian
     */
    private function seedTemplatePenilaian(): void
    {
        echo "📊 Seeding template penilaian...\n";

        // Get RPS and CPMK for TIF301
        $stmt = $this->pdo->query("
            SELECT r.id_rps, c.id_cpmk
            FROM rps r
            JOIN cpmk c ON c.id_rps = r.id_rps
            WHERE r.kode_mk = 'TIF301' AND r.tahun_ajaran = '2024/2025'
            LIMIT 3
        ");
        $rpsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get jenis penilaian
        $stmt = $this->pdo->query("SELECT id_jenis FROM jenis_penilaian ORDER BY id_jenis");
        $jenisIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $templates = [];
        foreach ($rpsData as $data) {
            $templates[] = [$data['id_rps'], $data['id_cpmk'], $jenisIds[0], 10.00]; // Quiz
            $templates[] = [$data['id_rps'], $data['id_cpmk'], $jenisIds[1], 20.00]; // Tugas
            $templates[] = [$data['id_rps'], $data['id_cpmk'], $jenisIds[3], 30.00]; // UTS
            $templates[] = [$data['id_rps'], $data['id_cpmk'], $jenisIds[4], 40.00]; // UAS
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO template_penilaian (id_rps, id_cpmk, id_jenis, bobot)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($templates as $t) {
            $stmt->execute($t);
        }
    }

    /**
     * Seed komponen penilaian
     */
    private function seedKomponenPenilaian(): void
    {
        echo "📋 Seeding komponen penilaian...\n";

        // Get kelas and template
        $stmt = $this->pdo->query("
            SELECT k.id_kelas, tp.id_template
            FROM kelas k
            JOIN template_penilaian tp ON tp.id_rps = k.id_rps
            WHERE k.kode_mk = 'TIF301'
            LIMIT 12
        ");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $komponen = [];
        foreach ($data as $d) {
            $komponen[] = [
                $d['id_kelas'], $d['id_template'], 'Quiz Minggu 1', 'Quiz tentang konsep dasar',
                '2024-09-10', '2024-09-10', 10.00, 100.00
            ];
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO komponen_penilaian (id_kelas, id_template, nama_komponen, deskripsi,
                                             tanggal_pelaksanaan, deadline, bobot_realisasi, nilai_maksimal)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($komponen as $k) {
            $stmt->execute($k);
        }
    }

    /**
     * Seed nilai detail
     */
    private function seedNilaiDetail(): void
    {
        echo "💯 Seeding nilai detail...\n";

        // Get enrollment and komponen
        $stmt = $this->pdo->query("
            SELECT e.id_enrollment, kp.id_komponen
            FROM enrollment e
            JOIN kelas k ON e.id_kelas = k.id_kelas
            JOIN komponen_penilaian kp ON kp.id_kelas = k.id_kelas
            LIMIT 100
        ");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $nilai = [];
        foreach ($data as $d) {
            $nilaiMentah = rand(60, 100);
            $nilai[] = [$d['id_enrollment'], $d['id_komponen'], $nilaiMentah, 'DSN001'];
        }

        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO nilai_detail (id_enrollment, id_komponen, nilai_mentah, dinilai_oleh)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($nilai as $n) {
            $stmt->execute($n);
        }
    }

    /**
     * Seed ketercapaian CPMK
     */
    private function seedKetercapaianCPMK(): void
    {
        echo "🎯 Seeding ketercapaian CPMK...\n";

        // Get enrollment and CPMK
        $stmt = $this->pdo->query("
            SELECT e.id_enrollment, c.id_cpmk
            FROM enrollment e
            JOIN kelas k ON e.id_kelas = k.id_kelas
            JOIN cpmk c ON c.id_rps = k.id_rps
            LIMIT 200
        ");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $ketercapaian = [];
        foreach ($data as $d) {
            $nilaiCpmk = rand(50, 95);
            $statusTercapai = $nilaiCpmk >= 60;
            $ketercapaian[] = [$d['id_enrollment'], $d['id_cpmk'], $nilaiCpmk, $statusTercapai];
        }

        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO ketercapaian_cpmk (id_enrollment, id_cpmk, nilai_cpmk, status_tercapai)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($ketercapaian as $k) {
            $stmt->execute($k);
        }
    }

    /**
     * Seed rencana mingguan
     */
    private function seedRencanaMingguan(): void
    {
        echo "📅 Seeding rencana mingguan...\n";

        // Get RPS and SubCPMK
        $stmt = $this->pdo->query("
            SELECT r.id_rps, s.id_subcpmk
            FROM rps r
            JOIN cpmk c ON c.id_rps = r.id_rps
            JOIN subcpmk s ON s.id_cpmk = c.id_cpmk
            WHERE r.kode_mk = 'TIF301'
            LIMIT 10
        ");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $rencana = [];
        $minggu = 1;
        foreach ($data as $d) {
            $materi = json_encode(['Topik ' . $minggu, 'Sub-topik ' . $minggu . '.1']);
            $metode = json_encode(['Ceramah', 'Diskusi', 'Praktikum']);
            $aktivitas = json_encode(['Presentasi', 'Quiz', 'Latihan']);

            $rencana[] = [
                $d['id_rps'], $minggu, $d['id_subcpmk'], $materi, $metode, $aktivitas,
                'PowerPoint, Video', 'Proyektor, Laptop', 'Mahasiswa belajar dengan diskusi kelompok', 150
            ];
            $minggu++;
        }

        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO rencana_mingguan (id_rps, minggu_ke, id_subcpmk, materi, metode, aktivitas,
                                          media_software, media_hardware, pengalaman_belajar, estimasi_waktu_menit)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($rencana as $r) {
            $stmt->execute($r);
        }
    }

    /**
     * Seed pustaka
     */
    private function seedPustaka(): void
    {
        echo "📚 Seeding pustaka...\n";

        // Get RPS
        $stmt = $this->pdo->query("SELECT id_rps FROM rps WHERE kode_mk = 'TIF301' LIMIT 1");
        $idRps = $stmt->fetchColumn();

        if ($idRps) {
            $pustaka = [
                [$idRps, 'utama', 'Software Engineering: A Practitioner\'s Approach', 'Roger S. Pressman', 2019, 'McGraw-Hill', '9780078022128', null],
                [$idRps, 'utama', 'Clean Code: A Handbook of Agile Software Craftsmanship', 'Robert C. Martin', 2008, 'Prentice Hall', '9780132350884', null],
                [$idRps, 'pendukung', 'Design Patterns: Elements of Reusable Object-Oriented Software', 'Gang of Four', 1994, 'Addison-Wesley', '9780201633610', null],
                [$idRps, 'pendukung', 'The Mythical Man-Month', 'Frederick P. Brooks Jr.', 1995, 'Addison-Wesley', '9780201835953', null]
            ];

            $stmt = $this->pdo->prepare("
                INSERT INTO pustaka (id_rps, jenis, referensi, penulis, tahun, penerbit, isbn, url)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            foreach ($pustaka as $p) {
                $stmt->execute($p);
            }
        }
    }

    /**
     * Seed ambang batas
     */
    private function seedAmbangBatas(): void
    {
        echo "⚖️  Seeding ambang batas...\n";

        // Get RPS
        $stmt = $this->pdo->query("SELECT id_rps FROM rps");
        $rpsIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $ambangBatas = [];
        foreach ($rpsIds as $idRps) {
            $ambangBatas[] = [$idRps, 40.01, 50.00, 75.00];
        }

        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO ambang_batas (id_rps, batas_kelulusan_cpmk, batas_kelulusan_mk, persentase_mahasiswa_lulus)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($ambangBatas as $ab) {
            $stmt->execute($ab);
        }
    }

    /**
     * Seed realisasi pertemuan (lecture reports)
     */
    private function seedRealisasiPertemuan(): void
    {
        echo "📝 Seeding realisasi pertemuan (berita acara)...\n";

        // Get kelas with their dosen
        $stmt = $this->pdo->query("
            SELECT DISTINCT
                k.id_kelas,
                k.kode_mk,
                k.nama_kelas,
                tm.id_dosen,
                rm.id_minggu,
                rm.minggu_ke,
                rm.materi
            FROM kelas k
            JOIN tugas_mengajar tm ON k.id_kelas = tm.id_kelas
            LEFT JOIN rencana_mingguan rm ON k.id_rps = rm.id_rps
            WHERE tm.peran = 'koordinator'
            ORDER BY k.id_kelas, rm.minggu_ke
            LIMIT 20
        ");
        $kelasData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($kelasData)) {
            echo "   ⚠️  No class data found, skipping...\n";
            return;
        }

        // Get kaprodi for verification
        $stmt = $this->pdo->query("SELECT id_dosen FROM dosen WHERE id_dosen = 'DSN001' LIMIT 1");
        $kaprodiId = $stmt->fetchColumn();

        $realisasi = [];
        $currentDate = new \DateTime('2024-09-02'); // Start of semester

        foreach ($kelasData as $index => $data) {
            $mingguKe = $data['minggu_ke'] ?? ($index % 14) + 1;
            $tanggalPelaksanaan = clone $currentDate;
            $tanggalPelaksanaan->modify('+' . (($index % 14) * 7) . ' days');

            // Decode materi if it's JSON
            $materiRencana = 'Materi Pertemuan ' . $mingguKe;
            if (!empty($data['materi'])) {
                $materiDecoded = json_decode($data['materi'], true);
                if (is_array($materiDecoded) && !empty($materiDecoded)) {
                    $materiRencana = implode(', ', $materiDecoded);
                }
            }

            // Create different scenarios based on index
            $scenario = $index % 5;

            switch ($scenario) {
                case 0: // Draft - being worked on
                    $realisasi[] = [
                        $data['id_kelas'],
                        $data['id_minggu'],
                        $tanggalPelaksanaan->format('Y-m-d'),
                        $materiRencana,
                        'Ceramah, Diskusi Kelompok',
                        null, // no kendala yet
                        'Mahasiswa cukup aktif dalam diskusi',
                        'draft',
                        null, // not verified
                        null,
                        null,
                        $data['id_dosen'],
                        $tanggalPelaksanaan->format('Y-m-d H:i:s')
                    ];
                    break;

                case 1: // Submitted - waiting verification
                    $realisasi[] = [
                        $data['id_kelas'],
                        $data['id_minggu'],
                        $tanggalPelaksanaan->format('Y-m-d'),
                        $materiRencana . ' - Dengan studi kasus praktis',
                        'Ceramah, Praktikum, Quiz',
                        'Proyektor sempat bermasalah di awal perkuliahan',
                        'Materi tersampaikan dengan baik. Quiz dilakukan di akhir sesi.',
                        'submitted',
                        null,
                        null,
                        null,
                        $data['id_dosen'],
                        $tanggalPelaksanaan->format('Y-m-d H:i:s')
                    ];
                    break;

                case 2: // Verified - approved by kaprodi
                    $verifiedAt = clone $tanggalPelaksanaan;
                    $verifiedAt->modify('+2 days');
                    $realisasi[] = [
                        $data['id_kelas'],
                        $data['id_minggu'],
                        $tanggalPelaksanaan->format('Y-m-d'),
                        $materiRencana . ' - Sesuai RPS',
                        'Ceramah, Diskusi, Presentasi Kelompok',
                        null,
                        'Perkuliahan berjalan lancar. Mahasiswa aktif bertanya.',
                        'verified',
                        $kaprodiId,
                        $verifiedAt->format('Y-m-d H:i:s'),
                        'Berita acara sudah sesuai dengan RPS. Metode pembelajaran sudah baik.',
                        $data['id_dosen'],
                        $tanggalPelaksanaan->format('Y-m-d H:i:s')
                    ];
                    break;

                case 3: // Rejected - needs revision
                    $verifiedAt = clone $tanggalPelaksanaan;
                    $verifiedAt->modify('+1 day');
                    $realisasi[] = [
                        $data['id_kelas'],
                        $data['id_minggu'],
                        $tanggalPelaksanaan->format('Y-m-d'),
                        'Materi singkat tentang ' . $materiRencana,
                        'Ceramah',
                        'Banyak mahasiswa yang terlambat',
                        'Materi kurang mendalam',
                        'rejected',
                        $kaprodiId,
                        $verifiedAt->format('Y-m-d H:i:s'),
                        'Materi yang disampaikan belum sesuai dengan RPS. Mohon lengkapi dengan metode pembelajaran yang lebih variatif dan jelaskan lebih detail materi yang disampaikan.',
                        $data['id_dosen'],
                        $tanggalPelaksanaan->format('Y-m-d H:i:s')
                    ];
                    break;

                case 4: // Verified - another approved one
                    $verifiedAt = clone $tanggalPelaksanaan;
                    $verifiedAt->modify('+3 days');
                    $realisasi[] = [
                        $data['id_kelas'],
                        $data['id_minggu'],
                        $tanggalPelaksanaan->format('Y-m-d'),
                        $materiRencana . ' - Dengan demonstrasi',
                        'Ceramah, Demonstrasi, Latihan',
                        'Waktu sedikit kurang untuk latihan',
                        'Demonstrasi berjalan baik. Mahasiswa mengikuti dengan antusias.',
                        'verified',
                        $kaprodiId,
                        $verifiedAt->format('Y-m-d H:i:s'),
                        'Pembelajaran sudah sesuai RPS. Demonstrasi sangat membantu pemahaman mahasiswa.',
                        $data['id_dosen'],
                        $tanggalPelaksanaan->format('Y-m-d H:i:s')
                    ];
                    break;
            }
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO realisasi_pertemuan (
                id_kelas, id_minggu, tanggal_pelaksanaan, materi_disampaikan,
                metode_digunakan, kendala, catatan_dosen, status,
                verified_by, verified_at, komentar_kaprodi, created_by, created_at
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($realisasi as $r) {
            $stmt->execute($r);
        }

        echo "   ✅ Created " . count($realisasi) . " lecture reports with mixed statuses\n";
    }

    /**
     * Seed kehadiran (attendance records)
     */
    private function seedKehadiran(): void
    {
        echo "✅ Seeding kehadiran (attendance)...\n";

        // Get realisasi pertemuan with their class enrollment
        $stmt = $this->pdo->query("
            SELECT
                rp.id_realisasi,
                rp.id_kelas,
                rp.status,
                e.nim
            FROM realisasi_pertemuan rp
            JOIN enrollment e ON rp.id_kelas = e.id_kelas
            WHERE e.status = 'aktif'
            ORDER BY rp.id_realisasi, e.nim
        ");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($data)) {
            echo "   ⚠️  No realisasi or enrollment data found, skipping...\n";
            return;
        }

        $kehadiran = [];
        $currentRealisasi = null;
        $studentCount = 0;

        foreach ($data as $row) {
            // Track when we move to a new realisasi
            if ($currentRealisasi !== $row['id_realisasi']) {
                $currentRealisasi = $row['id_realisasi'];
                $studentCount = 0;
            }

            $studentCount++;

            // Create realistic attendance patterns
            // 70% hadir, 15% izin, 10% sakit, 5% alpha
            $rand = rand(1, 100);
            if ($rand <= 70) {
                $status = 'hadir';
                $keterangan = null;
            } elseif ($rand <= 85) {
                $status = 'izin';
                $keterangan = 'Izin keperluan keluarga';
            } elseif ($rand <= 95) {
                $status = 'sakit';
                $keterangan = 'Sakit demam';
            } else {
                $status = 'alpha';
                $keterangan = null;
            }

            // For draft reports, make attendance slightly higher (for testing)
            if ($row['status'] === 'draft' && $rand <= 80) {
                $status = 'hadir';
                $keterangan = null;
            }

            $kehadiran[] = [
                $row['id_realisasi'],
                $row['nim'],
                $status,
                $keterangan
            ];
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO kehadiran (id_realisasi, nim, status, keterangan)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($kehadiran as $k) {
            $stmt->execute($k);
        }

        echo "   ✅ Created " . count($kehadiran) . " attendance records\n";
    }

    /**
     * Convert numeric grade to letter grade
     */
    private function convertToGrade(float $nilai): string
    {
        if ($nilai >= 85) return 'A';
        if ($nilai >= 80) return 'A-';
        if ($nilai >= 75) return 'B+';
        if ($nilai >= 70) return 'B';
        if ($nilai >= 65) return 'B-';
        if ($nilai >= 60) return 'C+';
        if ($nilai >= 55) return 'C';
        if ($nilai >= 50) return 'C-';
        if ($nilai >= 45) return 'D';
        return 'E';
    }

    /**
     * Print summary of seeded data
     */
    private function printSummary(): void
    {
        echo "\n";
        echo "📊 Seeding Summary:\n";
        echo "==================\n";

        $tables = [
            'fakultas', 'prodi', 'dosen', 'kurikulum', 'cpl', 'matakuliah',
            'rps', 'cpmk', 'subcpmk', 'kelas', 'mahasiswa', 'enrollment',
            'users', 'komponen_penilaian', 'nilai_detail', 'ketercapaian_cpmk',
            'realisasi_pertemuan', 'kehadiran'
        ];

        foreach ($tables as $table) {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "   " . ucfirst($table) . ": $count records\n";
        }

        echo "\n✅ Sample Login Credentials:\n";
        echo "   Admin: admin / admin123\n";
        echo "   Kaprodi: kaprodi_tif / kaprodi123\n";
        echo "   Dosen: dosen1 / dosen123\n";
        echo "   Mahasiswa: 202401001 / mhs123\n";
    }
}
