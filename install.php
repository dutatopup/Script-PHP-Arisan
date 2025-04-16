<?php
require_once 'core/database.php';

// Buat tabel
$queries = [
    "CREATE TABLE IF NOT EXISTS peserta (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama VARCHAR(100) NOT NULL,
        aktif BOOLEAN DEFAULT 1,
        tanggal_daftar TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS laporan (
        id INT AUTO_INCREMENT PRIMARY KEY,
        peserta_id INT NOT NULL,
        jenis_setor ENUM('cash', 'transfer', 'belum') NOT NULL,
        jumlah DECIMAL(10,2) NOT NULL,
        keterangan TEXT,
        tanggal_input TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (peserta_id) REFERENCES peserta(id)
    )",
    
    "CREATE TABLE IF NOT EXISTS pemenang (
        id INT AUTO_INCREMENT PRIMARY KEY,
        peserta_id INT NOT NULL,
        tanggal DATETIME NOT NULL,
        jumlah DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (peserta_id) REFERENCES peserta(id)
    )"
];

foreach ($queries as $query) {
    if ($conn->query($query) === TRUE) {
        echo "Tabel berhasil dibuat/ditemukan<br>";
    } else {
        echo "Error membuat tabel: " . $conn->error . "<br>";
    }
}

// Tambah admin default (username: admin, password: admin123)
// Catatan: Ini hanya untuk demo, di production gunakan password hashing
echo "Setup selesai. <a href='login.php'>Login</a>";