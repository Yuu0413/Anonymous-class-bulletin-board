<?php
session_start();

// ★全部 test に変更（IDだけは数字の1にしておく！）
$dummy_user = [
    'id'       => 1,       // ユーザーID
    'name'     => 'test',  // ユーザー名
    'email'    => 'test',  // ログインID
    'password' => 'test'   // パスワード
];

// 以下、ロジックは同じ
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
        $error = 'IDもパスワードも test で入れます'; // エラー文も親切に
        header("Location: auth_login.php?error=" . urlencode($error));
        exit();
    }
} else {
    header("Location: auth_login.php");
    exit();
}