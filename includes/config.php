<?php
// ============================================================
//  includes/config.php  — Database connection & site config
// ============================================================
define('DB_HOST',     'localhost');
define('DB_USER',     'root');
define('DB_PASS',     '');          // change if your MySQL has a password
define('DB_NAME',     'campusconnect');
define('SITE_NAME',   'CampusConnect');
define('SITE_URL',    'http://localhost/campusconnect');

// ── PDO connection ───────────────────────────────────────────
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('<div class="alert alert-danger m-4"><strong>Database Error:</strong> '
                .htmlspecialchars($e->getMessage())
                .'<br>Make sure XAMPP MySQL is running and you have imported <code>database.sql</code>.</div>');
        }
    }
    return $pdo;
}

// ── Session helpers ───────────────────────────────────────────
session_start();

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Returns current user row with session caching to avoid a DB hit on every page.
 * Cache is busted by calling currentUser(true) after profile updates.
 */
function currentUser(bool $forceRefresh = false): ?array {
    if (!isLoggedIn()) return null;
    if ($forceRefresh || !isset($_SESSION['_user_cache'])) {
        $db = getDB();
        $st = $db->prepare('SELECT * FROM users WHERE id = ?');
        $st->execute([$_SESSION['user_id']]);
        $row = $st->fetch() ?: null;
        $_SESSION['_user_cache'] = $row;
    }
    return $_SESSION['_user_cache'];
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: '.SITE_URL.'/pages/login.php');
        exit;
    }
}

// ── Flash messages ────────────────────────────────────────────
function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

// ── Sanitise output ───────────────────────────────────────────
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// ── Rate limiting (login brute-force protection) ──────────────
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_SECS', 300); // 5 minutes

function checkRateLimit(string $key): bool {
    $data = $_SESSION['_rl'][$key] ?? ['count' => 0, 'first' => time()];
    if ((time() - $data['first']) > LOGIN_LOCKOUT_SECS) {
        $data = ['count' => 0, 'first' => time()];
    }
    return $data['count'] < LOGIN_MAX_ATTEMPTS;
}
function incrementRateLimit(string $key): void {
    $data = $_SESSION['_rl'][$key] ?? ['count' => 0, 'first' => time()];
    if ((time() - $data['first']) > LOGIN_LOCKOUT_SECS) {
        $data = ['count' => 0, 'first' => time()];
    }
    $data['count']++;
    $_SESSION['_rl'][$key] = $data;
}
function clearRateLimit(string $key): void {
    unset($_SESSION['_rl'][$key]);
}
function rateLimitSecondsLeft(string $key): int {
    $data = $_SESSION['_rl'][$key] ?? null;
    if (!$data) return 0;
    $elapsed = time() - $data['first'];
    return max(0, LOGIN_LOCKOUT_SECS - $elapsed);
}

// ── Avatar helper ─────────────────────────────────────────────
/**
 * Returns the <img src> URL for a user avatar.
 * Falls back to picsum if no real upload exists.
 */
function avatarUrl(array $user): string {
    $av = $user['avatar'] ?? 'default.jpg';
    $path = __DIR__ . '/../assets/uploads/avatars/' . $av;
    if ($av !== 'default.jpg' && file_exists($path)) {
        return SITE_URL . '/assets/uploads/avatars/' . rawurlencode($av);
    }
    return 'https://picsum.photos/seed/' . urlencode($user['name']) . '/200/200';
}

// ── Department → Course catalogue (used on register & profile) ───────────────
define('DEPT_COURSES_PHP', [
    'Computer Science Engineering' => [
        ['name'=>'Computer Science',                     'sem'=>8, 'label'=>'B.Tech · 8 Semesters'],
        ['name'=>'CSE – Artificial Intelligence',        'sem'=>8, 'label'=>'B.Tech · 8 Semesters'],
        ['name'=>'CSE – Data Science',                   'sem'=>8, 'label'=>'B.Tech · 8 Semesters'],
        ['name'=>'CSE – Cybersecurity',                  'sem'=>8, 'label'=>'B.Tech · 8 Semesters'],
        ['name'=>'CSE – Cloud Computing',                'sem'=>8, 'label'=>'B.Tech · 8 Semesters'],
        ['name'=>'CSE – IoT',                            'sem'=>8, 'label'=>'B.Tech · 8 Semesters'],
    ],
    'Mechanical Engineering' => [
        ['name'=>'Mechanical Engineering',               'sem'=>8, 'label'=>'B.E. · 8 Semesters'],
        ['name'=>'Mechanical – Robotics & Automation',   'sem'=>8, 'label'=>'B.E. · 8 Semesters'],
        ['name'=>'Mechanical – Automobile',              'sem'=>8, 'label'=>'B.E. · 8 Semesters'],
    ],
    'Electrical Engineering' => [
        ['name'=>'Electrical Engineering',               'sem'=>8, 'label'=>'B.E. · 8 Semesters'],
        ['name'=>'Electrical & Electronics (EEE)',       'sem'=>8, 'label'=>'B.E. · 8 Semesters'],
        ['name'=>'Electrical – Power Systems',           'sem'=>8, 'label'=>'B.E. · 8 Semesters'],
    ],
    'Civil Engineering' => [
        ['name'=>'Civil Engineering',                    'sem'=>8, 'label'=>'B.E. · 8 Semesters'],
        ['name'=>'Civil – Construction Management',      'sem'=>8, 'label'=>'B.E. · 8 Semesters'],
        ['name'=>'Civil – Environmental Engineering',    'sem'=>8, 'label'=>'B.E. · 8 Semesters'],
    ],
    'Information Technology' => [
        ['name'=>'Information Technology',               'sem'=>8, 'label'=>'B.Tech · 8 Semesters'],
        ['name'=>'IT – Cloud & DevOps',                  'sem'=>8, 'label'=>'B.Tech · 8 Semesters'],
        ['name'=>'IT – IoT & Embedded Systems',          'sem'=>8, 'label'=>'B.Tech · 8 Semesters'],
    ],
    'Electronics' => [
        ['name'=>'Electronics & Communication',          'sem'=>8, 'label'=>'B.E. · 8 Semesters'],
        ['name'=>'Electronics – VLSI Design',            'sem'=>8, 'label'=>'B.E. · 8 Semesters'],
        ['name'=>'Electronics – Embedded Systems',       'sem'=>8, 'label'=>'B.E. · 8 Semesters'],
    ],
    'Chemical Engineering' => [
        ['name'=>'Chemical Engineering',                 'sem'=>8, 'label'=>'B.E. · 8 Semesters'],
        ['name'=>'Chemical – Petrochemical',             'sem'=>8, 'label'=>'B.E. · 8 Semesters'],
    ],
    'Data Science' => [
        ['name'=>'Data Science',                         'sem'=>6, 'label'=>'B.Sc. · 6 Semesters'],
        ['name'=>'Data Science & Analytics',             'sem'=>6, 'label'=>'B.Sc. · 6 Semesters'],
        ['name'=>'Statistics & Data Science',            'sem'=>6, 'label'=>'B.Sc. · 6 Semesters'],
    ],
    'Business Administration' => [
        ['name'=>'Business Administration',              'sem'=>6, 'label'=>'BBA · 6 Semesters'],
        ['name'=>'BBA – Finance',                        'sem'=>6, 'label'=>'BBA · 6 Semesters'],
        ['name'=>'BBA – Marketing',                      'sem'=>6, 'label'=>'BBA · 6 Semesters'],
        ['name'=>'BBA – Human Resources',                'sem'=>6, 'label'=>'BBA · 6 Semesters'],
        ['name'=>'MBA',                                  'sem'=>4, 'label'=>'MBA · 4 Semesters (2 Yrs)'],
        ['name'=>'MBA – Finance',                        'sem'=>4, 'label'=>'MBA · 4 Semesters (2 Yrs)'],
        ['name'=>'MBA – Marketing',                      'sem'=>4, 'label'=>'MBA · 4 Semesters (2 Yrs)'],
        ['name'=>'MBA – Business Analytics',             'sem'=>4, 'label'=>'MBA · 4 Semesters (2 Yrs)'],
    ],
    'UX & Design' => [
        ['name'=>'UX Design',                            'sem'=>8, 'label'=>'B.Des · 8 Semesters'],
        ['name'=>'UX – Interaction Design',              'sem'=>8, 'label'=>'B.Des · 8 Semesters'],
        ['name'=>'UX – Product Design',                  'sem'=>8, 'label'=>'B.Des · 8 Semesters'],
    ],
    'MCA (Postgraduate)' => [
        ['name'=>'MCA',                                  'sem'=>4, 'label'=>'MCA · 4 Semesters (2 Yrs)'],
        ['name'=>'MCA – Artificial Intelligence',        'sem'=>4, 'label'=>'MCA · 4 Semesters (2 Yrs)'],
        ['name'=>'MCA – Data Science & Analytics',       'sem'=>4, 'label'=>'MCA · 4 Semesters (2 Yrs)'],
        ['name'=>'MCA – Cloud Computing',                'sem'=>4, 'label'=>'MCA · 4 Semesters (2 Yrs)'],
        ['name'=>'MCA – Cybersecurity',                  'sem'=>4, 'label'=>'MCA · 4 Semesters (2 Yrs)'],
        ['name'=>'MCA – Software Engineering',           'sem'=>4, 'label'=>'MCA · 4 Semesters (2 Yrs)'],
    ],
]);
