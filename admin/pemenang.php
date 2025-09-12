<?php
require_once '../core/auth.php';
redirect_if_not_logged_in();
setlocale(LC_TIME, 'id_ID', 'id', 'id_ID.UTF-8');
include '../core/header.php';
// Di bagian atas file, sebelum memproses apapun
if (isset($_SESSION['pemenang_sementara'])) {
    unset($_SESSION['pemenang_sementara']);
}

// Proses kocok pemenang
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kocok'])) {
    // Ambil peserta aktif yang BELUM PERNAH menang sama sekali
    $query = "SELECT p.id, p.nama FROM peserta p 
              WHERE p.aktif = 1 
              AND p.id NOT IN (
                  SELECT pm.peserta_id FROM pemenang pm
              )
              ORDER BY RAND() LIMIT 1";

    $result = $conn->query($query);

    // Di bagian proses kocok pemenang, setelah mendapatkan pemenang
    if ($result->num_rows > 0) {
        $pemenang = $result->fetch_assoc();

        // Cek apakah peserta pernah menang sebelumnya
        $query_histori = "SELECT pm.tanggal, pm.jumlah FROM pemenang pm 
                     WHERE pm.peserta_id = ? 
                     ORDER BY pm.tanggal DESC LIMIT 1";
        $stmt_histori = $conn->prepare($query_histori);
        $stmt_histori->bind_param("i", $pemenang['id']);
        $stmt_histori->execute();
        $histori_pemenang = $stmt_histori->get_result()->fetch_assoc();
        $stmt_histori->close();

        // Simpan data pemenang sementara di session
        $_SESSION['pemenang_sementara'] = [
            'id' => $pemenang['id'],
            'nama' => $pemenang['nama'],
            'pernah_menang' => $histori_pemenang
        ];

        // Hitung TOTAL SETORAN (cash + transfer) bulan ini
        $query_total = "SELECT SUM(jumlah) as total FROM laporan 
                       WHERE jenis_setor IN ('cash', 'transfer') 
                       AND MONTH(tanggal_input) = MONTH(CURRENT_DATE())";
        $result_total = $conn->query($query_total);
        $total_arisan = $result_total->fetch_assoc()['total'] ?? 0;

        // Hitung jumlah peserta yang sudah setor
        $query_peserta_setor = "SELECT COUNT(DISTINCT peserta_id) as jumlah_peserta 
                               FROM laporan 
                               WHERE jenis_setor IN ('cash', 'transfer') 
                               AND MONTH(tanggal_input) = MONTH(CURRENT_DATE())";
        $result_peserta = $conn->query($query_peserta_setor);
        $jumlah_peserta_setor = $result_peserta->fetch_assoc()['jumlah_peserta'] ?? 0;

        // Hadiah adalah TOTAL SETORAN
        $hadiah = $total_arisan;

        // Simpan data pemenang di session untuk digunakan di modal
        $_SESSION['pemenang_sementara'] = [
            'id' => $pemenang['id'],
            'nama' => $pemenang['nama'],
            'hadiah' => $hadiah,
            'pernah_menang' => $histori_pemenang ? [
                'tanggal' => $histori_pemenang['tanggal'],
                'jumlah' => $histori_pemenang['jumlah']
            ] : null
        ];
    } else {
        $pesan = [
            'tipe' => 'warning',
            'isi' => 'Tidak ada peserta yang memenuhi syarat, semua peserta sudah pernah menjadi pemenang arisan.'
        ];
    }
}
// Tampilkan pesan
if (isset($pesan)) {
    echo '<div class="alert alert-' . $pesan['tipe'] . '">' . $pesan['isi'] . '</div>';
}

// Ambil pemenang bulan lalu (yang menerima hadiah bulan ini)
$query_pemenang_lalu = "SELECT pm.*, p.nama FROM pemenang pm 
    JOIN peserta p ON pm.peserta_id = p.id 
    WHERE MONTH(pm.tanggal) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) 
    AND YEAR(pm.tanggal) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
    ORDER BY pm.tanggal DESC LIMIT 1";
$pemenang_lalu = $conn->query($query_pemenang_lalu);

// Ambil pemenang bulan ini (hasil undian, belum menerima hadiah)
$query_pemenang_ini = "SELECT pm.*, p.nama FROM pemenang pm 
    JOIN peserta p ON pm.peserta_id = p.id 
    WHERE MONTH(pm.tanggal) = MONTH(CURRENT_DATE()) 
    AND YEAR(pm.tanggal) = YEAR(CURRENT_DATE())
    ORDER BY pm.tanggal DESC LIMIT 1";
$pemenang_ini = $conn->query($query_pemenang_ini);

// Ambil histori pemenang
$histori = $conn->query("SELECT pm.*, p.nama FROM pemenang pm 
                         JOIN peserta p ON pm.peserta_id = p.id 
                         ORDER BY pm.tanggal DESC LIMIT 10");

// Cek apakah sudah ada pemenang sama sekali
$query_pemenang_total = "SELECT COUNT(*) as total FROM pemenang";
$result_total = $conn->query($query_pemenang_total);
$sudah_ada_pemenang_total = $result_total->fetch_assoc()['total'] > 0;

// Cek apakah sudah ada pemenang bulan ini
$query_pemenang_bulan_ini = "SELECT COUNT(*) as total FROM pemenang 
    WHERE MONTH(tanggal) = MONTH(CURRENT_DATE()) AND YEAR(tanggal) = YEAR(CURRENT_DATE())";
$result_bulan_ini = $conn->query($query_pemenang_bulan_ini);
$sudah_ada_pemenang_bulan_ini = $result_bulan_ini->fetch_assoc()['total'] > 0;
?>

<h2 class="mb-4">Pengundian Arisan</h2>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h4>Penerima Hadiah Bulan Ini</h4>
            </div>
            <div class="card-body text-center">
                <?php if ($pemenang_lalu->num_rows > 0): ?>
                    <?php $row = $pemenang_lalu->fetch_assoc(); ?>
                    <?php
                    // setlocale(LC_TIME, 'id_ID', 'id', 'id_ID.UTF-8');
                    $tanggal = strtotime($row['tanggal']);
                    $format_tanggal = strftime('%A, %d %B %Y', $tanggal);
                    ?>
                    <div class="alert alert-success">
                        <h4>Pemenang Bulan Lalu (Menerima Uang Bulan Ini):</h4>
                        <h3 class="text-center my-4"><?= htmlspecialchars($row['nama']) ?></h3>
                        <p class="mb-1"> <?= $format_tanggal ?>
                        </p>
                        <p class="mb-1"><strong>Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></strong></p>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">Belum ada pemenang bulan lalu.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h4>Kocok Pemenang Bulan Ini</h4>
            </div>
            <div class="card-body text-center">
                <?php if (!$sudah_ada_pemenang_total): ?>
                    <!-- Tombol kuning: pembukaan arisan -->
                    <div id="openingAnimationContainer" style="display: none;">
                        <div class="matrix-animation" id="openingMatrixAnimation"></div>
                        <h3 class="mt-3" id="openingWinnerName"></h3>
                    </div>
                    <form method="post" id="openingKocokForm">
                        <p>Klik tombol di bawah untuk mengundi pemenang arisan pembukaan (bulan pertama):</p>
                        <button type="button" id="openingKocokButton" class="btn btn-warning btn-lg">
                            <i class="bi bi-shuffle"></i> Kocok Pemenang Pembukaan
                        </button>
                    </form>
                    <script>
                        document.getElementById('openingKocokButton').addEventListener('click', function () {
                            const container = document.getElementById('openingAnimationContainer');
                            const animation = document.getElementById('openingMatrixAnimation');
                            const kocokForm = document.getElementById('openingKocokForm');
                            const winnerName = document.getElementById('openingWinnerName');

                            kocokForm.style.display = 'none';
                            container.style.display = 'block';
                            animation.style.display = 'block';

                            // Ambil peserta dari PHP
                            const peserta = <?php
                            $result = $conn->query("SELECT nama FROM peserta WHERE aktif = 1");
                            $data = [];
                            while ($r = $result->fetch_assoc())
                                $data[] = $r['nama'];
                            echo json_encode($data);
                            ?>;

                            if (!peserta || peserta.length === 0) {
                                animation.style.display = 'none';
                                winnerName.textContent = '';
                                alert('Tidak ada peserta aktif.');
                                container.innerHTML = `
                                <div class="alert alert-warning">
                                    Tidak ada peserta aktif.
                                </div>
                            `;
                                return;
                            }

                            // Acak urutan peserta
                            const shuffledPeserta = peserta
                                .map(value => ({ value, sort: Math.random() }))
                                .sort((a, b) => a.sort - b.sort)
                                .map(({ value }) => value);

                            let idx = 0;
                            const duration = 10000; // durasi animasi dalam ms (10 detik)
                            const intervalMs = 100; // interval antar nama (0.1 detik)
                            const startTime = Date.now();

                            let animInterval = setInterval(() => {
                                if (Date.now() - startTime >= duration) {
                                    clearInterval(animInterval);
                                    // Pilih pemenang acak
                                    const winner = peserta[Math.floor(Math.random() * peserta.length)];
                                    animation.style.display = 'none';
                                    winnerName.textContent = winner;

                                    // Tampilkan modal konfirmasi pemenang pembukaan
                                    document.getElementById('modalWinnerName').textContent = winner;
                                    document.getElementById('inputWinnerName').value = winner;
                                    document.getElementById('inputWinnerAmount').value = 0;
                                    document.getElementById('detailHadiah').innerHTML = `
                                    <div class="mb-3">
                                        <p class="mb-1"><strong>Pemenang pembukaan arisan belum menerima hadiah.</strong></p>
                                        <hr>
                                        <h5 class="mb-1 text-warning"><strong>Hadiah akan diberikan pada bulan depan.</strong></h5>
                                    </div>
                                `;
                                    const modal = new bootstrap.Modal(document.getElementById('winnerModal'));
                                    modal.show();
                                    return;
                                }

                                // Tampilkan nama peserta satu per satu, looping array
                                let nama = shuffledPeserta[idx % shuffledPeserta.length];
                                let sensorNama = '';
                                let step = 2; // bisa diganti 2 atau 3 sesuai keinginan
                                for (let i = 0; i < nama.length; i += step) {
                                    sensorNama += nama[i];
                                    if (i + 1 < nama.length) sensorNama += '*';
                                    if (step === 3 && i + 2 < nama.length) sensorNama += '*';
                                }
                                animation.textContent = sensorNama;
                                winnerName.textContent = '';
                                idx++;
                            }, intervalMs);
                        });
                    </script>
                <?php elseif (!$sudah_ada_pemenang_bulan_ini): ?>
                    <!-- Tombol biru: pengocokan bulan berjalan -->
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
                    <script>
                        document.getElementById('kocokButton').addEventListener('click', function () {
                            const container = document.getElementById('animationContainer');
                            const animation = document.getElementById('matrixAnimation');
                            const kocokForm = document.getElementById('kocokForm');
                            const winnerName = document.getElementById('winnerName');

                            kocokForm.style.display = 'none';
                            container.style.display = 'block';
                            animation.style.display = 'block';

                            // Ambil peserta dari PHP
                            const peserta = <?php
                            $result = $conn->query("SELECT nama FROM peserta WHERE aktif = 1 AND id NOT IN (SELECT peserta_id FROM pemenang)");
                            $data = [];
                            while ($r = $result->fetch_assoc())
                                $data[] = $r['nama'];
                            echo json_encode($data);
                            ?>;

                            if (!peserta || peserta.length === 0) {
                                animation.style.display = 'none';
                                winnerName.textContent = '';
                                alert('Tidak ada peserta pemenang karena semua sudah pernah menang.');
                                container.innerHTML = `
                                <div class="alert alert-warning">
                                    Tidak ada peserta pemenang karena semua sudah pernah menang.
                                </div>
                            `;
                                return;
                            }

                            // Acak urutan peserta
                            const shuffledPeserta = peserta
                                .map(value => ({ value, sort: Math.random() }))
                                .sort((a, b) => a.sort - b.sort)
                                .map(({ value }) => value);

                            let idx = 0;
                            const duration = 10000; // durasi animasi dalam ms (10 detik)
                            const intervalMs = 70; // interval antar nama (0.1 detik)
                            const startTime = Date.now();

                            let animInterval = setInterval(() => {
                                // Jika waktu sudah habis, stop animasi
                                if (Date.now() - startTime >= duration) {
                                    clearInterval(animInterval);
                                    // Pilih pemenang acak
                                    const winner = peserta[Math.floor(Math.random() * peserta.length)];
                                    animation.style.display = 'none';
                                    winnerName.textContent = winner;

                                    fetch('get_total_arisan.php')
                                        .then(response => {
                                            if (!response.ok) throw new Error('Network response was not ok');
                                            return response.json();
                                        })
                                        .then(data => {
                                            const total = parseFloat(data.total_arisan) || 0;
                                            const jumlah_peserta = parseInt(data.jumlah_peserta) || 0;
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
                                            let detailHadiah = `
                                            <div class="mb-3">
                                                <p class="mb-1"><strong>Total Setoran:</strong> Rp ${formatRupiah(total)}</p>
                                                <p class="mb-1"><strong>Jumlah Peserta Setor:</strong> ${jumlah_peserta} orang</p>
                                                <hr>
                                                <h5 class="mb-1"><strong>Hadiah untuk Pemenang:</strong> Rp ${formatRupiah(total)}</h5>
                                        `;
                                            const pernahMenang = <?php echo isset($_SESSION['pemenang_sementara']['pernah_menang']) ? 'true' : 'false'; ?>;
                                            if (pernahMenang) {
                                                const lastWinDate = new Date("<?php echo $_SESSION['pemenang_sementara']['pernah_menang']['tanggal'] ?? ''; ?>");
                                                const lastWinAmount = <?php echo $_SESSION['pemenang_sementara']['pernah_menang']['jumlah'] ?? 0; ?>;
                                                detailHadiah += `
                                                <div class="alert alert-warning mt-3">
                                                    <strong>PERINGATAN!</strong>
                                                    <p>Peserta ini sudah pernah memenangkan arisan sebelumnya:</p>
                                                    <p><strong>Tanggal Menang:</strong> ${lastWinDate.toLocaleDateString('id-ID')}</p>
                                                    <p><strong>Hadiah Sebelumnya:</strong> Rp ${formatRupiah(lastWinAmount)}</p>
                                                    <p class="mb-0">Apakah Anda yakin ingin memilihnya lagi?</p>
                                                </div>
                                            `;
                                            }
                                            detailHadiah += `</div>`;
                                            document.getElementById('detailHadiah').innerHTML = detailHadiah;
                                            const modal = new bootstrap.Modal(document.getElementById('winnerModal'));
                                            modal.show();
                                        })
                                        .catch(error => {
                                            console.error('Error:', error);
                                            alert('Terjadi kesalahan saat memuat data hadiah: ' + error.message);
                                        });
                                    return;
                                }

                                // Tampilkan nama peserta satu per satu, looping array
                                let nama = shuffledPeserta[idx % shuffledPeserta.length];
                                let sensorNama = '';
                                let step = 3; // bisa diganti 2 atau 3 sesuai keinginan
                                for (let i = 0; i < nama.length; i += step) {
                                    sensorNama += nama[i];
                                    if (i + 1 < nama.length) sensorNama += '*';
                                    if (step === 3 && i + 2 < nama.length) sensorNama += '^';
                                }
                                animation.textContent = sensorNama;
                                winnerName.textContent = '';
                                idx++;
                            }, intervalMs); // Tampilkan nama tiap 0.1 detik
                        });

                        // Handle penolakan pemenang
                        document.getElementById('rejectButton')?.addEventListener('click', function () {
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
                    </script>
                <?php else: ?>
                    <div class="alert alert-info">
                        Pemenang untuk bulan ini sudah ditentukan. Silakan lanjut ke bulan berikutnya.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
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
                                    <th>Mendapatkan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $histori->fetch_assoc()): ?>
                                    <?php
                                    // setlocale(LC_TIME, 'id_ID', 'id', 'id_ID.UTF-8');
                                    $tanggal = strtotime($row['tanggal']);
                                    $format_tanggal = strftime('%A, %d %B %Y', $tanggal);
                                    ?>
                                    <tr>
                                        <td><?= $format_tanggal ?></td>
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
        padding: 10px;
        border-radius: 5px;
    }`;
    document.head.appendChild(style);

    document.addEventListener('DOMContentLoaded', function () {
        const rejectButton = document.getElementById('rejectButton');
        if (rejectButton) {
            rejectButton.addEventListener('click', function () {
                // Tutup modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('winnerModal'));
                if (modal) modal.hide();

                // Reset tampilan
                const container = document.getElementById('animationContainer');
                const animation = document.getElementById('matrixAnimation');
                const kocokForm = document.getElementById('kocokForm');
                const winnerName = document.getElementById('winnerName');

                if (container) container.style.display = 'none';
                if (animation) animation.style.display = 'none';
                if (winnerName) winnerName.textContent = '';
                if (kocokForm) kocokForm.style.display = 'block';

                // Hapus backdrop modal jika ada
                const backdrops = document.getElementsByClassName('modal-backdrop');
                while (backdrops.length > 0) {
                    backdrops[0].parentNode.removeChild(backdrops[0]);
                }

                // Enable body scrolling kembali
                document.body.style.overflow = 'auto';
                document.body.style.paddingRight = '0';
            });
        }
    });
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
                        <button type="submit" name="aksi" value="terima" class="btn btn-success btn-sm">
                            <i class="bi bi-check-circle"></i> Terima
                        </button>
                    </form>
                    <button type="button" id="rejectButton" class="btn btn-danger btn-sm">
                        <i class="bi bi-x-circle"></i> Tolak
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../core/footer.php'; ?>