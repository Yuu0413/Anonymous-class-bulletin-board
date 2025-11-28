<?php
// 1. データベース接続設定 (PostgreSQL 用)
$host = 'localhost';
$db   = 'review_app_db'; 
$user = 'db_user';       
$pass = 'your_password'; 

$dsn = "pgsql:host=$host;dbname=$dbname";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    // 第2引数にユーザー、第3引数にパスワードを渡す
    $pdo = new PDO($dsn, $user, $password, $options);
} catch (\PDOException $e) {
    die("データベース接続エラー (PostgreSQL): " . $e->getMessage());
}

// 2. 変数設定、キーワード、ページネーション、ランキング基準の取得

$keyword = $_GET['q'] ?? ''; 
$page = (int) ($_GET['page'] ?? 1); 
$perPage = 15; 
$offset = ($page - 1) * $perPage;
$rankBy = $_GET['rank_by'] ?? 'popular';

// 検索キーワード用に '%%' で囲む (LIKE検索用)。小文字化して大文字小文字を区別しない検索を実現。
$searchKeyword = '%' . mb_strtolower($keyword, 'UTF-8') . '%';

// ページタイトル設定
if (!empty($keyword)) {
    $rankingTitle = '「' . htmlspecialchars($keyword) . '」の検索結果';
    $searchMode = true;
} else if ($rankBy === 'newest') {
    $rankingTitle = '新着授業ランキング';
    $searchMode = false;
} else {
    $rankingTitle = '人気授業ランキング';
    $rankBy = 'popular'; 
    $searchMode = false;
}

// 3. SQLクエリの構築

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
";

// WHERE句: キーワード検索機能 (検索モードの場合のみ適用)
$whereClause = "WHERE 1=1";
if ($searchMode) {
    $whereClause .= " 
        AND (LOWER(c.course_name) LIKE :keyword OR LOWER(c.professor_name) LIKE :keyword)
    ";
}

// GROUP BY 句
$groupByClause = "
    GROUP BY 
        c.course_id, c.course_name, c.professor_name
";

// ORDER BY 句を設定
$orderByClause = "ORDER BY ";
if ($rankBy === 'newest' && !$searchMode) {
    // 新着ランキング（非検索モード時）: 最新投稿日時が新しい順
    $orderByClause .= "last_reviewed_at DESC, review_count DESC";
} else {
    // 人気ランキング（デフォルト）または検索結果: 口コミ数が多い順、同数の場合は平均総合評価順
    $orderByClause .= "review_count DESC, avg_overall_rating DESC";
}


// 最終的なクエリ
$finalQuery = $baseQuery . $whereClause . $groupByClause . $orderByClause . " LIMIT :limit OFFSET :offset";

// 総件数カウントクエリ
$countQuery = "
    SELECT COUNT(*) AS total
    FROM (" . $baseQuery . $whereClause . $groupByClause . ") AS T
";

// 4. クエリの実行

// --- 総件数の取得 ---
$countStmt = $pdo->prepare($countQuery);
if ($searchMode) {
    $countStmt->bindParam(':keyword', $searchKeyword, PDO::PARAM_STR);
}
$countStmt->execute();
$totalCount = $countStmt->fetch()['total'];

$totalPages = ceil($totalCount / $perPage);

// --- ランキング/検索結果の取得 ---
$stmt = $pdo->prepare($finalQuery);
if ($searchMode) {
    $stmt->bindParam(':keyword', $searchKeyword, PDO::PARAM_STR);
}
$stmt->bindParam(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$results = $stmt->fetchAll();


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
        /* -------------------------------------- */
        /* CSS スタイル */
        /* -------------------------------------- */
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            padding: 20px;
            background-color: #e9ecef;
            color: #333;
        }
        .container { 
            max-width: 1200px; 
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
        .search-form { 
            display: flex; 
            margin-bottom: 20px; 
        }
        .search-form input[type="text"] {
            flex-grow: 1;
            padding: 10px;
            border: 2px solid #007bff; /* 検索フォームを強調 */
            border-radius: 6px 0 0 6px;
            font-size: 1em;
        }
        .search-form button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 0 6px 6px 0;
            cursor: pointer;
            font-size: 1em;
        }
        .search-form button:hover {
            background-color: #0056b3;
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
            background: #6c757d; 
        }
        /* リンク, ページネーション (省略) */
    </style>
</head>
<body>
    <div class="container">
        <h1>📚 <?= htmlspecialchars($rankingTitle) ?></h1>
        
        <form action="board_search.php" method="GET" class="search-form">
            <input type="hidden" name="rank_by" value="<?= htmlspecialchars($rankBy) ?>">
            <input type="text" name="q" placeholder="授業名または先生名を入力..." value="<?= htmlspecialchars($keyword) ?>">
            <button type="submit">検索</button>
        </form>

        <?php if (!$searchMode): ?>
            <div class="ranking-switch">
                ランキング順序: 
                <a href="board_search.php?rank_by=popular" class="<?= $rankBy === 'popular' ? 'active' : '' ?>">
                    人気順
                </a>
                <a href="board_search.php?rank_by=newest" class="<?= $rankBy === 'newest' ? 'active' : '' ?>">
                    新着順
                </a>
            </div>
        <?php endif; ?>
        
        <p>全 **<?= number_format($totalCount) ?>** 件中、<?= $offset + 1 ?>件目から<?= $offset + count($results) ?>件目を表示しています。</p>

        <table class="result-table">
            <thead>
                <tr>
                    <th style="width: 8%;">順位</th>
                    <th style="width: 25%;">授業名</th>
                    <th style="width: 15%;">教授名</th>
                    <th style="width: 15%;">平均総合評価</th>
                    <th style="width: 10%;">平均楽単度</th>
                    <th style="width: 12%;">口コミ数</th>
                    <th style="width: 15%;">最終投稿日時</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($results)): ?>
                    <tr><td colspan="7" style="text-align: center;">条件に一致する授業は見つかりませんでした。</td></tr>
                <?php else: ?>
                    <?php foreach ($results as $index => $course): 
                        $rank = $offset + $index + 1;
                        $badgeClass = '';
                        if (!$searchMode && $rankBy === 'popular' && $rank <= 3) {
                            $badgeClass = ($rank == 1) ? 'top1-badge top3-badge' : (($rank == 2) ? 'top2-badge top3-badge' : 'top3-badge');
                        }
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
            // ページネーションのクエリ文字列にキーワードとランキング基準を保持
            $queryString = http_build_query(array_filter(['q' => $keyword, 'rank_by' => $rankBy])); 

            if ($totalPages > 1) {
                // 前へ
                if ($currentPage > 1) {
                    echo '<a href="board_search.php?' . $queryString . '&page=' . ($currentPage - 1) . '">« 前へ</a>';
                } else {
                    echo '<span>« 前へ</span>';
                }

                // ページ番号
                for ($i = 1; $i <= $totalPages; $i++) {
                    if ($i == $currentPage) {
                        echo '<span class="current">' . $i . '</span>';
                    } else {
                        echo '<a href="board_search.php?' . $queryString . '&page=' . $i . '">' . $i . '</a>';
                    }
                }

                // 次へ
                if ($currentPage < $totalPages) {
                    echo '<a href="board_search.php?' . $queryString . '&page=' . ($currentPage + 1) . '">次へ »</a>';
                } else {
                    echo '<span>次へ »</span>';
                }
            }
            ?>
        </div>
    </div>
</body>
</html>