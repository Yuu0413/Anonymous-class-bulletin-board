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

// 2. 変数設定、キーワードとページネーションの取得
$keyword = $_GET['q'] ?? ''; // 検索キーワード
$page = (int) ($_GET['page'] ?? 1); 
$perPage = 10; // 1ページあたりの表示件数
$offset = ($page - 1) * $perPage;

// 検索キーワード用に '%%' で囲む (LIKE検索用)。小文字化して大文字小文字を区別しない検索を実現。
$searchKeyword = '%' . mb_strtolower($keyword, 'UTF-8') . '%';

// 3. SQLクエリの構築

// 基本クエリ（平均評価と口コミ数を算出）
$baseQuery = "
    SELECT 
        c.course_id,
        c.course_name,
        c.professor_name,
        COUNT(r.review_id) AS review_count,
        COALESCE(AVG(r.overall_rating), 0) AS avg_overall_rating,  -- 総合評価の平均
        COALESCE(AVG(r.easiness_rating), 0) AS avg_easiness_rating  -- 楽単度の平均
    FROM 
        courses c
    LEFT JOIN 
        reviews r ON c.course_id = r.course_id
";

// WHERE句: キーワード検索機能
$whereClause = "WHERE 1=1";
if (!empty($keyword)) {
    // LOWER()とLIKEを組み合わせて大文字小文字を区別しない検索
    $whereClause .= " 
        AND (LOWER(c.course_name) LIKE :keyword OR LOWER(c.professor_name) LIKE :keyword)
    ";
}

// GROUP BY 句
$groupByClause = "
    GROUP BY 
        c.course_id, c.course_name, c.professor_name
";

// ORDER BY 句 (検索結果も口コミ件数が多い順にソート)
// 評価は総合評価を基準とする
$orderByClause = "
    ORDER BY review_count DESC, avg_overall_rating DESC
";

// 最終的な検索結果クエリ
$searchQuery = $baseQuery . $whereClause . $groupByClause . $orderByClause . " LIMIT :limit OFFSET :offset";

// 総件数カウントクエリ
$countQuery = "
    SELECT COUNT(*) AS total
    FROM (" . $baseQuery . $whereClause . $groupByClause . ") AS T
";

// 4. クエリの実行
$stmt = $pdo->prepare($searchQuery);
if (!empty($keyword)) {
    $stmt->bindParam(':keyword', $searchKeyword, PDO::PARAM_STR);
}
$stmt->bindParam(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$results = $stmt->fetchAll();

$countStmt = $pdo->prepare($countQuery);
if (!empty($keyword)) {
    $countStmt->bindParam(':keyword', $searchKeyword, PDO::PARAM_STR);
}
$countStmt->execute();
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
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>授業検索結果</title>
    <style>
        /* CSS スタイル (変更なし) */
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            padding: 20px;
            background-color: #e9ecef;
            color: #333;
        }
        .container { 
            max-width: 1000px; 
            margin: 20px auto; 
            background: white; 
            padding: 30px; 
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
        }
        h1 { 
            color: #28a745;
            border-bottom: 3px solid #28a745; 
            padding-bottom: 15px; 
            margin-bottom: 25px;
            font-size: 1.8em;
        }
        .search-form { 
            display: flex; 
            margin-bottom: 20px; 
        }
        .search-form input[type="text"] {
            flex-grow: 1;
            padding: 10px;
            border: 2px solid #ccc;
            border-radius: 6px 0 0 6px;
            font-size: 1em;
        }
        .search-form button {
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 0 6px 6px 0;
            cursor: pointer;
            font-size: 1em;
        }
        .search-form button:hover {
            background-color: #1e7e34;
        }
        
        .result-info { 
            margin-bottom: 20px; 
            font-weight: bold; 
            color: #333;
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
            border: 1px solid #28a745;
            text-decoration: none; 
            color: #28a745; 
            border-radius: 6px; 
            display: inline-block;
        }
        .pagination span.current { 
            background: #28a745; 
            color: white; 
            border-color: #28a745;
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
        <h1>🔍 授業検索</h1>
        
        <form action="search.php" method="GET" class="search-form">
            <input type="text" name="q" placeholder="授業名または先生名を入力..." value="<?= htmlspecialchars($keyword) ?>">
            <button type="submit">検索</button>
        </form>

        <p class="result-info">
            <?php if (!empty($keyword)): ?>
                検索キーワード: **<?= htmlspecialchars($keyword) ?>** の結果 (全 **<?= number_format($totalCount) ?>** 件)
            <?php else: ?>
                全授業一覧を表示しています (全 **<?= number_format($totalCount) ?>** 件)
            <?php endif; ?>
        </p>

        <table class="result-table">
            <thead>
                <tr>
                    <th style="width: 30%;">授業名</th>
                    <th style="width: 20%;">教授名</th>
                    <th style="width: 25%;">平均総合評価</th>
                    <th style="width: 15%;">平均楽単度</th>
                    <th style="width: 10%;">口コミ数</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($results)): ?>
                    <tr><td colspan="5" style="text-align: center;">検索条件に一致する授業はありませんでした。</td></tr>
                <?php else: ?>
                    <?php foreach ($results as $course): ?>
                    <tr>
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
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php 
            $queryString = http_build_query(array_filter(['q' => $keyword])); 
            
            if ($totalPages > 1) {
                // 前へ
                if ($currentPage > 1) {
                    echo '<a href="search.php?' . $queryString . '&page=' . ($currentPage - 1) . '">« 前へ</a>';
                } else {
                    echo '<span>« 前へ</span>';
                }

                // ページ番号
                for ($i = 1; $i <= $totalPages; $i++) {
                    if ($i == $currentPage) {
                        echo '<span class="current">' . $i . '</span>';
                    } else {
                        echo '<a href="search.php?' . $queryString . '&page=' . $i . '">' . $i . '</a>';
                    }
                }

                // 次へ
                if ($currentPage < $totalPages) {
                    echo '<a href="search.php?' . $queryString . '&page=' . ($currentPage + 1) . '">次へ »</a>';
                } else {
                    echo '<span>次へ »</span>';
                }
            }
            ?>
        </div>
    </div>
</body>
</html>