CREATE DATABASE IF NOT EXISTS smartlearner;
USE smartlearner;

-- ========================
-- 1. USERS
-- ========================
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    gender ENUM('Male','Female','Other'),
    profile_img VARCHAR(255) DEFAULT 'images/user.png',
    role ENUM('Teacher','Student') NOT NULL DEFAULT 'Student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ========================
-- 2. CLASSROOMS
-- ========================
CREATE TABLE classrooms (
    class_id INT AUTO_INCREMENT PRIMARY KEY,

    course_name VARCHAR(150) NOT NULL,
    section VARCHAR(50),
    class_code VARCHAR(10) UNIQUE NOT NULL,

    -- Main schedule (Day 1)
    day1 VARCHAR(15),              
    day1_start TIME,                
    day1_end TIME,               
    day1_room VARCHAR(50),

    -- Secondary schedule (Day 2)
    day2 VARCHAR(15),               
    day2_start TIME,
    day2_end TIME,
    day2_room VARCHAR(50),

    -- Lab schedule (optional)
    lab_day VARCHAR(15) NULL,      
    lab_start TIME NULL,
    lab_end TIME NULL,
    lab_room VARCHAR(50) NULL,

    -- Ownership and management
    created_by INT NOT NULL,       
    status ENUM('active','archived') NOT NULL DEFAULT 'active', 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ========================
-- 3. CLASSROOM MEMBERS
-- ========================
CREATE TABLE IF NOT EXISTS classroom_members (
    member_id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT,
    user_id INT,
    role ENUM('Teacher','Teaching Assistant','Student') NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classrooms(class_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ========================
-- 4. POSTS (Classroom Stream)
-- ========================
CREATE TABLE IF NOT EXISTS posts (
    post_id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT,
    user_id INT,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classrooms(class_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Support multiple files for posts
CREATE TABLE IF NOT EXISTS post_files (
    file_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE CASCADE
);

-- ========================
-- 5. COMMENTS on POSTS
-- ========================
CREATE TABLE IF NOT EXISTS comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT,
    user_id INT,
    comment_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ========================
-- 6. MATERIALS
-- ========================
CREATE TABLE IF NOT EXISTS materials (
    material_id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT,
    user_id INT,
    title VARCHAR(200) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classrooms(class_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Multiple files for materials
CREATE TABLE IF NOT EXISTS material_files (
    material_file_id INT AUTO_INCREMENT PRIMARY KEY,
    material_id INT,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (material_id) REFERENCES materials(material_id) ON DELETE CASCADE
);

-- ========================
-- 7. ASSIGNMENTS
-- ========================
CREATE TABLE IF NOT EXISTS assignments (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT,
    user_id INT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    due_date DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classrooms(class_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Multiple instruction files per assignment
CREATE TABLE IF NOT EXISTS assignment_files (
    assign_file_id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assignment_id) REFERENCES assignments(assignment_id) ON DELETE CASCADE
);

-- ========================
-- 8. ASSIGNMENT SUBMISSIONS (students)
-- ========================
CREATE TABLE IF NOT EXISTS assignment_submissions (
    submission_id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT,
    user_id INT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    marks INT DEFAULT 0,
    feedback TEXT,
    FOREIGN KEY (assignment_id) REFERENCES assignments(assignment_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Multiple files per submission
CREATE TABLE IF NOT EXISTS assignment_submission_files (
    sub_file_id INT AUTO_INCREMENT PRIMARY KEY,
    submission_id INT,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (submission_id) REFERENCES assignment_submissions(submission_id) ON DELETE CASCADE
);

-- ========================
-- 9. QUIZZES
-- ========================
CREATE TABLE IF NOT EXISTS quizzes (
    quiz_id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT,
    title VARCHAR(200) NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    created_by INT,
    evaluated TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    original_start_time DATETIME NULL,
    original_end_time DATETIME NULL,
    FOREIGN KEY (class_id) REFERENCES classrooms(class_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);


-- QUIZ QUESTIONS
CREATE TABLE IF NOT EXISTS quiz_questions (
    question_id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT,
    question_text TEXT NOT NULL,
    correct_option TINYINT,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(quiz_id) ON DELETE CASCADE
);

-- QUIZ OPTIONS
CREATE TABLE IF NOT EXISTS quiz_options (
    option_id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT,
    option_text VARCHAR(255) NOT NULL,
    option_number TINYINT NOT NULL,
    FOREIGN KEY (question_id) REFERENCES quiz_questions(question_id) ON DELETE CASCADE
);

-- QUIZ RESPONSES
CREATE TABLE IF NOT EXISTS quiz_responses (
    response_id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT,
    question_id INT,
    user_id INT,
    selected_option TINYINT,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(quiz_id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES quiz_questions(question_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- QUIZ RESULTS
CREATE TABLE IF NOT EXISTS quiz_results (
    result_id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT,
    user_id INT,
    marks INT DEFAULT 0,
    evaluated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(quiz_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- ========================
-- 10. NOTIFICATIONS
-- ========================
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    message VARCHAR(255) NOT NULL,
    link VARCHAR(255),
    status ENUM('unread','read') DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ========================
-- 11. SCHEDULES
-- ========================
CREATE TABLE IF NOT EXISTS schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT,
    user_id INT,
    day ENUM('Mon','Tue','Wed','Thu','Fri','Sat','Sun'),
    time VARCHAR(50),
    room VARCHAR(50),
    FOREIGN KEY (class_id) REFERENCES classrooms(class_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

