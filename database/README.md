# Database Management

This directory contains database migrations, seeders, and related scripts for the OBE System.

## Directory Structure

```
database/
├── migrations/           # SQL migration files
├── seeders/             # PHP seeder classes
│   └── DatabaseSeeder.php
├── seed.php             # Seeder runner script
└── README.md            # This file
```

## Database Seeding

### Prerequisites

1. Ensure PostgreSQL is running
2. Database is created (see main README)
3. Schema is applied (run the main SQL schema file)
4. `.env` file is configured with database credentials

### Running the Seeder

To populate the database with sample data:

```bash
# From project root
php database/seed.php

# Or from database directory
cd database
php seed.php
```

### What Gets Seeded

The seeder populates the following data:

#### Master Data
- **3 Fakultas**: FTI, FEB, FT
- **3 Program Studi**: Teknik Informatika, Sistem Informasi, Manajemen
- **6 Dosen**: Sample lecturers with different roles
- **4 Roles**: admin, kaprodi, dosen, mahasiswa

#### Curriculum Data
- **3 Kurikulum**: Including active (K2024) and archived (K2020)
- **9 CPL**: Program Learning Outcomes across 4 categories
  - Sikap (Attitude)
  - Pengetahuan (Knowledge)
  - Keterampilan Umum (General Skills)
  - Keterampilan Khusus (Specific Skills)
- **10 Mata Kuliah**: Courses from semester 1-4
- **6 Prasyarat**: Course prerequisites

#### Learning Plans
- **3 RPS**: Semester Learning Plans for key courses
- **12 CPMK**: Course Learning Outcomes
- **3 SubCPMK**: Sub-outcomes with indicators
- **CPMK-CPL Relations**: Mapping with contribution weights

#### Classes & Students
- **6 Kelas**: 2 classes (A, B) for 3 courses
- **50 Mahasiswa**: Students enrolled in K2024 curriculum
- **100+ Enrollment Records**: Student-class enrollments
- **10 Tugas Mengajar**: Teaching assignments for lecturers

#### Assessment Data
- **6 Jenis Penilaian**: Quiz, Tugas, Praktikum, UTS, UAS, Project
- **Template Penilaian**: Assessment templates per CPMK
- **Komponen Penilaian**: Actual assessment components per class
- **100+ Nilai Detail**: Student grades with automatic weighted calculation
- **200+ Ketercapaian CPMK**: CPMK achievement records

#### Supporting Data
- **10 Rencana Mingguan**: Weekly learning plans with JSONB data
- **4 Pustaka**: Reference books for courses
- **Ambang Batas**: Pass thresholds for courses
- **Users**: Login accounts for different roles

## Sample Login Credentials

After seeding, you can use these credentials to test the system:

| Role      | Username     | Password    | Description                    |
|-----------|--------------|-------------|--------------------------------|
| Admin     | admin        | admin123    | System Administrator           |
| Kaprodi   | kaprodi_tif  | kaprodi123  | Head of Study Program          |
| Dosen     | dosen1       | dosen123    | Lecturer (can view all data)   |
| Dosen     | dosen2       | dosen123    | Another lecturer               |
| Mahasiswa | 202401001    | mhs123      | Student 1                      |
| Mahasiswa | 202401002    | mhs123      | Student 2                      |

## Data Flow Demonstration

The seeded data demonstrates the complete OBE flow:

1. **Curriculum Setup**:
   - Fakultas → Prodi → Kurikulum → CPL → Mata Kuliah

2. **Learning Planning**:
   - RPS → CPMK → SubCPMK
   - CPMK mapped to CPL with contribution weights
   - Weekly learning plans with detailed activities

3. **Class Management**:
   - Classes created from RPS
   - Lecturers assigned to classes
   - Students enrolled in appropriate classes

4. **Assessment**:
   - Assessment templates defined per CPMK
   - Actual assessment components per class
   - Student grades recorded
   - CPMK achievement calculated
   - CPL achievement derived from CPMK

## Resetting Data

To reset and reseed the database:

```bash
# 1. Drop and recreate the database schema
psql -U postgres -d obe_system -f OBE-Database-Schema-v3-WITH-KURIKULUM.sql

# 2. Run the seeder
php database/seed.php
```

## Customizing Seed Data

To modify the seed data:

1. Edit `database/seeders/DatabaseSeeder.php`
2. Locate the relevant `seed*()` method
3. Modify the data arrays
4. Run the seeder again

Example:

```php
// In seedMahasiswa()
private function seedMahasiswa(): void
{
    // Change number of students
    for ($i = 1; $i <= 100; $i++) {  // Changed from 50 to 100
        // ... rest of code
    }
}
```

## Troubleshooting

### Connection Errors

If you see database connection errors:

1. Check `.env` file exists and has correct credentials
2. Verify PostgreSQL is running: `systemctl status postgresql`
3. Test connection: `psql -U your_user -d obe_system`

### Duplicate Data Errors

The seeder uses `ON CONFLICT DO NOTHING` for most inserts, so it's safe to run multiple times. However, if you want clean data:

```bash
# Clear all data (careful!)
psql -U postgres -d obe_system -c "TRUNCATE TABLE mahasiswa, enrollment, kelas, rps, cpmk, cpl, matakuliah, kurikulum, dosen, prodi, fakultas CASCADE;"

# Then reseed
php database/seed.php
```

### Foreign Key Errors

If you get foreign key constraint errors:

1. Ensure the database schema is up to date
2. Check that the `OBE-Database-Schema-v3-WITH-KURIKULUM.sql` was applied
3. Verify all extensions are enabled (uuid-ossp, pgcrypto)

## Development Notes

### Transaction Safety

The seeder runs all operations in a single transaction. If any error occurs, all changes are rolled back automatically.

### Performance

Seeding 50 students with full enrollment and assessment data takes approximately 2-3 seconds. For larger datasets, consider:

- Using batch inserts
- Temporarily disabling triggers
- Using COPY command for bulk data

### Future Enhancements

Potential improvements:

- [ ] Add command-line arguments for customization (e.g., number of students)
- [ ] Support for seeding specific tables only
- [ ] Import data from CSV files
- [ ] Generate random but realistic Indonesian names
- [ ] Add more diverse assessment scenarios
