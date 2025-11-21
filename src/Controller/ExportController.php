<?php

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Service\ExportService;
use App\Middleware\AuthMiddleware;

/**
 * Export Controller
 * Handles data export endpoints
 */
class ExportController
{
    private ExportService $service;

    public function __construct()
    {
        $this->service = new ExportService();
    }

    /**
     * Export RPS to PDF
     * GET /api/export/rps/:id/pdf
     */
    public function exportRPSToPDF(Request $request, int $id): void
    {
        $filepath = $this->service->exportRPSToPDF($id);

        if (!$filepath) {
            Response::json([
                'success' => false,
                'error' => 'RPS not found or export failed'
            ], 404);
            return;
        }

        $this->downloadFile($filepath, basename($filepath));
        $this->service->cleanupTempFile($filepath);
    }

    /**
     * Export analytics to PDF
     * POST /api/export/analytics/pdf
     */
    public function exportAnalyticsToPDF(Request $request): void
    {
        $data = $request->getBody();

        if (empty($data['title']) || empty($data['tables'])) {
            Response::json([
                'success' => false,
                'error' => 'Missing required fields: title, tables'
            ], 400);
            return;
        }

        $data['date'] = date('d F Y');

        $filepath = $this->service->exportAnalyticsToPDF($data);

        $this->downloadFile($filepath, 'Report_' . date('Y-m-d') . '.pdf');
        $this->service->cleanupTempFile($filepath);
    }

    /**
     * Export analytics to Excel
     * POST /api/export/analytics/excel
     */
    public function exportAnalyticsToExcel(Request $request): void
    {
        $data = $request->getBody();

        if (empty($data['title']) || empty($data['tables'])) {
            Response::json([
                'success' => false,
                'error' => 'Missing required fields: title, tables'
            ], 400);
            return;
        }

        $filepath = $this->service->exportAnalyticsToExcel($data);

        $this->downloadFile($filepath, 'Report_' . date('Y-m-d') . '.xlsx');
        $this->service->cleanupTempFile($filepath);
    }

    /**
     * Export nilai to Excel
     * GET /api/export/nilai/:id_kelas/excel
     */
    public function exportNilaiToExcel(Request $request, int $idKelas): void
    {
        $filepath = $this->service->exportNilaiToExcel($idKelas);

        if (!$filepath) {
            Response::json([
                'success' => false,
                'error' => 'Kelas not found or export failed'
            ], 404);
            return;
        }

        $this->downloadFile($filepath, basename($filepath));
        $this->service->cleanupTempFile($filepath);
    }

    /**
     * Export mahasiswa list to Excel
     * GET /api/export/mahasiswa/excel
     */
    public function exportMahasiswaToExcel(Request $request): void
    {
        $filters = [
            'prodi' => $request->getQuery('prodi'),
            'angkatan' => $request->getQuery('angkatan')
        ];

        $filepath = $this->service->exportMahasiswaToExcel($filters);

        $this->downloadFile($filepath, 'Mahasiswa_' . date('Y-m-d') . '.xlsx');
        $this->service->cleanupTempFile($filepath);
    }

    /**
     * Export kurikulum comparison to Excel
     * POST /api/export/kurikulum/comparison
     */
    public function exportKurikulumComparison(Request $request): void
    {
        $data = $request->getBody();

        if (empty($data['data'])) {
            Response::json([
                'success' => false,
                'error' => 'Missing required field: data'
            ], 400);
            return;
        }

        $filepath = $this->service->exportKurikulumComparisonToExcel($data);

        $this->downloadFile($filepath, 'Kurikulum_Comparison_' . date('Y-m-d') . '.xlsx');
        $this->service->cleanupTempFile($filepath);
    }

    /**
     * Helper: Download file
     */
    private function downloadFile(string $filepath, string $filename): void
    {
        if (!file_exists($filepath)) {
            Response::json([
                'success' => false,
                'error' => 'File not found'
            ], 404);
            return;
        }

        $mimeType = mime_content_type($filepath);
        $size = filesize($filepath);

        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . $size);
        header('Cache-Control: must-revalidate');
        header('Pragma: public');

        readfile($filepath);
        exit;
    }
}
