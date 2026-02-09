<?php
session_start();
require_once 'includes/db.php';

$error = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Шукається користувач
    $stmt = $pdo->prepare("SELECT * FROM [dbo].[User] WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // ЗАПИС ДАНИХ У СЕСІЮ
        $_SESSION['user_id'] = $user['id_user'];
        $_SESSION['username'] = $user['username'];

        // Додається роль: якщо в БД NULL, ставимо 0 (користувач)
        // Використовується (int) для впевненості, що це число
        $_SESSION['role'] = isset($user['role']) ? (int)$user['role'] : 0;

        header("Location: index.php");
        exit;
    } else {
        $error = "Невірний email або пароль!";
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Вхід | IAMMUSIC</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .auth-form { max-width: 400px; margin: 100px auto; background: #181818; padding: 40px; border-radius: 8px; text-align: center; }
        .auth-form input { width: 100%; padding: 12px; margin: 10px 0; background: #3e3e3e; border: none; color: white; border-radius: 4px; box-sizing: border-box; }
        .auth-form button { width: 100%; padding: 12px; background: #1db954; border: none; color: white; font-weight: bold; border-radius: 25px; cursor: pointer; margin-top: 20px; transition: 0.3s; }
        .auth-form button:hover { background: #1ed760; transform: scale(1.02); }
        .auth-form a { color: #b3b3b3; font-size: 14px; text-decoration: none; display: block; margin-top: 15px; }
        .auth-form a:hover { color: white; }
        .error-msg { color: #ff4d4d; margin-bottom: 15px; font-size: 14px; }
    </style>
</head>
<body style="display: block; background: #000; font-family: 'Segoe UI', sans-serif;">
<div class="auth-form">
    <h2 style="color: #1db954; letter-spacing: 2px;">I AM MUSIC</h2>
    <h1 style="color: white; font-size: 24px; margin: 20px 0;">Щоб продовжити, увійдіть.</h1>

    <?php if($error): ?>
        <p class="error-msg"><?= $error ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Пароль" required>
        <button type="submit">УВІЙТИ</button>
    </form>

    <a href="register.php">Немає акаунту? <span style="color: white; text-decoration: underline;">ЗАРЕЄСТРУВАТИСЯ</span></a>
</div>
</body>
</html>