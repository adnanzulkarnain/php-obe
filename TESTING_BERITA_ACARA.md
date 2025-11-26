# Testing Guide - Berita Acara Perkuliahan System

## 📋 Test Checklist Overview

- [ ] Backend API Endpoints
- [ ] Frontend Components
- [ ] Integration Flow (End-to-End)
- [ ] Authorization & Permissions
- [ ] Error Handling
- [ ] Data Validation

---

## 🔧 Backend API Testing

### Prerequisites
```bash
# Ensure database migration has run
php migrate.php migrate

# Check database connection
php migrate.php status

# Start PHP development server (if needed)
php -S localhost:8000 -t public
```

### Test Environment Setup

#### 1. Get Authentication Token

```bash
# Login as Dosen
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "dosen1",
    "password": "password"
  }'

# Save token from response
TOKEN="your_jwt_token_here"
```

#### 2. Login as Kaprodi

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "kaprodi1",
    "password": "password"
  }'

KAPRODI_TOKEN="kaprodi_jwt_token_here"
```

---

## 🧪 API Endpoint Tests

### 1. Create Berita Acara (POST /api/realisasi-pertemuan)

**Test Case 1.1: Create with Valid Data**

```bash
curl -X POST http://localhost:8000/api/realisasi-pertemuan \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "id_kelas": 1,
    "tanggal_pelaksanaan": "2025-11-25",
    "materi_disampaikan": "Pengenalan OOP: Class, Object, Method",
    "metode_digunakan": "Ceramah, Diskusi, dan Praktik",
    "kendala": "",
    "catatan_dosen": "Mahasiswa antusias",
    "kehadiran": [
      {"nim": "2021001", "status": "hadir", "keterangan": ""},
      {"nim": "2021002", "status": "izin", "keterangan": "Sakit"}
    ]
  }'
```

**Expected Result:**
- ✅ Status: 201 Created
- ✅ Returns created berita acara with id_realisasi
- ✅ Status: 'draft'
- ✅ Kehadiran records created

**Test Case 1.2: Create with Missing Required Fields**

```bash
curl -X POST http://localhost:8000/api/realisasi-pertemuan \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "id_kelas": 1
  }'
```

**Expected Result:**
- ✅ Status: 400 Bad Request
- ✅ Error message: validation errors

**Test Case 1.3: Create with Future Date**

```bash
curl -X POST http://localhost:8000/api/realisasi-pertemuan \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "id_kelas": 1,
    "tanggal_pelaksanaan": "2026-12-31",
    "materi_disampaikan": "Test"
  }'
```

**Expected Result:**
- ✅ Status: 400 Bad Request
- ✅ Error: "Tanggal pelaksanaan tidak boleh di masa depan"

---

### 2. Get List Berita Acara (GET /api/realisasi-pertemuan)

**Test Case 2.1: Get by Dosen**

```bash
curl -X GET "http://localhost:8000/api/realisasi-pertemuan?id_dosen=D001" \
  -H "Authorization: Bearer $TOKEN"
```

**Expected Result:**
- ✅ Status: 200 OK
- ✅ Returns array of berita acara
- ✅ Only returns records created by D001

**Test Case 2.2: Get with Filters**

```bash
curl -X GET "http://localhost:8000/api/realisasi-pertemuan?id_kelas=1&status=draft" \
  -H "Authorization: Bearer $TOKEN"
```

**Expected Result:**
- ✅ Status: 200 OK
- ✅ Returns filtered results
- ✅ All records have status='draft'

**Test Case 2.3: Get with Date Range**

```bash
curl -X GET "http://localhost:8000/api/realisasi-pertemuan?id_dosen=D001&tanggal_dari=2025-11-01&tanggal_sampai=2025-11-30" \
  -H "Authorization: Bearer $TOKEN"
```

**Expected Result:**
- ✅ Status: 200 OK
- ✅ Returns records within date range

---

### 3. Get Berita Acara by ID (GET /api/realisasi-pertemuan/:id)

**Test Case 3.1: Get Existing Record**

```bash
curl -X GET http://localhost:8000/api/realisasi-pertemuan/1 \
  -H "Authorization: Bearer $TOKEN"
```

**Expected Result:**
- ✅ Status: 200 OK
- ✅ Returns full details including kehadiran
- ✅ Includes kehadiran_summary

**Test Case 3.2: Get Non-existent Record**

```bash
curl -X GET http://localhost:8000/api/realisasi-pertemuan/999999 \
  -H "Authorization: Bearer $TOKEN"
```

**Expected Result:**
- ✅ Status: 404 Not Found
- ✅ Error message: "Berita acara tidak ditemukan"

---

### 4. Update Berita Acara (PUT /api/realisasi-pertemuan/:id)

**Test Case 4.1: Update Draft**

```bash
curl -X PUT http://localhost:8000/api/realisasi-pertemuan/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "materi_disampaikan": "Updated material content"
  }'
```

**Expected Result:**
- ✅ Status: 200 OK
- ✅ Returns updated record
- ✅ Materi updated successfully

**Test Case 4.2: Update Submitted Record (Should Fail)**

```bash
# First submit
curl -X POST http://localhost:8000/api/realisasi-pertemuan/1/submit \
  -H "Authorization: Bearer $TOKEN"

# Then try to update
curl -X PUT http://localhost:8000/api/realisasi-pertemuan/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"materi_disampaikan": "Try to update"}'
```

**Expected Result:**
- ✅ Status: 400 Bad Request
- ✅ Error: "hanya dapat diubah jika berstatus draft atau ditolak"

**Test Case 4.3: Update Other Dosen's Record (Should Fail)**

```bash
# Use different dosen token
curl -X PUT http://localhost:8000/api/realisasi-pertemuan/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $OTHER_DOSEN_TOKEN" \
  -d '{"materi_disampaikan": "Unauthorized update"}'
```

**Expected Result:**
- ✅ Status: 403 Forbidden
- ✅ Error: "Anda tidak berwenang"

---

### 5. Submit for Verification (POST /api/realisasi-pertemuan/:id/submit)

**Test Case 5.1: Submit Valid Draft**

```bash
curl -X POST http://localhost:8000/api/realisasi-pertemuan/1/submit \
  -H "Authorization: Bearer $TOKEN"
```

**Expected Result:**
- ✅ Status: 200 OK
- ✅ Status changed to 'submitted'
- ✅ Returns updated record

**Test Case 5.2: Submit Without Materi (Should Fail)**

```bash
# Create minimal record first
curl -X POST http://localhost:8000/api/realisasi-pertemuan \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "id_kelas": 1,
    "tanggal_pelaksanaan": "2025-11-25"
  }'

# Try to submit (get ID from previous response)
curl -X POST http://localhost:8000/api/realisasi-pertemuan/2/submit \
  -H "Authorization: Bearer $TOKEN"
```

**Expected Result:**
- ✅ Status: 400 Bad Request
- ✅ Error: "Materi yang disampaikan wajib diisi"

**Test Case 5.3: Submit Already Submitted (Should Fail)**

```bash
curl -X POST http://localhost:8000/api/realisasi-pertemuan/1/submit \
  -H "Authorization: Bearer $TOKEN"
```

**Expected Result:**
- ✅ Status: 400 Bad Request
- ✅ Error: "hanya dapat disubmit jika berstatus draft atau ditolak"

---

### 6. Verify Berita Acara (POST /api/realisasi-pertemuan/:id/verify)

**Test Case 6.1: Approve by Kaprodi**

```bash
curl -X POST http://localhost:8000/api/realisasi-pertemuan/1/verify \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $KAPRODI_TOKEN" \
  -d '{
    "approved": true,
    "komentar": "Materi sesuai dengan RPS"
  }'
```

**Expected Result:**
- ✅ Status: 200 OK
- ✅ Status changed to 'verified'
- ✅ verified_by set to kaprodi ID
- ✅ verified_at timestamp set

**Test Case 6.2: Reject by Kaprodi**

```bash
curl -X POST http://localhost:8000/api/realisasi-pertemuan/2/verify \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $KAPRODI_TOKEN" \
  -d '{
    "approved": false,
    "komentar": "Materi tidak sesuai RPS. Perlu disesuaikan dengan topik minggu ke-3."
  }'
```

**Expected Result:**
- ✅ Status: 200 OK
- ✅ Status changed to 'rejected'
- ✅ komentar_kaprodi saved

**Test Case 6.3: Verify Without Kaprodi Role (Should Fail)**

```bash
curl -X POST http://localhost:8000/api/realisasi-pertemuan/1/verify \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"approved": true}'
```

**Expected Result:**
- ✅ Status: 403 Forbidden
- ✅ Error: Unauthorized

**Test Case 6.4: Verify Non-Submitted Record (Should Fail)**

```bash
# Create draft
curl -X POST http://localhost:8000/api/realisasi-pertemuan \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "id_kelas": 1,
    "tanggal_pelaksanaan": "2025-11-25",
    "materi_disampaikan": "Test"
  }'

# Try to verify (get ID from response)
curl -X POST http://localhost:8000/api/realisasi-pertemuan/3/verify \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $KAPRODI_TOKEN" \
  -d '{"approved": true}'
```

**Expected Result:**
- ✅ Status: 400 Bad Request
- ✅ Error: "hanya dapat diverifikasi jika berstatus submitted"

---

### 7. Get Pending Verifications (GET /api/realisasi-pertemuan/pending-verification)

**Test Case 7.1: Get as Kaprodi**

```bash
curl -X GET http://localhost:8000/api/realisasi-pertemuan/pending-verification \
  -H "Authorization: Bearer $KAPRODI_TOKEN"
```

**Expected Result:**
- ✅ Status: 200 OK
- ✅ Returns only submitted records
- ✅ Sorted by created_at (FIFO)

**Test Case 7.2: Get as Dosen (Should Fail)**

```bash
curl -X GET http://localhost:8000/api/realisasi-pertemuan/pending-verification \
  -H "Authorization: Bearer $TOKEN"
```

**Expected Result:**
- ✅ Status: 403 Forbidden

---

### 8. Compare with RPS (GET /api/realisasi-pertemuan/:id/compare-rps)

**Test Case 8.1: Compare with Linked RPS**

```bash
curl -X GET http://localhost:8000/api/realisasi-pertemuan/1/compare-rps \
  -H "Authorization: Bearer $TOKEN"
```

**Expected Result:**
- ✅ Status: 200 OK
- ✅ Returns comparison data
- ✅ Includes rencana_materi, rencana_metode
- ✅ Includes analysis object

**Test Case 8.2: Compare without RPS Link**

```bash
# Create without id_minggu
curl -X GET http://localhost:8000/api/realisasi-pertemuan/2/compare-rps \
  -H "Authorization: Bearer $TOKEN"
```

**Expected Result:**
- ✅ Status: 200 OK
- ✅ analysis.has_plan = false

---

### 9. Export PDF (GET /api/realisasi-pertemuan/:id/export-pdf)

**Test Case 9.1: Export Existing Record**

```bash
curl -X GET http://localhost:8000/api/realisasi-pertemuan/1/export-pdf \
  -H "Authorization: Bearer $TOKEN" \
  --output berita_acara.pdf
```

**Expected Result:**
- ✅ Status: 200 OK
- ✅ Content-Type: application/pdf
- ✅ PDF file downloaded
- ✅ Contains all details

---

### 10. Get Statistics (GET /api/dosen/:id_dosen/realisasi-statistics)

**Test Case 10.1: Get Dosen Statistics**

```bash
curl -X GET http://localhost:8000/api/dosen/D001/realisasi-statistics \
  -H "Authorization: Bearer $TOKEN"
```

**Expected Result:**
- ✅ Status: 200 OK
- ✅ Returns statistics object
- ✅ Contains counts for each status

---

## 🎨 Frontend Component Testing

### Manual UI Testing Checklist

#### Dosen Dashboard (/berita-acara)

- [ ] Page loads without errors
- [ ] Statistics cards display correctly
- [ ] List shows dosen's berita acara
- [ ] Filters work (status, date range)
- [ ] Status badges display correct color
- [ ] "Buat Berita Acara" button navigates correctly
- [ ] View button opens details
- [ ] Edit button shows for draft/rejected only
- [ ] Export PDF downloads file
- [ ] Empty state shows when no data

#### Berita Acara Form (/berita-acara/create)

**Create Mode:**
- [ ] Form loads with empty fields
- [ ] Kelas dropdown populated
- [ ] Date defaults to today, max=today
- [ ] Materi field is required
- [ ] Kehadiran section appears after selecting kelas
- [ ] Student list loads from kelas
- [ ] Can change attendance status (buttons work)
- [ ] Can add keterangan per student
- [ ] "Semua Hadir" quick action works
- [ ] "Semua Alpha" quick action works
- [ ] Search student works
- [ ] Filter by status works
- [ ] Summary statistics update real-time
- [ ] "Simpan Draft" saves without submit
- [ ] "Submit untuk Verifikasi" validates and submits
- [ ] Validation errors show correctly
- [ ] Success message on save
- [ ] Redirects to list after save

**Edit Mode (Draft):**
- [ ] Form loads with existing data
- [ ] All fields populated correctly
- [ ] Kelas dropdown disabled
- [ ] Kehadiran data loaded
- [ ] Can modify all fields
- [ ] Can save changes
- [ ] Can submit for verification

**Edit Mode (Rejected):**
- [ ] Rejection notice shows
- [ ] Kaprodi komentar displayed
- [ ] Can edit all fields
- [ ] Can resubmit

**Edit Mode (Submitted/Verified) - View Only:**
- [ ] Cannot edit notice shows
- [ ] Form is read-only
- [ ] Back button works

#### Kaprodi Verification Dashboard (/verifikasi-berita-acara)

- [ ] Page loads without errors
- [ ] Statistics cards show correct counts
- [ ] Pending list shows submitted records only
- [ ] Sort by tanggal submit works
- [ ] Review button opens modal
- [ ] Empty state when no pending

#### Verification Modal

**Detail Tab:**
- [ ] All lecture details display
- [ ] Materi shown correctly
- [ ] Metode shown correctly
- [ ] Kendala shown (if any)
- [ ] Catatan dosen shown (if any)
- [ ] Date formatted correctly

**Kehadiran Tab:**
- [ ] Student list displays
- [ ] Status icons correct
- [ ] Summary statistics correct
- [ ] Persentase kehadiran calculated
- [ ] Read-only mode (no editing)

**Comparison Tab:**
- [ ] RPS plan shows (if linked)
- [ ] Realisasi shows
- [ ] Analysis displayed
- [ ] Material match percentage shows
- [ ] Warning if no plan linked

**Verification Actions:**
- [ ] Approve/Reject buttons work
- [ ] Cannot submit without decision
- [ ] Komentar required for reject
- [ ] Komentar optional for approve
- [ ] Success message on verify
- [ ] Modal closes after submit
- [ ] List refreshes automatically

---

## 🔄 Integration Testing (End-to-End)

### Workflow Test 1: Happy Path

**Scenario: Dosen creates, submits, Kaprodi approves**

1. **Login as Dosen**
   - [ ] Login successful
   - [ ] Dashboard loads
   - [ ] "Berita Acara" menu visible

2. **Create Berita Acara**
   - [ ] Navigate to /berita-acara
   - [ ] Click "Buat Berita Acara"
   - [ ] Select kelas
   - [ ] Fill tanggal
   - [ ] Fill materi (required)
   - [ ] Fill metode
   - [ ] Mark attendance for all students
   - [ ] Save as draft
   - [ ] Redirects to list
   - [ ] New record visible with "Draft" status

3. **Submit for Verification**
   - [ ] Open draft record
   - [ ] Click "Submit untuk Verifikasi"
   - [ ] Success message
   - [ ] Status changes to "Menunggu Verifikasi"
   - [ ] Cannot edit anymore

4. **Login as Kaprodi**
   - [ ] Logout dosen
   - [ ] Login as kaprodi
   - [ ] "Verifikasi BA" menu visible

5. **Verify Berita Acara**
   - [ ] Navigate to /verifikasi-berita-acara
   - [ ] Pending count shows 1
   - [ ] Click "Review"
   - [ ] Modal opens with details
   - [ ] Review all tabs
   - [ ] Select "Terverifikasi"
   - [ ] Add optional comment
   - [ ] Submit
   - [ ] Success message
   - [ ] Record disappears from pending

6. **Check as Dosen**
   - [ ] Logout kaprodi
   - [ ] Login as dosen
   - [ ] Navigate to /berita-acara
   - [ ] Record shows "Terverifikasi" status
   - [ ] Cannot edit

### Workflow Test 2: Rejection Path

**Scenario: Dosen submits, Kaprodi rejects, Dosen fixes**

1. **Dosen Creates & Submits**
   - [ ] Create berita acara with minimal info
   - [ ] Submit for verification

2. **Kaprodi Rejects**
   - [ ] Login as kaprodi
   - [ ] Review submission
   - [ ] Select "Ditolak"
   - [ ] Add komentar (required): "Materi tidak sesuai RPS"
   - [ ] Submit
   - [ ] Success

3. **Dosen Fixes**
   - [ ] Login as dosen
   - [ ] See "Ditolak" status
   - [ ] Rejection komentar visible
   - [ ] Click edit
   - [ ] Fix materi
   - [ ] Resubmit
   - [ ] Status back to "Menunggu Verifikasi"

4. **Kaprodi Approves**
   - [ ] Login as kaprodi
   - [ ] Review again
   - [ ] Approve
   - [ ] Status "Terverifikasi"

### Workflow Test 3: PDF Export

**Scenario: Export berita acara at different statuses**

1. **Export Draft**
   - [ ] Login as dosen
   - [ ] Create draft
   - [ ] Click export PDF
   - [ ] PDF downloads
   - [ ] Contains all data
   - [ ] Status shows "Draft"

2. **Export Verified**
   - [ ] Create and get verified
   - [ ] Export PDF
   - [ ] PDF includes verification section
   - [ ] Kaprodi name shown
   - [ ] Verification date shown
   - [ ] Komentar shown (if any)

---

## 🔐 Authorization Testing

### Role-Based Access Control

**Test as Dosen:**
- [ ] Can access /berita-acara
- [ ] Can create berita acara
- [ ] Can only edit own records
- [ ] Can only edit draft/rejected
- [ ] Cannot access /verifikasi-berita-acara
- [ ] Cannot verify records

**Test as Kaprodi:**
- [ ] Cannot access /berita-acara (or sees all if admin)
- [ ] Can access /verifikasi-berita-acara
- [ ] Can view all submitted records
- [ ] Can verify/reject
- [ ] Cannot edit berita acara content

**Test as Admin:**
- [ ] Can access all pages
- [ ] Can perform all actions
- [ ] Full CRUD access

**Test as Mahasiswa:**
- [ ] Cannot access berita acara pages
- [ ] Gets 403 Forbidden

### Cross-User Testing

**Test Dosen A Cannot Edit Dosen B's Record:**
1. [ ] Login as Dosen A
2. [ ] Get berita acara ID from Dosen B
3. [ ] Try to access /berita-acara/{id}/edit
4. [ ] Should get 403 or redirect

---

## 🐛 Error Handling Testing

### Network Errors

- [ ] Test with server down
- [ ] Test with slow connection
- [ ] Test with timeout

### Validation Errors

- [ ] Submit empty form
- [ ] Submit with invalid date
- [ ] Submit with future date
- [ ] Submit without required fields

### State Errors

- [ ] Edit submitted record
- [ ] Submit without materi
- [ ] Verify non-submitted record
- [ ] Verify already verified

---

## ✅ Testing Summary Template

```
Date: _______________
Tester: _____________
Environment: ________

Backend API Tests: ___/60 passed
Frontend UI Tests: ___/50 passed
Integration Tests: ___/15 passed
Authorization Tests: ___/10 passed
Error Handling: ___/10 passed

Total: ___/145 passed

Critical Issues Found: ___
Medium Issues: ___
Minor Issues: ___

Notes:
_________________________________
_________________________________
_________________________________
```

---

## 📊 Performance Testing (Optional)

### Load Testing

```bash
# Test create endpoint with ab (Apache Bench)
ab -n 100 -c 10 -H "Authorization: Bearer $TOKEN" \
  -p create_payload.json \
  -T application/json \
  http://localhost:8000/api/realisasi-pertemuan

# Expected: < 500ms average response time
```

### Database Performance

```sql
-- Check query performance
EXPLAIN ANALYZE SELECT * FROM realisasi_pertemuan WHERE status = 'submitted';

-- Check index usage
SELECT * FROM pg_stat_user_indexes WHERE relname = 'realisasi_pertemuan';
```

---

## 🎯 Test Coverage Goals

- **Backend API**: 100% endpoint coverage
- **Business Logic**: All workflow states tested
- **Authorization**: All role combinations tested
- **UI Components**: All user interactions tested
- **Integration**: Complete workflows tested

---

## 📝 Bug Report Template

```markdown
## Bug Report

**Title:**
**Severity:** Critical / High / Medium / Low
**Component:** Backend API / Frontend / Integration
**Endpoint/Page:**

### Steps to Reproduce:
1.
2.
3.

### Expected Behavior:


### Actual Behavior:


### Screenshots/Logs:


### Environment:
- Browser/Tool:
- OS:
- Backend Version:
```
