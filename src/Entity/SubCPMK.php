<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * SubCPMK Entity
 * Represents sub-learning outcomes for a CPMK
 */
class SubCPMK
{
    public ?int $id_subcpmk = null;
    public int $id_cpmk;
    public string $kode_subcpmk;
    public string $deskripsi;
    public ?string $indikator = null;
    public ?int $urutan = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    // Additional info
    public ?string $kode_cpmk = null;

    public static function fromArray(array $data): self
    {
        $instance = new self();
        foreach ($data as $key => $value) {
            if (property_exists($instance, $key)) {
                // Convert string values to proper types
                if (in_array($key, ['id_subcpmk', 'id_cpmk', 'urutan']) && $value !== null) {
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

        if (empty($this->id_cpmk)) {
            $errors[] = 'ID CPMK wajib diisi';
        }

        if (empty($this->kode_subcpmk)) {
            $errors[] = 'Kode SubCPMK wajib diisi';
        }

        if (empty($this->deskripsi)) {
            $errors[] = 'Deskripsi SubCPMK wajib diisi';
        }

        if ($this->urutan !== null && $this->urutan < 1) {
            $errors[] = 'Urutan harus >= 1';
        }

        return $errors;
    }
}
