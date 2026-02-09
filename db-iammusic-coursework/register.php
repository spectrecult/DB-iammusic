<?php
require_once 'includes/db.php';

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (strlen($password) < 6) {
        $error = "Пароль має бути не менше 6 символів!";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            // Використовується [dbo].[User], щоб уникнути конфліктів із зарезервованими словами SQL
            $sql = "INSERT INTO [dbo].[User] (username, email, password, dateJoined) VALUES (?, ?, ?, GETDATE())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username, $email, $hashed_password]);

            $success = "Акаунт створено! Зараз ви будете перенаправлені...";
            header("refresh:2;url=login.php");
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                $error = "Цей Email вже зареєстровано!";
            } else {
                $error = "Помилка бази даних: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Реєстрація | I AM MUSIC</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: linear-gradient(to bottom, #121212, #000000);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            color: white;
        }
        .register-container {
            background-color: #000000;
            padding: 40px;
            border-radius: 12px;
            width: 100%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .logo-green {
            color: #1db954;
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 20px;
            letter-spacing: -1px;
        }
        h1 { font-size: 24px; margin-bottom: 30px; }

        .form-group { text-align: left; margin-bottom: 15px; }
        label { display: block; font-size: 14px; margin-bottom: 8px; font-weight: bold; }

        input {
            width: 100%;
            padding: 12px;
            background: #121212;
            border: 1px solid #727272;
            border-radius: 4px;
            color: white;
            box-sizing: border-box;
            font-size: 16px;
        }
        input:focus {
            border-color: #1db954;
            outline: none;
        }

        .btn-signup {
            width: 100%;
            padding: 14px;
            background-color: #1db954;
            color: black;
            border: none;
            border-radius: 500px;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            cursor: pointer;
            margin-top: 20px;
            transition: transform 0.2s;
        }
        .btn-signup:hover { transform: scale(1.03); background-color: #1ed760; }

        .error-msg { color: #ff4d4d; margin-bottom: 15px; font-size: 14px; }
        .success-msg { color: #1db954; margin-bottom: 15px; font-size: 14px; }

        hr { border: 0; border-top: 1px solid #282828; margin: 30px 0; }
        p { color: #b3b3b3; font-size: 14px; }
        a { color: white; font-weight: bold; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="register-container">
    <div class="logo-green">I AM MUSIC</div>
    <h1>Зареєструйтеся, щоб почати слухати</h1>

    <?php if($error): ?>
        <div class="error-msg"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if($success): ?>
        <div class="success-msg"><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="username">Як вас звати?</label>
            <input type="text" name="username" id="username" placeholder="Введіть ім'я профілю" required>
        </div>

        <div class="form-group">
            <label for="email">Яка ваша адреса електронної пошти?</label>
            <input type="email" name="email" id="email" placeholder="Введіть свій email" required>
        </div>

        <div class="form-group">
            <label for="password">Придумайте пароль</label>
            <input type="password" name="password" id="password" placeholder="Мінімум 6 символів" required>
        </div>

        <button type="submit" class="btn-signup">Зареєструватися</button>
    </form>

    <hr>
    <p>Вже маєте акаунт? <a href="login.php">Увійти</a>.</p>
</div>

</body>
</html>