# 🎓 CampusConnect — University Student Connection Portal

<div align="center">

![CampusConnect Banner](https://picsum.photos/seed/campusbanner/1200/300)

**A secure, feature-rich student networking platform built with PHP, MySQL, Bootstrap 5, and vanilla JavaScript.**

[![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com)
[![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](https://developer.mozilla.org/en-US/docs/Web/JavaScript)
[![XAMPP](https://img.shields.io/badge/XAMPP-Compatible-FB7A24?style=for-the-badge&logo=apache&logoColor=white)](https://apachefriends.org)
[![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](LICENSE)

[Features](#-features) • [Screenshots](#-screenshots) • [Installation](#-installation) • [Database](#-database-schema) • [File Structure](#-file-structure) • [Usage](#-usage) • [Contributing](#-contributing)

</div>

---

## 📖 About

**CampusConnect** is a full-stack student networking portal that allows university students to discover peers, showcase projects, collaborate on groups, attend events, share study resources, and send direct messages — all within a verified, secure campus environment.

Built as a college project using a classic **LAMP/XAMPP** stack:

| Layer      | Technology                          |
|------------|-------------------------------------|
| Frontend   | HTML5, CSS3, Bootstrap 5.3, JavaScript (ES6+) |
| Backend    | PHP 8.0+                            |
| Database   | MySQL 8.0+                          |
| Server     | Apache (XAMPP / WAMP / MAMP / LAMP) |
| IDE        | VS Code / Sublime Text              |

---

## ✨ Features

### 👤 User System
- **Register & Login** with university email verification (bcrypt password hashing)
- **CSRF protection** on every form submission
- **Edit profile** — name, department, semester, university, skills, bio
- **Change password** with current-password verification
- **Delete account** with cascade cleanup
- **Online/offline status** indicator

### 🔍 Student Discovery
- Browse all verified students with **live search**
- Filter by **department, semester, and skills**
- **Pagination** on large result sets
- **View public profiles** with bio, skills, projects, groups, and endorsements

### 🤝 Connections & Messaging
- Send, accept, and reject **connection requests**
- **Direct messaging** — real-time chat thread per conversation
- Unread **message badge** in navbar
- Enter to send, Shift+Enter for new line, auto-scroll to latest message

### 💡 Projects Showcase
- Post projects with **title, description, tech stack, GitHub URL, and live demo link**
- Filter by **category** (Web, Mobile, AI/ML, Hardware, Research) and **status**
- Sort by **newest, most liked, or most viewed**
- **Like** projects (toggle), leave **comments**, view count tracking
- Mark projects as **"Looking for Team"** — students can send join requests
- **Edit and delete** your own projects

### 📅 Events & Hackathons
- Browse **upcoming and past** campus events
- Filter by **category** (Hackathon, Workshop, Seminar, Cultural, Sports)
- **RSVP system** — Going / Interested / Remove RSVP
- Live attendee counts
- **Create events** from a modal form (online/in-person, max attendees, registration deadline)

### 📌 Notice Board
- Post **notices and announcements** with categories (Internship, Academic, Opportunity, Urgent, etc.)
- Tag system for searchable notices
- **Pinned notices** appear at the top
- **Expiry dates** for time-limited posts
- View count tracking

### 📚 Study Resources
- Share **notes, videos, books, articles, and tools**
- Filter by **type, department, semester, subject**
- Sort by **newest or most liked**
- **Like resources** (toggle)
- Direct external link to resource

### 👏 Skill Endorsements
- Endorse a peer for any skill listed on their profile
- Endorsement counts visible on profile with progress bar
- Toggle to **remove endorsement**
- Fires a **notification** to the endorsed student

### 🏆 Leaderboard
- **Most Connected** — ranked by number of accepted connections
- **Top Builders** — ranked by total project likes
- **Most Endorsed** — ranked by total endorsements received
- **Most Active** — ranked by group participation
- Top 3 **podium display**, full table below

### 🔔 Notifications
- Real-time **notification bell** in navbar with unread count badge
- Inline preview of latest 6 notifications in a dropdown
- Full **notifications page** with type-based icons and timestamps
- One-click **mark all as read**
- Notification types: connection request/accepted, project like, project comment, join request, endorsement, new message

### 📊 Dashboard
- **7 live stat cards** — connections, groups, projects, likes, endorsements, pending requests, unread messages
- **Activity feed** showing campus-wide recent actions
- **Pending requests** with inline accept/reject
- **My Projects** with edit/view links and join-request indicators
- **People You May Know** (smart suggestion — excludes existing connections)
- **Upcoming Events** from your RSVPs
- **Recent Notices** preview
- **Quick Actions** panel linking to all major features

### 📬 Contact & Support
- Working **contact form** saved to database
- Subject categories for proper routing
- **FAQ accordion** with 6 common questions
- Multiple contact method cards (Email, Phone, Location, Report Abuse)

### 🔒 Security
- **CSRF tokens** on all POST forms
- **bcrypt password hashing** (`PASSWORD_BCRYPT`)
- **PDO prepared statements** — no SQL injection
- `htmlspecialchars()` on all output — no XSS
- **Session-based authentication**
- University email required for registration
- Cascade deletes for account removal

---

## 🖼 Screenshots

> Screenshots are taken from the live running app on localhost.

| Page | Description |
|------|-------------|
| 🏠 Landing Page | Editorial hero, features, student grid, groups, stats, CTA |
| 📊 Dashboard | Stats, activity feed, quick actions, suggestions |
| 👥 Students | Search + filter grid with connection status |
| 💡 Projects | Category-filtered showcase with likes and comments |
| 📅 Events | Upcoming/past events with RSVP buttons |
| 📌 Notices | Tagged announcements with pinning and expiry |
| 📚 Resources | Study material filtered by dept/sem/type |
| 🏆 Leaderboard | Tabbed rankings with podium display |
| 💬 Messages | Inbox sidebar + real-time-style chat thread |
| 👤 Profile | Editable profile with endorsements and projects |

---

## 🚀 Installation

### Prerequisites

Make sure you have one of the following installed:
- **XAMPP** (Windows/macOS/Linux) — [Download](https://apachefriends.org)
- **WAMP** (Windows) — [Download](https://wampserver.com)
- **MAMP** (macOS) — [Download](https://mamp.info)
- **LAMP** (Linux) — install via your package manager

PHP 8.0+ and MySQL 8.0+ are required.

---

### Step 1 — Clone or Download

```bash
# Clone via Git
git clone https://github.com/Saqib-Hussain-07/campusconnect.git

# Or download the ZIP and extract
```

---

### Step 2 — Place in Web Root

| Server | Web Root Path |
|--------|--------------|
| XAMPP (Windows) | `C:\xampp\htdocs\campusconnect\` |
| XAMPP (macOS) | `/Applications/XAMPP/htdocs/campusconnect/` |
| WAMP | `C:\wamp64\www\campusconnect\` |
| MAMP | `/Applications/MAMP/htdocs/campusconnect/` |
| LAMP | `/var/www/html/campusconnect/` |

---

### Step 3 — Start Your Server

Open **XAMPP Control Panel** and start:
- ✅ **Apache**
- ✅ **MySQL**

---

### Step 4 — Import the Database

1. Open your browser and go to `http://localhost/phpmyadmin`
2. Click **"New"** in the left sidebar
3. Create a database named **`campusconnect`**
4. Select it, then click **Import** tab
5. Import **`database.sql`** first
6. Then import **`database_update.sql`**

Or via command line:

```bash
mysql -u root -p campusconnect < database.sql
mysql -u root -p campusconnect < database_update.sql
```

---

### Step 5 — Configure the App

Open `includes/config.php` and update if needed:

```php
define('DB_HOST',  'localhost');
define('DB_USER',  'root');
define('DB_PASS',  '');           // Add your MySQL password if set
define('DB_NAME',  'campusconnect');
define('SITE_URL', 'http://localhost/campusconnect');
```

---

### Step 6 — Open in Browser

```
http://localhost/campusconnect
```

---

### 🔑 Demo Login Credentials

| Name | Email | Password | Department |
|------|-------|----------|------------|
| Priya Sharma | `priya@iitmumbai.edu` | `password123` | Computer Science |
| Arjun Patel | `arjun@nitsurat.edu` | `password123` | Mechanical Engineering |
| Sneha Reddy | `sneha@spjimr.edu` | `password123` | Business Administration |
| Vikram Das | `vikram@nift.edu` | `password123` | UX Design |
| Meera Joshi | `meera@bits.edu` | `password123` | Computer Science |
| Karthik Nair | `karthik@vit.edu` | `password123` | Mechanical Engineering |

---

## 🗄 Database Schema

The project uses **19 tables** across two SQL files.

### Core Tables (`database.sql`)

| Table | Description |
|-------|-------------|
| `users` | Student accounts — name, email, password, department, semester, university, skills, bio |
| `groups_list` | Study groups, project teams, and forums |
| `group_members` | Many-to-many: users ↔ groups |
| `connections` | Friend/connection requests with status (pending / accepted / rejected) |
| `messages` | Direct messages between users with read status |
| `newsletter` | Email subscriptions |
| `contact_messages` | Contact form submissions |

### Extended Tables (`database_update.sql`)

| Table | Description |
|-------|-------------|
| `projects` | Student project showcase (title, tech stack, GitHub, live URL, category, status, likes, views) |
| `project_likes` | Many-to-many: users ↔ liked projects |
| `project_comments` | Comments on projects |
| `project_requests` | Join-team requests on projects |
| `events` | Campus events and hackathons with RSVP tracking |
| `event_rsvps` | User RSVP status per event (going / interested / not going) |
| `notices` | Campus notices and announcements with tags and expiry |
| `endorsements` | Skill endorsements between students |
| `resources` | Shared study resources (notes, videos, books, tools) |
| `resource_likes` | Many-to-many: users ↔ liked resources |
| `activity_feed` | Campus-wide activity log |
| `notifications` | Per-user notification inbox |

### Entity Relationship Summary

```
users ─────────┬──── connections (self-referential)
               ├──── messages (self-referential)
               ├──── group_members ──── groups_list
               ├──── projects ──┬── project_likes
               │                ├── project_comments
               │                └── project_requests
               ├──── events ──── event_rsvps
               ├──── notices
               ├──── endorsements (self-referential)
               ├──── resources ──── resource_likes
               ├──── activity_feed
               └──── notifications
```

---

## 📁 File Structure

```
campusconnect/
│
├── 📄 index.php                    # Landing page (hero, features, students, groups, stats, CTA)
├── 📄 database.sql                 # Core schema + seed data (import first)
├── 📄 database_update.sql          # Extended schema + seed data (import second)
│
├── 📁 includes/                    # Shared PHP partials
│   ├── config.php                  # DB connection, session helpers, CSRF, flash messages
│   ├── header.php                  # Navbar, ticker, notification bell, mobile menu
│   └── footer.php                  # Footer, newsletter form, floating chat button
│
├── 📁 assets/
│   ├── css/
│   │   └── style.css               # Full design system (Bootstrap 5 + custom editorial CSS)
│   └── js/
│       └── main.js                 # Scroll reveal, counters, filters, tabs, smooth scroll
│
└── 📁 pages/
    │
    ├── ── Auth ──────────────────────────────────────────────────────
    ├── register.php                # Sign up with validation (name, email, dept, skills, bio)
    ├── login.php                   # Login with bcrypt verification + demo credentials shown
    ├── logout.php                  # Marks user offline, destroys session
    ├── change_password.php         # Verify current password, update to new
    ├── delete_account.php          # Permanently delete account (cascade)
    │
    ├── ── Core Pages ────────────────────────────────────────────────
    ├── dashboard.php               # Stats, feed, requests, projects, suggestions, events
    ├── profile.php                 # Edit profile info, change password, danger zone
    ├── students.php                # Browse/search/filter students with pagination
    ├── view_student.php            # Public profile: bio, skills, endorsements, projects, groups
    ├── groups.php                  # Browse + filter groups, create group modal
    ├── messages.php                # Inbox sidebar + chat thread + send message
    │
    ├── ── Projects ──────────────────────────────────────────────────
    ├── projects.php                # Browse showcase: filter, sort, like, view
    ├── view_project.php            # Project detail: comments, likes, join request, author card
    ├── add_project.php             # Create / edit project form
    ├── like_project.php            # Toggle like (POST handler)
    │
    ├── ── Events ────────────────────────────────────────────────────
    ├── events.php                  # Upcoming + past events, RSVP, create event modal
    ├── create_event.php            # Save new event (POST handler)
    ├── rsvp_event.php              # Toggle RSVP going/interested (POST handler)
    │
    ├── ── Notice Board ──────────────────────────────────────────────
    ├── notices.php                 # Browse notices with categories, tags, expiry
    ├── post_notice.php             # Save new notice (POST handler)
    │
    ├── ── Study Resources ───────────────────────────────────────────
    ├── resources.php               # Browse resources: filter by type/dept/sem/sort
    ├── post_resource.php           # Save shared resource (POST handler)
    ├── like_resource.php           # Toggle like (POST handler)
    │
    ├── ── Social Features ───────────────────────────────────────────
    ├── send_connection.php         # Send connection request (POST handler)
    ├── handle_connection.php       # Accept or reject request (POST handler)
    ├── endorse.php                 # Toggle skill endorsement (POST handler)
    ├── join_group.php              # Join a group (POST handler)
    ├── create_group.php            # Create a new group (POST handler)
    │
    ├── ── Notifications ─────────────────────────────────────────────
    ├── notifications.php           # Full notification list, marks all read on visit
    ├── mark_read.php               # Mark all notifications read (GET handler)
    │
    ├── ── Discovery ─────────────────────────────────────────────────
    ├── leaderboard.php             # 4-tab rankings: Connected, Builders, Endorsed, Active
    ├── contact.php                 # Contact form (saved to DB) + FAQ accordion
    └── newsletter.php              # Newsletter subscribe (POST handler)
```

**Total: 37 PHP files + 1 CSS + 1 JS + 2 SQL = 41 files**

---

## 🎨 Design System

CampusConnect uses a custom editorial design language built on top of Bootstrap 5.

### Color Palette

| Token | Value | Usage |
|-------|-------|-------|
| `--ink` | `#0d0d0d` | Primary text, borders, buttons |
| `--paper` | `#f5f0e8` | Page background, warm off-white |
| `--cream` | `#ede8d8` | Subtle borders, hover backgrounds |
| `--rust` | `#c94f2c` | Primary accent, CTAs, badges |
| `--moss` | `#2d4a3e` | Success states, security section |
| `--gold` | `#c9a84c` | Endorsements, events, leaderboard |
| `--sky` | `#1a3a5c` | Resources, info states |

### Typography

| Role | Font | Usage |
|------|------|-------|
| Display | **Bebas Neue** | Section headings, large numbers |
| Serif | **DM Serif Display** | Italic accents in headings |
| Body | **Bricolage Grotesque** | All body text, UI elements |
| Mono | **JetBrains Mono** | Labels, badges, meta info, code |

### Key Design Principles
- **Solid borders** over drop shadows — editorial / print-inspired
- **Ink-fill hover effects** — cards and buttons fill with `--ink` on hover
- **Ticker tape** at top — live scrolling platform stats
- **Grid-rule section dividers** — `border-top: 1.5px solid var(--ink)`
- **Custom CSS counters** with eased animation
- **Scroll reveal** on all sections using `IntersectionObserver`

---

## 🔧 Configuration

All configuration is in `includes/config.php`:

```php
// Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');            // Your MySQL password
define('DB_NAME', 'campusconnect');

// Site
define('SITE_NAME', 'CampusConnect');
define('SITE_URL',  'http://localhost/campusconnect');
```

### Changing the Site URL

If running on a live server, update `SITE_URL` to your domain:

```php
define('SITE_URL', 'https://campusconnect.yourdomain.com');
```

---

## 📋 Usage Guide

### For Students

1. **Register** at `/pages/register.php` with your university email
2. **Complete your profile** — add skills, bio, semester, university
3. **Browse Students** — search by department or skill, send connection requests
4. **Post a Project** — add your GitHub projects to the showcase
5. **Join Groups** — find study circles, project teams, or open forums
6. **RSVP to Events** — hackathons, workshops, and campus events
7. **Share Resources** — upload study notes, video links, or useful tools
8. **Endorse Peers** — click any skill on a student's profile to endorse them
9. **Message Connections** — start a direct conversation from any profile
10. **Check the Leaderboard** — see the most connected and active students

### For Admins / Faculty

- Access `phpmyadmin` to view all `contact_messages` submitted via the contact form
- Manage pinned notices directly in the `notices` table
- Monitor `activity_feed` for platform usage statistics
- View `newsletter` subscriptions for announcements

---

## 🔐 Security Features

| Feature | Implementation |
|---------|---------------|
| Password Hashing | `password_hash()` with `PASSWORD_BCRYPT` |
| SQL Injection Prevention | PDO prepared statements with `?` placeholders |
| XSS Prevention | `htmlspecialchars()` via custom `e()` helper on all output |
| CSRF Protection | Per-session random token validated on every POST request |
| Session Security | `session_start()` in `config.php`, session destroyed on logout |
| Input Validation | Server-side validation on all form fields before DB write |

> ⚠️ **Note:** For production deployment, also configure HTTPS, set `session.cookie_secure = true`, and use environment variables for DB credentials.

---

## 🛠 Tech Stack Details

### Backend (PHP)
- **PDO** with MySQL driver for all database operations
- **Static connection** (`getDB()`) — single connection per request
- Helper functions: `isLoggedIn()`, `requireLogin()`, `currentUser()`, `setFlash()`, `getFlash()`, `e()`
- **POST/Redirect/GET** pattern on all form submissions to prevent double-submit

### Frontend
- **Bootstrap 5.3** — grid, offcanvas, accordion, dropdowns, modals
- **Font Awesome 6.5** — all icons
- **Google Fonts** — Bebas Neue, DM Serif Display, Bricolage Grotesque, JetBrains Mono
- **Vanilla JS** — no jQuery dependency; `IntersectionObserver` for scroll reveal, `fetch`-free

### Database
- **MySQL 8.0** with `utf8mb4` charset for full Unicode support
- `FOREIGN KEY` constraints with `ON DELETE CASCADE` for referential integrity
- `UNIQUE KEY` constraints to prevent duplicate likes, connections, and memberships
- `TIMESTAMP DEFAULT CURRENT_TIMESTAMP` on all tables for audit trail

---

## 🤝 Contributing

Contributions are welcome! Here's how to get started:

```bash
# 1. Fork the repository
# 2. Clone your fork
git clone https://github.com/Saqib-Hussain-07/campusconnect.git

# 3. Create a feature branch
git checkout -b feature/your-feature-name

# 4. Make your changes and commit
git add .
git commit -m "feat: add your feature description"

# 5. Push and open a Pull Request
git push origin feature/your-feature-name
```

### Contribution Guidelines
- Follow the existing code style (no frameworks, plain PHP + PDO)
- Always sanitize output with `e()` and use prepared statements
- Add CSRF token validation to any new POST handler
- Test on XAMPP before submitting a PR
- Keep SQL changes in a migration file, not in `database.sql` directly

### Ideas for Future Features
- [ ] Admin panel with user management and analytics
- [ ] Email notifications via PHPMailer
- [ ] File upload for project screenshots and profile photos
- [ ] Real-time notifications using Server-Sent Events (SSE)
- [ ] Group chat / forum threads
- [ ] Resume / CV builder for students
- [ ] Alumni network section
- [ ] Mobile PWA support
- [ ] Dark mode toggle
- [ ] OAuth login via Google (`.edu` domain restriction)

---

## 🐛 Known Issues & Troubleshooting

### "Database Error" on first load
**Fix:** Make sure MySQL is running in XAMPP and you have imported both `database.sql` and `database_update.sql`.

### Blank page or 500 error
**Fix:** Enable PHP error display by adding to the top of `includes/config.php`:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### Images not loading (profile photos)
**Note:** Profile images use [Picsum Photos](https://picsum.photos) (random placeholder images seeded by name). An internet connection is required to display them. In production, replace with a local file upload system.

### Session errors after code changes
**Fix:** Clear your browser cookies or open an incognito window and log in fresh.

### `database_update.sql` fails with "table already exists"
**Fix:** Run each `CREATE TABLE IF NOT EXISTS` block individually, or drop the `campusconnect` database and reimport both files in order.

---

## 📄 License

This project is licensed under the **MIT License** — see [LICENSE](LICENSE) for details.

---

## 👨‍💻 Author

Built with ❤️ as a college project demonstrating a full-stack PHP + MySQL web application.

| | |
|--|--|
| **Stack** | PHP · MySQL · Bootstrap 5 · JavaScript |
| **Server** | Apache via XAMPP |
| **Design** | Editorial / Print-inspired UI |
| **Lines of Code** | ~4,500+ (PHP) · 733 (CSS) · 110 (JS) · 450 (SQL) |
| **Pages** | 37 PHP files |
| **DB Tables** | 19 tables |

---

## 🙏 Acknowledgements

- [Bootstrap](https://getbootstrap.com) — CSS framework
- [Font Awesome](https://fontawesome.com) — Icon library
- [Google Fonts](https://fonts.google.com) — Bebas Neue, DM Serif Display, Bricolage Grotesque, JetBrains Mono
- [Picsum Photos](https://picsum.photos) — Placeholder images for demo

---

<div align="center">

**⭐ Star this repository if you found it useful!**

Made for students, by students.

</div>
