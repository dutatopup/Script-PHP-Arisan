<?php
session_start();
define('BASE_URL', 'http://localhost:8081');

// Fungsi helper dasar
function is_logged_in() {
    return isset($_SESSION['admin_id']); // Lebih baik gunakan admin_id daripada hanya admin
}