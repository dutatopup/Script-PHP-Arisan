## Sistem Arisan

Peserta dikocok diawal tanpa mendapatkan sejumlah uang setoran, kemudian pada kocokan bulan berikutnya pemenang bulan lalu mendapatkan uang arisannya dan pemenang bulan tersebut tidak akan mendapatkan uangnya pada bulan itu, namun pada bulan berikutnya

## Script hanya membaca periode bulanan saja

Jika ada peserta yang ikut dobel maka buatlah namanya lagi namun tidak boleh sama
misal peserta Adi ikut dobel, maka penulisannya Adi 1 dan Adi 2 karena setiap kocokan hanya membaca peserta yang belum dapat arisan saja
tidak ada bot agar nama tertentu memiliki rate kemenangan, rate nama muncul adalah satu banding total peserta


## STEP INSTALL

1. Upload script ke hosting
2. Buatlah database
3. Konfigurasi databasenya pada core/database.php
4. Konfigurasi juga domain nya pada core/config.php
5. Instalasi pertama : buka domain yang sudah dikonfigurasi tadi sesuai dengan dimana kamu install script misal "domainkamu.com/install.php" tunggu sampai proses selesai tanpa ada error, jika ada error periksa kembali Konfigurasinya
6. Login ke halaman ddashboard melalui "domainkamu.com/login.php"
7. Data login default Username : admin dan Password : admin1234
8. Ubahlah password pada menu peserta

### MENU Tambah Peserta
Melalui menu Peserta, bisa disable atau enable peserta dan edit atau hapus peserta

### MENU Laporan
Untuk input uang setoran, akan disable jika sudah ada pemenang setelah kocokan disimpan

### MENU Histori
Untuk melihat histori setoran dari periode bulan

### MENU Pemenang
Untuk kocok arisan dan penyimpan pemenang arisan serta melihat histori pemenang dari periode bulanannya

### Header / index.php
Halaman dashboard detail penerima arisan
