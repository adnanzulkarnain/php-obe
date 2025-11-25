<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * RealisasiPertemuan (Lecture Report / Berita Acara) Entity
 * Represents actual lecture delivery report
 */
class RealisasiPertemuan
{
    public ?int $id_realisasi = null;
    public int $id_kelas;
    public ?int $id_minggu = null; // Reference to rencana_mingguan
    public string $tanggal_pelaksanaan;

    // Lecture details
    public ?string $materi_disampaikan = null;
    public ?string $metode_digunakan = null;
    public ?string $kendala = null;
    public ?string $catatan_dosen = null;

    // Verification workflow
    public string $status = 'draft'; // draft, submitted, verified, rejected
    public ?string $verified_by = null; // ID dosen (kaprodi)
    public ?string $verified_at = null;
    public ?string $komentar_kaprodi = null;

    // Metadata
    public ?string $created_by = null; // ID dosen who created the report
    public ?string $created_at = null;
    public ?string $updated_at = null;

    // Additional info (joined from other tables)
    public ?string $nama_kelas = null;
    public ?string $kode_mk = null;
    public ?string $nama_mk = null;
    public ?int $minggu_ke = null;
    public ?string $nama_dosen = null;
    public ?string $nama_verifier = null;
    public ?string $hari = null;
    public ?string $jam_mulai = null;
    public ?string $jam_selesai = null;

    // Comparison with plan
    public ?array $rencana = null; // Original plan from rencana_mingguan

    public static function fromArray(array $data): self
    {
        $instance = new self();
        foreach ($data as $key => $value) {
            if (property_exists($instance, $key)) {
                // Convert string values to proper types
                if (in_array($key, ['id_realisasi', 'id_kelas', 'id_minggu', 'minggu_ke']) && $value !== null) {
                    $instance->$key = (int)$value;
                } else {
                    $instance->$key = $value;
                }
            }
        }
        return $instance;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public function validate(): array
    {
        $errors = [];

        if (empty($this->id_kelas)) {
            $errors[] = 'ID Kelas wajib diisi';
        }

        if (empty($this->tanggal_pelaksanaan)) {
            $errors[] = 'Tanggal pelaksanaan wajib diisi';
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->tanggal_pelaksanaan)) {
            $errors[] = 'Format tanggal pelaksanaan harus YYYY-MM-DD';
        }

        if (!empty($this->status) && !in_array($this->status, ['draft', 'submitted', 'verified', 'rejected'])) {
            $errors[] = 'Status harus salah satu dari: draft, submitted, verified, rejected';
        }

        return $errors;
    }

    /**
     * Check if this report can be edited
     */
    public function canEdit(): bool
    {
        return in_array($this->status, ['draft', 'rejected']);
    }

    /**
     * Check if this report can be submitted
     */
    public function canSubmit(): bool
    {
        return in_array($this->status, ['draft', 'rejected']);
    }

    /**
     * Check if this report can be verified
     */
    public function canVerify(): bool
    {
        return $this->status === 'submitted';
    }

    /**
     * Get status label in Indonesian
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            'draft' => 'Draft',
            'submitted' => 'Menunggu Verifikasi',
            'verified' => 'Terverifikasi',
            'rejected' => 'Ditolak',
            default => 'Unknown'
        };
    }
}
