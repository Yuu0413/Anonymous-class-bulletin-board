<?php
/* class/class_detail.php */

// 1. セッション開始
session_start();

// 2. DB接続 (共通ファイル)
require 'class_db_connect.php';

// 3. IDの取得と検証
// URLパラメータ ?course_id=1 を取得
$c_id = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);

// IDがない場合はトップへ戻すなどの処理
if (!$c_id) {
    header("Location: ../index.php"); // 仮の戻り先
    exit;
}

// 変数初期化
$course = null;
$reviews = [];
$avg_rating = 0;
$error_msg = "";

try {
    // -----------------------------------------
    // A. 授業情報の取得 (coursesテーブル)
    // -----------------------------------------
    $sql_course = "SELECT * FROM courses WHERE course_id = :id";
    $stmt = $pdo->prepare($sql_course);
    $stmt->bindValue(':id', $c_id, PDO::PARAM_INT);
    $stmt->execute();
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$course) {
        $error_msg = "指定された授業が見つかりませんでした。";
    } else {
        // -----------------------------------------
        // B. レビュー情報の取得 (reviewsテーブル)
        // ※ reviewsテーブルが存在しない場合、ここはエラーになります。
        //   開発中は try-catch で囲んで無視するか、テーブルを作成してください。
        // -----------------------------------------

        // ★本来は他チームの設計に合わせますが、ここでは仮定して記述します
        $sql_reviews = "SELECT * FROM reviews WHERE course_id = :id ORDER BY created_at DESC";
        $stmt_r = $pdo->prepare($sql_reviews);
        $stmt_r->bindValue(':id', $c_id, PDO::PARAM_INT);
        $stmt_r->execute();
        $reviews = $stmt_r->fetchAll(PDO::FETCH_ASSOC);

        // C. 平均評価の計算
        if (count($reviews) > 0) {
            $total = 0;
            foreach ($reviews as $r) {
                $total += $r['rating']; // ratingカラム(1~5)を想定
            }
            $avg_rating = round($total / count($reviews), 1);
        }
    }

} catch (PDOException $e) {
    // テーブルがない等のDBエラー
    $error_msg = "データ取得エラー: " . $e->getMessage();
}

// XSS対策関数
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// 星評価を表示する関数
function renderStars($rating) {
    $rating = round($rating);
    $output = '';
    // 黄色の星
    for ($i = 0; $i < $rating; $i++) {
        $output .= '<span class="text-warning">★</span>';
    }
    // 空の星
    for ($i = $rating; $i < 5; $i++) {
        $output .= '<span class="text-muted">☆</span>';
    }
    return $output;
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($course) ? h($course['course_name']) : '詳細不明'; ?> | 授業レビュー</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="class_register.css">
    <style>
        /* 詳細ページ独自のスタイル */
        .rating-badge {
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
        }
        .review-card {
            border-left: 5px solid #0d6efd; /* アクセントカラー */
            background-color: #fff;
            margin-bottom: 1rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>

<div class="container py-5">

    <div class="mb-4">
        <a href="../search/index.php" class="btn btn-outline-secondary">&larr; 授業一覧に戻る</a>
    </div>

    <?php if ($error_msg): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo h($error_msg); ?>
        </div>
    <?php elseif ($course): ?>

        <div class="card register-card mb-5 p-4">
            <div class="card-body text-center">
                <span class="badge bg-primary mb-2">授業詳細</span>
                <h1 class="display-5 fw-bold mb-3"><?php echo h($course['course_name']); ?></h1>
                <p class="text-secondary fs-4 mb-4">
                    担当: <span class="text-dark fw-bold"><?php echo h($course['professor_name']); ?></span>
                </p>

                <div class="bg-light p-3 rounded-3 d-inline-block">
                    <div class="text-muted small">平均評価</div>
                    <div class="rating-badge">
                        <?php echo $avg_rating; ?>
                        <span class="fs-6 text-warning">
                            <?php echo renderStars($avg_rating); ?>
                        </span>
                    </div>
                    <div class="small text-muted">
                        (口コミ: <?php echo count($reviews); ?>件)
                    </div>
                </div>

                <div class="mt-4">
                    <a href="../review/post.php?course_id=<?php echo h($course['course_id']); ?>" class="btn btn-primary btn-lg shadow-sm">
                        ✎ この授業の口コミを書く
                    </a>
                </div>
            </div>
        </div>

        <h3 class="mb-4 fw-bold text-secondary border-bottom pb-2">
            みんなの口コミ <span class="badge bg-secondary rounded-pill fs-6"><?php echo count($reviews); ?></span>
        </h3>

        <?php if (count($reviews) > 0): ?>
            <div class="row">
                <?php foreach ($reviews as $review): ?>
                    <div class="col-12">
                        <div class="card review-card p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <span class="fw-bold me-2">匿名さん</span>
                                    <?php echo renderStars($review['rating']); ?>
                                </div>
                                <small class="text-muted">
                                    <?php echo h(date('Y/m/d', strtotime($review['created_at']))); ?>
                                </small>
                            </div>
                            <p class="card-text mb-0" style="white-space: pre-wrap;"><?php echo h($review['comment']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5 text-muted bg-white rounded shadow-sm">
                <p class="mb-0 fs-5">まだ口コミがありません 😢</p>
                <p class="small">最初の投稿者になって、後輩を助けましょう！</p>
            </div>
        <?php endif; ?>

    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>