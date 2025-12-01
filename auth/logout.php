<?php
// 1. セッションを開始
session_start();

// 2. セッション変数をすべて解除
$_SESSION = array();

// 3. セッションクッキーを削除
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. サーバー側のセッションを完全に破棄
session_destroy();

// --- ここまででログアウト処理完了 ---

// 5. 画面表示の準備
$root_path = '../';
$page_title = 'ログアウト完了';
$page_css = 'logout.css'; // 作成したCSSを指定

// ヘッダー読み込み
// (注意: ここで新しい空のセッションが開始されますが、user_idは空なので「未ログイン」状態です)
require_once $root_path . 'includes/header.php';
?>

<div class="message-box">
    <h2>ログアウトしました</h2>
    <p>ご利用ありがとうございました。</p>

    <a href="auth_login.php">再度ログインする</a>
</div>

<?php
// フッター読み込み
require_once $root_path . 'includes/footer.php';
?>