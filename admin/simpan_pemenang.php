<?php
require_once '../core/auth.php';
require_once '../core/database.php';
redirect_if_not_logged_in();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    $nama = $conn->real_escape_string($_POST['nama']);
    
    // Dapatkan ID peserta berdasarkan nama
    $stmt = $conn->prepare("SELECT id FROM peserta WHERE nama = ?");
    $stmt->bind_param("s", $nama);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $peserta_id = $result->fetch_assoc()['id'];

        if ($_POST['aksi'] === 'terima') {
            // Simpan pemenang bulan ini, hadiah 0
            $stmt = $conn->prepare("INSERT INTO pemenang (peserta_id, tanggal, jumlah) VALUES (?, NOW(), 0)");
            $stmt->bind_param("i", $peserta_id);
            $stmt->execute();

            // Update hadiah pemenang bulan sebelumnya
            $bulan_lalu = date('n', strtotime('-1 month'));
            $tahun_lalu = date('Y', strtotime('-1 month'));

            // Hitung total setoran bulan ini
            $query_total = "SELECT SUM(jumlah) as total FROM laporan 
                WHERE jenis_setor IN ('cash', 'transfer') 
                AND MONTH(tanggal_input) = MONTH(CURRENT_DATE()) 
                AND YEAR(tanggal_input) = YEAR(CURRENT_DATE())";
            $result_total = $conn->query($query_total);
            $total_arisan = $result_total->fetch_assoc()['total'] ?? 0;

            // Update hadiah pemenang bulan lalu
            $update = $conn->prepare("UPDATE pemenang SET jumlah = ? 
                WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = ?");
            $update->bind_param("dii", $total_arisan, $bulan_lalu, $tahun_lalu);
            $update->execute();
            $update->close();

            $_SESSION['pesan'] = ['tipe' => 'success', 'isi' => 'Pemenang bulan ini berhasil disimpan. Hadiah bulan lalu sudah diupdate.'];
        } elseif ($_POST['aksi'] === 'tolak') {
            $_SESSION['pesan'] = ['tipe' => 'warning', 'isi' => 'Pemenang ditolak, silakan kocok ulang'];
        }
        
        $stmt->close();
    } else {
        $_SESSION['pesan'] = ['tipe' => 'danger', 'isi' => 'Peserta tidak ditemukan'];
    }
    
    // Hapus session pemenang sementara
    unset($_SESSION['pemenang_sementara']);
}

header("Location: pemenang.php");
exit;
?>