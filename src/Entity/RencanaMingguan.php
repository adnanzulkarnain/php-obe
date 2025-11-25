<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * RencanaMingguan (Weekly Planning) Entity
 * Represents weekly lecture plan from RPS
 */
class RencanaMingguan
{
    public ?int $id_minggu = null;
    public int $id_rps;
    public int $minggu_ke; // Week number (1-16)
    public ?int $id_subcpmk = null;

    // JSONB fields (stored as JSON in database)
    public mixed $materi = null; // Material to be taught
    public mixed $metode = null; // Teaching methods
    public mixed $aktivitas = null; // Learning activities

    // Media and resources
    public ?string $media_software = null;
    public ?string $media_hardware = null;
    public ?string $pengalaman_belajar = null;
    public int $estimasi_waktu_menit = 150; // Default: 150 minutes

    // Metadata
    public ?string $created_at = null;
    public ?string $updated_at = null;

    // Additional info (joined from other tables)
    public ?string $kode_mk = null;
    public ?string $nama_mk = null;
    public ?string $nama_subcpmk = null;

    public static function fromArray(array $data): self
    {
        $instance = new self();
        foreach ($data as $key => $value) {
            if (property_exists($instance, $key)) {
                // Convert string values to proper types
                if (in_array($key, ['id_minggu', 'id_rps', 'minggu_ke', 'id_subcpmk', 'estimasi_waktu_menit']) && $value !== null) {
                    $instance->$key = (int)$value;
                } elseif (in_array($key, ['materi', 'metode', 'aktivitas']) && is_string($value)) {
                    // Decode JSON fields
                    $instance->$key = json_decode($value, true);
                } else {
                    $instance->$key = $value;
                }
            }
        }
        return $instance;
    }

    public function toArray(): array
    {
        $data = get_object_vars($this);

        // Encode JSON fields
        if ($data['materi'] !== null && !is_string($data['materi'])) {
            $data['materi'] = json_encode($data['materi']);
        }
        if ($data['metode'] !== null && !is_string($data['metode'])) {
            $data['metode'] = json_encode($data['metode']);
        }
        if ($data['aktivitas'] !== null && !is_string($data['aktivitas'])) {
            $data['aktivitas'] = json_encode($data['aktivitas']);
        }

        return $data;
    }

    public function validate(): array
    {
        $errors = [];

        if (empty($this->id_rps)) {
            $errors[] = 'ID RPS wajib diisi';
        }

        if (empty($this->minggu_ke)) {
            $errors[] = 'Minggu ke wajib diisi';
        } elseif ($this->minggu_ke < 1 || $this->minggu_ke > 16) {
            $errors[] = 'Minggu ke harus antara 1-16';
        }

        if ($this->estimasi_waktu_menit !== null && $this->estimasi_waktu_menit < 0) {
            $errors[] = 'Estimasi waktu tidak boleh negatif';
        }

        return $errors;
    }
}
