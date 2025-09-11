<?php
// Fungsi format tanggal Indonesia di PHP
function format_tanggal_indonesia() {
    $hari = array('Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu');
    $bulan = array(1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                   'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
    
    $hari_ini = $hari[date('w')];
    $tanggal = date('d');
    $bulan_ini = $bulan[date('n')];
    $tahun = date('Y');
    
    return "$hari_ini, $tanggal $bulan_ini $tahun";
}

// Menggunakan fungsi
$tanggal_hari_ini = format_tanggal_indonesia();
?>