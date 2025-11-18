<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\KehadiranRepository;
use App\Repository\KelasRepository;

/**
 * Kehadiran Service
 * Business logic for attendance management
 */
class KehadiranService
{
    private KehadiranRepository $repository;
    private KelasRepository $kelasRepository;
    private AuditLogService $auditLog;

    public function __construct()
    {
        $this->repository = new KehadiranRepository();
        $this->kelasRepository = new KelasRepository();
        $this->auditLog = new AuditLogService();
    }

    // ========== REALISASI PERTEMUAN METHODS ==========

    /**
     * Get realisasi pertemuan by kelas
     */
    public function getRealisasiByKelas(int $idKelas): array
    {
        return $this->repository->findRealisasiByKelas($idKelas);
    }

    /**
     * Get realisasi pertemuan by ID
     */
    public function getRealisasiById(int $idRealisasi): array
    {
        $realisasi = $this->repository->findRealisasiWithDetails($idRealisasi);

        if (!$realisasi) {
            throw new \Exception('Realisasi pertemuan tidak ditemukan', 404);
        }

        // Get kehadiran
        $kehadiran = $this->repository->findKehadiranByRealisasi($idRealisasi);
        $realisasi['kehadiran'] = $kehadiran;

        return $realisasi;
    }

    /**
     * Create realisasi pertemuan
     */
    public function createRealisasi(array $data, int $userId, string $idDosen): array
    {
        // Validate required fields
        $required = ['id_kelas', 'tanggal_pelaksanaan'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \Exception("Field {$field} wajib diisi", 400);
            }
        }

        // Check if kelas exists
        $kelas = $this->kelasRepository->find($data['id_kelas']);
        if (!$kelas) {
            throw new \Exception('Kelas tidak ditemukan', 404);
        }

        // Prepare data
        $realisasiData = [
            'id_kelas' => (int)$data['id_kelas'],
            'id_minggu' => !empty($data['id_minggu']) ? (int)$data['id_minggu'] : null,
            'tanggal_pelaksanaan' => $data['tanggal_pelaksanaan'],
            'materi_disampaikan' => $data['materi_disampaikan'] ?? null,
            'metode_digunakan' => $data['metode_digunakan'] ?? null,
            'kendala' => $data['kendala'] ?? null,
            'catatan_dosen' => $data['catatan_dosen'] ?? null,
            'created_by' => $idDosen
        ];

        // Create realisasi
        $idRealisasi = $this->repository->createRealisasi($realisasiData);

        // Audit log
        $this->auditLog->log('realisasi_pertemuan', $idRealisasi, 'INSERT', null, $realisasiData, $userId);

        return $this->repository->findRealisasiWithDetails($idRealisasi);
    }

    /**
     * Update realisasi pertemuan
     */
    public function updateRealisasi(int $idRealisasi, array $data, int $userId): array
    {
        // Check if exists
        $existing = $this->repository->find($idRealisasi);
        if (!$existing) {
            throw new \Exception('Realisasi pertemuan tidak ditemukan', 404);
        }

        // Prepare update data
        $updateData = [];
        $allowedFields = ['id_minggu', 'tanggal_pelaksanaan', 'materi_disampaikan', 'metode_digunakan', 'kendala', 'catatan_dosen'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (empty($updateData)) {
            throw new \Exception('Tidak ada data yang diupdate', 400);
        }

        // Update realisasi
        $this->repository->updateRealisasi($idRealisasi, $updateData);

        // Audit log
        $this->auditLog->log('realisasi_pertemuan', $idRealisasi, 'UPDATE', $existing, $updateData, $userId);

        return $this->repository->findRealisasiWithDetails($idRealisasi);
    }

    /**
     * Delete realisasi pertemuan
     */
    public function deleteRealisasi(int $idRealisasi, int $userId): void
    {
        // Check if exists
        $realisasi = $this->repository->find($idRealisasi);
        if (!$realisasi) {
            throw new \Exception('Realisasi pertemuan tidak ditemukan', 404);
        }

        // Delete realisasi (will cascade delete kehadiran)
        $this->repository->deleteRealisasi($idRealisasi);

        // Audit log
        $this->auditLog->log('realisasi_pertemuan', $idRealisasi, 'DELETE', $realisasi, null, $userId);
    }

    // ========== KEHADIRAN METHODS ==========

    /**
     * Input kehadiran (bulk)
     */
    public function inputKehadiran(int $idRealisasi, array $kehadiranList, int $userId): array
    {
        // Check if realisasi exists
        $realisasi = $this->repository->find($idRealisasi);
        if (!$realisasi) {
            throw new \Exception('Realisasi pertemuan tidak ditemukan', 404);
        }

        $results = [
            'success' => [],
            'failed' => []
        ];

        foreach ($kehadiranList as $item) {
            try {
                if (empty($item['nim']) || empty($item['status'])) {
                    throw new \Exception('nim dan status wajib diisi');
                }

                // Validate status
                $validStatuses = ['hadir', 'izin', 'sakit', 'alpha'];
                if (!in_array($item['status'], $validStatuses)) {
                    throw new \Exception('Status tidak valid');
                }

                $kehadiranData = [
                    'id_realisasi' => $idRealisasi,
                    'nim' => $item['nim'],
                    'status' => $item['status'],
                    'keterangan' => $item['keterangan'] ?? null
                ];

                $idKehadiran = $this->repository->upsertKehadiran($kehadiranData);

                $results['success'][] = [
                    'nim' => $item['nim'],
                    'id_kehadiran' => $idKehadiran
                ];

                // Audit log
                $this->auditLog->log('kehadiran', $idKehadiran, 'UPSERT', null, $kehadiranData, $userId);
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'nim' => $item['nim'] ?? null,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Get kehadiran by mahasiswa
     */
    public function getKehadiranByMahasiswa(string $nim, int $idKelas): array
    {
        return $this->repository->findKehadiranByMahasiswa($nim, $idKelas);
    }

    /**
     * Get attendance summary by kelas
     */
    public function getAttendanceSummary(int $idKelas): array
    {
        return $this->repository->getAttendanceSummary($idKelas);
    }
}
