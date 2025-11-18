<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * Tugas Mengajar (Teaching Assignment) Entity
 * Represents teaching assignment for a lecturer in a class
 */
class TugasMengajar
{
    public ?int $id_tugas = null;
    public int $id_kelas;
    public string $id_dosen;
    public string $peran; // koordinator, pengampu, asisten
    public ?string $created_at = null;

    // Additional info (joined from dosen)
    public ?string $nama_dosen = null;
    public ?string $email_dosen = null;

    public static function fromArray(array $data): self
    {
        $instance = new self();
        foreach ($data as $key => $value) {
            if (property_exists($instance, $key)) {
                // Convert string values to proper types
                if ($key === 'id_tugas' && $value !== null) {
                    $instance->$key = (int)$value;
                } elseif ($key === 'id_kelas') {
                    $instance->$key = (int)$value;
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

        if (empty($this->id_dosen)) {
            $errors[] = 'ID Dosen wajib diisi';
        }

        if (empty($this->peran)) {
            $errors[] = 'Peran dosen wajib diisi';
        } elseif (!in_array($this->peran, ['koordinator', 'pengampu', 'asisten'])) {
            $errors[] = 'Peran harus salah satu dari: koordinator, pengampu, asisten';
        }

        return $errors;
    }
}
