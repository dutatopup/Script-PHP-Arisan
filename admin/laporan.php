<?php
require_once '../core/auth.php';
redirect_if_not_logged_in();

include '../core/header.php';

// Ambil bulan dan tahun saat ini
$bulan_ini = date('n'); // 1-12
$tahun_ini = date('Y');

// Cek apakah sudah ada pemenang bulan ini
$query_pemenang = "SELECT COUNT(*) as total FROM pemenang 
                  WHERE MONTH(tanggal) = MONTH(CURRENT_DATE()) 
                  AND YEAR(tanggal) = YEAR(CURRENT_DATE())";
$result_pemenang = $conn->query($query_pemenang);
$sudah_ada_pemenang = $result_pemenang->fetch_assoc()['total'] > 0;

// Cek apakah sudah ada data tempat untuk bulan ini
$query_cek_tempat = "SELECT COUNT(*) as total FROM tempat 
                     WHERE bulan = ? AND tahun = ?";
$stmt_cek_tempat = $conn->prepare($query_cek_tempat);
$stmt_cek_tempat->bind_param("ii", $bulan_ini, $tahun_ini);
$stmt_cek_tempat->execute();
$result_cek_tempat = $stmt_cek_tempat->get_result();
$sudah_ada_tempat = $result_cek_tempat->fetch_assoc()['total'] > 0;
$stmt_cek_tempat->close();

// Proses input tempat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_tempat'])) {
    if ($sudah_ada_tempat) {
        $pesan_tempat = ['tipe' => 'danger', 'isi' => 'Tidak bisa input tempat karena data tempat bulan ini sudah ada'];
    } else {
        $nama_tempat = $conn->real_escape_string($_POST['nama_tempat']);
        
        $stmt = $conn->prepare("INSERT INTO tempat (nama_tempat, bulan, tahun) VALUES (?, ?, ?)");
        $stmt->bind_param("sii", $nama_tempat, $bulan_ini, $tahun_ini);
        
        if ($stmt->execute()) {
            $pesan_tempat = ['tipe' => 'success', 'isi' => 'Tempat berhasil disimpan'];
            $sudah_ada_tempat = true; // Update status setelah berhasil simpan
        } else {
            $pesan_tempat = ['tipe' => 'danger', 'isi' => 'Gagal menyimpan tempat'];
        }
        $stmt->close();
    }
}

// Proses update tempat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_tempat'])) {
    if ($sudah_ada_pemenang) {
        $pesan_tempat = ['tipe' => 'danger', 'isi' => 'Tidak bisa mengubah tempat karena pemenang sudah ditentukan'];
    } else {
        $id = intval($_POST['id']);
        $nama_tempat = $conn->real_escape_string($_POST['nama_tempat']);
        
        $stmt = $conn->prepare("UPDATE tempat SET nama_tempat = ? WHERE id = ?");
        $stmt->bind_param("si", $nama_tempat, $id);
        
        if ($stmt->execute()) {
            $pesan_tempat = ['tipe' => 'success', 'isi' => 'Tempat berhasil diupdate'];
        } else {
            $pesan_tempat = ['tipe' => 'danger', 'isi' => 'Gagal mengupdate tempat'];
        }
        $stmt->close();
    }
}

// Proses input laporan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    if ($sudah_ada_pemenang) {
        $pesan = ['tipe' => 'danger', 'isi' => 'Tidak bisa input laporan karena pemenang sudah ditentukan'];
    } else {
        $peserta_id = intval($_POST['peserta_id']);
        $jenis_setor = $conn->real_escape_string($_POST['jenis_setor']);
        $jumlah = ($jenis_setor === 'belum') ? 0 : floatval(str_replace('.', '', $_POST['jumlah']));
        $keterangan = $conn->real_escape_string($_POST['keterangan']);

        // Cek apakah peserta sudah setor hari ini
        $query_cek = "SELECT id, jumlah FROM laporan 
                     WHERE peserta_id = ? 
                     AND DATE(tanggal_input) = CURDATE()";
        $stmt_cek = $conn->prepare($query_cek);
        $stmt_cek->bind_param("i", $peserta_id);
        $stmt_cek->execute();
        $result_cek = $stmt_cek->get_result();

        if ($result_cek->num_rows > 0) {
            $laporan_exist = $result_cek->fetch_assoc();
            $_SESSION['peserta_duplikat'] = [
                'id' => $laporan_exist['id'],
                'jumlah' => $laporan_exist['jumlah']
            ];
            $pesan = ['tipe' => 'warning', 'isi' => 'Peserta sudah setor hari ini. Apakah ingin mengubah data?'];
        } else {
            $stmt = $conn->prepare("INSERT INTO laporan (peserta_id, jenis_setor, jumlah, keterangan) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isds", $peserta_id, $jenis_setor, $jumlah, $keterangan);

            if ($stmt->execute()) {
                $pesan = ['tipe' => 'success', 'isi' => 'Laporan berhasil disimpan'];
            } else {
                $pesan = ['tipe' => 'danger', 'isi' => 'Gagal menyimpan laporan'];
            }
            $stmt->close();
        }
        $stmt_cek->close();
    }
}

// Tampilkan pesan
if (isset($pesan)) {
    echo '<div class="alert alert-' . $pesan['tipe'] . '">' . $pesan['isi'] . '</div>';

    // Tampilkan modal konfirmasi jika ada duplikasi
    if ($pesan['tipe'] === 'warning' && isset($_SESSION['peserta_duplikat'])) {
        echo '
        <div class="modal fade show" id="confirmEditModal" tabindex="-1" style="display: block; padding-right: 17px;" aria-modal="true" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Konfirmasi</h5>
                    </div>
                    <div class="modal-body">
                        <p>Peserta sudah setor hari ini dengan jumlah Rp ' . number_format($_SESSION['peserta_duplikat']['jumlah'], 0, ',', '.') . '. Apakah ingin mengubah data?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" onclick="hideModal()">Batal</button>
                        <a href="update_laporan.php?id=' . $_SESSION['peserta_duplikat']['id'] . '" class="btn btn-sm btn-primary">Ya, Ubah Data</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
        <script>
            function hideModal() {
                document.getElementById("confirmEditModal").style.display = "none";
                document.querySelector(".modal-backdrop").style.display = "none";
            }
        </script>';
        unset($_SESSION['peserta_duplikat']);
    }
}

// Ambil data tempat untuk bulan ini
$tempat_data = $conn->query("SELECT * FROM tempat WHERE bulan = $bulan_ini AND tahun = $tahun_ini ORDER BY nama_tempat ASC");

// Ambil data peserta untuk dropdown
$peserta = $conn->query("SELECT id, nama FROM peserta WHERE aktif = 1 ORDER BY nama ASC");

// Ambil data laporan bulan ini dan hitung total
$query_laporan = "SELECT l.*, p.nama FROM laporan l 
                 JOIN peserta p ON l.peserta_id = p.id 
                 WHERE MONTH(l.tanggal_input) = MONTH(CURRENT_DATE()) 
                 AND YEAR(l.tanggal_input) = YEAR(CURRENT_DATE())
                 ORDER BY p.nama ASC, l.tanggal_input DESC";
$laporan = $conn->query($query_laporan);

// Hitung total setoran bulan ini
$query_total = "SELECT SUM(jumlah) as total FROM laporan 
               WHERE jenis_setor IN ('cash', 'transfer') 
               AND MONTH(tanggal_input) = MONTH(CURRENT_DATE()) 
               AND YEAR(tanggal_input) = YEAR(CURRENT_DATE())";
$result_total = $conn->query($query_total);
$total_bulan_ini = $result_total->fetch_assoc()['total'] ?? 0;
?>

<h2 class="mb-4">Input Laporan Arisan - <?= date('F Y') ?></h2>

<!-- Form dan Tabel Tempat -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4>Data Tempat Bulan <?= date('F Y') ?></h4>
                <?php if (!$sudah_ada_tempat && !$sudah_ada_pemenang): ?>
                    <button type="button" class="btn btn-sm btn-primary" id="toggleTempatForm">
                        <i class="bi bi-plus-circle"></i> Tambah Tempat
                    </button>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <!-- Form Input Tempat (Awalnya Disembunyikan) -->
                <div id="formTempatContainer" style="display: <?= $sudah_ada_tempat ? 'none' : 'none' ?>;" class="mb-4">
                    <?php if (isset($pesan_tempat)): ?>
                        <div class="alert alert-<?= $pesan_tempat['tipe'] ?>"><?= $pesan_tempat['isi'] ?></div>
                    <?php endif; ?>
                    
                    <form method="post" id="formTempat">
                        <div class="row">
                            <div class="col-md-8">
                                <label class="form-label">Nama Tempat</label>
                                <input type="text" name="nama_tempat" class="form-control form-control-sm" required 
                                       placeholder="Masukkan nama tempat" <?= $sudah_ada_pemenang ? 'disabled' : '' ?>>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" name="simpan_tempat" class="btn btn-sm btn-success me-2" 
                                    <?= $sudah_ada_pemenang ? 'disabled' : '' ?>>
                                    <i class="bi bi-check-circle"></i> Simpan
                                </button>
                                <button type="button" id="batalTempat" class="btn btn-sm btn-secondary">
                                    <i class="bi bi-x-circle"></i> Batal
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Tabel Data Tempat -->
                <?php if ($tempat_data->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Tempat</th>
                                    <th>Tanggal Dibuat</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no_tempat = 1; ?>
                                <?php while ($row_tempat = $tempat_data->fetch_assoc()): ?>
                                    <tr id="row-tempat-<?= $row_tempat['id'] ?>">
                                        <td><?= $no_tempat++ ?></td>
                                        <td>
                                            <span id="nama-tempat-<?= $row_tempat['id'] ?>">
                                                <?= htmlspecialchars($row_tempat['nama_tempat']) ?>
                                            </span>
                                            <div id="form-edit-tempat-<?= $row_tempat['id'] ?>" style="display: none;">
                                                <form method="post" class="d-flex">
                                                    <input type="hidden" name="id" value="<?= $row_tempat['id'] ?>">
                                                    <input type="text" name="nama_tempat" 
                                                           value="<?= htmlspecialchars($row_tempat['nama_tempat']) ?>" 
                                                           class="form-control form-control-sm me-2" required
                                                           <?= $sudah_ada_pemenang ? 'disabled' : '' ?>>
                                                    <button type="submit" name="update_tempat" 
                                                            class="btn btn-sm btn-success me-1"
                                                            <?= $sudah_ada_pemenang ? 'disabled' : '' ?>>
                                                        <i class="bi bi-check"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-secondary btn-batal-edit" 
                                                            data-id="<?= $row_tempat['id'] ?>">
                                                        <i class="bi bi-x"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($row_tempat['created_at'])) ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-warning btn-edit-tempat" 
                                                    data-id="<?= $row_tempat['id'] ?>"
                                                    <?= $sudah_ada_pemenang ? 'disabled' : '' ?>>
                                                <i class="bi bi-pencil"></i> Ubah
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> 
                        <?php if ($sudah_ada_pemenang): ?>
                            Data tempat untuk bulan <?= date('F Y') ?> tidak tersedia karena pemenang sudah ditentukan.
                        <?php else: ?>
                            Belum ada data tempat untuk bulan <?= date('F Y') ?>. Klik "Tambah Tempat" untuk menambahkan.
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h4>Form Setoran</h4>
            </div>
            <div class="card-body">
                <form method="post" id="formSetoran">
                    <div class="mb-3">
                        <label class="form-label sm">Peserta</label>
                        <select name="peserta_id" class="form-select" required <?= $sudah_ada_pemenang ? 'disabled' : '' ?>>
                            <option value="">Pilih Peserta</option>
                            <?php while ($row = $peserta->fetch_assoc()): ?>
                                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['nama']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label sm">Jenis Setoran</label>
                        <select name="jenis_setor" class="form-select" id="jenisSetor" required <?= $sudah_ada_pemenang ? 'disabled' : '' ?>>
                            <option value="cash">Cash</option>
                            <option value="transfer">Transfer</option>
                            <option value="belum">Belum Setor</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label sm">Jumlah (Rp)</label>
                        <input type="text" name="jumlah" id="inputJumlah" class="form-control form-control-sm"
                            placeholder="100000" required oninput="formatRupiah(this)" <?= $sudah_ada_pemenang ? 'disabled' : '' ?>>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <textarea name="keterangan" class="form-control form-control-sm" rows="2" <?= $sudah_ada_pemenang ? 'disabled' : '' ?>></textarea>
                    </div>

                    <button type="submit" name="simpan" class="btn btn-sm btn-primary" <?= $sudah_ada_pemenang ? 'disabled' : '' ?>>Simpan Laporan</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4>Laporan Bulan Ini</h4>
                <span class="badge bg-primary">
                    Total: Rp <?= number_format($total_bulan_ini, 0, ',', '.') ?>
                </span>
            </div>
            <div class="card-body">
                <?php if ($laporan->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Peserta</th>
                                    <th>Jenis</th>
                                    <th>Jumlah</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; ?>
                                <?php while ($row = $laporan->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= date('d/m/Y', strtotime($row['tanggal_input'])) ?></td>
                                        <td><?= htmlspecialchars($row['nama']) ?></td>
                                        <td>
                                            <span
                                                class="badge bg-<?= $row['jenis_setor'] === 'cash' ? 'success' : ($row['jenis_setor'] === 'transfer' ? 'primary' : 'danger') ?>">
                                                <?= ucfirst($row['jenis_setor']) ?>
                                            </span>
                                        </td>
                                        <td>Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning edit-laporan" data-id="<?= $row['id'] ?>"
                                                data-jumlah="<?= $row['jumlah'] ?>"
                                                data-jenis-setor="<?= $row['jenis_setor'] ?>" <?= $sudah_ada_pemenang ? 'disabled' : '' ?>>
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info"><i class="bi bi-info-circle"></i> Belum ada laporan untuk bulan ini</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Laporan -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Setoran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                    <?= $sudah_ada_pemenang ? 'disabled' : '' ?>></button>
            </div>
            <form id="formEdit" method="post" action="update_laporan.php">
                <div class="modal-body">
                    <input type="hidden" name="id" id="editId">

                    <!-- Tambahkan field Jenis Setor -->
                    <div class="mb-3">
                        <label class="form-label">Jenis Setoran</label>
                        <select name="jenis_setor" id="editJenisSetor" class="form-select form-select-sm" required
                            <?= $sudah_ada_pemenang ? 'disabled' : '' ?>>
                            <option value="cash">Cash</option>
                            <option value="transfer">Transfer</option>
                            <option value="belum">Belum Setor</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Jumlah (Rp)</label>
                        <input type="text" name="jumlah" id="editJumlah" class="form-control form-control-sm" required
                            oninput="formatRupiah(this)" <?= $sudah_ada_pemenang ? 'disabled' : '' ?>>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" <?= $sudah_ada_pemenang ? 'disabled' : '' ?>>Simpan
                        Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
     // Toggle form tempat - hanya muncul jika belum ada data tempat dan belum ada pemenang
    document.getElementById('toggleTempatForm')?.addEventListener('click', function() {
        const formContainer = document.getElementById('formTempatContainer');
        formContainer.style.display = formContainer.style.display === 'none' ? 'block' : 'none';
    });

    // Batal tambah tempat
    document.getElementById('batalTempat')?.addEventListener('click', function() {
        document.getElementById('formTempatContainer').style.display = 'none';
        document.getElementById('formTempat').reset();
    });

    // Edit tempat - hanya bisa jika belum ada pemenang
    document.querySelectorAll('.btn-edit-tempat').forEach(button => {
        button.addEventListener('click', function() {
            if (!this.disabled) {
                const id = this.getAttribute('data-id');
                const namaSpan = document.getElementById('nama-tempat-' + id);
                const formEdit = document.getElementById('form-edit-tempat-' + id);
                
                namaSpan.style.display = 'none';
                formEdit.style.display = 'block';
                this.style.display = 'none';
            }
        });
    });

    // Batal edit tempat
    document.querySelectorAll('.btn-batal-edit').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const namaSpan = document.getElementById('nama-tempat-' + id);
            const formEdit = document.getElementById('form-edit-tempat-' + id);
            const editButton = document.querySelector('.btn-edit-tempat[data-id="' + id + '"]');
            
            namaSpan.style.display = 'inline';
            formEdit.style.display = 'none';
            editButton.style.display = 'inline-block';
        });
    });

    // Disable input jumlah jika "Belum Setor" dipilih
    document.getElementById('jenisSetor').addEventListener('change', function () {
        const inputJumlah = document.getElementById('inputJumlah');
        if (this.value === 'belum') {
            inputJumlah.value = '0';
            inputJumlah.disabled = true;
        } else {
            inputJumlah.disabled = <?= $sudah_ada_pemenang ? 'true' : 'false' ?>;
            if (inputJumlah.value === '0') {
                inputJumlah.value = '';
            }
        }
    });

    // Handle modal edit laporan
    document.querySelectorAll('.edit-laporan').forEach(button => {
        button.addEventListener('click', function () {
            if (!this.disabled) {
                const id = this.getAttribute('data-id');
                const jumlah = this.getAttribute('data-jumlah').replace(/\./g, '');
                const jenisSetor = this.getAttribute('data-jenis-setor');

                document.getElementById('editId').value = id;
                document.getElementById('editJenisSetor').value = jenisSetor;
                document.getElementById('editJumlah').value = new Intl.NumberFormat('id-ID').format(jumlah);

                const editJenisSetor = document.getElementById('editJenisSetor');
                const editJumlah = document.getElementById('editJumlah');

                if (jenisSetor === 'belum') {
                    editJumlah.disabled = true;
                    editJumlah.value = '0';
                } else {
                    editJumlah.disabled = false;
                }

                editJenisSetor.addEventListener('change', function () {
                    if (this.value === 'belum') {
                        editJumlah.value = '0';
                        editJumlah.disabled = true;
                    } else {
                        editJumlah.disabled = false;
                        if (editJumlah.value === '0') {
                            editJumlah.value = '';
                        }
                    }
                });

                const modal = new bootstrap.Modal(document.getElementById('editModal'));
                modal.show();
            }
        });
    });

    document.getElementById('editJenisSetor').addEventListener('change', function () {
        const editJumlah = document.getElementById('editJumlah');
        if (this.value === 'belum') {
            editJumlah.value = '0';
            editJumlah.disabled = true;
        } else {
            editJumlah.disabled = false;
            if (editJumlah.value === '0') {
                editJumlah.value = '';
            }
        }
    });
</script>

<?php include '../core/footer.php'; ?>