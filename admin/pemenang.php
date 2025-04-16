<?php
require_once '../core/auth.php';
redirect_if_not_logged_in();

include '../core/header.php';

// Proses kocok pemenang
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kocok'])) {
    // Ambil peserta aktif yang belum menang bulan ini
    $query = "SELECT p.id, p.nama FROM peserta p 
              WHERE p.aktif = 1 
              AND p.id NOT IN (
                  SELECT pm.peserta_id FROM pemenang pm 
                  WHERE MONTH(pm.tanggal) = MONTH(CURRENT_DATE())
              ) 
              ORDER BY RAND() LIMIT 1";

    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $pemenang = $result->fetch_assoc();

        // Hitung TOTAL SETORAN (cash + transfer) bulan ini
        $query_total = "SELECT SUM(jumlah) as total FROM laporan 
                       WHERE jenis_setor IN ('cash', 'transfer') 
                       AND MONTH(tanggal_input) = MONTH(CURRENT_DATE())";
        $result_total = $conn->query($query_total);
        $total_arisan = $result_total->fetch_assoc()['total'] ?? 0;

        // Hitung jumlah peserta yang sudah setor (hanya untuk informasi)
        $query_peserta_setor = "SELECT COUNT(DISTINCT peserta_id) as jumlah_peserta 
                               FROM laporan 
                               WHERE jenis_setor IN ('cash', 'transfer') 
                               AND MONTH(tanggal_input) = MONTH(CURRENT_DATE())";
        $result_peserta = $conn->query($query_peserta_setor);
        $jumlah_peserta_setor = $result_peserta->fetch_assoc()['jumlah_peserta'] ?? 0;

        // Hadiah adalah TOTAL SETORAN (tidak dibagi jumlah peserta)
        $hadiah = $total_arisan;

        // Simpan pemenang ke database
        $stmt = $conn->prepare("INSERT INTO pemenang (peserta_id, tanggal, jumlah) VALUES (?, NOW(), ?)");
        $stmt->bind_param("id", $pemenang['id'], $hadiah);

        if ($stmt->execute()) {
            $pesan = ['tipe' => 'success', 'isi' => 'Pemenang berhasil ditentukan'];
        } else {
            $pesan = ['tipe' => 'danger', 'isi' => 'Gagal menyimpan pemenang'];
        }
        $stmt->close();
    } else {
        $pesan = ['tipe' => 'warning', 'isi' => 'Tidak ada peserta yang memenuhi syarat'];
    }
}

// Tampilkan pesan
if (isset($pesan)) {
    echo '<div class="alert alert-' . $pesan['tipe'] . '">' . $pesan['isi'] . '</div>';
}

// Ambil pemenang bulan ini
$query = "SELECT pm.*, p.nama FROM pemenang pm 
          JOIN peserta p ON pm.peserta_id = p.id 
          WHERE MONTH(pm.tanggal) = MONTH(CURRENT_DATE()) 
          ORDER BY pm.tanggal DESC";
$pemenang = $conn->query($query);

// Ambil histori pemenang
$histori = $conn->query("SELECT pm.*, p.nama FROM pemenang pm 
                         JOIN peserta p ON pm.peserta_id = p.id 
                         ORDER BY pm.tanggal DESC LIMIT 10");
?>

<h2 class="mb-4">Pengundian Arisan</h2>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h4>Kocok Pemenang</h4>
            </div>
            <div class="card-body text-center">
                <?php if ($pemenang->num_rows > 0): ?>
                    <?php $row = $pemenang->fetch_assoc(); ?>
                    <div class="alert alert-success">
                        <h4>Pemenang Bulan Ini:</h4>
                        <h3 class="text-center my-4"><?= htmlspecialchars($row['nama']) ?></h3>
                        <p class="mb-1"><strong>Tanggal:</strong> <?= date('d F Y', strtotime($row['tanggal'])) ?></p>
                        <p class="mb-1"><strong>Hadiah:</strong> Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></p>
                    </div>
                <?php else: ?>
                    <div id="animationContainer" style="display: none;">
                        <div class="matrix-animation" id="matrixAnimation"></div>
                        <h3 class="mt-3" id="winnerName"></h3>
                    </div>

                    <form method="post" id="kocokForm">
                        <p>Klik tombol di bawah untuk mengundi pemenang arisan bulan ini:</p>
                        <button type="button" id="kocokButton" class="btn btn-primary btn-lg">
                            <i class="bi bi-shuffle"></i> Kocok Pemenang
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4>Histori Pemenang</h4>
            </div>
            <div class="card-body">
                <?php if ($histori->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Pemenang</th>
                                    <th>Hadiah</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $histori->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                                        <td><?= htmlspecialchars($row['nama']) ?></td>
                                        <td>Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">Belum ada histori pemenang</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Animasi Matrix dan pengundian pemenang
        document.getElementById('kocokButton').addEventListener('click', function() {
            const container = document.getElementById('animationContainer');
            const animation = document.getElementById('matrixAnimation');
            const kocokForm = document.getElementById('kocokForm');

            // Sembunyikan tombol, tampilkan animasi
            kocokForm.style.display = 'none';
            container.style.display = 'block';
            animation.style.display = 'block';

            // Animasi matrix
            const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
            let interval = setInterval(() => {
                let randomText = '';
                for (let i = 0; i < 10; i++) {
                    randomText += chars.charAt(Math.floor(Math.random() * chars.length));
                }
                animation.textContent = randomText;
            }, 100);

            // Setelah 10 detik, tampilkan pemenang
            setTimeout(() => {
                clearInterval(interval);

                // Pastikan peserta terdefinisi
                const peserta = <?php
                                $result = $conn->query("SELECT nama FROM peserta WHERE aktif = 1");
                                $data = [];
                                while ($r = $result->fetch_assoc()) $data[] = $r['nama'];
                                echo json_encode($data);
                                ?>;

                if (peserta && peserta.length > 0) {
                    const winner = peserta[Math.floor(Math.random() * peserta.length)];
                    document.getElementById('winnerName').textContent = winner;
                    animation.style.display = 'none';

                    fetch('get_total_arisan.php')
                        .then(response => {
                            if (!response.ok) throw new Error('Network response was not ok');
                            return response.json();
                        })
                        .then(data => {
                            const total = parseFloat(data.total_arisan) || 0;
                            const jumlah_peserta = parseInt(data.jumlah_peserta) || 0;

                            // Format angka untuk display
                            const formatRupiah = (angka) => {
                                return new Intl.NumberFormat('id-ID', {
                                    style: 'decimal',
                                    minimumFractionDigits: 0,
                                    maximumFractionDigits: 0
                                }).format(angka);
                            };

                            document.getElementById('modalWinnerName').textContent = winner;
                            document.getElementById('inputWinnerName').value = winner;
                            document.getElementById('inputWinnerAmount').value = total;

                            document.getElementById('detailHadiah').innerHTML = `
            <div class="mb-3">
                <p class="mb-1"><strong>Total Setoran:</strong> Rp ${formatRupiah(total)}</p>
                <p class="mb-1"><strong>Jumlah Peserta Setor:</strong> ${jumlah_peserta} orang</p>
                <hr>
                <h5 class="mb-1"><strong>Hadiah untuk Pemenang:</strong> Rp ${formatRupiah(total)}</h5>
                <small class="text-muted">(Total semua setoran cash + transfer bulan ini)</small>
            </div>
        `;

                            const modal = new bootstrap.Modal(document.getElementById('winnerModal'));
                            modal.show();
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Terjadi kesalahan saat memuat data hadiah: ' + error.message);
                        });
                } else {
                    alert('Tidak ada peserta yang tersedia');
                }
            }, 10000); // Kembalikan ke 10 detik
        });

        // Handle penolakan pemenang
        document.getElementById('rejectButton')?.addEventListener('click', function() {
            // Tutup modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('winnerModal'));
            if (modal) modal.hide();

            // Dapatkan elemen yang diperlukan
            const container = document.getElementById('animationContainer');
            const animation = document.getElementById('matrixAnimation');
            const kocokForm = document.getElementById('kocokForm');
            const winnerName = document.getElementById('winnerName');

            // Reset semua tampilan ke keadaan awal
            container.style.display = 'none';
            animation.style.display = 'none';
            winnerName.textContent = '';
            kocokForm.style.display = 'block';

            // Hapus backdrop modal jika ada
            const backdrops = document.getElementsByClassName('modal-backdrop');
            while (backdrops.length > 0) {
                backdrops[0].parentNode.removeChild(backdrops[0]);
            }

            // Enable body scrolling kembali
            document.body.style.overflow = 'auto';
            document.body.style.paddingRight = '0';
        });
    });

    // Style untuk animasi matrix
    const style = document.createElement('style');
    style.textContent = `
.matrix-animation {
    font-family: monospace;
    font-size: 24px;
    color: #0f0;
    text-shadow: 0 0 5px #0f0;
    letter-spacing: 5px;
    text-align: center;
    margin: 20px 0;
    background-color: black;
}`;
    document.head.appendChild(style);
</script>
<!-- Modal Konfirmasi Pemenang -->
<div class="modal fade" id="winnerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Pemenang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <h4 id="modalWinnerName" class="mb-4"></h4>
                <div id="detailHadiah" class="mb-3"></div>
                <p>Apakah pemenang ini valid?</p>
                <div class="d-flex justify-content-center gap-3">
                    <form method="post" action="simpan_pemenang.php" class="d-inline">
                        <input type="hidden" name="nama" id="inputWinnerName">
                        <input type="hidden" name="jumlah" id="inputWinnerAmount">
                        <button type="submit" name="aksi" value="terima" class="btn btn-success btn-lg">
                            <i class="bi bi-check-circle"></i> Terima
                        </button>
                    </form>
                    <button type="button" id="rejectButton" class="btn btn-danger btn-lg">
                        <i class="bi bi-x-circle"></i> Tolak
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../core/footer.php'; ?>