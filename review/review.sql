CREATE TABLE reviews (
    review_id   SERIAL PRIMARY KEY,  -- 口コミID（自動採番）

    course_id   INTEGER NOT NULL,    -- どの授業への口コミか（courses.course_id）
    user_id     INTEGER,             -- 投稿者ユーザーID（匿名運用のため必須ではない）

    rating      INTEGER NOT NULL,    -- 評価（1〜5 を想定：class_detail.php で使用）
    comment     TEXT,                -- 口コミ本文

    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                                     -- 投稿日時（自動で現在時刻が入る）
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