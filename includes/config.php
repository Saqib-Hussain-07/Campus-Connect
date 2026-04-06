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
function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    $db = getDB();
    $st = $db->prepare('SELECT * FROM users WHERE id = ?');
    $st->execute([$_SESSION['user_id']]);
    return $st->fetch() ?: null;
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
