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
use App\Controller\MasterDataController;
use App\Controller\AnalyticsController;
use App\Controller\RencanaMingguanController;
use App\Controller\KehadiranController;
use App\Controller\DocumentController;
use App\Controller\NotificationController;
use App\Controller\SumberBelajarController;
use App\Controller\RPSExportController;
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
// MASTER DATA MANAGEMENT
// ============================================

// Fakultas
$router->get('/fakultas', [MasterDataController::class, 'getAllFakultas'], [AuthMiddleware::class]);
$router->get('/fakultas/:id', [MasterDataController::class, 'getFakultas'], [AuthMiddleware::class]);
$router->post('/fakultas', [MasterDataController::class, 'createFakultas'], [AuthMiddleware::class]);
$router->put('/fakultas/:id', [MasterDataController::class, 'updateFakultas'], [AuthMiddleware::class]);

// Prodi
$router->get('/prodi', [MasterDataController::class, 'getAllProdi'], [AuthMiddleware::class]);
$router->get('/prodi/:id', [MasterDataController::class, 'getProdi'], [AuthMiddleware::class]);
$router->post('/prodi', [MasterDataController::class, 'createProdi'], [AuthMiddleware::class]);
$router->put('/prodi/:id', [MasterDataController::class, 'updateProdi'], [AuthMiddleware::class]);
$router->get('/prodi/:id_prodi/mahasiswa-statistics', [MahasiswaController::class, 'getStatisticsByProdi'], [AuthMiddleware::class]);

// Dosen
$router->get('/dosen', [DosenController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dosen/:id', [DosenController::class, 'show'], [AuthMiddleware::class]);
$router->post('/dosen', [DosenController::class, 'create'], [AuthMiddleware::class]);
$router->put('/dosen/:id', [DosenController::class, 'update'], [AuthMiddleware::class]);
$router->delete('/dosen/:id', [DosenController::class, 'delete'], [AuthMiddleware::class]);
$router->get('/dosen/:id/teaching-assignments', [DosenController::class, 'getTeachingAssignments'], [AuthMiddleware::class]);

// Mahasiswa
$router->get('/mahasiswa', [MahasiswaController::class, 'index'], [AuthMiddleware::class]);
$router->get('/mahasiswa/:nim', [MahasiswaController::class, 'show'], [AuthMiddleware::class]);
$router->post('/mahasiswa', [MahasiswaController::class, 'create'], [AuthMiddleware::class]);
$router->put('/mahasiswa/:nim', [MahasiswaController::class, 'update'], [AuthMiddleware::class]);
$router->delete('/mahasiswa/:nim', [MahasiswaController::class, 'delete'], [AuthMiddleware::class]);
$router->get('/mahasiswa/angkatan/:angkatan', [MahasiswaController::class, 'getByAngkatan'], [AuthMiddleware::class]);

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

// Pustaka (Learning References)
$router->get('/rps/:id/pustaka', [SumberBelajarController::class, 'getPustakaByRPS'], [AuthMiddleware::class]);
$router->get('/pustaka/:id', [SumberBelajarController::class, 'getPustaka'], [AuthMiddleware::class]);
$router->post('/pustaka', [SumberBelajarController::class, 'createPustaka'], [AuthMiddleware::class]);
$router->put('/pustaka/:id', [SumberBelajarController::class, 'updatePustaka'], [AuthMiddleware::class]);
$router->delete('/pustaka/:id', [SumberBelajarController::class, 'deletePustaka'], [AuthMiddleware::class]);

// Media Pembelajaran (Learning Media)
$router->get('/rps/:id/media-pembelajaran', [SumberBelajarController::class, 'getMediaByRPS'], [AuthMiddleware::class]);
$router->get('/media-pembelajaran/:id', [SumberBelajarController::class, 'getMedia'], [AuthMiddleware::class]);
$router->post('/media-pembelajaran', [SumberBelajarController::class, 'createMedia'], [AuthMiddleware::class]);
$router->put('/media-pembelajaran/:id', [SumberBelajarController::class, 'updateMedia'], [AuthMiddleware::class]);
$router->delete('/media-pembelajaran/:id', [SumberBelajarController::class, 'deleteMedia'], [AuthMiddleware::class]);

// Sumber Belajar Statistics
$router->get('/rps/:id/sumber-belajar-stats', [SumberBelajarController::class, 'getStats'], [AuthMiddleware::class]);

// RPS Export
$router->get('/rps/:id/export/markdown', [RPSExportController::class, 'exportMarkdown'], [AuthMiddleware::class]);
$router->get('/rps/:id/export/html', [RPSExportController::class, 'exportHTML'], [AuthMiddleware::class]);
$router->get('/rps/:id/export/json', [RPSExportController::class, 'exportJSON'], [AuthMiddleware::class]);
$router->get('/rps/:id/preview', [RPSExportController::class, 'preview'], [AuthMiddleware::class]);

// ============================================
// RENCANA PEMBELAJARAN MINGGUAN (Weekly Learning Plan)
// ============================================

$router->get('/rps/:id/rencana-mingguan', [RencanaMingguanController::class, 'getByRPS'], [AuthMiddleware::class]);
$router->get('/rencana-mingguan/:id', [RencanaMingguanController::class, 'show'], [AuthMiddleware::class]);
$router->post('/rencana-mingguan', [RencanaMingguanController::class, 'create'], [AuthMiddleware::class]);
$router->put('/rencana-mingguan/:id', [RencanaMingguanController::class, 'update'], [AuthMiddleware::class]);
$router->delete('/rencana-mingguan/:id', [RencanaMingguanController::class, 'delete'], [AuthMiddleware::class]);
$router->post('/rps/:id/rencana-mingguan/bulk-create', [RencanaMingguanController::class, 'bulkCreate'], [AuthMiddleware::class]);
$router->get('/rps/:id/rencana-mingguan/stats', [RencanaMingguanController::class, 'getStats'], [AuthMiddleware::class]);

// ============================================
// REALISASI PERTEMUAN & KEHADIRAN (Attendance)
// ============================================

// Realisasi Pertemuan
$router->get('/kelas/:id/realisasi-pertemuan', [KehadiranController::class, 'getRealisasiByKelas'], [AuthMiddleware::class]);
$router->get('/realisasi-pertemuan/:id', [KehadiranController::class, 'getRealisasiById'], [AuthMiddleware::class]);
$router->post('/realisasi-pertemuan', [KehadiranController::class, 'createRealisasi'], [AuthMiddleware::class]);
$router->put('/realisasi-pertemuan/:id', [KehadiranController::class, 'updateRealisasi'], [AuthMiddleware::class]);
$router->delete('/realisasi-pertemuan/:id', [KehadiranController::class, 'deleteRealisasi'], [AuthMiddleware::class]);

// Kehadiran
$router->post('/realisasi-pertemuan/:id/kehadiran', [KehadiranController::class, 'inputKehadiran'], [AuthMiddleware::class]);
$router->get('/mahasiswa/:nim/kehadiran/kelas/:id_kelas', [KehadiranController::class, 'getKehadiranByMahasiswa'], [AuthMiddleware::class]);
$router->get('/kelas/:id/attendance-summary', [KehadiranController::class, 'getAttendanceSummary'], [AuthMiddleware::class]);

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

// Ketercapaian CPMK & CPL
$router->get('/enrollment/:id/ketercapaian-cpmk', [PenilaianController::class, 'getKetercapaianCPMK'], [AuthMiddleware::class]);
$router->get('/enrollment/:id/ketercapaian-cpl', [PenilaianController::class, 'getKetercapaianCPL'], [AuthMiddleware::class]);

// Finalisasi Nilai
$router->post('/enrollment/:id/finalize-grades', [PenilaianController::class, 'finalizeGrades'], [AuthMiddleware::class]);
$router->post('/kelas/:id/finalize-grades', [PenilaianController::class, 'finalizeKelasGrades'], [AuthMiddleware::class]);

// ============================================
// ANALYTICS & REPORTING
// ============================================

// Dashboard
$router->get('/analytics/dashboard', [AnalyticsController::class, 'getDashboard'], [AuthMiddleware::class]);
$router->get('/analytics/trends', [AnalyticsController::class, 'getTrends'], [AuthMiddleware::class]);

// CPMK & CPL Reports
$router->get('/analytics/kelas/:id/cpmk-report', [AnalyticsController::class, 'getCPMKReportByKelas'], [AuthMiddleware::class]);
$router->get('/analytics/kurikulum/:id/cpl-report', [AnalyticsController::class, 'getCPLReportByKurikulum'], [AuthMiddleware::class]);

// Student Performance
$router->get('/analytics/mahasiswa/:nim/performance', [AnalyticsController::class, 'getMahasiswaPerformance'], [AuthMiddleware::class]);

// ============================================
// DOCUMENT MANAGEMENT
// ============================================

$router->get('/documents/:entity_type/:entity_id', [DocumentController::class, 'getByEntity'], [AuthMiddleware::class]);
$router->get('/documents/:id', [DocumentController::class, 'show'], [AuthMiddleware::class]);
$router->post('/documents', [DocumentController::class, 'upload'], [AuthMiddleware::class]);
$router->delete('/documents/:id', [DocumentController::class, 'delete'], [AuthMiddleware::class]);
$router->get('/documents/stats/:entity_type/:entity_id', [DocumentController::class, 'getStats'], [AuthMiddleware::class]);

// ============================================
// NOTIFICATION SYSTEM
// ============================================

$router->get('/notifications', [NotificationController::class, 'index'], [AuthMiddleware::class]);
$router->get('/notifications/unread-count', [NotificationController::class, 'getUnreadCount'], [AuthMiddleware::class]);
$router->post('/notifications/:id/read', [NotificationController::class, 'markAsRead'], [AuthMiddleware::class]);
$router->post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'], [AuthMiddleware::class]);
$router->delete('/notifications/:id', [NotificationController::class, 'delete'], [AuthMiddleware::class]);

// Admin-only notification endpoints
$router->post('/notifications/create', [NotificationController::class, 'create'], [AuthMiddleware::class]);
$router->post('/notifications/broadcast', [NotificationController::class, 'broadcast'], [AuthMiddleware::class]);
$router->post('/notifications/notify-role', [NotificationController::class, 'notifyByRole'], [AuthMiddleware::class]);

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
