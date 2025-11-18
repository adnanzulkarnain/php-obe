# Use Cases: Kurikulum Management Module
## Sistem Informasi Kurikulum OBE

**Version:** 1.0  
**Date:** October 22, 2025  
**Module:** Kurikulum Management

---

## 📋 Table of Contents

1. [UC-K01: Create New Curriculum](#uc-k01-create-new-curriculum)
2. [UC-K02: Approve Curriculum](#uc-k02-approve-curriculum)
3. [UC-K03: Activate Curriculum](#uc-k03-activate-curriculum)
4. [UC-K04: Define CPL for Curriculum](#uc-k04-define-cpl-for-curriculum)
5. [UC-K05: Add Mata Kuliah to Curriculum](#uc-k05-add-mata-kuliah-to-curriculum)
6. [UC-K06: Create MK Mapping Between Curricula](#uc-k06-create-mk-mapping-between-curricula)
7. [UC-K07: Assign Student to Curriculum](#uc-k07-assign-student-to-curriculum)
8. [UC-K08: Compare Curricula](#uc-k08-compare-curricula)
9. [UC-K09: Deactivate Curriculum](#uc-k09-deactivate-curriculum)
10. [UC-K10: Archive Curriculum](#uc-k10-archive-curriculum)

---

## UC-K01: Create New Curriculum

### Basic Information

| Attribute | Value |
|-----------|-------|
| **Use Case ID** | UC-K01 |
| **Use Case Name** | Create New Curriculum |
| **Actor** | Kaprodi |
| **Priority** | CRITICAL |
| **Preconditions** | - User logged in as Kaprodi<br>- Prodi exists in system |
| **Postconditions** | - New curriculum created with status 'draft'<br>- System generates id_kurikulum |

### Main Flow

1. Kaprodi navigates to Kurikulum Management menu
2. System displays list of existing curricula for the prodi
3. Kaprodi clicks "Buat Kurikulum Baru"
4. System displays curriculum creation form
5. Kaprodi inputs:
   - Kode Kurikulum (e.g., "K2029")
   - Nama Kurikulum (e.g., "Kurikulum OBE 2029")
   - Tahun Berlaku (e.g., 2029)
   - Deskripsi
6. Kaprodi uploads SK dokumen (optional at draft stage)
7. Kaprodi clicks "Simpan"
8. System validates:
   - Kode kurikulum unique per prodi ✅
   - Tahun berlaku is valid year ✅
9. System creates curriculum with status = 'draft'
10. System logs audit trail
11. System displays success message
12. System redirects to curriculum detail page

### Alternative Flows

**A1: Validation Failed**
- 8a. If kode kurikulum already exists:
  - System shows error: "Kode kurikulum sudah digunakan"
  - Returns to step 5

**A2: Cancel**
- User can click "Batal" at any time
- System discards unsaved changes
- Returns to curriculum list

### Business Rules

- BR-K01: Kode kurikulum must be unique per prodi
- BR-K02: Tahun berlaku must be >= current year - 10
- BR-K03: Curriculum starts in 'draft' status

### UI Mockup Notes

```
┌─────────────────────────────────────────────────────────┐
│  Buat Kurikulum Baru                              [×]   │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  Kode Kurikulum *      [_____________]                  │
│                        (e.g., K2029)                    │
│                                                         │
│  Nama Kurikulum *      [_____________________________]  │
│                        (e.g., Kurikulum OBE 2029)       │
│                                                         │
│  Tahun Berlaku *       [____]                           │
│                        (e.g., 2029)                     │
│                                                         │
│  Deskripsi             [____________________________]   │
│                        [____________________________]   │
│                        [____________________________]   │
│                                                         │
│  SK Dokumen (optional) [Choose File] No file chosen    │
│                                                         │
│                        [Batal]  [Simpan]               │
└─────────────────────────────────────────────────────────┘
```

---

## UC-K02: Approve Curriculum

### Basic Information

| Attribute | Value |
|-----------|-------|
| **Use Case ID** | UC-K02 |
| **Use Case Name** | Approve Curriculum |
| **Actor** | Kaprodi, Dekan |
| **Priority** | HIGH |
| **Preconditions** | - Curriculum exists with status 'review'<br>- CPL and MK structure defined<br>- SK document uploaded |
| **Postconditions** | - Curriculum status changes to 'approved'<br>- Approval notification sent<br>- Ready for activation |

### Main Flow

1. Reviewer navigates to Kurikulum Approval queue
2. System displays list of curricula pending approval
3. Reviewer selects curriculum to review
4. System displays curriculum details:
   - Basic info (kode, nama, tahun)
   - CPL list (count and categories)
   - MK structure (total MK, total SKS)
   - SK document
5. Reviewer reviews all components
6. Reviewer clicks "Setujui"
7. System prompts for approval notes (optional)
8. Reviewer confirms approval
9. System validates:
   - At least 1 CPL defined ✅
   - At least 5 MK defined ✅
   - SK document uploaded ✅
10. System updates status to 'approved'
11. System records approver and approval date
12. System sends notification to curriculum creator
13. System logs audit trail
14. System displays success message

### Alternative Flows

**A1: Reject Curriculum**
- 6a. Reviewer clicks "Tolak"
- System prompts for rejection reason (required)
- System updates status to 'revised'
- System sends notification with rejection reason
- Creator can edit and resubmit

**A2: Request Revision**
- 6b. Reviewer clicks "Revisi"
- System prompts for revision notes
- Status remains 'review' but marked for revision
- Creator receives specific revision requests

**A3: Validation Failed**
- 9a. If validation fails:
  - System shows specific error messages
  - Cannot approve until requirements met

### Business Rules

- BR-K04: Curriculum must have min 1 CPL before approval
- BR-K05: Curriculum must have min 5 MK before approval
- BR-K06: SK document must be uploaded before approval
- BR-K07: Approval requires documented reason/notes

---

## UC-K03: Activate Curriculum

### Basic Information

| Attribute | Value |
|-----------|-------|
| **Use Case ID** | UC-K03 |
| **Use Case Name** | Activate Curriculum |
| **Actor** | Kaprodi |
| **Priority** | HIGH |
| **Preconditions** | - Curriculum status = 'approved'<br>- Current year >= tahun_berlaku |
| **Postconditions** | - Curriculum status = 'aktif'<br>- Available for student enrollment<br>- Can be set as primary curriculum |

### Main Flow

1. Kaprodi navigates to Curriculum Management
2. System displays approved curricula
3. Kaprodi selects curriculum to activate
4. System displays activation confirmation dialog
5. System shows:
   - Curriculum details
   - Impact analysis (existing active curricula)
   - Option to set as primary curriculum
6. Kaprodi confirms activation
7. System validates:
   - Status is 'approved' ✅
   - Current year >= tahun_berlaku ✅
8. System updates status to 'aktif'
9. If set as primary:
   - System removes 'is_primary' from other curricula
   - System sets 'is_primary = TRUE' for this curriculum
10. System sends notifications to all faculty
11. System logs audit trail
12. System displays success message

### Alternative Flows

**A1: Multiple Active Curricula**
- 5a. If other curricula already active:
  - System shows warning about parallel curricula
  - Requires explicit confirmation
  - Documents which angkatan uses which curriculum

**A2: Too Early to Activate**
- 7a. If current year < tahun_berlaku:
  - System shows error
  - Cannot activate yet

### Business Rules

- BR-K08: Only 1 curriculum can be 'primary' per prodi
- BR-K09: Multiple curricula can be 'aktif' simultaneously
- BR-K10: Curriculum can only be activated if approved

---

## UC-K04: Define CPL for Curriculum

### Basic Information

| Attribute | Value |
|-----------|-------|
| **Use Case ID** | UC-K04 |
| **Use Case Name** | Define CPL for Curriculum |
| **Actor** | Kaprodi |
| **Priority** | CRITICAL |
| **Preconditions** | - Curriculum exists (any status except 'arsip')<br>- User is Kaprodi of the prodi |
| **Postconditions** | - CPL added to curriculum<br>- CPL available for CPMK mapping |

### Main Flow

1. Kaprodi opens curriculum detail page
2. System displays curriculum info and CPL section
3. Kaprodi clicks "Tambah CPL"
4. System displays CPL creation form
5. Kaprodi inputs:
   - Kode CPL (e.g., "CPL-1")
   - Deskripsi lengkap
   - Kategori (dropdown):
     * Sikap
     * Pengetahuan
     * Keterampilan Umum
     * Keterampilan Khusus
   - Urutan (for ordering)
6. Kaprodi clicks "Simpan"
7. System validates:
   - Kode CPL unique within curriculum ✅
   - Deskripsi not empty ✅
   - Kategori selected ✅
8. System creates CPL record
9. System logs audit trail
10. System displays CPL in list
11. System shows success message

### Alternative Flows

**A1: Copy CPL from Previous Curriculum**
- 3a. Kaprodi clicks "Copy dari Kurikulum Lain"
- System shows dialog to select source curriculum
- Kaprodi selects source curriculum
- System displays CPL from source
- Kaprodi selects CPL to copy (multi-select)
- System copies selected CPL with option to edit
- Allows modifications before saving

**A2: Bulk Import CPL**
- 3b. Kaprodi clicks "Import CPL"
- System provides Excel template
- Kaprodi downloads template, fills data
- Kaprodi uploads filled Excel
- System validates and previews import
- Kaprodi confirms import
- System creates all CPL in batch

**A3: Edit Existing CPL**
- User can click "Edit" on existing CPL
- System shows edit form
- User modifies data
- System validates changes
- If CPL already mapped to CPMK:
  - System shows warning
  - Requires confirmation

**A4: Deactivate CPL**
- User clicks "Nonaktifkan" on CPL
- System checks if CPL is mapped to any CPMK
- If mapped:
  - System shows error: "Cannot deactivate, still mapped to CPMK"
- If not mapped:
  - System sets is_active = FALSE
  - CPL hidden from active list

### Business Rules

- BR-K11: CPL kode must be unique within curriculum
- BR-K12: Same CPL kode can exist in different curricula
- BR-K13: CPL cannot be deleted if mapped to CPMK
- BR-K14: Each curriculum should have 8-15 CPL (recommended)
- BR-K15: CPL must be distributed across all 4 categories

### Sample Data

```
CPL-1 (Sikap): Bertakwa kepada Tuhan Yang Maha Esa dan mampu menunjukkan 
               sikap religius

CPL-2 (Pengetahuan): Menguasai konsep teoretis sains alam, aplikasi 
                      matematika rekayasa, prinsip-prinsip rekayasa (engineering 
                      principles), sains rekayasa dan perancangan rekayasa

CPL-3 (Keterampilan Umum): Mampu menerapkan pemikiran logis, kritis, 
                             sistematis, dan inovatif dalam konteks pengembangan 
                             atau implementasi ilmu pengetahuan dan teknologi

CPL-4 (Keterampilan Khusus): Mampu merancang, mengimplementasi, mengevaluasi, 
                               dan meningkatkan sistem, proses, dan komponen 
                               sistem perangkat lunak
```

---

## UC-K05: Add Mata Kuliah to Curriculum

### Basic Information

| Attribute | Value |
|-----------|-------|
| **Use Case ID** | UC-K05 |
| **Use Case Name** | Add Mata Kuliah to Curriculum |
| **Actor** | Kaprodi |
| **Priority** | CRITICAL |
| **Preconditions** | - Curriculum exists<br>- User is Kaprodi |
| **Postconditions** | - MK added to curriculum structure<br>- MK available for RPS creation |

### Main Flow

1. Kaprodi opens curriculum detail page
2. System displays MK structure section
3. Kaprodi clicks "Tambah Mata Kuliah"
4. System displays MK creation form
5. Kaprodi inputs:
   - Kode MK (e.g., "IF101")
   - Nama MK Indonesia
   - Nama MK English
   - SKS (1-6)
   - Semester (1-14)
   - Rumpun MK
   - Jenis MK (Wajib/Pilihan/MKWU)
6. Kaprodi optionally sets prerequisites:
   - Selects MK prasyarat from same curriculum
   - Sets type (wajib/alternatif)
7. Kaprodi clicks "Simpan"
8. System validates:
   - Kode MK not empty ✅
   - SKS between 1-6 ✅
   - Semester between 1-14 ✅
   - Prerequisites are from same curriculum ✅
   - No circular dependency ✅
9. System creates MK record with composite key (kode_mk, id_kurikulum)
10. System creates prasyarat records if any
11. System logs audit trail
12. System displays MK in curriculum structure
13. System shows success message

### Alternative Flows

**A1: MK with Same Code Exists in Another Curriculum**
- 8a. System detects kode_mk exists in other curriculum
- System shows info message: "MK dengan kode sama sudah ada di kurikulum lain"
- System allows creation (different curricula allowed)
- User can optionally copy content from other curriculum's MK

**A2: Copy MK from Previous Curriculum**
- 3a. Kaprodi clicks "Copy dari Kurikulum Lain"
- System shows curriculum selector
- Kaprodi selects source curriculum
- System displays MK from source
- Kaprodi selects MK to copy (multi-select)
- System copies MK structure
- Automatically maps prerequisites if they exist
- Allows modifications before saving

**A3: Bulk Import MK**
- 3b. Kaprodi clicks "Import MK"
- System provides Excel template with columns:
  * kode_mk, nama_mk, sks, semester, rumpun, jenis, prasyarat
- Kaprodi fills and uploads Excel
- System validates all rows
- Shows validation report
- Kaprodi confirms import
- System creates all MK in batch

**A4: Edit Existing MK**
- User clicks "Edit" on MK
- System shows edit form
- User can modify all fields except kode_mk
- If MK has RPS or enrollments:
  - System shows impact warning
  - Changes may affect active classes
- Requires confirmation for major changes (SKS, semester)

**A5: Deactivate MK**
- User clicks "Nonaktifkan" on MK
- System checks if MK has:
  * Active RPS
  * Active classes
  * Enrolled students
- If any exist:
  - System prevents deactivation
  - Shows message: "MK masih digunakan"
- If none:
  - System sets is_active = FALSE
  - MK hidden from active list (soft delete)

**A6: Circular Dependency Detection**
- 8a. If prerequisite chain creates circular dependency:
  - System detects cycle (e.g., A → B → C → A)
  - Shows error: "Circular dependency detected"
  - Lists the cycle
  - Prevents save

### Business Rules

- BR-K16: Same kode_mk can exist in multiple curricula (different MK)
- BR-K17: MK cannot be hard deleted (soft delete only)
- BR-K18: Prerequisites must be from same curriculum
- BR-K19: Prerequisites must be from earlier semester
- BR-K20: No circular dependencies allowed
- BR-K21: Total SKS per semester should be 18-24 (recommended)

### Validation Rules

```python
def validate_prerequisites(kode_mk, id_kurikulum, prerequisites):
    """Validate prerequisites for a course"""
    
    # Get semester of this MK
    mk_semester = get_mk_semester(kode_mk, id_kurikulum)
    
    for prasyarat in prerequisites:
        # Check same curriculum
        if prasyarat.id_kurikulum != id_kurikulum:
            raise ValidationError("Prasyarat harus dari kurikulum yang sama")
        
        # Check earlier semester
        prasyarat_semester = get_mk_semester(prasyarat.kode_mk, id_kurikulum)
        if prasyarat_semester >= mk_semester:
            raise ValidationError("Prasyarat harus dari semester lebih awal")
        
        # Check circular dependency
        if has_circular_dependency(kode_mk, prasyarat.kode_mk, id_kurikulum):
            raise ValidationError("Circular dependency terdeteksi")
    
    return True
```

---

## UC-K06: Create MK Mapping Between Curricula

### Basic Information

| Attribute | Value |
|-----------|-------|
| **Use Case ID** | UC-K06 |
| **Use Case Name** | Create MK Mapping Between Curricula |
| **Actor** | Kaprodi |
| **Priority** | HIGH |
| **Preconditions** | - At least 2 curricula exist for prodi<br>- Both curricula have MK defined |
| **Postconditions** | - MK mapping created<br>- Available for grade conversion |

### Main Flow

1. Kaprodi navigates to "Pemetaan Kurikulum"
2. System displays mapping management page
3. Kaprodi clicks "Buat Pemetaan Baru"
4. System displays mapping form
5. Kaprodi selects:
   - Source curriculum (older)
   - Target curriculum (newer)
6. System displays MK from both curricula side-by-side
7. Kaprodi creates mapping:
   - Selects MK from source
   - Selects MK from target (or multiple)
   - Selects mapping type:
     * **Ekuivalen (100%)**: Completely equivalent
     * **Sebagian (50-99%)**: Partially equivalent
     * **Diganti**: Replaced by combination of new MK
     * **Dihapus**: No equivalent in new curriculum
   - Sets conversion weight (0-100%)
   - Adds notes/keterangan
8. Kaprodi clicks "Simpan Pemetaan"
9. System validates:
   - Source and target from different curricula ✅
   - MK exist in respective curricula ✅
   - Weight between 0-100 ✅
10. System creates mapping record
11. System logs audit trail
12. System displays mapping in list

### Alternative Flows

**A1: Auto-Suggest Mapping**
- 6a. System analyzes both curricula
- System suggests automatic mappings based on:
  * Same kode_mk
  * Similar nama_mk (fuzzy match)
  * Same SKS and semester
- Kaprodi can accept, reject, or modify suggestions

**A2: Bulk Mapping**
- 7a. Kaprodi selects multiple MK from source
- For each, selects target and type
- System validates all mappings
- Creates all in batch

**A3: One-to-Many Mapping**
- 7b. One source MK maps to multiple target MK
- Example: IF101 (old) → IF101A + IF101B (new)
- System creates separate mapping records
- Total weight can be > 100% (distributed)

**A4: View Existing Mappings**
- User can view all mappings between 2 curricula
- Display as matrix or list
- Can export to Excel

### Business Rules

- BR-K22: Mapping is directional (old → new)
- BR-K23: One MK can map to multiple MK (one-to-many)
- BR-K24: Multiple MK can map to one MK (many-to-one)
- BR-K25: Mapping is used for:
  * Transfer students
  * Curriculum transition
  * Grade conversion
  * RPL (Prior Learning Recognition)

### Use Case: Convert Student Grades

**Scenario:** Student transfers from angkatan 2024 (K2024) to angkatan 2029 (K2029)

```python
def convert_grades(nim, id_kurikulum_lama, id_kurikulum_baru):
    """Convert student grades when changing curriculum"""
    
    # Get all completed courses
    completed_mk = get_completed_courses(nim)
    
    converted_grades = []
    
    for mk in completed_mk:
        # Find mapping
        mapping = get_mk_mapping(
            mk.kode_mk, 
            id_kurikulum_lama, 
            id_kurikulum_baru
        )
        
        if mapping:
            if mapping.tipe == 'ekuivalen':
                # Direct conversion
                converted_grades.append({
                    'kode_mk_baru': mapping.kode_mk_baru,
                    'nilai': mk.nilai,
                    'sks': mk.sks
                })
            elif mapping.tipe == 'sebagian':
                # Partial credit
                converted_grades.append({
                    'kode_mk_baru': mapping.kode_mk_baru,
                    'nilai': mk.nilai * (mapping.bobot_konversi / 100),
                    'sks': mk.sks * (mapping.bobot_konversi / 100)
                })
            # ... handle other types
    
    return converted_grades
```

---

## UC-K07: Assign Student to Curriculum

### Basic Information

| Attribute | Value |
|-----------|-------|
| **Use Case ID** | UC-K07 |
| **Use Case Name** | Assign Student to Curriculum |
| **Actor** | Admin, System (automatic) |
| **Priority** | CRITICAL |
| **Preconditions** | - Student record exists<br>- Active curriculum exists for prodi |
| **Postconditions** | - Student assigned to curriculum<br>- Assignment is IMMUTABLE<br>- Student can only enroll in classes from assigned curriculum |

### Main Flow

1. System triggers on new student enrollment
2. System determines curriculum based on:
   - Student's angkatan
   - Prodi's active curricula
   - Business rules
3. System assigns student to appropriate curriculum
4. System validates assignment
5. System locks assignment (IMMUTABLE)
6. System logs audit trail
7. System sends notification to student with curriculum info

### Alternative Flows

**A1: Manual Assignment by Admin**
- Admin navigates to Student Management
- Selects student
- Views curriculum assignment section
- If not yet assigned:
  - Selects curriculum from active curricula
  - Confirms assignment
  - System performs validation
  - Assignment is locked

**A2: Bulk Assignment**
- Admin uploads student list with angkatan
- System auto-assigns based on rules
- Shows preview of assignments
- Admin confirms
- System assigns all in batch

**A3: Multiple Active Curricula**
- If multiple curricula active for same year:
  - System uses is_primary flag
  - Or asks admin to specify
  - Or uses latest activated curriculum

### Business Rules

- BR-K26: Student curriculum assignment is IMMUTABLE
- BR-K27: Assignment based on angkatan and active curricula
- BR-K28: Student can only take courses from their curriculum
- BR-K29: Assignment must be done before first enrollment

### Assignment Logic

```python
def assign_curriculum_to_student(nim, angkatan, id_prodi):
    """Auto-assign curriculum based on angkatan"""
    
    # Find active curricula for prodi
    curricula = get_active_curricula(id_prodi, angkatan)
    
    if len(curricula) == 0:
        raise Exception("No active curriculum found")
    
    if len(curricula) == 1:
        # Only one active curriculum
        id_kurikulum = curricula[0].id_kurikulum
    else:
        # Multiple active curricula
        # Use primary curriculum
        primary = [k for k in curricula if k.is_primary]
        if primary:
            id_kurikulum = primary[0].id_kurikulum
        else:
            # Use newest
            id_kurikulum = max(curricula, key=lambda k: k.tahun_berlaku).id_kurikulum
    
    # Assign to student
    update_student_curriculum(nim, id_kurikulum)
    
    # Log
    log_audit(f"Student {nim} assigned to curriculum {id_kurikulum}")
    
    return id_kurikulum
```

### Validation on Enrollment

```sql
-- Trigger to prevent enrollment in wrong curriculum
CREATE TRIGGER trigger_validate_enrollment_kurikulum
BEFORE INSERT ON enrollment
FOR EACH ROW
EXECUTE FUNCTION validate_enrollment_kurikulum();

-- Function
CREATE OR REPLACE FUNCTION validate_enrollment_kurikulum()
RETURNS TRIGGER AS $$
DECLARE
    v_kurikulum_mahasiswa INT;
    v_kurikulum_kelas INT;
BEGIN
    SELECT id_kurikulum INTO v_kurikulum_mahasiswa
    FROM mahasiswa WHERE nim = NEW.nim;
    
    SELECT id_kurikulum INTO v_kurikulum_kelas
    FROM kelas WHERE id_kelas = NEW.id_kelas;
    
    IF v_kurikulum_mahasiswa != v_kurikulum_kelas THEN
        RAISE EXCEPTION 'Student can only enroll in classes from their curriculum (BR-K04)';
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;
```

---

## UC-K08: Compare Curricula

### Basic Information

| Attribute | Value |
|-----------|-------|
| **Use Case ID** | UC-K08 |
| **Use Case Name** | Compare Curricula |
| **Actor** | Kaprodi, Dekan |
| **Priority** | MEDIUM |
| **Preconditions** | - At least 2 curricula exist for prodi |
| **Postconditions** | - Comparison report displayed/exported |

### Main Flow

1. User navigates to "Perbandingan Kurikulum"
2. System displays curriculum comparison tool
3. User selects 2 or more curricula to compare
4. User clicks "Bandingkan"
5. System generates comparison showing:
   - **Basic Info:**
     * Kode, nama, tahun berlaku
     * Status, jumlah mahasiswa
   - **CPL Comparison:**
     * CPL yang sama
     * CPL yang ditambah
     * CPL yang dihapus
     * CPL yang dimodifikasi
   - **MK Structure:**
     * Total MK, total SKS
     * MK yang sama
     * MK yang ditambah
     * MK yang dihapus
     * MK yang dimodifikasi (SKS, semester)
   - **Distribution:**
     * SKS per semester comparison
     * MK per semester comparison
6. User can export comparison to PDF/Excel

### Alternative Flows

**A1: Detailed MK Comparison**
- User clicks on specific MK
- System shows side-by-side comparison:
  * SKS changes
  * Semester placement
  * CPMK changes
  * Prerequisite changes

**A2: Visual Comparison**
- System displays visual charts:
  * Bar chart: Total CPL per kategori
  * Line chart: SKS distribution per semester
  * Pie chart: MK by jenis (wajib/pilihan)

### Sample Output

```
═══════════════════════════════════════════════════════════
PERBANDINGAN KURIKULUM
Program Studi: Teknik Informatika
═══════════════════════════════════════════════════════════

                  K2024              K2029
───────────────────────────────────────────────────────────
Tahun Berlaku     2024               2029
Status            Aktif              Aktif
Mahasiswa         523                156
───────────────────────────────────────────────────────────

CPL COMPARISON
───────────────────────────────────────────────────────────
Total CPL         10                 12
  - Sikap         2                  2    (sama)
  - Pengetahuan   3                  4    (+1)
  - Ketr. Umum    2                  2    (sama)
  - Ketr. Khusus  3                  4    (+1)

MATA KULIAH COMPARISON
───────────────────────────────────────────────────────────
Total MK          48                 52
Total SKS         144                148
  - Sama          44 MK
  - Ditambah      -                  8 MK
  - Dihapus       4 MK               -
  - Dimodifikasi  2 MK (perubahan SKS)

DISTRIBUSI SKS PER SEMESTER
───────────────────────────────────────────────────────────
Semester  K2024  K2029   Diff
   1       20     20      0
   2       20     21      +1
   3       18     19      +1
   ...

PERUBAHAN SIGNIFIKAN
───────────────────────────────────────────────────────────
1. [+] IF407 - Machine Learning (3 SKS, Semester 7)
2. [+] IF408 - Cloud Computing (3 SKS, Semester 7)
3. [-] IF301 - Matematika Lanjut (dihapus)
4. [Δ] IF205 - Basis Data (2 SKS → 3 SKS)

═══════════════════════════════════════════════════════════
```

---

## UC-K09: Deactivate Curriculum

### Basic Information

| Attribute | Value |
|-----------|-------|
| **Use Case ID** | UC-K09 |
| **Use Case Name** | Deactivate Curriculum |
| **Actor** | Kaprodi |
| **Priority** | MEDIUM |
| **Preconditions** | - Curriculum status = 'aktif'<br>- All students have active enrollment |
| **Postconditions** | - Curriculum status = 'non-aktif'<br>- No new students can be assigned<br>- Existing students continue |

### Main Flow

1. Kaprodi navigates to Curriculum Management
2. System displays list of curricula
3. Kaprodi selects curriculum to deactivate
4. System displays curriculum info and impact analysis:
   - Current students count
   - Active classes count
   - Estimated graduation timeline
5. Kaprodi clicks "Nonaktifkan"
6. System prompts for confirmation and reason
7. Kaprodi confirms
8. System validates:
   - Not the only active curriculum ✅
   - Has active students (OK, they continue) ✅
9. System updates status to 'non-aktif'
10. System logs audit trail with reason
11. System sends notification to relevant parties
12. System displays success message

### Alternative Flows

**A1: Last Active Curriculum**
- 8a. If this is the only active curriculum:
  - System shows error
  - Cannot deactivate (prodi must have at least 1 active curriculum)

**A2: No Students**
- 4a. If no students enrolled:
  - System shows option to directly archive
  - Skip 'non-aktif' status

### Business Rules

- BR-K30: Curriculum can be deactivated if students still enrolled
- BR-K31: At least 1 active curriculum must exist per prodi
- BR-K32: Deactivation prevents new student assignments
- BR-K33: Existing students continue with deactivated curriculum

---

## UC-K10: Archive Curriculum

### Basic Information

| Attribute | Value |
|-----------|-------|
| **Use Case ID** | UC-K10 |
| **Use Case Name** | Archive Curriculum |
| **Actor** | Admin, System (automatic) |
| **Priority** | LOW |
| **Preconditions** | - Curriculum status = 'non-aktif'<br>- No active students (all graduated/DO) |
| **Postconditions** | - Curriculum status = 'arsip'<br>- Historical data preserved<br>- Not visible in regular lists |

### Main Flow

1. System runs nightly job to check curricula
2. For each 'non-aktif' curriculum:
   - Check if any active students remain
   - If all students graduated/DO:
     - Mark for archival
3. System generates archival report
4. Admin reviews report
5. Admin confirms archival
6. System updates status to 'arsip'
7. System creates backup of all related data
8. System logs audit trail
9. Curriculum removed from regular views (available in archive)

### Alternative Flows

**A1: Manual Archive**
- Admin navigates to curriculum
- Clicks "Arsipkan"
- System validates no active students
- Confirms archival
- Executes archival process

### Business Rules

- BR-K34: Can only archive if all students graduated/DO
- BR-K35: Archived curricula preserved for historical records
- BR-K36: Archived curricula can be viewed but not modified
- BR-K37: Used for accreditation and audit purposes

---

## 📊 Curriculum Lifecycle State Diagram

```
┌──────────┐
│  Draft   │
└────┬─────┘
     │ submit
     ▼
┌──────────┐
│  Review  │◄────────┐
└────┬─────┘         │
     │ approve       │ request revision
     ▼               │
┌──────────┐         │
│ Approved │─────────┘
└────┬─────┘   reject
     │ activate
     ▼
┌──────────┐
│  Aktif   │ (accepting students)
└────┬─────┘
     │ deactivate
     ▼
┌──────────┐
│Non-Aktif │ (students still enrolled)
└────┬─────┘
     │ archive (when all students graduate)
     ▼
┌──────────┐
│  Arsip   │ (historical only)
└──────────┘
```

---

## 🎯 Success Criteria

| Criteria | Target |
|----------|--------|
| **Curriculum Creation Time** | < 30 minutes for basic structure |
| **CPL Definition** | 8-15 CPL per curriculum |
| **MK Structure** | 40-60 MK, 120-160 total SKS |
| **Approval Time** | < 2 weeks from submission |
| **Student Assignment** | 100% students assigned before first enrollment |
| **Data Integrity** | 0 enrollment violations (wrong curriculum) |

---

**END OF DOCUMENT**
