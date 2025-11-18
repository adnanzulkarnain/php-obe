# Implementation Guide: OBE System with Kurikulum Management
## Quick Reference & Migration Strategy

**Version:** 3.0  
**Date:** October 22, 2025  
**Target Audience:** Development Team, Database Admin

---

## 📋 Document Summary

Dokumen ini adalah panduan cepat untuk implementasi Sistem Informasi Kurikulum OBE yang sudah direvisi dengan **Kurikulum Management** sebagai core feature.

---

## 🎯 Key Changes from Original Design

### Critical Fixes Implemented

| Issue | Original Design | New Design (v3.0) | Impact |
|-------|----------------|-------------------|---------|
| **CPL Location** | CPL tied to RPS | CPL tied to KURIKULUM | HIGH - Eliminates duplication |
| **MK Identity** | Single PK: kode_mk | Composite PK: (kode_mk, id_kurikulum) | HIGH - Allows same code in different curricula |
| **Enrollment** | Missing kelas & enrollment | Added kelas & enrollment entities | CRITICAL - Proper student-class relationship |
| **Calculation** | Ambiguous penilaian structure | Clear template → instance pattern | HIGH - Enables proper calculation |
| **Curriculum Support** | Not supported | Full curriculum management | CRITICAL - Business requirement |
| **Student Assignment** | Not defined | Immutable curriculum assignment | HIGH - Data integrity |

---

## 🗄️ Database Implementation Checklist

### Phase 1: Core Schema (Week 1)

```bash
# Step 1: Create database
psql -U postgres
CREATE DATABASE obe_system WITH ENCODING 'UTF8';
\c obe_system

# Step 2: Enable extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

# Step 3: Execute schema
psql -U postgres -d obe_system -f OBE-Database-Schema-v3-WITH-KURIKULUM.sql

# Step 4: Verify installation
SELECT COUNT(*) FROM information_schema.tables 
WHERE table_schema = 'public' AND table_type = 'BASE TABLE';
-- Expected: ~30 tables
```

### Phase 2: Sample Data (Week 1)

```sql
-- Insert master data
INSERT INTO fakultas (id_fakultas, nama) VALUES ('FTI', 'Fakultas Teknologi Industri');
INSERT INTO prodi (id_prodi, id_fakultas, nama, jenjang) VALUES ('TIF', 'FTI', 'Teknik Informatika', 'S1');

-- Insert curricula
INSERT INTO kurikulum (id_prodi, kode_kurikulum, nama_kurikulum, tahun_berlaku, status, is_primary) 
VALUES 
('TIF', 'K2024', 'Kurikulum OBE 2024', 2024, 'aktif', FALSE),
('TIF', 'K2029', 'Kurikulum OBE 2029', 2029, 'aktif', TRUE);

-- Insert CPL for K2024
INSERT INTO cpl (id_kurikulum, kode_cpl, deskripsi, kategori) 
SELECT 
    (SELECT id_kurikulum FROM kurikulum WHERE kode_kurikulum = 'K2024'),
    'CPL-' || i,
    'Deskripsi CPL-' || i,
    CASE 
        WHEN i <= 2 THEN 'sikap'
        WHEN i <= 5 THEN 'pengetahuan'
        WHEN i <= 7 THEN 'keterampilan_umum'
        ELSE 'keterampilan_khusus'
    END
FROM generate_series(1, 10) i;

-- Verify
SELECT k.kode_kurikulum, COUNT(cpl.id_cpl) as total_cpl
FROM kurikulum k
LEFT JOIN cpl ON cpl.id_kurikulum = k.id_kurikulum
GROUP BY k.kode_kurikulum;
```

### Phase 3: Test Triggers & Validations (Week 2)

```sql
-- Test 1: Prevent hard delete of MK
INSERT INTO matakuliah (kode_mk, id_kurikulum, nama_mk, sks, semester, jenis_mk)
VALUES ('IF101', 1, 'Test MK', 3, 1, 'wajib');

DELETE FROM matakuliah WHERE kode_mk = 'IF101';
-- Expected: ERROR - Hard delete not allowed

-- Test 2: Prevent curriculum change for student
INSERT INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan)
VALUES ('2024001', 'Test Student', 'test@example.com', 'TIF', 1, '2024');

UPDATE mahasiswa SET id_kurikulum = 2 WHERE nim = '2024001';
-- Expected: ERROR - Curriculum is immutable

-- Test 3: Prevent enrollment in wrong curriculum
-- Create kelas for K2024
INSERT INTO kelas (kode_mk, id_kurikulum, nama_kelas, semester, tahun_ajaran)
VALUES ('IF101', 1, 'A', 'Ganjil', '2024/2025');

-- Try to enroll K2029 student in K2024 class
INSERT INTO mahasiswa (nim, nama, email, id_prodi, id_kurikulum, angkatan)
VALUES ('2029001', 'Test Student 2', 'test2@example.com', 'TIF', 2, '2029');

INSERT INTO enrollment (nim, id_kelas)
VALUES ('2029001', 1);
-- Expected: ERROR - Student can only enroll in classes from their curriculum
```

---

## 🔄 Migration Strategy (Existing System)

### If You Have Existing Data

```sql
-- ============================================
-- MIGRATION SCRIPT: v2.0 → v3.0
-- ============================================

BEGIN;

-- Step 1: Create default curriculum for existing prodi
INSERT INTO kurikulum (id_prodi, kode_kurikulum, nama_kurikulum, tahun_berlaku, status, is_primary)
SELECT 
    id_prodi,
    'K2024',
    'Kurikulum Existing 2024',
    2024,
    'aktif',
    TRUE
FROM prodi
ON CONFLICT DO NOTHING;

-- Step 2: Migrate existing CPL to curriculum
-- Assuming old CPL table had: (id_cpl, id_prodi, ...)
ALTER TABLE cpl_old RENAME TO cpl_backup;

INSERT INTO cpl (id_kurikulum, kode_cpl, deskripsi, kategori, urutan)
SELECT 
    k.id_kurikulum,
    c.kode_cpl,
    c.deskripsi,
    c.kategori,
    c.urutan
FROM cpl_backup c
JOIN kurikulum k ON k.id_prodi = c.id_prodi AND k.kode_kurikulum = 'K2024';

-- Step 3: Migrate MK to curriculum-based structure
ALTER TABLE matakuliah_old RENAME TO matakuliah_backup;

INSERT INTO matakuliah (kode_mk, id_kurikulum, nama_mk, sks, semester, jenis_mk)
SELECT 
    m.kode_mk,
    k.id_kurikulum,
    m.nama_mk,
    m.sks,
    m.semester,
    m.jenis_mk
FROM matakuliah_backup m
JOIN kurikulum k ON k.id_prodi = m.id_prodi AND k.kode_kurikulum = 'K2024';

-- Step 4: Assign existing students to curriculum
UPDATE mahasiswa m
SET id_kurikulum = (
    SELECT k.id_kurikulum 
    FROM kurikulum k 
    WHERE k.id_prodi = m.id_prodi 
    AND k.kode_kurikulum = 'K2024'
)
WHERE id_kurikulum IS NULL;

-- Step 5: Verify migration
SELECT 
    'Curricula' as entity,
    COUNT(*) as count
FROM kurikulum
UNION ALL
SELECT 'CPL', COUNT(*) FROM cpl
UNION ALL
SELECT 'Matakuliah', COUNT(*) FROM matakuliah
UNION ALL
SELECT 'Mahasiswa with curriculum', COUNT(*) FROM mahasiswa WHERE id_kurikulum IS NOT NULL;

COMMIT;

-- ============================================
-- POST-MIGRATION VERIFICATION
-- ============================================

-- Check for students without curriculum
SELECT nim, nama FROM mahasiswa WHERE id_kurikulum IS NULL;
-- Should return 0 rows

-- Check CPL distribution
SELECT k.kode_kurikulum, cpl.kategori, COUNT(*) 
FROM kurikulum k
JOIN cpl ON cpl.id_kurikulum = k.id_kurikulum
GROUP BY k.kode_kurikulum, cpl.kategori
ORDER BY k.kode_kurikulum, cpl.kategori;

-- Check MK per curriculum
SELECT k.kode_kurikulum, COUNT(mk.kode_mk) as total_mk, SUM(mk.sks) as total_sks
FROM kurikulum k
LEFT JOIN matakuliah mk ON mk.id_kurikulum = k.id_kurikulum
GROUP BY k.kode_kurikulum;
```

---

## 🏗️ Application Layer Implementation

### 1. Entity Models (TypeScript/Node.js Example)

```typescript
// models/Kurikulum.ts
interface Kurikulum {
  id_kurikulum: number;
  id_prodi: string;
  kode_kurikulum: string;
  nama_kurikulum: string;
  tahun_berlaku: number;
  tahun_berakhir?: number;
  status: 'draft' | 'review' | 'approved' | 'aktif' | 'non-aktif' | 'arsip';
  is_primary: boolean;
  nomor_sk?: string;
  tanggal_sk?: Date;
  created_at: Date;
  updated_at: Date;
}

// models/CPL.ts
interface CPL {
  id_cpl: number;
  id_kurikulum: number;
  kode_cpl: string;
  deskripsi: string;
  kategori: 'sikap' | 'pengetahuan' | 'keterampilan_umum' | 'keterampilan_khusus';
  urutan: number;
  is_active: boolean;
}

// models/MataKuliah.ts
interface MataKuliah {
  kode_mk: string;
  id_kurikulum: number;
  nama_mk: string;
  sks: number;
  semester: number;
  jenis_mk: 'wajib' | 'pilihan' | 'MKWU';
  is_active: boolean;
}

// models/Mahasiswa.ts
interface Mahasiswa {
  nim: string;
  nama: string;
  email: string;
  id_prodi: string;
  id_kurikulum: number; // IMMUTABLE!
  angkatan: string;
  status: 'aktif' | 'cuti' | 'lulus' | 'DO';
}
```

### 2. Service Layer (Business Logic)

```typescript
// services/KurikulumService.ts
class KurikulumService {
  
  async createKurikulum(data: CreateKurikulumDTO): Promise<Kurikulum> {
    // Validate unique kode_kurikulum per prodi
    const exists = await this.checkKurikulumExists(data.id_prodi, data.kode_kurikulum);
    if (exists) {
      throw new Error('Kode kurikulum sudah digunakan');
    }
    
    // Create curriculum
    const kurikulum = await db.kurikulum.create({
      data: {
        ...data,
        status: 'draft',
        created_at: new Date()
      }
    });
    
    // Log audit
    await auditLog.log({
      table_name: 'kurikulum',
      record_id: kurikulum.id_kurikulum,
      action: 'INSERT',
      new_data: kurikulum
    });
    
    return kurikulum;
  }
  
  async activateKurikulum(id_kurikulum: number, set_as_primary: boolean = false): Promise<void> {
    // Validate status
    const kurikulum = await this.getKurikulum(id_kurikulum);
    if (kurikulum.status !== 'approved') {
      throw new Error('Kurikulum harus disetujui terlebih dahulu');
    }
    
    // Start transaction
    await db.transaction(async (tx) => {
      // If setting as primary, remove primary flag from others
      if (set_as_primary) {
        await tx.kurikulum.updateMany({
          where: { id_prodi: kurikulum.id_prodi, is_primary: true },
          data: { is_primary: false }
        });
      }
      
      // Activate curriculum
      await tx.kurikulum.update({
        where: { id_kurikulum },
        data: { 
          status: 'aktif',
          is_primary: set_as_primary
        }
      });
      
      // Send notifications
      await notificationService.sendToFaculty(
        kurikulum.id_prodi,
        `Kurikulum ${kurikulum.nama_kurikulum} telah diaktifkan`
      );
    });
  }
  
  async assignStudentToCurriculum(nim: string, angkatan: string, id_prodi: string): Promise<void> {
    // Find appropriate curriculum
    const curricula = await db.kurikulum.findMany({
      where: {
        id_prodi,
        status: 'aktif',
        tahun_berlaku: { lte: parseInt(angkatan) }
      },
      orderBy: { tahun_berlaku: 'desc' }
    });
    
    if (curricula.length === 0) {
      throw new Error('Tidak ada kurikulum aktif');
    }
    
    // Use primary or latest
    const curriculum = curricula.find(k => k.is_primary) || curricula[0];
    
    // Assign (IMMUTABLE - can only be set once)
    await db.mahasiswa.update({
      where: { nim },
      data: { id_kurikulum: curriculum.id_kurikulum }
    });
  }
}
```

### 3. API Controllers

```typescript
// controllers/KurikulumController.ts
class KurikulumController {
  
  async create(req: Request, res: Response) {
    try {
      const data = req.body;
      const kurikulum = await kurikulumService.createKurikulum(data);
      res.status(201).json({
        success: true,
        data: kurikulum
      });
    } catch (error) {
      res.status(400).json({
        success: false,
        error: error.message
      });
    }
  }
  
  async activate(req: Request, res: Response) {
    try {
      const { id_kurikulum } = req.params;
      const { set_as_primary } = req.body;
      
      await kurikulumService.activateKurikulum(
        parseInt(id_kurikulum),
        set_as_primary
      );
      
      res.json({
        success: true,
        message: 'Kurikulum berhasil diaktifkan'
      });
    } catch (error) {
      res.status(400).json({
        success: false,
        error: error.message
      });
    }
  }
  
  async compare(req: Request, res: Response) {
    try {
      const ids = req.query.ids.split(',').map(id => parseInt(id));
      const comparison = await kurikulumService.compareCurricula(ids);
      res.json({
        success: true,
        data: comparison
      });
    } catch (error) {
      res.status(400).json({
        success: false,
        error: error.message
      });
    }
  }
}
```

### 4. Validation Middleware

```typescript
// middleware/enrollmentValidation.ts
async function validateEnrollment(req: Request, res: Response, next: NextFunction) {
  try {
    const { nim, id_kelas } = req.body;
    
    // Get student's curriculum
    const mahasiswa = await db.mahasiswa.findUnique({
      where: { nim },
      select: { id_kurikulum: true }
    });
    
    // Get class's curriculum
    const kelas = await db.kelas.findUnique({
      where: { id_kelas },
      select: { id_kurikulum: true }
    });
    
    // Validate same curriculum (BR-K04)
    if (mahasiswa.id_kurikulum !== kelas.id_kurikulum) {
      return res.status(403).json({
        success: false,
        error: 'Mahasiswa hanya dapat mengambil kelas dari kurikulumnya'
      });
    }
    
    next();
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
}
```

---

## 🧪 Testing Checklist

### Unit Tests

```typescript
// tests/kurikulum.test.ts
describe('Kurikulum Management', () => {
  
  test('Create curriculum with unique code', async () => {
    const data = {
      id_prodi: 'TIF',
      kode_kurikulum: 'K2024',
      nama_kurikulum: 'Test Curriculum',
      tahun_berlaku: 2024
    };
    
    const kurikulum = await kurikulumService.createKurikulum(data);
    expect(kurikulum.status).toBe('draft');
    expect(kurikulum.kode_kurikulum).toBe('K2024');
  });
  
  test('Prevent duplicate kode_kurikulum', async () => {
    // Create first
    await kurikulumService.createKurikulum({ /* data */ });
    
    // Try to create duplicate
    await expect(
      kurikulumService.createKurikulum({ /* same data */ })
    ).rejects.toThrow('Kode kurikulum sudah digunakan');
  });
  
  test('Student curriculum is immutable', async () => {
    const mahasiswa = await createTestStudent();
    
    await expect(
      db.mahasiswa.update({
        where: { nim: mahasiswa.nim },
        data: { id_kurikulum: 999 }
      })
    ).rejects.toThrow();
  });
  
  test('Student can only enroll in their curriculum', async () => {
    const mahasiswaK2024 = await createTestStudent({ id_kurikulum: 1 });
    const kelasK2029 = await createTestKelas({ id_kurikulum: 2 });
    
    await expect(
      enrollmentService.enroll(mahasiswaK2024.nim, kelasK2029.id_kelas)
    ).rejects.toThrow('Student can only enroll in classes from their curriculum');
  });
});
```

### Integration Tests

```typescript
describe('End-to-End: Curriculum Lifecycle', () => {
  
  test('Complete curriculum creation and activation flow', async () => {
    // 1. Create curriculum
    const kurikulum = await kurikulumService.createKurikulum({
      id_prodi: 'TIF',
      kode_kurikulum: 'K2029',
      nama_kurikulum: 'Kurikulum Test 2029',
      tahun_berlaku: 2029
    });
    expect(kurikulum.status).toBe('draft');
    
    // 2. Add CPL
    await cplService.createCPL({
      id_kurikulum: kurikulum.id_kurikulum,
      kode_cpl: 'CPL-1',
      deskripsi: 'Test CPL',
      kategori: 'sikap'
    });
    
    // 3. Add MK
    await matakuliahService.createMK({
      kode_mk: 'IF101',
      id_kurikulum: kurikulum.id_kurikulum,
      nama_mk: 'Test MK',
      sks: 3,
      semester: 1,
      jenis_mk: 'wajib'
    });
    
    // 4. Submit for approval
    await kurikulumService.submitForApproval(kurikulum.id_kurikulum);
    const submitted = await kurikulumService.getKurikulum(kurikulum.id_kurikulum);
    expect(submitted.status).toBe('review');
    
    // 5. Approve
    await kurikulumService.approve(kurikulum.id_kurikulum, 'DOSEN001');
    const approved = await kurikulumService.getKurikulum(kurikulum.id_kurikulum);
    expect(approved.status).toBe('approved');
    
    // 6. Activate
    await kurikulumService.activateKurikulum(kurikulum.id_kurikulum, true);
    const activated = await kurikulumService.getKurikulum(kurikulum.id_kurikulum);
    expect(activated.status).toBe('aktif');
    expect(activated.is_primary).toBe(true);
  });
});
```

---

## 📊 Performance Optimization

### Recommended Indexes

```sql
-- Already created in schema, but verify:
EXPLAIN ANALYZE SELECT * FROM mahasiswa WHERE id_kurikulum = 1;
-- Should use: idx_mahasiswa_kurikulum

EXPLAIN ANALYZE SELECT * FROM matakuliah WHERE id_kurikulum = 1 AND is_active = TRUE;
-- Should use: idx_mk_kurikulum

-- If slow, add:
CREATE INDEX CONCURRENTLY idx_enrollment_kurikulum ON enrollment (nim, id_kelas)
  INCLUDE (status, nilai_akhir);
```

### Query Optimization

```sql
-- Bad: Multiple queries
SELECT * FROM kurikulum WHERE id_kurikulum = 1;
SELECT * FROM cpl WHERE id_kurikulum = 1;
SELECT * FROM matakuliah WHERE id_kurikulum = 1;

-- Good: Single query with JOINs
SELECT 
  k.*,
  json_agg(DISTINCT jsonb_build_object('id', c.id_cpl, 'kode', c.kode_cpl, 'deskripsi', c.deskripsi)) as cpl,
  json_agg(DISTINCT jsonb_build_object('kode', m.kode_mk, 'nama', m.nama_mk, 'sks', m.sks)) as matakuliah
FROM kurikulum k
LEFT JOIN cpl c ON c.id_kurikulum = k.id_kurikulum
LEFT JOIN matakuliah m ON m.id_kurikulum = k.id_kurikulum
WHERE k.id_kurikulum = 1
GROUP BY k.id_kurikulum;
```

### Materialized View Refresh

```sql
-- Set up automatic refresh (run daily at 2 AM)
CREATE OR REPLACE FUNCTION refresh_mv_kurikulum()
RETURNS void AS $$
BEGIN
  REFRESH MATERIALIZED VIEW CONCURRENTLY mv_ketercapaian_kelas;
  REFRESH MATERIALIZED VIEW CONCURRENTLY mv_ketercapaian_cpl;
  REFRESH MATERIALIZED VIEW CONCURRENTLY mv_statistik_kurikulum;
END;
$$ LANGUAGE plpgsql;

-- Create cron job
SELECT cron.schedule('refresh-mv-daily', '0 2 * * *', 'SELECT refresh_mv_kurikulum()');
```

---

## 🚀 Deployment Checklist

### Pre-Deployment

- [ ] Backup existing database
- [ ] Test migration script in staging
- [ ] Verify all triggers and constraints
- [ ] Test materialized view refresh
- [ ] Load test with expected concurrent users
- [ ] Security audit (SQL injection, XSS)
- [ ] Prepare rollback plan

### Deployment

- [ ] Schedule maintenance window
- [ ] Notify users of downtime
- [ ] Stop application servers
- [ ] Execute database migration
- [ ] Verify data integrity
- [ ] Start application servers
- [ ] Smoke test critical features
- [ ] Monitor error logs
- [ ] Send completion notification

### Post-Deployment

- [ ] Monitor performance metrics
- [ ] Check audit logs for anomalies
- [ ] Verify materialized views updating
- [ ] User acceptance testing
- [ ] Collect feedback
- [ ] Document issues and resolutions

---

## 📞 Support & Maintenance

### Common Issues & Solutions

**Issue 1: Student enrolled in wrong curriculum**
```sql
-- Diagnosis
SELECT m.nim, m.nama, m.id_kurikulum, e.id_kelas, k.id_kurikulum as kelas_kurikulum
FROM mahasiswa m
JOIN enrollment e ON e.nim = m.nim
JOIN kelas k ON k.id_kelas = e.id_kelas
WHERE m.id_kurikulum != k.id_kurikulum;

-- This should return 0 rows. If not, there's a data integrity issue.
-- Prevention: Trigger already in place
```

**Issue 2: Curriculum cannot be deleted**
```sql
-- Check dependencies
SELECT 
  'Mahasiswa' as entity, COUNT(*) as count
FROM mahasiswa WHERE id_kurikulum = ?
UNION ALL
SELECT 'CPL', COUNT(*) FROM cpl WHERE id_kurikulum = ?
UNION ALL
SELECT 'Matakuliah', COUNT(*) FROM matakuliah WHERE id_kurikulum = ?;

-- If counts > 0, cannot delete. Use deactivate → archive instead.
```

**Issue 3: Slow queries on large datasets**
```sql
-- Analyze slow query
EXPLAIN (ANALYZE, BUFFERS) 
SELECT /* your slow query */;

-- Check index usage
SELECT schemaname, tablename, indexname, idx_scan
FROM pg_stat_user_indexes
ORDER BY idx_scan;

-- Unused indexes have idx_scan = 0
```

---

## ✅ Production Readiness Checklist

### Database

- [ ] All tables created with correct schema
- [ ] All indexes in place
- [ ] All triggers working
- [ ] All constraints enforced
- [ ] Materialized views created
- [ ] Backup strategy configured
- [ ] Replication configured (if needed)

### Application

- [ ] All API endpoints implemented
- [ ] Input validation on all forms
- [ ] Error handling comprehensive
- [ ] Audit logging working
- [ ] Authentication & authorization working
- [ ] Session management secure
- [ ] File upload/download working

### Testing

- [ ] Unit tests passing (>70% coverage)
- [ ] Integration tests passing
- [ ] E2E tests passing
- [ ] Load testing completed
- [ ] Security testing completed
- [ ] User acceptance testing completed

### Documentation

- [ ] API documentation complete
- [ ] User manual complete
- [ ] Admin manual complete
- [ ] Deployment guide complete
- [ ] Troubleshooting guide complete

---

**END OF GUIDE**

For detailed specifications, refer to:
- `OBE-System-Specification-Document.md`
- `OBE-Database-Schema-v3-WITH-KURIKULUM.sql`
- `Use-Cases-Kurikulum-Management.md`
