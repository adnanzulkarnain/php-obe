<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\BaseRepository;

/**
 * Document Service
 * Business logic for document management
 */
class DocumentService
{
    private BaseRepository $repository;
    private AuditLogService $auditLog;

    public function __construct()
    {
        $this->repository = new BaseRepository();
        $this->repository->setTable('documents');
        $this->repository->setPrimaryKey('id_document');
        $this->auditLog = new AuditLogService();
    }

    /**
     * Get documents by entity
     */
    public function getByEntity(string $entityType, int $entityId): array
    {
        $sql = "
            SELECT
                d.*,
                u.username as uploaded_by_username
            FROM documents d
            LEFT JOIN users u ON d.uploaded_by = u.id_user
            WHERE d.entity_type = :entity_type AND d.entity_id = :entity_id
            ORDER BY d.created_at DESC
        ";

        return $this->repository->query($sql, [
            'entity_type' => $entityType,
            'entity_id' => $entityId
        ]);
    }

    /**
     * Get document by ID
     */
    public function getById(int $idDocument): array
    {
        $document = $this->repository->find($idDocument);

        if (!$document) {
            throw new \Exception('Dokumen tidak ditemukan', 404);
        }

        return $document;
    }

    /**
     * Upload document
     */
    public function upload(array $data, int $userId): array
    {
        // Validate required fields
        $required = ['entity_type', 'entity_id', 'file_name', 'file_path', 'file_type'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \Exception("Field {$field} wajib diisi", 400);
            }
        }

        // Prepare data
        $documentData = [
            'entity_type' => $data['entity_type'],
            'entity_id' => (int)$data['entity_id'],
            'file_name' => $data['file_name'],
            'file_path' => $data['file_path'],
            'file_type' => $data['file_type'],
            'file_size' => $data['file_size'] ?? null,
            'mime_type' => $data['mime_type'] ?? null,
            'uploaded_by' => $userId,
            'description' => $data['description'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Insert document
        $sql = "
            INSERT INTO documents
            (entity_type, entity_id, file_name, file_path, file_type, file_size, mime_type, uploaded_by, description, created_at)
            VALUES
            (:entity_type, :entity_id, :file_name, :file_path, :file_type, :file_size, :mime_type, :uploaded_by, :description, :created_at)
            RETURNING id_document
        ";

        $stmt = $this->repository->getDb()->prepare($sql);
        $stmt->execute($documentData);
        $idDocument = (int)$stmt->fetch(\PDO::FETCH_ASSOC)['id_document'];

        // Audit log
        $this->auditLog->log('documents', $idDocument, 'INSERT', null, $documentData, $userId);

        return $this->repository->find($idDocument);
    }

    /**
     * Delete document
     */
    public function delete(int $idDocument, int $userId): void
    {
        // Check if document exists
        $document = $this->repository->find($idDocument);
        if (!$document) {
            throw new \Exception('Dokumen tidak ditemukan', 404);
        }

        // Delete from database
        $sql = "DELETE FROM documents WHERE id_document = :id_document";
        $stmt = $this->repository->getDb()->prepare($sql);
        $stmt->execute(['id_document' => $idDocument]);

        // TODO: Delete physical file from storage
        // if (file_exists($document['file_path'])) {
        //     unlink($document['file_path']);
        // }

        // Audit log
        $this->auditLog->log('documents', $idDocument, 'DELETE', $document, null, $userId);
    }

    /**
     * Get statistics by entity type
     */
    public function getStatsByEntity(string $entityType, int $entityId): array
    {
        $sql = "
            SELECT
                COUNT(*) as total_documents,
                SUM(file_size) as total_size,
                COUNT(DISTINCT file_type) as total_types
            FROM documents
            WHERE entity_type = :entity_type AND entity_id = :entity_id
        ";

        return $this->repository->queryOne($sql, [
            'entity_type' => $entityType,
            'entity_id' => $entityId
        ]) ?: [];
    }
}
