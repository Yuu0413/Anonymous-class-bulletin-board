<?php
// --------------------------------------------------
// 設定: データベース接続情報
// --------------------------------------------------
// ★チームで共有されている接続情報に変更してください
$host = 'localhost';
$dbname = 'your_database_name'; // ★自分のDB名に変更
$user = 'your_user';            // ★Postgresのユーザー名 (例: postgres)
$pass = 'your_password';        // ★Postgresのパスワード

try {
    // PostgreSQL接続 (ご提示のSQLがPostgres形式のため)
    $dsn = "pgsql:host=$host;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password);

    // エラー発生時に例外を投げる設定
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // 本番環境ではエラーメッセージを隠すのが一般的ですが、開発中は表示します
    exit('データベース接続失敗: ' . $e->getMessage());
}

// --------------------------------------------------
// 処理: 並び替え機能 (Controller)
// --------------------------------------------------

// URLパラメータを取得 (例: ranking.php?sort=easiness)
$sort_param = $_GET['sort'] ?? 'review_count';

// 許可するソート条件のリスト (ホワイトリスト方式でセキュリティ対策)
$allowed_sorts = [
    'review_count' => 'review_count DESC',       // 口コミ件数が多い順
    'easiness'     => 'avg_easiness DESC',       // 楽単度が高い順
    'overall'      => 'avg_overall DESC',        // 総合評価が高い順 (＝先生の人気)
];

// 正しいパラメータならそれを使い、不正ならデフォルト(review_count)を使う
$order_by = $allowed_sorts[$sort_param] ?? 'review_count DESC';

// --------------------------------------------------
// 処理: データ取得 (Model)
// --------------------------------------------------

$sql = "
    SELECT
        c.course_id,
        c.course_name,
        c.professor_name,
        -- 口コミ数
        COUNT(r.review_id) as review_count,
        -- 楽単度の平均 (NULLなら0にする)
        COALESCE(AVG(r.easiness_rating), 0) as avg_easiness,
        -- 総合評価の平均
        COALESCE(AVG(r.overall_rating), 0) as avg_overall
    FROM
        courses c
    LEFT JOIN
        reviews r ON c.course_id = r.course_id
    GROUP BY
        c.course_id
    ORDER BY
        {$order_by}
";

// クエリ実行
try {
    $stmt = $pdo->query($sql);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    exit('データ取得失敗: ' . $e->getMessage());
}

// XSS対策用関数 (表示時に使用)
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>授業人気ランキング</title>
    <link rel="stylesheet" href="ranking.css">
</head>
<body>

    <div class="container">
        <header class="page-header">
            <h1>🏆 授業人気ランキング</h1>
            <p>口コミや楽単度から、人気の授業を探そう！</p>
        </header>

        <div class="sort-menu">
            <span class="sort-label">並び替え:</span>
            <a href="?sort=review_count" class="sort-btn <?= $sort_param === 'review_count' ? 'active' : '' ?>">
                📝 口コミ数順
            </a>
            <a href="?sort=easiness" class="sort-btn <?= $sort_param === 'easiness' ? 'active' : '' ?>">
                ✨ 楽単度順
            </a>
            <a href="?sort=overall" class="sort-btn <?= $sort_param === 'overall' ? 'active' : '' ?>">
                🔥 人気順(総合)
            </a>
        </div>

        <div class="ranking-list">
            <?php if (empty($courses)): ?>
                <p class="no-data">現在、登録されている授業はありません。</p>
            <?php else: ?>
                <?php foreach ($courses as $index => $course): ?>
                    <div class="course-card">
                        <div class="rank-badge rank-<?= $index + 1 ?>"><?= $index + 1 ?>位</div>

                        <div class="card-content">
                            <h2 class="course-title">
                                <a href="detail.php?id=<?= h($course['course_id']) ?>">
                                    <?= h($course['course_name']) ?>
                                </a>
                            </h2>
                            <p class="professor">
                                担当: <?= h($course['professor_name']) ?> 先生
                            </p>

                            <div class="stats-container">
                                <div class="stat-box">
                                    <span class="stat-label">口コミ</span>
                                    <span class="stat-value"><?= $course['review_count'] ?></span>
                                    <span class="stat-unit">件</span>
                                </div>
                                <div class="stat-box">
                                    <span class="stat-label">楽単度</span>
                                    <span class="stat-value star-color">
                                        ★<?= number_format($course['avg_easiness'], 1) ?>
                                    </span>
                                </div>
                                <div class="stat-box">
                                    <span class="stat-label">総合評価</span>
                                    <span class="stat-value star-color">
                                        ★<?= number_format($course['avg_overall'], 1) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>