<?php

namespace App\Service;

use App\Exception\ValidationException;
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\ValidationException as RespectValidationException;

/**
 * Validation Service
 * Centralized validation using Respect\Validation
 */
class ValidationService
{
    private array $errors = [];

    /**
     * Validate data against rules
     *
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @throws ValidationException
     */
    public function validate(array $data, array $rules): void
    {
        $this->errors = [];

        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;

            try {
                $rule->assert($value);
            } catch (RespectValidationException $e) {
                $this->errors[$field] = $e->getMessage();
            }
        }

        if (!empty($this->errors)) {
            throw new ValidationException('Validation failed', $this->errors);
        }
    }

    /**
     * Check validation (returns bool instead of throwing)
     */
    public function check(array $data, array $rules): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;

            try {
                $rule->assert($value);
            } catch (RespectValidationException $e) {
                $this->errors[$field] = $e->getMessage();
            }
        }

        return empty($this->errors);
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Validate login credentials
     */
    public static function validateLogin(array $data): void
    {
        $validator = new self();

        $rules = [
            'username' => v::notEmpty()->alnum()->length(3, 50),
            'password' => v::notEmpty()->length(6, null)
        ];

        $validator->validate($data, $rules);
    }

    /**
     * Validate kurikulum creation
     */
    public static function validateKurikulum(array $data): void
    {
        $validator = new self();

        $rules = [
            'id_prodi' => v::notEmpty()->alpha()->length(2, 10),
            'kode_kurikulum' => v::notEmpty()->alnum()->length(1, 20),
            'nama_kurikulum' => v::notEmpty()->length(1, 200),
            'tahun_berlaku' => v::notEmpty()->intVal()->between(2000, 2100)
        ];

        $validator->validate($data, $rules);
    }

    /**
     * Validate CPL creation
     */
    public static function validateCPL(array $data): void
    {
        $validator = new self();

        $rules = [
            'id_kurikulum' => v::notEmpty()->intVal(),
            'kode_cpl' => v::notEmpty()->alnum()->length(1, 20),
            'deskripsi' => v::notEmpty()->length(1, 1000),
            'kategori' => v::optional(v::in(['sikap', 'pengetahuan', 'keterampilan_umum', 'keterampilan_khusus']))
        ];

        $validator->validate($data, $rules);
    }

    /**
     * Validate CPMK creation
     */
    public static function validateCPMK(array $data): void
    {
        $validator = new self();

        $rules = [
            'kode_mk' => v::notEmpty()->alnum()->length(1, 20),
            'id_kurikulum' => v::notEmpty()->intVal(),
            'kode_cpmk' => v::notEmpty()->alnum()->length(1, 20),
            'deskripsi' => v::notEmpty()->length(1, 1000)
        ];

        $validator->validate($data, $rules);
    }

    /**
     * Validate mata kuliah creation
     */
    public static function validateMataKuliah(array $data): void
    {
        $validator = new self();

        $rules = [
            'kode_mk' => v::notEmpty()->alnum()->length(1, 20),
            'id_kurikulum' => v::notEmpty()->intVal(),
            'nama_mk' => v::notEmpty()->length(1, 200),
            'sks' => v::notEmpty()->intVal()->between(1, 6),
            'semester' => v::notEmpty()->intVal()->between(1, 14),
            'jenis_mk' => v::optional(v::in(['wajib', 'pilihan']))
        ];

        $validator->validate($data, $rules);
    }

    /**
     * Validate mahasiswa creation
     */
    public static function validateMahasiswa(array $data): void
    {
        $validator = new self();

        $rules = [
            'nim' => v::notEmpty()->digit()->length(8, 20),
            'nama' => v::notEmpty()->length(1, 200),
            'email' => v::optional(v::email()),
            'id_prodi' => v::notEmpty()->alpha()->length(2, 10),
            'id_kurikulum' => v::notEmpty()->intVal(),
            'angkatan' => v::notEmpty()->intVal()->between(2000, 2100)
        ];

        $validator->validate($data, $rules);
    }

    /**
     * Validate dosen creation
     */
    public static function validateDosen(array $data): void
    {
        $validator = new self();

        $rules = [
            'nip' => v::notEmpty()->alnum()->length(5, 50),
            'nama' => v::notEmpty()->length(1, 200),
            'email' => v::optional(v::email()),
            'id_prodi' => v::notEmpty()->alpha()->length(2, 10)
        ];

        $validator->validate($data, $rules);
    }

    /**
     * Validate kelas creation
     */
    public static function validateKelas(array $data): void
    {
        $validator = new self();

        $rules = [
            'kode_mk' => v::notEmpty()->alnum()->length(1, 20),
            'id_kurikulum' => v::notEmpty()->intVal(),
            'nama_kelas' => v::notEmpty()->length(1, 50),
            'semester_berjalan' => v::notEmpty()->alnum()->length(5, 10),
            'kapasitas' => v::optional(v::intVal()->between(1, 200))
        ];

        $validator->validate($data, $rules);
    }

    /**
     * Validate email address
     */
    public static function validateEmail(string $email): bool
    {
        try {
            v::email()->assert($email);
            return true;
        } catch (RespectValidationException $e) {
            return false;
        }
    }

    /**
     * Validate NIM format
     */
    public static function validateNIM(string $nim): bool
    {
        try {
            v::digit()->length(8, 20)->assert($nim);
            return true;
        } catch (RespectValidationException $e) {
            return false;
        }
    }

    /**
     * Validate required fields
     */
    public static function validateRequired(array $data, array $requiredFields): void
    {
        $validator = new self();
        $rules = [];

        foreach ($requiredFields as $field) {
            $rules[$field] = v::notEmpty();
        }

        $validator->validate($data, $rules);
    }

    /**
     * Sanitize input string
     */
    public static function sanitize(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize array of strings
     */
    public static function sanitizeArray(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = self::sanitize($value);
            } elseif (is_array($value)) {
                $sanitized[$key] = self::sanitizeArray($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Check if string is valid JSON
     */
    public static function isValidJSON(string $string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Validate file upload
     */
    public static function validateFileUpload(array $file, array $allowedTypes, int $maxSize): array
    {
        $errors = [];

        if (empty($file['name'])) {
            $errors[] = 'No file uploaded';
            return $errors;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload error';
            return $errors;
        }

        // Check file size
        if ($file['size'] > $maxSize) {
            $maxSizeMB = $maxSize / 1048576;
            $errors[] = "File size exceeds maximum of {$maxSizeMB}MB";
        }

        // Check file type
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedTypes)) {
            $errors[] = "File type not allowed. Allowed: " . implode(', ', $allowedTypes);
        }

        return $errors;
    }

    /**
     * Validate date format
     */
    public static function isValidDate(string $date, string $format = 'Y-m-d'): bool
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * Validate integer range
     */
    public static function isIntInRange(int $value, int $min, int $max): bool
    {
        return $value >= $min && $value <= $max;
    }
}
