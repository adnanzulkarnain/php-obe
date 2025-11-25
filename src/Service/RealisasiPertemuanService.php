<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\RealisasiPertemuan;
use App\Repository\RealisasiPertemuanRepository;
use App\Repository\KelasRepository;
use App\Repository\RencanaMinggualRepository;
use App\Repository\KehadiranRepository;

/**
 * RealisasiPertemuan Service
 * Business logic for lecture reports
 */
class RealisasiPertemuanService
{
    private RealisasiPertemuanRepository $repository;
    private KelasRepository $kelasRepo;
    private RencanaMinggualRepository $rencanaRepo;
    private KehadiranRepository $kehadiranRepo;
    private AuditLogService $auditLog;

    public function __construct()
    {
        $this->repository = new RealisasiPertemuanRepository();
        $this->kelasRepo = new KelasRepository();
        $this->rencanaRepo = new RencanaMinggualRepository();
        $this->kehadiranRepo = new KehadiranRepository();
        $this->auditLog = new AuditLogService();
    }

    /**
     * Create berita acara (lecture report)
     */
    public function create(array $data, int $userId, string $idDosen): array
    {
        // Create entity and validate
        $realisasi = RealisasiPertemuan::fromArray($data);
        $errors = $realisasi->validate();

        if (!empty($errors)) {
            throw new \Exception(implode(', ', $errors), 400);
        }

        // Check if kelas exists
        $kelas = $this->kelasRepo->find($realisasi->id_kelas);
        if (!$kelas) {
            throw new \Exception('Kelas tidak ditemukan', 404);
        }

        // Check if dosen is authorized to create report for this kelas
        if (!$this->isDosenAuthorized($idDosen, $realisasi->id_kelas)) {
            throw new \Exception('Anda tidak berwenang membuat berita acara untuk kelas ini', 403);
        }

        // Validate tanggal pelaksanaan (should not be in future)
        if (strtotime($realisasi->tanggal_pelaksanaan) > time()) {
            throw new \Exception('Tanggal pelaksanaan tidak boleh di masa depan', 400);
        }

        // Create realisasi
        $realisasiData = [
            'id_kelas' => $realisasi->id_kelas,
            'id_minggu' => $data['id_minggu'] ?? null,
            'tanggal_pelaksanaan' => $realisasi->tanggal_pelaksanaan,
            'materi_disampaikan' => $data['materi_disampaikan'] ?? null,
            'metode_digunakan' => $data['metode_digunakan'] ?? null,
            'kendala' => $data['kendala'] ?? null,
            'catatan_dosen' => $data['catatan_dosen'] ?? null,
            'status' => 'draft',
            'created_by' => $idDosen,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $idRealisasi = $this->repository->create($realisasiData);

        // If attendance data is provided, save it
        if (isset($data['kehadiran']) && is_array($data['kehadiran'])) {
            $this->saveKehadiran($idRealisasi, $data['kehadiran']);
        }

        // Audit log
        $this->auditLog->log(
            'realisasi_pertemuan',
            $idRealisasi,
            'INSERT',
            null,
            $realisasiData,
            $userId
        );

        return $this->repository->findByIdWithDetails($idRealisasi);
    }

    /**
     * Update berita acara
     */
    public function update(int $idRealisasi, array $data, int $userId, string $idDosen): array
    {
        $realisasi = $this->repository->find($idRealisasi);

        if (!$realisasi) {
            throw new \Exception('Berita acara tidak ditemukan', 404);
        }

        // Business rule: Can only update if status is draft or rejected
        if (!in_array($realisasi['status'], ['draft', 'rejected'])) {
            throw new \Exception('Berita acara hanya dapat diubah jika berstatus draft atau ditolak', 400);
        }

        // Check if dosen is the creator
        if ($realisasi['created_by'] !== $idDosen) {
            throw new \Exception('Anda tidak berwenang mengubah berita acara ini', 403);
        }

        // Prepare update data
        $updateData = [
            'id_minggu' => $data['id_minggu'] ?? $realisasi['id_minggu'],
            'tanggal_pelaksanaan' => $data['tanggal_pelaksanaan'] ?? $realisasi['tanggal_pelaksanaan'],
            'materi_disampaikan' => $data['materi_disampaikan'] ?? $realisasi['materi_disampaikan'],
            'metode_digunakan' => $data['metode_digunakan'] ?? $realisasi['metode_digunakan'],
            'kendala' => $data['kendala'] ?? $realisasi['kendala'],
            'catatan_dosen' => $data['catatan_dosen'] ?? $realisasi['catatan_dosen'],
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Validate tanggal if changed
        if (isset($data['tanggal_pelaksanaan']) && strtotime($updateData['tanggal_pelaksanaan']) > time()) {
            throw new \Exception('Tanggal pelaksanaan tidak boleh di masa depan', 400);
        }

        // Update realisasi
        $this->repository->update($idRealisasi, $updateData);

        // If attendance data is provided, update it
        if (isset($data['kehadiran']) && is_array($data['kehadiran'])) {
            // Delete old attendance and save new
            $this->kehadiranRepo->deleteByRealisasi($idRealisasi);
            $this->saveKehadiran($idRealisasi, $data['kehadiran']);
        }

        // Audit log
        $this->auditLog->log(
            'realisasi_pertemuan',
            $idRealisasi,
            'UPDATE',
            $realisasi,
            $updateData,
            $userId
        );

        return $this->repository->findByIdWithDetails($idRealisasi);
    }

    /**
     * Submit berita acara for verification
     */
    public function submitForVerification(int $idRealisasi, int $userId, string $idDosen): array
    {
        $realisasi = $this->repository->find($idRealisasi);

        if (!$realisasi) {
            throw new \Exception('Berita acara tidak ditemukan', 404);
        }

        // Check if dosen is the creator
        if ($realisasi['created_by'] !== $idDosen) {
            throw new \Exception('Anda tidak berwenang submit berita acara ini', 403);
        }

        // Business rule: Can only submit if status is draft or rejected
        if (!in_array($realisasi['status'], ['draft', 'rejected'])) {
            throw new \Exception('Berita acara hanya dapat disubmit jika berstatus draft atau ditolak', 400);
        }

        // Validate required fields before submission
        if (empty($realisasi['materi_disampaikan'])) {
            throw new \Exception('Materi yang disampaikan wajib diisi sebelum submit', 400);
        }

        // Submit for verification
        $success = $this->repository->submitForVerification($idRealisasi);

        if (!$success) {
            throw new \Exception('Gagal submit berita acara', 500);
        }

        // Audit log
        $this->auditLog->log(
            'realisasi_pertemuan',
            $idRealisasi,
            'SUBMIT',
            $realisasi,
            ['status' => 'submitted'],
            $userId
        );

        return $this->repository->findByIdWithDetails($idRealisasi);
    }

    /**
     * Verify berita acara (by kaprodi)
     */
    public function verify(
        int $idRealisasi,
        bool $approved,
        ?string $komentar,
        int $userId,
        string $kaprodiId
    ): array {
        $realisasi = $this->repository->find($idRealisasi);

        if (!$realisasi) {
            throw new \Exception('Berita acara tidak ditemukan', 404);
        }

        // Business rule: Can only verify if status is submitted
        if ($realisasi['status'] !== 'submitted') {
            throw new \Exception('Berita acara hanya dapat diverifikasi jika berstatus submitted', 400);
        }

        // Check if kaprodi is authorized (same prodi)
        // TODO: Add proper authorization check based on prodi

        // Determine new status
        $newStatus = $approved ? 'verified' : 'rejected';

        // Update verification status
        $success = $this->repository->updateVerificationStatus(
            $idRealisasi,
            $newStatus,
            $kaprodiId,
            $komentar
        );

        if (!$success) {
            throw new \Exception('Gagal memverifikasi berita acara', 500);
        }

        // Audit log
        $this->auditLog->log(
            'realisasi_pertemuan',
            $idRealisasi,
            'VERIFY',
            $realisasi,
            [
                'status' => $newStatus,
                'verified_by' => $kaprodiId,
                'komentar_kaprodi' => $komentar
            ],
            $userId
        );

        return $this->repository->findByIdWithDetails($idRealisasi);
    }

    /**
     * Get berita acara by kelas
     */
    public function getByKelas(int $idKelas, ?array $filters = []): array
    {
        return $this->repository->findByKelas($idKelas, $filters);
    }

    /**
     * Get berita acara by dosen
     */
    public function getByDosen(string $idDosen, ?array $filters = []): array
    {
        return $this->repository->findByDosen($idDosen, $filters);
    }

    /**
     * Get pending verifications for kaprodi
     */
    public function getPendingVerificationForKaprodi(?string $kaprodiId = null): array
    {
        return $this->repository->findPendingVerification($kaprodiId);
    }

    /**
     * Compare berita acara with RPS plan
     */
    public function compareWithRPS(int $idRealisasi): array
    {
        $comparison = $this->repository->compareWithRencana($idRealisasi);

        if (!$comparison) {
            throw new \Exception('Berita acara tidak ditemukan', 404);
        }

        // Add comparison analysis
        $analysis = [
            'has_plan' => !empty($comparison['id_minggu']),
            'deviations' => []
        ];

        if ($analysis['has_plan']) {
            // Compare materials (simple text comparison)
            // In production, you might want more sophisticated comparison
            if (isset($comparison['rencana_materi']) && isset($comparison['materi_disampaikan'])) {
                $analysis['material_match'] = $this->compareTexts(
                    $comparison['materi_disampaikan'],
                    json_decode($comparison['rencana_materi'], true)
                );
            }
        }

        $comparison['analysis'] = $analysis;

        return $comparison;
    }

    /**
     * Get statistics by kelas
     */
    public function getStatisticsByKelas(int $idKelas): array
    {
        return $this->repository->getStatisticsByKelas($idKelas);
    }

    /**
     * Get statistics by dosen
     */
    public function getStatisticsByDosen(string $idDosen): array
    {
        return $this->repository->getStatisticsByDosen($idDosen);
    }

    /**
     * Delete berita acara
     */
    public function delete(int $idRealisasi, int $userId, string $idDosen, string $userRole): bool
    {
        $realisasi = $this->repository->find($idRealisasi);

        if (!$realisasi) {
            throw new \Exception('Berita acara tidak ditemukan', 404);
        }

        // Business rule: Can only delete if status is draft or if user is admin/kaprodi
        if ($realisasi['status'] !== 'draft' && !in_array($userRole, ['admin', 'kaprodi'])) {
            throw new \Exception('Berita acara hanya dapat dihapus jika berstatus draft', 400);
        }

        // Check if dosen is the creator or admin/kaprodi
        if ($realisasi['created_by'] !== $idDosen && !in_array($userRole, ['admin', 'kaprodi'])) {
            throw new \Exception('Anda tidak berwenang menghapus berita acara ini', 403);
        }

        // Delete attendance first
        $this->kehadiranRepo->deleteByRealisasi($idRealisasi);

        // Delete realisasi
        $success = $this->repository->delete($idRealisasi);

        // Audit log
        $this->auditLog->log(
            'realisasi_pertemuan',
            $idRealisasi,
            'DELETE',
            $realisasi,
            null,
            $userId
        );

        return $success;
    }

    /**
     * Save attendance records
     */
    private function saveKehadiran(int $idRealisasi, array $kehadiranData): void
    {
        $records = [];

        foreach ($kehadiranData as $record) {
            $records[] = [
                'id_realisasi' => $idRealisasi,
                'nim' => $record['nim'],
                'status' => $record['status'] ?? 'alpha',
                'keterangan' => $record['keterangan'] ?? null
            ];
        }

        if (!empty($records)) {
            $this->kehadiranRepo->bulkInsert($records);
        }
    }

    /**
     * Check if dosen is authorized to create report for kelas
     */
    private function isDosenAuthorized(string $idDosen, int $idKelas): bool
    {
        // Check if dosen is assigned to this kelas
        $pengampu = $this->kelasRepo->getDosenByKelas($idKelas);

        foreach ($pengampu as $dosen) {
            if ($dosen['id_dosen'] === $idDosen) {
                return true;
            }
        }

        return false;
    }

    /**
     * Simple text comparison helper
     */
    private function compareTexts(string $actual, $planned): float
    {
        if (is_array($planned)) {
            $planned = implode(' ', $planned);
        }

        // Simple similarity check
        similar_text(strtolower($actual), strtolower($planned), $percent);
        return round($percent, 2);
    }

    /**
     * Get repository instance
     */
    public function getRepository(): RealisasiPertemuanRepository
    {
        return $this->repository;
    }
}
