<?php
require_once '../core/auth.php';
redirect_if_not_logged_in();

include '../core/header.php';

// Ambil parameter bulan dan tahun
$bulan = isset($_GET['bulan']) ? intval($_GET['bulan']) : date('n');
$tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');

// Query untuk mendapatkan laporan berdasarkan bulan dan tahun
$query = "SELECT l.*, p.nama 
          FROM laporan l
          JOIN peserta p ON l.peserta_id = p.id
          WHERE MONTH(l.tanggal_input) = ? AND YEAR(l.tanggal_input) = ?
          ORDER BY l.tanggal_input DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $bulan, $tahun);
$stmt->execute();
$result = $stmt->get_result();
$laporan = $result->fetch_all(MYSQLI_ASSOC);

// Hitung total setoran
$total_cash = 0;
$total_transfer = 0;
foreach ($laporan as $item) {
    if ($item['jenis_setor'] === 'cash') {
        $total_cash += $item['jumlah'];
    } elseif ($item['jenis_setor'] === 'transfer') {
        $total_transfer += $item['jumlah'];
    }
}
$total_keseluruhan = $total_cash + $total_transfer;
?>

<h2 class="mb-4">Histori Laporan Arisan</h2>

<div class="card mb-4">
    <div class="card-header">
        <h4>Filter Laporan</h4>
    </div>
    <div class="card-body">
        <form method="get" class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label sm">Bulan</label>
                <select name="bulan" class="form-select">
                    <?php
                    $nama_bulan = [
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                    ];
                    foreach ($nama_bulan as $key => $value) {
                        $selected = ($key == $bulan) ? 'selected' : '';
                        echo "<option value='$key' $selected>$value</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label sm">Tahun</label>
                <select name="tahun" class="form-select">
                    <?php
                    $tahun_sekarang = date('Y');
                    for ($i = $tahun_sekarang; $i >= $tahun_sekarang - 5; $i--) {
                        $selected = ($i == $tahun) ? 'selected' : '';
                        echo "<option value='$i' $selected>$i</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end mb-3">
                <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                <a href="histori_laporan.php" class="btn btn-sm btn-secondary ms-2">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4>Laporan Bulan <?= $nama_bulan[$bulan] ?> <?= $tahun ?></h4>
        <div>
            <span class="badge bg-primary">Total: Rp <?= number_format($total_keseluruhan, 0, ',', '.') ?></span>
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($laporan)): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Tanggal</th>
                            <th>Peserta</th>
                            <th>Jenis Setoran</th>
                            <th>Jumlah</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($laporan as $item): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($item['tanggal_input'])) ?></td>
                            <td><?= htmlspecialchars($item['nama']) ?></td>
                            <td>
                                <span class="badge bg-<?= $item['jenis_setor'] === 'cash' ? 'success' : ($item['jenis_setor'] === 'transfer' ? 'primary' : 'warning') ?>">
                                    <?= ucfirst($item['jenis_setor']) ?>
                                </span>
                            </td>
                            <td>Rp <?= number_format($item['jumlah'], 0, ',', '.') ?></td>
                            <td><?= htmlspecialchars($item['keterangan'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="table-active">
                            <td colspan="3"><strong>Total</strong></td>
                            <td><strong>Rp <?= number_format($total_keseluruhan, 0, ',', '.') ?></strong></td>
                            <td></td>
                        </tr>
                        <tr class="table-secondary">
                            <td colspan="2"></td>
                            <td>Cash: Rp <?= number_format($total_cash, 0, ',', '.') ?></td>
                            <td>Transfer: Rp <?= number_format($total_transfer, 0, ',', '.') ?></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">Tidak ada laporan untuk bulan <?= $nama_bulan[$bulan] ?> <?= $tahun ?></div>
        <?php endif; ?>
    </div>
</div>

<?php include '../core/footer.php'; ?>