<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * Template Penilaian Entity
 * Represents grading template per RPS linking CPMK to assessment types
 */
class TemplatePenilaian
{
    public ?int $id_template = null;
    public int $id_rps;
    public int $id_cpmk;
    public int $id_jenis;
    public float $bobot;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    // Additional info
    public ?string $kode_cpmk = null;
    public ?string $nama_jenis = null;

    public static function fromArray(array $data): self
    {
        $instance = new self();
        foreach ($data as $key => $value) {
            if (property_exists($instance, $key)) {
                if (in_array($key, ['id_template', 'id_rps', 'id_cpmk', 'id_jenis']) && $value !== null) {
                    $instance->$key = (int)$value;
                } elseif ($key === 'bobot') {
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

        if (empty($this->id_rps)) {
            $errors[] = 'ID RPS wajib diisi';
        }

        if (empty($this->id_cpmk)) {
            $errors[] = 'ID CPMK wajib diisi';
        }

        if (empty($this->id_jenis)) {
            $errors[] = 'ID Jenis Penilaian wajib diisi';
        }

        if ($this->bobot < 0 || $this->bobot > 100) {
            $errors[] = 'Bobot harus antara 0-100';
        }

        return $errors;
    }
}
