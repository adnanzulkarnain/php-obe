<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * RPS (Rencana Pembelajaran Semester) Entity
 * Represents semester learning plan for a course
 */
class RPS
{
    public ?int $id_rps = null;
    public string $kode_mk;
    public int $id_kurikulum;
    public string $semester_berlaku; // Ganjil, Genap
    public string $tahun_ajaran; // 2024/2025
    public string $status = 'draft'; // draft, submitted, revised, approved, active, archived
    public ?string $ketua_pengembang = null;
    public ?string $tanggal_disusun = null;

    // Deskripsi MK
    public ?string $deskripsi_mk = null;
    public ?string $deskripsi_singkat = null;

    // Metadata
    public ?string $created_by = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    // Additional info (joined from other tables)
    public ?string $nama_mk = null;
    public ?int $sks = null;
    public ?string $nama_ketua = null;
    public ?string $current_version = null;

    public static function fromArray(array $data): self
    {
        $instance = new self();
        foreach ($data as $key => $value) {
            if (property_exists($instance, $key)) {
                // Convert string values to proper types
                if ($key === 'id_rps' && $value !== null) {
                    $instance->$key = (int)$value;
                } elseif ($key === 'id_kurikulum') {
                    $instance->$key = (int)$value;
                } elseif ($key === 'sks' && $value !== null) {
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

        if (empty($this->kode_mk)) {
            $errors[] = 'Kode MK wajib diisi';
        }

        if (empty($this->id_kurikulum)) {
            $errors[] = 'ID Kurikulum wajib diisi';
        }

        if (empty($this->semester_berlaku)) {
            $errors[] = 'Semester berlaku wajib diisi';
        } elseif (!in_array($this->semester_berlaku, ['Ganjil', 'Genap'])) {
            $errors[] = 'Semester berlaku harus "Ganjil" atau "Genap"';
        }

        if (empty($this->tahun_ajaran)) {
            $errors[] = 'Tahun ajaran wajib diisi';
        } elseif (!preg_match('/^\d{4}\/\d{4}$/', $this->tahun_ajaran)) {
            $errors[] = 'Format tahun ajaran harus YYYY/YYYY (contoh: 2024/2025)';
        }

        if (!empty($this->status) && !in_array($this->status, ['draft', 'submitted', 'revised', 'approved', 'active', 'archived'])) {
            $errors[] = 'Status harus salah satu dari: draft, submitted, revised, approved, active, archived';
        }

        return $errors;
    }
}
