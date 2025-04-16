CREATE DATABASE IF NOT EXISTS arisan_db;
USE arisan_db;

CREATE TABLE IF NOT EXISTS peserta_arisan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  aktif BOOLEAN DEFAULT TRUE,
  tanggal_daftar TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS laporan_arisan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  peserta_id INT NOT NULL,
  jenis_setor ENUM('cash', 'transfer', 'belum') NOT NULL,
  jumlah DECIMAL(10,2) NOT NULL,
  keterangan TEXT,
  tanggal_input TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (peserta_id) REFERENCES peserta_arisan(id)
);

CREATE TABLE IF NOT EXISTS pemenang_arisan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  peserta_id INT NOT NULL,
  hari VARCHAR(20),
  tanggal DATE NOT NULL,
  jam TIME NOT NULL,
  jumlah DECIMAL(10,2) NOT NULL,
  status ENUM('terima', 'tolak', 'pending') DEFAULT 'pending',
  waktu_input TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (peserta_id) REFERENCES peserta_arisan(id)
);