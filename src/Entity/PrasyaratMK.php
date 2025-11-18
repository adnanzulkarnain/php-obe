<?php

namespace App\Entity;

use InvalidArgumentException;

/**
 * PrasyaratMK Entity
 * Represents course prerequisites
 *
 * Defines which courses must be completed before enrolling in a course
 */
class PrasyaratMK
{
    private ?int $idPrasyarat;
    private string $kodeMk;
    private int $idKurikulum;
    private string $kodeMkPrasyarat;
    private string $jenisPrasyarat;
    private ?string $createdAt;

    // Valid jenis prasyarat values
    private const VALID_JENIS = ['wajib', 'alternatif'];

    public function __construct(array $data)
    {
        $this->validateAndSetData($data);
    }

    private function validateAndSetData(array $data): void
    {
        // Required fields
        if (empty($data['kode_mk'])) {
            throw new InvalidArgumentException('Kode MK is required');
        }
        if (empty($data['id_kurikulum'])) {
            throw new InvalidArgumentException('ID Kurikulum is required');
        }
        if (empty($data['kode_mk_prasyarat'])) {
            throw new InvalidArgumentException('Kode MK Prasyarat is required');
        }

        // Business rule: Cannot have self as prerequisite
        if ($data['kode_mk'] === $data['kode_mk_prasyarat']) {
            throw new InvalidArgumentException('A course cannot be its own prerequisite');
        }

        // Validate jenis prasyarat
        $jenis = $data['jenis_prasyarat'] ?? 'wajib';
        if (!in_array($jenis, self::VALID_JENIS)) {
            throw new InvalidArgumentException(
                'Invalid jenis prasyarat. Must be one of: ' . implode(', ', self::VALID_JENIS)
            );
        }

        // Validate id_kurikulum is integer
        if (!is_numeric($data['id_kurikulum'])) {
            throw new InvalidArgumentException('ID Kurikulum must be a valid integer');
        }

        // Set properties
        $this->idPrasyarat = isset($data['id_prasyarat']) ? (int)$data['id_prasyarat'] : null;
        $this->kodeMk = $data['kode_mk'];
        $this->idKurikulum = (int)$data['id_kurikulum'];
        $this->kodeMkPrasyarat = $data['kode_mk_prasyarat'];
        $this->jenisPrasyarat = $jenis;
        $this->createdAt = $data['created_at'] ?? null;
    }

    // Getters
    public function getIdPrasyarat(): ?int
    {
        return $this->idPrasyarat;
    }

    public function getKodeMk(): string
    {
        return $this->kodeMk;
    }

    public function getIdKurikulum(): int
    {
        return $this->idKurikulum;
    }

    public function getKodeMkPrasyarat(): string
    {
        return $this->kodeMkPrasyarat;
    }

    public function getJenisPrasyarat(): string
    {
        return $this->jenisPrasyarat;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    // Helper methods
    public function isWajib(): bool
    {
        return $this->jenisPrasyarat === 'wajib';
    }

    public function isAlternatif(): bool
    {
        return $this->jenisPrasyarat === 'alternatif';
    }

    // Convert to array
    public function toArray(): array
    {
        $data = [
            'kode_mk' => $this->kodeMk,
            'id_kurikulum' => $this->idKurikulum,
            'kode_mk_prasyarat' => $this->kodeMkPrasyarat,
            'jenis_prasyarat' => $this->jenisPrasyarat,
            'created_at' => $this->createdAt,
        ];

        if ($this->idPrasyarat !== null) {
            $data['id_prasyarat'] = $this->idPrasyarat;
        }

        return $data;
    }

    // Static method to get valid jenis values
    public static function getValidJenis(): array
    {
        return self::VALID_JENIS;
    }
}
