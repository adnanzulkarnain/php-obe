<?php

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Service\DocumentService;
use App\Middleware\AuthMiddleware;
use App\Utils\FileUploadHelper;

/**
 * Document Controller
 * Handles document/file upload and management
 */
class DocumentController
{
    private DocumentService $service;

    public function __construct()
    {
        $this->service = new DocumentService();
    }

    /**
     * Get all documents
     * GET /api/documents
     */
    public function index(Request $request): void
    {
        $kategori = $request->getQuery('kategori');
        $documents = $this->service->getAllDocuments($kategori);

        Response::json([
            'success' => true,
            'data' => $documents
        ]);
    }

    /**
     * Get document by ID
     * GET /api/documents/:id
     */
    public function show(Request $request, int $id): void
    {
        $document = $this->service->getDocumentById($id);

        if (!$document) {
            Response::json([
                'success' => false,
                'error' => 'Document not found'
            ], 404);
            return;
        }

        Response::json([
            'success' => true,
            'data' => $document
        ]);
    }

    /**
     * Get documents by category and reference
     * GET /api/documents/by-ref?kategori=rps&id_ref=1
     */
    public function getByRef(Request $request): void
    {
        $kategori = $request->getQuery('kategori');
        $idRef = (int) $request->getQuery('id_ref');

        if (!$kategori || !$idRef) {
            Response::json([
                'success' => false,
                'error' => 'kategori and id_ref are required'
            ], 400);
            return;
        }

        $documents = $this->service->getDocumentsByCategoryAndRef($kategori, $idRef);

        Response::json([
            'success' => true,
            'data' => $documents
        ]);
    }

    /**
     * Get current user's documents
     * GET /api/documents/my-documents
     */
    public function myDocuments(Request $request): void
    {
        $user = AuthMiddleware::getCurrentUser();
        $documents = $this->service->getDocumentsByUploader($user['id_user']);

        Response::json([
            'success' => true,
            'data' => $documents
        ]);
    }

    /**
     * Upload document
     * POST /api/documents/upload
     */
    public function upload(Request $request): void
    {
        $user = AuthMiddleware::getCurrentUser();

        // Check if file was uploaded
        if (empty($_FILES['file'])) {
            Response::json([
                'success' => false,
                'error' => 'No file uploaded'
            ], 400);
            return;
        }

        // Get metadata from request
        $data = $request->getBody();

        if (empty($data['kategori_dokumen'])) {
            Response::json([
                'success' => false,
                'error' => 'kategori_dokumen is required'
            ], 400);
            return;
        }

        // Upload document
        $result = $this->service->uploadDocument(
            $_FILES['file'],
            [
                'kategori_dokumen' => $data['kategori_dokumen'],
                'id_ref' => $data['id_ref'] ?? null,
                'deskripsi' => $data['deskripsi'] ?? null
            ],
            $user['id_user']
        );

        if (!$result['success']) {
            Response::json([
                'success' => false,
                'error' => $result['error']
            ], 400);
            return;
        }

        // Get created document
        $document = $this->service->getDocumentById($result['document_id']);

        Response::json([
            'success' => true,
            'message' => 'Document uploaded successfully',
            'data' => $document
        ], 201);
    }

    /**
     * Update document metadata
     * PUT /api/documents/:id
     */
    public function update(Request $request, int $id): void
    {
        $user = AuthMiddleware::getCurrentUser();

        // Verify ownership
        if (!$this->service->validateOwnership($id, $user['id_user'])) {
            Response::json([
                'success' => false,
                'error' => 'Unauthorized'
            ], 403);
            return;
        }

        $data = $request->getBody();

        $success = $this->service->updateDocument($id, $data, $user['id_user']);

        if (!$success) {
            Response::json([
                'success' => false,
                'error' => 'Failed to update document'
            ], 400);
            return;
        }

        $document = $this->service->getDocumentById($id);

        Response::json([
            'success' => true,
            'message' => 'Document updated successfully',
            'data' => $document
        ]);
    }

    /**
     * Delete document
     * DELETE /api/documents/:id
     */
    public function delete(Request $request, int $id): void
    {
        $user = AuthMiddleware::getCurrentUser();

        // Verify ownership or admin
        if (!$this->service->validateOwnership($id, $user['id_user']) && $user['role'] !== 'admin') {
            Response::json([
                'success' => false,
                'error' => 'Unauthorized'
            ], 403);
            return;
        }

        $success = $this->service->deleteDocument($id, $user['id_user']);

        if (!$success) {
            Response::json([
                'success' => false,
                'error' => 'Failed to delete document'
            ], 400);
            return;
        }

        Response::json([
            'success' => true,
            'message' => 'Document deleted successfully'
        ]);
    }

    /**
     * Download document
     * GET /api/documents/:id/download
     */
    public function download(Request $request, int $id): void
    {
        $fileData = $this->service->downloadDocument($id);

        if (!$fileData) {
            Response::json([
                'success' => false,
                'error' => 'Document not found or file missing'
            ], 404);
            return;
        }

        // Set headers for file download
        header('Content-Type: ' . $fileData['mime_type']);
        header('Content-Disposition: attachment; filename="' . $fileData['filename'] . '"');
        header('Content-Length: ' . $fileData['size']);
        header('Cache-Control: must-revalidate');
        header('Pragma: public');

        // Output file
        readfile($fileData['file_path']);
        exit;
    }

    /**
     * Get storage usage
     * GET /api/documents/storage-usage
     */
    public function storageUsage(Request $request): void
    {
        $user = AuthMiddleware::getCurrentUser();
        $usage = $this->service->getUserStorageUsage($user['id_user']);

        Response::json([
            'success' => true,
            'data' => $usage
        ]);
    }

    /**
     * Get upload configuration
     * GET /api/documents/upload-config
     */
    public function uploadConfig(Request $request): void
    {
        Response::json([
            'success' => true,
            'data' => [
                'max_file_size' => FileUploadHelper::getMaxFileSize(),
                'max_file_size_formatted' => FileUploadHelper::formatFileSize(FileUploadHelper::getMaxFileSize()),
                'allowed_types' => FileUploadHelper::getAllowedTypes()
            ]
        ]);
    }

    /**
     * Get statistics (Admin only)
     * GET /api/documents/statistics
     */
    public function statistics(Request $request): void
    {
        $user = AuthMiddleware::getCurrentUser();

        if ($user['role'] !== 'admin') {
            Response::json([
                'success' => false,
                'error' => 'Unauthorized. Admin only.'
            ], 403);
            return;
        }

        $stats = $this->service->getStatistics();

        Response::json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
