<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Service\PrasyaratMKService;
use App\Middleware\AuthMiddleware;

/**
 * PrasyaratMK Controller
 * API endpoints for course prerequisites management
 */
class PrasyaratMKController
{
    private PrasyaratMKService $service;

    public function __construct()
    {
        $this->service = new PrasyaratMKService();
    }

    /**
     * Get prerequisites for a course
     * GET /api/matakuliah/:kodeMk/prerequisites?id_kurikulum=1
     */
    public function getPrerequisites(string $kodeMk): void
    {
        try {
            $idKurikulum = Request::input('id_kurikulum');

            if (!$idKurikulum) {
                Response::error('id_kurikulum is required', 400);
                return;
            }

            $prerequisites = $this->service->getPrerequisites($kodeMk, (int)$idKurikulum);

            Response::success($prerequisites);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get courses that require this course as prerequisite
     * GET /api/matakuliah/:kodeMk/required-by?id_kurikulum=1
     */
    public function getCoursesRequiring(string $kodeMk): void
    {
        try {
            $idKurikulum = Request::input('id_kurikulum');

            if (!$idKurikulum) {
                Response::error('id_kurikulum is required', 400);
                return;
            }

            $courses = $this->service->getCoursesRequiring($kodeMk, (int)$idKurikulum);

            Response::success($courses);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get prerequisite tree (recursive)
     * GET /api/matakuliah/:kodeMk/prerequisite-tree?id_kurikulum=1
     */
    public function getPrerequisiteTree(string $kodeMk): void
    {
        try {
            $idKurikulum = Request::input('id_kurikulum');

            if (!$idKurikulum) {
                Response::error('id_kurikulum is required', 400);
                return;
            }

            $tree = $this->service->getPrerequisiteTree($kodeMk, (int)$idKurikulum);

            Response::success($tree);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Add prerequisite to a course
     * POST /api/prerequisites
     * Body: {kode_mk, id_kurikulum, kode_mk_prasyarat, jenis_prasyarat}
     */
    public function create(): void
    {
        try {
            $data = Request::json();
            $userId = AuthMiddleware::getUserId();

            $idPrasyarat = $this->service->create($data, $userId);

            Response::success([
                'message' => 'Prerequisite added successfully',
                'id_prasyarat' => $idPrasyarat,
            ], 201);
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 409);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Delete prerequisite by ID
     * DELETE /api/prerequisites/:id
     */
    public function delete(string $id): void
    {
        try {
            $userId = AuthMiddleware::getUserId();
            $success = $this->service->delete((int)$id, $userId);

            if ($success) {
                Response::success(['message' => 'Prerequisite deleted successfully']);
            } else {
                Response::error('Failed to delete prerequisite', 500);
            }
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Delete prerequisite by mata kuliah and prasyarat
     * DELETE /api/matakuliah/:kodeMk/prerequisites/:kodeMkPrasyarat?id_kurikulum=1
     */
    public function deleteByMataKuliah(string $kodeMk, string $kodeMkPrasyarat): void
    {
        try {
            $idKurikulum = Request::input('id_kurikulum');

            if (!$idKurikulum) {
                Response::error('id_kurikulum is required', 400);
                return;
            }

            $userId = AuthMiddleware::getUserId();
            $success = $this->service->deleteByMataKuliahAndPrasyarat(
                $kodeMk,
                (int)$idKurikulum,
                $kodeMkPrasyarat,
                $userId
            );

            if ($success) {
                Response::success(['message' => 'Prerequisite deleted successfully']);
            } else {
                Response::error('Failed to delete prerequisite', 500);
            }
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Check if student can enroll based on prerequisites
     * GET /api/students/:nim/enrollment-eligibility/:kodeMk?id_kurikulum=1
     */
    public function checkEnrollmentEligibility(string $nim, string $kodeMk): void
    {
        try {
            $idKurikulum = Request::input('id_kurikulum');

            if (!$idKurikulum) {
                Response::error('id_kurikulum is required', 400);
                return;
            }

            $result = $this->service->checkEnrollmentEligibility(
                $nim,
                $kodeMk,
                (int)$idKurikulum
            );

            Response::success($result);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get prerequisite statistics for a kurikulum
     * GET /api/kurikulum/:id/prerequisite-statistics
     */
    public function getStatistics(string $id): void
    {
        try {
            $statistics = $this->service->getStatistics((int)$id);
            Response::success($statistics);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Bulk add prerequisites
     * POST /api/prerequisites/bulk
     * Body: {prerequisites: [{kode_mk, id_kurikulum, kode_mk_prasyarat, jenis_prasyarat}, ...]}
     */
    public function bulkCreate(): void
    {
        try {
            $data = Request::json();
            $userId = AuthMiddleware::getUserId();

            if (!isset($data['prerequisites']) || !is_array($data['prerequisites'])) {
                Response::error('Array of prerequisites is required in "prerequisites" field', 400);
                return;
            }

            $results = $this->service->bulkCreate($data['prerequisites'], $userId);

            Response::success([
                'message' => 'Bulk create completed',
                'total_processed' => count($data['prerequisites']),
                'success_count' => count($results['success']),
                'failed_count' => count($results['failed']),
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }
}
