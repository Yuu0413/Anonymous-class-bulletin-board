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

/* ---------------------------------------------------------
 * 3. 動作確認用のテストデータ
 *
 *   前提:
 *     courses テーブルに course_id = 1 の授業が登録されていること
 *   用途:
 *     class_detail.php で口コミ表示や平均値計算が正しく動くか確認する
 * ------------------------------------------------------*/
INSERT INTO reviews (course_id, rating, comment) VALUES
(1, 5, 'とても分かりやすい授業でした。単位も取りやすいです！'),
(1, 3, '課題が少し多いですが、勉強になります。'),
(1, 4, '先生が面白いです。出席は重要。');