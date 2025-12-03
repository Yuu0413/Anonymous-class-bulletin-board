<?php
// 1. 共通パーツのパス設定
$root_path = '../';
$page_title = 'ログイン';
$page_css = 'auth.css'; // 作成したCSSファイルを指定

// 2. ヘッダー読み込み
require_once $root_path . 'includes/header.php';
?>

<div class="signin">
    <h2>ログイン</h2>

    <form action="auth_session.php" method="POST">
        <label for="signin-id">アカウント名</label>
        <input id="signin-id" name="username" type="text" placeholder="メールアドレスを入力" required>

        <label for="signin-pass">パスワード</label>
        <input id="signin-pass" name="password" type="password" placeholder="パスワードを入力" required>

        <button name="signin" type="submit">ログインする</button>

        <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">

        <label for="newsign-id">アカウントをお持ちでない方</label>
        <button type="submit" formaction="signup.php">新規登録画面へ</button>
    </form>
</div>
<?php
// 3. フッター読み込み
require_once $root_path . 'includes/footer.php';
?>