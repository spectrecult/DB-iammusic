<div class="card" onclick="location.href='album_details.php?id=<?= $album['id_album'] ?>'">
    <div class="img-container">
        <img src="<?= $album['imageURL'] ?: 'img/default.png' ?>" alt="Cover">
        <button class="play-btn"><span>▶</span></button>
    </div>
    <h4><?= htmlspecialchars($album['title']) ?></h4>
    <p><?= htmlspecialchars($album['artist']) ?></p>
</div>