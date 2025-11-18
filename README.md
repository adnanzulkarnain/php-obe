# Sistem Informasi Kurikulum OBE

Sistem Informasi Kurikulum berbasis Outcome-Based Education (OBE) dengan Multi-Curriculum Support untuk perguruan tinggi.

## 🎯 Fitur Utama

- ✅ **Multi-Curriculum Support** - Kelola beberapa kurikulum secara bersamaan
- ✅ **CPL & CPMK Management** - Definisi dan pemetaan capaian pembelajaran
- ✅ **RPS Digital** - Pembuatan dan approval RPS elektronik
- ✅ **Sistem Penilaian Otomatis** - Perhitungan ketercapaian CPMK & CPL
- ✅ **Analytics Dashboard** - Monitoring dan pelaporan OBE compliance
- ✅ **Audit Trail** - Logging lengkap untuk akreditasi

## 🛠️ Technology Stack

- **Backend**: PHP 8.3
- **Database**: PostgreSQL 14+
- **Authentication**: JWT
- **Architecture**: Clean Architecture (Repository Pattern, Service Layer)

## 📋 Prerequisites

- PHP >= 8.3
- PostgreSQL >= 14
- Composer
- Apache/Nginx with mod_rewrite

## 🚀 Installation

### 1. Clone Repository

```bash
git clone <repository-url>
cd php-obe
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Setup Environment

```bash
cp .env.example .env
```

Edit `.env` file dengan konfigurasi database Anda:

```env
DB_HOST=localhost
DB_PORT=5432
DB_NAME=obe_system
DB_USER=obe_user
DB_PASSWORD=your_password

JWT_SECRET=your_secret_key_here
```

### 4. Setup Database

```bash
# Create database
createdb obe_system

# Execute schema
psql -d obe_system -f OBE-Database-Schema-v3-WITH-KURIKULUM.sql
```

### 5. Create Storage Directories

```bash
mkdir -p storage/uploads
mkdir -p logs
chmod -R 775 storage logs
```

### 6. Configure Web Server

#### Apache

Create virtual host:

```apache
<VirtualHost *:80>
    ServerName obe-system.local
    DocumentRoot /path/to/php-obe/public

    <Directory /path/to/php-obe/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/obe-error.log
    CustomLog ${APACHE_LOG_DIR}/obe-access.log combined
</VirtualHost>
```

#### Nginx

```nginx
server {
    listen 80;
    server_name obe-system.local;
    root /path/to/php-obe/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 7. Start Development Server

Alternatively, you can use PHP built-in server for development:

```bash
php -S localhost:8000 -t public
```

## 📚 API Documentation

### Base URL

```
http://localhost:8000/api
```

### Authentication

All protected endpoints require Bearer token in Authorization header:

```
Authorization: Bearer <your_jwt_token>
```

### Endpoints

#### Authentication

- `POST /api/auth/login` - Login
- `POST /api/auth/logout` - Logout
- `GET /api/auth/profile` - Get user profile
- `POST /api/auth/change-password` - Change password

#### Kurikulum Management

- `GET /api/kurikulum` - Get all kurikulum
- `GET /api/kurikulum/:id` - Get kurikulum detail
- `POST /api/kurikulum` - Create kurikulum (UC-K01)
- `POST /api/kurikulum/:id/approve` - Approve kurikulum (UC-K02)
- `POST /api/kurikulum/:id/activate` - Activate kurikulum (UC-K03)
- `POST /api/kurikulum/:id/deactivate` - Deactivate kurikulum (UC-K09)
- `GET /api/kurikulum/compare?ids=1,2` - Compare kurikulum (UC-K08)

#### CPL Management

- `GET /api/cpl?id_kurikulum=1` - Get CPL by kurikulum
- `POST /api/cpl` - Create CPL (UC-K04)
- `PUT /api/cpl/:id` - Update CPL
- `DELETE /api/cpl/:id` - Deactivate CPL

#### Mata Kuliah Management

- `GET /api/matakuliah?id_kurikulum=1` - Get MK by kurikulum
- `POST /api/matakuliah` - Create MK (UC-K05)
- `PUT /api/matakuliah/:kode_mk/:id_kurikulum` - Update MK
- `DELETE /api/matakuliah/:kode_mk/:id_kurikulum` - Deactivate MK

### Example Request

```bash
# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "kaprodi",
    "password": "password123"
  }'

# Create Kurikulum
curl -X POST http://localhost:8000/api/kurikulum \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token>" \
  -d '{
    "id_prodi": "TIF",
    "kode_kurikulum": "K2024",
    "nama_kurikulum": "Kurikulum OBE 2024",
    "tahun_berlaku": 2024,
    "deskripsi": "Kurikulum berbasis OBE"
  }'
```

## 🏗️ Project Structure

```
php-obe/
├── public/              # Entry point
│   ├── index.php
│   └── .htaccess
├── src/
│   ├── Config/          # Configuration
│   │   └── Database.php
│   ├── Core/            # Core classes
│   │   ├── Router.php
│   │   ├── Request.php
│   │   ├── Response.php
│   │   └── BaseRepository.php
│   ├── Middleware/      # Middleware
│   │   ├── AuthMiddleware.php
│   │   └── CorsMiddleware.php
│   ├── Entity/          # Entity models
│   │   ├── Kurikulum.php
│   │   ├── CPL.php
│   │   └── MataKuliah.php
│   ├── Repository/      # Data access layer
│   │   ├── KurikulumRepository.php
│   │   ├── CPLRepository.php
│   │   └── MataKuliahRepository.php
│   ├── Service/         # Business logic
│   │   ├── KurikulumService.php
│   │   ├── CPLService.php
│   │   └── MataKuliahService.php
│   ├── Controller/      # HTTP controllers
│   │   ├── AuthController.php
│   │   ├── KurikulumController.php
│   │   ├── CPLController.php
│   │   └── MataKuliahController.php
│   ├── Utils/           # Helper functions
│   │   └── JWTHelper.php
│   └── routes.php       # Route definitions
├── storage/
│   └── uploads/         # Uploaded files
├── logs/                # Application logs
├── .env.example
├── composer.json
└── README.md
```

## 🧪 Testing

Run PHP Code Sniffer:

```bash
composer cs
```

Run unit tests (when available):

```bash
composer test
```

## 🔒 Security

- All passwords are hashed using bcrypt
- JWT tokens expire after 2 hours
- CSRF protection enabled
- SQL injection prevention via prepared statements
- XSS prevention via input sanitization
- All write operations are logged in audit trail

## 📝 Business Rules

Key business rules enforced by the system:

- **BR-K01**: Mahasiswa mengikuti satu kurikulum sepanjang studi (IMMUTABLE)
- **BR-K02**: MK dengan kode sama di kurikulum berbeda = MK berbeda
- **BR-K03**: MK tidak dapat dihapus dari kurikulum (soft delete only)
- **BR-K04**: Mahasiswa hanya bisa ambil MK dari kurikulumnya
- **BR-K05**: Support 2+ kurikulum berjalan paralel
- **BR-K06**: Pemetaan MK antar kurikulum untuk konversi
- **BR-K07**: CPL terikat ke kurikulum (bisa berbeda antar kurikulum)

## 👥 User Roles

- **Admin**: System administration
- **Kaprodi**: Manage kurikulum, approve RPS, analytics
- **Dosen**: Create RPS, input nilai, manage kelas
- **Mahasiswa**: View RPS, check nilai, track progress

## 📄 License

This project is proprietary software. All rights reserved.

## 👨‍💻 Development Team

For support and questions, please contact the development team.

---

**Version**: 1.0.0
**Last Updated**: November 2024
**Status**: Development
