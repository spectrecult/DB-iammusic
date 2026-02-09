<?php
require_once 'includes/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id']) && isset($_POST['id_review'])) {
    $reviewId = intval($_POST['id_review']);
    $content = trim($_POST['content']);
    $userId = $_SESSION['user_id'];

    if (!empty($content)) {
        try {
            // Оновлюється тільки якщо цей відгук належить поточному користувачу
            $stmt = $pdo->prepare("UPDATE dbo.Review SET content = ? WHERE id_review = ? AND id_user = ?");
            $stmt->execute([$content, $reviewId, $userId]);
        } catch (PDOException $e) {
            die("Помилка при редагуванні: " . $e->getMessage());
        }
    }

    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
} else {
    header("Location: index.php");
    exit;
}