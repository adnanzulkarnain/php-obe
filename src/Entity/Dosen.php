<?php

namespace App\Entity;

use InvalidArgumentException;

/**
 * Dosen Entity
 * Represents a lecturer/faculty member profile
 */
class Dosen
{
    private string $idDosen;
    private ?string $nidn;
    private string $nama;
    private string $email;
    private ?string $phone;
    private ?string $idProdi;
    private string $status;
    private ?string $createdAt;
    private ?string $updatedAt;

    // Valid status values
    private const VALID_STATUS = ['aktif', 'cuti', 'pensiun'];

    public function __construct(array $data)
    {
        $this->validateAndSetData($data);
    }

    private function validateAndSetData(array $data): void
    {
        // Required fields
        if (empty($data['id_dosen'])) {
            throw new InvalidArgumentException('ID Dosen is required');
        }
        if (empty($data['nama'])) {
            throw new InvalidArgumentException('Nama is required');
        }
        if (empty($data['email'])) {
            throw new InvalidArgumentException('Email is required');
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format');
        }

        // Validate NIDN format if provided (should be 10 digits)
        if (!empty($data['nidn'])) {
            if (!preg_match('/^\d{10}$/', $data['nidn'])) {
                throw new InvalidArgumentException('NIDN must be 10 digits');
            }
        }

        // Validate phone if provided
        if (!empty($data['phone'])) {
            if (!preg_match('/^[\d\+\-\(\)\s]{8,20}$/', $data['phone'])) {
                throw new InvalidArgumentException('Invalid phone format');
            }
        }

        // Validate status
        $status = $data['status'] ?? 'aktif';
        if (!in_array($status, self::VALID_STATUS)) {
            throw new InvalidArgumentException(
                'Invalid status. Must be one of: ' . implode(', ', self::VALID_STATUS)
            );
        }

        // Set properties
        $this->idDosen = $data['id_dosen'];
        $this->nidn = $data['nidn'] ?? null;
        $this->nama = $data['nama'];
        $this->email = $data['email'];
        $this->phone = $data['phone'] ?? null;
        $this->idProdi = $data['id_prodi'] ?? null;
        $this->status = $status;
        $this->createdAt = $data['created_at'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? null;
    }

    // Getters
    public function getIdDosen(): string
    {
        return $this->idDosen;
    }

    public function getNidn(): ?string
    {
        return $this->nidn;
    }

    public function getNama(): string
    {
        return $this->nama;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getIdProdi(): ?string
    {
        return $this->idProdi;
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

    public function isPensiun(): bool
    {
        return $this->status === 'pensiun';
    }

    // Convert to array
    public function toArray(): array
    {
        return [
            'id_dosen' => $this->idDosen,
            'nidn' => $this->nidn,
            'nama' => $this->nama,
            'email' => $this->email,
            'phone' => $this->phone,
            'id_prodi' => $this->idProdi,
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
