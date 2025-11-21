<?php

namespace App\Service;

use App\Repository\DocumentRepository;
use App\Utils\FileUploadHelper;
use App\Service\AuditLogService;

/**
 * Document Service
 * Business logic for document management
 */
class DocumentService
{
    private DocumentRepository $repository;
    private AuditLogService $auditLog;

    public function __construct()
    {
        $this->repository = new DocumentRepository();
        $this->auditLog = new AuditLogService();
    }

    /**
     * Get all documents
     */
    public function getAllDocuments(?string $kategori = null): array
    {
        return $this->repository->findAll($kategori);
    }

    /**
     * Get document by ID
     */
    public function getDocumentById(int $id): ?array
    {
        return $this->repository->findById($id);
    }

    /**
     * Get documents by category and reference
     */
    public function getDocumentsByCategoryAndRef(string $kategori, int $idRef): array
    {
        return $this->repository->findByCategoryAndRef($kategori, $idRef);
    }

    /**
     * Get documents by uploader
     */
    public function getDocumentsByUploader(int $uploadedBy): array
    {
        return $this->repository->findByUploader($uploadedBy);
    }

    /**
     * Upload document
     *
     * @param array $file The $_FILES['fieldname'] array
     * @param array $metadata Additional metadata
     * @param int $uploadedBy User ID
     * @return array ['success' => bool, 'document_id' => int|null, 'error' => string|null]
     */
    public function uploadDocument(array $file, array $metadata, int $uploadedBy): array
    {
        // Validate required metadata
        if (empty($metadata['kategori_dokumen'])) {
            return ['success' => false, 'document_id' => null, 'error' => 'Category is required'];
        }

        // Upload file
        $uploadResult = FileUploadHelper::upload($file, $metadata['kategori_dokumen']);

        if (!$uploadResult['success']) {
            return [
                'success' => false,
                'document_id' => null,
                'error' => $uploadResult['error']
            ];
        }

        // Create document record
        $documentData = [
            'nama_file' => $uploadResult['original_name'],
            'file_path' => $uploadResult['file_path'],
            'tipe_file' => $uploadResult['extension'],
            'ukuran_file' => $uploadResult['size'],
            'kategori_dokumen' => $metadata['kategori_dokumen'],
            'id_ref' => $metadata['id_ref'] ?? null,
            'uploaded_by' => $uploadedBy,
            'deskripsi' => $metadata['deskripsi'] ?? null
        ];

        try {
            $documentId = $this->repository->create($documentData);

            // Audit log
            $this->auditLog->log(
                'documents',
                $documentId,
                'create',
                null,
                $documentData,
                $uploadedBy
            );

            return [
                'success' => true,
                'document_id' => $documentId,
                'file_path' => $uploadResult['file_path'],
                'error' => null
            ];
        } catch (\Exception $e) {
            // Rollback: delete uploaded file
            FileUploadHelper::delete($uploadResult['file_path']);

            return [
                'success' => false,
                'document_id' => null,
                'error' => 'Failed to create document record: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update document metadata
     */
    public function updateDocument(int $id, array $data, int $userId): bool
    {
        $document = $this->repository->findById($id);

        if (!$document) {
            return false;
        }

        $success = $this->repository->update($id, $data);

        if ($success) {
            $this->auditLog->log(
                'documents',
                $id,
                'update',
                $document,
                $data,
                $userId
            );
        }

        return $success;
    }

    /**
     * Delete document
     */
    public function deleteDocument(int $id, int $userId): bool
    {
        $document = $this->repository->findById($id);

        if (!$document) {
            return false;
        }

        // Delete file from storage
        FileUploadHelper::delete($document['file_path']);

        // Delete database record
        $success = $this->repository->delete($id);

        if ($success) {
            $this->auditLog->log(
                'documents',
                $id,
                'delete',
                $document,
                null,
                $userId
            );
        }

        return $success;
    }

    /**
     * Download document
     */
    public function downloadDocument(int $id): ?array
    {
        $document = $this->repository->findById($id);

        if (!$document) {
            return null;
        }

        $filePath = FileUploadHelper::getFullPath($document['file_path']);

        if (!file_exists($filePath)) {
            return null;
        }

        return [
            'file_path' => $filePath,
            'filename' => $document['nama_file'],
            'mime_type' => mime_content_type($filePath),
            'size' => filesize($filePath)
        ];
    }

    /**
     * Get user storage usage
     */
    public function getUserStorageUsage(int $userId): array
    {
        $totalBytes = $this->repository->getTotalStorageByUser($userId);
        $maxBytes = FileUploadHelper::getMaxFileSize() * 10; // 10x max file size as quota

        return [
            'used_bytes' => $totalBytes,
            'used_formatted' => FileUploadHelper::formatFileSize($totalBytes),
            'quota_bytes' => $maxBytes,
            'quota_formatted' => FileUploadHelper::formatFileSize($maxBytes),
            'percentage' => $maxBytes > 0 ? round(($totalBytes / $maxBytes) * 100, 2) : 0
        ];
    }

    /**
     * Get statistics
     */
    public function getStatistics(): array
    {
        return $this->repository->getStatistics();
    }

    /**
     * Validate document ownership
     */
    public function validateOwnership(int $documentId, int $userId): bool
    {
        $document = $this->repository->findById($documentId);

        if (!$document) {
            return false;
        }

        return $document['uploaded_by'] === $userId;
    }
}
