<?php
require_once 'config.php';

// Koneksi database
$host = 'localhost';
$user = 'root';
$pass = 'root';
$dbname = 'arisan_db';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}