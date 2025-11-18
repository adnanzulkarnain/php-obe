<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * Komponen Penilaian Entity
 * Represents actual assessment component per class
 */
class KomponenPenilaian
{
    public ?int $id_komponen = null;
    public int $id_kelas;
    public ?int $id_template = null;
    public string $nama_komponen;
    public ?string $deskripsi = null;
    public ?string $tanggal_pelaksanaan = null;
    public ?string $deadline = null;
    public ?float $bobot_realisasi = null;
    public float $nilai_maksimal = 100.00;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    // Additional info
    public ?string $kode_mk = null;
    public ?string $nama_kelas = null;
    public ?string $nama_jenis = null;

    public static function fromArray(array $data): self
    {
        $instance = new self();
        foreach ($data as $key => $value) {
            if (property_exists($instance, $key)) {
                if (in_array($key, ['id_komponen', 'id_kelas', 'id_template']) && $value !== null) {
                    $instance->$key = (int)$value;
                } elseif (in_array($key, ['bobot_realisasi', 'nilai_maksimal'])) {
                    $instance->$key = $value !== null ? (float)$value : null;
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

        if (empty($this->nama_komponen)) {
            $errors[] = 'Nama komponen wajib diisi';
        }

        if ($this->bobot_realisasi !== null && ($this->bobot_realisasi < 0 || $this->bobot_realisasi > 100)) {
            $errors[] = 'Bobot realisasi harus antara 0-100';
        }

        if ($this->nilai_maksimal <= 0) {
            $errors[] = 'Nilai maksimal harus > 0';
        }

        return $errors;
    }
}
