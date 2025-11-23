<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Repository\PenilaianRepository;
use App\Repository\EnrollmentRepository;
use App\Repository\KelasRepository;
use App\Middleware\AuthMiddleware;

/**
 * Analytics Controller
 * Provides dashboard data and reporting for OBE analytics
 */
class AnalyticsController
{
    private PenilaianRepository $penilaianRepo;
    private EnrollmentRepository $enrollmentRepo;
    private KelasRepository $kelasRepo;

    public function __construct()
    {
        $this->penilaianRepo = new PenilaianRepository();
        $this->enrollmentRepo = new EnrollmentRepository();
        $this->kelasRepo = new KelasRepository();
    }

    /**
     * Get dashboard overview
     * GET /api/analytics/dashboard
     */
    public function getDashboard(): void
    {
        try {
            $idProdi = Request::get('id_prodi');
            $tahunAjaran = Request::get('tahun_ajaran');

            $data = [
                'summary' => $this->getSummaryStats($idProdi, $tahunAjaran),
                'recent_activity' => $this->getRecentActivity(),
                'alerts' => $this->getAlerts($idProdi)
            ];

            Response::success($data);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Get CPMK achievement report by kelas
     * GET /api/analytics/kelas/:id/cpmk-report
     */
    public function getCPMKReportByKelas(string $idKelas): void
    {
        try {
            // Get kelas info
            $kelas = $this->kelasRepo->find((int)$idKelas);
            if (!$kelas) {
                Response::error('Kelas tidak ditemukan', 404);
                return;
            }

            // Get CPMK achievements aggregated by kelas
            $sql = "
                SELECT
                    c.id_cpmk,
                    c.kode_cpmk,
                    c.deskripsi,
                    COUNT(DISTINCT kc.id_enrollment) as jumlah_mahasiswa,
                    AVG(kc.nilai_cpmk) as rata_rata_nilai,
                    MIN(kc.nilai_cpmk) as nilai_min,
                    MAX(kc.nilai_cpmk) as nilai_max,
                    COUNT(CASE WHEN kc.status_tercapai = TRUE THEN 1 END) as jumlah_lulus,
                    ROUND(COUNT(CASE WHEN kc.status_tercapai = TRUE THEN 1 END)::NUMERIC / NULLIF(COUNT(DISTINCT kc.id_enrollment), 0) * 100, 2) as persentase_lulus
                FROM cpmk c
                JOIN kelas k ON c.id_rps = k.id_rps
                LEFT JOIN enrollment e ON k.id_kelas = e.id_kelas
                LEFT JOIN ketercapaian_cpmk kc ON e.id_enrollment = kc.id_enrollment AND c.id_cpmk = kc.id_cpmk
                WHERE k.id_kelas = :id_kelas
                GROUP BY c.id_cpmk, c.kode_cpmk, c.deskripsi, c.urutan
                ORDER BY c.urutan ASC
            ";

            $cpmkData = $this->penilaianRepo->query($sql, ['id_kelas' => (int)$idKelas]);

            $result = [
                'kelas' => $kelas,
                'cpmk_achievements' => $cpmkData
            ];

            Response::success($result);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Get CPL achievement report by kurikulum
     * GET /api/analytics/kurikulum/:id/cpl-report
     */
    public function getCPLReportByKurikulum(string $idKurikulum): void
    {
        try {
            $angkatan = Request::get('angkatan');

            $sql = "
                SELECT
                    cpl.id_cpl,
                    cpl.kode_cpl,
                    cpl.deskripsi,
                    cpl.kategori,
                    COUNT(DISTINCT kcpl.id_enrollment) as jumlah_mahasiswa,
                    AVG(kcpl.nilai_cpl) as rata_rata_nilai,
                    MIN(kcpl.nilai_cpl) as nilai_min,
                    MAX(kcpl.nilai_cpl) as nilai_max,
                    COUNT(CASE WHEN kcpl.status_tercapai = TRUE THEN 1 END) as jumlah_lulus,
                    ROUND(COUNT(CASE WHEN kcpl.status_tercapai = TRUE THEN 1 END)::NUMERIC / NULLIF(COUNT(DISTINCT kcpl.id_enrollment), 0) * 100, 2) as persentase_lulus
                FROM cpl
                LEFT JOIN ketercapaian_cpl kcpl ON cpl.id_cpl = kcpl.id_cpl
                LEFT JOIN enrollment e ON kcpl.id_enrollment = e.id_enrollment
                LEFT JOIN mahasiswa m ON e.nim = m.nim
                WHERE cpl.id_kurikulum = :id_kurikulum
            ";

            $params = ['id_kurikulum' => (int)$idKurikulum];

            if ($angkatan) {
                $sql .= " AND m.angkatan = :angkatan";
                $params['angkatan'] = $angkatan;
            }

            $sql .= " GROUP BY cpl.id_cpl, cpl.kode_cpl, cpl.deskripsi, cpl.kategori, cpl.urutan
                      ORDER BY cpl.kategori, cpl.urutan ASC";

            $cplData = $this->penilaianRepo->query($sql, $params);

            Response::success([
                'id_kurikulum' => (int)$idKurikulum,
                'angkatan' => $angkatan,
                'cpl_achievements' => $cplData
            ]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Get mahasiswa performance detail
     * GET /api/analytics/mahasiswa/:nim/performance
     * Optimized: Reduced from 4 separate queries to 1 query using CTEs
     */
    public function getMahasiswaPerformance(string $nim): void
    {
        try {
            // Get all performance data in a single optimized query
            $enrollments = $this->enrollmentRepo->getMahasiswaPerformanceData($nim);

            if (empty($enrollments)) {
                Response::success([
                    'nim' => $nim,
                    'enrollments' => [],
                    'cpmk_achievements' => [],
                    'cpl_achievements' => [],
                    'gpa' => 0
                ]);
                return;
            }

            // Extract GPA from first row (same for all rows)
            $gpa = $enrollments[0]['gpa'] ?? 0;

            // Collect all CPMK and CPL achievements from enrollments
            $allCpmkAchievements = [];
            $allCplAchievements = [];

            foreach ($enrollments as &$enrollment) {
                // Decode JSON achievements
                $cpmkData = json_decode($enrollment['cpmk_achievements'] ?? '[]', true);
                $cplData = json_decode($enrollment['cpl_achievements'] ?? '[]', true);

                // Remove JSON fields from enrollment to keep response clean
                unset($enrollment['cpmk_achievements']);
                unset($enrollment['cpl_achievements']);
                unset($enrollment['gpa']);

                // Collect achievements
                if (is_array($cpmkData)) {
                    $allCpmkAchievements = array_merge($allCpmkAchievements, $cpmkData);
                }
                if (is_array($cplData)) {
                    $allCplAchievements = array_merge($allCplAchievements, $cplData);
                }
            }

            Response::success([
                'nim' => $nim,
                'enrollments' => $enrollments,
                'cpmk_achievements' => $allCpmkAchievements,
                'cpl_achievements' => $allCplAchievements,
                'gpa' => (float)$gpa
            ]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Get trending data for analytics
     * GET /api/analytics/trends
     */
    public function getTrends(): void
    {
        try {
            $idProdi = Request::get('id_prodi');
            $startYear = Request::get('start_year', date('Y') - 2);
            $endYear = Request::get('end_year', date('Y'));

            // Trend data by tahun_ajaran
            $sql = "
                SELECT
                    k.tahun_ajaran,
                    AVG(e.nilai_akhir) as rata_nilai,
                    COUNT(DISTINCT e.nim) as jumlah_mahasiswa,
                    COUNT(CASE WHEN e.nilai_huruf IN ('A', 'A-', 'AB', 'B+', 'B') THEN 1 END) as jumlah_lulus_baik
                FROM kelas k
                JOIN enrollment e ON k.id_kelas = e.id_kelas
                JOIN matakuliah mk ON k.kode_mk = mk.kode_mk AND k.id_kurikulum = mk.id_kurikulum
                WHERE k.tahun_ajaran BETWEEN :start_year AND :end_year
            ";

            $params = [
                'start_year' => $startYear,
                'end_year' => $endYear
            ];

            if ($idProdi) {
                $sql .= " AND mk.id_kurikulum IN (SELECT id_kurikulum FROM kurikulum WHERE id_prodi = :id_prodi)";
                $params['id_prodi'] = $idProdi;
            }

            $sql .= " GROUP BY k.tahun_ajaran ORDER BY k.tahun_ajaran ASC";

            $trends = $this->kelasRepo->query($sql, $params);

            Response::success(['trends' => $trends]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    // ===== PRIVATE HELPER METHODS =====

    private function getSummaryStats(?string $idProdi, ?string $tahunAjaran): array
    {
        $sql = "
            SELECT
                COUNT(DISTINCT k.id_kelas) as total_kelas,
                COUNT(DISTINCT e.nim) as total_mahasiswa,
                COUNT(DISTINCT CASE WHEN e.nilai_huruf IS NOT NULL THEN e.id_enrollment END) as nilai_diinput,
                AVG(CASE WHEN e.nilai_akhir > 0 THEN e.nilai_akhir END) as rata_nilai
            FROM kelas k
            LEFT JOIN enrollment e ON k.id_kelas = e.id_kelas
            WHERE 1=1
        ";

        $params = [];

        if ($tahunAjaran) {
            $sql .= " AND k.tahun_ajaran = :tahun_ajaran";
            $params['tahun_ajaran'] = $tahunAjaran;
        }

        if ($idProdi) {
            $sql .= " AND k.id_kurikulum IN (SELECT id_kurikulum FROM kurikulum WHERE id_prodi = :id_prodi)";
            $params['id_prodi'] = $idProdi;
        }

        return $this->kelasRepo->queryOne($sql, $params) ?: [];
    }

    private function getRecentActivity(): array
    {
        $sql = "
            SELECT
                al.action,
                al.table_name,
                al.record_id,
                al.created_at,
                u.username,
                u.user_type
            FROM audit_log al
            JOIN users u ON al.user_id = u.id_user
            WHERE al.table_name IN ('nilai_detail', 'ketercapaian_cpmk', 'ketercapaian_cpl', 'enrollment')
            ORDER BY al.created_at DESC
            LIMIT 10
        ";

        return $this->penilaianRepo->query($sql) ?: [];
    }

    private function getAlerts(?string $idProdi): array
    {
        // Get classes with low achievement rates
        $sql = "
            SELECT
                k.id_kelas,
                k.nama_kelas,
                mk.nama_mk,
                COUNT(DISTINCT e.id_enrollment) as total_mahasiswa,
                AVG(e.nilai_akhir) as rata_nilai
            FROM kelas k
            JOIN matakuliah mk ON k.kode_mk = mk.kode_mk AND k.id_kurikulum = mk.id_kurikulum
            LEFT JOIN enrollment e ON k.id_kelas = e.id_kelas
            WHERE k.tahun_ajaran >= :current_year
        ";

        $params = ['current_year' => date('Y')];

        if ($idProdi) {
            $sql .= " AND mk.id_kurikulum IN (SELECT id_kurikulum FROM kurikulum WHERE id_prodi = :id_prodi)";
            $params['id_prodi'] = $idProdi;
        }

        $sql .= " GROUP BY k.id_kelas, k.nama_kelas, mk.nama_mk
                  HAVING AVG(e.nilai_akhir) < 60
                  ORDER BY AVG(e.nilai_akhir) ASC
                  LIMIT 5";

        $lowPerformingKelas = $this->kelasRepo->query($sql, $params) ?: [];

        return array_map(function ($kelas) {
            return [
                'type' => 'low_performance',
                'message' => "Kelas {$kelas['nama_kelas']} memiliki rata-rata nilai rendah: " . round($kelas['rata_nilai'], 2),
                'data' => $kelas
            ];
        }, $lowPerformingKelas);
    }
}
