<?php
session_start();

// ダミーユーザー情報
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
    if ($email === $dummy_user['email'] && $password === $dummy_user['password']) {

        session_regenerate_id(true);
        $_SESSION['user_id'] = $dummy_user['id'];
        $_SESSION['user_name'] = $dummy_user['name'];

        header("Location: ../index.php");
        exit();

    } else {
        $error = 'IDもパスワードも test で入れます'; // エラーメッセージ
        header("Location: auth_login.php?error=" . urlencode($error));
        exit();
    }
} else {
    header("Location: auth_login.php");
    exit();
}