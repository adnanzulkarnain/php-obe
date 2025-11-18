<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * Kelas (Class) Entity
 * Represents a class offering for a mata kuliah (course)
 */
class Kelas
{
    public ?int $id_kelas = null;
    public string $kode_mk;
    public int $id_kurikulum;
    public ?int $id_rps = null;
    public string $nama_kelas; // A, B, C, etc
    public string $semester; // Ganjil, Genap
    public string $tahun_ajaran; // 2024/2025
    public int $kapasitas = 40;
    public int $kuota_terisi = 0;

    // Jadwal
    public ?string $hari = null;
    public ?string $jam_mulai = null;
    public ?string $jam_selesai = null;
    public ?string $ruangan = null;

    public string $status = 'draft'; // draft, open, closed, completed
    public ?string $created_at = null;
    public ?string $updated_at = null;

    // Additional info (joined from matakuliah)
    public ?string $nama_mk = null;
    public ?int $sks = null;

    public static function fromArray(array $data): self
    {
        $instance = new self();
        foreach ($data as $key => $value) {
            if (property_exists($instance, $key)) {
                // Convert string values to proper types
                if ($key === 'id_kelas' && $value !== null) {
                    $instance->$key = (int)$value;
                } elseif ($key === 'id_kurikulum') {
                    $instance->$key = (int)$value;
                } elseif ($key === 'id_rps' && $value !== null) {
                    $instance->$key = (int)$value;
                } elseif (in_array($key, ['kapasitas', 'kuota_terisi', 'sks']) && $value !== null) {
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

        if (empty($this->kode_mk)) {
            $errors[] = 'Kode MK wajib diisi';
        }

        if (empty($this->id_kurikulum)) {
            $errors[] = 'ID Kurikulum wajib diisi';
        }

        if (empty($this->nama_kelas)) {
            $errors[] = 'Nama kelas wajib diisi';
        }

        if (empty($this->semester)) {
            $errors[] = 'Semester wajib diisi';
        } elseif (!in_array($this->semester, ['Ganjil', 'Genap'])) {
            $errors[] = 'Semester harus "Ganjil" atau "Genap"';
        }

        if (empty($this->tahun_ajaran)) {
            $errors[] = 'Tahun ajaran wajib diisi';
        } elseif (!preg_match('/^\d{4}\/\d{4}$/', $this->tahun_ajaran)) {
            $errors[] = 'Format tahun ajaran harus YYYY/YYYY (contoh: 2024/2025)';
        }

        if ($this->kapasitas < 1) {
            $errors[] = 'Kapasitas harus minimal 1';
        }

        if ($this->kuota_terisi < 0) {
            $errors[] = 'Kuota terisi tidak boleh negatif';
        }

        if ($this->kuota_terisi > $this->kapasitas) {
            $errors[] = 'Kuota terisi tidak boleh melebihi kapasitas';
        }

        if (!empty($this->status) && !in_array($this->status, ['draft', 'open', 'closed', 'completed'])) {
            $errors[] = 'Status harus salah satu dari: draft, open, closed, completed';
        }

        // Validate schedule consistency
        $hasSchedule = !empty($this->hari) || !empty($this->jam_mulai) || !empty($this->jam_selesai) || !empty($this->ruangan);
        if ($hasSchedule) {
            if (empty($this->hari)) {
                $errors[] = 'Hari wajib diisi jika ada jadwal';
            }
            if (empty($this->jam_mulai)) {
                $errors[] = 'Jam mulai wajib diisi jika ada jadwal';
            }
            if (empty($this->jam_selesai)) {
                $errors[] = 'Jam selesai wajib diisi jika ada jadwal';
            }
        }

        return $errors;
    }
}
