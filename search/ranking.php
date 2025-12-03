<?php
// ▼▼▼ エラーを表示させる設定 ▼▼▼
ini_set('display_errors', 1);
error_reporting(E_ALL);
// ▲▲▲▲▲▲

// 1. 設定と共通パーツ
$root_path = '../';
$page_title = 'ランキング';
$page_css = 'board_search.css'; // デザインは検索画面と共通

require_once $root_path . 'includes/header.php';
require_once $root_path . 'includes/db.php';

// 2. パラメータ取得（人気順か新着順か）
$rankBy = $_GET['rank_by'] ?? 'popular';

if ($rankBy === 'newest') {
    $rankingTitle = '✨ 新着授業ランキング';
} else {
    $rankingTitle = '🔥 人気授業ランキング';
    $rankBy = 'popular';
}

// 3. SQLクエリ構築
$course_data = [];
$error_msg = "";

try {
    // ランキング専用クエリ
    $sql = "
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
        GROUP BY
            c.course_id, c.course_name, c.professor_name
    ";

    // 並び替え条件
    if ($rankBy === 'newest') {
        // 新着順: 最新投稿日時が新しい順 (NULLは最後)
        $sql .= " ORDER BY last_reviewed_at DESC NULLS LAST, c.course_id DESC ";
    } else {
        // 人気順: 口コミ数が多い順 -> 同数なら平均評価が高い順
        $sql .= " ORDER BY review_count DESC, avg_overall_rating DESC ";
    }

    // 上位20件のみ表示
    $sql .= " LIMIT 20 ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $course_data = $stmt->fetchAll();

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

    <!-- タイトル -->
    <div class="text-center mb-5">
        <h2 class="fw-bold"><?php echo h($rankingTitle); ?></h2>
        <p class="text-muted">学生のみんなが注目している授業TOP20</p>
    </div>

    <!-- 切り替えタブ -->
    <div class="ranking-switch">
        <a href="?rank_by=popular" class="<?php echo $rankBy === 'popular' ? 'active' : ''; ?>">
            🔥 人気ランキング
        </a>
        <a href="?rank_by=newest" class="<?php echo $rankBy === 'newest' ? 'active' : ''; ?>">
            ✨ 新着の口コミ
        </a>
    </div>

    <!-- エラーまたはデータ表示 -->
    <?php if ($error_msg): ?>
        <div class="alert alert-danger"><?php echo h($error_msg); ?></div>
    <?php elseif (empty($course_data)): ?>
        <div class="text-center py-5 text-muted">
            <p class="fs-5">まだデータがありません。</p>
            <a href="../review/review_post.php" class="btn btn-primary mt-2">最初の口コミを投稿する</a>
        </div>
    <?php else: ?>
        
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($course_data as $i => $row): 
                $rank = $i + 1;
                // 上位3位に特別な色をつける
                $rankClass = '';
                if ($rankBy === 'popular') {
                    if ($rank === 1) $rankClass = 'rank-1';
                    elseif ($rank === 2) $rankClass = 'rank-2';
                    elseif ($rank === 3) $rankClass = 'rank-3';
                }
            ?>
            <div class="col">
                <div class="course-card">
                    <!-- 順位バッジ -->
                    <div class="rank-badge <?php echo $rankClass ? $rankClass : 'bg-secondary'; ?>">
                        <?php echo $rank; ?>位
                    </div>

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
                                <span>口コミ数</span>
                                <div class="fw-bold">
                                    <?php echo number_format($row['review_count']); ?> 件
                                </div>
                            </div>
                        </div>
                        
                        <div class="review-meta">
                            最終更新: <?php echo $row['last_reviewed_at'] ? date('Y/m/d', strtotime($row['last_reviewed_at'])) : '-'; ?>
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