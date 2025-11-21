CREATE TABLE courses (
    course_id SERIAL PRIMARY KEY,
    course_name VARCHAR(100) NOT NULL,
    professor_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);


--------------------------------------------------
-- 2. reviews テーブル (評価項目と投稿日時を更新)
--------------------------------------------------
-- 既存の reviews テーブルがある場合は、このコマンドで削除してください
-- DROP TABLE reviews;

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