<?php

namespace Database\Seeders;

use App\Config\Database;
use PDO;

/**
 * Database Seeder
 * Seeds database with sample data
 */
class DatabaseSeeder
{
    private PDO $pdo;

    public function __construct()
    {
        Database::connect();
        $this->pdo = Database::getConnection();
    }

    /**
     * Run all seeders
     */
    public function run(): void
    {
        echo "Seeding database...\n";

        $this->seedUsers();
        $this->seedFakultas();
        $this->seedProdi();
        $this->seedKurikulum();

        echo "Database seeded successfully!\n";
    }

    /**
     * Seed users
     */
    private function seedUsers(): void
    {
        echo "Seeding users...\n";

        $users = [
            [
                'username' => 'admin',
                'password' => password_hash('admin123', PASSWORD_BCRYPT),
                'email' => 'admin@obe-system.edu',
                'nama' => 'Administrator',
                'role' => 'admin'
            ],
            [
                'username' => 'kaprodi',
                'password' => password_hash('kaprodi123', PASSWORD_BCRYPT),
                'email' => 'kaprodi@obe-system.edu',
                'nama' => 'Kepala Program Studi',
                'role' => 'kaprodi'
            ],
            [
                'username' => 'dosen',
                'password' => password_hash('dosen123', PASSWORD_BCRYPT),
                'email' => 'dosen@obe-system.edu',
                'nama' => 'Dosen Pengampu',
                'role' => 'dosen'
            ]
        ];

        $stmt = $this->pdo->prepare("
            INSERT INTO users (username, password, email, nama, role)
            VALUES (?, ?, ?, ?, ?)
            ON CONFLICT (username) DO NOTHING
        ");

        foreach ($users as $user) {
            $stmt->execute([
                $user['username'],
                $user['password'],
                $user['email'],
                $user['nama'],
                $user['role']
            ]);
        }
    }

    /**
     * Seed fakultas
     */
    private function seedFakultas(): void
    {
        echo "Seeding fakultas...\n";

        $fakultas = [
            ['id_fakultas' => 'FTI', 'nama_fakultas' => 'Fakultas Teknologi Informasi'],
            ['id_fakultas' => 'FEB', 'nama_fakultas' => 'Fakultas Ekonomi dan Bisnis'],
            ['id_fakultas' => 'FH', 'nama_fakultas' => 'Fakultas Hukum']
        ];

        $stmt = $this->pdo->prepare("
            INSERT INTO fakultas (id_fakultas, nama_fakultas)
            VALUES (?, ?)
            ON CONFLICT (id_fakultas) DO NOTHING
        ");

        foreach ($fakultas as $f) {
            $stmt->execute([$f['id_fakultas'], $f['nama_fakultas']]);
        }
    }

    /**
     * Seed prodi
     */
    private function seedProdi(): void
    {
        echo "Seeding prodi...\n";

        $prodi = [
            ['id_prodi' => 'TIF', 'id_fakultas' => 'FTI', 'nama_prodi' => 'Teknik Informatika', 'jenjang' => 'S1'],
            ['id_prodi' => 'SI', 'id_fakultas' => 'FTI', 'nama_prodi' => 'Sistem Informasi', 'jenjang' => 'S1'],
            ['id_prodi' => 'MAN', 'id_fakultas' => 'FEB', 'nama_prodi' => 'Manajemen', 'jenjang' => 'S1']
        ];

        $stmt = $this->pdo->prepare("
            INSERT INTO prodi (id_prodi, id_fakultas, nama_prodi, jenjang)
            VALUES (?, ?, ?, ?)
            ON CONFLICT (id_prodi) DO NOTHING
        ");

        foreach ($prodi as $p) {
            $stmt->execute([$p['id_prodi'], $p['id_fakultas'], $p['nama_prodi'], $p['jenjang']]);
        }
    }

    /**
     * Seed kurikulum
     */
    private function seedKurikulum(): void
    {
        echo "Seeding kurikulum...\n";

        $kurikulum = [
            [
                'id_prodi' => 'TIF',
                'kode_kurikulum' => 'K2024',
                'nama_kurikulum' => 'Kurikulum OBE 2024',
                'tahun_berlaku' => 2024,
                'status' => 'active'
            ],
            [
                'id_prodi' => 'SI',
                'kode_kurikulum' => 'K2024',
                'nama_kurikulum' => 'Kurikulum OBE 2024',
                'tahun_berlaku' => 2024,
                'status' => 'active'
            ]
        ];

        $stmt = $this->pdo->prepare("
            INSERT INTO kurikulum (id_prodi, kode_kurikulum, nama_kurikulum, tahun_berlaku, status)
            VALUES (?, ?, ?, ?, ?)
            ON CONFLICT DO NOTHING
        ");

        foreach ($kurikulum as $k) {
            $stmt->execute([
                $k['id_prodi'],
                $k['kode_kurikulum'],
                $k['nama_kurikulum'],
                $k['tahun_berlaku'],
                $k['status']
            ]);
        }
    }
}
