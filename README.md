# 📚 Dokumentasi Sistem Informasi Kurikulum OBE v3.0

**Sistem Manajemen Kurikulum Outcome-Based Education dengan Multi-Curriculum Support**

---

## 🎯 Ringkasan Proyek

Sistem Informasi Kurikulum OBE adalah platform digital komprehensif untuk mendukung implementasi Outcome-Based Education di perguruan tinggi, dengan fitur utama **Kurikulum Management** yang memungkinkan pengelolaan multiple curricula secara paralel.

### Fitur Utama

- ✅ **Multi-Curriculum Support** - Kelola beberapa kurikulum secara bersamaan
- ✅ **CPL & CPMK Management** - Definisi dan pemetaan capaian pembelajaran
- ✅ **RPS Digital** - Pembuatan dan approval RPS elektronik
- ✅ **Sistem Penilaian Otomatis** - Perhitungan ketercapaian CPMK & CPL
- ✅ **Analytics Dashboard** - Monitoring dan pelaporan OBE compliance
- ✅ **Audit Trail** - Logging lengkap untuk akreditasi

---

## 📄 Dokumen Yang Tersedia

### 1. Software Specification Document (SDD)
**File:** `OBE-System-Specification-Document.md`

Dokumen spesifikasi lengkap meliputi:
- Executive Summary & System Architecture
- Database Design (ERD & Schema)
- User Roles & Permissions Matrix
- 13 Functional Requirements (FR-001 s/d FR-013)
- Non-Functional Requirements (Performance, Security, dll)
- Testing Strategy & Success Metrics
- Implementation Timeline (6 phases)
- API Design & Technical Specifications

---

### 2. Database Schema (SQL DDL)
**File:** `OBE-Database-Schema-v3-WITH-KURIKULUM.sql`

Schema database PostgreSQL lengkap dengan:
- 30+ tables dengan relasi lengkap
- Core Entity: Kurikulum, CPL, CPMK, MK, RPS, Kelas, Enrollment
- Triggers untuk auto-calculation & validation
- Materialized Views untuk analytics
- Indexes untuk performa optimal
- Business rule enforcement (BR-K01 s/d BR-K37)
- Sample data untuk testing

---

### 3. Use Cases: Kurikulum Management
**File:** `Use-Cases-Kurikulum-Management.md`

10 use cases detail untuk Kurikulum Management:
- UC-K01: Create New Curriculum
- UC-K02: Approve Curriculum
- UC-K03: Activate Curriculum
- UC-K04: Define CPL for Curriculum
- UC-K05: Add Mata Kuliah to Curriculum
- UC-K06: Create MK Mapping Between Curricula
- UC-K07: Assign Student to Curriculum
- UC-K08: Compare Curricula
- UC-K09: Deactivate Curriculum
- UC-K10: Archive Curriculum

---

### 4. Implementation Guide
**File:** `Implementation-Guide-Quick-Reference.md`

Panduan praktis untuk development team:
- Database implementation checklist
- Migration strategy (v2 → v3)
- Application layer examples (TypeScript/Node.js)
- Testing checklist (unit & integration tests)
- Performance optimization tips
- Deployment checklist
- Common issues & troubleshooting

---

## 🔑 Business Rules Kunci

### Kurikulum Management

| Rule ID | Deskripsi | Status |
|---------|-----------|--------|
| BR-K01 | Mahasiswa mengikuti satu kurikulum sepanjang studi (IMMUTABLE) | ✅ Enforced |
| BR-K02 | MK dengan kode sama di kurikulum berbeda = MK berbeda | ✅ Composite PK |
| BR-K03 | MK tidak dapat dihapus dari kurikulum (soft delete only) | ✅ Trigger |
| BR-K04 | Mahasiswa hanya bisa ambil MK dari kurikulumnya | ✅ Trigger |
| BR-K05 | Support 2+ kurikulum berjalan paralel | ✅ Multiple active |
| BR-K06 | Pemetaan MK antar kurikulum untuk konversi | ✅ Table mapping |
| BR-K07 | CPL terikat ke kurikulum (bisa berbeda antar kurikulum) | ✅ FK constraint |

---

## 🚀 Quick Start

### 1. Setup Database

```bash
# Create database
createdb obe_system

# Execute schema
psql -d obe_system -f OBE-Database-Schema-v3-WITH-KURIKULUM.sql

# Verify
psql -d obe_system -c "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='public';"
```

### 2. Insert Master Data

```sql
-- Fakultas & Prodi
INSERT INTO fakultas VALUES ('FTI', 'Fakultas Teknologi Industri');
INSERT INTO prodi VALUES ('TIF', 'FTI', 'Teknik Informatika', 'S1', 'A');

-- Kurikulum
INSERT INTO kurikulum (id_prodi, kode_kurikulum, nama_kurikulum, tahun_berlaku, status, is_primary)
VALUES ('TIF', 'K2024', 'Kurikulum OBE 2024', 2024, 'aktif', TRUE);
```

---

## 📈 Implementation Timeline

| Phase | Duration | Deliverables |
|-------|----------|--------------|
| **Phase 1: Foundation** | Week 1-2 | Database setup, master data, auth |
| **Phase 2: Kurikulum** | Week 3-4 | Kurikulum CRUD, CPL, MK management |
| **Phase 3: RPS** | Week 5-6 | RPS creation, CPMK, approval workflow |
| **Phase 4: Kelas & Enrollment** | Week 7-8 | Class management, student enrollment |
| **Phase 5: Penilaian** | Week 9-10 | Assessment, calculation, grading |
| **Phase 6: Analytics** | Week 11-12 | Dashboards, reports, materialized views |

**Total:** 12 weeks for MVP

---

## 👥 User Roles & Access

| Role | Key Functions |
|------|---------------|
| **Kaprodi** | Manage kurikulum, CPL, approve RPS, view analytics |
| **Dosen** | Create RPS, define CPMK, input nilai, view class data |
| **Mahasiswa** | View RPS, check nilai, track CPMK progress |
| **Admin** | Manage users, system config, data import/export |

---

## 📝 Version History

| Version | Date | Changes |
|---------|------|---------|
| **v1.0** | Oct 22, 2025 | Initial draft - basic OBE features |
| **v2.0** | Oct 22, 2025 | Major revision - fixed database design |
| **v3.0** | Oct 22, 2025 | **Current** - Added Kurikulum Management |

---

**Last Updated:** October 22, 2025  
**Document Version:** 3.0  
**Status:** Ready for Implementation

**Semoga sukses dengan implementasinya! 🚀**
