-- ============================================================
--  CampusConnect Database Schema
-- ============================================================
Create campusconnect if not exit;
USE campusconnect;


-- ── Users ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)        NOT NULL,
    email       VARCHAR(150)        NOT NULL UNIQUE,
    password    VARCHAR(255)        NOT NULL,
    department  VARCHAR(100),
    semester    TINYINT UNSIGNED,
    university  VARCHAR(150),
    skills      TEXT,                          -- comma-separated
    bio         TEXT,
    avatar      VARCHAR(255)        DEFAULT 'default.jpg',
    is_verified TINYINT(1)          DEFAULT 0,
    is_online   TINYINT(1)          DEFAULT 0,
    created_at  TIMESTAMP           DEFAULT CURRENT_TIMESTAMP
);

-- ── Study Groups ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS groups_list (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150)        NOT NULL,
    description TEXT,
    type        ENUM('study','project','forum') DEFAULT 'study',
    status      ENUM('active','recruiting','open') DEFAULT 'active',
    created_by  INT,
    banner_img  VARCHAR(255)        DEFAULT '',
    created_at  TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ── Group Members ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS group_members (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    user_id  INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY ux_member (group_id, user_id),
    FOREIGN KEY (group_id) REFERENCES groups_list(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)  REFERENCES users(id)       ON DELETE CASCADE
);

-- ── Connections ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS connections (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    from_user   INT NOT NULL,
    to_user     INT NOT NULL,
    status      ENUM('pending','accepted','rejected') DEFAULT 'pending',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY ux_conn (from_user, to_user),
    FOREIGN KEY (from_user) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (to_user)   REFERENCES users(id) ON DELETE CASCADE
);

-- ── Messages ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS messages (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    from_user  INT NOT NULL,
    to_user    INT NOT NULL,
    body       TEXT NOT NULL,
    is_read    TINYINT(1) DEFAULT 0,
    sent_at    TIMESTAMP  DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (from_user) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (to_user)   REFERENCES users(id) ON DELETE CASCADE
);

-- ── Newsletter ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS newsletter (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(150) NOT NULL UNIQUE,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── Contact messages ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS contact_messages (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100),
    email      VARCHAR(150),
    subject    VARCHAR(200),
    message    TEXT,
    sent_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
--  Seed Data
-- ============================================================

-- Demo users (password = "password123" hashed with bcrypt)
INSERT INTO users (name, email, password, department, semester, university, skills, bio, is_verified, is_online) VALUES
('Priya Sharma',  'priya@iitmumbai.edu',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Computer Science', 6, 'IIT Mumbai',  'Python,Machine Learning,React,Node.js', 'Passionate about AI and web development. Looking for project partners!', 1, 1),
('Arjun Patel',   'arjun@nitsurat.edu',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mechanical Engineering', 4, 'NIT Surat', 'CAD/SolidWorks,Thermodynamics,MATLAB,AutoCAD', 'Mechanical enthusiast. Into robotics and automation.', 1, 1),
('Sneha Reddy',   'sneha@spjimr.edu',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Business Administration', 5, 'SPJIMR',    'Marketing,Finance,Excel,Power BI', 'BBA student with a passion for startups and finance.', 1, 0),
('Vikram Das',    'vikram@nift.edu',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'UX Design', 3, 'NIFT Delhi',               'Figma,UI Design,Prototyping,Illustrator', 'UX designer focused on accessibility and clean interfaces.', 1, 0),
('Meera Joshi',   'meera@bits.edu',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Computer Science', 8, 'BITS Pilani', 'Java,Spring Boot,AWS,Docker', 'Final year CS student. Backend dev, cloud enthusiast.', 1, 1),
('Karthik Nair',  'karthik@vit.edu',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mechanical Engineering', 6, 'VIT Vellore','Robotics,3D Printing,Arduino,Embedded C', 'Robotics club lead. Building autonomous drones.', 1, 1);

-- Demo groups
INSERT INTO groups_list (name, description, type, status, created_by) VALUES
('DBMS Study Circle',  'Weekly sessions on SQL, normalization, ER diagrams, and exam prep.',   'study',   'active',     1),
('AI Research Team',   'Building an AI campus assistant. Seeking ML, NLP, full-stack devs.',   'project',  'recruiting', 1),
('Tech Talk Forum',    'Discuss tech trends, career advice, interview prep, industry insights.','forum',   'open',       2),
('Math Masters',       'Calculus, linear algebra, probability — collaborative problem-solving.','study',   'active',     3),
('App Dev Hub',        'Building a campus food ordering app. Needs Flutter, UI, backend devs.', 'project', 'recruiting', 4),
('Career Connect',     'Resume reviews, mock interviews, internship referrals and resources.',  'forum',   'open',       5);

-- Add all demo users to some groups
INSERT INTO group_members (group_id, user_id) VALUES
(1,1),(1,2),(1,3),(1,4),
(2,1),(2,5),(2,6),
(3,2),(3,3),(3,4),(3,5),(3,6),
(4,3),(4,4),
(5,4),(5,5),
(6,1),(6,2),(6,3),(6,4),(6,5),(6,6);
