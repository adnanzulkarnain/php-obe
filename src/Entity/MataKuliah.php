<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * Mata Kuliah Entity
 */
class MataKuliah
{
    public string $kode_mk;
    public int $id_kurikulum;
    public string $nama_mk;
    public ?string $nama_mk_eng = null;
    public int $sks;
    public int $semester;
    public ?string $rumpun = null;
    public string $jenis_mk; // wajib, pilihan, MKWU
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
