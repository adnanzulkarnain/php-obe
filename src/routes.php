<?php

declare(strict_types=1);

/**
 * Application Routes
 * Define all API endpoints here
 */

use App\Controller\AuthController;
use App\Controller\KurikulumController;
use App\Controller\CPLController;
use App\Controller\MataKuliahController;
use App\Controller\KelasController;
use App\Middleware\AuthMiddleware;

// ============================================
// Public Routes (No Authentication Required)
// ============================================

// Authentication
$router->post('/auth/login', [AuthController::class, 'login']);
$router->post('/auth/logout', [AuthController::class, 'logout']);

// ============================================
// Protected Routes (Authentication Required)
// ============================================

// Profile
$router->get('/auth/profile', [AuthController::class, 'profile'], [AuthMiddleware::class]);
$router->post('/auth/change-password', [AuthController::class, 'changePassword'], [AuthMiddleware::class]);

// ============================================
// KURIKULUM MANAGEMENT
// ============================================

// Get kurikulum list and detail
$router->get('/kurikulum', [KurikulumController::class, 'index'], [AuthMiddleware::class]);
$router->get('/kurikulum/:id', [KurikulumController::class, 'show'], [AuthMiddleware::class]);

// Create and manage kurikulum (UC-K01, UC-K02, UC-K03, UC-K09)
$router->post('/kurikulum', [KurikulumController::class, 'create'], [AuthMiddleware::class]);
$router->post('/kurikulum/:id/approve', [KurikulumController::class, 'approve'], [AuthMiddleware::class]);
$router->post('/kurikulum/:id/activate', [KurikulumController::class, 'activate'], [AuthMiddleware::class]);
$router->post('/kurikulum/:id/deactivate', [KurikulumController::class, 'deactivate'], [AuthMiddleware::class]);

// Compare kurikulum (UC-K08)
$router->get('/kurikulum/compare', [KurikulumController::class, 'compare'], [AuthMiddleware::class]);

// ============================================
// CPL MANAGEMENT
// ============================================

// Get CPL
$router->get('/cpl', [CPLController::class, 'index'], [AuthMiddleware::class]);

// Create, update, delete CPL (UC-K04)
$router->post('/cpl', [CPLController::class, 'create'], [AuthMiddleware::class]);
$router->put('/cpl/:id', [CPLController::class, 'update'], [AuthMiddleware::class]);
$router->delete('/cpl/:id', [CPLController::class, 'delete'], [AuthMiddleware::class]);

// ============================================
// MATA KULIAH MANAGEMENT
// ============================================

// Get Mata Kuliah
$router->get('/matakuliah', [MataKuliahController::class, 'index'], [AuthMiddleware::class]);

// Create, update, delete MK (UC-K05)
$router->post('/matakuliah', [MataKuliahController::class, 'create'], [AuthMiddleware::class]);
$router->put('/matakuliah/:kode_mk/:id_kurikulum', [MataKuliahController::class, 'update'], [AuthMiddleware::class]);
$router->delete('/matakuliah/:kode_mk/:id_kurikulum', [MataKuliahController::class, 'delete'], [AuthMiddleware::class]);

// ============================================
// KELAS MANAGEMENT
// ============================================

// Get kelas list and detail
$router->get('/kelas', [KelasController::class, 'index'], [AuthMiddleware::class]);
$router->get('/kelas/:id', [KelasController::class, 'show'], [AuthMiddleware::class]);

// Create, update, delete kelas
$router->post('/kelas', [KelasController::class, 'create'], [AuthMiddleware::class]);
$router->put('/kelas/:id', [KelasController::class, 'update'], [AuthMiddleware::class]);
$router->delete('/kelas/:id', [KelasController::class, 'delete'], [AuthMiddleware::class]);

// Change kelas status
$router->post('/kelas/:id/status', [KelasController::class, 'changeStatus'], [AuthMiddleware::class]);

// Get statistics
$router->get('/kelas/statistics', [KelasController::class, 'statistics'], [AuthMiddleware::class]);

// Teaching assignments
$router->get('/kelas/:id/dosen', [KelasController::class, 'getTeachingAssignments'], [AuthMiddleware::class]);
$router->post('/kelas/:id/dosen', [KelasController::class, 'assignDosen'], [AuthMiddleware::class]);
$router->put('/kelas/:id/dosen/:id_dosen', [KelasController::class, 'updateDosenPeran'], [AuthMiddleware::class]);
$router->delete('/kelas/:id/dosen/:id_dosen', [KelasController::class, 'removeDosen'], [AuthMiddleware::class]);

// Dosen endpoints
$router->get('/dosen/:id_dosen/kelas', [KelasController::class, 'getDosenKelas'], [AuthMiddleware::class]);
$router->get('/dosen/:id_dosen/teaching-load', [KelasController::class, 'getTeachingLoadStats'], [AuthMiddleware::class]);

// ============================================
// Health Check
// ============================================

$router->get('/health', function () {
    \App\Core\Response::json([
        'status' => 'OK',
        'timestamp' => date('Y-m-d H:i:s'),
        'environment' => $_ENV['APP_ENV'] ?? 'production',
    ]);
});

// Root endpoint
$router->get('/', function () {
    \App\Core\Response::json([
        'message' => 'Sistem Informasi Kurikulum OBE API',
        'version' => '1.0.0',
        'documentation' => '/api/docs',
    ]);
});
