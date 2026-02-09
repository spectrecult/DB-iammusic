<?php
require_once 'includes/db.php';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

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
        $stmtAlbums = $pdo->query("SELECT TOP 6 id_album, title, artist, imageURL, releaseYear FROM dbo.Album ORDER BY id_album DESC");
        $albums = $stmtAlbums->fetchAll(PDO::FETCH_ASSOC);

        $stmtSongs = $pdo->query("
            SELECT TOP 5 s.id_song, s.title, s.artist, a.imageURL, s.audioURL 
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
$greeting = ($hour < 12) ? "Доброго ранку" : (($hour < 18) ? "Доброго дня" : "Добрий вечір");
?>

    <style>
        .main-content {
            background: linear-gradient(to bottom, #222222, #121212);
            min-height: 100vh;
            overflow-y: auto;
            padding: 30px 30px 150px 30px !important;
        }

        .home-wrapper {
            display: block;
            width: 100%;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .search-form {
            display: flex;
            align-items: center;
            background: rgba(255,255,255,0.07);
            border-radius: 20px;
            padding: 6px 15px;
            width: 280px;
            border: 1px solid transparent;
            transition: 0.3s;
        }
        .search-form:focus-within {
            border-color: #1db954;
            background: rgba(255,255,255,0.12);
            width: 320px;
        }
        .search-form input {
            background: transparent;
            border: none;
            color: white;
            margin-left: 10px;
            outline: none;
            width: 100%;
            font-size: 13px;
        }
        .search-icon-svg { width: 16px; height: 16px; fill: #b3b3b3; }

        .section-title { font-size: 24px; font-weight: 700; margin: 30px 0 20px; color: white; }

        .quick-access-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
            margin-bottom: 40px;
        }
        .quick-card {
            background: rgba(255,255,255,0.05);
            border-radius: 4px;
            display: flex;
            align-items: center;
            overflow: hidden;
            transition: 0.3s;
            cursor: pointer;
        }
        .quick-card:hover { background: rgba(255,255,255,0.15); }
        .quick-card img { width: 80px; height: 80px; object-fit: cover; }
        .quick-card span { padding: 0 20px; font-weight: 700; color: white; }

        .rec-list { background: rgba(255,255,255,0.03); border-radius: 8px; padding: 10px; }
        .rec-item { display: flex; align-items: center; padding: 10px; border-radius: 4px; transition: 0.2s; cursor: pointer; color: white; }
        .rec-item:hover { background: rgba(255,255,255,0.1); }
        .rec-item img { width: 48px; height: 48px; border-radius: 4px; margin-right: 15px; object-fit: cover; }
    </style>

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
                        <div class="card" onclick="location.href='album_details.php?id=<?= $album['id_album']; ?>'">
                            <div class="img-container">
                                <img src="<?= $album['imageURL'] ?: 'img/default.png'; ?>" alt="Cover">
                                <button class="play-btn"><span>▶</span></button>
                            </div>
                            <h4><?= htmlspecialchars($album['title']); ?></h4>
                            <p><?= htmlspecialchars($album['artist']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </section>
                <div style="margin-top: 20px;"><a href="index.php" style="color: #1db954; text-decoration: none;">← Назад</a></div>

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
                                <div class="card" onclick="location.href='album_details.php?id=<?= $album['id_album']; ?>'">
                                    <div class="img-container">
                                        <img src="<?= $album['imageURL'] ?: 'img/default.png'; ?>" alt="Cover">
                                        <button class="play-btn"><span>▶</span></button>
                                    </div>
                                    <h4><?= htmlspecialchars($album['title']); ?></h4>
                                    <p><?= htmlspecialchars($album['artist']); ?></p>
                                </div>
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