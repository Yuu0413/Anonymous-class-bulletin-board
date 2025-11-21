<?php
// 1. データベース接続設定 (PostgreSQL 用)
$host = 'localhost';
$db   = 'review_app_db'; 
$user = 'db_user';       
$pass = 'your_password'; 

// DSN (Data Source Name)
$dsn = "pgsql:host=$host;dbname=$db;user=$user;password=$pass";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, null, null, $options);
} catch (\PDOException $e) {
    die("データベース接続エラー (PostgreSQL): " . $e->getMessage());
}

// 2. ページネーションとランキング基準設定

$page = (int) ($_GET['page'] ?? 1); // 現在のページ番号
$perPage = 15; // 1ページあたりの表示件数
$offset = ($page - 1) * $perPage;

// ランキング基準の取得 (デフォルトは人気順)
$rankBy = $_GET['rank_by'] ?? 'popular';
$rankingTitle = ($rankBy === 'newest') ? '新着授業ランキング' : '人気授業ランキング';

// 3. SQLクエリの構築と実行 (PostgreSQL 構文)
// 基本クエリ（平均評価、口コミ数、最新投稿日時を算出）
$baseQuery = "
    SELECT 
        c.course_id,
        c.course_name,
        c.professor_name,
        COUNT(r.review_id) AS review_count,
        COALESCE(AVG(r.overall_rating), 0) AS avg_overall_rating,
        COALESCE(AVG(r.easiness_rating), 0) AS avg_easiness_rating,
        MAX(r.created_at) AS last_reviewed_at  -- 最新の口コミ投稿日時
    FROM 
        courses c
    LEFT JOIN 
        reviews r ON c.course_id = r.course_id
    GROUP BY 
        c.course_id, c.course_name, c.professor_name
";

// ORDER BY 句を設定
$orderByClause = "ORDER BY ";
if ($rankBy === 'newest') {
    // 新着ランキング: 最新投稿日時が新しい順 (降順)
    $orderByClause .= "last_reviewed_at DESC, review_count DESC";
} else {
    // 人気ランキング (デフォルト): 口コミ数が多い順、同数の場合は平均総合評価順
    $orderByClause .= "review_count DESC, avg_overall_rating DESC";
    $rankBy = 'popular'; // 明示的に人気順とする
}


// 最終的なランキングクエリ 
$rankingQuery = $baseQuery . $orderByClause . " 
    LIMIT :limit OFFSET :offset
";

// 総件数カウントクエリ
$countQuery = "
    SELECT COUNT(*) AS total
    FROM (" . $baseQuery . ") AS T
";

// --- クエリ実行：ランキング取得 ---
$stmt = $pdo->prepare($rankingQuery);
$stmt->bindParam(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$results = $stmt->fetchAll();

// --- クエリ実行：総件数取得 ---
$countStmt = $pdo->query($countQuery);
$totalCount = $countStmt->fetch()['total'];

$totalPages = ceil($totalCount / $perPage);
$currentPage = $page;

/**
 * 評価を星アイコンに変換するヘルパー関数
 */
function displayStarRating($rating) {
    $fullStar = '⭐';
    $emptyStar = '☆';
    $output = '';
    
    $full = floor($rating);
    for ($i = 0; $i < $full; $i++) {
        $output .= $fullStar;
    }
    
    $remain = 5 - $full;
    for ($i = 0; $i < $remain; $i++) {
        $output .= $emptyStar;
    }
    
    return $output;
}

/**
 * 日時を整形するヘルパー関数
 */
function formatDateTime($datetime) {
    if (!$datetime) return 'N/A';
    // PostgreSQLのTIMESTAMPはPHPでそのままDateTimeとして扱える
    $dt = new DateTime($datetime);
    return $dt->format('Y/m/d H:i');
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($rankingTitle) ?></title>
    <style>
        /* CSS スタイル */
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            padding: 20px;
            background-color: #e9ecef;
            color: #333;
        }
        .container { 
            max-width: 1200px; /* 横幅を少し広げました */
            margin: 20px auto; 
            background: white; 
            padding: 30px; 
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
        }
        h1 { 
            color: #007bff;
            border-bottom: 3px solid #007bff; 
            padding-bottom: 15px; 
            margin-bottom: 15px;
            font-size: 1.8em;
        }
        .ranking-switch {
            text-align: right;
            margin-bottom: 20px;
        }
        .ranking-switch a {
            padding: 8px 15px;
            margin-left: 10px;
            border: 1px solid #007bff;
            border-radius: 6px;
            text-decoration: none;
            color: #007bff;
            display: inline-block;
        }
        .ranking-switch a.active {
            background-color: #007bff;
            color: white;
            font-weight: bold;
        }
        p {
            margin-bottom: 20px;
        }
        /* テーブルスタイル */
        .result-table { 
            width: 100%; 
            border-collapse: separate; 
            border-spacing: 0 10px;
        }
        .result-table th { 
            background-color: #343a40; 
            color: white; 
            padding: 15px; 
            text-align: left;
            font-weight: 600;
        }
        .result-table th:first-child { border-top-left-radius: 8px; }
        .result-table th:last-child { border-top-right-radius: 8px; }
        
        .result-table td { 
            background-color: #f8f9fa;
            border: 1px solid #dee2e6; 
            border-width: 1px 0;
            padding: 15px;
            vertical-align: middle;
        }
        .result-table tr:hover td { 
            background-color: #e2f0ff;
            transition: background-color 0.2s;
        }
        /* ランキングバッジ */
        .ranking-badge { 
            font-weight: bold; 
            color: white; 
            padding: 5px 10px; 
            border-radius: 20px; 
            margin-right: 8px; 
            font-size: 1em;
            display: inline-block;
            min-width: 50px;
            text-align: center;
        }
        .ranking-badge { background: #6c757d; }
        .top3-badge { 
            background: #ffc107;
            color: #333; 
            border: 2px solid #ff9800;
        }
        .top2-badge { background: #adb5bd; }
        .top1-badge { background: #007bff; }
        
        /* リンク */
        a { 
            color: #0056b3; 
            text-decoration: none; 
            font-weight: 500;
        }
        a:hover { 
            text-decoration: underline; 
        }

        /* ページネーション */
        .pagination { 
            margin-top: 30px; 
            text-align: center; 
        }
        .pagination a, .pagination span { 
            padding: 10px 18px; 
            margin: 0 5px; 
            border: 1px solid #007bff; 
            text-decoration: none; 
            color: #007bff; 
            border-radius: 6px; 
            display: inline-block;
        }
        .pagination span.current { 
            background: #007bff; 
            color: white; 
            border-color: #007bff;
            font-weight: bold;
        }
        .pagination span {
             color: #6c757d;
             border-color: #adb5bd;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?= htmlspecialchars($rankingTitle) ?></h1>
        
        <div class="ranking-switch">
            <a href="board.php?rank_by=popular" class="<?= $rankBy === 'popular' ? 'active' : '' ?>">
                人気順ランキング
            </a>
            <a href="board.php?rank_by=newest" class="<?= $rankBy === 'newest' ? 'active' : '' ?>">
                新着順ランキング
            </a>
        </div>
        
        <p>全授業を口コミの件数（または最新投稿日時）に基づいて表示しています。（全 **<?= number_format($totalCount) ?>** 件）</p>

        <table class="result-table">
            <thead>
                <tr>
                    <th style="width: 8%;">順位</th>
                    <th style="width: 25%;">授業名</th>
                    <th style="width: 15%;">教授名</th>
                    <th style="width: 15%;">平均総合評価</th>
                    <th style="width: 10%;">平均楽単度</th>
                    <th style="width: 12%;">口コミ数</th>
                    <th style="width: 15%;">最終投稿日時</th> </tr>
            </thead>
            <tbody>
                <?php if (empty($results)): ?>
                    <tr><td colspan="7" style="text-align: center;">現在、口コミのある授業は登録されていません。</td></tr>
                <?php else: ?>
                    <?php foreach ($results as $index => $course): 
                        // ランキング順位を計算
                        $rank = $offset + $index + 1;
                        $badgeClass = '';
                        if ($rankBy === 'popular' && $rank == 1) $badgeClass = 'top1-badge top3-badge';
                        else if ($rankBy === 'popular' && $rank == 2) $badgeClass = 'top2-badge top3-badge';
                        else if ($rankBy === 'popular' && $rank == 3) $badgeClass = 'top3-badge';
                    ?>
                    <tr>
                        <td>
                            <span class="ranking-badge <?= $badgeClass ?>">
                                <?= $rank ?>位
                            </span>
                        </td>
                        <td>
                            <a href="../class/detail.php?id=<?= htmlspecialchars($course['course_id']) ?>">
                                **<?= htmlspecialchars($course['course_name']) ?>**
                            </a>
                        </td>
                        <td><?= htmlspecialchars($course['professor_name']) ?></td>
                        <td>
                            <?= displayStarRating($course['avg_overall_rating']) ?>
                            (**<?= number_format($course['avg_overall_rating'], 1) ?>**)
                        </td>
                        <td>
                            <?= displayStarRating($course['avg_easiness_rating']) ?>
                            (**<?= number_format($course['avg_easiness_rating'], 1) ?>**)
                        </td>
                        <td><?= number_format($course['review_count']) ?> 件</td>
                        <td>
                            <?= formatDateTime($course['last_reviewed_at']) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php 
            // ページネーションのクエリ文字列にランキング基準を保持
            $queryString = http_build_query(array_filter(['rank_by' => $rankBy])); 

            if ($totalPages > 1) {
                // 前へ
                if ($currentPage > 1) {
                    echo '<a href="board.php?' . $queryString . '&page=' . ($currentPage - 1) . '">« 前へ</a>';
                } else {
                    echo '<span>« 前へ</span>';
                }

                // ページ番号
                for ($i = 1; $i <= $totalPages; $i++) {
                    if ($i == $currentPage) {
                        echo '<span class="current">' . $i . '</span>';
                    } else {
                        echo '<a href="board.php?' . $queryString . '&page=' . $i . '">' . $i . '</a>';
                    }
                }

                // 次へ
                if ($currentPage < $totalPages) {
                    echo '<a href="board.php?' . $queryString . '&page=' . ($currentPage + 1) . '">次へ »</a>';
                } else {
                    echo '<span>次へ »</span>';
                }
            }
            ?>
        </div>
    </div>
</body>
</html>