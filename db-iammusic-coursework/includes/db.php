<?php
$serverName = "localhost\\MSSQLSERVER1";
$database = "iammusic";
$uid = "";
$pwd = "";

try {
    $pdo = new PDO("sqlsrv:Server=$serverName;Database=$database", $uid, $pwd);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Помилка підключення до SSMS: " . $e->getMessage());
}
?>