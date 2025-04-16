<?php
require_once 'config.php';
require_once 'database.php';

function redirect_if_not_logged_in() {
    if (!is_logged_in()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header("Location: " . BASE_URL . "login.php");
        exit;
    }
}

// Fungsi login (pindahkan dari login.php ke sini)
function attempt_login($username, $password) {
    global $conn;
    
    // Contoh: Ganti dengan query database yang sesungguhnya
    if ($username === 'mazagung' && $password === 'kry412') {
        $_SESSION['admin_id'] = 1;
        $_SESSION['admin_name'] = 'Administrator';
        return true;
    }
    return false;
}