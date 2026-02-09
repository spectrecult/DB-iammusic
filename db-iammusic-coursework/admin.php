<?php
require_once 'includes/auth_check.php';
requireAdmin(); // session_start() та перевірка ролі вже всередині
require_once 'includes/db.php';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

$message = "";

// ОБРОБКА ДІЙ (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        //КОРИСТУВАЧІ
        if (isset($_POST['delete_user'])) {
            $u_id = intval($_POST['id_user']);
            if ($u_id === (int)$_SESSION['user_id']) {
                $message = "<div class='status-msg error'>❌ Ви не можете видалити себе!</div>";
            } else {
                $pdo->beginTransaction();
                $pdo->prepare("DELETE FROM dbo.Review WHERE id_user = ?")->execute([$u_id]);
                $pdo->prepare("DELETE FROM dbo.Playlist_Details WHERE id_playlist IN (SELECT id_playlist FROM dbo.Playlist WHERE id_user = ?)")->execute([$u_id]);
                $pdo->prepare("DELETE FROM dbo.Playlist WHERE id_user = ?")->execute([$u_id]);
                $pdo->prepare("DELETE FROM dbo.[User] WHERE id_user = ?")->execute([$u_id]);
                $pdo->commit();
                $message = "<div class='status-msg success'>🗑️ Користувача видалено!</div>";
            }
        }

        if (isset($_POST['change_role'])) {
            $u_id = intval($_POST['id_user']);
            if ($u_id !== (int)$_SESSION['user_id']) {
                $new_role = (int)$_POST['current_role'] === 1 ? 0 : 1;
                $pdo->prepare("UPDATE dbo.[User] SET role = ? WHERE id_user = ?")->execute([$new_role, $u_id]);
                $message = "<div class='status-msg success'>✅ Роль змінено!</div>";
            }
        }
        //АЛЬБОМИ ТА ТРЕКИ
        if (isset($_POST['delete_album'])) {
            $id = intval($_POST['id_album']);
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM dbo.Song WHERE id_album = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM dbo.Album WHERE id_album = ?")->execute([$id]);
            $pdo->commit();
            $message = "<div class='status-msg success'>🗑️ Альбом видалено!</div>";
        }

        if (isset($_POST['delete_track'])) {
            $pdo->prepare("DELETE FROM dbo.Song WHERE id_song = ?")->execute([intval($_POST['id_song'])]);
            $message = "<div class='status-msg success'>🗑️ Трек видалено!</div>";
        }

        if (isset($_POST['add_album'])) {
            $pdo->prepare("INSERT INTO dbo.Album (title, artist, imageURL, releaseYear) VALUES (?, ?, ?, ?)")
                    ->execute([trim($_POST['title']), trim($_POST['artist']), trim($_POST['imageURL']), intval($_POST['releaseYear'])]);
            $message = "<div class='status-msg success'>✅ Альбом додано!</div>";
        }

        if (isset($_POST['add_track'])) {
            $pdo->prepare("INSERT INTO dbo.Song (id_album, title, artist, duration, audioURL) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$_POST['id_album'], trim($_POST['track_title']), trim($_POST['track_artist']), intval($_POST['duration']), trim($_POST['audioURL'])]);
            $message = "<div class='status-msg success'>✅ Трек додано!</div>";
        }

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $message = "<div class='status-msg error'>❌ Помилка БД: " . $e->getMessage() . "</div>";
    }
}

// ОТРИМАННЯ ДАНИХ
$albums = $pdo->query("SELECT * FROM dbo.Album ORDER BY id_album DESC")->fetchAll(PDO::FETCH_ASSOC);
$songs  = $pdo->query("SELECT TOP 30 s.*, a.title as album_title FROM dbo.Song s JOIN dbo.Album a ON s.id_album = a.id_album ORDER BY s.id_song DESC")->fetchAll(PDO::FETCH_ASSOC);
$users  = $pdo->query("SELECT id_user, username, email, role, dateJoined FROM dbo.[User] ORDER BY role DESC, username ASC")->fetchAll(PDO::FETCH_ASSOC);

// ДАНІ ДЛЯ ЗВІТНОСТІ
$report_albums = $pdo->query("
    SELECT a.title, 
    (SELECT COUNT(*) FROM dbo.Song WHERE id_album = a.id_album) as song_count,
    (SELECT COUNT(*) FROM dbo.Review r JOIN dbo.Song s ON r.id_song = s.id_song WHERE s.id_album = a.id_album) as review_count
    FROM dbo.Album a ORDER BY review_count DESC
")->fetchAll(PDO::FETCH_ASSOC);

$report_users = $pdo->query("
    SELECT u.username, 
    (SELECT COUNT(*) FROM dbo.Playlist WHERE id_user = u.id_user) as p_count,
    (SELECT COUNT(*) FROM dbo.Review WHERE id_user = u.id_user) as r_count
    FROM dbo.[User] u ORDER BY r_count DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .admin-container { padding: 40px; color: white; padding-bottom: 150px; }
    .tab-nav { display: flex; gap: 10px; margin-bottom: 30px; border-bottom: 1px solid #282828; padding-bottom: 15px; }
    .tab-btn { background: #282828; color: white; border: none; padding: 12px 25px; border-radius: 20px; cursor: pointer; font-weight: bold; transition: 0.3s; }
    .tab-btn.active { background: #1db954; color: black; }
    .tab-btn:hover:not(.active) { background: #3e3e3e; }

    .tab-btn.report-btn { border: 1px solid #1db954; color: #1db954; }
    .tab-btn.report-btn.active { color: black; background: #1db954; }

    .form-section { display: none; animation: fadeIn 0.3s ease; }
    .form-section.active { display: block; }

    .flex-layout { display: flex; gap: 40px; align-items: flex-start; flex-wrap: wrap; }
    .admin-form { background: #181818; padding: 30px; border-radius: 12px; width: 350px; display: flex; flex-direction: column; gap: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
    .admin-form h3 { margin-top: 0; color: #1db954; }
    .admin-form input, .admin-form select { padding: 12px; border-radius: 6px; border: 1px solid #333; background: #000; color: white; outline: none; }
    .admin-form input:focus { border-color: #1db954; }

    .data-table-container { flex: 1; min-width: 500px; background: #181818; border-radius: 12px; overflow: hidden; margin-bottom: 30px; }
    .data-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .data-table th, .data-table td { padding: 15px; text-align: left; border-bottom: 1px solid #282828; }
    .data-table th { background: #282828; color: #b3b3b3; text-transform: uppercase; font-size: 11px; }
    .data-table tr:hover { background: rgba(255,255,255,0.05); }

    .del-btn { background: transparent; color: #ff4444; border: 1px solid #ff4444; padding: 6px 12px; border-radius: 4px; cursor: pointer; transition: 0.3s; font-size: 11px; }
    .del-btn:hover { background: #ff4444; color: white; }

    .role-badge { padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; }
    .badge-admin { background: #1db954; color: black; }
    .badge-user { background: #555; color: white; }

    .submit-btn { background: #1db954; color: black; border: none; padding: 15px; border-radius: 30px; font-weight: bold; cursor: pointer; transition: 0.3s; margin-top: 10px; }
    .submit-btn:hover { background: #1ed760; transform: scale(1.02); }

    .status-msg { padding: 15px; border-radius: 6px; margin-bottom: 25px; font-weight: 500; }
    .success { background: rgba(29, 185, 84, 0.1); color: #1db954; border: 1px solid #1db954; }
    .error { background: rgba(255, 68, 68, 0.1); color: #ff4444; border: 1px solid #ff4444; }

    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>

<main class="main-content">
    <div class="admin-container">
        <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h1 style="margin: 0;">⚙️ Панель адміністратора</h1>
            <span style="background: #1db954; color: black; padding: 5px 15px; border-radius: 20px; font-weight: bold; font-size: 12px;">ADMIN MODE</span>
        </header>

        <?= $message ?>

        <div class="tab-nav">
            <button class="tab-btn active" onclick="openTab('album-tab', this)">💿 АЛЬБОМИ</button>
            <button class="tab-btn" onclick="openTab('track-tab', this)">🎵 ТРЕКИ</button>
            <button class="tab-btn" onclick="openTab('user-tab', this)">👥 КОРИСТУВАЧІ</button>
            <button class="tab-btn report-btn" onclick="openTab('report-tab', this)">📊 ЗВІТНІСТЬ</button>
        </div>

        <section id="album-tab" class="form-section active">
            <div class="flex-layout">
                <form class="admin-form" method="POST">
                    <h3>Новий альбом</h3>
                    <input type="text" name="title" placeholder="Назва" required>
                    <input type="text" name="artist" placeholder="Виконавець" required>
                    <input type="text" name="imageURL" placeholder="URL обкладинки">
                    <input type="number" name="releaseYear" value="<?= date('Y') ?>">
                    <button type="submit" name="add_album" class="submit-btn">СТВОРИТИ</button>
                </form>

                <div class="data-table-container">
                    <table class="data-table">
                        <thead><tr><th>ID</th><th>Обкладинка</th><th>Назва</th><th>Дія</th></tr></thead>
                        <tbody>
                        <?php foreach ($albums as $a): ?>
                            <tr>
                                <td><?= $a['id_album'] ?></td>
                                <td><img src="<?= $a['imageURL'] ?: 'img/default.png' ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;"></td>
                                <td><b><?= htmlspecialchars($a['title']) ?></b><br><small><?= htmlspecialchars($a['artist']) ?></small></td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Видалити альбом?')">
                                        <input type="hidden" name="id_album" value="<?= $a['id_album'] ?>">
                                        <button type="submit" name="delete_album" class="del-btn">Видалити</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section id="track-tab" class="form-section">
            <div class="flex-layout">
                <form class="admin-form" method="POST">
                    <h3>Новий трек</h3>
                    <select name="id_album" required>
                        <option value="" disabled selected>Альбом</option>
                        <?php foreach ($albums as $a): ?>
                            <option value="<?= $a['id_album'] ?>"><?= htmlspecialchars($a['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="track_title" placeholder="Назва" required>
                    <input type="text" name="track_artist" placeholder="Артист">
                    <input type="number" name="duration" placeholder="Сек">
                    <input type="text" name="audioURL" placeholder="URL файлу">
                    <button type="submit" name="add_track" class="submit-btn">ДОДАТИ</button>
                </form>

                <div class="data-table-container">
                    <table class="data-table">
                        <thead><tr><th>Назва</th><th>Альбом</th><th>Дія</th></tr></thead>
                        <tbody>
                        <?php foreach ($songs as $s): ?>
                            <tr>
                                <td><b><?= htmlspecialchars($s['title']) ?></b></td>
                                <td><?= htmlspecialchars($s['album_title']) ?></td>
                                <td>
                                    <form method="POST"><input type="hidden" name="id_song" value="<?= $s['id_song'] ?>">
                                        <button type="submit" name="delete_track" class="del-btn">Видалити</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section id="user-tab" class="form-section">
            <div class="data-table-container">
                <table class="data-table">
                    <thead><tr><th>ID</th><th>Користувач</th><th>Дата</th><th>Роль</th><th>Дії</th></tr></thead>
                    <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= $u['id_user'] ?></td>
                            <td><b><?= htmlspecialchars($u['username']) ?></b><br><small><?= htmlspecialchars($u['email']) ?></small></td>
                            <td><?= date('d.m.Y', strtotime($u['dateJoined'])) ?></td>
                            <td><span class="role-badge <?= $u['role'] == 1 ? 'badge-admin' : 'badge-user' ?>"><?= $u['role'] == 1 ? 'ADMIN' : 'USER' ?></span></td>
                            <td>
                                <div style="display: flex; gap: 8px;">
                                    <form method="POST">
                                        <input type="hidden" name="id_user" value="<?= $u['id_user'] ?>">
                                        <input type="hidden" name="current_role" value="<?= $u['role'] ?>">
                                        <button type="submit" name="change_role" class="tab-btn" style="padding: 5px 12px; font-size: 11px;">Роль</button>
                                    </form>
                                    <?php if ($u['id_user'] != $_SESSION['user_id']): ?>
                                        <form method="POST" onsubmit="return confirm('Видалити?')">
                                            <input type="hidden" name="id_user" value="<?= $u['id_user'] ?>">
                                            <button type="submit" name="delete_user" class="del-btn">Видалити</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="report-tab" class="form-section">
            <div class="flex-layout">
                <div class="data-table-container">
                    <h3 style="padding: 20px; color: #1db954; margin: 0;">📊 Популярність Альбомів</h3>
                    <table class="data-table">
                        <thead>
                        <tr><th>Альбом</th><th>Пісень</th><th>Відгуків</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($report_albums as $ra): ?>
                            <tr>
                                <td><b><?= htmlspecialchars($ra['title']) ?></b></td>
                                <td><?= $ra['song_count'] ?></td>
                                <td style="color: #1db954; font-weight: bold;"><?= $ra['review_count'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="data-table-container">
                    <h3 style="padding: 20px; color: #1db954; margin: 0;">🏆 Активність Слухачів</h3>
                    <table class="data-table">
                        <thead>
                        <tr><th>Нікнейм</th><th>Плейлісти</th><th>Відгуки</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($report_users as $ru): ?>
                            <tr>
                                <td><b><?= htmlspecialchars($ru['username']) ?></b></td>
                                <td><?= $ru['p_count'] ?></td>
                                <td style="color: #1db954; font-weight: bold;"><?= $ru['r_count'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <div style="height: 100px;"></div>
    </div>
</main>

<script>
    function openTab(tabId, btn) {
        document.querySelectorAll('.form-section').forEach(s => s.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById(tabId).classList.add('active');
        btn.classList.add('active');
    }
</script>