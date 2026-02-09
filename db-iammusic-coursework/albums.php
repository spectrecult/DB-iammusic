<?php
require_once 'includes/db.php';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

$query = "SELECT id_album, title, artist, imageURL, releaseYear FROM dbo.Album";
$result = $pdo->query($query);
?>

    <style>
        .albums-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 24px;
            padding-top: 20px;
        }

        .album-card {
            background: #181818;
            padding: 16px;
            border-radius: 8px;
            text-decoration: none;
            color: white;
            transition: background 0.3s ease;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .album-card:hover {
            background: #282828;
        }

        .img-wrapper {
            position: relative;
            width: 100%;
            aspect-ratio: 1/1;
            margin-bottom: 12px;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(0,0,0,0.5);
        }

        .album-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .album-card h3 {
            margin: 0 0 8px 0;
            font-size: 16px;
            font-weight: 700;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .album-card p {
            color: #b3b3b3;
            font-size: 14px;
            margin: 0;
        }

        .play-btn {
            position: absolute;
            bottom: 8px;
            right: 8px;
            background: #1db954;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: black;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
            box-shadow: 0 8px 16px rgba(0,0,0,0.6);
            border: none;
            cursor: pointer;
        }

        .play-btn span {
            font-size: 22px;
            margin-left: 3px;
        }

        .album-card:hover .play-btn {
            opacity: 1;
            transform: translateY(0);
        }

        .play-btn:hover {
            background-color: #1ed760;
            transform: scale(1.06);
        }
    </style>

    <main class="main-content" style="padding: 24px; min-height: 100vh;">
        <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 8px;">Музичні альбоми</h2>

        <div class="albums-grid">
            <?php while($album = $result->fetch(PDO::FETCH_ASSOC)): ?>
                <a href="album_details.php?id=<?= $album['id_album']; ?>" class="album-card">
                    <div class="img-wrapper">
                        <img src="<?= htmlspecialchars($album['imageURL'] ?: 'img/default_album.png'); ?>" alt="Cover">
                        <button class="play-btn">
                            <span>▶</span>
                        </button>
                    </div>
                    <h3><?= htmlspecialchars($album['title']); ?></h3>
                    <p><?= htmlspecialchars($album['artist']); ?> • <?= htmlspecialchars($album['releaseYear']); ?></p>
                </a>
            <?php endwhile; ?>
        </div>
    </main>

<?php require_once 'includes/footer.php'; ?>