<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arisan Billman</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .main-container {
            min-height: calc(100vh - 120px);
        }

        .form-label.sm {
            display: flex;
            align-items: center;
            /* biar teks rata tengah */
            height: calc(1.5em + .5rem + 2px);
            /* tinggi sama kayak form-control-sm */
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Arisan Billman</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="peserta.php"><i class="bi bi-people-fill"></i> Peserta</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="laporan.php"><i class="bi bi-journal-text"></i> Laporan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="histori_laporan.php"><i class="bi bi-clock-history"></i> Histori</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pemenang.php"><i class="bi bi-trophy-fill"></i> Pemenang</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container main-container py-4">