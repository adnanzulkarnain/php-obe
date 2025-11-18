<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * Kurikulum Entity
 */
class Kurikulum
{
    public int $id_kurikulum;
    public string $id_prodi;
    public string $kode_kurikulum;
    public string $nama_kurikulum;
    public int $tahun_berlaku;
    public ?int $tahun_berakhir = null;
    public string $status; // draft, review, approved, aktif, non-aktif, arsip
    public bool $is_primary = false;
    public ?string $deskripsi = null;
    public ?string $nomor_sk = null;
    public ?string $tanggal_sk = null;
    public ?string $dokumen_sk = null;
    public string $created_at;
    public string $updated_at;

    /**
     * Create from array
     */
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

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
