<?php
require_once '../core/auth.php';
redirect_if_not_logged_in();

include '../core/header.php';
?>

<h2 class="mb-4">Dashboard Arisan</h2>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h5 class="card-title">Total Peserta</h5>
                <?php
                $query = "SELECT COUNT(*) as total FROM peserta WHERE aktif = 1";
                $result = $conn->query($query);
                $total = $result->fetch_assoc()['total'];
                ?>
                <h2 class="card-text"><?= $total ?></h2>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h5 class="card-title">Pemenang Bulan Ini</h5>
                <?php
                $query = "SELECT p.nama FROM pemenang pm 
                          JOIN peserta p ON pm.peserta_id = p.id 
                          WHERE MONTH(pm.tanggal) = MONTH(CURRENT_DATE()) 
                          AND YEAR(pm.tanggal) = YEAR(CURRENT_DATE())";
                $result = $conn->query($query);
                $pemenang = $result->fetch_assoc();
                ?>
                <h2 class="card-text"><?= $pemenang ? $pemenang['nama'] : 'Belum ada' ?></h2>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card text-white bg-info">
            <div class="card-body">
                <h5 class="card-title">Total Setoran</h5>
                <?php
                $query = "SELECT SUM(jumlah) as total FROM laporan 
                          WHERE jenis_setor IN ('cash', 'transfer') 
                          AND MONTH(tanggal_input) = MONTH(CURRENT_DATE())";
                $result = $conn->query($query);
                $total = $result->fetch_assoc()['total'] ?? 0;
                ?>
                <h2 class="card-text">Rp <?= number_format($total, 0, ',', '.') ?></h2>
            </div>
        </div>
    </div>
</div>

<?php include '../core/footer.php'; ?>