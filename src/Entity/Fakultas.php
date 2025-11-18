<?php

namespace App\Entity;

use InvalidArgumentException;

/**
 * Fakultas Entity
 * Represents a faculty/school within the university
 */
class Fakultas
{
    private string $idFakultas;
    private string $nama;
    private ?string $createdAt;
    private ?string $updatedAt;

    public function __construct(array $data)
    {
        $this->validateAndSetData($data);
    }

    private function validateAndSetData(array $data): void
    {
        // Required fields
        if (empty($data['id_fakultas'])) {
            throw new InvalidArgumentException('ID Fakultas is required');
        }
        if (empty($data['nama'])) {
            throw new InvalidArgumentException('Nama is required');
        }

        // Validate ID format (alphanumeric, 3-20 characters)
        if (!preg_match('/^[A-Za-z0-9_-]{3,20}$/', $data['id_fakultas'])) {
            throw new InvalidArgumentException('ID Fakultas must be 3-20 alphanumeric characters');
        }

        // Validate nama length
        if (strlen($data['nama']) < 3 || strlen($data['nama']) > 100) {
            throw new InvalidArgumentException('Nama must be between 3 and 100 characters');
        }

        // Set properties
        $this->idFakultas = $data['id_fakultas'];
        $this->nama = $data['nama'];
        $this->createdAt = $data['created_at'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? null;
    }

    // Getters
    public function getIdFakultas(): string
    {
        return $this->idFakultas;
    }

    public function getNama(): string
    {
        return $this->nama;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    // Convert to array
    public function toArray(): array
    {
        return [
            'id_fakultas' => $this->idFakultas,
            'nama' => $this->nama,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
