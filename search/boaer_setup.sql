-------------------------------------------------
-- 1. users, courses テーブル (変更なし)
--------------------------------------------------
CREATE TABLE users (
    user_id SERIAL PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE courses (
    course_id SERIAL PRIMARY KEY,
    course_name VARCHAR(100) NOT NULL,
    professor_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);


--------------------------------------------------
-- 2. reviews テーブル (評価項目と投稿日時を更新)
--------------------------------------------------
CREATE TABLE reviews (
    review_id SERIAL PRIMARY KEY,

    course_id INTEGER NOT NULL,
    user_id INTEGER,

    overall_rating SMALLINT NOT NULL CHECK (overall_rating >= 1 AND overall_rating <= 5), -- 総合評価 (1～5)
    easiness_rating SMALLINT NOT NULL CHECK (easiness_rating >= 1 AND easiness_rating <= 5),   -- 楽単度 (1～5)

    review_text TEXT NOT NULL,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP, -- 投稿日時

    FOREIGN KEY (course_id)
        REFERENCES courses(course_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)
        REFERENCES users(user_id) ON DELETE SET NULL
);


-- ========================================================================
-- テストデータ挿入 (評価項目と投稿日時を反映)
-- ========================================================================

-- users
INSERT INTO users (email, password_hash) VALUES
('alice@university.ac.jp', 'alice_hashed_pw_123'),
('bob@university.ac.jp', 'bob_hashed_pw_456');

-- courses
INSERT INTO courses (course_name, professor_name) VALUES
('Webアプリケーション開発論', '山田 太郎'),       -- ID: 1
('データ構造とアルゴリズム', '佐藤 次郎'),         -- ID: 2
('クリエイティブライティング', '田中 花子'),      -- ID: 3
('国際経済学入門', '伊藤 健');                    -- ID: 4 (口コミなし)

-- reviews (overall_rating, easiness_rating を追加し、created_at をシミュレーション)
INSERT INTO reviews (course_id, user_id, overall_rating, easiness_rating, review_text, created_at) VALUES
(1, 1, 5, 4, 'PHPとPostgreSQLを使った開発実践ができてとても良かった。', '2025-10-01 10:00:00'),
(1, 2, 4, 5, '課題は多いが、実力がつく。先生の説明は丁寧。', '2025-10-02 11:30:00'),
(1, NULL, 5, 3, '（匿名投稿）卒業制作に役立つ内容でした。', '2025-11-20 15:00:00'),
(2, 1, 3, 2, '内容は高度だが、基礎的な理解には役立った。', '2025-09-15 09:00:00'),
(2, NULL, 4, 3, '難しすぎてついていけなかったが、先生のサポートは良かった。', '2025-11-21 15:00:00'), -- 最も新しいレビュー
(3, 2, 5, 5, '文章の構成力が飛躍的に上がった。先生のフィードバックが丁寧で素晴らしい。', '2025-10-25 14:00:00'),
(3, NULL, 5, 5, '星5つ。最高の授業でした。', '2025-11-21 10:00:00');