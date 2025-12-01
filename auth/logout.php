<?php
// 1. セッションを開始（破棄するためにはまず開始する必要があります）
session_start();

// 2. セッション変数をすべて解除（空にする）
$_SESSION = array();

// 3. セッションクッキーを削除（ブラウザ側のIDも消すための定型処理）
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. サーバー側のセッションを完全に破棄
session_destroy();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ログアウト完了 - 授業口コミサイト</title>

    <style>
        /* ログインページからスタイルを流用 */
        .message-box {
            width: 300px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-family: sans-serif;
            text-align: center;
            background-color: #fff; /* 背景色を明示的に白に設定 */
        }

        .message-box h2 {
            margin-top: 0;
            color: #333;
        }

        .message-box p {
            margin-bottom: 20px;
            font-size: 16px;
        }

        .message-box a {
            display: inline-block;
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .message-box a:hover {
            background-color: #0056b3;
        }

        body {
            background-color: #e6f0ff;
            font-family: "Hiragino Sans", "Meiryo", sans-serif;
        }
    </style>
</head>

<body>
    <div class="message-box">
        <h2>ログアウトしました</h2>
        <p>ご利用ありがとうございました。</p>

        <a href="auth_login.php">再度ログインする</a>
    </div>
</body>
</html>