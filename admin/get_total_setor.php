<?php
require_once '../core/database.php';

$result = $conn->query("SELECT 
    SUM(CASE WHEN jenis_setor = 'cash' THEN jumlah ELSE 0 END) as cash,
    SUM(CASE WHEN jenis_setor = 'transfer' THEN jumlah ELSE 0 END) as transfer,
    SUM(CASE WHEN jenis_setor = 'belum' THEN jumlah ELSE 0 END) as belum
    FROM laporan
    WHERE MONTH(tanggal_input) = MONTH(CURRENT_DATE())");

header('Content-Type: application/json');
echo json_encode($result->fetch_assoc());
?>