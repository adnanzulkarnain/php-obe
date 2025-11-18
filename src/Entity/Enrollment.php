<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * Enrollment (KRS) Entity
 * Represents student enrollment in a class
 */
class Enrollment
{
    public ?int $id_enrollment = null;
    public string $nim;
    public int $id_kelas;
    public ?string $tanggal_daftar = null;
    public string $status = 'aktif'; // aktif, mengulang, drop, lulus
    public ?float $nilai_akhir = null;
    public ?string $nilai_huruf = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    // Additional info (joined from other tables)
    public ?string $nama_mahasiswa = null;
    public ?string $kode_mk = null;
    public ?string $nama_mk = null;
    public ?string $nama_kelas = null;
    public ?int $sks = null;
    public ?string $semester = null;
    public ?string $tahun_ajaran = null;
    public ?int $id_kurikulum = null;

    public static function fromArray(array $data): self
    {
        $instance = new self();
        foreach ($data as $key => $value) {
            if (property_exists($instance, $key)) {
                // Convert string values to proper types
                if ($key === 'id_enrollment' && $value !== null) {
                    $instance->$key = (int)$value;
                } elseif ($key === 'id_kelas') {
                    $instance->$key = (int)$value;
                } elseif (in_array($key, ['id_kurikulum', 'sks']) && $value !== null) {
                    $instance->$key = (int)$value;
                } elseif ($key === 'nilai_akhir' && $value !== null) {
                    $instance->$key = (float)$value;
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

        if (empty($this->nim)) {
            $errors[] = 'NIM mahasiswa wajib diisi';
        }

        if (empty($this->id_kelas)) {
            $errors[] = 'ID Kelas wajib diisi';
        }

        if (!empty($this->status) && !in_array($this->status, ['aktif', 'mengulang', 'drop', 'lulus'])) {
            $errors[] = 'Status harus salah satu dari: aktif, mengulang, drop, lulus';
        }

        // Validate nilai_akhir range
        if ($this->nilai_akhir !== null) {
            if ($this->nilai_akhir < 0 || $this->nilai_akhir > 100) {
                $errors[] = 'Nilai akhir harus berada dalam rentang 0-100';
            }
        }

        // Validate nilai_huruf
        if ($this->nilai_huruf !== null) {
            $validGrades = ['A', 'A-', 'AB', 'B+', 'B', 'B-', 'BC', 'C+', 'C', 'C-', 'D', 'E'];
            if (!in_array($this->nilai_huruf, $validGrades)) {
                $errors[] = 'Nilai huruf tidak valid';
            }
        }

        // Business rule: If status is 'lulus', nilai must be set
        if ($this->status === 'lulus' && $this->nilai_akhir === null) {
            $errors[] = 'Nilai akhir harus diisi untuk status lulus';
        }

        return $errors;
    }
}
