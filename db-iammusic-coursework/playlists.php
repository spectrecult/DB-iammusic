<?php
require_once 'includes/db.php';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// Перевірка авторизації
if (!isset($_SESSION['user_id'])) {
    echo "<main class='main-content' style='padding: 40px; color: white;'>
            <h2>Будь ласка, <a href='login.php' style='color: #1db954;'>увійдіть</a>, щоб керувати плейлістами.</h2>
          </main>";
    include_once 'includes/footer.php';
    exit;
}

$userId = $_SESSION['user_id'];

//Функція для звітності: перевірка тривалості
function formatDuration($seconds) {
    if ($seconds <= 0) return "0 хв.";
    $minutes = floor($seconds / 60);
    return $minutes . " хв.";
}

// СТВОРЕННЯ ПЛЕЙЛІСТА
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['playlist_name'])) {
    $name = trim($_POST['playlist_name']);
    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO dbo.Playlist (id_user, [name]) VALUES (?, ?)");
        $stmt->execute([$userId, $name]);
        header("Location: playlists.php");
        exit;
    }
}

// ОТРИМАННЯ ПЛЕЙЛІСТІВ + ІНТЕГРОВАНА ЗВІТНІСТЬ (Агрегація даних)
$stmt = $pdo->prepare("
    SELECT 
        p.id_playlist,
        CAST(p.name AS NVARCHAR(MAX)) as name, 
        COUNT(pd.id_song) as total_songs,
        ISNULL(SUM(s.duration), 0) as total_seconds
    FROM dbo.Playlist p
    LEFT JOIN dbo.Playlist_Details pd ON p.id_playlist = pd.id_playlist
    LEFT JOIN dbo.Song s ON pd.id_song = s.id_song
    WHERE p.id_user = ? 
    GROUP BY p.id_playlist, CAST(p.name AS NVARCHAR(MAX))
    ORDER BY p.id_playlist DESC
");
$stmt->execute([$userId]);
$playlists = $stmt->fetchAll();
?>

    <main class="main-content" style="padding: 40px; color: white; padding-bottom: 120px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
            <h1 style="font-size: 32px; font-weight: 700;">Мої плейлісти</h1>
            <button onclick="document.getElementById('create-modal').style.display='flex'"
                    style="background: #1db954; color: white; border: none; padding: 12px 25px; border-radius: 25px; font-weight: bold; cursor: pointer; transition: 0.2s;"
                    onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                + СТВОРИТИ ПЛЕЙЛІСТ
            </button>
        </div>

        <?php if (count($playlists) > 0): ?>
            <div class="song-grid">
                <?php foreach ($playlists as $pl): ?>
                    <div class="card" onclick="location.href='playlist_details.php?id=<?= $pl['id_playlist'] ?>'">

                        <div class="img-container" style="display: flex; align-items: center; justify-content: center; background: #282828;">
                            <span style="font-size: 64px;">🎵</span>

                            <button class="play-btn">
                                <span>▶</span>
                            </button>
                        </div>

                        <h4><?= htmlspecialchars($pl['name']) ?></h4>

                        <p style="margin-bottom: 4px;">Плейліст • <?= htmlspecialchars($_SESSION['username']) ?></p>
                        <p style="color: #b3b3b3; font-size: 13px; line-height: 1.4;">
                            📄 Треків: <?= $pl['total_songs'] ?><br>
                            ⏱ Час: <?= formatDuration($pl['total_seconds']) ?>
                        </p>

                        <div style="margin-top: 10px; font-size: 11px; color: #1db954; border-top: 1px solid #333; pt: 5px;">
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color: #b3b3b3;">У вас поки немає плейлістів. Створіть перший!</p>
        <?php endif; ?>

        <div id="create-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center;">
            <div style="background: #282828; padding: 30px; border-radius: 12px; width: 400px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
                <h2 style="margin-top: 0;">Новий плейліст</h2>
                <form method="POST">
                    <input type="text" name="playlist_name" placeholder="Назва плейліста" required
                           style="width: 100%; padding: 12px; border-radius: 4px; border: none; background: #3e3e3e; color: white; margin: 20px 0;">
                    <div style="display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" onclick="document.getElementById('create-modal').style.display='none'"
                                style="background: transparent; color: white; border: none; cursor: pointer; font-weight: bold;">Скасувати</button>
                        <button type="submit" style="background: #1db954; color: white; border: none; padding: 10px 20px; border-radius: 20px; font-weight: bold; cursor: pointer;">Створити</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

<?php require_once 'includes/footer.php'; ?>