<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * CPMK (Capaian Pembelajaran Mata Kuliah) Entity
 * Represents course learning outcomes
 */
class CPMK
{
    public ?int $id_cpmk = null;
    public int $id_rps;
    public string $kode_cpmk;
    public string $deskripsi;
    public ?int $urutan = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    // Additional info (joined from other tables)
    public ?string $kode_mk = null;
    public ?string $nama_mk = null;

    public static function fromArray(array $data): self
    {
        $instance = new self();
        foreach ($data as $key => $value) {
            if (property_exists($instance, $key)) {
                // Convert string values to proper types
                if (in_array($key, ['id_cpmk', 'id_rps', 'urutan']) && $value !== null) {
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

        if (empty($this->id_rps)) {
            $errors[] = 'ID RPS wajib diisi';
        }

        if (empty($this->kode_cpmk)) {
            $errors[] = 'Kode CPMK wajib diisi';
        }

        if (empty($this->deskripsi)) {
            $errors[] = 'Deskripsi CPMK wajib diisi';
        }

        if ($this->urutan !== null && $this->urutan < 1) {
            $errors[] = 'Urutan harus >= 1';
        }

        return $errors;
    }
}
