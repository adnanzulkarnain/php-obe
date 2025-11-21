<?php

namespace App\Repository;

use App\Core\BaseRepository;

/**
 * Document Repository
 * Handles database operations for documents
 */
class DocumentRepository extends BaseRepository
{
    /**
     * Find all documents
     */
    public function findAll(?string $kategori = null): array
    {
        $sql = "SELECT d.*, u.username as uploaded_by_username
                FROM documents d
                LEFT JOIN users u ON d.uploaded_by = u.id_user
                WHERE 1=1";

        $params = [];

        if ($kategori) {
            $sql .= " AND d.kategori_dokumen = :kategori";
            $params['kategori'] = $kategori;
        }

        $sql .= " ORDER BY d.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Find document by ID
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT d.*, u.username as uploaded_by_username
                FROM documents d
                LEFT JOIN users u ON d.uploaded_by = u.id_user
                WHERE d.id_document = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Find documents by category and reference ID
     */
    public function findByCategoryAndRef(string $kategori, int $idRef): array
    {
        $sql = "SELECT d.*, u.username as uploaded_by_username
                FROM documents d
                LEFT JOIN users u ON d.uploaded_by = u.id_user
                WHERE d.kategori_dokumen = :kategori
                AND d.id_ref = :id_ref
                ORDER BY d.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'kategori' => $kategori,
            'id_ref' => $idRef
        ]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Find documents by uploader
     */
    public function findByUploader(int $uploadedBy): array
    {
        $sql = "SELECT * FROM documents
                WHERE uploaded_by = :uploaded_by
                ORDER BY created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uploaded_by' => $uploadedBy]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Create new document record
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO documents (
                    nama_file, file_path, tipe_file, ukuran_file,
                    kategori_dokumen, id_ref, uploaded_by, deskripsi,
                    created_at
                ) VALUES (
                    :nama_file, :file_path, :tipe_file, :ukuran_file,
                    :kategori_dokumen, :id_ref, :uploaded_by, :deskripsi,
                    NOW()
                ) RETURNING id_document";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'nama_file' => $data['nama_file'],
            'file_path' => $data['file_path'],
            'tipe_file' => $data['tipe_file'],
            'ukuran_file' => $data['ukuran_file'],
            'kategori_dokumen' => $data['kategori_dokumen'],
            'id_ref' => $data['id_ref'] ?? null,
            'uploaded_by' => $data['uploaded_by'] ?? null,
            'deskripsi' => $data['deskripsi'] ?? null
        ]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Update document metadata
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];

        if (isset($data['nama_file'])) {
            $fields[] = 'nama_file = :nama_file';
            $params['nama_file'] = $data['nama_file'];
        }

        if (isset($data['deskripsi'])) {
            $fields[] = 'deskripsi = :deskripsi';
            $params['deskripsi'] = $data['deskripsi'];
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE documents SET " . implode(', ', $fields) . " WHERE id_document = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Delete document record
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM documents WHERE id_document = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Get total storage used by user
     */
    public function getTotalStorageByUser(int $uploadedBy): int
    {
        $sql = "SELECT COALESCE(SUM(ukuran_file), 0) as total
                FROM documents
                WHERE uploaded_by = :uploaded_by";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uploaded_by' => $uploadedBy]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Get documents statistics
     */
    public function getStatistics(): array
    {
        $sql = "SELECT
                    COUNT(*) as total_documents,
                    SUM(ukuran_file) as total_size,
                    kategori_dokumen,
                    COUNT(*) as count_by_category
                FROM documents
                GROUP BY kategori_dokumen";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
