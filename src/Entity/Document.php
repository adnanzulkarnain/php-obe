<?php

namespace App\Entity;

/**
 * Document Entity
 * Represents a document/file in the system
 */
class Document
{
    private ?int $id_document = null;
    private string $nama_file;
    private string $file_path;
    private string $tipe_file;
    private int $ukuran_file;
    private string $kategori_dokumen;
    private ?int $id_ref = null;
    private ?int $uploaded_by = null;
    private ?string $deskripsi = null;
    private ?string $created_at = null;

    // Document categories
    public const CATEGORY_RPS = 'rps';
    public const CATEGORY_SILABUS = 'silabus';
    public const CATEGORY_MATERI = 'materi';
    public const CATEGORY_TUGAS = 'tugas';
    public const CATEGORY_SOAL = 'soal';
    public const CATEGORY_KURIKULUM = 'kurikulum';
    public const CATEGORY_LAINNYA = 'lainnya';

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
            'id_document' => $this->id_document,
            'nama_file' => $this->nama_file,
            'file_path' => $this->file_path,
            'tipe_file' => $this->tipe_file,
            'ukuran_file' => $this->ukuran_file,
            'kategori_dokumen' => $this->kategori_dokumen,
            'id_ref' => $this->id_ref,
            'uploaded_by' => $this->uploaded_by,
            'deskripsi' => $this->deskripsi,
            'created_at' => $this->created_at
        ];
    }

    // Getters and Setters

    public function getIdDocument(): ?int
    {
        return $this->id_document;
    }

    public function setIdDocument(int $id_document): void
    {
        $this->id_document = $id_document;
    }

    public function getNamaFile(): string
    {
        return $this->nama_file;
    }

    public function setNamaFile(string $nama_file): void
    {
        $this->nama_file = $nama_file;
    }

    public function getFilePath(): string
    {
        return $this->file_path;
    }

    public function setFilePath(string $file_path): void
    {
        $this->file_path = $file_path;
    }

    public function getTipeFile(): string
    {
        return $this->tipe_file;
    }

    public function setTipeFile(string $tipe_file): void
    {
        $this->tipe_file = $tipe_file;
    }

    public function getUkuranFile(): int
    {
        return $this->ukuran_file;
    }

    public function setUkuranFile(int $ukuran_file): void
    {
        $this->ukuran_file = $ukuran_file;
    }

    public function getKategoriDokumen(): string
    {
        return $this->kategori_dokumen;
    }

    public function setKategoriDokumen(string $kategori_dokumen): void
    {
        $this->kategori_dokumen = $kategori_dokumen;
    }

    public function getIdRef(): ?int
    {
        return $this->id_ref;
    }

    public function setIdRef(?int $id_ref): void
    {
        $this->id_ref = $id_ref;
    }

    public function getUploadedBy(): ?int
    {
        return $this->uploaded_by;
    }

    public function setUploadedBy(?int $uploaded_by): void
    {
        $this->uploaded_by = $uploaded_by;
    }

    public function getDeskripsi(): ?string
    {
        return $this->deskripsi;
    }

    public function setDeskripsi(?string $deskripsi): void
    {
        $this->deskripsi = $deskripsi;
    }

    public function getCreatedAt(): ?string
    {
        return $this->created_at;
    }

    public function setCreatedAt(?string $created_at): void
    {
        $this->created_at = $created_at;
    }

    /**
     * Get file size in human readable format
     */
    public function getFormattedSize(): string
    {
        $bytes = $this->ukuran_file;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get file extension
     */
    public function getExtension(): string
    {
        return pathinfo($this->nama_file, PATHINFO_EXTENSION);
    }
}
