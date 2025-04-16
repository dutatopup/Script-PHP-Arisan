<?php
require_once '../core/auth.php';
require_once '../core/database.php';
redirect_if_not_logged_in();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    $nama = $conn->real_escape_string($_POST['nama']);
    $jumlah = floatval($_POST['jumlah']);
    
    // Dapatkan ID peserta berdasarkan nama
    $stmt = $conn->prepare("SELECT id FROM peserta WHERE nama = ?");
    $stmt->bind_param("s", $nama);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $peserta_id = $result->fetch_assoc()['id'];
        
        if ($_POST['aksi'] === 'terima') {
            // Simpan sebagai pemenang
            $stmt = $conn->prepare("INSERT INTO pemenang (peserta_id, tanggal, jumlah) VALUES (?, NOW(), ?)");
            $stmt->bind_param("id", $peserta_id, $jumlah);
            
            if ($stmt->execute()) {
                $_SESSION['pesan'] = ['tipe' => 'success', 'isi' => 'Pemenang berhasil disimpan'];
            } else {
                $_SESSION['pesan'] = ['tipe' => 'danger', 'isi' => 'Gagal menyimpan pemenang'];
            }
        } elseif ($_POST['aksi'] === 'tolak') {
            $_SESSION['pesan'] = ['tipe' => 'warning', 'isi' => 'Pemenang ditolak, silakan kocok ulang'];
        }
        
        $stmt->close();
    } else {
        $_SESSION['pesan'] = ['tipe' => 'danger', 'isi' => 'Peserta tidak ditemukan'];
    }
}

header("Location: pemenang.php");
exit;
?>