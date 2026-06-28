<?php
// DB接続設定
// phpMyAdminで作成したDB情報に合わせて変更してください

define('DB_HOST', 'localhost');
define('DB_NAME', 'calma2018_yatagarasudb');
define('DB_USER', 'calma2018_yatadb');
define('DB_PASS', 'yatagarasudb');
define('DB_CHARSET', 'utf8mb4');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('DB接続エラー: ' . $e->getMessage());
}
