<?php
/* class_register.php */

// 1. セッション開始
session_start();

// 2. DB接続
require 'class_db_connect.php';

// 3. 【仮実装】ログインモック
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 999; 
}

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

// 変数初期化
$message = "";
$alertClass = ""; // Bootstrapのアラート色指定用

// 4. POST処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $c_name = $_POST['course_name'];
    $p_name = $_POST['prof_name'];

    if (!empty($c_name) && !empty($p_name)) {
        try {
            $sql = "INSERT INTO courses (course_name, professor_name) VALUES (:c_name, :p_name)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':c_name', $c_name, PDO::PARAM_STR);
            $stmt->bindValue(':p_name', $p_name, PDO::PARAM_STR);
            $stmt->execute();

            $message = "授業「" . htmlspecialchars($c_name) . "」を登録しました！";
            $alertClass = "alert-success"; // 緑色

        } catch (PDOException $e) {
            $message = "エラーが発生しました: " . $e->getMessage();
            $alertClass = "alert-danger"; // 赤色
        }
    } else {
        $message = "全ての項目を入力してください。";
        $alertClass = "alert-warning"; // 黄色
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>授業登録 | 匿名口コミアプリ</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="class_register.css">
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">

            <div class="text-center mt-5 mb-4">
                <div class="header-icon">📚</div>
                <h1 class="h3 fw-bold text-dark">授業の新規登録</h1>
                <p class="text-muted">みんなのために授業情報を追加しましょう</p>
            </div>

            <?php if ($message): ?>
                <div class="alert <?php echo $alertClass; ?> alert-dismissible fade show shadow-sm" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card register-card p-4">
                <div class="card-body">
                    <form method="post" action="">
                        <div class="mb-4">
                            <label for="course_name" class="form-label fw-bold text-secondary">授業名</label>
                            <input type="text" class="form-control" id="course_name" name="course_name" placeholder="例: 情報工学概論" required>
                        </div>

                        <div class="mb-4">
                            <label for="prof_name" class="form-label fw-bold text-secondary">担当教授名</label>
                            <input type="text" class="form-control" id="prof_name" name="prof_name" placeholder="例: 山田 太郎" required>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold">
                                登録する
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="text-center mt-4">
                <a href="../index.php" class="text-decoration-none text-secondary">
                    &larr; トップページへ戻る
                </a>
            </div>


            <div class="class-debug-area">
                <h3>🔧 [Dev] DB登録済みデータ (最新10件)</h3>
                <ul class="class-debug-list">
                <?php
                $sql_select = "SELECT * FROM courses ORDER BY course_id DESC LIMIT 10";
                $stmt = $pdo->query($sql_select);

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<li>";
                    echo "ID:" . htmlspecialchars($row['course_id']) . " ";
                    echo "<strong>" . htmlspecialchars($row['course_name']) . "</strong> ";
                    echo '<span class="text-muted small">(' . htmlspecialchars($row['professor_name']) . ')</span>';
                    echo "</li>";
                }
                ?>
                </ul>
            </div>
            </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>