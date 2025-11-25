/* courses（授業）テーブルを作成する命令 */
CREATE TABLE courses (
    course_id SERIAL PRIMARY KEY,        /* ID: 自動で1,2,3...と番号が振られる */
    course_name VARCHAR(100) NOT NULL,   /* 授業名: 100文字まで、空っぽは禁止 */
    professor_name VARCHAR(100) NOT NULL,/* 教授名: 100文字まで、空っぽは禁止 */
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP /* 登録日時: 自動で今の時間が入る */
);

/*=== 説明 ===
 SERIAL: PostgreSQL特有の書き方で、「データを追加したら勝手に連番にするよ」という意味です。
PRIMARY KEY: これが「背番号（主キー）」だよ、という印です。
この作業は「箱の組み立て」なので、最初の一回だけでOKです。 */