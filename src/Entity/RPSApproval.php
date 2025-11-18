<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * RPS Approval Entity
 * Represents approval workflow for RPS
 */
class RPSApproval
{
    public ?int $id_approval = null;
    public int $id_rps;
    public ?string $approver = null;
    public int $approval_level; // 1=Ketua RPS, 2=Kaprodi, 3=Dekan
    public string $status = 'pending'; // pending, approved, rejected, revised
    public ?string $komentar = null;
    public ?string $approved_at = null;
    public ?string $created_at = null;

    // Additional info
    public ?string $nama_approver = null;
    public ?string $email_approver = null;

    public static function fromArray(array $data): self
    {
        $instance = new self();
        foreach ($data as $key => $value) {
            if (property_exists($instance, $key)) {
                // Convert string values to proper types
                if ($key === 'id_approval' && $value !== null) {
                    $instance->$key = (int)$value;
                } elseif (in_array($key, ['id_rps', 'approval_level'])) {
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

        if (empty($this->id_rps)) {
            $errors[] = 'ID RPS wajib diisi';
        }

        if (empty($this->approval_level) || !in_array($this->approval_level, [1, 2, 3])) {
            $errors[] = 'Approval level harus 1 (Ketua RPS), 2 (Kaprodi), atau 3 (Dekan)';
        }

        if (!empty($this->status) && !in_array($this->status, ['pending', 'approved', 'rejected', 'revised'])) {
            $errors[] = 'Status harus salah satu dari: pending, approved, rejected, revised';
        }

        return $errors;
    }

    public function getApprovalLevelName(): string
    {
        return match ($this->approval_level) {
            1 => 'Ketua RPS',
            2 => 'Kaprodi',
            3 => 'Dekan',
            default => 'Unknown'
        };
    }
}
