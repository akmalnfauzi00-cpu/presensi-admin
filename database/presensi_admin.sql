-- =========================================================
-- Database: presensi_admin
-- Engine: MySQL (XAMPP)
-- Charset: utf8mb4
-- =========================================================

CREATE DATABASE IF NOT EXISTS presensi_admin
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE presensi_admin;

-- =========================================================
-- 1) users (Admin) - login username + password
-- =========================================================
CREATE TABLE IF NOT EXISTS users (
  id_user        CHAR(36) PRIMARY KEY,
  username       VARCHAR(50) NOT NULL UNIQUE,
  password_hash  VARCHAR(255) NOT NULL,
  nama           VARCHAR(100) NOT NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Seed admin default: username=admin, password=admin123 (hash dibikin nanti via PHP)
-- (biar aman, jangan hardcode hash di sini)
-- =========================================================
-- 2) guru (sesuai rancangan)
-- id_guru kamu pakai untuk NIP
-- =========================================================
CREATE TABLE IF NOT EXISTS guru (
  id_guru       VARCHAR(36) PRIMARY KEY,          -- NIP (sesuai keputusanmu)
  nama_guru     VARCHAR(100) NOT NULL,
  jenis_kelamin VARCHAR(20)  NOT NULL,            -- L / P
  alamat        TEXT         NULL,
  no_hp         VARCHAR(20)  NULL,
  email         VARCHAR(100) NULL,
  status_aktif  ENUM('AKTIF','CUTI','NONAKTIF') NOT NULL DEFAULT 'AKTIF', -- tambahan utk UI kamu
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================================
-- 3) kehadiran (tabel "Kehadiran" di rancangan: per tanggal & lokasi sekolah)
-- =========================================================
CREATE TABLE IF NOT EXISTS kehadiran (
  id_presensi  VARCHAR(36) PRIMARY KEY,
  tanggal      DATE NOT NULL,
  lokasi       VARCHAR(150) NULL,                 -- string alamat (opsional)
  lat_sekolah  DECIMAL(10,7) NULL,                -- tambahan: lat
  lng_sekolah  DECIMAL(10,7) NULL,                -- tambahan: lng
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_kehadiran_tanggal (tanggal)
) ENGINE=InnoDB;

-- =========================================================
-- 4) presensi_master (sesuai rancangan)
-- =========================================================
CREATE TABLE IF NOT EXISTS presensi_master (
  id_master        VARCHAR(36) PRIMARY KEY,
  jam_masuk        TIME NOT NULL,
  jam_pulang       TIME NOT NULL,
  batas_terlambat  TIME NOT NULL,
  minimal_reward   VARCHAR(10) NOT NULL DEFAULT '80', -- sesuai rancangan (minimal_reward)
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================================
-- 5) presensi_detail (sesuai rancangan + tambahan file foto + lokasi + terlambat)
-- status_kehadiran sesuai rancangan: Hadir / Izin / Sakit / Alfa
-- =========================================================
CREATE TABLE IF NOT EXISTS presensi_detail (
  id_detail        VARCHAR(36) PRIMARY KEY,
  id_presensi      VARCHAR(36) NOT NULL,
  id_guru          VARCHAR(36) NOT NULL,
  jam_masuk        TIME NULL,
  jam_keluar       TIME NULL,
  status_kehadiran VARCHAR(50) NOT NULL,          -- Hadir / Izin / Sakit / Alfa

  -- tambahan agar sesuai kebutuhan (wajib foto, geofence)
  foto_masuk_path  VARCHAR(255) NULL,
  foto_pulang_path VARCHAR(255) NULL,
  lat_masuk        DECIMAL(10,7) NULL,
  lng_masuk        DECIMAL(10,7) NULL,
  lat_pulang       DECIMAL(10,7) NULL,
  lng_pulang       DECIMAL(10,7) NULL,
  is_terlambat     TINYINT(1) NOT NULL DEFAULT 0,

  -- rancangan: reward & sp di detail (boleh tetap, tapi nanti lebih rapi di tabel dokumen)
  reward           VARCHAR(100) NULL,
  sp               VARCHAR(100) NULL,

  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT fk_detail_presensi
    FOREIGN KEY (id_presensi) REFERENCES kehadiran(id_presensi)
    ON DELETE CASCADE ON UPDATE CASCADE,

  CONSTRAINT fk_detail_guru
    FOREIGN KEY (id_guru) REFERENCES guru(id_guru)
    ON DELETE CASCADE ON UPDATE CASCADE,

  KEY idx_detail_tanggal (id_presensi),
  KEY idx_detail_guru (id_guru),
  KEY idx_detail_status (status_kehadiran)
) ENGINE=InnoDB;

-- =========================================================
-- 6) evaluasi_kehadiran (sesuai rancangan)
-- =========================================================
CREATE TABLE IF NOT EXISTS evaluasi_kehadiran (
  id_evaluasi   VARCHAR(36) PRIMARY KEY,
  id_guru       VARCHAR(36) NOT NULL,
  periode       VARCHAR(20) NOT NULL,             -- contoh: 2026-02
  persentase    FLOAT NOT NULL,
  status        ENUM('REWARD','PERINGATAN','NETRAL') NOT NULL DEFAULT 'NETRAL',
  keterangan    VARCHAR(255) NULL,
  tanggal       DATE NOT NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT fk_eval_guru
    FOREIGN KEY (id_guru) REFERENCES guru(id_guru)
    ON DELETE CASCADE ON UPDATE CASCADE,

  UNIQUE KEY uq_eval_guru_periode (id_guru, periode)
) ENGINE=InnoDB;

-- =========================================================
-- 7) setting (sesuai rancangan + lat/lng + radius)
-- lokasi_sekolah di rancangan berupa "koordinat sekolah"
-- =========================================================
CREATE TABLE IF NOT EXISTS setting (
  id_setting        VARCHAR(36) PRIMARY KEY,
  jam_masuk         TIME NOT NULL,
  jam_pulang        TIME NOT NULL,
  batas_terlambat   TIME NOT NULL,
  minimal_persentase VARCHAR(10) NOT NULL DEFAULT '80',
  lokasi_sekolah    VARCHAR(150) NULL,            -- boleh string/alamat
  lat_sekolah       DECIMAL(10,7) NULL,
  lng_sekolah       DECIMAL(10,7) NULL,
  radius_absen      VARCHAR(10) NOT NULL DEFAULT '150',
  created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================================
-- 8) laporan (sesuai rancangan) + file path pdf (tambahan)
-- =========================================================
CREATE TABLE IF NOT EXISTS laporan (
  id_laporan       VARCHAR(36) PRIMARY KEY,
  periode          VARCHAR(20) NOT NULL,          -- 2026-01
  tanggal_generate DATETIME NOT NULL,
  dibuat_oleh      VARCHAR(36) NOT NULL,          -- FK ke users
  keterangan       TEXT NULL,
  file_pdf_path    VARCHAR(255) NULL,             -- tambahan: path PDF laporan
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT fk_laporan_user
    FOREIGN KEY (dibuat_oleh) REFERENCES users(id_user)
    ON DELETE RESTRICT ON UPDATE CASCADE,

  UNIQUE KEY uq_laporan_periode (periode)
) ENGINE=InnoDB;

-- =========================================================
-- 9) izin_sakit (tambahan untuk approval admin)
-- karena kamu bilang izin/sakit perlu approve, tanpa lampiran
-- =========================================================
CREATE TABLE IF NOT EXISTS izin_sakit (
  id_izin       VARCHAR(36) PRIMARY KEY,
  id_detail     VARCHAR(36) NULL,                 -- opsional: terkait presensi_detail
  id_guru       VARCHAR(36) NOT NULL,
  tanggal       DATE NOT NULL,
  jenis         ENUM('IZIN','SAKIT') NOT NULL,
  alasan        VARCHAR(255) NULL,
  status        ENUM('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
  diproses_oleh VARCHAR(36) NULL,                 -- admin users.id_user
  diproses_pada DATETIME NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT fk_izin_guru
    FOREIGN KEY (id_guru) REFERENCES guru(id_guru)
    ON DELETE CASCADE ON UPDATE CASCADE,

  CONSTRAINT fk_izin_admin
    FOREIGN KEY (diproses_oleh) REFERENCES users(id_user)
    ON DELETE SET NULL ON UPDATE CASCADE,

  CONSTRAINT fk_izin_detail
    FOREIGN KEY (id_detail) REFERENCES presensi_detail(id_detail)
    ON DELETE SET NULL ON UPDATE CASCADE,

  KEY idx_izin_tanggal (tanggal),
  KEY idx_izin_status (status)
) ENGINE=InnoDB;

-- =========================================================
-- 10) reward_sp_dokumen (tambahan untuk upload PDF reward/SP)
-- sesuai kebutuhan kamu: dokumen PDF + status diunduh
-- =========================================================
CREATE TABLE IF NOT EXISTS reward_sp_dokumen (
  id_dokumen     VARCHAR(36) PRIMARY KEY,
  id_guru        VARCHAR(36) NOT NULL,
  periode        VARCHAR(20) NOT NULL,            -- 2026-02
  jenis          ENUM('REWARD','SP') NOT NULL,
  deskripsi      VARCHAR(255) NULL,
  file_pdf_path  VARCHAR(255) NOT NULL,
  status_unduh   ENUM('BELUM_DIUNDUH','SUDAH_DIUNDUH') NOT NULL DEFAULT 'BELUM_DIUNDUH',
  dibuat_oleh    VARCHAR(36) NOT NULL,            -- admin users.id_user
  dibuat_pada    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_doc_guru
    FOREIGN KEY (id_guru) REFERENCES guru(id_guru)
    ON DELETE CASCADE ON UPDATE CASCADE,

  CONSTRAINT fk_doc_admin
    FOREIGN KEY (dibuat_oleh) REFERENCES users(id_user)
    ON DELETE RESTRICT ON UPDATE CASCADE,

  KEY idx_doc_guru (id_guru),
  KEY idx_doc_periode (periode)
) ENGINE=InnoDB;