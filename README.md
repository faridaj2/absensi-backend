# Absensi Backend - SIKAP Darussalam 2

Backend API untuk aplikasi absensi guru, pegawai, dan siswa. Dibangun dengan Laravel 13 + Sanctum + SQLite/MySQL.

Frontend build (React/Vite) sudah disertakan di folder public/ sehingga repo ini siap di-pull langsung ke shared hosting.

## Fitur Utama

- Multi-tenant per instansi - data ter-scope otomatis via trait BelongsToInstansi.
- Absensi pegawai & guru - validasi GPS radius + jadwal jam masuk/pulang, status otomatis tepat_waktu / telat.
- Absensi siswa per jam pelajaran - guru bisa klaim slot mengajar guru lain yang telat, dan guru asli bisa ambil kembali slotnya (release).
- Jadwal kerja & jadwal pelajaran - per hari, per jam ke.
- Manajemen izin/sakit/alpa manual oleh admin.
- Laporan - rekap kehadiran, JP mengajar, matriks harian; cetak A/B.
- Monitor realtime - pantau guru/pegawai yang sudah absen & kelas yang sudah terabsen.
- Role: superadmin, admin, guru, pegawai.

## Stack

| Komponen | Versi |
|---|---|
| PHP | ^8.3 |
| Laravel | ^13.17 |
| Laravel Sanctum | ^4.3 |
| Database | SQLite (dev) / MySQL (prod) |
| Frontend (built) | React 19 + Vite 8 + Tailwind 4 |

## Instalasi Lokal

git clone git@github.com:faridaj2/absensi-backend.git
cd absensi-backend
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve

API: http://127.0.0.1:8000/api

## Deploy ke Shared Hosting

1. Clone repo ini ke hosting.
2. Salin .env.example ke .env, isi APP_URL, kredensial DB, SANCTUM_STATEFUL_DOMAINS.
3. Jalankan php artisan key:generate
4. Arahkan document root ke public/.
5. Jalankan php artisan migrate --force.
6. Jalankan php artisan config:cache dan php artisan route:cache.

Jika tidak bisa ubah document root, pindahkan isi public/ ke public_html/ dan sesuaikan path require di index.php.

## Akun Default (DatabaseSeeder)

| Role | Email | Password |
|---|---|---|
| Superadmin | superadmin@example.com | password |
| Admin | admin@example.com | password |
| Guru | guru@example.com | password |
| Pegawai | pegawai@example.com | password |

## Struktur

app/
  Http/Controllers/Api/
  Http/Requests/
  Http/Resources/
  Models/
  Services/
  Traits/BelongsToInstansi.php
database/migrations/
routes/api.php
public/ (document root + built frontend)

## Endpoint Utama

| Method | Path |
|---|---|
| POST | /api/login |
| GET | /api/me |
| POST | /api/absensi/pegawai |
| GET | /api/absensi-siswa/slot |
| POST | /api/absensi-siswa/{id}/claim |
| POST | /api/absensi-siswa/{id}/release |
| GET | /api/monitor/hari-ini |
| GET | /api/laporan/rekap-guru |

Lihat routes/api.php untuk lengkapnya.

## Testing

php artisan test

---

Dikembangkan untuk SMP Darussalam 2.
