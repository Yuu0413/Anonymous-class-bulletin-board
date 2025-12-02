<?php
// 1. 設定と共通パーツ
$root_path = '../';
$page_title = '口コミ投稿';
$page_css = 'review_post.css';

require_once $root_path . 'includes/header.php';
require_once $root_path . 'includes/db.php'; // 共通DB設定

// 2. 変数初期化
$message = "";
$alertClass = "";
$courses = [];

/* ▼ XSS 対策用関数 */
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/* ▼ 授業一覧の取得（プルダウン用） */
try {
    $sql = "SELECT course_id, course_name, professor_name
            FROM courses
            ORDER BY course_name ASC";
    $stmt = $pdo->query($sql);
    $courses = $stmt->fetchAll();
} catch (PDOException $e) {
    $message = "授業一覧の取得に失敗しました: " . $e->getMessage();
    $alertClass = "alert-danger";
}

/* ▼ URLパラメータから course_id を取得（既定値） */
$selected_course_id = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);
$overall_rating = null;
$easiness_rating = null;
$review_text = "";


/* ▼ POST 処理（口コミ登録） */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. 入力値の取得
    $selected_course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
    $overall_rating     = filter_input(INPUT_POST, 'overall_rating', FILTER_VALIDATE_INT);
    $easiness_rating    = filter_input(INPUT_POST, 'easiness_rating', FILTER_VALIDATE_INT);
    $review_text        = isset($_POST['review_text']) ? trim($_POST['review_text']) : '';

    // 2. バリデーション
    if (!$selected_course_id || !$overall_rating || !$easiness_rating) {
        $message    = "授業、総合評価、楽単度はすべて選択してください。";
        $alertClass = "alert-warning";
    } elseif ($overall_rating < 1 || $overall_rating > 5 || $easiness_rating < 1 || $easiness_rating > 5) {
        $message    = "評価は 1〜5 の範囲で選択してください。";
        $alertClass = "alert-warning";
    } elseif (empty($review_text)) {
        $message    = "口コミ本文を入力してください。";
        $alertClass = "alert-warning";
    } else {
        try {
            // 3. DBへの保存
            $sql = "INSERT INTO reviews (course_id, user_id, overall_rating, easiness_rating, review_text)
                    VALUES (:course_id, :user_id, :overall_rating, :easiness_rating, :review_text)";

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':course_id', $selected_course_id, PDO::PARAM_INT);
            // session_start() は header.php で行われているので $_SESSION は使えます
            $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindValue(':overall_rating', $overall_rating, PDO::PARAM_INT);
            $stmt->bindValue(':easiness_rating', $easiness_rating, PDO::PARAM_INT);
            $stmt->bindValue(':review_text', $review_text, PDO::PARAM_STR);
            $stmt->execute();

            // ★登録完了後の移動
            // board_search.php がまだ未完成かもしれないので、一旦 home.php に戻します
            // 完成したら header("Location: ../search/board_search.php?msg=posted"); に変えてください
            header("Location: ../home.php?msg=posted");
            exit;

        } catch (PDOException $e) {
            $message    = "エラーが発生しました: " . $e->getMessage();
            $alertClass = "alert-danger";
        }
    }
}
?>

<!-- Bootstrap CSS (このページ専用) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container pb-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">

            <div class="text-center mt-5 mb-4">
                <div class="header-icon">✎</div>
                <h1 class="h3 fw-bold text-dark">授業の口コミを投稿</h1>
                <p class="text-muted">後輩の履修選びの参考になるように、正直な感想を書いてください</p>
            </div>

            <?php if ($message): ?>
                <div class="alert <?php echo $alertClass; ?> alert-dismissible fade show shadow-sm" role="alert">
                    <?php echo h($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card register-card p-4 bg-white">
                <div class="card-body">

                    <?php if (empty($courses)): ?>
                        <div class="alert alert-info">
                            まだ授業が登録されていません。<br>
                            先に<a href="../class/class_register.php">授業の登録</a>を行ってください。
                        </div>
                    <?php else: ?>
                        <form method="post" action="">

                            <div class="mb-4">
                                <label for="course_id" class="form-label fw-bold text-secondary">授業を選択</label>
                                <select class="form-select" id="course_id" name="course_id" required>
                                    <option value="">-- 選択してください --</option>
                                    <?php foreach ($courses as $c): ?>
                                        <option value="<?php echo h($c['course_id']); ?>"
                                            <?php if ((int)$selected_course_id === (int)$c['course_id']) echo 'selected'; ?>>
                                            <?php echo h($c['course_name']); ?>（<?php echo h($c['professor_name']); ?>）
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold text-secondary">総合評価（★1〜5）</label>
                                <div class="d-flex gap-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="overall_rating" id="overall_<?php echo $i; ?>" value="<?php echo $i; ?>" required <?php if ((int)$overall_rating === $i) echo 'checked'; ?>>
                                            <label class="form-check-label text-warning" for="overall_<?php echo $i; ?>">
                                                <?php echo str_repeat("★", $i); ?>
                                            </label>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold text-secondary">楽単度（★1〜5）</label>
                                <div class="form-text mb-2">単位の取りやすさ、課題の少なさなど</div>
                                <div class="d-flex gap-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="easiness_rating" id="easiness_<?php echo $i; ?>" value="<?php echo $i; ?>" required <?php if ((int)$easiness_rating === $i) echo 'checked'; ?>>
                                            <label class="form-check-label text-warning" for="easiness_<?php echo $i; ?>">
                                                <?php echo str_repeat("★", $i); ?>
                                            </label>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="review_text" class="form-label fw-bold text-secondary">口コミ本文</label>
                                <textarea class="form-control" id="review_text" name="review_text" rows="5" required placeholder="授業の雰囲気、課題量、テストの難易度、出席状況など自由に書いてください。"><?php echo h($review_text); ?></textarea>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg fw-bold">
                                    口コミを投稿する
                                </button>
                            </div>

                        </form>
                    <?php endif; ?>

                </div>
            </div>

            <div class="text-center mt-4">
                <a href="../home.php" class="text-decoration-none text-secondary">
                    &larr; メインメニューに戻る
                </a>
            </div>

        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php
// フッター読み込み
require_once $root_path . 'includes/footer.php';
?>