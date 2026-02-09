<?php
require_once 'includes/db.php';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

$playlistId = isset($_GET['id']) ? intval($_GET['id']) : 0;

$stmt = $pdo->prepare("SELECT * FROM dbo.Playlist WHERE id_playlist = ?");
$stmt->execute([$playlistId]);
$playlist = $stmt->fetch();

if (!$playlist) {
    echo "<main class='main-content' style='padding: 40px; color: white;'><h2>Плейліст не знайдено</h2></main>";
    include_once 'includes/footer.php';
    exit;
}

$songsStmt = $pdo->prepare("
    SELECT s.*, a.imageURL 
    FROM dbo.Song s
    JOIN dbo.Playlist_Details pd ON s.id_song = pd.id_song
    LEFT JOIN dbo.Album a ON s.id_album = a.id_album
    WHERE pd.id_playlist = ?
");
$songsStmt->execute([$playlistId]);
$playlistSongs = $songsStmt->fetchAll();
?>

    <style>
        .main-content {
            padding: 0;
            background: #121212;
            color: white;
            min-height: 100vh;
            padding-bottom: 200px !important;
        }

        .playlist-header {
            position: relative;
            padding: 60px 30px 30px;
            display: flex;
            align-items: flex-end;
            gap: 30px;
            background: linear-gradient(to bottom, #282828, #121212);
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

        .song-row { transition: background 0.2s; color: #fff; cursor: default; }
        .song-row:hover { background: rgba(255,255,255,0.1); }
        .index-col { width: 45px; text-align: center; }
        .play-icon-small { display: none; cursor: pointer; color: #1db954; }
        .song-row:hover .song-index { display: none; }
        .song-row:hover .play-icon-small { display: inline-block; }
        .song-title { font-weight: bold; transition: 0.2s; }
        .song-row:hover .song-title { color: #1db954; }

        .remove-btn { color: #b3b3b3; cursor: pointer; opacity: 0; transition: 0.2s; font-size: 18px; }
        .song-row:hover .remove-btn { opacity: 1; }
        .remove-btn:hover { color: #fa5858 !important; transform: scale(1.2); }
    </style>

    <main class="main-content">
        <div class="playlist-header">
            <div class="search-container">
                <svg class="search-icon-svg" viewBox="0 0 24 24">
                    <path d="M10.533 1.27a9.262 9.262 0 1 0 5.739 16.506l4.747 4.748a.75.75 0 1 0 1.061-1.061l-4.748-4.747a9.262 9.262 0 0 0-6.799-15.446zm-7.762 9.262a7.762 7.762 0 1 1 15.524 0 7.762 7.762 0 0 1-15.524 0z"></path>
                </svg>
                <input type="text" id="playlistSearch" placeholder="Пошук у плейлісті" onkeyup="filterPlaylist()">
            </div>

            <div style="width: 232px; height: 232px; background: #282828; display: flex; align-items: center; justify-content: center; border-radius: 4px; overflow: hidden; box-shadow: 0 8px 40px rgba(0,0,0,0.5);">
                <?php
                $playlistCover = (!empty($playlistSongs) && !empty($playlistSongs[0]['imageURL']))
                        ? $playlistSongs[0]['imageURL']
                        : 'assets/default_playlist.png';
                ?>
                <img src="<?= htmlspecialchars($playlistCover); ?>" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            <div>
                <p style="text-transform: uppercase; font-size: 12px; font-weight: bold; color: #b3b3b3; margin-bottom: 8px;">плейліст</p>
                <h1 style="font-size: 72px; margin: 0 0 10px 0; font-weight: 900; letter-spacing: -2px;"><?= htmlspecialchars($playlist['name']); ?></h1>
                <p style="color: #fff; font-size: 14px;"><strong><?= $_SESSION['username'] ?? 'Користувач'; ?></strong> • <span id="track-count"><?= count($playlistSongs); ?></span> треків</p>
            </div>
        </div>

        <div style="padding: 30px 30px;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.1); color: #b3b3b3; font-size: 12px; text-transform: uppercase;">
                    <th style="padding: 10px; width: 45px; text-align: center;">#</th>
                    <th style="padding: 10px;">назва</th>
                    <th style="padding: 10px;">виконавець</th>
                    <th style="padding: 10px; text-align: right; padding-right: 20px;">час</th>
                    <th style="padding: 10px; width: 50px;"></th>
                </tr>
                </thead>
                <tbody id="playlist-body">
                <?php if (count($playlistSongs) > 0): ?>
                    <?php foreach ($playlistSongs as $i => $s):
                        $img = $s['imageURL'] ?? 'assets/default_cover.png';
                        $searchData = strtolower(htmlspecialchars($s['title'] . ' ' . $s['artist']));
                        ?>
                        <tr class="song-row" id="track-row-<?= $s['id_song']; ?>" data-search="<?= $searchData; ?>">
                            <td class="index-col" onclick="playPlaylistSong(<?= $i; ?>)" style="padding: 12px 10px;">
                                <span class="song-index" style="color: #b3b3b3;"><?= $i + 1; ?></span>
                                <span class="play-icon-small">▶</span>
                            </td>
                            <td onclick="playPlaylistSong(<?= $i; ?>)" style="padding: 12px 10px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <img src="<?= htmlspecialchars($img); ?>" style="width: 40px; height: 40px; border-radius: 4px; object-fit: cover;">
                                    <span class="song-title"><?= htmlspecialchars($s['title']); ?></span>
                                </div>
                            </td>
                            <td style="padding: 12px 10px; color: #b3b3b3;"><?= htmlspecialchars($s['artist']); ?></td>
                            <td style="padding: 12px 10px; text-align: right; padding-right: 20px; color: #b3b3b3;">
                                <?= floor(($s['duration'] ?? 0) / 60) . ':' . sprintf('%02d', ($s['duration'] ?? 0) % 60); ?>
                            </td>
                            <td style="padding: 12px 10px; text-align: center;">
                                <span class="remove-btn" title="Видалити" onclick="removeFromPlaylist(event, <?= $s['id_song']; ?>, <?= $playlistId; ?>)">🗑</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="padding: 40px; text-align: center; color: #b3b3b3;">Плейліст порожній</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="height: 100px;"></div>
    </main>

    <script>
        window.currentPlaylist = <?= json_encode(array_map(function($s) {
            return [
                    'title' => $s['title'],
                    'artist' => $s['artist'],
                    'imageURL' => $s['imageURL'] ?? 'assets/default_cover.png',
                    'audioURL' => $s['audioURL']
            ];
        }, $playlistSongs)); ?>;

        function playPlaylistSong(index) {
            if (window.currentPlaylist && window.currentPlaylist[index]) {
                const song = window.currentPlaylist[index];
                if (typeof playSong === 'function') {
                    playSong(song.title, song.artist, song.imageURL, song.audioURL);
                    window.currentSongIndex = index;
                }
            }
        }

        function filterPlaylist() {
            const query = document.getElementById('playlistSearch').value.toLowerCase();
            const rows = document.querySelectorAll('.song-row');
            rows.forEach(row => {
                row.style.display = row.getAttribute('data-search').includes(query) ? "" : "none";
            });
        }

        function removeFromPlaylist(event, songId, playlistId) {
            event.stopPropagation();
            if (!confirm('Видалити трек?')) return;
            fetch('remove_from_playlist.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id_song=${songId}&id_playlist=${playlistId}`
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const row = document.getElementById(`track-row-${songId}`);
                        row.style.opacity = '0';
                        setTimeout(() => {
                            row.remove();
                            document.getElementById('track-count').innerText = parseInt(document.getElementById('track-count').innerText) - 1;
                        }, 300);
                    }
                });
        }
    </script>

<?php require_once 'includes/footer.php'; ?>