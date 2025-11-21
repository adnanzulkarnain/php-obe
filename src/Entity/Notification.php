<?php

namespace App\Entity;

/**
 * Notification Entity
 * Represents a notification in the system
 */
class Notification
{
    private ?int $id_notification = null;
    private int $id_user;
    private string $tipe_notifikasi;
    private string $judul;
    private string $pesan;
    private ?string $link = null;
    private bool $is_read = false;
    private bool $is_sent_email = false;
    private ?string $created_at = null;
    private ?string $read_at = null;

    // Notification types
    public const TYPE_RPS_APPROVAL = 'rps_approval';
    public const TYPE_RPS_REJECTION = 'rps_rejection';
    public const TYPE_NILAI_DEADLINE = 'nilai_deadline';
    public const TYPE_ANNOUNCEMENT = 'announcement';
    public const TYPE_KURIKULUM_APPROVAL = 'kurikulum_approval';
    public const TYPE_KELAS_ASSIGNMENT = 'kelas_assignment';
    public const TYPE_SYSTEM = 'system';

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->hydrate($data);
        }
    }

    public function hydrate(array $data): void
    {
        foreach ($data as $key => $value) {
            $method = 'set' . str_replace('_', '', ucwords($key, '_'));
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
    }

    public function toArray(): array
    {
        return [
            'id_notification' => $this->id_notification,
            'id_user' => $this->id_user,
            'tipe_notifikasi' => $this->tipe_notifikasi,
            'judul' => $this->judul,
            'pesan' => $this->pesan,
            'link' => $this->link,
            'is_read' => $this->is_read,
            'is_sent_email' => $this->is_sent_email,
            'created_at' => $this->created_at,
            'read_at' => $this->read_at
        ];
    }

    // Getters and Setters

    public function getIdNotification(): ?int
    {
        return $this->id_notification;
    }

    public function setIdNotification(int $id_notification): void
    {
        $this->id_notification = $id_notification;
    }

    public function getIdUser(): int
    {
        return $this->id_user;
    }

    public function setIdUser(int $id_user): void
    {
        $this->id_user = $id_user;
    }

    public function getTipeNotifikasi(): string
    {
        return $this->tipe_notifikasi;
    }

    public function setTipeNotifikasi(string $tipe_notifikasi): void
    {
        $this->tipe_notifikasi = $tipe_notifikasi;
    }

    public function getJudul(): string
    {
        return $this->judul;
    }

    public function setJudul(string $judul): void
    {
        $this->judul = $judul;
    }

    public function getPesan(): string
    {
        return $this->pesan;
    }

    public function setPesan(string $pesan): void
    {
        $this->pesan = $pesan;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(?string $link): void
    {
        $this->link = $link;
    }

    public function getIsRead(): bool
    {
        return $this->is_read;
    }

    public function setIsRead(bool $is_read): void
    {
        $this->is_read = $is_read;
    }

    public function getIsSentEmail(): bool
    {
        return $this->is_sent_email;
    }

    public function setIsSentEmail(bool $is_sent_email): void
    {
        $this->is_sent_email = $is_sent_email;
    }

    public function getCreatedAt(): ?string
    {
        return $this->created_at;
    }

    public function setCreatedAt(?string $created_at): void
    {
        $this->created_at = $created_at;
    }

    public function getReadAt(): ?string
    {
        return $this->read_at;
    }

    public function setReadAt(?string $read_at): void
    {
        $this->read_at = $read_at;
    }

    public function markAsRead(): void
    {
        $this->is_read = true;
        $this->read_at = date('Y-m-d H:i:s');
    }

    public function isUnread(): bool
    {
        return !$this->is_read;
    }
}
