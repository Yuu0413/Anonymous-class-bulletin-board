<?php
session_start();

// --------------------------------------------------
// 1. DB接続設定
// --------------------------------------------------
$host = 'localhost';
$dbname = 'a_class'; 
$user = 'soto';   
$password = 'IGEGk8Ok'; 

try {
    $dsn = "pgsql:host=$host;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    exit('データベース接続失敗: ' . $e->getMessage());
}

/* ▼ 仮ログイン（本番では認証チームと連携） */
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 999;  // 仮ユーザーID
}

/* ▼ XSS 対策用 */
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/* ▼ 授業一覧の取得（プルダウン用） */
$courses = [];
try {
    $sql = "SELECT course_id, course_name, professor_name
            FROM courses
            ORDER BY course_name ASC";
    $stmt = $pdo->query($sql);
    $courses = $stmt->fetchAll();
} catch (PDOException $e) {
    $load_error = "授業一覧の取得に失敗しました: " . $e->getMessage();
}

/* ▼ URLパラメータから course_id を取得（あれば既定値にする） */
$selected_course_id = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);

/* ▼ メッセージ類 */
$message = "";
$alertClass = "";

/* ▼ POST 処理（口コミ登録） */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. 入力値の取得（DBのカラム名に合わせる）
    $selected_course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
    $overall_rating     = filter_input(INPUT_POST, 'overall_rating', FILTER_VALIDATE_INT); // 総合評価
    $easiness_rating    = filter_input(INPUT_POST, 'easiness_rating', FILTER_VALIDATE_INT); // 楽単度
    $review_text        = isset($_POST['review_text']) ? trim($_POST['review_text']) : ''; // 本文

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
            // 3. DBへの保存（SQLの項目名を修正済み）
            $sql = "INSERT INTO reviews (course_id, user_id, overall_rating, easiness_rating, review_text)
                    VALUES (:course_id, :user_id, :overall_rating, :easiness_rating, :review_text)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':course_id', $selected_course_id, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindValue(':overall_rating', $overall_rating, PDO::PARAM_INT);
            $stmt->bindValue(':easiness_rating', $easiness_rating, PDO::PARAM_INT);
            $stmt->bindValue(':review_text', $review_text, PDO::PARAM_STR);
            $stmt->execute();

            // 登録完了後は一覧画面（board_search.php）に戻る
            // ※必要であれば class_detail.php などに変更してください
            header("Location: board_search.php?msg=posted"); 
            exit;

        } catch (PDOException $e) {
            $message    = "口コミ登録中にエラーが発生しました: " . $e->getMessage();
            $alertClass = "alert-danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>口コミ投稿 | 授業レビュー</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f9f9f9; }
        .register-card { border: none; shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header-icon { font-size: 3rem; color: #0d6efd; }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">

            <div class="text-center mt-5 mb-4">
                <div class="header-icon">✎</div>
                <h1 class="h3 fw-bold text-dark">授業の口コミを投稿</h1>
                <p class="text-muted">後輩の履修選びの参考になるように、正直な感想を書いてください</p>
            </div>

            <?php if (!empty($load_error)) { ?>
                <div class="alert alert-danger">
                    <?php echo h($load_error); ?>
                </div>
            <?php } ?>

            <?php if ($message) { ?>
                <div class="alert <?php echo $alertClass; ?> alert-dismissible fade show shadow-sm" role="alert">
                    <?php echo h($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php } ?>

            <div class="card register-card p-4 bg-white shadow-sm">
                <div class="card-body">

                    <?php if (empty($courses)) { ?>
                        <p class="text-muted">
                            まだ授業が登録されていません。<br>
                            DBのcoursesテーブルを確認してください。
                        </p>
                    <?php } else { ?>
                        <form method="post" action="">

                            <div class="mb-4">
                                <label for="course_id" class="form-label fw-bold text-secondary">授業を選択</label>
                                <select class="form-select" id="course_id" name="course_id" required>
                                    <option value="">-- 選択してください --</option>
                                    <?php foreach ($courses as $c) { ?>
                                        <option value="<?php echo h($c['course_id']); ?>"
                                            <?php if ((int)$selected_course_id === (int)$c['course_id']) echo 'selected'; ?>>
                                            <?php echo h($c['course_name']); ?>（<?php echo h($c['professor_name']); ?>）
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold text-secondary">総合評価（★1〜5）</label>
                                <div class="d-flex gap-2">
                                    <?php for ($i = 1; $i <= 5; $i++) { ?>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input"
                                                   type="radio"
                                                   name="overall_rating"
                                                   id="overall_<?php echo $i; ?>"
                                                   value="<?php echo $i; ?>"
                                                   required
                                                <?php if (isset($overall_rating) && (int)$overall_rating === $i) echo 'checked'; ?>>
                                            <label class="form-check-label text-warning" for="overall_<?php echo $i; ?>">
                                                <?php echo str_repeat("★", $i); ?>
                                            </label>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold text-secondary">楽単度（★1〜5）</label>
                                <div class="form-text mb-2">単位の取りやすさ、課題の少なさなど</div>
                                <div class="d-flex gap-2">
                                    <?php for ($i = 1; $i <= 5; $i++) { ?>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input"
                                                   type="radio"
                                                   name="easiness_rating"
                                                   id="easiness_<?php echo $i; ?>"
                                                   value="<?php echo $i; ?>"
                                                   required
                                                <?php if (isset($easiness_rating) && (int)$easiness_rating === $i) echo 'checked'; ?>>
                                            <label class="form-check-label text-warning" for="easiness_<?php echo $i; ?>">
                                                <?php echo str_repeat("★", $i); ?>
                                            </label>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="review_text" class="form-label fw-bold text-secondary">口コミ本文</label>
                                <textarea class="form-control"
                                          id="review_text"
                                          name="review_text"
                                          rows="5"
                                          required
                                          placeholder="授業の雰囲気、課題量、テストの難易度、出席状況など自由に書いてください。"><?php
                                            echo isset($review_text) ? h($review_text) : '';
                                        ?></textarea>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg fw-bold">
                                    口コミを投稿する
                                </button>
                            </div>

                        </form>
                    <?php } ?>

                </div>
            </div>

            <div class="text-center mt-4">
                <a href="board_search.php" class="text-decoration-none text-secondary">
                    &larr; 授業一覧に戻る
                </a>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>