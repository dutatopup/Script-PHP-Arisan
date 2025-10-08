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
    )",

    "CREATE TABLE IF NOT EXISTS admin (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        nama VARCHAR(100) NOT NULL
    )",

    "CREATE TABLE tempat (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_tempat VARCHAR(255) NOT NULL,
    bulan INT NOT NULL,
    tahun INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )"
];

foreach ($queries as $query) {
    if ($conn->query($query) === TRUE) {
        echo "Tabel berhasil dibuat/ditemukan<br>";
    } else {
        echo "Error membuat tabel: " . $conn->error . "<br>";
    }
}

// Tambah admin default jika belum ada
$username = 'admin';
$password = password_hash('admin1234', PASSWORD_DEFAULT); // gunakan hash untuk keamanan
$nama = 'Admin';

$stmt = $conn->prepare("SELECT id FROM admin WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    $stmt_insert = $conn->prepare("INSERT INTO admin (username, password, nama) VALUES (?, ?, ?)");
    $stmt_insert->bind_param("sss", $username, $password, $nama);
    if ($stmt_insert->execute()) {
        echo "Admin default berhasil dibuat.<br>";
    } else {
        echo "Gagal membuat admin default: " . $conn->error . "<br>";
    }
    $stmt_insert->close();
} else {
    echo "Admin default sudah ada.<br>";
}
$stmt->close();

// Setup selesai. <a href='login.php'>Login</a>