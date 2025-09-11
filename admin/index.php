<?php
require_once '../core/auth.php';
redirect_if_not_logged_in();

include '../core/header.php';
include '../core/indotime.php'
?>

<h2 class="mb-4">Dashboard Arisan</h2>
<p class="mb-3"><?= $tanggal_hari_ini ?></p>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h5 class="card-title">Total Peserta Aktif</h5>
                <?php
                $query = "SELECT COUNT(*) as total FROM peserta WHERE aktif = 1";
                $result = $conn->query($query);
                $total = $result->fetch_assoc()['total'];
                ?>
                <h2 class="card-text"><?= $total ?> Orang</h2>
                <p class="mb-0"><strong>Billman Kroya 04</strong></p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h5 class="card-title">Pemenang Bulan Ini</h5>
                <?php
                $query = "SELECT p.nama, pm.tanggal FROM pemenang pm 
                          JOIN peserta p ON pm.peserta_id = p.id 
                          WHERE MONTH(pm.tanggal) = MONTH(CURRENT_DATE()) 
                          AND YEAR(pm.tanggal) = YEAR(CURRENT_DATE())";
                $result = $conn->query($query);
                $pemenang = $result->fetch_assoc();
                ?>
                <?php if ($pemenang): 
                    $bulan = date('F', strtotime($pemenang['tanggal']));
                    $tanggal = date('d', strtotime($pemenang['tanggal']));
                    $tahun = date('Y', strtotime($pemenang['tanggal']));
                ?>
                    <h2 class="card-text"><?= $pemenang['nama'] ?></h2>
                    <!-- <p class="mb-0">Bulan Menang: <strong><?= $bulan ?></strong></p> -->
                    <p class="mb-0"><strong><?= $tanggal ?> <?= $bulan ?> <?= $tahun ?></strong></p>
                <?php else: ?>
                    <h2 class="card-text">Belum ada</h2>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card text-white bg-danger">
            <div class="card-body">
                <h5 class="card-title">Total Setoran</h5>
                <?php
                // Total setoran bulan ini
                $query = "SELECT SUM(jumlah) as total FROM laporan 
                          WHERE jenis_setor IN ('cash', 'transfer') 
                          AND MONTH(tanggal_input) = MONTH(CURRENT_DATE()) 
                          AND YEAR(tanggal_input) = YEAR(CURRENT_DATE())";
                $result = $conn->query($query);
                $total = $result->fetch_assoc()['total'] ?? 0;

                // Ambil tanggal setoran terakhir bulan ini
                $query_tgl = "SELECT tanggal_input FROM laporan 
                              WHERE jenis_setor IN ('cash', 'transfer') 
                              AND MONTH(tanggal_input) = MONTH(CURRENT_DATE()) 
                              AND YEAR(tanggal_input) = YEAR(CURRENT_DATE()) 
                              ORDER BY tanggal_input DESC LIMIT 1";
                $result_tgl = $conn->query($query_tgl);
                $tgl_setoran = $result_tgl->fetch_assoc()['tanggal_input'] ?? null;

                if ($tgl_setoran) {
                    $tanggal_setoran = date('d', strtotime($tgl_setoran));
                    $bulan_setoran = date('F', strtotime($tgl_setoran));
                    $tahun_setoran = date('Y', strtotime($tgl_setoran));
                    $tgl_label = "$tanggal_setoran $bulan_setoran $tahun_setoran";
                } else {
                    $tgl_label = "Belum ada setoran";
                }
                ?>
                <h2 class="card-text">Rp <?= number_format($total, 0, ',', '.') ?></h2>
                <p class="mb-0"><strong><?= $tgl_label ?></strong></p>
            </div>
        </div>
    </div>

    <!-- Card Pemenang Bulan Lalu & Total Setoran -->
    <div class="col-md-12 mb-4">
        <div class="card text-white bg-info">
            <div class="card-body">
                <h5 class="card-title">Pemenang Bulan Lalu & Total Setoran Bulan Ini</h5>
                <?php
                // Pemenang bulan lalu
                $query = "SELECT p.nama FROM pemenang pm 
                          JOIN peserta p ON pm.peserta_id = p.id 
                          WHERE MONTH(pm.tanggal) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) 
                          AND YEAR(pm.tanggal) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))";
                $result = $conn->query($query);
                $pemenang_lalu = $result->fetch_assoc();

                // Total setoran bulan ini
                $query = "SELECT SUM(jumlah) as total FROM laporan 
                          WHERE jenis_setor IN ('cash', 'transfer') 
                          AND MONTH(tanggal_input) = MONTH(CURRENT_DATE()) 
                          AND YEAR(tanggal_input) = YEAR(CURRENT_DATE())";
                $result = $conn->query($query);
                $total_setoran = $result->fetch_assoc()['total'] ?? 0;
                ?>
                <h4 class="card-text mb-2">Peserta: <strong><?= $pemenang_lalu ? $pemenang_lalu['nama'] : 'Belum ada' ?></strong></h4>
                <h4 class="card-text">Mendapatkan: <strong>Rp <?= number_format($total_setoran, 0, ',', '.') ?></strong></h4>
            </div>
        </div>
    </div>
</div>

<?php include '../core/footer.php'; ?>