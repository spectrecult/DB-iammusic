<?php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Потрібна авторизація");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $songId = intval($_POST['song_id']);
    $playlistId = intval($_POST['playlist_id']);

    try {
        // Перевірка, чи пісня вже є в цьому плейлісті
        $check = $pdo->prepare("SELECT * FROM dbo.Playlist_Details WHERE id_playlist = ? AND id_song = ?");
        $check->execute([$playlistId, $songId]);

        if (!$check->fetch()) {
            $addStmt = $pdo->prepare("INSERT INTO dbo.Playlist_Details (id_playlist, id_song) VALUES (?, ?)");
            $addStmt->execute([$playlistId, $songId]);
        }

        // Повернення користувача назад на ту сторінку, де він був
        $referrer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
        header("Location: " . $referrer);
        exit;

    } catch (PDOException $e) {
        die("Помилка бази даних: " . $e->getMessage());
    }
}