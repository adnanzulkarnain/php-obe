<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * Nilai Detail Entity
 * Represents student grade per assessment component
 */
class NilaiDetail
{
    public ?int $id_nilai_detail = null;
    public int $id_enrollment;
    public int $id_komponen;
    public float $nilai_mentah;
    public ?float $nilai_tertimbang = null;
    public ?string $catatan = null;
    public ?string $dinilai_oleh = null;
    public ?string $tanggal_input = null;
    public ?string $updated_at = null;

    // Additional info
    public ?string $nim = null;
    public ?string $nama_mahasiswa = null;
    public ?string $nama_komponen = null;
    public ?float $bobot_komponen = null;
    public ?float $nilai_maksimal = null;

    public static function fromArray(array $data): self
    {
        $instance = new self();
        foreach ($data as $key => $value) {
            if (property_exists($instance, $key)) {
                if (in_array($key, ['id_nilai_detail', 'id_enrollment', 'id_komponen']) && $value !== null) {
                    $instance->$key = (int)$value;
                } elseif (in_array($key, ['nilai_mentah', 'nilai_tertimbang', 'bobot_komponen', 'nilai_maksimal']) && $value !== null) {
                    $instance->$key = (float)$value;
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

        if (empty($this->id_enrollment)) {
            $errors[] = 'ID Enrollment wajib diisi';
        }

        if (empty($this->id_komponen)) {
            $errors[] = 'ID Komponen wajib diisi';
        }

        if ($this->nilai_mentah < 0) {
            $errors[] = 'Nilai mentah tidak boleh negatif';
        }

        return $errors;
    }
}
