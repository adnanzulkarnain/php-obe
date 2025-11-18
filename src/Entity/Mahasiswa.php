<?php

namespace App\Entity;

use InvalidArgumentException;

/**
 * Mahasiswa Entity
 * Represents a student profile
 *
 * IMPORTANT: id_kurikulum is IMMUTABLE - cannot be changed after creation
 */
class Mahasiswa
{
    private string $nim;
    private string $nama;
    private string $email;
    private ?string $idProdi;
    private int $idKurikulum; // IMMUTABLE
    private string $angkatan;
    private string $status;
    private ?string $createdAt;
    private ?string $updatedAt;

    // Valid status values
    private const VALID_STATUS = ['aktif', 'cuti', 'lulus', 'DO', 'keluar'];

    public function __construct(array $data)
    {
        $this->validateAndSetData($data);
    }

    private function validateAndSetData(array $data): void
    {
        // Required fields
        if (empty($data['nim'])) {
            throw new InvalidArgumentException('NIM is required');
        }
        if (empty($data['nama'])) {
            throw new InvalidArgumentException('Nama is required');
        }
        if (empty($data['email'])) {
            throw new InvalidArgumentException('Email is required');
        }
        if (empty($data['id_kurikulum'])) {
            throw new InvalidArgumentException('ID Kurikulum is required and immutable');
        }
        if (empty($data['angkatan'])) {
            throw new InvalidArgumentException('Angkatan is required');
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format');
        }

        // Validate NIM format (flexible, but must be alphanumeric)
        if (!preg_match('/^[A-Za-z0-9]{6,20}$/', $data['nim'])) {
            throw new InvalidArgumentException('NIM must be 6-20 alphanumeric characters');
        }

        // Validate angkatan format (YYYY)
        if (!preg_match('/^\d{4}$/', $data['angkatan'])) {
            throw new InvalidArgumentException('Angkatan must be 4-digit year (e.g., 2024)');
        }

        // Validate angkatan is reasonable (between 1900 and current year + 1)
        $angkatan = (int)$data['angkatan'];
        $currentYear = (int)date('Y');
        if ($angkatan < 1900 || $angkatan > $currentYear + 1) {
            throw new InvalidArgumentException("Angkatan must be between 1900 and {$currentYear}");
        }

        // Validate status
        $status = $data['status'] ?? 'aktif';
        if (!in_array($status, self::VALID_STATUS)) {
            throw new InvalidArgumentException(
                'Invalid status. Must be one of: ' . implode(', ', self::VALID_STATUS)
            );
        }

        // Validate id_kurikulum is integer
        if (!is_numeric($data['id_kurikulum'])) {
            throw new InvalidArgumentException('ID Kurikulum must be a valid integer');
        }

        // Set properties
        $this->nim = $data['nim'];
        $this->nama = $data['nama'];
        $this->email = $data['email'];
        $this->idProdi = $data['id_prodi'] ?? null;
        $this->idKurikulum = (int)$data['id_kurikulum'];
        $this->angkatan = $data['angkatan'];
        $this->status = $status;
        $this->createdAt = $data['created_at'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? null;
    }

    // Getters
    public function getNim(): string
    {
        return $this->nim;
    }

    public function getNama(): string
    {
        return $this->nama;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getIdProdi(): ?string
    {
        return $this->idProdi;
    }

    public function getIdKurikulum(): int
    {
        return $this->idKurikulum;
    }

    public function getAngkatan(): string
    {
        return $this->angkatan;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    // Status check helpers
    public function isAktif(): bool
    {
        return $this->status === 'aktif';
    }

    public function isCuti(): bool
    {
        return $this->status === 'cuti';
    }

    public function isLulus(): bool
    {
        return $this->status === 'lulus';
    }

    public function isDO(): bool
    {
        return $this->status === 'DO';
    }

    public function isKeluar(): bool
    {
        return $this->status === 'keluar';
    }

    // Academic status helpers
    public function isActiveStudent(): bool
    {
        return in_array($this->status, ['aktif', 'cuti']);
    }

    public function hasGraduated(): bool
    {
        return $this->status === 'lulus';
    }

    public function hasDropped(): bool
    {
        return in_array($this->status, ['DO', 'keluar']);
    }

    // Convert to array
    public function toArray(): array
    {
        return [
            'nim' => $this->nim,
            'nama' => $this->nama,
            'email' => $this->email,
            'id_prodi' => $this->idProdi,
            'id_kurikulum' => $this->idKurikulum,
            'angkatan' => $this->angkatan,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    // Static method to get valid status values
    public static function getValidStatus(): array
    {
        return self::VALID_STATUS;
    }
}
