<?php
session_start();

// 将来的にはここでデータベース接続ファイル(includes/db.php)を読み込みます
// require_once '../includes/db.php';

// ダミーユーザー情報（テスト用）
$dummy_user = [
    'id'       => 1,       // ユーザーID
    'name'     => 'test',  // ユーザー名
    'email'    => 'test',  // ログインID
    'password' => 'test'   // パスワード
];

if (isset($_POST['signin'])) {
    $email = $_POST['username'];
    $password = $_POST['password'];

    // 入力が 'test' かつ 'test' ならログイン成功
    // ※本番ではここで password_verify($password, $hash) などを使います
    if ($email === $dummy_user['email'] && $password === $dummy_user['password']) {

        // セッションIDの再生成（セキュリティ対策）
        session_regenerate_id(true);

        // セッション変数に保存
        $_SESSION['user_id'] = $dummy_user['id'];
        $_SESSION['user_name'] = $dummy_user['name'];

        // ★修正点: ログイン成功時の遷移先を home.php に変更
        // ../ は「一つ上の階層」という意味です
        header("Location: ../home.php");
        exit();

    } else {
        // エラー時の処理
        $error = 'IDもパスワードも test で入れます'; 
        // 同じフォルダ内のログイン画面に戻す
        header("Location: auth_login.php?error=" . urlencode($error));
        exit();
    }
} else {
    // POSTアクセスでない場合もログイン画面に戻す
    header("Location: auth_login.php");
    exit();
}