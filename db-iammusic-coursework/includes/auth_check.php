<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
function requireAdmin() {
    if (!isset($_SESSION['role']) || (int)$_SESSION['role'] !== 1) {
        header("Location: index.php?error=access_denied");
        exit();
    }
}
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}