<?php
require_once 'includes/db.php';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
echo '<link rel="stylesheet" href="assets/css/style.css">';
// Налаштування лімітів відображення
$limit_albums = 6;
$limit_recommended_songs = 5;

// Налаштування часу для привітання
$morning_end = 12;
$day_end = 18;

$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    if ($searchTerm) {
        $query = "SELECT id_album, title, artist, imageURL, releaseYear FROM dbo.Album 
                  WHERE title LIKE ? OR artist LIKE ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute(["%$searchTerm%", "%$searchTerm%"]);
        $albums = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $isSearch = true;
    } else {
        $isSearch = false;
        $stmtAlbums = $pdo->query("SELECT TOP $limit_albums id_album, title, artist, imageURL, releaseYear FROM dbo.Album ORDER BY id_album DESC");
        $albums = $stmtAlbums->fetchAll(PDO::FETCH_ASSOC);

        $stmtSongs = $pdo->query("
            SELECT TOP $limit_recommended_songs s.id_song, s.title, s.artist, a.imageURL, s.audioURL 
            FROM dbo.Song s 
            JOIN dbo.Album a ON s.id_album = a.id_album 
            ORDER BY NEWID()
        ");
        $recommendedSongs = $stmtSongs->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $albums = [];
    $recommendedSongs = [];
}

$hour = date('H');
$greeting = ($hour < $morning_end) ? "Доброго ранку" : (($hour < $day_end) ? "Доброго дня" : "Добрий вечір");
?>

    <main class="main-content">
        <div class="home-wrapper">
            <div class="top-bar">
                <div>
                    <h2 style="font-size: 32px; font-weight: 700; color: white; margin: 0;">
                        <?= $greeting ?>, <?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Гість'; ?>!
                    </h2>
                </div>

                <form action="index.php" method="GET" class="search-form">
                    <svg class="search-icon-svg" viewBox="0 0 24 24">
                        <path d="M10.533 1.27a9.262 9.262 0 1 0 5.739 16.506l4.747 4.748a.75.75 0 1 0 1.061-1.061l-4.748-4.747a9.262 9.262 0 0 0-6.799-15.446zm-7.762 9.262a7.762 7.762 0 1 1 15.524 0 7.762 7.762 0 0 1-15.524 0z"></path>
                    </svg>
                    <input type="text" name="search" placeholder="Пошук..." value="<?= htmlspecialchars($searchTerm); ?>">
                </form>
            </div>

            <?php if ($isSearch): ?>
            <h3 class="section-title">Результати пошуку</h3>
            <section class="song-grid">
                <?php foreach ($albums as $album): ?>
                    <?php include 'includes/album-card.php'; ?>
                <?php endforeach; ?>
            </section>
            <?php endif; ?>

            <?php else: ?>
                <div class="quick-access-grid">
                    <?php foreach (array_slice($albums, 0, 6) as $album): ?>
                        <div class="quick-card" onclick="location.href='album_details.php?id=<?= $album['id_album']; ?>'">
                            <img src="<?= $album['imageURL'] ?: 'img/default.png' ?>" alt="Cover">
                            <span><?= htmlspecialchars($album['title']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
                    <div>
                        <h3 class="section-title" style="margin-top: 0;">Популярні альбоми</h3>
                        <section class="song-grid">
                            <?php foreach ($albums as $album): ?>
                                <?php include 'includes/album-card.php'; ?>
                            <?php endforeach; ?>
                        </section>
                    </div>

                    <div>
                        <h3 class="section-title" style="margin-top: 0;">Для вас</h3>
                        <div class="rec-list">
                            <?php
                            $jsPlaylist = json_encode($recommendedSongs);
                            foreach ($recommendedSongs as $index => $song): ?>
                                <div class="rec-item" onclick='playFromQueue(<?= $jsPlaylist ?>, <?= $index ?>)'>
                                    <img src="<?= $song['imageURL'] ?: 'img/default.png' ?>">
                                    <div style="overflow: hidden;">
                                        <div style="font-weight: 600; white-space: nowrap; text-overflow: ellipsis; overflow: hidden;"><?= htmlspecialchars($song['title']) ?></div>
                                        <div style="color: #b3b3b3; font-size: 12px;"><?= htmlspecialchars($song['artist']) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div style="height: 120px; width: 100%; display: block; clear: both;"></div>
        </div>
    </main>

    <script>
        function playFromQueue(playlist, startIndex) {
            window.currentPlaylist = playlist;
            const song = playlist[startIndex];
            if (typeof playSong === 'function') {
                playSong(song.title, song.artist, song.imageURL, song.audioURL);
                window.currentSongIndex = startIndex;
            }
        }
    </script>

<?php require_once 'includes/footer.php'; ?>

