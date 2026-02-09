<?php
require_once 'includes/db.php';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// Отримання або ID, або Title
$songIdParam = isset($_GET['id']) ? intval($_GET['id']) : 0;
$songTitleParam = isset($_GET['title']) ? $_GET['title'] : '';

// ОТРИМАННЯ ДАНИХ ПРО ПІСНЮ
if ($songIdParam > 0) {
    $stmt = $pdo->prepare("
        SELECT s.*, a.title as album_name, a.imageURL 
        FROM dbo.Song s
        LEFT JOIN dbo.Album a ON s.id_album = a.id_album
        WHERE s.id_song = ?
    ");
    $stmt->execute([$songIdParam]);
} else {
    $stmt = $pdo->prepare("
        SELECT s.*, a.title as album_name, a.imageURL 
        FROM dbo.Song s
        LEFT JOIN dbo.Album a ON s.id_album = a.id_album
        WHERE s.title = ?
    ");
    $stmt->execute([$songTitleParam]);
}

$song = $stmt->fetch();

if (!$song) {
    echo "<main class='main-content' style='padding: 40px; color: white;'><h2>Пісню не знайдено</h2></main>";
    include_once 'includes/footer.php';
    exit;
}

$songId = $song['id_song'];
$currentUrl = "song_details.php?id=" . $songId;

// ЛОГІКА ДОДАВАННЯ ВІДГУКУ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id']) && isset($_POST['comment'])) {
    $comment = trim($_POST['comment']);
    if (!empty($comment)) {
        $ins = $pdo->prepare("INSERT INTO dbo.Review (id_user, id_song, content, timestamp) VALUES (?, ?, ?, GETDATE())");
        $ins->execute([$_SESSION['user_id'], $songId, $comment]);
        header("Location: " . $currentUrl);
        exit;
    }
}

// Отримання плейлістів
$userPlaylists = [];
if (isset($_SESSION['user_id'])) {
    $plStmt = $pdo->prepare("SELECT id_playlist, name FROM dbo.Playlist WHERE id_user = ?");
    $plStmt->execute([$_SESSION['user_id']]);
    $userPlaylists = $plStmt->fetchAll();
}

// ОТРИМАННЯ ВІДГУКІВ
$reviewsStmt = $pdo->prepare("
    SELECT r.*, u.username 
    FROM dbo.Review r
    JOIN dbo.[User] u ON r.id_user = u.id_user
    WHERE r.id_song = ?
    ORDER BY r.timestamp DESC
");
$reviewsStmt->execute([$songId]);
$reviews = $reviewsStmt->fetchAll();
?>

    <main class="main-content" style="padding: 40px; color: white; padding-bottom: 120px;">
        <div style="display: flex; gap: 30px; align-items: flex-end; margin-bottom: 40px;">
            <img src="<?= htmlspecialchars($song['imageURL'] ?: 'img/default.png') ?>" style="width: 250px; height: 250px; border-radius: 8px; object-fit: cover; box-shadow: 0 8px 24px rgba(0,0,0,0.5);">
            <div>
                <p style="text-transform: uppercase; font-size: 12px; font-weight: bold; color: #b3b3b3;">Пісня</p>
                <h1 style="font-size: 72px; margin: 10px 0;"><?= htmlspecialchars($song['title']) ?></h1>
                <p style="font-size: 18px;">
                    <strong><?= htmlspecialchars($song['artist']) ?></strong> • <?= htmlspecialchars($song['album_name'] ?? 'Сингл') ?>
                </p>
            </div>
        </div>

        <div style="display: flex; gap: 15px;">
            <button onclick="playSong('<?= addslashes($song['title']) ?>', '<?= addslashes($song['artist']) ?>', '<?= $song['imageURL'] ?>', '<?= $song['audioURL'] ?>')"
                    style="background: #1db954; border: none; padding: 15px 40px; border-radius: 30px; color: white; font-weight: bold; cursor: pointer;">
                СЛУХАТИ
            </button>

            <?php if (isset($_SESSION['user_id'])): ?>
                <button onclick="document.getElementById('playlist-modal').style.display='flex'"
                        style="background: transparent; border: 2px solid #b3b3b3; padding: 13px 30px; border-radius: 30px; color: white; font-weight: bold; cursor: pointer;">
                    ДОДАТИ В ПЛЕЙЛІСТ
                </button>
            <?php endif; ?>
        </div>

        <hr style="border: 0.1px solid #282828; margin: 40px 0;">

        <section style="max-width: 800px;">
            <h3 style="margin-bottom: 20px;">Відгуки слухачів (<?= count($reviews) ?>)</h3>

            <?php if (isset($_SESSION['user_id'])): ?>
                <form method="POST" style="margin-bottom: 30px;">
                <textarea name="comment" placeholder="Напишіть свою думку про трек..." required
                          style="width: 100%; height: 80px; background: #282828; border: none; border-radius: 8px; padding: 15px; color: white; margin-bottom: 10px; resize: none;"></textarea>
                    <button type="submit" style="background: white; color: black; border: none; padding: 10px 25px; border-radius: 20px; font-weight: bold; cursor: pointer;">Опублікувати</button>
                </form>
            <?php else: ?>
                <p style="color: #b3b3b3; margin-bottom: 30px;">Увійдіть, щоб залишити відгук.</p>
            <?php endif; ?>

            <div class="reviews-list">
                <?php foreach ($reviews as $rev): ?>
                    <div id="review-box-<?= $rev['id_review'] ?>" style="background: #181818; padding: 20px; border-radius: 8px; margin-bottom: 15px; border: 1px solid #282828; position: relative;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <strong style="color: #1db954;"><?= htmlspecialchars($rev['username']) ?></strong>
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <span style="font-size: 11px; color: #b3b3b3;"><?= date('d.m.Y H:i', strtotime($rev['timestamp'])) ?></span>

                                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $rev['id_user']): ?>
                                    <div style="display: flex; gap: 10px;">
                                        <button onclick="toggleEdit(<?= $rev['id_review'] ?>)" style="background: none; border: none; color: #b3b3b3; cursor: pointer; font-size: 14px; padding: 0;" title="Редагувати">
                                            ✏️
                                        </button>

                                        <form action="delete_review.php" method="POST" onsubmit="return confirm('Видалити цей відгук?');" style="display: inline;">
                                            <input type="hidden" name="id_review" value="<?= $rev['id_review'] ?>">
                                            <button type="submit" style="background: none; border: none; color: #fa5858; cursor: pointer; font-size: 16px; padding: 0; line-height: 1;" title="Видалити">
                                                🗑
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <p id="content-display-<?= $rev['id_review'] ?>" style="margin: 0; line-height: 1.5;"><?= nl2br(htmlspecialchars($rev['content'])) ?></p>

                        <form id="edit-form-<?= $rev['id_review'] ?>" action="edit_review.php" method="POST" style="display: none; margin-top: 10px;">
                            <input type="hidden" name="id_review" value="<?= $rev['id_review'] ?>">
                            <textarea name="content" required style="width: 100%; height: 60px; background: #282828; border: 1px solid #1db954; border-radius: 8px; padding: 10px; color: white; resize: none;"><?= htmlspecialchars($rev['content']) ?></textarea>
                            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px;">
                                <button type="button" onclick="toggleEdit(<?= $rev['id_review'] ?>)" style="background: transparent; color: #b3b3b3; border: none; cursor: pointer; font-size: 12px;">Скасувати</button>
                                <button type="submit" style="background: #1db954; color: white; border: none; padding: 5px 15px; border-radius: 15px; cursor: pointer; font-weight: bold; font-size: 12px;">Зберегти</button>
                            </div>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <div id="playlist-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center;">
        <div style="background: #282828; padding: 30px; border-radius: 12px; width: 400px; color: white;">
            <h2 style="margin-top: 0;">Оберіть плейліст</h2>
            <form action="add_to_playlist.php" method="POST">
                <input type="hidden" name="song_id" value="<?= $songId ?>">
                <select name="playlist_id" required style="width: 100%; padding: 12px; background: #3e3e3e; color: white; border: none; border-radius: 4px; margin: 20px 0;">
                    <?php if(empty($userPlaylists)): ?>
                        <option disabled>У вас немає плейлістів</option>
                    <?php else: ?>
                        <?php foreach ($userPlaylists as $pl): ?>
                            <option value="<?= $pl['id_playlist'] ?>"><?= htmlspecialchars($pl['name']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="document.getElementById('playlist-modal').style.display='none'" style="background: transparent; color: white; border: none; cursor: pointer;">Скасувати</button>
                    <button type="submit" <?= empty($userPlaylists) ? 'disabled' : '' ?> style="background: #1db954; color: white; border: none; padding: 10px 20px; border-radius: 20px; font-weight: bold; cursor: pointer;">Додати</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Функція для перемикання між текстом та формою редагування
        function toggleEdit(id) {
            const display = document.getElementById('content-display-' + id);
            const form = document.getElementById('edit-form-' + id);

            if (form.style.display === 'none') {
                display.style.display = 'none';
                form.style.display = 'block';
            } else {
                display.style.display = 'block';
                form.style.display = 'none';
            }
        }

        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('openModal')) {
                document.getElementById('playlist-modal').style.display = 'flex';
            }
        }
    </script>

<?php require_once 'includes/footer.php'; ?>