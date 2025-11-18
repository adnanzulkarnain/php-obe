<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Service\FakultasService;
use App\Middleware\AuthMiddleware;

/**
 * Fakultas Controller
 * API endpoints for fakultas (faculty/school) management
 */
class FakultasController
{
    private FakultasService $service;

    public function __construct()
    {
        $this->service = new FakultasService();
    }

    /**
     * Get all fakultas
     * GET /api/fakultas?q=search
     */
    public function index(): void
    {
        try {
            $search = Request::input('q');

            if ($search) {
                $fakultas = $this->service->search($search);
            } else {
                $fakultas = $this->service->getAll();
            }

            Response::success($fakultas);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get fakultas by ID with details
     * GET /api/fakultas/:id
     */
    public function show(string $id): void
    {
        try {
            $fakultas = $this->service->getById($id);

            if (!$fakultas) {
                Response::error('Fakultas not found', 404);
                return;
            }

            Response::success($fakultas);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Create new fakultas
     * POST /api/fakultas
     */
    public function create(): void
    {
        try {
            $data = Request::json();
            $userId = AuthMiddleware::getUserId();

            $idFakultas = $this->service->create($data, $userId);

            Response::success([
                'message' => 'Fakultas created successfully',
                'id_fakultas' => $idFakultas,
            ], 201);
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Update fakultas
     * PUT /api/fakultas/:id
     */
    public function update(string $id): void
    {
        try {
            $data = Request::json();
            $userId = AuthMiddleware::getUserId();

            $success = $this->service->update($id, $data, $userId);

            if ($success) {
                Response::success(['message' => 'Fakultas updated successfully']);
            } else {
                Response::error('Failed to update fakultas', 500);
            }
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Delete fakultas
     * DELETE /api/fakultas/:id
     */
    public function delete(string $id): void
    {
        try {
            $userId = AuthMiddleware::getUserId();
            $success = $this->service->delete($id, $userId);

            if ($success) {
                Response::success(['message' => 'Fakultas deleted successfully']);
            } else {
                Response::error('Failed to delete fakultas', 500);
            }
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 409); // Conflict
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Get fakultas statistics
     * GET /api/fakultas/statistics
     */
    public function getStatistics(): void
    {
        try {
            $statistics = $this->service->getStatistics();
            Response::success($statistics);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }
}
