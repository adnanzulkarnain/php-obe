<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Service\RealisasiPertemuanService;
use App\Repository\KehadiranRepository;
use App\Middleware\AuthMiddleware;

/**
 * RealisasiPertemuan (Lecture Report) Controller
 */
class RealisasiPertemuanController
{
    private RealisasiPertemuanService $service;
    private KehadiranRepository $kehadiranRepo;

    public function __construct()
    {
        $this->service = new RealisasiPertemuanService();
        $this->kehadiranRepo = new KehadiranRepository();
    }

    /**
     * Get berita acara list with filters
     * GET /api/realisasi-pertemuan?id_kelas=1&status=submitted
     */
    public function index(): void
    {
        try {
            $idKelas = Request::input('id_kelas');
            $idDosen = Request::input('id_dosen');
            $status = Request::input('status');
            $mingguKe = Request::input('minggu_ke');
            $tanggalDari = Request::input('tanggal_dari');
            $tanggalSampai = Request::input('tanggal_sampai');

            $filters = [];
            if ($status) {
                $filters['status'] = $status;
            }
            if ($mingguKe) {
                $filters['minggu_ke'] = $mingguKe;
            }
            if ($tanggalDari) {
                $filters['tanggal_dari'] = $tanggalDari;
            }
            if ($tanggalSampai) {
                $filters['tanggal_sampai'] = $tanggalSampai;
            }

            if ($idKelas) {
                // Get by kelas
                $results = $this->service->getByKelas((int)$idKelas, $filters);
            } elseif ($idDosen) {
                // Get by dosen
                if (isset($filters['minggu_ke'])) {
                    unset($filters['minggu_ke']); // minggu_ke filter only for kelas
                }
                if ($idKelas) {
                    $filters['id_kelas'] = (int)$idKelas;
                }
                $results = $this->service->getByDosen($idDosen, $filters);
            } else {
                Response::error('Minimal id_kelas atau id_dosen harus diisi', 400);
                return;
            }

            Response::success($results);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get berita acara by ID with full details
     * GET /api/realisasi-pertemuan/:id
     */
    public function show(string $id): void
    {
        try {
            $realisasi = $this->service->getRepository()->findByIdWithDetails((int)$id);

            if (!$realisasi) {
                Response::error('Berita acara tidak ditemukan', 404);
                return;
            }

            // Get attendance data
            $kehadiran = $this->kehadiranRepo->findByRealisasi((int)$id);
            $realisasi['kehadiran'] = $kehadiran;

            // Get attendance summary
            $summary = $this->kehadiranRepo->getSummaryByRealisasi((int)$id);
            $realisasi['kehadiran_summary'] = $summary;

            Response::success($realisasi);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Create berita acara
     * POST /api/realisasi-pertemuan
     */
    public function create(): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'admin');

            $user = AuthMiddleware::user();
            $data = Request::only([
                'id_kelas',
                'id_minggu',
                'tanggal_pelaksanaan',
                'materi_disampaikan',
                'metode_digunakan',
                'kendala',
                'catatan_dosen',
                'kehadiran' // Array of attendance records
            ]);

            // Get id_dosen from user
            $idDosen = $user['ref_id']; // Assuming ref_id contains id_dosen for dosen users

            $realisasi = $this->service->create($data, $user['id_user'], $idDosen);

            Response::success($realisasi, 'Berita acara berhasil dibuat', 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Update berita acara
     * PUT /api/realisasi-pertemuan/:id
     */
    public function update(string $id): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'admin');

            $user = AuthMiddleware::user();
            $data = Request::only([
                'id_minggu',
                'tanggal_pelaksanaan',
                'materi_disampaikan',
                'metode_digunakan',
                'kendala',
                'catatan_dosen',
                'kehadiran'
            ]);

            $idDosen = $user['ref_id'];

            $realisasi = $this->service->update((int)$id, $data, $user['id_user'], $idDosen);

            Response::success($realisasi, 'Berita acara berhasil diperbarui');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Delete berita acara
     * DELETE /api/realisasi-pertemuan/:id
     */
    public function delete(string $id): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $idDosen = $user['ref_id'];
            $userRole = $user['user_type'];

            $success = $this->service->delete((int)$id, $user['id_user'], $idDosen, $userRole);

            if ($success) {
                Response::success(null, 'Berita acara berhasil dihapus');
            } else {
                Response::error('Gagal menghapus berita acara', 500);
            }
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Submit berita acara for verification
     * POST /api/realisasi-pertemuan/:id/submit
     */
    public function submit(string $id): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'admin');

            $user = AuthMiddleware::user();
            $idDosen = $user['ref_id'];

            $realisasi = $this->service->submitForVerification((int)$id, $user['id_user'], $idDosen);

            Response::success($realisasi, 'Berita acara berhasil disubmit untuk verifikasi');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Verify berita acara (by kaprodi)
     * POST /api/realisasi-pertemuan/:id/verify
     * Body: { "approved": true/false, "komentar": "..." }
     */
    public function verify(string $id): void
    {
        try {
            AuthMiddleware::requireRole('kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $kaprodiId = $user['ref_id'];

            $approved = Request::input('approved');
            $komentar = Request::input('komentar');

            if ($approved === null) {
                Response::error('Field approved wajib diisi (true/false)', 400);
                return;
            }

            $approved = filter_var($approved, FILTER_VALIDATE_BOOLEAN);

            $realisasi = $this->service->verify(
                (int)$id,
                $approved,
                $komentar,
                $user['id_user'],
                $kaprodiId
            );

            $message = $approved ? 'Berita acara berhasil diverifikasi' : 'Berita acara ditolak';
            Response::success($realisasi, $message);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get pending verifications for kaprodi
     * GET /api/realisasi-pertemuan/pending-verification
     */
    public function getPendingVerification(): void
    {
        try {
            AuthMiddleware::requireRole('kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $kaprodiId = $user['user_type'] === 'kaprodi' ? $user['ref_id'] : null;

            $results = $this->service->getPendingVerificationForKaprodi($kaprodiId);

            Response::success($results);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Compare berita acara with RPS plan
     * GET /api/realisasi-pertemuan/:id/compare-rps
     */
    public function compareWithRPS(string $id): void
    {
        try {
            $comparison = $this->service->compareWithRPS((int)$id);

            Response::success($comparison);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get statistics by kelas
     * GET /api/kelas/:id/realisasi-statistics
     */
    public function getStatisticsByKelas(string $idKelas): void
    {
        try {
            $stats = $this->service->getStatisticsByKelas((int)$idKelas);

            Response::success($stats);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get statistics by dosen
     * GET /api/dosen/:id_dosen/realisasi-statistics
     */
    public function getStatisticsByDosen(string $idDosen): void
    {
        try {
            $stats = $this->service->getStatisticsByDosen($idDosen);

            Response::success($stats);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get kehadiran (attendance) by realisasi
     * GET /api/realisasi-pertemuan/:id/kehadiran
     */
    public function getKehadiran(string $id): void
    {
        try {
            $kehadiran = $this->kehadiranRepo->findByRealisasi((int)$id);
            $summary = $this->kehadiranRepo->getSummaryByRealisasi((int)$id);

            Response::success([
                'kehadiran' => $kehadiran,
                'summary' => $summary
            ]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get kehadiran statistics by kelas
     * GET /api/kelas/:id/kehadiran-statistics
     */
    public function getKehadiranStatistics(string $idKelas): void
    {
        try {
            $stats = $this->kehadiranRepo->getStatisticsByKelas((int)$idKelas);

            Response::success($stats);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Export berita acara to PDF
     * GET /api/realisasi-pertemuan/:id/export-pdf
     */
    public function exportPDF(string $id): void
    {
        try {
            // Get realisasi with full details
            $realisasi = $this->service->getRepository()->findByIdWithDetails((int)$id);

            if (!$realisasi) {
                Response::error('Berita acara tidak ditemukan', 404);
                return;
            }

            // Get attendance data
            $kehadiran = $this->kehadiranRepo->findByRealisasi((int)$id);
            $summary = $this->kehadiranRepo->getSummaryByRealisasi((int)$id);

            // Generate PDF using mPDF
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_top' => 15,
                'margin_bottom' => 15,
                'margin_left' => 15,
                'margin_right' => 15
            ]);

            // Prepare HTML content
            $html = $this->generatePDFContent($realisasi, $kehadiran, $summary);

            $mpdf->WriteHTML($html);

            // Output PDF
            $filename = 'Berita_Acara_' . $realisasi['nama_mk'] . '_' . date('Ymd', strtotime($realisasi['tanggal_pelaksanaan'])) . '.pdf';
            $mpdf->Output($filename, 'D'); // D = download
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Generate PDF HTML content
     */
    private function generatePDFContent(array $realisasi, array $kehadiran, array $summary): string
    {
        $statusLabel = match($realisasi['status']) {
            'draft' => 'Draft',
            'submitted' => 'Menunggu Verifikasi',
            'verified' => 'Terverifikasi',
            'rejected' => 'Ditolak',
            default => 'Unknown'
        };

        $html = '
        <style>
            body { font-family: Arial, sans-serif; }
            h1 { text-align: center; color: #333; font-size: 18px; }
            h2 { color: #666; font-size: 14px; margin-top: 20px; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            table.info td { padding: 5px; }
            table.info td:first-child { width: 30%; font-weight: bold; }
            table.attendance { margin-top: 10px; }
            table.attendance th, table.attendance td { border: 1px solid #ddd; padding: 8px; }
            table.attendance th { background-color: #f5f5f5; }
            .section { margin-top: 20px; }
            .status { padding: 3px 10px; border-radius: 3px; display: inline-block; }
            .status.verified { background-color: #4CAF50; color: white; }
            .status.submitted { background-color: #FF9800; color: white; }
            .status.rejected { background-color: #F44336; color: white; }
            .status.draft { background-color: #9E9E9E; color: white; }
        </style>

        <h1>BERITA ACARA PERKULIAHAN</h1>

        <table class="info">
            <tr>
                <td>Mata Kuliah</td>
                <td>: ' . htmlspecialchars($realisasi['nama_mk']) . ' (' . htmlspecialchars($realisasi['kode_mk']) . ')</td>
            </tr>
            <tr>
                <td>Kelas</td>
                <td>: ' . htmlspecialchars($realisasi['nama_kelas']) . '</td>
            </tr>
            <tr>
                <td>Dosen Pengampu</td>
                <td>: ' . htmlspecialchars($realisasi['nama_dosen'] ?? '-') . '</td>
            </tr>
            <tr>
                <td>Tanggal Pelaksanaan</td>
                <td>: ' . date('d F Y', strtotime($realisasi['tanggal_pelaksanaan'])) . '</td>
            </tr>
            <tr>
                <td>Pertemuan Ke</td>
                <td>: ' . ($realisasi['minggu_ke'] ?? '-') . '</td>
            </tr>
            <tr>
                <td>Status</td>
                <td>: <span class="status ' . $realisasi['status'] . '">' . $statusLabel . '</span></td>
            </tr>
        </table>

        <div class="section">
            <h2>Materi yang Disampaikan</h2>
            <p>' . nl2br(htmlspecialchars($realisasi['materi_disampaikan'] ?? '-')) . '</p>
        </div>

        <div class="section">
            <h2>Metode Pembelajaran</h2>
            <p>' . nl2br(htmlspecialchars($realisasi['metode_digunakan'] ?? '-')) . '</p>
        </div>';

        if (!empty($realisasi['kendala'])) {
            $html .= '
        <div class="section">
            <h2>Kendala</h2>
            <p>' . nl2br(htmlspecialchars($realisasi['kendala'])) . '</p>
        </div>';
        }

        if (!empty($realisasi['catatan_dosen'])) {
            $html .= '
        <div class="section">
            <h2>Catatan Dosen</h2>
            <p>' . nl2br(htmlspecialchars($realisasi['catatan_dosen'])) . '</p>
        </div>';
        }

        // Attendance section
        if (!empty($kehadiran)) {
            $html .= '
        <div class="section">
            <h2>Daftar Kehadiran</h2>
            <p><strong>Total Mahasiswa:</strong> ' . ($summary['total_mahasiswa'] ?? 0) . ' |
               <strong>Hadir:</strong> ' . ($summary['hadir'] ?? 0) . ' |
               <strong>Izin:</strong> ' . ($summary['izin'] ?? 0) . ' |
               <strong>Sakit:</strong> ' . ($summary['sakit'] ?? 0) . ' |
               <strong>Alpha:</strong> ' . ($summary['alpha'] ?? 0) . ' |
               <strong>Persentase Kehadiran:</strong> ' . ($summary['persentase_kehadiran'] ?? 0) . '%</p>

            <table class="attendance">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th width="15%">NIM</th>
                        <th width="35%">Nama</th>
                        <th width="15%">Status</th>
                        <th width="30%">Keterangan</th>
                    </tr>
                </thead>
                <tbody>';

            $no = 1;
            foreach ($kehadiran as $row) {
                $statusKehadiran = match($row['status']) {
                    'hadir' => 'Hadir',
                    'izin' => 'Izin',
                    'sakit' => 'Sakit',
                    'alpha' => 'Alpha',
                    default => '-'
                };

                $html .= '
                    <tr>
                        <td style="text-align:center;">' . $no++ . '</td>
                        <td>' . htmlspecialchars($row['nim']) . '</td>
                        <td>' . htmlspecialchars($row['nama_mahasiswa'] ?? '-') . '</td>
                        <td style="text-align:center;">' . $statusKehadiran . '</td>
                        <td>' . htmlspecialchars($row['keterangan'] ?? '-') . '</td>
                    </tr>';
            }

            $html .= '
                </tbody>
            </table>
        </div>';
        }

        // Verification section
        if ($realisasi['status'] === 'verified' || $realisasi['status'] === 'rejected') {
            $html .= '
        <div class="section">
            <h2>Verifikasi</h2>
            <table class="info">
                <tr>
                    <td>Status Verifikasi</td>
                    <td>: ' . $statusLabel . '</td>
                </tr>
                <tr>
                    <td>Diverifikasi oleh</td>
                    <td>: ' . htmlspecialchars($realisasi['nama_verifier'] ?? '-') . '</td>
                </tr>
                <tr>
                    <td>Tanggal Verifikasi</td>
                    <td>: ' . ($realisasi['verified_at'] ? date('d F Y H:i', strtotime($realisasi['verified_at'])) : '-') . '</td>
                </tr>';

            if (!empty($realisasi['komentar_kaprodi'])) {
                $html .= '
                <tr>
                    <td>Komentar Kaprodi</td>
                    <td>: ' . nl2br(htmlspecialchars($realisasi['komentar_kaprodi'])) . '</td>
                </tr>';
            }

            $html .= '
            </table>
        </div>';
        }

        $html .= '
        <div style="margin-top: 40px;">
            <p style="text-align:right;">
                Dicetak pada: ' . date('d F Y H:i') . '
            </p>
        </div>';

        return $html;
    }

    /**
     * Get service instance (for accessing repository in show method)
     */
    private function getService(): RealisasiPertemuanService
    {
        return $this->service;
    }
}
