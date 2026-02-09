<?php
require_once 'includes/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id']) && isset($_POST['id_review'])) {
    $reviewId = intval($_POST['id_review']);
    $userId = $_SESSION['user_id'];

    try {
        // Видаляється за ID відгуку, але перевіряємо власника для безпеки
        $stmt = $pdo->prepare("DELETE FROM dbo.Review WHERE id_review = ? AND id_user = ?");
        $stmt->execute([$reviewId, $userId]);

        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    } catch (PDOException $e) {
        die("Помилка при видаленні: " . $e->getMessage());
    }
} else {
    header("Location: index.php");
    exit;
}