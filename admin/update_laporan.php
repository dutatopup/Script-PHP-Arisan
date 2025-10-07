<?php
require_once '../core/auth.php';
require_once '../core/database.php';
redirect_if_not_logged_in();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $jenis_setor = trim($_POST['jenis_setor']);
    
    // Jika jenis setor adalah "belum", set jumlah menjadi 0
    if ($jenis_setor === 'belum') {
        $jumlah = 0;
    } else {
        $jumlah = floatval(str_replace('.', '', $_POST['jumlah']));
    }

    // Update dua kolom sekaligus: jumlah dan jenis_setor
    $stmt = $conn->prepare("UPDATE laporan SET jumlah = ?, jenis_setor = ? WHERE id = ?");
    $stmt->bind_param("dsi", $jumlah, $jenis_setor, $id);
    
    if ($stmt->execute()) {
        $_SESSION['pesan'] = ['tipe' => 'success', 'isi' => 'Laporan berhasil diperbarui'];
    } else {
        $_SESSION['pesan'] = ['tipe' => 'danger', 'isi' => 'Gagal memperbarui laporan: ' . $conn->error];
    }
    
    $stmt->close();
} else {
    $_SESSION['pesan'] = ['tipe' => 'danger', 'isi' => 'Permintaan tidak valid'];
}

header("Location: laporan.php");
exit;
?>