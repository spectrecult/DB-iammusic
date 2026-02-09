<footer class="player-bar" style="
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 40px; background: #181818; color: white;
    position: fixed; bottom: 0; width: 100%; box-sizing: border-box;
    border-top: 1px solid #282828; z-index: 1000; height: 90px;
">
    <div style="width: 30%; display: flex; align-items: center; gap: 12px;">
        <img id="player-img" src="img/default.png" alt="" style="width: 56px; height: 56px; border-radius: 4px; object-fit: cover; display: none;">
        <div style="overflow: hidden;">
            <strong id="track-name" style="display: block; white-space: nowrap; text-overflow: ellipsis; overflow: hidden;">Оберіть трек</strong>
            <p id="track-artist" style="font-size: 12px; color: #b3b3b3; margin: 0;"></p>
        </div>
    </div>

    <div style="width: 40%; display: flex; flex-direction: column; align-items: center; gap: 8px;">
        <div style="display: flex; align-items: center; gap: 25px;">
            <span id="shuffle-btn" onclick="toggleShuffle()" style="font-size: 22px; color: #b3b3b3; cursor: pointer; transition: 0.2s; user-select: none;">⤮</span>
            <span onclick="prevTrack()" style="font-size: 20px; color: #b3b3b3; cursor: pointer; transition: 0.2s;">⏮</span>
            <div id="play-pause-btn" onclick="togglePlay()" style="width: 45px; height: 45px; background: white; color: black; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 20px;">▶</div>
            <span onclick="nextTrack()" style="font-size: 20px; color: #b3b3b3; cursor: pointer; transition: 0.2s;">⏭</span>
            <span id="repeat-btn" onclick="toggleRepeat()" style="font-size: 22px; color: #b3b3b3; cursor: pointer; transition: 0.2s; user-select: none;">↻</span>
        </div>
        <div style="width: 100%; display: flex; align-items: center; gap: 10px;">
            <span id="current-time" style="font-size: 11px; color: #b3b3b3; min-width: 35px; text-align: right;">0:00</span>
            <input type="range" id="progress-bar" min="0" max="100" value="0" step="0.1" oninput="seekTrack(this.value)" style="flex-grow: 1; accent-color: #1db954; cursor: pointer; height: 4px; margin: 0;">
            <span id="duration-time" style="font-size: 11px; color: #b3b3b3; min-width: 35px;">0:00</span>
        </div>
    </div>

    <div style="width: 30%; display: flex; align-items: center; justify-content: flex-end; gap: 12px;">
        <span id="volume-icon" onclick="muteTrack()" style="color: #b3b3b3; font-size: 20px; cursor: pointer; width: 30px; text-align: center;">🔊</span>
        <input type="range" id="volume-bar" min="0" max="1" value="0.5" step="0.01" oninput="changeVolume(this.value)" style="width: 100px; accent-color: #1db954; cursor: pointer; height: 4px;">
    </div>
</footer>

<script>
    let currentAudio = new Audio();
    let isPlaying = false;
    let isShuffle = false;
    let isRepeat = false;
    let lastVolume = 0.5;

    let shuffledIndices = [];
    let shufflePointer = 0;
    window.currentSongIndex = -1;

    // Глобальний список пісень з сервера
    const globalTrackList = <?php echo isset($songs) ? json_encode($songs) : '[]'; ?>;

    function getActiveList() {
        return (window.currentPlaylist && window.currentPlaylist.length > 0) ? window.currentPlaylist : globalTrackList;
    }

    function playSong(title, artist, img, audioPath) {
        const list = getActiveList();
        window.currentSongIndex = list.findIndex(s => s.audioURL === audioPath);

        document.getElementById('track-name').innerText = title;
        document.getElementById('track-artist').innerText = artist;
        const pImg = document.getElementById('player-img');
        pImg.src = img || 'img/default.png';
        pImg.style.display = 'block';

        currentAudio.src = audioPath;
        currentAudio.loop = isRepeat;
        currentAudio.play().then(() => {
            isPlaying = true;
            updateUI();
            if (isShuffle) updateShuffleQueue();
        }).catch(e => console.log("Playback error:", e));
    }

    function togglePlay() {
        if (!currentAudio.src) return;
        isPlaying ? currentAudio.pause() : currentAudio.play();
        isPlaying = !isPlaying;
        updateUI();
    }

    function updateUI() {
        document.getElementById('play-pause-btn').innerHTML = isPlaying ? '⏸' : '▶';
        document.getElementById('shuffle-btn').style.color = isShuffle ? '#1db954' : '#b3b3b3';
        document.getElementById('repeat-btn').style.color = isRepeat ? '#1db954' : '#b3b3b3';
    }

    function toggleShuffle() {
        isShuffle = !isShuffle;
        if (isShuffle) updateShuffleQueue();
        updateUI();
    }

    function toggleRepeat() {
        isRepeat = !isRepeat;
        currentAudio.loop = isRepeat;
        updateUI();
    }

    function updateShuffleQueue() {
        const list = getActiveList();
        shuffledIndices = Array.from(Array(list.length).keys());
        for (let i = shuffledIndices.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [shuffledIndices[i], shuffledIndices[j]] = [shuffledIndices[j], shuffledIndices[i]];
        }
        // Поставити поточний трек першим у черзі
        const currentPos = shuffledIndices.indexOf(window.currentSongIndex);
        if (currentPos !== -1) {
            shuffledIndices.splice(currentPos, 1);
            shuffledIndices.unshift(window.currentSongIndex);
        }
        shufflePointer = 0;
    }

    function nextTrack() {
        const list = getActiveList();
        if (list.length === 0) return;

        if (isShuffle) {
            shufflePointer = (shufflePointer + 1) % shuffledIndices.length;
            window.currentSongIndex = shuffledIndices[shufflePointer];
        } else {
            window.currentSongIndex = (window.currentSongIndex + 1) % list.length;
        }

        const track = list[window.currentSongIndex];
        playSong(track.title, track.artist, track.imageURL, track.audioURL);
    }

    function prevTrack() {
        const list = getActiveList();
        if (list.length === 0) return;

        if (isShuffle) {
            shufflePointer = (shufflePointer - 1 + shuffledIndices.length) % shuffledIndices.length;
            window.currentSongIndex = shuffledIndices[shufflePointer];
        } else {
            window.currentSongIndex = (window.currentSongIndex - 1 + list.length) % list.length;
        }

        const track = list[window.currentSongIndex];
        playSong(track.title, track.artist, track.imageURL, track.audioURL);
    }

    function changeVolume(value) {
        currentAudio.volume = value;
        const icon = document.getElementById('volume-icon');
        if (value == 0) { icon.innerText = '🔇'; icon.style.color = '#fa5858'; }
        else { icon.innerText = (value < 0.5 ? '🔉' : '🔊'); icon.style.color = (value > 0.8 ? '#1db954' : '#b3b3b3'); }
    }

    function muteTrack() {
        const bar = document.getElementById('volume-bar');
        if (currentAudio.volume > 0) { lastVolume = currentAudio.volume; bar.value = 0; changeVolume(0); }
        else { bar.value = lastVolume; changeVolume(lastVolume); }
    }

    currentAudio.ontimeupdate = () => {
        const bar = document.getElementById('progress-bar');
        if (currentAudio.duration) {
            bar.value = (currentAudio.currentTime / currentAudio.duration) * 100;
            document.getElementById('current-time').innerText = formatTime(currentAudio.currentTime);
            document.getElementById('duration-time').innerText = formatTime(currentAudio.duration);
        }
    };

    function seekTrack(v) { if (currentAudio.duration) currentAudio.currentTime = (v / 100) * currentAudio.duration; }
    function formatTime(s) {
        let m = Math.floor(s / 60);
        let sc = Math.floor(s % 60);
        return m + ":" + (sc < 10 ? '0' + sc : sc);
    }

    currentAudio.onended = () => { if (!isRepeat) nextTrack(); };
</script>