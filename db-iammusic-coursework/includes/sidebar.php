<div class="sidebar">
    <div class="logo">I AM MUSIC</div>
    <nav>
        <ul>
            <li><a href="index.php">🏠 Головна</a></li>
            <li><a href="albums.php">💿 Альбоми</a></li>
            <li><a href="playlists.php">📂 Плейлісти</a></li>

            <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 1): ?>
                <li>
                    <a href="admin.php" style="color: #1db954; font-weight: bold; margin-top: 10px; display: block;">
                        ⚙️ Керування базою
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>

    <hr style="border: 0.1px solid #282828; width: 100%; margin: 20px 0;">

    <div class="auth-links" style="margin-top: auto;">
        <?php if(isset($_SESSION['username'])): ?>
            <p style="color: #1db954; font-weight: bold; margin-bottom: 5px;">
                <span style="font-size: 10px;">●</span> <?php echo htmlspecialchars($_SESSION['username']); ?>
            </p>
            <a href="logout.php" style="font-size: 12px; color: #b3b3b3; text-decoration: none;">Вийти</a>
        <?php else: ?>
            <a href="login.php" style="display: block; margin-bottom: 10px; color: #fff; text-decoration: none;">Увійти</a>
            <a href="register.php" style="color: #b3b3b3; text-decoration: none;">Реєстрація</a>
        <?php endif; ?>
    </div>
</div>