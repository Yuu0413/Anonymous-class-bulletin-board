<?php
// 1. 設定
$root_path = './'; // home.php は一番上の階層なので './'
$page_title = 'メインメニュー';
$page_css = 'home.css'; // 専用CSSを指定

// 2. ヘッダー読み込み
require_once 'includes/header.php';
?>

<div class="home-container">
    <h2>ようこそ、<?php echo htmlspecialchars($_SESSION['user_name']); ?> さん</h2>
    <p class="subtitle">利用したい機能を選択してください。</p>

    <div class="menu-grid">

        <div class="menu-card">
            <h3>📂 授業・クラス</h3>
            <div class="card-links">
                <a href="class/class_register.php">クラス登録</a>
                <a href="class/class_detail.php">クラス詳細</a>
            </div>
        </div>

        <div class="menu-card">
            <h3>📝 レビュー</h3>
            <div class="card-links">
                <a href="review/review_post.php">レビュー投稿</a>
            </div>
        </div>

        <div class="menu-card">
            <h3>🔍 検索・ランキング</h3>
            <div class="card-links">
                <a href="search/board_search.php">掲示板検索</a>
                <a href="search/ranking.php">ランキング</a>
            </div>
        </div>

    </div>
</div>

<?php
// 3. フッター読み込み
require_once 'includes/footer.php';
?>