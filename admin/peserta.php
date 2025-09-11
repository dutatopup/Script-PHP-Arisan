<?php
require_once '../core/auth.php';
redirect_if_not_logged_in();

include '../core/header.php';

// Tambah peserta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
    $nama = trim($conn->real_escape_string($_POST['nama']));

    if (!empty($nama)) {
        $stmt = $conn->prepare("INSERT INTO peserta (nama, aktif) VALUES (?, 1)");
        $stmt->bind_param("s", $nama);
        if ($stmt->execute()) {
            $pesan = ['tipe' => 'success', 'isi' => 'Peserta berhasil ditambahkan'];
        } else {
            $pesan = ['tipe' => 'danger', 'isi' => 'Gagal menambahkan peserta'];
        }
        $stmt->close();
    } else {
        $pesan = ['tipe' => 'warning', 'isi' => 'Nama tidak boleh kosong'];
    }
}

// Toggle status peserta (aktif/nonaktif)
if (isset($_GET['toggle_status'])) {
    $id = intval($_GET['toggle_status']);

    // Dapatkan status saat ini
    $result = $conn->query("SELECT aktif FROM peserta WHERE id = $id");
    if ($result->num_rows > 0) {
        $current_status = $result->fetch_assoc()['aktif'];
        $new_status = $current_status ? 0 : 1; // Toggle status

        $stmt = $conn->prepare("UPDATE peserta SET aktif = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_status, $id);
        if ($stmt->execute()) {
            $action = $new_status ? 'diaktifkan' : 'dinonaktifkan';
            $pesan = ['tipe' => 'success', 'isi' => "Peserta berhasil $action"];
        } else {
            $pesan = ['tipe' => 'danger', 'isi' => 'Gagal mengubah status peserta'];
        }
        $stmt->close();
    }
}

// Edit peserta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_peserta'])) {
    $id = intval($_POST['edit_id']);
    $nama = trim($conn->real_escape_string($_POST['edit_nama']));
    if (!empty($nama)) {
        $stmt = $conn->prepare("UPDATE peserta SET nama = ? WHERE id = ?");
        $stmt->bind_param("si", $nama, $id);
        if ($stmt->execute()) {
            $pesan = ['tipe' => 'success', 'isi' => 'Nama peserta berhasil diubah'];
        } else {
            $pesan = ['tipe' => 'danger', 'isi' => 'Gagal mengubah nama peserta'];
        }
        $stmt->close();
    } else {
        $pesan = ['tipe' => 'warning', 'isi' => 'Nama tidak boleh kosong'];
    }
}

// Hapus peserta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_peserta'])) {
    $id = intval($_POST['hapus_id']);
    $stmt = $conn->prepare("DELETE FROM peserta WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $pesan = ['tipe' => 'success', 'isi' => 'Peserta berhasil dihapus'];
    } else {
        $pesan = ['tipe' => 'danger', 'isi' => 'Gagal menghapus peserta'];
    }
    $stmt->close();
}

// Tampilkan pesan
if (isset($pesan)) {
    echo '<div class="alert alert-' . $pesan['tipe'] . '">' . $pesan['isi'] . '</div>';
}

// Tab untuk peserta aktif, nonaktif, dan ganti password
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'aktif';

// Ambil daftar peserta berdasarkan status
$status = $active_tab === 'aktif' ? 1 : 0;
$peserta_query = $conn->query("SELECT * FROM peserta WHERE aktif = $status ORDER BY nama ASC");
?>

<h2 class="mb-4">Manajemen Peserta</h2>

<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link <?= $active_tab === 'aktif' ? 'active' : '' ?>" href="?tab=aktif">Peserta Aktif</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $active_tab === 'nonaktif' ? 'active' : '' ?>" href="?tab=nonaktif">Peserta Nonaktif</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $active_tab === 'password' ? 'active' : '' ?>" href="?tab=password">Ganti Password
            Admin</a>
    </li>
</ul>

<?php if ($active_tab !== 'password'): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h4>Tambah Peserta Baru</h4>
        </div>
        <div class="card-body">
            <form method="post">
                <div class="row">
                    <div class="col-md-8">
                        <input type="text" name="nama" class="form-control form-control-sm" placeholder="Nama peserta" required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" name="tambah" class="btn btn-sm btn-primary">Tambah Peserta</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h4>Daftar Peserta <?= $active_tab === 'aktif' ? 'Aktif' : 'Nonaktif' ?></h4>
        </div>
        <div class="card-body">
            <?php if ($peserta_query->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1;
                            while ($row = $peserta_query->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($row['nama']) ?></td>
                                    <td>
                                        <button class="btn btn-sm <?= $status ? 'btn-danger' : 'btn-success' ?> toggle-peserta"
                                            data-id="<?= $row['id'] ?>" data-status="<?= $status ?>"
                                            data-nama="<?= htmlspecialchars($row['nama']) ?>">
                                            <i class="bi <?= $status ? 'bi-person-x' : 'bi-person-check' ?>"></i>
                                            <?= $status ? '' : '' ?>
                                        </button>
                                        <button class="btn btn-sm btn-warning edit-peserta" data-id="<?= $row['id'] ?>"
                                            data-nama="<?= htmlspecialchars($row['nama']) ?>">
                                            <i class="bi bi-pencil "></i> 
                                        </button>
                                        <button class="btn btn-sm btn-danger hapus-peserta" data-id="<?= $row['id'] ?>"
                                            data-nama="<?= htmlspecialchars($row['nama']) ?>">
                                            <i class="bi bi-trash"></i> 
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">Tidak ada peserta yang <?= $active_tab === 'aktif' ? 'aktif' : 'nonaktif' ?></div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php if ($active_tab === 'password'): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h4>Ganti Password Admin</h4>
        </div>
        <div class="card-body">
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ganti_password'])) {
                $old = $_POST['old_password'] ?? '';
                $new = $_POST['new_password'] ?? '';
                $confirm = $_POST['confirm_password'] ?? '';
                $pesan_pw = '';

                // Ambil data admin dari session
                $admin_id = $_SESSION['admin_id'] ?? 0;
                $stmt = $conn->prepare("SELECT password FROM admin WHERE id = ?");
                $stmt->bind_param("i", $admin_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $admin = $result->fetch_assoc();

                if (!$admin || !password_verify($old, $admin['password'])) {
                    $pesan_pw = '<div class="alert alert-danger">Password lama salah!</div>';
                } elseif ($new !== $confirm) {
                    $pesan_pw = '<div class="alert alert-warning">Password baru dan konfirmasi tidak sama!</div>';
                } elseif (strlen($new) < 6) {
                    $pesan_pw = '<div class="alert alert-warning">Password baru minimal 6 karakter!</div>';
                } else {
                    $new_hash = password_hash($new, PASSWORD_DEFAULT);
                    $stmt2 = $conn->prepare("UPDATE admin SET password = ? WHERE id = ?");
                    $stmt2->bind_param("si", $new_hash, $admin_id);
                    if ($stmt2->execute()) {
                        $pesan_pw = '<div class="alert alert-success">Password berhasil diganti!</div>';
                    } else {
                        $pesan_pw = '<div class="alert alert-danger">Gagal mengganti password!</div>';
                    }
                    $stmt2->close();
                }
                $stmt->close();
                echo $pesan_pw;
            }
            ?>
            <form method="post">
                <div class="mb-3">
                    <label>Password Lama</label>
                    <input type="password" name="old_password" class="form-control form-control-sm" required>
                </div>
                <div class="mb-3">
                    <label>Password Baru</label>
                    <input type="password" name="new_password" class="form-control form-control-sm" required>
                </div>
                <div class="mb-3">
                    <label>Konfirmasi Password Baru</label>
                    <input type="password" name="confirm_password" class="form-control form-control-sm" required>
                </div>
                <button type="submit" name="ganti_password" class="btn btn-sm btn-primary">Ganti Password</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<!-- Modal Konfirmasi -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="confirmMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                <a id="confirmButton" href="#" class="btn btn-sm btn-primary">Ya, Lanjutkan</a>
            </div>
        </div>
    </div>
</div>

<script>
    // Handle modal konfirmasi
    document.querySelectorAll('.toggle-peserta').forEach(button => {
        button.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const status = this.getAttribute('data-status');
            const nama = this.getAttribute('data-nama');
            const action = status === '1' ? 'menonaktifkan' : 'mengaktifkan';

            document.getElementById('confirmMessage').textContent =
                `Apakah Anda yakin ingin ${action} peserta ${nama}?`;

            document.getElementById('confirmButton').href =
                `?toggle_status=${id}&tab=${'<?= $active_tab ?>'}`;

            const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
            modal.show();
        });
    });
</script>

<?php include '../core/footer.php'; ?>