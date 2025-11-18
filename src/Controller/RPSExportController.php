<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Response;
use App\Service\RPSExportService;
use App\Middleware\AuthMiddleware;

/**
 * RPS Export Controller
 * Handles RPS export to various formats
 */
class RPSExportController
{
    private RPSExportService $service;

    public function __construct()
    {
        $this->service = new RPSExportService();
    }

    /**
     * Export RPS to Markdown
     * GET /api/rps/:id/export/markdown
     */
    public function exportMarkdown(string $id): void
    {
        try {
            $markdown = $this->service->exportToMarkdown((int)$id);

            // Set headers for download
            header('Content-Type: text/markdown; charset=utf-8');
            header('Content-Disposition: attachment; filename="RPS_' . $id . '.md"');
            echo $markdown;
            exit;
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Export RPS to HTML
     * GET /api/rps/:id/export/html
     */
    public function exportHTML(string $id): void
    {
        try {
            $html = $this->service->exportToHTML((int)$id);

            // Set headers for download
            header('Content-Type: text/html; charset=utf-8');
            header('Content-Disposition: attachment; filename="RPS_' . $id . '.html"');
            echo $html;
            exit;
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Export RPS to JSON (complete data)
     * GET /api/rps/:id/export/json
     */
    public function exportJSON(string $id): void
    {
        try {
            $data = $this->service->exportToJSON((int)$id);
            Response::success($data);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Preview RPS in browser (HTML without download)
     * GET /api/rps/:id/preview
     */
    public function preview(string $id): void
    {
        try {
            $html = $this->service->exportToHTML((int)$id);

            // Output HTML directly (no download)
            header('Content-Type: text/html; charset=utf-8');
            echo $html;
            exit;
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }
}
