<?php
require_once '../core/auth.php';
require_once '../core/database.php';
redirect_if_not_logged_in();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $jumlah = floatval(str_replace('.', '', $_POST['jumlah']));
    
    $stmt = $conn->prepare("UPDATE laporan SET jumlah = ? WHERE id = ?");
    $stmt->bind_param("di", $jumlah, $id);
    
    if ($stmt->execute()) {
        $_SESSION['pesan'] = ['tipe' => 'success', 'isi' => 'Laporan berhasil diperbarui'];
    } else {
        $_SESSION['pesan'] = ['tipe' => 'danger', 'isi' => 'Gagal memperbarui laporan'];
    }
    
    $stmt->close();
}

header("Location: laporan.php");
exit;
?>