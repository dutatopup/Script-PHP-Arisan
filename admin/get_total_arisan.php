<?php
require_once '../core/database.php';

// Hitung total setoran CASH + TRANSFER bulan ini
$query_total = "SELECT SUM(jumlah) as total_arisan FROM laporan 
               WHERE jenis_setor IN ('cash', 'transfer') 
               AND MONTH(tanggal_input) = MONTH(CURRENT_DATE())";
$result_total = $conn->query($query_total);
$total_arisan = $result_total->fetch_assoc()['total_arisan'] ?? 0;

// Hitung jumlah peserta UNIK yang sudah setor (cash/transfer)
$query_peserta = "SELECT COUNT(DISTINCT peserta_id) as jumlah_peserta 
                 FROM laporan 
                 WHERE jenis_setor IN ('cash', 'transfer') 
                 AND MONTH(tanggal_input) = MONTH(CURRENT_DATE())";
$result_peserta = $conn->query($query_peserta);
$jumlah_peserta = $result_peserta->fetch_assoc()['jumlah_peserta'] ?? 0;

header('Content-Type: application/json');
echo json_encode([
    'total_arisan' => (float)$total_arisan,
    'jumlah_peserta' => (int)$jumlah_peserta
]);
?>