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

function attempt_login($username, $password) {
    global $conn;

    // Query admin berdasarkan username
    $stmt = $conn->prepare("SELECT id, username, password, nama FROM admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        // Pastikan password di-hash di database, gunakan password_verify
        if (password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['nama'];
            return true;
        }
    }
    return false;
}