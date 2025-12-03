<?php
// ▼▼▼ エラーを表示させる設定 ▼▼▼
ini_set('display_errors', 1);
error_reporting(E_ALL);
// ▲▲▲▲▲▲

// 1. 設定と共通パーツ読み込み
$root_path = '../'; 
$page_title = '授業詳細';
$page_css = 'class_detail.css'; 

require_once $root_path . 'includes/header.php';
require_once $root_path . 'includes/db.php';

// 2. IDの取得と検証
$c_id = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);

// 変数初期化
$course = null;
$reviews = [];
$avg_rating = 0;
$error_msg = "";
$update_msg = "";

// ★修正: IDがない場合、いきなり飛ばさずにエラーメッセージを表示するように変更
if (!$c_id) {
    $error_msg = "エラー: 授業IDが指定されていません。<br>一覧画面から授業を選択してください。";
} else {
    // --------------------------------------------------
    // IDがある場合のみ、DB更新や取得処理を行う
    // --------------------------------------------------

    // 3. POST処理（授業概要の更新）
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_description'])) {
        $new_desc = trim($_POST['description']);
        
        try {
            $sql_update = "UPDATE courses SET description = :desc WHERE course_id = :id";
            $stmt_up = $pdo->prepare($sql_update);
            $stmt_up->bindValue(':desc', $new_desc, PDO::PARAM_STR);
            $stmt_up->bindValue(':id', $c_id, PDO::PARAM_INT);
            $stmt_up->execute();
            
            // 更新完了後、画面を再読み込み
            header("Location: class_detail.php?course_id=" . $c_id . "&updated=1");
            exit;
        } catch (PDOException $e) {
            $error_msg = "更新エラー: " . $e->getMessage();
        }
    }

    // 更新完了メッセージの確認
    if (isset($_GET['updated'])) {
        $update_msg = "授業概要を更新しました！";
    }

    try {
        // A. 授業情報の取得
        $sql_course = "SELECT * FROM courses WHERE course_id = :id";
        $stmt = $pdo->prepare($sql_course);
        $stmt->bindValue(':id', $c_id, PDO::PARAM_INT);
        $stmt->execute();
        $course = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$course) {
            $error_msg = "指定された授業が見つかりませんでした。";
        } else {
            // B. レビュー情報の取得
            $sql_reviews = "SELECT * FROM reviews WHERE course_id = :id ORDER BY created_at DESC";
            $stmt_r = $pdo->prepare($sql_reviews);
            $stmt_r->bindValue(':id', $c_id, PDO::PARAM_INT);
            $stmt_r->execute();
            $reviews = $stmt_r->fetchAll(PDO::FETCH_ASSOC);

            // C. 平均評価の計算
            if (count($reviews) > 0) {
                $total = 0;
                foreach ($reviews as $r) {
                    $rating_val = isset($r['overall_rating']) ? $r['overall_rating'] : (isset($r['rating']) ? $r['rating'] : 0);
                    $total += $rating_val; 
                }
                $avg_rating = round($total / count($reviews), 1);
            }
        }

    } catch (PDOException $e) {
        $error_msg = "データ取得エラー: " . $e->getMessage();
    }
}

// XSS対策関数
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// 星評価を表示する関数
function renderStars($rating) {
    $rating = round($rating);
    $output = '';
    for ($i = 0; $i < $rating; $i++) {
        $output .= '<span class="text-warning">★</span>';
    }
    for ($i = $rating; $i < 5; $i++) {
        $output .= '<span class="text-muted">☆</span>';
    }
    return $output;
}
?>

<!-- Bootstrap CSS (このページ専用) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container py-5">

    <div class="mb-4">
        <!-- ブラウザの履歴で戻る -->
        <a href="javascript:history.back()" class="btn btn-outline-secondary">&larr; 戻る</a>
    </div>

    <?php if ($error_msg): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo $error_msg; ?>
        </div>
    <?php elseif ($course): ?>

        <?php if ($update_msg): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo h($update_msg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card register-card mb-5 p-4">
            <div class="card-body text-center">
                <span class="badge bg-primary mb-2">授業詳細</span>
                <h1 class="display-5 fw-bold mb-3"><?php echo h($course['course_name']); ?></h1>
                <p class="text-secondary fs-4 mb-4">
                    担当: <span class="text-dark fw-bold"><?php echo h($course['professor_name']); ?></span>
                </p>

                <!-- 平均評価バッジ -->
                <div class="bg-light p-3 rounded-3 d-inline-block mb-4">
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

                <!-- 授業概要エリア -->
                <div class="text-start border-top pt-4">
                    <h5 class="fw-bold text-secondary">📖 授業概要・シラバスメモ</h5>
                    
                    <div class="p-3 bg-light border rounded mb-2">
                        <?php if (!empty($course['description'])): ?>
                            <div style="white-space: pre-wrap;"><?php echo h($course['description']); ?></div>
                        <?php else: ?>
                            <p class="text-muted mb-0 small">
                                ※ この授業の詳しい説明はまだ登録されていません。<br>
                                「概要を編集する」ボタンから情報を追加して、みんなに共有しましょう！
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="text-end">
                        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#editDescriptionArea">
                            ✎ 概要を編集する
                        </button>
                    </div>

                    <div class="collapse mt-3" id="editDescriptionArea">
                        <div class="card card-body bg-white border-primary">
                            <form method="post">
                                <label for="description" class="form-label fw-bold">授業の説明を編集</label>
                                <textarea name="description" id="description" class="form-control mb-3" rows="5" placeholder="授業の目標、内容、評価方法、教科書などの情報を入力してください..."><?php echo h($course['description'] ?? ''); ?></textarea>
                                <div class="text-end">
                                    <button type="button" class="btn btn-secondary btn-sm me-2" data-bs-toggle="collapse" data-bs-target="#editDescriptionArea">キャンセル</button>
                                    <button type="submit" name="update_description" class="btn btn-primary btn-sm">保存する</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="mt-5">
                    <a href="../review/review_post.php?course_id=<?php echo h($course['course_id']); ?>" class="btn btn-primary btn-lg shadow-sm w-100">
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
                                    <?php 
                                        $r_val = isset($review['overall_rating']) ? $review['overall_rating'] : (isset($review['rating']) ? $review['rating'] : 0);
                                        echo renderStars($r_val); 
                                    ?>
                                </div>
                                <small class="text-muted">
                                    <?php echo h(date('Y/m/d', strtotime($review['created_at']))); ?>
                                </small>
                            </div>
                            <p class="card-text mb-0" style="white-space: pre-wrap;"><?php 
                                $r_text = isset($review['review_text']) ? $review['review_text'] : (isset($review['comment']) ? $review['comment'] : '');
                                echo h($r_text); 
                            ?></p>
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

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php
// フッター読み込み
require_once $root_path . 'includes/footer.php';
?>