<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>授業口コミサイト</title>

    <style>
        .signin {
            width: 300px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-family: sans-serif;
        }

        .signin label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
        }

        .signin input {
            width: 100%;
            padding: 8px;
            margin-bottom: 16px;
            font-size: 14px;
        }

        .signin button {
            width: 100%;
            padding: 10px;
            margin-bottom: 12px;
        }
        body {
        background-color: #e6f0ff; 
        font-family: "Hiragino Sans", "Meiryo", sans-serif;
    }
    </style>
</head>

<body>
    <div class="signin">
        <h2>ログイン</h2>

        <form action="" method="POST">
            <label for="signin-id">アカウント名</label>
            <input id="signin-id" name="username" type="text" placeholder="メールアドレスを入力">

            <label for="signin-pass">パスワード</label>
            <input id="signin-pass" name="password" type="password" placeholder="パスワードを入力">

            <button name="signin" type="submit">ログインする</button>

            <label for="newsign-id">新規登録</label>
            <button type="submit" formaction="signup.php">新規登録</button>
        </form>
    </div>
</body>
</html>
