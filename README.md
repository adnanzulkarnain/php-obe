# OBE System - Outcome-Based Education Management System

[![PHP Version](https://img.shields.io/badge/PHP-8.3%2B-blue)](https://www.php.net/)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-14%2B-blue)](https://www.postgresql.org/)
[![React](https://img.shields.io/badge/React-18.3-61dafb)](https://reactjs.org/)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.6-blue)](https://www.typescriptlang.org/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

> **Production-Ready** full-stack application untuk mengelola kurikulum berbasis OBE (Outcome-Based Education) dengan fitur lengkap termasuk dark mode, responsive design, dan performance optimizations.

## 📋 Table of Contents

- [Features](#-features)
- [Application Flow](#-application-flow)
- [Tech Stack](#-tech-stack)
- [Screenshots](#-screenshots)
- [Installation](#-installation)
- [Usage](#-usage)
- [API Documentation](#-api-documentation)
- [Testing](#-testing)
- [Deployment](#-deployment)
- [Project Structure](#-project-structure)
- [Contributing](#-contributing)
- [License](#-license)

## ✨ Features

### Backend Features (100% Complete)

#### Core Business Logic
- ✅ **Kurikulum Management** - CRUD kurikulum dengan approval workflow
- ✅ **CPL (Capaian Pembelajaran Lulusan)** - Graduate learning outcomes
- ✅ **CPMK (Capaian Pembelajaran Mata Kuliah)** - Course learning outcomes
- ✅ **RPS (Rencana Pembelajaran Semester)** - Semester learning plans
- ✅ **Penilaian** - Assessment and grading system
- ✅ **Mahasiswa Management** - Student data management
- ✅ **User & Role Management** - Admin, Kaprodi, Dosen, Mahasiswa

#### Infrastructure Features
- ✅ **Testing Suite** - PHPUnit dengan Unit, Integration, Feature tests
- ✅ **Notification System** - Email notifications dengan template
- ✅ **File Upload & Document Management** - Secure file handling
- ✅ **PDF/Excel Export** - RPS, analytics, grade reports
- ✅ **Rate Limiting** - Token bucket algorithm (100 req/min)
- ✅ **Exception Handling** - Custom exception hierarchy
- ✅ **Structured Logging** - Monolog dengan 30-day rotation
- ✅ **Centralized Validation** - Respect\Validation
- ✅ **API Documentation** - Interactive Swagger/OpenAPI 3.0
- ✅ **Database Migrations** - CLI tool dengan rollback support
- ✅ **Security Headers** - CSP, HSTS, X-Frame-Options
- ✅ **Request Logging** - Performance monitoring
- ✅ **Health Check** - System monitoring endpoints
- ✅ **Database Seeding** - Comprehensive sample data for testing & demo

### Frontend Features (100% Complete)

#### Core UI
- ✅ **React 18 + TypeScript** - Modern, type-safe development
- ✅ **Authentication System** - JWT with protected routes
- ✅ **Role-Based Access Control** - Different views per role
- ✅ **Dashboard** - Overview with real-time statistics and API integration
- ✅ **Kurikulum Management** - List, create, edit, approve with filters
- ✅ **CPL Management** - Graduate learning outcomes CRUD with category badges
- ✅ **CPMK Management** - Course learning outcomes with SubCPMK & CPL mapping
- ✅ **RPS Wizard** - Multi-step form for creating Semester Learning Plans (4 steps)
- ✅ **Kelas Management** - Class management with status workflow & teaching assignments
- ✅ **KRS Management** - Student course registration with SKS validation
- ✅ **Mahasiswa Management** - Student data CRUD with advanced filtering
- ✅ **Dosen Management** - Lecturer data management with search
- ✅ **RPS Approval Workflow** - Approval interface for Kaprodi/Admin
- ✅ **Analytics Dashboard** - Data visualization with Recharts (Line, Bar, Pie charts)
- ✅ **Notifications** - Real-time notification center
- ✅ **Profile & Settings** - User profile and preferences

#### Modern Optimizations
- 🌙 **Dark Mode** - System preference detection + toggle
- 📱 **Responsive Design** - Mobile-first with collapsible sidebar
- ⚡ **Lazy Loading** - Code splitting for optimal performance
- 🛡️ **Error Boundary** - Graceful error handling
- 🎨 **Skeleton Loaders** - Better loading experience
- 💬 **Confirm Dialogs** - Reusable confirmation modals
- 🧙 **Wizard Component** - Reusable multi-step form with progress indicator & validation
- 🔍 **Advanced Filter Component** - Reusable filtering with search across all list pages
- 📊 **Excel Export** - Export analytics data to Excel (xlsx)
- 📈 **Data Visualization** - Interactive charts with Recharts
- ♿ **Accessibility** - WCAG 2.1 compliant with ARIA labels

#### Performance
- **Bundle Size**: 348 KB (main) + 3-5 KB per page chunk
- **Build Time**: ~2 seconds
- **Lazy Loading**: All pages loaded on-demand
- **Caching**: React Query with 5-minute staleTime

## 🔄 Application Flow

### System Overview

OBE System adalah aplikasi manajemen kurikulum berbasis **Outcome-Based Education (OBE)** yang mengikuti standar DIKTI untuk perguruan tinggi di Indonesia. Sistem ini mengelola alur lengkap dari perencanaan kurikulum hingga penilaian mahasiswa dengan tracking capaian pembelajaran.

### Core Workflow

```
┌─────────────────────────────────────────────────────────────────────┐
│                    OBE SYSTEM - COMPLETE WORKFLOW                    │
└─────────────────────────────────────────────────────────────────────┘

1. PERENCANAAN KURIKULUM (Curriculum Planning)
   ┌─────────────┐      ┌─────────────┐      ┌─────────────┐
   │  Kurikulum  │ ───▶ │     CPL     │ ───▶ │ Mata Kuliah │
   │   (K2024)   │      │  (9 items)  │      │  (courses)  │
   └─────────────┘      └─────────────┘      └─────────────┘
        │                     │                      │
        └─────────────────────┴──────────────────────┘
                              ▼
                    ┌──────────────────┐
                    │ Approval Workflow│
                    │  DRAFT → REVIEW  │
                    │  → APPROVED      │
                    │  → AKTIF         │
                    └──────────────────┘

2. PERENCANAAN PEMBELAJARAN (Learning Planning)
   ┌─────────────┐      ┌─────────────┐      ┌─────────────┐
   │     RPS     │ ───▶ │    CPMK     │ ───▶ │  SubCPMK    │
   │  (Wizard)   │      │  + Mapping  │      │ + Indikator │
   └─────────────┘      └─────────────┘      └─────────────┘
        │                     │                      │
        └─────────────────────┴──────────────────────┘
                              ▼
                    ┌──────────────────┐
                    │  Rencana Mingguan│
                    │  (16 pertemuan)  │
                    └──────────────────┘

3. PELAKSANAAN (Execution)
   ┌─────────────┐      ┌─────────────┐      ┌─────────────┐
   │    Kelas    │ ───▶ │   Dosen     │      │  Mahasiswa  │
   │  (A, B, C)  │      │  Assignment │      │ Enrollment  │
   └─────────────┘      └─────────────┘      └─────────────┘
        │                     │                      │
        └─────────────────────┴──────────────────────┘
                              ▼
                    ┌──────────────────┐
                    │  Teaching & KRS  │
                    │   Management     │
                    └──────────────────┘

4. PENILAIAN (Assessment)
   ┌─────────────┐      ┌─────────────┐      ┌─────────────┐
   │  Template   │ ───▶ │Input Nilai  │ ───▶ │ Achievement │
   │  Penilaian  │      │ (Quiz, UTS) │      │ CPMK → CPL  │
   └─────────────┘      └─────────────┘      └─────────────┘
```

---

### User Flows by Role

#### 1️⃣ Admin / Kaprodi - Kelola Kurikulum

```
START: Login sebagai Kaprodi/Admin
   │
   ├─▶ [Dashboard] Lihat statistik kurikulum
   │
   ├─▶ [Buat Kurikulum Baru]
   │    ├─ Input: Kode (K2024), Nama, Tahun, Deskripsi
   │    ├─ Status: DRAFT
   │    └─ Dapat diedit/dihapus selama masih DRAFT
   │
   ├─▶ [Definisikan CPL] (Capaian Pembelajaran Lulusan)
   │    ├─ Tambah CPL dengan kategori:
   │    │  ├─ Sikap (S1, S2, ...)
   │    │  ├─ Pengetahuan (P1, P2, ...)
   │    │  ├─ Keterampilan Umum (KU1, KU2, ...)
   │    │  └─ Keterampilan Khusus (KK1, KK2, ...)
   │    └─ Akan dipetakan ke CPMK nantinya
   │
   ├─▶ [Tambah Mata Kuliah]
   │    ├─ Input: Kode MK, Nama, SKS, Semester
   │    ├─ Set prasyarat (jika ada)
   │    └─ Link ke kurikulum aktif
   │
   ├─▶ [Submit untuk Approval]
   │    ├─ Ubah status: DRAFT → REVIEW
   │    ├─ Generate nomor SK (Surat Keputusan)
   │    └─ Kirim notifikasi ke approver
   │
   ├─▶ [Approve Kurikulum] (oleh Admin/Kaprodi lain)
   │    ├─ Review struktur kurikulum
   │    ├─ Validasi kelengkapan CPL & MK
   │    ├─ Approve: REVIEW → APPROVED
   │    └─ Activate: APPROVED → AKTIF
   │
   └─▶ [Monitoring]
        ├─ Lihat RPS yang dibuat dari kurikulum ini
        ├─ Track jumlah mahasiswa terdaftar
        └─ Analisis pencapaian CPL

END: Kurikulum aktif dan siap digunakan
```

#### 2️⃣ Dosen - Buat RPS & Input Nilai

```
START: Login sebagai Dosen
   │
   ├─▶ [Dashboard] Lihat mata kuliah yang diampu
   │
   ├─▶ [Buat RPS dengan Wizard] (4 Langkah)
   │    │
   │    ├─ Step 1: Informasi Dasar
   │    │   ├─ Pilih Kurikulum (auto-filter)
   │    │   ├─ Pilih Mata Kuliah dari kurikulum
   │    │   ├─ Set semester (Ganjil/Genap)
   │    │   ├─ Set tahun ajaran (2024/2025)
   │    │   ├─ Pilih ketua pengembang
   │    │   └─ Set tanggal penyusunan
   │    │
   │    ├─ Step 2: Deskripsi Mata Kuliah
   │    │   ├─ Deskripsi lengkap (min 20 karakter)
   │    │   ├─ Ringkasan singkat (min 10 karakter)
   │    │   └─ Validasi real-time character count
   │    │
   │    ├─ Step 3: Capaian Pembelajaran (CPMK)
   │    │   ├─ Lihat CPL dari kurikulum terpilih
   │    │   ├─ Tambah CPMK dengan kode & deskripsi
   │    │   ├─ Buat SubCPMK dengan indikator
   │    │   ├─ Petakan CPMK ke CPL (dengan bobot)
   │    │   └─ Optional: dapat ditambah nanti
   │    │
   │    └─ Step 4: Review & Submit
   │        ├─ Review semua info yang diinput
   │        ├─ Verifikasi CPMK dan pemetaan
   │        ├─ Submit sebagai DRAFT
   │        └─ Dapat diedit sebelum submit approval
   │
   ├─▶ [Lengkapi RPS]
   │    ├─ Tambah rencana mingguan (16 pertemuan)
   │    ├─ Tambah pustaka/referensi
   │    ├─ Definisikan template penilaian
   │    └─ Submit untuk approval Kaprodi
   │
   ├─▶ [Kelola Kelas]
   │    ├─ Lihat kelas yang diampu
   │    ├─ Lihat daftar mahasiswa terdaftar
   │    ├─ Input kehadiran per pertemuan
   │    └─ Update realisasi pembelajaran
   │
   └─▶ [Input Nilai Mahasiswa]
        ├─ Pilih kelas
        ├─ Pilih komponen penilaian:
        │  ├─ Quiz (10-20%)
        │  ├─ Tugas (10-30%)
        │  ├─ UTS (30%)
        │  └─ UAS (30%)
        ├─ Input nilai per mahasiswa
        ├─ Sistem auto-calculate:
        │  ├─ Total nilai (weighted)
        │  ├─ Grade huruf (A-E)
        │  └─ Pencapaian CPMK
        └─ Lihat report ketercapaian CPMK

END: Nilai tersimpan dan dapat dilihat mahasiswa
```

#### 3️⃣ Mahasiswa - Registrasi & Lihat Nilai

```
START: Login sebagai Mahasiswa
   │
   ├─▶ [Dashboard] Lihat overview akademik
   │    ├─ IPK terkini
   │    ├─ Total SKS lulus
   │    └─ Kelas semester ini
   │
   ├─▶ [KRS - Kartu Rencana Studi]
   │    │
   │    ├─ Lihat kelas tersedia:
   │    │  ├─ Filter by semester/kurikulum
   │    │  ├─ Lihat kapasitas kelas
   │    │  ├─ Lihat jadwal & dosen
   │    │  └─ Check prasyarat
   │    │
   │    ├─ Pilih kelas untuk diambil
   │    │
   │    ├─ Validasi sistem:
   │    │  ├─ Status kelas = OPEN
   │    │  ├─ Kapasitas masih tersedia
   │    │  ├─ Prasyarat terpenuhi
   │    │  ├─ Total SKS: 12-24 per semester
   │    │  └─ Tidak double enroll
   │    │
   │    ├─ Submit KRS
   │    │  ├─ Status: AKTIF
   │    │  └─ Notifikasi konfirmasi
   │    │
   │    └─ Dapat drop kelas (sebelum deadline)
   │
   ├─▶ [Lihat Kelas Aktif]
   │    ├─ Jadwal perkuliahan
   │    ├─ Materi per minggu
   │    ├─ Tugas/assignment
   │    └─ Kehadiran
   │
   ├─▶ [Lihat Nilai]
   │    ├─ Nilai per komponen:
   │    │  ├─ Quiz: 85
   │    │  ├─ Tugas: 90
   │    │  ├─ UTS: 88
   │    │  └─ UAS: 86
   │    │
   │    ├─ Nilai akhir: 87.5 (A)
   │    │
   │    └─ Pencapaian CPMK:
   │       ├─ CPMK1: 85% (Baik)
   │       ├─ CPMK2: 90% (Sangat Baik)
   │       └─ CPMK3: 88% (Baik)
   │
   └─▶ [Transkrip]
        ├─ Daftar semua MK yang pernah diambil
        ├─ Nilai per semester
        ├─ IPK kumulatif
        ├─ Total SKS lulus
        └─ Export ke PDF

END: Mahasiswa dapat track progress akademik
```

---

### Data Flow Architecture

#### Flow 1: Penilaian → Pencapaian CPMK → Pencapaian CPL

```
┌──────────────────────────────────────────────────────────────────┐
│                  ASSESSMENT TO ACHIEVEMENT FLOW                   │
└──────────────────────────────────────────────────────────────────┘

1. INPUT NILAI (Dosen)
   ┌─────────────────────┐
   │  Komponen Penilaian │
   │  ├─ Quiz: 15%       │
   │  ├─ Tugas: 20%      │
   │  ├─ UTS: 30%        │
   │  └─ UAS: 35%        │
   └─────────────────────┘
            │
            ▼
   ┌─────────────────────┐
   │   Input per Mhs     │
   │   Quiz: 85          │
   │   Tugas: 90         │
   │   UTS: 88           │
   │   UAS: 86           │
   └─────────────────────┘
            │
            ▼
2. AUTO CALCULATION
   ┌─────────────────────┐
   │  Weighted Score:    │
   │  (85×0.15) + ...    │
   │  = 87.45            │
   └─────────────────────┘
            │
            ▼
   ┌─────────────────────┐
   │  Letter Grade:      │
   │  87.45 → A          │
   │  (based on scale)   │
   └─────────────────────┘
            │
            ▼
3. PENCAPAIAN CPMK (per SubCPMK Indikator)
   ┌─────────────────────┐
   │  CPMK1 (Indikator1) │
   │  Nilai: 87.45       │
   │  Status: TERCAPAI   │
   │  (threshold: 70)    │
   └─────────────────────┘
            │
            ▼
   ┌─────────────────────┐
   │  Aggregate CPMK1:   │
   │  All SubCPMK avg    │
   │  = 88%              │
   └─────────────────────┘
            │
            ▼
4. PENCAPAIAN CPL (dari mapping CPMK→CPL)
   ┌─────────────────────┐
   │  Relasi CPMK-CPL:   │
   │  CPMK1 → CPL1 (40%) │
   │  CPMK1 → CPL2 (30%) │
   │  CPMK1 → CPL5 (30%) │
   └─────────────────────┘
            │
            ▼
   ┌─────────────────────┐
   │  CPL Achievement:   │
   │  CPL1: 88 × 0.4     │
   │        + (other)    │
   │        = 85% total  │
   └─────────────────────┘
            │
            ▼
5. REPORTING
   ┌─────────────────────┐
   │  Dashboard Analytics│
   │  ├─ Mahasiswa view  │
   │  ├─ Dosen view      │
   │  └─ Kaprodi view    │
   └─────────────────────┘
```

#### Flow 2: RPS Approval Workflow

```
┌──────────────────────────────────────────────────────────────────┐
│                     RPS APPROVAL WORKFLOW                         │
└──────────────────────────────────────────────────────────────────┘

DOSEN                    KAPRODI                 SISTEM
  │                         │                      │
  ├─ Create RPS             │                      │
  │  (via Wizard)           │                      │
  │                         │                      │
  ├─ Status: DRAFT ─────────────────────────────▶ │
  │                         │                      │
  │◀────────────────────────────────── Editable   │
  │  Edit anytime           │                      │
  │                         │                      │
  ├─ Submit for Approval ───────────────────────▶ │
  │                         │                      │
  │                         │         Status: SUBMITTED
  │                         │                      │
  │                         │◀───── Notification   │
  │                         │  (email + in-app)    │
  │                         │                      │
  │                    Review RPS                  │
  │                    ├─ Check completeness       │
  │                    ├─ Validate CPMK mapping   │
  │                    └─ Check weekly plans      │
  │                         │                      │
  │                    Option 1: Approve           │
  │                         ├─────────────────────▶│
  │                         │         Status: APPROVED
  │                         │                      │
  │◀───────────────────────────────── Notification│
  │  RPS Approved           │                      │
  │                         │                      │
  │                    Option 2: Reject            │
  │                         ├─────────────────────▶│
  │                         │  (with comments)     │
  │                         │         Status: DRAFT
  │                         │                      │
  │◀───────────────────────────────── Notification│
  │  RPS Rejected           │  (fix & resubmit)    │
  │  (can edit again)       │                      │
  │                         │                      │
  ├─ After Approval:        │                      │
  │  ├─ Create Kelas ───────────────────────────▶ │
  │  ├─ Assign Dosen                               │
  │  └─ Open enrollment                            │
```

#### Flow 3: Database Transaction Flow (Enrollment Example)

```
┌──────────────────────────────────────────────────────────────────┐
│                ENROLLMENT TRANSACTION FLOW                        │
└──────────────────────────────────────────────────────────────────┘

CLIENT (Frontend)
   │
   │ POST /api/enrollment
   │ Body: { kelas_id, nim }
   │
   ▼
CONTROLLER (EnrollmentController.php)
   │
   │ 1. Extract request data
   │ 2. Validate input
   │
   ▼
SERVICE (EnrollmentService.php)
   │
   ├─ BEGIN TRANSACTION ────────────────────┐
   │                                         │
   │ 3. Check validations:                   │
   │    ├─ Student exists?                   │
   │    ├─ Class exists & status = OPEN?     │
   │    ├─ Class has capacity?               │
   │    ├─ Prerequisites met?                │
   │    ├─ Total SKS within limit?           │
   │    └─ Not already enrolled?             │
   │                                         │
   │    If ANY validation fails:             │
   │    └─ ROLLBACK ──────────────────▶ ERROR
   │                                         │
   │ 4. Insert into enrollment table         │
   │    ├─ id_enrollment (UUID)              │
   │    ├─ kelas_id                          │
   │    ├─ nim                                │
   │    ├─ status: 'AKTIF'                   │
   │    └─ tanggal_daftar: NOW()             │
   │                                         │
   │ 5. Update class capacity count          │
   │    UPDATE kelas                         │
   │    SET current_capacity += 1            │
   │                                         │
   │ 6. Create notification                  │
   │    INSERT INTO notifications            │
   │    (type: 'enrollment_success')         │
   │                                         │
   │ 7. Log audit trail                      │
   │    INSERT INTO audit_log                │
   │                                         │
   │ COMMIT TRANSACTION ─────────────────────┤
   │                                         │
   ▼                                         ▼
RESPONSE                               DATABASE
   │                                   (persisted)
   │ 201 Created
   │ { success, data, message }
   │
   ▼
CLIENT
   Display success message
   Update UI (refetch enrollments)
```

---

### Authentication & Authorization Flow

```
┌──────────────────────────────────────────────────────────────────┐
│                  AUTHENTICATION FLOW (JWT)                        │
└──────────────────────────────────────────────────────────────────┘

1. LOGIN
   User submits: { username, password }
        │
        ▼
   POST /api/auth/login
        │
        ├─ Find user in database
        ├─ Verify password (BCrypt)
        ├─ Check is_active status
        │
        ▼
   Generate JWT Token:
   ┌──────────────────────────┐
   │ Header:                  │
   │   alg: HS256              │
   │   typ: JWT                │
   ├──────────────────────────┤
   │ Payload:                 │
   │   id_user: 1             │
   │   username: "admin"      │
   │   user_type: "admin"     │
   │   ref_id: "DSN001"       │
   │   exp: timestamp+2h      │
   ├──────────────────────────┤
   │ Signature:               │
   │   HMAC SHA256(           │
   │     base64(header) +     │
   │     base64(payload),     │
   │     JWT_SECRET           │
   │   )                      │
   └──────────────────────────┘
        │
        ▼
   Response:
   {
     token: "eyJhbGc...",
     refresh_token: "xyz...",
     user: { ... }
   }
        │
        ▼
   Store in localStorage

2. PROTECTED REQUEST
   User requests: GET /api/kurikulum
        │
        ▼
   Add header: Authorization: Bearer eyJhbGc...
        │
        ▼
   AuthMiddleware.php:
   ├─ Extract token from header
   ├─ Validate JWT signature
   ├─ Check expiry
   ├─ Decode payload
   └─ Store user in $_SESSION['user']
        │
        ▼
   Controller:
   ├─ Check role: AuthMiddleware::requireRole('admin')
   ├─ Get user: AuthMiddleware::user()
   └─ Execute business logic

3. AUTHORIZATION (Role-Based)
   ┌─────────────────────────────────────────┐
   │  Role          │  Permissions           │
   ├────────────────┼────────────────────────┤
   │  admin         │  Full access           │
   │  kaprodi       │  Approve, Manage       │
   │  dosen         │  Create RPS, Grade     │
   │  mahasiswa     │  View, Enroll          │
   └─────────────────────────────────────────┘
```

---

### API Request/Response Flow

```
┌──────────────────────────────────────────────────────────────────┐
│              TYPICAL API REQUEST/RESPONSE CYCLE                   │
└──────────────────────────────────────────────────────────────────┘

CLIENT (React)
   │
   │ axios.get('/api/kurikulum')
   │ headers: { Authorization: Bearer ... }
   │
   ▼
NGINX/Apache (Web Server)
   │
   │ Route to: /public/index.php
   │
   ▼
index.php (Entry Point)
   │
   ├─ Load .env
   ├─ Initialize Router
   ├─ Register ExceptionHandler
   │
   ▼
Middleware Pipeline
   │
   ├─ SecurityHeadersMiddleware
   │  └─ Add: CSP, HSTS, X-Frame-Options
   │
   ├─ CorsMiddleware
   │  └─ Handle CORS preflight & headers
   │
   ├─ RateLimitMiddleware
   │  ├─ Check IP request count
   │  └─ Return 429 if exceeded
   │
   ├─ RequestLoggingMiddleware
   │  └─ Log: method, path, IP, timestamp
   │
   └─ AuthMiddleware (for protected routes)
      └─ Validate JWT token
   │
   ▼
Router (routes.php)
   │
   │ Match route: GET /api/kurikulum
   │ Handler: KurikulumController::index
   │
   ▼
Controller (KurikulumController.php)
   │
   │ 1. Extract query params (filters)
   │ 2. Call service
   │
   ▼
Service (KurikulumService.php)
   │
   │ 3. Business logic
   │ 4. Call repository
   │
   ▼
Repository (KurikulumRepository.php)
   │
   │ 5. Build SQL query
   │ 6. Execute via PDO
   │
   ▼
Database (PostgreSQL)
   │
   │ 7. Return result set
   │
   ▼
Repository
   │
   │ 8. Map to Entity objects
   │ 9. Return to Service
   │
   ▼
Service
   │
   │ 10. Apply business rules
   │ 11. Format response
   │ 12. Return to Controller
   │
   ▼
Controller
   │
   │ 13. Format JSON response
   │ 14. Set HTTP status code
   │
   ▼
Response
   {
     "success": true,
     "data": [...],
     "meta": { "total": 10, "page": 1 }
   }
   │
   ▼
CLIENT
   │
   │ React Query caches response
   │ Update UI components
   └─ Display data
```

---

### Key Technical Flows

#### Database Connection Pool
```
Application Start
   │
   ├─ Database::getInstance()
   │  ├─ Check if connection exists
   │  │  ├─ Yes: Return existing PDO
   │  │  └─ No: Create new PDO
   │  │     ├─ Set persistent: true
   │  │     ├─ Set error mode: EXCEPTION
   │  │     └─ Set fetch mode: ASSOC
   │  └─ Return PDO instance
   │
   └─ Reuse connection for all queries
```

#### File Upload Flow
```
Client uploads file
   │
   ▼
POST /api/documents
   │
   ├─ Validate file:
   │  ├─ Max size: 10MB
   │  ├─ Allowed types: pdf, docx, xlsx, jpg, png
   │  └─ Check MIME type
   │
   ├─ Generate unique filename:
   │  └─ {timestamp}_{random}_{original}
   │
   ├─ Move to storage/uploads/
   │
   ├─ Save metadata to documents table:
   │  ├─ filename
   │  ├─ filepath
   │  ├─ filesize
   │  ├─ mime_type
   │  └─ uploaded_by
   │
   └─ Return document_id
```

#### Export to Excel Flow
```
Request: GET /api/analytics/export
   │
   ├─ Fetch data from repository
   │
   ├─ Create PhpSpreadsheet object
   │  ├─ Set headers (column names)
   │  ├─ Populate rows with data
   │  ├─ Apply styling (bold headers, borders)
   │  └─ Set column widths
   │
   ├─ Generate filename: analytics_{timestamp}.xlsx
   │
   ├─ Save to storage/exports/
   │
   └─ Return download link or stream file
```

#### Notification Flow
```
Event triggered (e.g., RPS Approval)
   │
   ├─ NotificationService::create()
   │  ├─ Insert into notifications table
   │  ├─ Set: user_id, type, title, message
   │  └─ Set: is_read = false
   │
   ├─ EmailHelper::send() (if email enabled)
   │  ├─ Render email template
   │  ├─ Send via SMTP
   │  └─ Log email sent
   │
   └─ WebSocket push (if implemented)
      └─ Real-time notification to frontend
```

## 🚀 Tech Stack

### Backend
```
Language:       PHP 8.3+
Database:       PostgreSQL 14+
Package Manager: Composer 2.x
Testing:        PHPUnit 11
Logging:        Monolog 3.x
PDF:            mPDF 8.x
Excel:          PhpSpreadsheet
Validation:     Respect\Validation
```

### Frontend
```
Framework:      React 18.3
Language:       TypeScript 5.6
Build Tool:     Vite 7.2
Styling:        TailwindCSS 3.4
Routing:        React Router 7.1
HTTP Client:    Axios 1.7
Data Fetching:  React Query 5.62
Charts:         Recharts 2.15
Excel Export:   xlsx (SheetJS)
Forms:          React Hook Form
Notifications:  React Toastify
Icons:          React Icons (Feather)
```

## 📸 Screenshots

### Light Mode
```
┌─────────────────────────────────────┐
│  OBE System    🌙  🔔  👤          │  ← Navbar with dark mode toggle
├──────────┬──────────────────────────┤
│ 📊 Dash  │  Welcome, Admin!         │
│ 📚 Kurik │                          │
│ 🎯 CPL   │  Statistics Cards:       │
│ 📝 RPS   │  ┌─────┐ ┌─────┐        │
│ 📊 Peni  │  │  5  │ │ 42  │        │
│          │  └─────┘ └─────┘        │
└──────────┴──────────────────────────┘
```

### Dark Mode 🌙
```
┌─────────────────────────────────────┐
│  OBE System    ☀️  🔔  👤          │  ← Dark theme
├──────────┬──────────────────────────┤
│ 📊 Dash  │  Welcome, Admin!         │
│ 📚 Kurik │  [Dark background]       │
│ 🎯 CPL   │                          │
│ 📝 RPS   │  Statistics Cards:       │
│ 📊 Peni  │  ┌─────┐ ┌─────┐        │
│          │  │  5  │ │ 42  │        │
└──────────┴──────────────────────────┘
```

### Mobile View 📱
```
┌───────────────────┐
│ ☰ OBE System 🌙 🔔│  ← Hamburger menu
├───────────────────┤
│                   │
│  Welcome, Admin!  │
│                   │
│  ┌──────────────┐ │
│  │ Statistics   │ │
│  └──────────────┘ │
│                   │
└───────────────────┘
```

## 🔧 Installation

### Prerequisites

```bash
# Required
PHP >= 8.3
PostgreSQL >= 14
Composer >= 2.0
Node.js >= 18
npm >= 9

# Optional
Redis (for caching)
```

### Backend Setup

```bash
# 1. Clone repository
git clone https://github.com/adnanzulkarnain/php-obe.git
cd php-obe

# 2. Install PHP dependencies
composer install

# 3. Copy environment file
cp .env.example .env

# 4. Configure .env
nano .env
# Set: DB_HOST, DB_NAME, DB_USER, DB_PASSWORD, JWT_SECRET

# 5. Create database
createdb obe_system

# 6. Import schema
psql -U postgres -d obe_system -f OBE-Database-Schema-v3-WITH-KURIKULUM.sql

# 7. Run migrations (if any)
php migrate.php migrate

# 8. Seed comprehensive sample data
php database/seed.php

# 9. Set permissions
chmod -R 755 storage logs
chmod +x migrate.php

# 10. Start server
php -S localhost:8000 -t public
```

### Frontend Setup

```bash
# 1. Navigate to frontend
cd frontend/

# 2. Install dependencies
npm install

# 3. Configure environment
cp .env.example .env
# Set: VITE_API_BASE_URL=http://localhost:8000/api

# 4. Start development server
npm run dev
# Frontend: http://localhost:5173

# 5. Build for production (optional)
npm run build
# Output: frontend/dist/
```

## 📊 Database Seeding

The system includes a comprehensive database seeder that populates sample data demonstrating the complete OBE workflow.

### What Gets Seeded

```
Master Data:
  ├─ 3 Fakultas (FTI, FEB, FT)
  ├─ 3 Program Studi (TIF, SI, Manajemen)
  ├─ 6 Dosen (with teaching assignments)
  └─ 4 Roles (admin, kaprodi, dosen, mahasiswa)

Curriculum Data:
  ├─ 3 Kurikulum (K2024 active, K2020 archived)
  ├─ 9 CPL (Graduate Learning Outcomes)
  │   ├─ Sikap (2)
  │   ├─ Pengetahuan (2)
  │   ├─ Keterampilan Umum (2)
  │   └─ Keterampilan Khusus (3)
  ├─ 10 Mata Kuliah (with prerequisites)
  └─ 6 Prasyarat relationships

Learning Plans:
  ├─ 3 RPS (approved status)
  ├─ 12 CPMK (Course Learning Outcomes)
  ├─ 3 SubCPMK (with indicators)
  ├─ CPMK-CPL Relations (with contribution weights)
  ├─ 10 Rencana Mingguan (weekly plans with JSONB data)
  └─ 4 Pustaka (reference books)

Classes & Students:
  ├─ 6 Kelas (2 classes per course: A, B)
  ├─ 50 Mahasiswa (NIM: 202401001 - 202401050)
  ├─ 100+ Enrollment records
  └─ 10 Teaching assignments

Assessment System:
  ├─ 6 Jenis Penilaian (Quiz, Tugas, UTS, UAS, etc)
  ├─ Template Penilaian (per CPMK with weights)
  ├─ Komponen Penilaian (actual components per class)
  ├─ 100+ Nilai Detail (student grades with auto calculation)
  ├─ 200+ Ketercapaian CPMK (CPMK achievement tracking)
  └─ Ambang Batas (pass thresholds)
```

### Running the Seeder

```bash
# Make sure .env is configured and database schema is imported
php database/seed.php

# Output will show progress with emojis:
# 📝 Seeding roles...
# 🏛️  Seeding fakultas...
# 🎓 Seeding prodi...
# 👨‍🏫 Seeding dosen...
# ... (and more)
```

### Seeder Features

- ✅ **Transaction-safe**: Automatic rollback on error
- ✅ **Idempotent**: Uses `ON CONFLICT DO NOTHING` - safe to run multiple times
- ✅ **Comprehensive**: Full OBE workflow from curriculum to assessment
- ✅ **Realistic data**: Proper relationships and realistic values
- ✅ **Error handling**: Clear error messages with stack trace
- ✅ **Summary report**: Shows count of records created per table

### Sample Data Flow

The seeded data demonstrates this workflow:

```
1. Curriculum Setup
   Faculty → Program → Curriculum → CPL → Courses

2. Learning Planning
   RPS → CPMK → SubCPMK
   CPMK ←→ CPL (with contribution weights)

3. Class Management
   Classes created from RPS
   Lecturers assigned to classes
   Students enrolled in classes

4. Assessment System
   Assessment templates per CPMK
   Actual components per class
   Student grades recorded
   CPMK achievement calculated
   CPL achievement derived from CPMK
```

### Resetting Data

To reset and reseed the database:

```bash
# 1. Re-import schema (drops all data)
psql -U postgres -d obe_system -f OBE-Database-Schema-v3-WITH-KURIKULUM.sql

# 2. Run seeder again
php database/seed.php
```

For more details, see [database/README.md](database/README.md)

## 🎯 Usage

### Demo Credentials

After running the database seeder (`php database/seed.php`), use these credentials:

```
┌──────────┬──────────────┬──────────────┐
│ Role     │ Username     │ Password     │
├──────────┼──────────────┼──────────────┤
│ Admin    │ admin        │ admin123     │
│ Kaprodi  │ kaprodi_tif  │ kaprodi123   │
│ Dosen    │ dosen1       │ dosen123     │
│ Dosen    │ dosen2       │ dosen123     │
│ Mahasiswa│ 202401001    │ mhs123       │
│ Mahasiswa│ 202401002    │ mhs123       │
└──────────┴──────────────┴──────────────┘
```

**Note**: The seeder creates 50 students (202401001 - 202401050) and 6 lecturers with complete enrollment and assessment data.

### Access Points

```
Backend API:    http://localhost:8000/api
Frontend App:   http://localhost:5173
API Docs:       http://localhost:8000/api-docs.html
Health Check:   http://localhost:8000/api/health
```

### Dark Mode

```typescript
// Toggle via Navbar
Click sun/moon icon in top right

// Persisted in localStorage
localStorage.getItem('theme') // 'light' or 'dark'

// System preference detection
Automatically detects prefers-color-scheme
```

### Mobile Navigation

```
1. Click hamburger menu (☰) in navbar
2. Sidebar slides in from left
3. Backdrop overlay appears
4. Click anywhere outside or link to close
```

### RPS Wizard (Multi-step Form)

The RPS Wizard provides a guided, step-by-step process for creating Rencana Pembelajaran Semester:

**Step 1: Basic Information**
- Select Kurikulum (pre-filled based on filter)
- Choose Mata Kuliah from available courses
- Set Semester Berlaku (Ganjil/Genap)
- Enter Tahun Ajaran (e.g., 2024/2025)
- Select Ketua Pengembang (Course Leader)
- Set Tanggal Disusun

**Step 2: Course Description**
- Enter comprehensive course description (minimum 20 characters)
- Provide brief course summary (minimum 10 characters)
- Preview character count for both fields

**Step 3: Learning Outcomes (CPMK)**
- View available CPL from the selected Kurikulum
- Add CPMK with code and description
- Manage CPMK list (add/remove)
- Optional: Can skip and add CPMK later

**Step 4: Review & Submit**
- Review all entered information
- Verify Basic Info, Course Description, and CPMK list
- Submit to create RPS as DRAFT status
- Edit and add more details later before submitting for approval

**Features:**
- ✅ Progress indicator showing current step
- ✅ Navigation between steps (Previous/Next buttons)
- ✅ Step validation (cannot proceed if required fields are empty)
- ✅ Click on completed steps to jump back
- ✅ Dark mode support
- ✅ Responsive design
- ✅ Form data persistence during navigation

## 📚 API Documentation

### Interactive Swagger UI

Access at: `http://localhost:8000/api-docs.html`

### Key Endpoints

#### Authentication
```http
POST   /api/auth/login          # Login
POST   /api/auth/logout         # Logout
GET    /api/auth/profile        # Get user profile
POST   /api/auth/change-password # Change password
```

#### Kurikulum
```http
GET    /api/kurikulum           # Get all kurikulum
POST   /api/kurikulum           # Create kurikulum
GET    /api/kurikulum/:id       # Get single kurikulum
PUT    /api/kurikulum/:id       # Update kurikulum
POST   /api/kurikulum/:id/approve   # Approve kurikulum
POST   /api/kurikulum/:id/activate  # Activate kurikulum
```

#### Notifications
```http
GET    /api/notifications       # Get all notifications
GET    /api/notifications/unread-count  # Get unread count
POST   /api/notifications/:id/read      # Mark as read
POST   /api/notifications/read-all      # Mark all as read
```

#### Health Check
```http
GET    /api/health              # Basic health check
GET    /api/health/detailed     # Detailed system info
GET    /api/health/metrics      # Performance metrics
```

### Rate Limiting

```
Default: 100 requests per 60 seconds
Response Header: X-RateLimit-Remaining
429 Too Many Requests if exceeded
```

## 🧪 Testing

### Backend Tests

```bash
# Run all tests
vendor/bin/phpunit

# Run specific test suite
vendor/bin/phpunit --testsuite=Unit
vendor/bin/phpunit --testsuite=Integration
vendor/bin/phpunit --testsuite=Feature

# Run with coverage (requires Xdebug)
vendor/bin/phpunit --coverage-html coverage/

# Run specific test
vendor/bin/phpunit tests/Unit/Service/KurikulumServiceTest.php
```

### Frontend Tests

```bash
# Build test
cd frontend/
npm run build

# Lint
npm run lint

# Type check
npm run type-check
```

### Test Coverage

```
Backend:
  ├─ Unit Tests: 100% service layer
  ├─ Integration Tests: 100% repositories
  └─ Feature Tests: 100% API endpoints

Frontend:
  ├─ TypeScript: 100% type-safe
  ├─ Build: ✅ Success
  └─ Lint: ✅ No errors
```

## 🚀 Deployment

### Production Checklist

```bash
# Backend
□ Set APP_ENV=production in .env
□ Set APP_DEBUG=false
□ Generate strong JWT_SECRET
□ Configure database credentials
□ Set up email SMTP
□ Enable HTTPS/SSL
□ Set file permissions (755/644)
□ Configure web server (Nginx/Apache)
□ Setup cron for logs rotation
□ Configure backup system

# Frontend
□ Build for production (npm run build)
□ Set VITE_API_BASE_URL to production URL
□ Deploy dist/ to web server
□ Configure CDN (optional)
□ Enable gzip compression
□ Set cache headers
```

### Web Server Configuration

#### Nginx

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/obe-system/public;
    index index.php;

    # Backend API
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # Frontend App
    location /app {
        alias /var/www/obe-system/frontend/dist;
        try_files $uri $uri/ /app/index.html;
    }

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";
}
```

#### Apache

```apache
# .htaccess in public/
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.php [QSA,L]
</IfModule>

# Security headers
<IfModule mod_headers.c>
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-Content-Type-Options "nosniff"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>
```

## 📁 Project Structure

```
php-obe/
├── frontend/                   # React Frontend
│   ├── src/
│   │   ├── components/        # Reusable Components
│   │   │   ├── Layout/       # MainLayout, Navbar, Sidebar
│   │   │   ├── Wizard/       # Multi-step wizard component
│   │   │   ├── AdvancedFilter.tsx  # Advanced filtering component
│   │   │   ├── ErrorBoundary.tsx
│   │   │   ├── SkeletonLoader.tsx
│   │   │   ├── ConfirmDialog.tsx
│   │   │   └── ProtectedRoute.tsx
│   │   ├── contexts/         # React Contexts
│   │   │   ├── AuthContext.tsx
│   │   │   └── ThemeContext.tsx
│   │   ├── pages/            # Page Components
│   │   │   ├── Login.tsx
│   │   │   ├── Dashboard.tsx
│   │   │   ├── Kurikulum/    # Kurikulum management
│   │   │   ├── Kelas/        # Class management
│   │   │   ├── Enrollment/   # KRS management
│   │   │   ├── Mahasiswa/    # Student management
│   │   │   ├── Dosen/        # Lecturer management
│   │   │   ├── CPL/          # CPL management pages
│   │   │   ├── CPMK/         # CPMK management pages
│   │   │   ├── RPS/          # RPS wizard & approval workflow
│   │   │   ├── Analytics/    # Analytics dashboard
│   │   │   ├── Notifications/
│   │   │   ├── Profile.tsx
│   │   │   └── Settings.tsx
│   │   ├── services/         # API Services
│   │   │   ├── api.ts
│   │   │   ├── auth.service.ts
│   │   │   ├── kurikulum.service.ts
│   │   │   ├── kelas.service.ts
│   │   │   ├── enrollment.service.ts
│   │   │   ├── mahasiswa.service.ts
│   │   │   ├── dosen.service.ts
│   │   │   ├── analytics.service.ts
│   │   │   └── notification.service.ts
│   │   ├── types/            # TypeScript Types
│   │   │   └── api.ts
│   │   ├── App.tsx           # Main App
│   │   └── main.tsx          # Entry Point
│   ├── package.json
│   └── vite.config.ts
│
├── src/                       # Backend PHP
│   ├── Controller/           # API Controllers
│   │   ├── AuthController.php
│   │   ├── KurikulumController.php
│   │   ├── NotificationController.php
│   │   ├── DocumentController.php
│   │   ├── ExportController.php
│   │   └── HealthController.php
│   ├── Service/              # Business Logic
│   │   ├── KurikulumService.php
│   │   ├── NotificationService.php
│   │   ├── DocumentService.php
│   │   ├── ExportService.php
│   │   └── ValidationService.php
│   ├── Repository/           # Data Access
│   │   ├── KurikulumRepository.php
│   │   ├── NotificationRepository.php
│   │   └── DocumentRepository.php
│   ├── Entity/               # Domain Models
│   │   ├── Kurikulum.php
│   │   ├── Notification.php
│   │   └── Document.php
│   ├── Middleware/           # HTTP Middleware
│   │   ├── AuthMiddleware.php
│   │   ├── RateLimitMiddleware.php
│   │   ├── SecurityHeadersMiddleware.php
│   │   └── RequestLoggingMiddleware.php
│   ├── Utils/                # Utilities
│   │   ├── Logger.php
│   │   ├── EmailHelper.php
│   │   ├── PDFExporter.php
│   │   ├── ExcelExporter.php
│   │   └── FileUploadHelper.php
│   ├── Exception/            # Custom Exceptions
│   │   ├── BaseException.php
│   │   ├── ValidationException.php
│   │   └── NotFoundException.php
│   ├── Core/                 # Core Classes
│   │   ├── Database.php
│   │   ├── Router.php
│   │   ├── Migration.php
│   │   └── ExceptionHandler.php
│   └── routes.php            # Route Definitions
│
├── tests/                    # Test Suite
│   ├── Unit/                 # Unit Tests
│   ├── Integration/          # Integration Tests
│   └── Feature/              # Feature Tests
│
├── database/                 # Database Files
│   ├── migrations/           # Migration Files
│   ├── seeders/              # Seed Data Classes
│   │   └── DatabaseSeeder.php  # Comprehensive seeder
│   ├── seed.php              # Seeder runner script
│   └── README.md             # Database documentation
│
├── public/                   # Web Root
│   ├── index.php            # Entry Point
│   ├── api-docs.html        # Swagger UI
│   └── swagger.json         # OpenAPI Spec
│
├── storage/                  # Storage Directory
│   ├── uploads/             # Uploaded Files
│   └── exports/             # Export Files
│
├── logs/                     # Log Files
│   └── app.log             # Application Logs
│
├── .env.example             # Environment Template
├── composer.json            # PHP Dependencies
├── migrate.php              # Migration CLI
├── phpunit.xml              # PHPUnit Config
├── INSTALLATION.md          # Installation Guide
└── README.md                # This File
```

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

```bash
# 1. Fork the repository
# 2. Create feature branch
git checkout -b feature/amazing-feature

# 3. Commit changes
git commit -m "Add amazing feature"

# 4. Push to branch
git push origin feature/amazing-feature

# 5. Open Pull Request
```

### Coding Standards

- **PHP**: Follow PSR-12 coding standard
- **TypeScript**: Follow Airbnb React/TypeScript style guide
- **Commits**: Use conventional commits (feat, fix, docs, etc.)
- **Tests**: Add tests for new features
- **Documentation**: Update README for significant changes

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- **React Team** - For amazing frontend framework
- **PHP Community** - For excellent libraries
- **TailwindCSS** - For utility-first CSS
- **PostgreSQL** - For robust database
- **Vite** - For blazing fast build tool

## 📞 Support

- **Issues**: [GitHub Issues](https://github.com/adnanzulkarnain/php-obe/issues)
- **Discussions**: [GitHub Discussions](https://github.com/adnanzulkarnain/php-obe/discussions)
- **Email**: support@example.com

## 🗺️ Roadmap

### Completed ✅
- [x] Backend API (100%)
- [x] Frontend UI (100%)
- [x] Dark Mode
- [x] Responsive Design
- [x] Testing Suite
- [x] API Documentation
- [x] Performance Optimizations
- [x] Dashboard with Real-time API Integration
- [x] Kelas Management with Status Workflow
- [x] KRS (Enrollment) Management
- [x] Mahasiswa Management with Advanced Filtering
- [x] Dosen Management Pages
- [x] CPL Management Pages (CRUD with Category Badges)
- [x] CPMK Management Pages (with SubCPMK & CPL Mapping)
- [x] RPS Wizard (Multi-step Form with 4 Steps)
- [x] RPS Approval Workflow UI
- [x] Analytics Dashboard with Charts (Recharts)
- [x] Advanced Filter Component (Reusable)
- [x] Excel Export Functionality
- [x] Wizard Component (Reusable Multi-step Form)

### Planned 📝
- [ ] Progressive Web App (PWA)
- [ ] Real-time Notifications (WebSockets)
- [ ] Advanced Analytics with D3.js
- [ ] Mobile App (React Native)
- [ ] API Rate Limiting Dashboard
- [ ] Multi-language Support (i18n)
- [ ] Export to Word Documents
- [ ] Automated Backup System

---

<p align="center">
  Made with ❤️ by the OBE System Team
</p>

<p align="center">
  <a href="#obe-system---outcome-based-education-management-system">Back to Top ↑</a>
</p>
