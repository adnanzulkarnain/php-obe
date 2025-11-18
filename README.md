# 🎓 Sistem Informasi Kurikulum OBE (Outcome-Based Education)

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue)](https://php.net)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-14%2B-blue)](https://postgresql.org)
[![Status](https://img.shields.io/badge/Status-Production%20Ready-success)](https://github.com/adnanzulkarnain/php-obe)
[![Completion](https://img.shields.io/badge/Completion-90%25-brightgreen)](https://github.com/adnanzulkarnain/php-obe)

Sistem Informasi Kurikulum OBE adalah aplikasi berbasis web untuk mengelola kurikulum dan pembelajaran berbasis capaian (Outcome-Based Education). Sistem ini dirancang khusus untuk perguruan tinggi di Indonesia yang membutuhkan pengelolaan kurikulum sesuai standar SN-DIKTI dan kebutuhan akreditasi.

## 📖 Deskripsi

Sistem ini menyediakan solusi lengkap untuk:
- Manajemen Multi-Kurikulum secara paralel
- Tracking CPL (Capaian Pembelajaran Lulusan) dan CPMK (Capaian Pembelajaran Mata Kuliah)
- Digital RPS (Rencana Pembelajaran Semester) dengan approval workflow
- Sistem penilaian dengan auto-aggregation ke CPL
- Analytics dan reporting untuk keperluan akreditasi
- Document management dan notification system

## ✨ Fitur Lengkap

### 🎯 **Manajemen Kurikulum (100%)**
- ✅ CRUD Kurikulum dengan versioning
- ✅ Multi-curriculum support (2+ kurikulum berjalan paralel)
- ✅ Workflow: draft → review → approved → aktif
- ✅ Kurikulum comparison tool
- ✅ Soft delete & archiving
- ✅ Primary curriculum designation

### 📚 **Master Data Management (100%)**
- ✅ Fakultas & Program Studi CRUD
- ✅ Dosen Management (NIDN, email validation)
- ✅ Mahasiswa Management (immutable curriculum assignment)
- ✅ Mata Kuliah Management per kurikulum
- ✅ Advanced filtering & search
- ✅ Statistics & analytics

### 📝 **RPS Management (100%)**
- ✅ CRUD RPS dengan versioning
- ✅ Multi-level approval workflow
- ✅ Rencana Pembelajaran 16 Minggu (JSONB support)
- ✅ Pustaka & Referensi (utama/pendukung)
- ✅ Media Pembelajaran (software/hardware/platform)
- ✅ **Export RPS: Markdown, HTML, JSON**
- ✅ **RPS Preview in browser**
- ✅ RPS statistics & validation

### 🎯 **CPMK & CPL Management (100%)**
- ✅ CRUD CPMK & SubCPMK
- ✅ Mapping CPMK ke CPL dengan bobot kontribusi
- ✅ Matrix pemetaan CPL-CPMK
- ✅ Validation kelengkapan RPS
- ✅ Statistics per RPS

### 💯 **Sistem Penilaian - COMPLETE (100%)**
- ✅ Template penilaian per RPS
- ✅ Komponen penilaian per kelas
- ✅ Input nilai (single & bulk)
- ✅ Auto-calculate nilai tertimbang
- ✅ **Persist CPMK achievements** ⭐ NEW!
- ✅ **Auto-aggregate CPL dari CPMK** ⭐ NEW!
- ✅ **Grade finalization system** ⭐ NEW!
- ✅ Grade conversion (A sampai E)
- ✅ Statistics & performance metrics

### 👥 **Kelas & Enrollment (100%)**
- ✅ Manajemen kelas
- ✅ Enrollment mahasiswa (KRS)
- ✅ Teaching assignments
- ✅ Capacity validation
- ✅ Curriculum compatibility check
- ✅ Transcript generation
- ✅ GPA calculation

### 📅 **Kehadiran & Realisasi Pertemuan (100%)**
- ✅ Realisasi pertemuan per kelas
- ✅ Comparison rencana vs realisasi
- ✅ Input kehadiran (bulk input)
- ✅ Attendance tracking per mahasiswa
- ✅ Attendance statistics & percentage
- ✅ Status: hadir, izin, sakit, alpha
- ✅ Kendala dan catatan dosen

### 📊 **Analytics & Reporting (100%)**
- ✅ Dashboard overview
- ✅ CPMK achievement report per kelas
- ✅ CPL achievement report per kurikulum
- ✅ Student performance tracking (complete)
- ✅ GPA calculation
- ✅ Trend analysis by tahun ajaran
- ✅ Low performance alerts
- ✅ Materialized views for fast queries

### 📁 **Document Management (100%)**
- ✅ Upload dokumen per entity (RPS, Kelas, dll)
- ✅ File metadata tracking (size, type, mime)
- ✅ Document statistics
- ✅ Multi-entity support
- ✅ Delete management

### 🔔 **Notification System (100%)**
- ✅ User notifications with read/unread
- ✅ Unread count for badge display
- ✅ Mark as read/Mark all
- ✅ **Broadcast** to multiple users
- ✅ **Notify by role** (all dosen, all mahasiswa)
- ✅ Admin notification tools

### 🔐 **Security & Audit (100%)**
- ✅ JWT Authentication
- ✅ Role-Based Access Control (RBAC)
- ✅ Audit logging (all operations)
- ✅ User activity tracking
- ✅ IP address & user agent logging
- ✅ Password hashing (bcrypt)

## 🏗️ Arsitektur

### Tech Stack
- **Backend**: PHP 8.0+
- **Database**: PostgreSQL 14+
- **Authentication**: JWT (JSON Web Tokens)
- **Architecture**: Clean Architecture
  - Repository Pattern
  - Service Layer
  - MVC Pattern

### Struktur Direktori
```
php-obe/
├── database/
│   └── migrations/                # Database migrations
│       └── 001_create_ketercapaian_cpl.sql
├── public/
│   └── index.php                  # Entry point
├── src/
│   ├── Controller/                # 23 Controllers
│   │   ├── AuthController.php
│   │   ├── KurikulumController.php
│   │   ├── CPLController.php
│   │   ├── MataKuliahController.php
│   │   ├── KelasController.php
│   │   ├── EnrollmentController.php
│   │   ├── RPSController.php
│   │   ├── CPMKController.php
│   │   ├── PenilaianController.php
│   │   ├── DosenController.php
│   │   ├── MahasiswaController.php
│   │   ├── MasterDataController.php
│   │   ├── AnalyticsController.php
│   │   ├── RencanaMingguanController.php
│   │   ├── KehadiranController.php
│   │   ├── DocumentController.php
│   │   ├── NotificationController.php
│   │   ├── SumberBelajarController.php
│   │   └── RPSExportController.php
│   ├── Core/                      # Framework core
│   │   ├── BaseRepository.php
│   │   ├── Database.php
│   │   ├── Request.php
│   │   ├── Response.php
│   │   └── Router.php
│   ├── Entity/                    # Domain models
│   ├── Middleware/                # Middleware
│   │   ├── AuthMiddleware.php
│   │   └── CorsMiddleware.php
│   ├── Repository/                # 20 Repositories
│   │   ├── KurikulumRepository.php
│   │   ├── CPLRepository.php
│   │   ├── MataKuliahRepository.php
│   │   ├── KelasRepository.php
│   │   ├── EnrollmentRepository.php
│   │   ├── RPSRepository.php
│   │   ├── CPMKRepository.php
│   │   ├── PenilaianRepository.php
│   │   ├── DosenRepository.php
│   │   ├── MahasiswaRepository.php
│   │   ├── FakultasRepository.php
│   │   ├── ProdiRepository.php
│   │   ├── RencanaMingguanRepository.php
│   │   ├── KehadiranRepository.php
│   │   ├── PustakaRepository.php
│   │   └── MediaPembelajaranRepository.php
│   ├── Service/                   # 13 Services
│   │   ├── KurikulumService.php
│   │   ├── CPLService.php
│   │   ├── MataKuliahService.php
│   │   ├── KelasService.php
│   │   ├── EnrollmentService.php
│   │   ├── RPSService.php
│   │   ├── CPMKService.php
│   │   ├── PenilaianService.php
│   │   ├── DosenService.php
│   │   ├── MahasiswaService.php
│   │   ├── RencanaMingguanService.php
│   │   ├── KehadiranService.php
│   │   ├── DocumentService.php
│   │   ├── NotificationService.php
│   │   ├── RPSExportService.php
│   │   └── AuditLogService.php
│   └── routes.php                 # Route definitions (140+ endpoints)
├── OBE-Database-Schema-v3-WITH-KURIKULUM.sql
├── .env.example
├── composer.json
└── README.md
```

## 📦 Instalasi

### Requirements
- PHP >= 8.0
- PostgreSQL >= 14
- Composer
- Extensions: `pdo_pgsql`, `mbstring`, `json`

### Setup Database

1. **Clone repository**
```bash
git clone https://github.com/adnanzulkarnain/php-obe.git
cd php-obe
```

2. **Install dependencies**
```bash
composer install
```

3. **Setup database**
```bash
# Create database
createdb obe_db

# Run main schema
psql -U postgres -d obe_db -f OBE-Database-Schema-v3-WITH-KURIKULUM.sql

# Run migrations
psql -U postgres -d obe_db -f database/migrations/001_create_ketercapaian_cpl.sql
```

4. **Configure environment**
```bash
cp .env.example .env
# Edit .env dengan database credentials
```

Example `.env`:
```env
DB_HOST=localhost
DB_PORT=5432
DB_NAME=obe_db
DB_USER=postgres
DB_PASSWORD=yourpassword

JWT_SECRET=your-secret-key-here
JWT_EXPIRY=7200

APP_ENV=development
APP_DEBUG=true
```

5. **Start development server**
```bash
php -S localhost:8000 -t public
```

Server berjalan di `http://localhost:8000`

## 🚀 API Documentation

### Base URL
```
http://localhost:8000/api
```

### Authentication
Semua endpoint (kecuali `/auth/login`) memerlukan JWT token:
```
Authorization: Bearer <your_jwt_token>
```

### Complete Endpoint List (140+)

#### 1. Authentication
```
POST   /auth/login              - Login
POST   /auth/logout             - Logout
GET    /auth/profile            - Get profile
POST   /auth/change-password    - Change password
```

#### 2. Master Data
```
# Fakultas & Prodi
GET    /fakultas                - List fakultas
GET    /fakultas/:id            - Get fakultas
POST   /fakultas                - Create fakultas
PUT    /fakultas/:id            - Update fakultas

GET    /prodi                   - List prodi
GET    /prodi/:id               - Get prodi
POST   /prodi                   - Create prodi
PUT    /prodi/:id               - Update prodi
GET    /prodi/:id_prodi/mahasiswa-statistics  - Statistics

# Dosen
GET    /dosen                   - List dosen (filters: prodi, status, search)
GET    /dosen/:id               - Get dosen
POST   /dosen                   - Create dosen
PUT    /dosen/:id               - Update dosen
DELETE /dosen/:id               - Delete dosen
GET    /dosen/:id/teaching-assignments - Teaching load

# Mahasiswa
GET    /mahasiswa               - List mahasiswa (filters: prodi, kurikulum, angkatan, status)
GET    /mahasiswa/:nim          - Get mahasiswa
POST   /mahasiswa               - Create mahasiswa
PUT    /mahasiswa/:nim          - Update mahasiswa
DELETE /mahasiswa/:nim          - Delete mahasiswa
GET    /mahasiswa/angkatan/:angkatan  - By angkatan
```

#### 3. Kurikulum & CPL
```
GET    /kurikulum               - List kurikulum
GET    /kurikulum/:id           - Get detail
POST   /kurikulum               - Create
POST   /kurikulum/:id/approve   - Approve
POST   /kurikulum/:id/activate  - Activate
POST   /kurikulum/:id/deactivate - Deactivate
GET    /kurikulum/compare       - Compare kurikulum

GET    /cpl                     - List CPL
GET    /cpl/:id                 - Get CPL
POST   /cpl                     - Create CPL
PUT    /cpl/:id                 - Update CPL
DELETE /cpl/:id                 - Delete CPL
```

#### 4. Mata Kuliah
```
GET    /matakuliah              - List mata kuliah
POST   /matakuliah              - Create MK
PUT    /matakuliah/:kode_mk/:id_kurikulum  - Update MK
DELETE /matakuliah/:kode_mk/:id_kurikulum - Delete MK
```

#### 5. RPS Management
```
# RPS CRUD
GET    /rps                     - List RPS
GET    /rps/:id                 - Get detail
POST   /rps                     - Create RPS
PUT    /rps/:id                 - Update RPS
DELETE /rps/:id                 - Delete RPS

# RPS Workflow
POST   /rps/:id/submit          - Submit for approval
POST   /rps/:id/activate        - Activate
POST   /rps/:id/archive         - Archive

# RPS Version
GET    /rps/:id/versions        - List versions
POST   /rps/:id/versions/:version_number/activate  - Set active version

# Pustaka (References)
GET    /rps/:id/pustaka         - List pustaka
GET    /pustaka/:id             - Get pustaka
POST   /pustaka                 - Create pustaka
PUT    /pustaka/:id             - Update pustaka
DELETE /pustaka/:id             - Delete pustaka

# Media Pembelajaran
GET    /rps/:id/media-pembelajaran  - List media
GET    /media-pembelajaran/:id      - Get media
POST   /media-pembelajaran          - Create media
PUT    /media-pembelajaran/:id      - Update media
DELETE /media-pembelajaran/:id      - Delete media

# Statistics
GET    /rps/:id/sumber-belajar-stats - Pustaka & media stats

# Export ⭐ NEW!
GET    /rps/:id/export/markdown - Export to Markdown
GET    /rps/:id/export/html     - Export to HTML (download)
GET    /rps/:id/export/json     - Export to JSON
GET    /rps/:id/preview         - Preview HTML in browser
```

#### 6. Rencana Pembelajaran Mingguan
```
GET    /rps/:id/rencana-mingguan  - List weekly plans
GET    /rencana-mingguan/:id      - Get detail
POST   /rencana-mingguan          - Create
PUT    /rencana-mingguan/:id      - Update
DELETE /rencana-mingguan/:id      - Delete
POST   /rps/:id/rencana-mingguan/bulk-create  - Bulk create 16 weeks ⭐
GET    /rps/:id/rencana-mingguan/stats        - Completion stats
```

#### 7. CPMK Management
```
GET    /cpmk                    - List CPMK
GET    /cpmk/:id                - Get detail
POST   /cpmk                    - Create CPMK
PUT    /cpmk/:id                - Update CPMK
DELETE /cpmk/:id                - Delete CPMK

# SubCPMK
GET    /cpmk/:id/subcpmk        - List SubCPMK
POST   /cpmk/:id/subcpmk        - Create SubCPMK
PUT    /subcpmk/:id             - Update SubCPMK
DELETE /subcpmk/:id             - Delete SubCPMK

# Mapping CPMK-CPL
POST   /cpmk/:id/map-cpl        - Map CPMK to CPL
GET    /cpmk/:id/cpl-mappings   - Get mappings
PUT    /cpmk-cpl-mapping/:id    - Update bobot
DELETE /cpmk-cpl-mapping/:id    - Delete mapping

# Statistics
GET    /rps/:id/cpmk-statistics - CPMK statistics
GET    /rps/:id/validate-cpmk   - Validate completeness
```

#### 8. Kelas & Enrollment
```
# Kelas
GET    /kelas                   - List kelas
GET    /kelas/:id               - Get detail
POST   /kelas                   - Create kelas
PUT    /kelas/:id               - Update kelas
DELETE /kelas/:id               - Delete kelas
POST   /kelas/:id/status        - Change status
GET    /kelas/statistics        - Statistics

# Teaching Assignments
GET    /kelas/:id/dosen         - List dosen in kelas
POST   /kelas/:id/dosen         - Assign dosen
PUT    /kelas/:id/dosen/:id_dosen  - Update role
DELETE /kelas/:id/dosen/:id_dosen - Remove dosen

# Enrollment
GET    /enrollment/:id          - Get enrollment
POST   /enrollment              - Enroll mahasiswa
POST   /enrollment/bulk         - Bulk enroll
POST   /enrollment/:id/drop     - Drop
PUT    /enrollment/:id/status   - Update status
PUT    /enrollment/:id/grades   - Update grades

# Mahasiswa Enrollment
GET    /mahasiswa/:nim/enrollment     - List enrollment
GET    /mahasiswa/:nim/krs            - Get KRS
GET    /mahasiswa/:nim/transcript     - Get transcript
GET    /mahasiswa/:nim/enrollment-capacity  - Validate capacity

# Kelas Enrollment
GET    /kelas/:id/enrollment          - List enrollments
GET    /kelas/:id/statistics          - Kelas statistics
```

#### 9. Penilaian (Grading)
```
# Template Penilaian
GET    /rps/:id/template-penilaian     - List templates
POST   /template-penilaian             - Create template
GET    /rps/:id/validate-template      - Validate bobot

# Komponen Penilaian
GET    /kelas/:id/komponen-penilaian   - List komponen
POST   /komponen-penilaian             - Create komponen
PUT    /komponen-penilaian/:id         - Update komponen
DELETE /komponen-penilaian/:id         - Delete komponen

# Input Nilai
POST   /nilai                          - Input single grade
POST   /nilai/bulk                     - Bulk input grades
GET    /enrollment/:id/nilai           - Get grades
GET    /komponen-penilaian/:id/nilai   - Grades by komponen

# Summary & Statistics
GET    /kelas/:id/nilai-summary        - Summary by kelas
GET    /komponen-penilaian/:id/statistics - Komponen stats
GET    /enrollment/:id/cpmk-achievement/:id_cpmk  - CPMK achievement
POST   /kelas/:id/recalculate-grades   - Recalculate

# Ketercapaian ⭐ NEW!
GET    /enrollment/:id/ketercapaian-cpmk  - CPMK achievements
GET    /enrollment/:id/ketercapaian-cpl   - CPL achievements

# Finalisasi ⭐ NEW!
POST   /enrollment/:id/finalize-grades    - Finalize student grades
POST   /kelas/:id/finalize-grades         - Finalize class grades

# Master Data
GET    /jenis-penilaian                   - List jenis penilaian
```

#### 10. Kehadiran (Attendance)
```
# Realisasi Pertemuan
GET    /kelas/:id/realisasi-pertemuan  - List realisasi
GET    /realisasi-pertemuan/:id        - Get detail with kehadiran
POST   /realisasi-pertemuan            - Create realisasi
PUT    /realisasi-pertemuan/:id        - Update realisasi
DELETE /realisasi-pertemuan/:id        - Delete realisasi

# Kehadiran
POST   /realisasi-pertemuan/:id/kehadiran  - Bulk input kehadiran ⭐
GET    /mahasiswa/:nim/kehadiran/kelas/:id_kelas  - Mahasiswa attendance
GET    /kelas/:id/attendance-summary       - Attendance summary
```

#### 11. Analytics & Reporting
```
GET    /analytics/dashboard            - Dashboard overview
GET    /analytics/trends               - Trend analysis
GET    /analytics/kelas/:id/cpmk-report       - CPMK report by kelas
GET    /analytics/kurikulum/:id/cpl-report    - CPL report by kurikulum
GET    /analytics/mahasiswa/:nim/performance  - Student performance (complete)
```

#### 12. Documents
```
GET    /documents/:entity_type/:entity_id  - List documents
GET    /documents/:id                      - Get document
POST   /documents                          - Upload document
DELETE /documents/:id                      - Delete document
GET    /documents/stats/:entity_type/:entity_id  - Statistics
```

#### 13. Notifications
```
# User Endpoints
GET    /notifications                  - List notifications (with filter)
GET    /notifications/unread-count     - Get unread count
POST   /notifications/:id/read         - Mark as read
POST   /notifications/mark-all-read    - Mark all as read
DELETE /notifications/:id              - Delete notification

# Admin Endpoints
POST   /notifications/create           - Create notification
POST   /notifications/broadcast        - Broadcast to users ⭐
POST   /notifications/notify-role      - Notify by role ⭐
```

### Example API Calls

#### Login
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "admin",
    "password": "password123"
  }'
```

#### Create Mahasiswa
```bash
curl -X POST http://localhost:8000/api/mahasiswa \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "nim": "220001",
    "nama": "John Doe",
    "email": "john@student.ac.id",
    "id_prodi": "IF",
    "id_kurikulum": 1,
    "angkatan": "2022",
    "status": "aktif"
  }'
```

#### Bulk Input Nilai
```bash
curl -X POST http://localhost:8000/api/nilai/bulk \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "id_komponen": 1,
    "nilai_list": [
      {"id_enrollment": 1, "nilai_mentah": 85, "catatan": "Baik"},
      {"id_enrollment": 2, "nilai_mentah": 90, "catatan": "Sangat baik"}
    ]
  }'
```

#### Finalize Grades (Auto CPMK & CPL)
```bash
curl -X POST http://localhost:8000/api/kelas/1/finalize-grades \
  -H "Authorization: Bearer <token>"
```

#### Export RPS to HTML
```bash
curl http://localhost:8000/api/rps/1/export/html \
  -H "Authorization: Bearer <token>" \
  -o RPS_001.html
```

#### Broadcast Notification
```bash
curl -X POST http://localhost:8000/api/notifications/notify-role \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "role": "mahasiswa",
    "type": "announcement",
    "title": "Pengumuman: Jadwal UTS",
    "message": "UTS akan dilaksanakan pada tanggal 15-20 Mei 2025",
    "link": "/schedule/uts"
  }'
```

## 📊 Database Schema

### Total Tables: 37

#### Core Tables
- `fakultas`, `prodi` - Organizational structure
- `kurikulum` - Curriculum definitions
- `cpl` - Program Learning Outcomes
- `matakuliah` - Courses (per curriculum)
- `dosen`, `mahasiswa` - User data
- `users`, `roles`, `user_roles` - Authentication

#### RPS Tables
- `rps` - Semester Learning Plans
- `rps_versions` - RPS versioning
- `rps_approvals` - Approval workflow
- `cpmk`, `subcpmk` - Course Learning Outcomes
- `relasi_cpmk_cpl` - CPMK-CPL mapping
- `rencana_mingguan` - Weekly learning plans
- `pustaka` - References
- `media_pembelajaran` - Learning media

#### Kelas & Enrollment
- `kelas` - Classes
- `pengampu_kelas` - Teaching assignments
- `enrollment` - Student enrollments

#### Penilaian Tables
- `jenis_penilaian` - Grading types
- `template_penilaian` - Grading templates
- `komponen_penilaian` - Grading components
- `nilai_detail` - Detailed grades
- `ketercapaian_cpmk` - CPMK achievements ⭐
- `ketercapaian_cpl` - CPL achievements ⭐

#### Attendance Tables
- `realisasi_pertemuan` - Class meetings
- `kehadiran` - Attendance records

#### Supporting Tables
- `ambang_batas` - Threshold configuration
- `documents` - Document management
- `notifications` - Notifications
- `audit_log` - Audit trail

#### Materialized Views (Analytics)
- `mv_ketercapaian_kelas` - CPMK achievement by class
- `mv_ketercapaian_cpl` - CPL achievement by curriculum
- `mv_statistik_kurikulum` - Curriculum statistics

## 🎯 Completion Status

### ✅ Completed (90%)
- [x] Kurikulum Management (100%)
- [x] Master Data CRUD (100%)
- [x] RPS Management with Export (100%)
- [x] CPMK & CPL Management (100%)
- [x] **Sistem Penilaian LENGKAP** (100%) ⭐
- [x] **Kehadiran & Realisasi** (100%) ⭐
- [x] **Analytics & Reporting** (100%) ⭐
- [x] **Document Management** (100%) ⭐
- [x] **Notification System** (100%) ⭐
- [x] Enrollment & Kelas (100%)
- [x] Pustaka & Media (100%)
- [x] Authentication & RBAC (100%)
- [x] Audit Logging (100%)

### 🔄 Optional (10%)
- [ ] Forum Diskusi
- [ ] Ujian Online
- [ ] Portfolio Management
- [ ] Integrasi PDDIKTI
- [ ] Mobile App API
- [ ] PDF Export dengan library

## 👥 User Roles

### Admin
- Full system access
- Manage all master data
- Approve kurikulum & RPS
- Broadcast notifications

### Kaprodi (Program Head)
- Manage program curriculum
- Approve RPS
- View analytics & reports
- Manage dosen & mahasiswa

### Dosen (Lecturer)
- Create & manage RPS
- Input grades
- Manage classes
- Input attendance
- View student performance

### Mahasiswa (Student)
- View RPS
- View grades & achievements
- View attendance
- Check transcript
- Receive notifications

## 🔒 Security Features

- ✅ JWT Authentication
- ✅ Role-Based Access Control (RBAC)
- ✅ Password hashing (bcrypt)
- ✅ SQL Injection prevention (prepared statements)
- ✅ XSS protection
- ✅ CSRF protection
- ✅ Audit logging (all operations)
- ✅ IP & User agent tracking
- ✅ Input validation & sanitization

## 📈 Performance

- Database indexing optimal (30+ indexes)
- Materialized views untuk analytics
- Efficient queries dengan JOIN optimization
- JSONB support untuk flexible data
- Database triggers untuk automation

## 📝 Business Rules

Key business rules (BR-K):
- **BR-K01**: Mahasiswa kurikulum immutable
- **BR-K02**: MK berbeda per kurikulum
- **BR-K03**: MK soft delete only
- **BR-K04**: Enrollment curriculum validation
- **BR-K05**: Multi-curriculum support

## 📊 Project Statistics

- **Total Endpoints**: 140+
- **Total Files**: 60+
- **Lines of Code**: ~7,000+
- **Database Tables**: 37
- **Controllers**: 23
- **Repositories**: 20
- **Services**: 13
- **Completion**: 90%
- **Status**: ✅ Production Ready

## 🧪 Testing

```bash
# Run linting
composer lint

# Run tests (if available)
composer test
```

## 📄 License

This project is licensed under the MIT License.

## 👨‍💻 Authors

- **Adnan Zulkarnain** - Lead Developer

## 📞 Contact

- **GitHub**: https://github.com/adnanzulkarnain/php-obe
- **Issues**: https://github.com/adnanzulkarnain/php-obe/issues

## 🙏 Acknowledgments

- PostgreSQL Community
- PHP Community
- Indonesian Higher Education Institutions

---

**Made with ❤️ for Indonesian Higher Education**

**Version**: 3.0
**Last Updated**: November 2025
**Status**: Production Ready ✅

*Sistem ini siap digunakan untuk operasional penuh perguruan tinggi dengan dukungan lengkap untuk akreditasi dan SN-DIKTI compliance.*
