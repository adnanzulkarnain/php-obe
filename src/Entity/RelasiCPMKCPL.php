<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * Relasi CPMK-CPL Entity
 * Represents mapping between CPMK and CPL with contribution weight
 */
class RelasiCPMKCPL
{
    public ?int $id_relasi = null;
    public int $id_cpmk;
    public int $id_cpl;
    public float $bobot_kontribusi = 100.00;
    public ?string $created_at = null;

    // Additional info
    public ?string $kode_cpmk = null;
    public ?string $kode_cpl = null;
    public ?string $deskripsi_cpl = null;

    public static function fromArray(array $data): self
    {
        $instance = new self();
        foreach ($data as $key => $value) {
            if (property_exists($instance, $key)) {
                // Convert string values to proper types
                if (in_array($key, ['id_relasi', 'id_cpmk', 'id_cpl']) && $value !== null) {
                    $instance->$key = (int)$value;
                } elseif ($key === 'bobot_kontribusi') {
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

        if (empty($this->id_cpmk)) {
            $errors[] = 'ID CPMK wajib diisi';
        }

        if (empty($this->id_cpl)) {
            $errors[] = 'ID CPL wajib diisi';
        }

        if ($this->bobot_kontribusi <= 0 || $this->bobot_kontribusi > 100) {
            $errors[] = 'Bobot kontribusi harus antara 0-100';
        }

        return $errors;
    }
}
