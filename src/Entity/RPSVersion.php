<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * RPS Version Entity
 * Represents version control for RPS
 */
class RPSVersion
{
    public ?int $id_version = null;
    public int $id_rps;
    public int $version_number;
    public ?string $status = null;
    public ?array $snapshot_data = null; // Will be stored as JSONB
    public ?string $created_by = null;
    public ?string $approved_by = null;
    public ?string $created_at = null;
    public ?string $approved_at = null;
    public ?string $keterangan = null;
    public bool $is_active = false;

    // Additional info
    public ?string $nama_creator = null;
    public ?string $nama_approver = null;

    public static function fromArray(array $data): self
    {
        $instance = new self();
        foreach ($data as $key => $value) {
            if (property_exists($instance, $key)) {
                // Convert string values to proper types
                if ($key === 'id_version' && $value !== null) {
                    $instance->$key = (int)$value;
                } elseif (in_array($key, ['id_rps', 'version_number'])) {
                    $instance->$key = (int)$value;
                } elseif ($key === 'is_active') {
                    $instance->$key = (bool)$value;
                } elseif ($key === 'snapshot_data' && is_string($value)) {
                    // Decode JSON if it's a string
                    $instance->$key = json_decode($value, true);
                } else {
                    $instance->$key = $value;
                }
            }
        }
        return $instance;
    }

    public function toArray(): array
    {
        $data = get_object_vars($this);
        // Convert snapshot_data to JSON string for database storage
        if (is_array($data['snapshot_data'])) {
            $data['snapshot_data'] = json_encode($data['snapshot_data']);
        }
        return $data;
    }

    public function validate(): array
    {
        $errors = [];

        if (empty($this->id_rps)) {
            $errors[] = 'ID RPS wajib diisi';
        }

        if (empty($this->version_number) || $this->version_number < 1) {
            $errors[] = 'Version number harus >= 1';
        }

        return $errors;
    }
}
