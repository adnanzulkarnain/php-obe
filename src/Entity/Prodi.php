<?php

namespace App\Entity;

use InvalidArgumentException;

/**
 * Prodi Entity
 * Represents a study program (Program Studi)
 */
class Prodi
{
    private string $idProdi;
    private string $idFakultas;
    private string $nama;
    private string $jenjang;
    private ?string $akreditasi;
    private ?int $tahunBerdiri;
    private ?string $createdAt;
    private ?string $updatedAt;

    // Valid jenjang values
    private const VALID_JENJANG = ['D3', 'D4', 'S1', 'S2', 'S3'];

    // Valid akreditasi values
    private const VALID_AKREDITASI = ['A', 'B', 'C', 'Unggul', 'Baik Sekali', 'Baik'];

    public function __construct(array $data)
    {
        $this->validateAndSetData($data);
    }

    private function validateAndSetData(array $data): void
    {
        // Required fields
        if (empty($data['id_prodi'])) {
            throw new InvalidArgumentException('ID Prodi is required');
        }
        if (empty($data['id_fakultas'])) {
            throw new InvalidArgumentException('ID Fakultas is required');
        }
        if (empty($data['nama'])) {
            throw new InvalidArgumentException('Nama is required');
        }
        if (empty($data['jenjang'])) {
            throw new InvalidArgumentException('Jenjang is required');
        }

        // Validate ID format (alphanumeric, 3-20 characters)
        if (!preg_match('/^[A-Za-z0-9_-]{3,20}$/', $data['id_prodi'])) {
            throw new InvalidArgumentException('ID Prodi must be 3-20 alphanumeric characters');
        }

        // Validate nama length
        if (strlen($data['nama']) < 3 || strlen($data['nama']) > 100) {
            throw new InvalidArgumentException('Nama must be between 3 and 100 characters');
        }

        // Validate jenjang
        if (!in_array($data['jenjang'], self::VALID_JENJANG)) {
            throw new InvalidArgumentException(
                'Invalid jenjang. Must be one of: ' . implode(', ', self::VALID_JENJANG)
            );
        }

        // Validate akreditasi if provided
        if (!empty($data['akreditasi'])) {
            if (!in_array($data['akreditasi'], self::VALID_AKREDITASI)) {
                throw new InvalidArgumentException(
                    'Invalid akreditasi. Must be one of: ' . implode(', ', self::VALID_AKREDITASI)
                );
            }
        }

        // Validate tahun_berdiri if provided
        if (!empty($data['tahun_berdiri'])) {
            $tahun = (int)$data['tahun_berdiri'];
            $currentYear = (int)date('Y');
            if ($tahun < 1900 || $tahun > $currentYear) {
                throw new InvalidArgumentException(
                    "Tahun berdiri must be between 1900 and {$currentYear}"
                );
            }
        }

        // Set properties
        $this->idProdi = $data['id_prodi'];
        $this->idFakultas = $data['id_fakultas'];
        $this->nama = $data['nama'];
        $this->jenjang = $data['jenjang'];
        $this->akreditasi = $data['akreditasi'] ?? null;
        $this->tahunBerdiri = !empty($data['tahun_berdiri']) ? (int)$data['tahun_berdiri'] : null;
        $this->createdAt = $data['created_at'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? null;
    }

    // Getters
    public function getIdProdi(): string
    {
        return $this->idProdi;
    }

    public function getIdFakultas(): string
    {
        return $this->idFakultas;
    }

    public function getNama(): string
    {
        return $this->nama;
    }

    public function getJenjang(): string
    {
        return $this->jenjang;
    }

    public function getAkreditasi(): ?string
    {
        return $this->akreditasi;
    }

    public function getTahunBerdiri(): ?int
    {
        return $this->tahunBerdiri;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    // Helper methods
    public function isDiploma(): bool
    {
        return in_array($this->jenjang, ['D3', 'D4']);
    }

    public function isSarjana(): bool
    {
        return $this->jenjang === 'S1';
    }

    public function isMagister(): bool
    {
        return $this->jenjang === 'S2';
    }

    public function isDoktor(): bool
    {
        return $this->jenjang === 'S3';
    }

    public function hasAkreditasiA(): bool
    {
        return in_array($this->akreditasi, ['A', 'Unggul']);
    }

    // Convert to array
    public function toArray(): array
    {
        return [
            'id_prodi' => $this->idProdi,
            'id_fakultas' => $this->idFakultas,
            'nama' => $this->nama,
            'jenjang' => $this->jenjang,
            'akreditasi' => $this->akreditasi,
            'tahun_berdiri' => $this->tahunBerdiri,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    // Static method to get valid values
    public static function getValidJenjang(): array
    {
        return self::VALID_JENJANG;
    }

    public static function getValidAkreditasi(): array
    {
        return self::VALID_AKREDITASI;
    }
}
