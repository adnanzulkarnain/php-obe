<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Service\DocumentService;
use App\Middleware\AuthMiddleware;

/**
 * Document Controller
 * Handles document management endpoints
 */
class DocumentController
{
    private DocumentService $service;

    public function __construct()
    {
        $this->service = new DocumentService();
    }

    /**
     * Get documents by entity
     * GET /api/documents/:entity_type/:entity_id
     */
    public function getByEntity(string $entityType, string $entityId): void
    {
        try {
            $documents = $this->service->getByEntity($entityType, (int)$entityId);
            Response::success($documents);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get document by ID
     * GET /api/documents/:id
     */
    public function show(string $id): void
    {
        try {
            $document = $this->service->getById((int)$id);
            Response::success($document);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Upload document
     * POST /api/documents
     */
    public function upload(): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'mahasiswa', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $data = Request::only([
                'entity_type',
                'entity_id',
                'file_name',
                'file_path',
                'file_type',
                'file_size',
                'mime_type',
                'description'
            ]);

            $document = $this->service->upload($data, $user['id_user']);

            Response::success($document, 'Dokumen berhasil diupload', 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Delete document
     * DELETE /api/documents/:id
     */
    public function delete(string $id): void
    {
        try {
            AuthMiddleware::requireRole('dosen', 'kaprodi', 'admin');

            $user = AuthMiddleware::user();
            $this->service->delete((int)$id, $user['id_user']);

            Response::success(null, 'Dokumen berhasil dihapus');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get statistics
     * GET /api/documents/stats/:entity_type/:entity_id
     */
    public function getStats(string $entityType, string $entityId): void
    {
        try {
            $stats = $this->service->getStatsByEntity($entityType, (int)$entityId);
            Response::success($stats);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }
}
