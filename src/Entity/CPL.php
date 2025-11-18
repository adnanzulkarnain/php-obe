<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * CPL (Capaian Pembelajaran Lulusan) Entity
 */
class CPL
{
    public int $id_cpl;
    public int $id_kurikulum;
    public string $kode_cpl;
    public string $deskripsi;
    public string $kategori; // sikap, pengetahuan, keterampilan_umum, keterampilan_khusus
    public ?int $urutan = null;
    public bool $is_active = true;
    public string $created_at;
    public string $updated_at;

    public static function fromArray(array $data): self
    {
        $instance = new self();
        foreach ($data as $key => $value) {
            if (property_exists($instance, $key)) {
                $instance->$key = $value;
            }
        }
        return $instance;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
