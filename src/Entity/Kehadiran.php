<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * Kehadiran (Attendance) Entity
 * Represents student attendance record for a lecture
 */
class Kehadiran
{
    public ?int $id_kehadiran = null;
    public int $id_realisasi; // Reference to realisasi_pertemuan
    public string $nim; // Student ID
    public string $status = 'hadir'; // hadir, izin, sakit, alpha
    public ?string $keterangan = null;

    // Metadata
    public ?string $created_at = null;

    // Additional info (joined from other tables)
    public ?string $nama_mahasiswa = null;
    public ?string $tanggal_pelaksanaan = null;
    public ?string $nama_mk = null;

    public static function fromArray(array $data): self
    {
        $instance = new self();
        foreach ($data as $key => $value) {
            if (property_exists($instance, $key)) {
                // Convert string values to proper types
                if (in_array($key, ['id_kehadiran', 'id_realisasi']) && $value !== null) {
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

        if (empty($this->id_realisasi)) {
            $errors[] = 'ID Realisasi wajib diisi';
        }

        if (empty($this->nim)) {
            $errors[] = 'NIM wajib diisi';
        }

        if (empty($this->status)) {
            $errors[] = 'Status kehadiran wajib diisi';
        } elseif (!in_array($this->status, ['hadir', 'izin', 'sakit', 'alpha'])) {
            $errors[] = 'Status harus salah satu dari: hadir, izin, sakit, alpha';
        }

        return $errors;
    }

    /**
     * Get status label in Indonesian
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            'hadir' => 'Hadir',
            'izin' => 'Izin',
            'sakit' => 'Sakit',
            'alpha' => 'Alpha',
            default => 'Unknown'
        };
    }

    /**
     * Check if student is present
     */
    public function isPresent(): bool
    {
        return $this->status === 'hadir';
    }
}
