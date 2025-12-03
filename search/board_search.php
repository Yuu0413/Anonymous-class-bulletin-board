<?php
// ▼▼▼ エラー表示設定 ▼▼▼
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. 設定と共通パーツ
$root_path = '../';
$page_title = '授業検索・一覧';
$page_css = 'board_search.css';

require_once $root_path . 'includes/header.php';
require_once $root_path . 'includes/db.php';

// 2. パラメータ取得
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$page    = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 12;
$offset  = ($page - 1) * $perPage;

// ソート順は指定がなければ「人気順」をデフォルトにしますが、画面には「ランキング」と出しません
$rankBy  = isset($_GET['rank_by']) ? $_GET['rank_by'] : 'popular';

// 検索モードかどうかの判定
$searchMode = !empty($keyword);

// タイトルの決定（ランキング表記を廃止）
if ($searchMode) {
    $pageHeaderTitle = '「' . htmlspecialchars($keyword) . '」の検索結果';
} else {
    $pageHeaderTitle = '授業一覧'; // シンプルなタイトルに変更
}

// 3. SQLクエリ構築
$courses = [];
$totalCount = 0;
$totalPages = 1;
$error_msg = "";

try {
    // ベースとなるSQL
    $baseQuery = "
        SELECT
            c.course_id,
            c.course_name,
            c.professor_name,
            COUNT(r.review_id) AS review_count,
            COALESCE(AVG(r.overall_rating), 0) AS avg_overall_rating,
            COALESCE(AVG(r.easiness_rating), 0) AS avg_easiness_rating,
            MAX(r.created_at) AS last_reviewed_at
        FROM
            courses c
        LEFT JOIN
            reviews r ON c.course_id = r.course_id
    ";

    // 検索条件
    $whereClause = " WHERE 1=1 ";
    if ($searchMode) {
        $whereClause .= " AND (c.course_name LIKE :keyword OR c.professor_name LIKE :keyword) ";
    }

    // グループ化
    $groupByClause = " GROUP BY c.course_id, c.course_name, c.professor_name ";

    // 並び替え (内部ロジックとしては人気順などでソートしておく)
    $orderByClause = " ORDER BY ";
    if ($rankBy === 'newest') {
        $orderByClause .= " last_reviewed_at DESC NULLS LAST, c.course_id DESC ";
    } else {
        // デフォルト: 口コミ数 > 平均評価
        $orderByClause .= " review_count DESC, avg_overall_rating DESC ";
    }

    // --- (1) 件数カウント ---
    $countSql = "SELECT COUNT(*) FROM ( " . $baseQuery . $whereClause . $groupByClause . " ) AS sub";
    $countStmt = $pdo->prepare($countSql);
    if ($searchMode) {
        $countStmt->bindValue(':keyword', '%' . $keyword . '%', PDO::PARAM_STR);
    }
    $countStmt->execute();
    $totalCount = $countStmt->fetchColumn();
    $totalPages = ceil($totalCount / $perPage);
    if ($totalPages < 1) $totalPages = 1;

    // --- (2) データ取得 ---
    $mainSql = $baseQuery . $whereClause . $groupByClause . $orderByClause . " LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($mainSql);
    if ($searchMode) {
        $stmt->bindValue(':keyword', '%' . $keyword . '%', PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll();

} catch (PDOException $e) {
    $error_msg = "データ取得エラー: " . $e->getMessage();
}

// ヘルパー関数
function renderStars($rating) {
    $rating = round($rating);
    $stars = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
    return '<span class="star">' . $stars . '</span>';
}
function h($s) {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
?>

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container py-5">

    <!-- 検索フォームエリア -->
    <div class="search-container">
        <h2 class="h4 search-title">🔍 授業を探す</h2>
        <form action="" method="GET" class="row g-2">
            <div class="col-md-9">
                <input type="text" name="q" class="form-control form-control-lg" 
                       placeholder="授業名、先生の名前..." value="<?php echo h($keyword); ?>">
            </div>
            <div class="col-md-3 d-grid">
                <button type="submit" class="btn btn-primary btn-lg">検索</button>
            </div>
        </form>
        <?php if ($searchMode): ?>
            <div class="mt-2 text-end">
                <a href="board_search.php" class="text-decoration-none text-secondary small">× 検索条件をクリア</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- 結果件数表示 -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="h5 fw-bold text-secondary mb-0"><?php echo h($pageHeaderTitle); ?></h3>
        <span class="badge bg-light text-dark border">
            全 <?php echo number_format($totalCount); ?> 件
        </span>
    </div>

    <?php if ($error_msg): ?>
        <div class="alert alert-danger"><?php echo h($error_msg); ?></div>
    <?php elseif (empty($results)): ?>
        <div class="text-center py-5 text-muted">
            <p class="fs-5">該当する授業が見つかりませんでした 😢</p>
            <p>キーワードを変えて検索するか、新しく登録してください。</p>
            <a href="../class/class_register.php" class="btn btn-success mt-2">授業を新しく登録する</a>
        </div>
    <?php else: ?>

        <!-- カード一覧表示エリア -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($results as $i => $row): ?>
            <div class="col">
                <div class="course-card">
                    <!-- ランキングバッジ（金銀銅）の表示ロジックを削除しました -->
                    
                    <div>
                        <h4 class="course-title text-truncate"><?php echo h($row['course_name']); ?></h4>
                        <p class="prof-name">👨‍🏫 <?php echo h($row['professor_name']); ?></p>
                        
                        <div class="rating-box">
                            <div class="rating-row">
                                <span>総合評価</span>
                                <div>
                                    <?php echo renderStars($row['avg_overall_rating']); ?>
                                    <span class="fw-bold ms-1"><?php echo number_format($row['avg_overall_rating'], 1); ?></span>
                                </div>
                            </div>
                            <div class="rating-row">
                                <span>楽単度</span>
                                <div>
                                    <span class="text-warning small">
                                        <?php echo str_repeat('♦', round($row['avg_easiness_rating'])); ?>
                                        <?php echo str_repeat('♢', 5 - round($row['avg_easiness_rating'])); ?>
                                    </span>
                                    <span class="fw-bold ms-1"><?php echo number_format($row['avg_easiness_rating'], 1); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="review-meta">
                            口コミ <?php echo number_format($row['review_count']); ?> 件
                            <br>
                            <small>最終: <?php echo $row['last_reviewed_at'] ? date('Y/m/d', strtotime($row['last_reviewed_at'])) : '-'; ?></small>
                        </div>
                    </div>

                    <div class="mt-3">
                        <a href="../class/class_detail.php?course_id=<?php echo h($row['course_id']); ?>" 
                           class="btn btn-outline-primary btn-detail stretched-link">
                           詳細を見る
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- ページネーション -->
        <?php if ($totalPages > 1): ?>
            <nav>
                <ul class="pagination">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?q=<?php echo h($keyword); ?>&page=<?php echo $page - 1; ?>">«</a>
                    </li>
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <li class="page-item <?php echo ($page === $p) ? 'active' : ''; ?>">
                            <a class="page-link" href="?q=<?php echo h($keyword); ?>&page=<?php echo $p; ?>">
                                <?php echo $p; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?q=<?php echo h($keyword); ?>&page=<?php echo $page + 1; ?>">»</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>

    <?php endif; ?>
    
    <div class="text-center mt-5">
        <a href="../home.php" class="text-secondary text-decoration-none">
            &larr; メインメニューへ戻る
        </a>
    </div>

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php
require_once $root_path . 'includes/footer.php';
?>