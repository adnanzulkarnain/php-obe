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
use App\Controller\EnrollmentController;
use App\Controller\RPSController;
use App\Controller\CPMKController;
use App\Controller\PenilaianController;
use App\Controller\DosenController;
use App\Controller\MahasiswaController;
use App\Controller\FakultasController;
use App\Controller\ProdiController;
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
// ENROLLMENT MANAGEMENT (KRS)
// ============================================

// Enrollment CRUD
$router->get('/enrollment/:id', [EnrollmentController::class, 'show'], [AuthMiddleware::class]);
$router->post('/enrollment', [EnrollmentController::class, 'enroll'], [AuthMiddleware::class]);
$router->post('/enrollment/bulk', [EnrollmentController::class, 'bulkEnroll'], [AuthMiddleware::class]);
$router->post('/enrollment/:id/drop', [EnrollmentController::class, 'drop'], [AuthMiddleware::class]);
$router->put('/enrollment/:id/status', [EnrollmentController::class, 'updateStatus'], [AuthMiddleware::class]);
$router->put('/enrollment/:id/grades', [EnrollmentController::class, 'updateGrades'], [AuthMiddleware::class]);

// Mahasiswa endpoints
$router->get('/mahasiswa/:nim/enrollment', [EnrollmentController::class, 'getByMahasiswa'], [AuthMiddleware::class]);
$router->get('/mahasiswa/:nim/krs', [EnrollmentController::class, 'getKRS'], [AuthMiddleware::class]);
$router->get('/mahasiswa/:nim/transcript', [EnrollmentController::class, 'getTranscript'], [AuthMiddleware::class]);
$router->get('/mahasiswa/:nim/enrollment-capacity', [EnrollmentController::class, 'validateCapacity'], [AuthMiddleware::class]);

// Kelas enrollment endpoints
$router->get('/kelas/:id/enrollment', [EnrollmentController::class, 'getByKelas'], [AuthMiddleware::class]);
$router->get('/kelas/:id/statistics', [EnrollmentController::class, 'getKelasStatistics'], [AuthMiddleware::class]);

// ============================================
// RPS MANAGEMENT (Rencana Pembelajaran Semester)
// ============================================

// RPS CRUD
$router->get('/rps', [RPSController::class, 'index'], [AuthMiddleware::class]);
$router->get('/rps/:id', [RPSController::class, 'show'], [AuthMiddleware::class]);
$router->post('/rps', [RPSController::class, 'create'], [AuthMiddleware::class]);
$router->put('/rps/:id', [RPSController::class, 'update'], [AuthMiddleware::class]);
$router->delete('/rps/:id', [RPSController::class, 'delete'], [AuthMiddleware::class]);

// RPS workflow
$router->post('/rps/:id/submit', [RPSController::class, 'submit'], [AuthMiddleware::class]);
$router->post('/rps/:id/activate', [RPSController::class, 'activate'], [AuthMiddleware::class]);
$router->post('/rps/:id/archive', [RPSController::class, 'archive'], [AuthMiddleware::class]);

// RPS approval
$router->post('/rps/approval/:id_approval', [RPSController::class, 'processApproval'], [AuthMiddleware::class]);
$router->get('/rps/pending-approvals', [RPSController::class, 'getPendingApprovals'], [AuthMiddleware::class]);

// RPS version control
$router->get('/rps/:id/versions', [RPSController::class, 'getVersions'], [AuthMiddleware::class]);
$router->post('/rps/:id/versions/:version_number/activate', [RPSController::class, 'setActiveVersion'], [AuthMiddleware::class]);

// RPS statistics
$router->get('/rps/statistics', [RPSController::class, 'statistics'], [AuthMiddleware::class]);

// ============================================
// CPMK MANAGEMENT (Capaian Pembelajaran Mata Kuliah)
// ============================================

// CPMK CRUD
$router->get('/cpmk', [CPMKController::class, 'index'], [AuthMiddleware::class]);
$router->get('/cpmk/:id', [CPMKController::class, 'show'], [AuthMiddleware::class]);
$router->post('/cpmk', [CPMKController::class, 'create'], [AuthMiddleware::class]);
$router->put('/cpmk/:id', [CPMKController::class, 'update'], [AuthMiddleware::class]);
$router->delete('/cpmk/:id', [CPMKController::class, 'delete'], [AuthMiddleware::class]);

// SubCPMK
$router->get('/cpmk/:id/subcpmk', [CPMKController::class, 'getSubCPMK'], [AuthMiddleware::class]);
$router->post('/cpmk/:id/subcpmk', [CPMKController::class, 'createSubCPMK'], [AuthMiddleware::class]);
$router->put('/subcpmk/:id', [CPMKController::class, 'updateSubCPMK'], [AuthMiddleware::class]);
$router->delete('/subcpmk/:id', [CPMKController::class, 'deleteSubCPMK'], [AuthMiddleware::class]);

// CPMK-CPL Mapping
$router->post('/cpmk/:id/map-cpl', [CPMKController::class, 'mapToCPL'], [AuthMiddleware::class]);
$router->get('/cpmk/:id/cpl-mappings', [CPMKController::class, 'getCPLMappings'], [AuthMiddleware::class]);
$router->get('/cpl/:id/cpmk-mappings', [CPMKController::class, 'getCPMKMappingsByCPL'], [AuthMiddleware::class]);
$router->put('/cpmk-cpl-mapping/:id', [CPMKController::class, 'updateMappingBobot'], [AuthMiddleware::class]);
$router->delete('/cpmk-cpl-mapping/:id', [CPMKController::class, 'deleteMapping'], [AuthMiddleware::class]);

// Statistics & Validation
$router->get('/rps/:id/cpmk-statistics', [CPMKController::class, 'getRPSStatistics'], [AuthMiddleware::class]);
$router->get('/rps/:id/validate-cpmk', [CPMKController::class, 'validateRPSCompleteness'], [AuthMiddleware::class]);

// ============================================
// PENILAIAN SYSTEM (Grading System)
// ============================================

// Template Penilaian
$router->get('/rps/:id/template-penilaian', [PenilaianController::class, 'getTemplatesByRPS'], [AuthMiddleware::class]);
$router->post('/template-penilaian', [PenilaianController::class, 'createTemplate'], [AuthMiddleware::class]);
$router->get('/rps/:id/validate-template', [PenilaianController::class, 'validateTemplateBobot'], [AuthMiddleware::class]);

// Komponen Penilaian
$router->get('/kelas/:id/komponen-penilaian', [PenilaianController::class, 'getKomponenByKelas'], [AuthMiddleware::class]);
$router->post('/komponen-penilaian', [PenilaianController::class, 'createKomponen'], [AuthMiddleware::class]);
$router->put('/komponen-penilaian/:id', [PenilaianController::class, 'updateKomponen'], [AuthMiddleware::class]);
$router->delete('/komponen-penilaian/:id', [PenilaianController::class, 'deleteKomponen'], [AuthMiddleware::class]);

// Nilai Input
$router->post('/nilai', [PenilaianController::class, 'inputNilai'], [AuthMiddleware::class]);
$router->post('/nilai/bulk', [PenilaianController::class, 'bulkInputNilai'], [AuthMiddleware::class]);
$router->get('/enrollment/:id/nilai', [PenilaianController::class, 'getNilaiByEnrollment'], [AuthMiddleware::class]);
$router->get('/komponen-penilaian/:id/nilai', [PenilaianController::class, 'getNilaiByKomponen'], [AuthMiddleware::class]);

// Summary & Statistics
$router->get('/kelas/:id/nilai-summary', [PenilaianController::class, 'getNilaiSummaryByKelas'], [AuthMiddleware::class]);
$router->get('/komponen-penilaian/:id/statistics', [PenilaianController::class, 'getKomponenStatistics'], [AuthMiddleware::class]);
$router->get('/enrollment/:id/cpmk-achievement/:id_cpmk', [PenilaianController::class, 'calculateCPMKAchievement'], [AuthMiddleware::class]);
$router->post('/kelas/:id/recalculate-grades', [PenilaianController::class, 'recalculateKelasGrades'], [AuthMiddleware::class]);

// Master Data
$router->get('/jenis-penilaian', [PenilaianController::class, 'getAllJenisPenilaian'], [AuthMiddleware::class]);

// ============================================
// DOSEN MANAGEMENT (Lecturer/Faculty Management)
// ============================================

// Dosen CRUD
$router->get('/dosen', [DosenController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dosen/:id', [DosenController::class, 'show'], [AuthMiddleware::class]);
$router->post('/dosen', [DosenController::class, 'create'], [AuthMiddleware::class]);
$router->put('/dosen/:id', [DosenController::class, 'update'], [AuthMiddleware::class]);
$router->delete('/dosen/:id', [DosenController::class, 'delete'], [AuthMiddleware::class]);

// Dosen Status Management
$router->post('/dosen/:id/change-status', [DosenController::class, 'changeStatus'], [AuthMiddleware::class]);

// Dosen Queries
$router->get('/dosen/nidn/:nidn', [DosenController::class, 'getByNidn'], [AuthMiddleware::class]);
$router->get('/dosen/status/:status', [DosenController::class, 'getByStatus'], [AuthMiddleware::class]);
$router->get('/prodi/:id/dosen', [DosenController::class, 'getByProdi'], [AuthMiddleware::class]);

// Dosen Statistics & Reports
$router->get('/dosen/statistics', [DosenController::class, 'getStatistics'], [AuthMiddleware::class]);
$router->get('/dosen/teaching-load', [DosenController::class, 'getTeachingLoad'], [AuthMiddleware::class]);

// User Account Management
$router->post('/dosen/:id/create-user', [DosenController::class, 'createUserAccount'], [AuthMiddleware::class]);

// ============================================
// MAHASISWA MANAGEMENT (Student Management)
// ============================================

// Mahasiswa CRUD
$router->get('/mahasiswa', [MahasiswaController::class, 'index'], [AuthMiddleware::class]);
$router->get('/mahasiswa/:nim', [MahasiswaController::class, 'show'], [AuthMiddleware::class]);
$router->post('/mahasiswa', [MahasiswaController::class, 'create'], [AuthMiddleware::class]);
$router->put('/mahasiswa/:nim', [MahasiswaController::class, 'update'], [AuthMiddleware::class]);
$router->delete('/mahasiswa/:nim', [MahasiswaController::class, 'delete'], [AuthMiddleware::class]);

// Mahasiswa Bulk Operations
$router->post('/mahasiswa/bulk', [MahasiswaController::class, 'bulkCreate'], [AuthMiddleware::class]);

// Mahasiswa Status Management
$router->post('/mahasiswa/:nim/change-status', [MahasiswaController::class, 'changeStatus'], [AuthMiddleware::class]);

// Mahasiswa Queries
$router->get('/mahasiswa/angkatan/:angkatan', [MahasiswaController::class, 'getByAngkatan'], [AuthMiddleware::class]);
$router->get('/mahasiswa/status/:status', [MahasiswaController::class, 'getByStatus'], [AuthMiddleware::class]);
$router->get('/prodi/:id/mahasiswa', [MahasiswaController::class, 'getByProdi'], [AuthMiddleware::class]);
$router->get('/kurikulum/:id/mahasiswa', [MahasiswaController::class, 'getByKurikulum'], [AuthMiddleware::class]);

// Mahasiswa Statistics & Reports
$router->get('/mahasiswa/statistics', [MahasiswaController::class, 'getStatistics'], [AuthMiddleware::class]);
$router->get('/mahasiswa/academic-data', [MahasiswaController::class, 'getAcademicData'], [AuthMiddleware::class]);

// User Account Management
$router->post('/mahasiswa/:nim/create-user', [MahasiswaController::class, 'createUserAccount'], [AuthMiddleware::class]);

// ============================================
// FAKULTAS MANAGEMENT (Faculty/School Management)
// ============================================

// Fakultas CRUD
$router->get('/fakultas', [FakultasController::class, 'index'], [AuthMiddleware::class]);
$router->get('/fakultas/:id', [FakultasController::class, 'show'], [AuthMiddleware::class]);
$router->post('/fakultas', [FakultasController::class, 'create'], [AuthMiddleware::class]);
$router->put('/fakultas/:id', [FakultasController::class, 'update'], [AuthMiddleware::class]);
$router->delete('/fakultas/:id', [FakultasController::class, 'delete'], [AuthMiddleware::class]);

// Fakultas Statistics
$router->get('/fakultas/statistics', [FakultasController::class, 'getStatistics'], [AuthMiddleware::class]);

// Fakultas Related Data (already defined in other sections)
// GET /fakultas/:id/prodi - defined in PRODI section
// GET /fakultas/:id/dosen - can be added if needed

// ============================================
// PRODI MANAGEMENT (Study Program Management)
// ============================================

// Prodi CRUD
$router->get('/prodi', [ProdiController::class, 'index'], [AuthMiddleware::class]);
$router->get('/prodi/:id', [ProdiController::class, 'show'], [AuthMiddleware::class]);
$router->post('/prodi', [ProdiController::class, 'create'], [AuthMiddleware::class]);
$router->put('/prodi/:id', [ProdiController::class, 'update'], [AuthMiddleware::class]);
$router->delete('/prodi/:id', [ProdiController::class, 'delete'], [AuthMiddleware::class]);

// Prodi Queries
$router->get('/prodi/jenjang/:jenjang', [ProdiController::class, 'getByJenjang'], [AuthMiddleware::class]);
$router->get('/fakultas/:id/prodi', [ProdiController::class, 'getByFakultas'], [AuthMiddleware::class]);

// Prodi Statistics
$router->get('/prodi/statistics', [ProdiController::class, 'getStatistics'], [AuthMiddleware::class]);
$router->get('/prodi/statistics/fakultas', [ProdiController::class, 'getStatisticsByFakultas'], [AuthMiddleware::class]);
$router->get('/prodi/statistics/jenjang', [ProdiController::class, 'getStatisticsByJenjang'], [AuthMiddleware::class]);

// Prodi Related Data (already defined in other sections)
// GET /prodi/:id/dosen - already defined in DOSEN section
// GET /prodi/:id/mahasiswa - already defined in MAHASISWA section

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
