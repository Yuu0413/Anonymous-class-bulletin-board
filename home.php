<?php
// ▼▼▼ エラーを表示させる魔法のコード ▼▼▼
ini_set('display_errors', 1);
error_reporting(E_ALL);
// ▲▲▲▲▲▲

// 1. 設定
$root_path = './'; 
$page_title = 'メインメニュー';
$page_css = 'home.css'; 

// 2. ヘッダー読み込み
require_once 'includes/header.php';
?>

<!-- メインコンテンツ -->
<div class="home-container">
    <h2>ようこそ、<?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'ゲスト'; ?> さん</h2>
    <p class="subtitle">利用したい機能を選択してください。</p>

    <div class="menu-grid">
        
        <!-- 授業管理 -->
        <div class="menu-card">
            <h3>📂 授業登録</h3>
            <div class="card-links">
                <a href="class/class_register.php">授業登録</a>
            </div>
        </div>

        <!-- レビュー -->
        <div class="menu-card">
            <h3>📝 レビュー</h3>
            <div class="card-links">
                <a href="review/review_post.php">レビュー投稿</a>
            </div>
        </div>

        <!-- 検索・ランキング -->
        <div class="menu-card">
            <h3>🔍 検索・ランキング</h3>

            <div class="card-links">
                <a href="search/board_search.php">授業一覧</a>
                <a href="search/ranking.php">ランキング</a>
            </div>
        </div>

    </div>
</div>

<?php
// 3. フッター読み込み
require_once 'includes/footer.php';
?>