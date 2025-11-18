<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Kelas;
use App\Entity\TugasMengajar;
use App\Repository\KelasRepository;
use App\Repository\TugasMengajarRepository;
use App\Repository\MataKuliahRepository;

/**
 * Kelas Service
 */
class KelasService
{
    private KelasRepository $repository;
    private TugasMengajarRepository $tugasMengajarRepo;
    private MataKuliahRepository $mataKuliahRepo;
    private AuditLogService $auditLog;

    public function __construct()
    {
        $this->repository = new KelasRepository();
        $this->tugasMengajarRepo = new TugasMengajarRepository();
        $this->mataKuliahRepo = new MataKuliahRepository();
        $this->auditLog = new AuditLogService();
    }

    /**
     * Create Kelas
     */
    public function create(array $data, int $userId): array
    {
        // Create entity and validate
        $kelas = Kelas::fromArray($data);
        $errors = $kelas->validate();

        if (!empty($errors)) {
            throw new \Exception(implode(', ', $errors), 400);
        }

        // Check if mata kuliah exists
        $mk = $this->mataKuliahRepo->findByKodeAndKurikulum($kelas->kode_mk, $kelas->id_kurikulum);
        if (!$mk) {
            throw new \Exception('Mata Kuliah tidak ditemukan', 404);
        }

        // Check if kelas already exists
        if ($this->repository->exists(
            $kelas->kode_mk,
            $kelas->id_kurikulum,
            $kelas->nama_kelas,
            $kelas->semester,
            $kelas->tahun_ajaran
        )) {
            throw new \Exception('Kelas dengan nama yang sama sudah ada untuk MK, semester, dan tahun ajaran ini', 400);
        }

        // Create kelas
        $kelasData = [
            'kode_mk' => $kelas->kode_mk,
            'id_kurikulum' => $kelas->id_kurikulum,
            'id_rps' => $kelas->id_rps,
            'nama_kelas' => $kelas->nama_kelas,
            'semester' => $kelas->semester,
            'tahun_ajaran' => $kelas->tahun_ajaran,
            'kapasitas' => $kelas->kapasitas,
            'kuota_terisi' => 0,
            'hari' => $kelas->hari,
            'jam_mulai' => $kelas->jam_mulai,
            'jam_selesai' => $kelas->jam_selesai,
            'ruangan' => $kelas->ruangan,
            'status' => $kelas->status,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $idKelas = $this->repository->create($kelasData);

        // Audit log
        $this->auditLog->log(
            'kelas',
            $idKelas,
            'INSERT',
            null,
            $kelasData,
            $userId
        );

        return $this->repository->findByIdWithDetails($idKelas);
    }

    /**
     * Update Kelas
     */
    public function update(int $idKelas, array $data, int $userId): array
    {
        $kelas = $this->repository->find($idKelas);

        if (!$kelas) {
            throw new \Exception('Kelas tidak ditemukan', 404);
        }

        // Prepare update data
        $updateData = [
            'nama_kelas' => $data['nama_kelas'] ?? $kelas['nama_kelas'],
            'kapasitas' => $data['kapasitas'] ?? $kelas['kapasitas'],
            'hari' => $data['hari'] ?? $kelas['hari'],
            'jam_mulai' => $data['jam_mulai'] ?? $kelas['jam_mulai'],
            'jam_selesai' => $data['jam_selesai'] ?? $kelas['jam_selesai'],
            'ruangan' => $data['ruangan'] ?? $kelas['ruangan'],
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // Validate new capacity against current enrollment
        if (isset($data['kapasitas'])) {
            $newCapacity = (int)$data['kapasitas'];
            if ($newCapacity < $kelas['kuota_terisi']) {
                throw new \Exception('Kapasitas tidak boleh lebih kecil dari jumlah mahasiswa yang sudah terdaftar (' . $kelas['kuota_terisi'] . ')', 400);
            }
        }

        // Create entity for validation
        $kelasEntity = Kelas::fromArray(array_merge($kelas, $updateData));
        $errors = $kelasEntity->validate();

        if (!empty($errors)) {
            throw new \Exception(implode(', ', $errors), 400);
        }

        // Update
        $this->repository->update($idKelas, $updateData);

        // Audit log
        $this->auditLog->log(
            'kelas',
            $idKelas,
            'UPDATE',
            $kelas,
            array_merge($kelas, $updateData),
            $userId
        );

        return $this->repository->findByIdWithDetails($idKelas);
    }

    /**
     * Change status
     */
    public function changeStatus(int $idKelas, string $status, int $userId): array
    {
        $kelas = $this->repository->find($idKelas);

        if (!$kelas) {
            throw new \Exception('Kelas tidak ditemukan', 404);
        }

        // Validate status
        if (!in_array($status, ['draft', 'open', 'closed', 'completed'])) {
            throw new \Exception('Status tidak valid', 400);
        }

        // Business rules for status changes
        if ($status === 'open') {
            // Must have at least one koordinator
            if (!$this->tugasMengajarRepo->hasKoordinator($idKelas)) {
                throw new \Exception('Kelas harus memiliki minimal satu dosen koordinator sebelum dibuka', 400);
            }
        }

        if ($status === 'completed') {
            // Can only complete if currently closed
            if ($kelas['status'] !== 'closed') {
                throw new \Exception('Kelas harus berstatus "closed" sebelum dapat diselesaikan', 400);
            }
        }

        // Update status
        $this->repository->changeStatus($idKelas, $status);

        // Audit log
        $this->auditLog->log(
            'kelas',
            $idKelas,
            'UPDATE',
            ['status' => $kelas['status']],
            ['status' => $status],
            $userId
        );

        return $this->repository->findByIdWithDetails($idKelas);
    }

    /**
     * Get kelas by ID
     */
    public function getById(int $idKelas): array
    {
        $kelas = $this->repository->findWithTeachingAssignments($idKelas);

        if (!$kelas) {
            throw new \Exception('Kelas tidak ditemukan', 404);
        }

        return $kelas;
    }

    /**
     * Get kelas by mata kuliah
     */
    public function getByMataKuliah(string $kodeMk, int $idKurikulum, ?array $filters = []): array
    {
        return $this->repository->findByMataKuliah($kodeMk, $idKurikulum, $filters);
    }

    /**
     * Get kelas by kurikulum
     */
    public function getByKurikulum(int $idKurikulum, ?array $filters = []): array
    {
        return $this->repository->findByKurikulum($idKurikulum, $filters);
    }

    /**
     * Get kelas by semester and tahun ajaran
     */
    public function getBySemesterTahunAjaran(string $semester, string $tahunAjaran, ?int $idKurikulum = null): array
    {
        return $this->repository->findBySemesterTahunAjaran($semester, $tahunAjaran, $idKurikulum);
    }

    /**
     * Delete kelas
     */
    public function delete(int $idKelas, int $userId): void
    {
        $kelas = $this->repository->find($idKelas);

        if (!$kelas) {
            throw new \Exception('Kelas tidak ditemukan', 404);
        }

        // Business rule: Cannot delete if has enrollments
        $enrollmentCount = $this->repository->getEnrollmentCount($idKelas);
        if ($enrollmentCount > 0) {
            throw new \Exception('Tidak dapat menghapus kelas yang sudah memiliki mahasiswa terdaftar', 400);
        }

        // Delete teaching assignments first
        $this->tugasMengajarRepo->removeAllByKelas($idKelas);

        // Delete kelas
        $this->repository->delete($idKelas);

        // Audit log
        $this->auditLog->log(
            'kelas',
            $idKelas,
            'DELETE',
            $kelas,
            null,
            $userId
        );
    }

    /**
     * Assign dosen to kelas (Teaching Assignment)
     */
    public function assignDosen(int $idKelas, array $data, int $userId): array
    {
        // Validate kelas exists
        $kelas = $this->repository->find($idKelas);
        if (!$kelas) {
            throw new \Exception('Kelas tidak ditemukan', 404);
        }

        // Create entity and validate
        $tugasMengajar = TugasMengajar::fromArray(array_merge($data, ['id_kelas' => $idKelas]));
        $errors = $tugasMengajar->validate();

        if (!empty($errors)) {
            throw new \Exception(implode(', ', $errors), 400);
        }

        // Check if dosen already assigned
        if ($this->tugasMengajarRepo->isDosenAssigned($idKelas, $tugasMengajar->id_dosen)) {
            throw new \Exception('Dosen sudah ditugaskan pada kelas ini', 400);
        }

        // Business rule: Only one koordinator per kelas
        if ($tugasMengajar->peran === 'koordinator') {
            if ($this->tugasMengajarRepo->hasKoordinator($idKelas)) {
                throw new \Exception('Kelas sudah memiliki koordinator. Hapus koordinator yang ada terlebih dahulu atau ubah perannya.', 400);
            }
        }

        // Create assignment
        $assignmentData = [
            'id_kelas' => $idKelas,
            'id_dosen' => $tugasMengajar->id_dosen,
            'peran' => $tugasMengajar->peran,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $idTugas = $this->tugasMengajarRepo->create($assignmentData);

        // Audit log
        $this->auditLog->log(
            'tugas_mengajar',
            $idTugas,
            'INSERT',
            null,
            $assignmentData,
            $userId
        );

        return $this->tugasMengajarRepo->find($idTugas);
    }

    /**
     * Remove dosen from kelas
     */
    public function removeDosen(int $idKelas, string $idDosen, int $userId): void
    {
        // Validate kelas exists
        $kelas = $this->repository->find($idKelas);
        if (!$kelas) {
            throw new \Exception('Kelas tidak ditemukan', 404);
        }

        // Check if assignment exists
        if (!$this->tugasMengajarRepo->isDosenAssigned($idKelas, $idDosen)) {
            throw new \Exception('Dosen tidak ditugaskan pada kelas ini', 404);
        }

        // Business rule: Cannot remove last dosen
        $assignmentCount = $this->tugasMengajarRepo->countByKelas($idKelas);
        if ($assignmentCount <= 1) {
            throw new \Exception('Tidak dapat menghapus dosen terakhir dari kelas', 400);
        }

        // Get assignment for audit log
        $assignment = $this->tugasMengajarRepo->findOne([
            'id_kelas' => $idKelas,
            'id_dosen' => $idDosen
        ]);

        // Remove assignment
        $this->tugasMengajarRepo->removeAssignment($idKelas, $idDosen);

        // Audit log
        $this->auditLog->log(
            'tugas_mengajar',
            $assignment['id_tugas'],
            'DELETE',
            $assignment,
            null,
            $userId
        );
    }

    /**
     * Update dosen peran
     */
    public function updateDosenPeran(int $idKelas, string $idDosen, string $peran, int $userId): array
    {
        // Validate kelas exists
        $kelas = $this->repository->find($idKelas);
        if (!$kelas) {
            throw new \Exception('Kelas tidak ditemukan', 404);
        }

        // Validate peran
        if (!in_array($peran, ['koordinator', 'pengampu', 'asisten'])) {
            throw new \Exception('Peran tidak valid', 400);
        }

        // Check if assignment exists
        if (!$this->tugasMengajarRepo->isDosenAssigned($idKelas, $idDosen)) {
            throw new \Exception('Dosen tidak ditugaskan pada kelas ini', 404);
        }

        // Get current assignment
        $currentAssignment = $this->tugasMengajarRepo->findOne([
            'id_kelas' => $idKelas,
            'id_dosen' => $idDosen
        ]);

        // Business rule: Only one koordinator per kelas
        if ($peran === 'koordinator') {
            $koordinator = $this->tugasMengajarRepo->findKoordinator($idKelas);
            if ($koordinator && $koordinator['id_dosen'] !== $idDosen) {
                throw new \Exception('Kelas sudah memiliki koordinator. Hapus koordinator yang ada terlebih dahulu atau ubah perannya.', 400);
            }
        }

        // Update peran
        $this->tugasMengajarRepo->updatePeran($idKelas, $idDosen, $peran);

        // Audit log
        $this->auditLog->log(
            'tugas_mengajar',
            $currentAssignment['id_tugas'],
            'UPDATE',
            ['peran' => $currentAssignment['peran']],
            ['peran' => $peran],
            $userId
        );

        return $this->tugasMengajarRepo->find($currentAssignment['id_tugas']);
    }

    /**
     * Get teaching assignments by kelas
     */
    public function getTeachingAssignments(int $idKelas): array
    {
        // Validate kelas exists
        $kelas = $this->repository->find($idKelas);
        if (!$kelas) {
            throw new \Exception('Kelas tidak ditemukan', 404);
        }

        return $this->tugasMengajarRepo->findByKelas($idKelas);
    }

    /**
     * Get teaching assignments by dosen
     */
    public function getTeachingAssignmentsByDosen(string $idDosen, ?array $filters = []): array
    {
        return $this->tugasMengajarRepo->findByDosen($idDosen, $filters);
    }

    /**
     * Get statistics
     */
    public function getStatistics(string $semester, string $tahunAjaran, ?int $idKurikulum = null): array
    {
        return $this->repository->getStatistics($semester, $tahunAjaran, $idKurikulum);
    }

    /**
     * Get teaching load stats for dosen
     */
    public function getTeachingLoadStats(string $idDosen, string $semester, string $tahunAjaran): array
    {
        return $this->tugasMengajarRepo->getTeachingLoadStats($idDosen, $semester, $tahunAjaran);
    }
}
