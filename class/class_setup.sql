/* 既存の courses テーブル作成（そのまま） */
CREATE TABLE courses (
    course_id SERIAL PRIMARY KEY,
    course_name VARCHAR(100) NOT NULL,
    professor_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


/* ▼▼▼ ここから下に追記 ▼▼▼ */

/* もし既にテーブルがあったら削除（開発用便利コマンド） */
DROP TABLE IF EXISTS reviews;

/* reviews テーブル作成 */
CREATE TABLE reviews (
    review_id SERIAL PRIMARY KEY,
    course_id INTEGER NOT NULL,
    user_id INTEGER,
    rating INTEGER NOT NULL,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

/* テストデータの挿入 */
INSERT INTO reviews (course_id, rating, comment) VALUES
(1, 5, 'とても分かりやすい授業でした。単位も取りやすいです！'),
(1, 3, '課題が少し多いですが、勉強になります。'),
(1, 4, '先生が面白いです。出席は重要。');