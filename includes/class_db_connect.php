<?php
/* class_db_connect.php */

// ▼▼▼ 開発者の環境に合わせて書き換えてください ▼▼▼
$host = 'localhost';      // 大学サーバー内なら基本 localhost でOK
$dbname = 'reosato'; // 大学から割り当てられたDB名
$user = 'reosato';
$password = 'U7Q3MHJl';
// ▲▲▲ 書き換えここまで ▲▲▲

// データソース名（DSN）: 接続の宛先情報
$dsn = "pgsql:host=$host;port=5432;dbname=$dbname";

try {
    // 接続実行！
    $pdo = new PDO($dsn, $user, $password);

    // エラーが起きたら隠さずに報告するように設定
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 成功したら何も言わない（静かに準備完了）

} catch (PDOException $e) {
    // 失敗したらここで止まってエラーを表示
    exit('データベース接続失敗: ' . $e->getMessage());
}
?>