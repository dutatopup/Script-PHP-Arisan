</div>
<footer class="bg-light py-3 mt-4">
    <div class="container text-center">
        <p class="mb-0">Arisan Billman &copy; <?= date('Y') ?></p>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Modal Edit Peserta -->
<div class="modal fade" id="editPesertaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" id="formEditPeserta">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Nama Peserta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="edit_id" id="editPesertaId">
                    <div class="mb-3">
                        <label>Nama Peserta</label>
                        <input type="text" name="edit_nama" id="editPesertaNama" class="form-control form-control-sm" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="edit_peserta" class="btn btn-sm btn-primary">Simpan Perubahan</button>
                </div>
            </div>
        </form>
    </div>
</div>
<!-- Modal Hapus Peserta -->
<div class="modal fade" id="hapusPesertaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog ">
        <form method="post" id="formHapusPeserta">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Hapus Peserta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="hapus_id" id="hapusPesertaId">
                    <p>Apakah Anda yakin ingin menghapus peserta <strong id="hapusPesertaNama"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="hapus_peserta" class="btn btn-sm btn-danger">Hapus</button>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
    document.querySelectorAll('.edit-peserta').forEach(button => {
        button.addEventListener('click', function () {
            document.getElementById('editPesertaId').value = this.getAttribute('data-id');
            document.getElementById('editPesertaNama').value = this.getAttribute('data-nama');
            const modal = new bootstrap.Modal(document.getElementById('editPesertaModal'));
            modal.show();
        });
    });

    document.querySelectorAll('.hapus-peserta').forEach(button => {
        button.addEventListener('click', function () {
            document.getElementById('hapusPesertaId').value = this.getAttribute('data-id');
            document.getElementById('hapusPesertaNama').textContent = this.getAttribute('data-nama');
            const modal = new bootstrap.Modal(document.getElementById('hapusPesertaModal'));
            modal.show();
        });
    });
</script>

</body>

</html>