<?php
require_once 'includes/db.php';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

$id_album = isset($_GET['id']) ? intval($_GET['id']) : 0;

try {
    $album_stmt = $pdo->prepare("SELECT * FROM dbo.Album WHERE id_album = ?");
    $album_stmt->execute([$id_album]);
    $album = $album_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$album) {
        die("<main class='main-content' style='padding:50px; color:white;'><h2>Альбом не знайдено</h2></main>");
    }

    $songs_stmt = $pdo->prepare("SELECT * FROM dbo.Song WHERE id_album = ? ORDER BY id_song ASC");
    $songs_stmt->execute([$id_album]);
    $songs_array = $songs_stmt->fetchAll(PDO::FETCH_ASSOC);

    $userPlaylists = [];
    if (isset($_SESSION['user_id'])) {
        $plStmt = $pdo->prepare("SELECT id_playlist, name FROM dbo.Playlist WHERE id_user = ?");
        $plStmt->execute([$_SESSION['user_id']]);
        $userPlaylists = $plStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die("Помилка бази даних: " . $e->getMessage());
}
?>

    <style>
        .main-content {
            padding: 0;
            background: #121212;
            min-height: 100vh;
            padding-bottom: 200px !important;
        }

        .album-header {
            position: relative;
            padding: 60px 30px 30px;
            display: flex;
            align-items: flex-end;
            gap: 30px;
            background: linear-gradient(to bottom, #535353, #121212);
        }

        .search-container {
            position: absolute;
            top: 30px;
            right: 30px;
            display: flex;
            align-items: center;
            background: rgba(0,0,0,0.3);
            border-radius: 20px;
            padding: 6px 15px;
            width: 220px;
            border: 1px solid rgba(255,255,255,0.1);
            transition: 0.3s;
            z-index: 10;
        }
        .search-container:focus-within {
            border-color: #1db954;
            background: rgba(0,0,0,0.5);
            width: 280px;
        }
        .search-container input {
            background: transparent; border: none; color: white; margin-left: 10px;
            outline: none; width: 100%; font-size: 13px;
        }
        .search-icon-svg { width: 16px; height: 16px; fill: #b3b3b3; }

        .song-row { transition: background 0.2s; color: #fff; cursor: pointer; }
        .song-row:hover { background: rgba(255,255,255,0.1); }
        .index-col { width: 45px; text-align: center; color: #b3b3b3; position: relative; }
        .play-icon-small { display: none; color: #1db954; font-size: 12px; }
        .song-row:hover .song-index { display: none; }
        .song-row:hover .play-icon-small { display: inline-block; }

        .more-btn { background: none; border: none; color: #b3b3b3; cursor: pointer; font-size: 20px; opacity: 0; }
        .song-row:hover .more-btn { opacity: 1; }
        .dropdown-content {
            display: none; position: absolute; right: 30px; background: #282828;
            min-width: 190px; box-shadow: 0 8px 16px rgba(0,0,0,0.6); z-index: 100; border-radius: 4px;
        }
        .dropdown-content a { color: #eaeaea; padding: 12px 16px; text-decoration: none; display: block; font-size: 13px; }
        .dropdown-content a:hover { background: #3e3e3e; }
        .show { display: block !important; }

        #playlist-modal {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.85); z-index: 3000; align-items: center; justify-content: center;
        }
    </style>

    <main class="main-content">
        <div class="album-header">
            <div class="search-container">
                <svg class="search-icon-svg" viewBox="0 0 24 24">
                    <path d="M10.533 1.27a9.262 9.262 0 1 0 5.739 16.506l4.747 4.748a.75.75 0 1 0 1.061-1.061l-4.748-4.747a9.262 9.262 0 0 0-6.799-15.446zm-7.762 9.262a7.762 7.762 0 1 1 15.524 0 7.762 7.762 0 0 1-15.524 0z"></path>
                </svg>
                <input type="text" id="trackSearch" placeholder="Пошук в альбомі" onkeyup="filterTracks()">
            </div>

            <img src="<?= htmlspecialchars($album['imageURL'] ?: 'img/default.png'); ?>"
                 style="width: 232px; height: 232px; box-shadow: 0 8px 40px rgba(0,0,0,0.5); border-radius: 4px; object-fit: cover;">
            <div>
                <p style="font-size: 12px; font-weight: bold; text-transform: uppercase; margin-bottom: 8px; color: #fff;">Альбом</p>
                <h1 style="font-size: 72px; margin: 0 0 10px 0; font-weight: 900; letter-spacing: -2px; color: #fff;">
                    <?= htmlspecialchars($album['title']); ?>
                </h1>
                <p style="margin: 0; font-size: 14px; color: #fff;">
                    <strong><?= htmlspecialchars($album['artist']); ?></strong> • <?= $album['releaseYear']; ?> •
                    <span style="color: #b3b3b3;"><?= count($songs_array); ?> треків</span>
                </p>
            </div>
        </div>

        <div style="padding: 30px 30px 0;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                <tr style="color: #b3b3b3; border-bottom: 1px solid rgba(255,255,255,0.1); font-size: 12px; text-transform: uppercase;">
                    <th style="padding: 10px; width: 45px; text-align: center;">#</th>
                    <th style="padding: 10px;">Назва</th>
                    <th style="padding: 10px;">Тривалість</th>
                    <th style="padding: 10px; width: 50px;"></th>
                </tr>
                </thead>
                <tbody id="songsTableBody">
                <?php foreach ($songs_array as $index => $song): ?>
                    <tr class="song-row" onclick="playFromAlbum(<?= $index; ?>)" data-title="<?= strtolower(htmlspecialchars($song['title'])); ?>">
                        <td class="index-col">
                            <span class="song-index"><?= $index + 1; ?></span>
                            <span class="play-icon-small">▶</span>
                        </td>
                        <td style="padding: 12px 10px;">
                            <div style="font-weight: 500; color: #fff;"><?= htmlspecialchars($song['title']); ?></div>
                            <div style="font-size: 13px; color: #b3b3b3;"><?= htmlspecialchars($song['artist']); ?></div>
                        </td>
                        <td style="padding: 12px 10px; color: #b3b3b3;">
                            <?= floor($song['duration'] / 60) . ':' . sprintf('%02d', $song['duration'] % 60); ?>
                        </td>
                        <td style="position: relative; text-align: center;">
                            <button class="more-btn" onclick="toggleSongMenu(event, <?= $song['id_song']; ?>)">•••</button>
                            <div id="menu-<?= $song['id_song']; ?>" class="dropdown-content">
                                <a href="song_details.php?id=<?= $song['id_song']; ?>">💬 Відгуки та деталі</a>
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <a href="#" onclick="openPlaylistModal(event, <?= $song['id_song']; ?>)">➕ Додати до плейліста</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="height: 100px;"></div>
    </main>

    <div id="playlist-modal">
        <div style="background: #282828; padding: 30px; border-radius: 12px; width: 360px; color: white;">
            <h3 style="margin-top: 0;">Додати до плейліста</h3>
            <form action="add_to_playlist.php" method="POST">
                <input type="hidden" name="song_id" id="modal-song-id">
                <select name="playlist_id" required style="width: 100%; padding: 12px; background: #3e3e3e; color: white; border: none; border-radius: 4px; margin: 15px 0 25px;">
                    <?php foreach ($userPlaylists as $pl): ?>
                        <option value="<?= $pl['id_playlist'] ?>"><?= htmlspecialchars($pl['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div style="display: flex; justify-content: flex-end; gap: 15px;">
                    <button type="button" onclick="closeModal()" style="background:none; color:#b3b3b3; border:none; cursor:pointer;">СКАСУВАТИ</button>
                    <button type="submit" style="background:#1db954; color:black; border:none; padding:10px 20px; border-radius:20px; font-weight:bold; cursor:pointer;">ДОДАТИ</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        window.currentPlaylist = <?php
        foreach($songs_array as &$s) { $s['imageURL'] = $album['imageURL']; }
        echo json_encode($songs_array);
        ?>;

        function playFromAlbum(index) {
            const song = window.currentPlaylist[index];
            if (typeof playSong === 'function') {
                playSong(song.title, song.artist, song.imageURL, song.audioURL);
                window.currentSongIndex = index;
            }
        }

        function filterTracks() {
            const query = document.getElementById('trackSearch').value.toLowerCase();
            document.querySelectorAll('.song-row').forEach(row => {
                row.style.display = row.getAttribute('data-title').includes(query) ? "" : "none";
            });
        }

        function toggleSongMenu(e, id) {
            e.stopPropagation();
            document.querySelectorAll('.dropdown-content').forEach(m => {
                if (m.id !== 'menu-'+id) m.classList.remove('show');
            });
            document.getElementById('menu-'+id).classList.toggle('show');
        }

        function openPlaylistModal(e, songId) {
            e.preventDefault(); e.stopPropagation();
            document.getElementById('modal-song-id').value = songId;
            document.getElementById('playlist-modal').style.display = 'flex';
        }

        function closeModal() { document.getElementById('playlist-modal').style.display = 'none'; }

        window.onclick = (e) => {
            if (!e.target.matches('.more-btn')) {
                document.querySelectorAll('.dropdown-content').forEach(m => m.classList.remove('show'));
            }
            if (e.target == document.getElementById('playlist-modal')) closeModal();
        }
    </script>

<?php require_once 'includes/footer.php'; ?>