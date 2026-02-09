<?php
require_once 'includes/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $songId = intval($_POST['id_song']);
    $playlistId = intval($_POST['id_playlist']);

    try {
        $stmt = $pdo->prepare("DELETE FROM dbo.Playlist_Details WHERE id_playlist = ? AND id_song = ?");
        $result = $stmt->execute([$playlistId, $songId]);

        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Не вдалося видалити запис']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}