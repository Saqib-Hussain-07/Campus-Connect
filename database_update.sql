-- ============================================================
--  CampusConnect — database_update.sql
--  Run AFTER the original database.sql
--  mysql -u root -p campusconnect < database_update.sql
-- ============================================================

USE campusconnect;

-- ── Projects Showcase ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS projects (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    title         VARCHAR(200)  NOT NULL,
    description   TEXT,
    tech_stack    VARCHAR(300),          -- comma-separated
    github_url    VARCHAR(300),
    live_url      VARCHAR(300),
    demo_img      VARCHAR(255)  DEFAULT '',
    category      ENUM('web','mobile','ml','hardware','research','other') DEFAULT 'other',
    status        ENUM('in_progress','completed','looking_for_team') DEFAULT 'in_progress',
    team_size     TINYINT UNSIGNED DEFAULT 1,
    likes         INT UNSIGNED DEFAULT 0,
    views         INT UNSIGNED DEFAULT 0,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── Project Likes ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS project_likes (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id    INT NOT NULL,
    liked_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY ux_like (project_id, user_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE
);

-- ── Project Comments ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS project_comments (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id    INT NOT NULL,
    body       TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE
);

-- ── Project Team Requests ────────────────────────────────────
CREATE TABLE IF NOT EXISTS project_requests (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id    INT NOT NULL,
    message    TEXT,
    status     ENUM('pending','accepted','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY ux_req (project_id, user_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE
);

-- ── Events / Hackathons ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS events (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,                    -- organiser
    title         VARCHAR(200) NOT NULL,
    description   TEXT,
    category      ENUM('hackathon','seminar','workshop','cultural','sports','other') DEFAULT 'other',
    venue         VARCHAR(200),
    event_date    DATETIME NOT NULL,
    registration_deadline DATETIME,
    max_attendees INT UNSIGNED DEFAULT 0,          -- 0 = unlimited
    banner_seed   VARCHAR(60) DEFAULT 'event1',
    is_online     TINYINT(1) DEFAULT 0,
    registration_link VARCHAR(300),
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── Event RSVPs ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS event_rsvps (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    event_id   INT NOT NULL,
    user_id    INT NOT NULL,
    status     ENUM('going','interested','not_going') DEFAULT 'going',
    rsvped_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY ux_rsvp (event_id, user_id),
    FOREIGN KEY (event_id) REFERENCES events(id)  ON DELETE CASCADE,
    FOREIGN KEY (user_id)  REFERENCES users(id)   ON DELETE CASCADE
);

-- ── Notice Board ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notices (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    title      VARCHAR(200) NOT NULL,
    body       TEXT NOT NULL,
    category   ENUM('opportunity','academic','internship','placement','general','urgent') DEFAULT 'general',
    tags       VARCHAR(300),                       -- comma-separated
    expires_at DATETIME,
    is_pinned  TINYINT(1) DEFAULT 0,
    views      INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── Skill Endorsements ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS endorsements (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    endorsed_id  INT NOT NULL,               -- who is being endorsed
    endorser_id  INT NOT NULL,               -- who is endorsing
    skill        VARCHAR(100) NOT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY ux_endorse (endorsed_id, endorser_id, skill),
    FOREIGN KEY (endorsed_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (endorser_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── Study Resources ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS resources (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    title       VARCHAR(200) NOT NULL,
    description TEXT,
    subject     VARCHAR(150),
    type        ENUM('notes','video','book','article','tool','other') DEFAULT 'other',
    url         VARCHAR(500),
    department  VARCHAR(100),
    semester    TINYINT UNSIGNED,
    likes       INT UNSIGNED DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── Resource Likes ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS resource_likes (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    resource_id INT NOT NULL,
    user_id     INT NOT NULL,
    liked_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY ux_rlike (resource_id, user_id),
    FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)     REFERENCES users(id)     ON DELETE CASCADE
);

-- ── Activity Feed ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS activity_feed (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    type        ENUM('project_added','event_created','notice_posted','resource_shared',
                     'connected','joined_group','endorsed') NOT NULL,
    ref_id      INT,                              -- ID of the related item
    ref_title   VARCHAR(200),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── Notifications ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notifications (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,                     -- recipient
    actor_id    INT,                              -- who triggered it
    type        ENUM('connection_request','connection_accepted',
                     'project_like','project_comment','project_join_request',
                     'endorsement','event_reminder','notice_new',
                     'message_new') NOT NULL,
    ref_id      INT,
    message     VARCHAR(300),
    is_read     TINYINT(1) DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)  REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================================
--  Seed: Projects
-- ============================================================
INSERT INTO projects (user_id,title,description,tech_stack,github_url,live_url,category,status,team_size,likes,views) VALUES
(1,'SmartCampus AI Assistant',
 'An AI-powered chatbot for answering campus queries — hostel rules, timetable, events. Built with GPT-4 API and React.',
 'Python,FastAPI,React,PostgreSQL,GPT-4',
 'https://github.com/demo/smartcampus','https://smartcampus.demo.io','ml','completed',3,24,180),

(1,'Campus Lost & Found App',
 'Mobile app to report and reclaim lost items on campus. Features image uploads, geo-tagging and real-time notifications.',
 'Flutter,Firebase,Dart,Google Maps API',
 'https://github.com/demo/lostandfound','','mobile','in_progress',2,11,94),

(2,'Automated Greenhouse Robot',
 'Arduino-based robot that monitors soil moisture, temperature and auto-waters plants. Presented at National Robotics Expo.',
 'Arduino,C++,Sensors,3D Printing,SolidWorks',
 'https://github.com/demo/greenhouse','','hardware','completed',2,18,142),

(3,'Campus Budget Tracker',
 'Web app for college societies to track budgets, expenses and generate reports. Used by 5 societies at SPJIMR.',
 'PHP,MySQL,Bootstrap,Chart.js',
 'https://github.com/demo/budgettracker','https://budget.demo.io','web','completed',1,9,67),

(4,'University Design System',
 'A comprehensive UI kit and design system for university web apps — components, icons, typography guidelines.',
 'Figma,CSS,JavaScript,Storybook',
 'https://github.com/demo/unidesign','https://figma.com/demo','web','completed',1,32,210),

(5,'CloudDeploy CLI',
 'Open-source CLI tool to deploy student projects to AWS with a single command. 120+ GitHub stars.',
 'Java,AWS SDK,Spring Boot,Docker,Terraform',
 'https://github.com/demo/clouddeploy','','ml','completed',2,45,380),

(6,'Drone Swarm Controller',
 'Research project on coordinating multiple drones using a centralised controller. Paper submitted to IEEE.',
 'Python,ROS,C++,Arduino,Embedded C',
 'https://github.com/demo/droneswarm','','hardware','in_progress',3,28,195);

-- ── Seed: Events ─────────────────────────────────────────────
INSERT INTO events (user_id,title,description,category,venue,event_date,registration_deadline,max_attendees,banner_seed,is_online) VALUES
(1,'HackFest 2025 — 36-Hour Hackathon',
 'Annual inter-college hackathon. Build anything in 36 hours. Prizes worth ₹2,00,000. Teams of 2-4.',
 'hackathon','LH-101 Auditorium, IIT Mumbai',
 '2025-11-15 09:00:00','2025-11-10 23:59:00',200,'ev1',0),

(2,'Robotics & Automation Workshop',
 'Hands-on workshop on Arduino, servo motors, and sensor integration. Beginner-friendly. Kit included.',
 'workshop','Mechanical Lab, Block C',
 '2025-10-20 10:00:00','2025-10-18 23:59:00',40,'ev2',0),

(3,'Startup Pitch Day',
 'Present your startup idea to a panel of investors and industry mentors. Cash prizes and incubation support.',
 'seminar','Auditorium Hall, SPJIMR',
 '2025-10-28 11:00:00','2025-10-25 23:59:00',100,'ev3',0),

(5,'DevOps & Cloud Computing Bootcamp',
 'Two-day intensive bootcamp on Docker, Kubernetes, CI/CD pipelines and AWS. Certificate provided.',
 'workshop','Online (Zoom)',
 '2025-11-05 09:00:00','2025-11-03 23:59:00',500,'ev4',1),

(4,'UX Design Sprint',
 'A full-day design sprint to solve a real campus problem. Figma, user research, prototyping — all in one day.',
 'workshop','Design Studio, NIFT',
 '2025-10-25 09:00:00','2025-10-22 23:59:00',30,'ev5',0),

(1,'Inter-College Tech Olympiad',
 'Competitive programming + tech quiz. Individual event. Rank in top 10 to win internship referrals.',
 'hackathon','Online',
 '2025-11-22 10:00:00','2025-11-20 23:59:00',0,'ev6',1);

-- ── Seed: RSVPs ──────────────────────────────────────────────
INSERT INTO event_rsvps (event_id,user_id,status) VALUES
(1,1,'going'),(1,2,'going'),(1,3,'interested'),(1,4,'going'),(1,5,'going'),
(2,2,'going'),(2,6,'going'),(2,1,'interested'),
(3,3,'going'),(3,1,'going'),(3,5,'going'),
(4,5,'going'),(4,1,'going'),(4,6,'going'),
(5,4,'going'),(5,1,'interested'),
(6,1,'going'),(6,5,'going'),(6,2,'going');

-- ── Seed: Notices ────────────────────────────────────────────
INSERT INTO notices (user_id,title,body,category,tags,is_pinned) VALUES
(1,'Summer Internship at TechCorp — Apply Now',
 'TechCorp is hiring summer interns for software development. 3-month paid internship. Min CGPA 7.5. Apply by Oct 30.',
 'internship','tech,internship,software,paid',1),
(5,'AWS Free Tier Study Resources — Shared Drive',
 'I have compiled all AWS certification study material, mock tests and cheatsheets in a Google Drive folder. DM me for access.',
 'academic','aws,cloud,resources,free',0),
(3,'Lost: Black Laptop Bag near Library',
 'Lost my black laptop bag (Dell XPS inside) near the central library on Oct 12. Please contact if found. Reward offered.',
 'general','lost,laptop,library',0),
(2,'Vacancy: Robotics Club Core Team',
 'Robotics Club is looking for 2 new core members for AY 2025-26. Must have basic electronics knowledge. Interview on Oct 22.',
 'opportunity','robotics,club,vacancy,interview',0),
(4,'Free Figma Pro Account for Students',
 'Figma is offering free Pro accounts to verified students. Use your .edu email. Verified it works — grab yours!',
 'opportunity','figma,design,free,tool',1),
(6,'IEEE Paper Call for Submissions — Deadline Nov 1',
 'IEEE is accepting undergraduate research papers for the 2025 conference. Great for your resume. Guidelines in comments.',
 'academic','ieee,research,paper,conference',0);

-- ── Seed: Endorsements ───────────────────────────────────────
INSERT INTO endorsements (endorsed_id,endorser_id,skill) VALUES
(1,2,'Python'),(1,3,'React'),(1,4,'Machine Learning'),(1,5,'Node.js'),
(2,1,'CAD/SolidWorks'),(2,6,'Robotics'),(2,3,'MATLAB'),
(3,1,'Marketing'),(3,5,'Finance'),(3,4,'Excel'),
(4,1,'Figma'),(4,3,'UI Design'),(4,5,'Prototyping'),
(5,1,'Java'),(5,2,'AWS'),(5,6,'Docker'),
(6,2,'Robotics'),(6,1,'Arduino'),(6,5,'Embedded C');

-- ── Seed: Resources ──────────────────────────────────────────
INSERT INTO resources (user_id,title,description,subject,type,url,department,semester,likes) VALUES
(1,'Complete DBMS Notes — IIT Pattern',
 'Comprehensive notes covering ER diagrams, normalization (1NF-BCNF), SQL, transactions. 80 pages.',
 'Database Management Systems','notes','https://drive.google.com/demo1','Computer Science',5,34),
(5,'System Design Primer (GitHub)',
 'The best free resource to learn system design for interviews. Covers scalability, databases, caching.',
 'System Design','article','https://github.com/donnemartin/system-design-primer','Computer Science',8,87),
(2,'Engineering Thermodynamics — Cengel',
 'Full PDF of Cengel & Boles Thermodynamics textbook with solved problems.',
 'Thermodynamics','book','https://drive.google.com/demo2','Mechanical Engineering',3,21),
(3,'Financial Accounting Crash Course',
 'YouTube playlist — 15 videos covering all topics for CA Foundation and BBA exams.',
 'Financial Accounting','video','https://youtube.com/playlist?list=demo','Business Administration',3,15),
(4,'Figma Variables & Auto Layout Tutorial',
 'Step-by-step tutorial on using variables, auto layout and component properties in Figma 2024.',
 'UI/UX Design','video','https://youtube.com/demo','UX Design',3,28),
(1,'Machine Learning A-Z (Free Udemy Coupons)',
 'Coupon codes for top ML courses on Udemy — valid till Nov 30. Updated weekly.',
 'Machine Learning','tool','https://udemy.com/demo','Computer Science',5,42),
(6,'Embedded Systems Handbook',
 'Complete guide to microcontrollers, interrupts, RTOS, and communication protocols.',
 'Embedded Systems','notes','https://drive.google.com/demo3','Mechanical Engineering',5,18);

-- ── Seed: Activity ───────────────────────────────────────────
INSERT INTO activity_feed (user_id,type,ref_id,ref_title) VALUES
(1,'project_added',1,'SmartCampus AI Assistant'),
(4,'project_added',4,'University Design System'),
(5,'project_added',6,'CloudDeploy CLI'),
(1,'event_created',1,'HackFest 2025'),
(3,'notice_posted',1,'Summer Internship at TechCorp'),
(1,'resource_shared',1,'Complete DBMS Notes'),
(5,'resource_shared',2,'System Design Primer');

-- ── Project likes from seed ──────────────────────────────────
INSERT INTO project_likes (project_id,user_id) VALUES
(1,2),(1,3),(1,4),(1,5),(1,6),
(4,1),(4,2),(4,3),
(5,1),(5,2),(5,3),(5,4),(5,6),
(6,1),(6,2),(6,3);
