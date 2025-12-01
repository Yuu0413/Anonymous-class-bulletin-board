<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$path = isset($root_path) ? $root_path : './';

// ログイン画面とログアウト画面、signup.php はログイン不要でアクセス可能にする
$current_file = basename($_SERVER['PHP_SELF']);
if (!isset($_SESSION['user_id']) && $current_file !== 'auth_login.php' && $current_file !== 'logout.php' && $current_file !== 'signup.php') {
    header("Location: {$path}auth/auth_login.php");
    exit();
}

$page_title = isset($page_title) ? $page_title : '授業口コミサイト';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>

    <?php if (isset($page_css)): ?>
        <link rel="stylesheet" href="<?php echo $path . 'css/' . $page_css; ?>">
    <?php endif; ?>

    <style>
        /* 共通スタイル */
        body {
            font-family: "Hiragino Sans", "Meiryo", sans-serif;
            background-color: #e6f0ff;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        header {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        header h1 {
            margin: 0;
            font-size: 1.2rem;
        }
        header a {
            color: white;
            text-decoration: none;
            margin-left: 15px;
            font-weight: bold;
        }
        header a:hover {
            text-decoration: underline;
        }
        main {
            flex: 1;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
            width: 100%;
            box-sizing: border-box;
        }
        footer {
            background-color: #333;
            color: #fff;
            text-align: center;
            padding: 10px;
            font-size: 0.8rem;
            margin-top: auto;
        }
    </style>
</head>
<body>

<header>
    <div class="site-logo">
        <h1><a href="<?php echo $path; ?>home.php" style="margin-left:0;">授業口コミサイト</a></h1>
    </div>
    <nav>
        <a href="<?php echo $path; ?>home.php">ホーム</a>
        <a href="<?php echo $path; ?>auth/logout.php">ログアウト</a>
    </nav>
</header>

<main>