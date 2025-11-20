<?php
/* class_register.php */

// 1. セッション（一時保存機能）を開始
session_start();

// 2. DB接続を持ってくる
require 'class_db_connect.php';

// 3. 【仮実装】ログイン機能がまだないので、無理やりログイン状態を作る
// 本番ではこのif文を消せば、ログインしていない人は弾かれるようになります
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 999; // 仮のIDを入れておく
}

// ログインチェック: IDを持っていない人はログイン画面へ追放
if (!isset($_SESSION['user_id'])) {
    // authディレクトリのログイン画面へ飛ばす（まだ無いならエラーになるが正しい挙動）
    header("Location: ../auth/login.php");
    exit;
}

// メッセージ表示用変数
$message = "";

// 4. 「登録ボタン」が押された時の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // フォームから送られてきたデータを受け取る
    $c_name = $_POST['course_name']; // 授業名
    $p_name = $_POST['prof_name'];   // 教授名

    // 空っぽじゃないかチェック
    if (!empty($c_name) && !empty($p_name)) {
        try {
            // SQLの準備（プレースホルダ :name を使う）
            // いきなり変数を埋め込むとハッキングされるので :name という仮置き場を使う
            $sql = "INSERT INTO courses (course_name, professor_name) VALUES (:c_name, :p_name)";
            
            // 予約を入れる ($pdoは db_connect.php で作った電話機)
            $stmt = $pdo->prepare($sql);
            
            // 仮置き場に本当の値をセットする（型を指定して安全にする）
            $stmt->bindValue(':c_name', $c_name, PDO::PARAM_STR);
            $stmt->bindValue(':p_name', $p_name, PDO::PARAM_STR);
            
            // 実行！
            $stmt->execute();

            $message = "✅ 授業「" . htmlspecialchars($c_name) . "」を登録しました！";
        } catch (PDOException $e) {
            $message = "❌ エラー: " . $e->getMessage();
        }
    } else {
        $message = "⚠️ 全ての項目を入力してください。";
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>授業登録 | 匿名口コミアプリ</title>
    <style>
        body { font-family: sans-serif; max-width: 600px; margin: 20px auto; padding: 20px; }
        .msg { padding: 10px; background: #f0f0f0; border-left: 5px solid #2196F3; margin-bottom: 20px; }
        form { background: #fafafa; padding: 20px; border: 1px solid #ddd; }
        input { width: 100%; padding: 8px; margin-bottom: 10px; box-sizing: border-box; }
        button { background: #333; color: white; padding: 10px 20px; border: none; cursor: pointer; }
    </style>
</head>
<body>

    <h1>📚 授業登録ページ</h1>
    
    <?php if ($message): ?>
        <div class="msg"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="post" action="">
        <label>授業名</label>
        <input type="text" name="course_name" placeholder="例: プログラミング基礎" required>
        
        <label>教授名</label>
        <input type="text" name="prof_name" placeholder="例: 佐藤 先生" required>
        
        <button type="submit">登録する</button>
    </form>

    <hr>

    <h3>📋 現在DBに入っている授業（確認用）</h3>
    <ul>
    <?php
    // DBからデータを全件取得して表示する
    $sql_select = "SELECT * FROM courses ORDER BY course_id DESC";
    $stmt = $pdo->query($sql_select);
    
    // 1行ずつ取り出して表示
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // htmlspecialchars は文字化けや攻撃を防ぐためのバリア
        echo "<li>";
        echo "ID:" . htmlspecialchars($row['course_id']) . " ";
        echo "<strong>" . htmlspecialchars($row['course_name']) . "</strong> ";
        echo "(" . htmlspecialchars($row['professor_name']) . ")";
        echo "</li>";
    }
    ?>
    </ul>

</body>
</html>