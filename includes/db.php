<?php
/* includes/db.php */

// --------------------------------------------------
// ▼ 設定切り替えスイッチ
// --------------------------------------------------
// Macで作業する時は true
// 大学にアップロードする時は false に書き換えてください
$is_local = true;

if ($is_local) {
    // 🏠 ローカル(Mac)用の設定
    // エラーメッセージから判明したあなたのユーザー名を使います
    $host = 'localhost';
    $dbname = 'yuta0413'; // ★ここが重要！
    $user = 'shibatayuta';   // ★ここが重要！
    $password = '';       // MacのPostgres.appはパスワードなしでOK
} else {
    // 🏫 大学サーバー用の設定 (reosato)
    // 元のファイルに書かれていた内容です
    $host = 'localhost';
    $dbname = 'reosato';
    $user = 'reosato';
    $password = 'U7Q3MHJl';
}
// --------------------------------------------------

// データソース名（DSN）
$dsn = "pgsql:host=$host;port=5432;dbname=$dbname";

try {
    // 接続実行！
    $pdo = new PDO($dsn, $user, $password);

    // エラー設定
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // データを連想配列で取得するように設定（便利なので追加しておきます）
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // 失敗したらエラーを表示
    exit('データベース接続失敗: ' . $e->getMessage());
}
?>